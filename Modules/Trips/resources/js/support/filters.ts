import type { LocationQuery, LocationQueryRaw } from 'vue-router'

/**
 * The Trips screen's filters, as the address bar carries them.
 *
 * Everything is a string because that is what a query string holds and what a
 * link has to round-trip: a view switch, a reload and a bookmark all go through
 * the URL, so keeping one representation removes every conversion but the one
 * at the edge.
 */
export interface TripFilterState {
  /** A customer id, or '' for every customer. */
  customer: string
  /** A status id, or '' for every status. */
  status: string
  search: string
  /** '1' for unbilled-only, '' otherwise. */
  unbilled: string
}

export const EMPTY_FILTERS: TripFilterState = {
  customer: '',
  status: '',
  search: '',
  unbilled: '',
}

/** The filters a route carries, with anything unrecognised dropped. */
export function readFilters(query: LocationQuery): TripFilterState {
  return {
    customer: numeric(query.customer),
    status: numeric(query.status),
    search: single(query.search).slice(0, 200),
    unbilled: query.unbilled === '1' ? '1' : '',
  }
}

/**
 * The query a link should carry.
 *
 * Empty filters are left out rather than written as blanks, so an unfiltered
 * Trips screen has a clean URL and two links to the same view compare equal.
 */
export function filterQuery(filters: TripFilterState): LocationQueryRaw {
  const query: LocationQueryRaw = {}

  for (const key of ['customer', 'status', 'search', 'unbilled'] as const) {
    if (filters[key] !== '') {
      query[key] = filters[key]
    }
  }

  return query
}

/** Whether anything is filtered, for the "clear" affordance. */
export function hasFilters(filters: TripFilterState): boolean {
  return (
    filters.customer !== '' ||
    filters.status !== '' ||
    filters.search !== '' ||
    filters.unbilled !== ''
  )
}

/**
 * The filters as one comparable string.
 *
 * A watcher on the object itself would fire on every route change, because the
 * screen above rebuilds it from the query each time. Comparing the four values
 * fires when they really differ, which is when a list is worth asking for
 * again.
 */
export function filterKey(filters: TripFilterState): string {
  return [filters.customer, filters.status, filters.search, filters.unbilled].join('|')
}

export function sameFilters(left: TripFilterState, right: TripFilterState): boolean {
  return (
    left.customer === right.customer &&
    left.status === right.status &&
    left.search === right.search &&
    left.unbilled === right.unbilled
  )
}

/** A filter value as the id it names, or null when it names nothing. */
export function idOf(value: string): number | null {
  const id = Number(value)

  return value !== '' && Number.isInteger(id) && id > 0 ? id : null
}

/** The filters as the board API takes them. */
export function boardParams(filters: TripFilterState): {
  search?: string
  customer_id?: number
  status_id?: number
  unbilled_only?: boolean
} {
  const params: {
    search?: string
    customer_id?: number
    status_id?: number
    unbilled_only?: boolean
  } = {}

  const customerId = idOf(filters.customer)

  if (customerId !== null) {
    params.customer_id = customerId
  }

  const statusId = idOf(filters.status)

  if (statusId !== null) {
    params.status_id = statusId
  }

  if (filters.search !== '') {
    params.search = filters.search
  }

  if (filters.unbilled === '1') {
    params.unbilled_only = true
  }

  return params
}

function single(value: LocationQuery[string]): string {
  const first = Array.isArray(value) ? value[0] : value

  return typeof first === 'string' ? first.trim() : ''
}

function numeric(value: LocationQuery[string]): string {
  const text = single(value)

  return idOf(text) === null ? '' : text
}
