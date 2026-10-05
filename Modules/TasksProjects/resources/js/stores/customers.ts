import { reactive } from 'vue'
import type { AxiosInstance } from 'axios'
import { listCustomers } from '@/api'
import type { Customer } from '@/types/api'

/**
 * A display name for every contact id the module renders.
 *
 * Projects, tasks and time all carry a bare `customer_id`, so the lists would
 * otherwise read `#42`. The names come from one host request per company
 * session, kept here where the projects table, the board and the reports page
 * all reach them.
 *
 * Everything degrades rather than throws: reading contacts needs a host
 * ability the caller may not have, and a contact deleted since keeps its id as
 * its label rather than blanking the row.
 */

/**
 * How many contacts one lookup asks the host for.
 *
 * The map exists to label ids the module already holds, not to browse the
 * address book, so it is deliberately capped: a company with more contacts
 * than this still gets names for most rows, and anything past the cap falls
 * back to `#id`. Forms keep their search-based picker, which is not capped and
 * is the right tool for choosing a contact.
 */
export const CUSTOMER_LIMIT = 200

const names = reactive<Record<number, string>>({})

let loaded = false
let inFlight: Promise<void> | null = null

/** The cached name, or a stable `#id` placeholder to render meanwhile. */
export function customerName(id: number | null): string {
  if (id === null) {
    return ''
  }

  return names[id] ?? `#${id}`
}

/** Whether a name for this id has arrived, for a caller that hides the rest. */
export function hasCustomerName(id: number | null): boolean {
  return id !== null && names[id] !== undefined
}

/**
 * Read the company's contacts, once.
 *
 * Several views ask on the same page, so the first call owns the request and
 * the rest wait on it. A failed read leaves the map empty and unlatched, so
 * the next view to need a name tries again.
 */
export async function ensureLoaded(client: AxiosInstance): Promise<void> {
  if (loaded) {
    return
  }

  inFlight ??= load(client)

  await inFlight
}

/** Forget the previous company's contacts: ids belong to one company. */
export function resetCustomers(): void {
  for (const key of Object.keys(names)) {
    delete names[Number(key)]
  }

  loaded = false
  inFlight = null
}

async function load(client: AxiosInstance): Promise<void> {
  try {
    for (const customer of await listCustomers(client, CUSTOMER_LIMIT)) {
      const id = customer?.id

      if (typeof id === 'number') {
        names[id] = labelFor(customer)
      }
    }

    loaded = true
  } catch {
    // The caller may not read contacts; the ids stay as their own labels.
  } finally {
    inFlight = null
  }
}

/**
 * What a contact is called, checked rather than trusted: this is host data
 * crossing a module boundary, and `display_name` is not on every host version.
 */
function labelFor(customer: Customer): string {
  const display = typeof customer.display_name === 'string' ? customer.display_name.trim() : ''

  if (display !== '') {
    return display
  }

  const name = typeof customer.name === 'string' ? customer.name.trim() : ''

  return name !== '' ? name : `#${customer.id}`
}
