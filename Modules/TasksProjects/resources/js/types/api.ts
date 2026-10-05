/** Shapes the host and the module share on every list endpoint. */

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

/** A Laravel resource collection over a paginator. */
export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

/** A single Laravel resource, which the host always wraps in `data`. */
export interface Wrapped<T> {
  data: T
}

/**
 * A host contact, as `/api/v1/customers` renders it. Only the fields the
 * project form needs are typed; the endpoint returns many more.
 */
export interface Customer {
  id: number
  name: string | null
  display_name?: string | null
  currency_id: number | null
}
