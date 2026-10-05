import type { AxiosInstance } from 'axios'
import type { Paginated, Wrapped } from '@/types/api'
import type { CompanyMember } from '@/types/member'
import type { ModuleSettings } from '@/types/settings'
import type { TaskStatus, TaskStatusInput } from '@/types/task-status'
import type { TaskSummary } from '@/types/task-summary'
import type { TimeEntry, TimeEntryInput, TimeEntryListParams } from '@/types/time-entry'
import type { RunningTimer, StartTimerInput, StopTimerInput } from '@/types/timer'

const BASE = '/api/v1/tasks-projects'

/** The endpoints the time screens talk to. */
export const TIME_API = {
  timeEntries: `${BASE}/time-entries`,
  timeEntry: (id: number): string => `${BASE}/time-entries/${id}`,
  timer: `${BASE}/timer`,
  timerStart: `${BASE}/timer/start`,
  timerStop: `${BASE}/timer/stop`,
  taskStatuses: `${BASE}/task-statuses`,
  taskStatus: (id: number): string => `${BASE}/task-statuses/${id}`,
  reorderTaskStatuses: `${BASE}/task-statuses/reorder`,
  tasks: `${BASE}/tasks`,
  task: (id: number): string => `${BASE}/tasks/${id}`,
  members: `${BASE}/members`,
  settings: `${BASE}/settings`,
} as const

/** Host endpoints the module reads through the same session client. */
export const HOST_TIME_API = {
  bootstrap: '/api/v1/bootstrap',
} as const

/** How many rows one list request asks for, and how many it may ever ask for. */
export const TIME_PAGE_SIZE = 25

const WEEK_PAGE_SIZE = 100
const MAX_WEEK_PAGES = 5
const TASK_SEARCH_LIMIT = 10

export async function listTimeEntries(
  client: AxiosInstance,
  params: TimeEntryListParams,
): Promise<Paginated<TimeEntry>> {
  const { data } = await client.get<Paginated<TimeEntry>>(TIME_API.timeEntries, { params })

  return data
}

/**
 * Every entry of one range, rather than one page of them.
 *
 * The week grid has to show whole days, so it follows the paginator instead of
 * cutting the last day in half. The page walk is bounded: a week with more
 * than five hundred entries is a data problem, not a view to render.
 */
export async function listAllTimeEntries(
  client: AxiosInstance,
  params: TimeEntryListParams,
): Promise<TimeEntry[]> {
  const entries: TimeEntry[] = []

  for (let page = 1; page <= MAX_WEEK_PAGES; page += 1) {
    const response = await listTimeEntries(client, { ...params, page, limit: WEEK_PAGE_SIZE })

    entries.push(...(response.data ?? []))

    if (!response.meta || page >= response.meta.last_page) {
      break
    }
  }

  return entries
}

export async function createTimeEntry(
  client: AxiosInstance,
  input: TimeEntryInput,
): Promise<TimeEntry> {
  const { data } = await client.post<Wrapped<TimeEntry>>(TIME_API.timeEntries, input)

  return data.data
}

export async function updateTimeEntry(
  client: AxiosInstance,
  id: number,
  input: TimeEntryInput,
): Promise<TimeEntry> {
  const { data } = await client.put<Wrapped<TimeEntry>>(TIME_API.timeEntry(id), input)

  return data.data
}

export async function deleteTimeEntry(client: AxiosInstance, id: number): Promise<void> {
  await client.delete(TIME_API.timeEntry(id))
}

/** The caller's running entry, or null when the clock is not running. */
export async function fetchTimer(client: AxiosInstance): Promise<TimeEntry | null> {
  const { data } = await client.get<RunningTimer>(TIME_API.timer)

  return data?.data ?? null
}

export async function startTimer(
  client: AxiosInstance,
  input: StartTimerInput,
): Promise<TimeEntry> {
  const { data } = await client.post<Wrapped<TimeEntry>>(TIME_API.timerStart, input)

  return data.data
}

/** Close the running entry, with whatever the stop dialog collected. */
export async function stopTimer(
  client: AxiosInstance,
  input: StopTimerInput = {},
): Promise<TimeEntry> {
  const { data } = await client.post<Wrapped<TimeEntry>>(TIME_API.timerStop, input)

  return data.data
}

export async function discardTimer(client: AxiosInstance): Promise<void> {
  await client.delete(TIME_API.timer)
}

export async function listTaskStatuses(client: AxiosInstance): Promise<TaskStatus[]> {
  const { data } = await client.get<Wrapped<TaskStatus[]>>(TIME_API.taskStatuses)

  return data.data ?? []
}

export async function createTaskStatus(
  client: AxiosInstance,
  input: TaskStatusInput,
): Promise<TaskStatus> {
  const { data } = await client.post<Wrapped<TaskStatus>>(TIME_API.taskStatuses, input)

  return data.data
}

export async function updateTaskStatus(
  client: AxiosInstance,
  id: number,
  input: TaskStatusInput,
): Promise<TaskStatus> {
  const { data } = await client.put<Wrapped<TaskStatus>>(TIME_API.taskStatus(id), input)

  return data.data
}

export async function deleteTaskStatus(client: AxiosInstance, id: number): Promise<void> {
  await client.delete(TIME_API.taskStatus(id))
}

/** Apply the wanted column order; the endpoint answers with the new list. */
export async function reorderTaskStatuses(
  client: AxiosInstance,
  ids: number[],
): Promise<TaskStatus[]> {
  const { data } = await client.post<Wrapped<TaskStatus[]>>(TIME_API.reorderTaskStatuses, { ids })

  return data.data ?? []
}

/** How a picker narrows the task search beyond the typed text. */
export interface TaskSearchOptions {
  /** Only this project's tasks; omit or null to search every task. */
  projectId?: number | null
  /** `0` for tasks not yet on an invoice; omit to search either way. */
  invoiced?: 0 | 1
  limit?: number
}

/** Tasks matching what the picker has typed so far. */
export async function searchTasks(
  client: AxiosInstance,
  search: string,
  options: TaskSearchOptions = {},
): Promise<TaskSummary[]> {
  const params: Record<string, string | number> = { limit: options.limit ?? TASK_SEARCH_LIMIT }

  if (search.trim() !== '') {
    params.search = search.trim()
  }

  if (typeof options.projectId === 'number') {
    params.project_id = options.projectId
  }

  if (options.invoiced !== undefined) {
    params.invoiced = options.invoiced
  }

  const { data } = await client.get<Paginated<TaskSummary>>(TIME_API.tasks, { params })

  return data.data ?? []
}

export async function fetchTask(client: AxiosInstance, id: number): Promise<TaskSummary> {
  const { data } = await client.get<Wrapped<TaskSummary>>(TIME_API.task(id))

  return data.data
}

/** The company's members, for the member filter and the "who logged it" column. */
export async function listTimeMembers(client: AxiosInstance): Promise<CompanyMember[]> {
  const { data } = await client.get<Wrapped<CompanyMember[]>>(TIME_API.members)

  return data.data ?? []
}

export async function fetchTimeSettings(client: AxiosInstance): Promise<ModuleSettings> {
  const { data } = await client.get<Wrapped<ModuleSettings>>(TIME_API.settings)

  return data.data
}

/**
 * The signed-in user's id, read from the host bootstrap payload.
 *
 * A module bundle has no access to the host's user store, and the time
 * endpoints answer "my time" only when they are asked for a specific
 * `user_id`, so the id is fetched once per company session from the same
 * round trip the shell itself uses.
 */
export async function fetchCurrentUserId(client: AxiosInstance): Promise<number | null> {
  const { data } = await client.get<{ current_user?: { id?: unknown } }>(HOST_TIME_API.bootstrap)
  const id = data?.current_user?.id

  return typeof id === 'number' ? id : null
}
