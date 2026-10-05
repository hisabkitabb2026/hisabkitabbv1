/**
 * Everything the invoicing flow passes between the module and the host.
 *
 * Money is integer minor units on both sides of the boundary and `quantity` is
 * decimal hours, which is what the host's own invoice form posts. The shapes
 * under "module" come from `billing/*`; the ones under "host" are the host's
 * own invoice, template, number and currency endpoints, typed here only as far
 * as the flow reads them.
 */

import type { Customer } from '@/types/api'

/** How a selection is collapsed into invoice lines. */
export type BillingGrouping = 'task' | 'project' | 'member' | 'summary'

/**
 * What to invoice, in exactly one of the three shapes the endpoint takes.
 *
 * The screens name whichever they know: the unbilled time page ticks entries
 * off, a row, a task page or a bulk selection names tasks, and a project
 * header names a project. They are mutually exclusive, which is what the
 * request rules enforce, so the union is spelled out rather than left as one
 * object with three optional keys.
 */
export interface EntryIdSelection {
  entryIds: number[]
  grouping?: BillingGrouping
}

export interface TaskIdSelection {
  taskIds: number[]
  grouping?: BillingGrouping
}

export interface ProjectSelection {
  projectId: number
  grouping?: BillingGrouping
}

export type BillingSelection = EntryIdSelection | TaskIdSelection | ProjectSelection

/** A customer with time waiting to be invoiced, in one currency. */
export interface UnbilledCustomer {
  customer_id: number
  entries: number
  minutes: number
  amount: number
  currency_id: number | null
}

/** One unbilled entry, with the names the review step shows. */
export interface UnbilledEntry {
  id: number
  task_id: number
  task_name: string
  project_id: number | null
  project_name: string | null
  user_id: number
  user_name: string
  date: string | null
  minutes: number
  amount: number
  rate: number
  currency_id: number | null
  description: string | null
}

/** One row of a grouped view, with the entries behind it. */
export interface UnbilledGroup {
  key: number | null
  label: string
  currency_id: number | null
  rate: number | null
  entry_ids: number[]
  minutes: number
  amount: number
  description: string | null
}

export interface UnbilledCurrencyTotal {
  currency_id: number | null
  minutes: number
  amount: number
}

/** What `billing/unbilled` answers for one customer. */
export interface UnbilledTime {
  customer_id: number
  from: string | null
  to: string | null
  entry_ids: number[]
  minutes: number
  currencies: UnbilledCurrencyTotal[]
  entries: UnbilledEntry[]
  groups: Record<BillingGrouping, UnbilledGroup[]>
}

/** One prepared invoice line. */
export interface PreparedItem {
  name: string
  description: string | null
  quantity: number
  price: number
  discount_type: string
  discount: number
  discount_val: number
  tax: number
  taxes: unknown[]
  total: number
}

/**
 * What `billing/prepare` answers: the invoice body, plus the entries behind
 * each line. `groups[i]` belongs to `items[i]`, which is what lets the created
 * line ids be zipped back onto the entries positionally.
 */
export interface PreparedInvoice {
  invoice_date: string
  customer_id: number
  currency_id: number | null
  discount: number
  discount_type: string
  discount_val: number
  tax: number
  sub_total: number
  total: number
  notes: string | null
  template_name: string | null
  taxes: unknown[]
  items: PreparedItem[]
  groups: { entry_ids: number[] }[]
}

/** One line of the body the host invoice endpoint takes. */
export interface InvoicePayloadItem extends PreparedItem {
  taxes: unknown[]
}

/**
 * The body posted to the host's `POST /api/v1/invoices`.
 *
 * Only the keys the host validates or stores: it recomputes the totals from
 * the lines, so what is sent here is the module's arithmetic offered for
 * checking rather than a figure the host trusts.
 */
export interface InvoicePayload {
  invoice_date: string
  due_date: string | null
  customer_id: number
  invoice_number: string
  currency_id: number | null
  exchange_rate: number | null
  discount: number
  discount_type: string
  discount_val: number
  tax: number
  sub_total: number
  total: number
  tax_included: boolean
  notes: string | null
  template_name: string
  items: InvoicePayloadItem[]
  taxes: unknown[]
}

/** The invoice the host answers with, as far as the module reads it. */
export interface CreatedInvoice {
  id: number
  invoice_number: string
  total: number
  items: { id: number }[]
}

/** A PDF template, as `GET /api/v1/invoices/templates` lists them. */
export interface InvoiceTemplate {
  name: string
  path: string
}

/** Enough of a host currency to render an amount in it. */
export interface CurrencyFormat {
  id: number
  code: string
  symbol: string
  precision: number
  thousand_separator: string
  decimal_separator: string
  swap_currency_symbol?: boolean
}

/** A host contact, with the currency the customer list carries. */
export interface BillingCustomer extends Customer {
  currency?: CurrencyFormat | null
}

/**
 * The company's own invoice defaults, read from the host bootstrap payload.
 *
 * A module bundle cannot reach the host's company store, so the module asks
 * the same endpoint the shell does and keeps only the settings a draft needs:
 * the home currency, the due-date rule, whether numbers generate themselves
 * and which template the user last defaulted to.
 */
export interface CompanyInvoiceDefaults {
  currency: CurrencyFormat | null
  dueDateDays: number
  setDueDateAutomatically: boolean
  autoGenerateNumber: boolean
  defaultTemplate: string | null
}

/** One line of the confirmation, pairing a created line with its entries. */
export interface ConfirmItem {
  invoice_item_id: number
  entry_ids: number[]
}
