import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import type { SortParams } from '@/api'
import type { TaskSortKey } from '@/api/board'
import type { TaskListParams } from '@/types/task'

/**
 * The Tasks screen's filters, as the address bar carries them.
 *
 * Everything is a string because that is what a query string holds and what a
 * link has to round-trip: a view switch, a reload and a bookmark all go
 * through the URL, so keeping one representation removes every conversion but
 * the one at the edge.
 */
export interface TaskFilterState {
  /** A project id, or '' for every project. */
  project: string
  /** A member id, or '' for everyone. */
  user: string
  /** A status id, or the pseudo values below, or '' for every status. */
  status: string
  search: string
}

/** The two status values that are not a board column. */
export const INVOICED_FILTERS = ['uninvoiced', 'invoiced'] as const

export type InvoicedFilter = (typeof INVOICED_FILTERS)[number]

export const EMPTY_FILTERS: TaskFilterState = {
  project: '',
  user: '',
  status: '',
  search: '',
}

/** Whether a status filter names the invoicing state rather than a column. */
export function isInvoicedFilter(status: string): status is InvoicedFilter {
  return status === 'uninvoiced' || status === 'invoiced'
}

/** The filters a route carries, with anything unrecognised dropped. */
export function readFilters(query: LocationQuery): TaskFilterState {
  return {
    project: numeric(query.project),
    user: numeric(query.user),
    status: statusOf(query.status),
    search: single(query.search).slice(0, 200),
  }
}

/**
 * The query a link should carry.
 *
 * Empty filters are left out rather than written as blanks, so an unfiltered
 * Tasks screen has a clean URL and two links to the same view compare equal.
 */
export function filterQuery(filters: TaskFilterState): LocationQueryRaw {
  const query: LocationQueryRaw = {}

  for (const key of ['project', 'user', 'status', 'search'] as const) {
    if (filters[key] !== '') {
      query[key] = filters[key]
    }
  }

  return query
}

/** Whether anything is filtered, for the "clear" affordance. */
export function hasFilters(filters: TaskFilterState): boolean {
  return filters.project !== '' || filters.user !== '' || filters.status !== '' || filters.search !== ''
}

/**
 * The filters as one comparable string.
 *
 * A watcher on the object itself would fire on every route change, because the
 * screen above rebuilds it from the query each time. Comparing the four values
 * fires when they really differ, which is when a list is worth asking for
 * again.
 */
export function filterKey(filters: TaskFilterState): string {
  return [filters.project, filters.user, filters.status, filters.search].join('|')
}

export function sameFilters(left: TaskFilterState, right: TaskFilterState): boolean {
  return (
    left.project === right.project &&
    left.user === right.user &&
    left.status === right.status &&
    left.search === right.search
  )
}

/**
 * The filters as `GET tasks` takes them.
 *
 * `project` is overridden by a project page, which fixes the list to its own
 * project whatever the address bar says.
 */
export function taskListParams(
  filters: TaskFilterState,
  overrides: { projectId?: number | null } = {},
): TaskListParams & SortParams<TaskSortKey> {
  const params: TaskListParams & SortParams<TaskSortKey> = {}
  const projectId = overrides.projectId ?? idOf(filters.project)

  if (projectId !== null) {
    params.project_id = projectId
  }

  const assigneeId = idOf(filters.user)

  if (assigneeId !== null) {
    params.assignee_id = assigneeId
  }

  if (isInvoicedFilter(filters.status)) {
    params.invoiced = filters.status === 'invoiced' ? 1 : 0
  } else {
    const statusId = idOf(filters.status)

    if (statusId !== null) {
      params.task_status_id = statusId
    }
  }

  if (filters.search !== '') {
    params.search = filters.search
  }

  return params
}

/** A filter value as the id it names, or null when it names nothing. */
export function idOf(value: string): number | null {
  const id = Number(value)

  return value !== '' && Number.isInteger(id) && id > 0 ? id : null
}

function single(value: LocationQuery[string]): string {
  const first = Array.isArray(value) ? value[0] : value

  return typeof first === 'string' ? first.trim() : ''
}

function numeric(value: LocationQuery[string]): string {
  const text = single(value)

  return idOf(text) === null ? '' : text
}

function statusOf(value: LocationQuery[string]): string {
  const text = single(value)

  return isInvoicedFilter(text) ? text : numeric(value)
}
