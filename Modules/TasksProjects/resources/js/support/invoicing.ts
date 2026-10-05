import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import {
  confirmInvoice,
  createInvoice,
  fetchBillingCustomer,
  fetchCompanyInvoiceDefaults,
  fetchExchangeRate,
  fetchNextInvoiceNumber,
  listInvoiceTemplates,
  prepareInvoice,
} from '@/api/billing'
import {
  askInvoiceNumber,
  clearStamp,
  denyInvoicing,
  holdStamp,
  invoicingStore,
  lockInvoicing,
  unlockInvoicing,
} from '@/stores/invoicing'
import { bumpTaskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { toDateString } from '@/support/format'
import { errorCode, errorStatus } from '@/support/http'
import type { Translate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type {
  BillingSelection,
  CompanyInvoiceDefaults,
  ConfirmItem,
  CreatedInvoice,
  InvoicePayload,
  PreparedInvoice,
} from '@/types/billing'

/**
 * Turning a selection of work into a draft invoice, in one sequence.
 *
 * Every entry point in the module, a task row, the bulk bar, a task page, a
 * project header and the unbilled time page, runs this and nothing else, so
 * "invoice this" means the same four steps wherever it is pressed:
 *
 * 1. `billing/prepare` turns the selection into the body the host takes.
 * 2. The host's own defaults fill in what its create form would have: the due
 *    date, the template, the number and an exchange rate when the contact does
 *    not settle in the company currency.
 * 3. The host's `POST /api/v1/invoices` writes the invoice, with the session's
 *    own client, so the module never touches the host invoice tables.
 * 4. `billing/confirm` stamps the entries with the line ids that came back,
 *    and the user lands on the host's edit page for the draft.
 *
 * Nothing here throws at a caller: every refusal becomes a notification, and
 * the one failure that leaves work behind, a created invoice whose entries were
 * not stamped, is parked in the store for `InvoiceRetryBanner` to finish.
 */

/** Where the host mounts the invoice screens. */
const INVOICES = '/admin/invoices'

/** Flip to trace the sequence in the console while working on it. */
const DEBUG = false

export interface InvoicingDeps {
  client: AxiosInstance
  /** The host router, which module pages receive as a prop. */
  router: Router
  notify: Notify
  t: Translate
}

/**
 * Invoice a selection and open the draft.
 *
 * Answers whether an invoice was created and stamped, so a caller that wants
 * to refresh something of its own knows whether anything changed. The task
 * lists refresh themselves: the sequence bumps the shared task version.
 */
export async function invoiceTasks(
  deps: InvoicingDeps,
  selection: BillingSelection,
): Promise<boolean> {
  const { notify, t } = deps

  // An invoice that exists but whose time still reads as unbilled has to be
  // finished before another is started, or the same hours reach two invoices.
  if (invoicingStore.pending !== null) {
    notify('warning', t('tasks_projects.billing.pending_stamp'))

    return false
  }

  if (!lockInvoicing()) {
    return false
  }

  try {
    return await run(deps, selection)
  } finally {
    unlockInvoicing()
  }
}

/**
 * Stamp the entries of an invoice that was created but never confirmed.
 *
 * `billing/confirm` is idempotent, so this is always safe to press again, and
 * it is the only way back from a half-finished invoice that does not risk a
 * second one.
 */
export async function retryStamp(
  client: AxiosInstance,
  notify: Notify,
  t: Translate,
): Promise<boolean> {
  const pending = invoicingStore.pending

  if (pending === null || !lockInvoicing()) {
    return false
  }

  try {
    const stamped = await confirmInvoice(client, pending.invoiceId, pending.items)

    debug('stamped on retry', stamped)
    clearStamp()
    bumpTaskVersion()
    notify('success', t('tasks_projects.billing.stamped', { count: stamped }))

    return true
  } catch (error: unknown) {
    notify('error', errorMessage(error, t('tasks_projects.billing.stamp_failed')))

    return false
  } finally {
    unlockInvoicing()
  }
}

/** Where the host shows an invoice that exists, for links out of the module. */
export function invoiceViewPath(invoiceId: number): string {
  return `${INVOICES}/${invoiceId}/view`
}

async function run(deps: InvoicingDeps, selection: BillingSelection): Promise<boolean> {
  const { client, notify, t } = deps

  let prepared: PreparedInvoice

  try {
    prepared = await prepareInvoice(client, selection)
  } catch (error: unknown) {
    reportPrepareFailure(deps, error)

    return false
  }

  debug('prepared', prepared)

  if (!Array.isArray(prepared.items) || prepared.items.length === 0) {
    notify('warning', t('tasks_projects.billing.nothing_to_invoice'))

    return false
  }

  // All three are the host's own answers and none of them is fatal: a company
  // whose bootstrap or template list cannot be read still gets an invoice,
  // with the same fields its create form would have left blank.
  const [defaults, templates, customer] = await Promise.all([
    fetchCompanyInvoiceDefaults(client).catch((): null => null),
    listInvoiceTemplates(client).catch((): [] => []),
    fetchBillingCustomer(client, prepared.customer_id).catch((): null => null),
  ])

  const currencyId = customer?.currency_id ?? customer?.currency?.id ?? prepared.currency_id
  const homeCurrencyId = defaults?.currency?.id ?? null
  const foreign = homeCurrencyId !== null && currencyId !== null && currencyId !== homeCurrencyId

  const invoiceNumber = await resolveNumber(deps, defaults, prepared.customer_id)

  if (invoiceNumber === null) {
    return false
  }

  let exchangeRate: number | null = null

  if (foreign && currencyId !== null) {
    exchangeRate = await fetchExchangeRate(client, currencyId).catch((): null => null)

    if (exchangeRate === null) {
      notify('warning', t('tasks_projects.billing.rate_failed'))
    }
  }

  const payload = invoicePayload(prepared, {
    invoiceNumber,
    currencyId,
    exchangeRate,
    dueDate: defaultDueDate(prepared.invoice_date, defaults),
    templateName: defaults?.defaultTemplate ?? templates[0]?.name ?? '',
  })

  debug('creating', payload)

  let invoice: CreatedInvoice

  try {
    invoice = await createInvoice(client, payload)
  } catch (error: unknown) {
    notify('error', errorMessage(error, t('tasks_projects.billing.create_failed')))

    return false
  }

  debug('created', invoice)

  const stamped = await stamp(deps, invoice, prepared)

  bumpTaskVersion()

  if (!stamped) {
    return false
  }

  notify('success', t('tasks_projects.billing.created', { number: invoice.invoice_number }))
  await openInvoice(deps.router, invoice.id)

  return true
}

/**
 * Say why `prepare` refused, in the words of the screen that asked.
 *
 * The three answers worth naming are the ones a person can act on: a selection
 * spanning two customers, a selection with no money in it, and an ability the
 * caller does not have. Everything else keeps the server's own message.
 */
function reportPrepareFailure(deps: InvoicingDeps, error: unknown): void {
  const { notify, t } = deps
  const code = errorCode(error)

  if (code === 'mixed_billing_selection') {
    const customers = customerCount(error)

    // The same refusal covers two customers and two currencies, and only the
    // first names ids, so the counted sentence is used only when it is true.
    notify(
      'error',
      customers > 1
        ? t('tasks_projects.billing.mixed_customers', { count: customers })
        : errorMessage(error, t('tasks_projects.billing.mixed_selection')),
    )

    return
  }

  if (code === 'nothing_to_invoice') {
    notify('warning', t('tasks_projects.billing.nothing_to_invoice'))

    return
  }

  if (errorStatus(error) === 403) {
    denyInvoicing()
    notify('error', t('tasks_projects.billing.forbidden'))

    return
  }

  notify('error', errorMessage(error, t('tasks_projects.billing.prepare_failed')))
}

/** How many customers the refused selection spanned, as the body reported. */
function customerCount(error: unknown): number {
  if (typeof error !== 'object' || error === null) {
    return 0
  }

  const data = (error as { response?: { data?: unknown } }).response?.data

  if (typeof data !== 'object' || data === null) {
    return 0
  }

  const ids = (data as { customer_ids?: unknown }).customer_ids

  return Array.isArray(ids) ? ids.length : 0
}

/**
 * The number the invoice will carry.
 *
 * A company that lets the host number its invoices gets the next one without
 * being asked. One that numbers them by hand, or a host that could not answer,
 * is asked, with whatever the endpoint did say filled in. Backing out of that
 * question is a cancelled invoice, not an invoice with a blank number.
 */
async function resolveNumber(
  deps: InvoicingDeps,
  defaults: CompanyInvoiceDefaults | null,
  customerId: number,
): Promise<string | null> {
  const suggested = await fetchNextInvoiceNumber(deps.client, customerId).catch((): null => null)

  if (defaults?.autoGenerateNumber !== false && suggested !== null) {
    return suggested
  }

  return askInvoiceNumber(suggested ?? '')
}

/** The due date the host's own form would have filled in, or none. */
function defaultDueDate(invoiceDate: string, defaults: CompanyInvoiceDefaults | null): string | null {
  if (defaults === null || !defaults.setDueDateAutomatically) {
    return null
  }

  const due = new Date(`${invoiceDate}T00:00:00`)

  if (Number.isNaN(due.getTime())) {
    return null
  }

  due.setDate(due.getDate() + defaults.dueDateDays)

  return toDateString(due)
}

interface InvoiceFields {
  invoiceNumber: string
  currencyId: number | null
  exchangeRate: number | null
  dueDate: string | null
  templateName: string
}

/**
 * The body the host invoice endpoint takes.
 *
 * Only the keys it validates or stores: the lines arrive with their zeroed
 * discount and tax fields so the host's item writer never reaches for a
 * missing index, and the totals are the module's arithmetic, which the host
 * recomputes from the same lines before it saves anything.
 */
function invoicePayload(payload: PreparedInvoice, fields: InvoiceFields): InvoicePayload {
  return {
    invoice_date: payload.invoice_date,
    due_date: fields.dueDate,
    customer_id: payload.customer_id,
    invoice_number: fields.invoiceNumber,
    // The host stores the contact's currency whatever is sent, and reads this
    // only to decide whether the rate applies, so the contact's is what goes.
    currency_id: fields.currencyId,
    exchange_rate: fields.exchangeRate,
    discount: payload.discount,
    discount_type: payload.discount_type,
    discount_val: payload.discount_val,
    tax: payload.tax,
    sub_total: payload.sub_total,
    total: payload.total,
    tax_included: false,
    notes: payload.notes,
    template_name: fields.templateName,
    items: payload.items.map((item) => ({ ...item })),
    taxes: [],
  }
}

/**
 * Hand the created line ids back to the module.
 *
 * `groups[i]` was produced alongside `items[i]`, and the host writes the lines
 * in the order they were posted, so zipping them positionally pairs each line
 * with the entries behind it. A failure here leaves a live invoice and unbilled
 * time, which the banner offers to fix: the call is idempotent.
 */
async function stamp(
  deps: InvoicingDeps,
  invoice: CreatedInvoice,
  payload: PreparedInvoice,
): Promise<boolean> {
  const { client, notify, t } = deps
  const items = confirmItems(invoice, payload)

  if (items.length === 0) {
    // Nothing to retry with: the host answered without the line ids, so the
    // invoice is real and the time behind it can only be matched by hand.
    notify('error', t('tasks_projects.billing.stamp_unmatched', { number: invoice.invoice_number }))
    await openInvoice(deps.router, invoice.id)

    return false
  }

  try {
    const stamped = await confirmInvoice(client, invoice.id, items)

    debug('stamped', stamped)

    return true
  } catch (error: unknown) {
    holdStamp({ invoiceId: invoice.id, invoiceNumber: invoice.invoice_number, items })
    notify(
      'error',
      errorMessage(
        error,
        t('tasks_projects.billing.stamp_failed_notice', { number: invoice.invoice_number }),
      ),
    )

    return false
  }
}

/** Each created line paired with the entries that produced it. */
function confirmItems(invoice: CreatedInvoice, payload: PreparedInvoice): ConfirmItem[] {
  const lines = Array.isArray(invoice.items) ? invoice.items : []
  const groups = Array.isArray(payload.groups) ? payload.groups : []
  const items: ConfirmItem[] = []

  groups.forEach((group, index) => {
    const line = lines[index]

    if (line && typeof line.id === 'number' && group.entry_ids.length > 0) {
      items.push({ invoice_item_id: line.id, entry_ids: group.entry_ids })
    }
  })

  return items
}

/**
 * Land on the host's edit page, or its view page when the guard refuses.
 *
 * Editing an invoice is its own host ability, and the module's own one does
 * not imply it, so a caller who may invoice but not edit still gets taken to
 * the invoice rather than left on the screen they pressed.
 */
async function openInvoice(router: Router, invoiceId: number): Promise<void> {
  if (await push(router, `${INVOICES}/${invoiceId}/edit`)) {
    return
  }

  await push(router, invoiceViewPath(invoiceId))
}

/** Whether the navigation actually landed. */
async function push(router: Router, path: string): Promise<boolean> {
  try {
    return !(await router.push(path))
  } catch {
    return false
  }
}

function debug(label: string, value: unknown): void {
  if (DEBUG) {
    console.debug(`[tasks-projects] invoicing: ${label}`, value)
  }
}
