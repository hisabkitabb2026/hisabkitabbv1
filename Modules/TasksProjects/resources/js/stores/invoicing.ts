import { reactive } from 'vue'
import type { ConfirmItem } from '@/types/billing'

/**
 * What the invoicing sequence has in flight, shared by every screen that can
 * start one.
 *
 * A module bundle has no Pinia, so this is a plain reactive singleton. Three
 * facts live here rather than in a component, because all three outlive the
 * screen that caused them:
 *
 * - `busy` is one lock for the whole module. Invoicing is four requests long,
 *   and a second click on another row while the first is still running would
 *   race the same time entries onto two invoices.
 * - `pending` is an invoice the host created whose entries were not stamped.
 *   It is the one state the user has to resolve, because the invoice exists
 *   and the time still reads as unbilled, so it is kept until the retry works
 *   rather than lost with the page.
 * - `allowed` is what a 403 taught us. The module settings endpoint does not
 *   say whether the caller may invoice, so the first refusal does, and the
 *   actions stop offering themselves for the rest of the session.
 */

/** An invoice that exists, with the stamp that did not land on its entries. */
export interface PendingStamp {
  invoiceId: number
  /** For the banner, so it names the invoice the user is looking at. */
  invoiceNumber: string
  items: ConfirmItem[]
}

/** A number the sequence is waiting for the user to type. */
interface NumberPrompt {
  suggested: string
  resolve: (value: string | null) => void
}

interface InvoicingState {
  /** True while a prepare-create-confirm sequence is running. */
  busy: boolean
  pending: PendingStamp | null
  /** False once the server has refused the ability once. */
  allowed: boolean
  prompt: NumberPrompt | null
}

export const invoicingStore = reactive<InvoicingState>({
  busy: false,
  pending: null,
  allowed: true,
  prompt: null,
})

/** Take the module-wide lock, or say that someone else holds it. */
export function lockInvoicing(): boolean {
  if (invoicingStore.busy) {
    return false
  }

  invoicingStore.busy = true

  return true
}

export function unlockInvoicing(): void {
  invoicingStore.busy = false
}

/** Remember an invoice whose entries are still unstamped. */
export function holdStamp(pending: PendingStamp): void {
  invoicingStore.pending = pending
}

export function clearStamp(): void {
  invoicingStore.pending = null
}

/** The server refused the ability, so stop offering the action. */
export function denyInvoicing(): void {
  invoicingStore.allowed = false
}

/**
 * Ask the user for the invoice number and wait for the answer.
 *
 * The modal that answers is mounted once in the company layout rather than by
 * every screen that can invoice, so the sequence can ask from anywhere without
 * each caller carrying a dialog of its own. A second ask while one is open
 * cancels the first, which cannot happen while `busy` holds but keeps the
 * promise from being dropped if it ever did.
 */
export function askInvoiceNumber(suggested: string): Promise<string | null> {
  answerInvoiceNumber(null)

  return new Promise<string | null>((resolve) => {
    invoicingStore.prompt = { suggested, resolve }
  })
}

/** Hand the sequence the number, or null when the user backed out. */
export function answerInvoiceNumber(value: string | null): void {
  const prompt = invoicingStore.prompt

  if (prompt === null) {
    return
  }

  invoicingStore.prompt = null
  prompt.resolve(value)
}

/** Forget the previous company: invoices and abilities belong to one. */
export function resetInvoicing(): void {
  answerInvoiceNumber(null)
  invoicingStore.busy = false
  invoicingStore.pending = null
  invoicingStore.allowed = true
}
