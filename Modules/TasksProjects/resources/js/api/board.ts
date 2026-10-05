import type { AxiosInstance } from 'axios'
import { BASE, TASKS_PROJECTS_API } from '@/api'
import type { SortParams } from '@/api'
import type { Paginated, Wrapped } from '@/types/api'
import type { BoardColumn, BoardParams } from '@/types/board'
import type { Project } from '@/types/project'
import type { ProjectMember, ProjectMemberInput } from '@/types/project-member'
import type {
  Task,
  TaskBulkInput,
  TaskBulkResult,
  TaskInput,
  TaskListParams,
  TaskMoveInput,
  TaskQuickInput,
} from '@/types/task'
import type { TaskStatus } from '@/types/task-status'
import type { TimeEntry, TimeEntryListParams } from '@/types/time-entry'
import type { StopTimerInput } from '@/types/timer'

/** The columns `GET tasks` orders by. Mirrors `TaskService::SORT_KEYS`. */
export const TASK_SORT_KEYS = ['number', 'name', 'priority', 'due_date', 'created_at'] as const

export type TaskSortKey = (typeof TASK_SORT_KEYS)[number]

/** The endpoints the board, the task lists and the project detail read. */
export const BOARD_API = {
  board: `${BASE}/board`,
  tasks: `${BASE}/tasks`,
  task: (id: number): string => `${BASE}/tasks/${id}`,
  moveTask: (id: number): string => `${BASE}/tasks/${id}/move`,
  startTask: (id: number): string => `${BASE}/tasks/${id}/start`,
  stopTask: (id: number): string => `${BASE}/tasks/${id}/stop`,
  taskTimeLog: (id: number): string => `${BASE}/tasks/${id}/time-log`,
  bulkTasks: `${BASE}/tasks/bulk`,
  taskStatuses: `${BASE}/task-statuses`,
  timeEntries: `${BASE}/time-entries`,
  projectMembers: (projectId: number): string => `${BASE}/projects/${projectId}/members`,
  projectMember: (projectId: number, userId: number): string =>
    `${BASE}/projects/${projectId}/members/${userId}`,
} as const

/** Every column of the company with its tasks, in one request. */
export async function fetchBoard(
  client: AxiosInstance,
  params: BoardParams,
): Promise<BoardColumn[]> {
  const { data } = await client.get<Wrapped<BoardColumn[]>>(BOARD_API.board, { params })

  return data.data
}

/** The board columns on their own, for the drawer's status picker. */
export async function listTaskStatuses(client: AxiosInstance): Promise<TaskStatus[]> {
  const { data } = await client.get<Wrapped<TaskStatus[]>>(BOARD_API.taskStatuses)

  return data.data
}

export async function listTasks(
  client: AxiosInstance,
  params: TaskListParams & SortParams<TaskSortKey>,
): Promise<Paginated<Task>> {
  const { data } = await client.get<Paginated<Task>>(BOARD_API.tasks, { params })

  return data
}

/**
 * Create a task.
 *
 * The quick shape is the whole of what the start dialog knows: the server
 * picks the default column and the rest of the defaults, so starting the clock
 * on something new never means filling in a form first.
 */
export async function createTask(
  client: AxiosInstance,
  input: TaskInput | TaskQuickInput,
): Promise<Task> {
  const { data } = await client.post<Wrapped<Task>>(BOARD_API.tasks, input)

  return data.data
}

export async function updateTask(
  client: AxiosInstance,
  id: number,
  input: TaskInput,
): Promise<Task> {
  const { data } = await client.put<Wrapped<Task>>(BOARD_API.task(id), input)

  return data.data
}

export async function deleteTask(client: AxiosInstance, id: number): Promise<void> {
  await client.delete(BOARD_API.task(id))
}

/** One task with the time summary the list carries, for the task page. */
export async function fetchTaskDetail(client: AxiosInstance, id: number): Promise<Task> {
  const { data } = await client.get<Wrapped<Task>>(BOARD_API.task(id))

  return data.data
}

/**
 * Put the caller's clock on a task.
 *
 * Answers 409 `timer_already_running` when their timer is on another task,
 * which is a question for the caller rather than a failure: the run control
 * offers to stop the other one first.
 */
export async function startTask(
  client: AxiosInstance,
  id: number,
  description: string | null = null,
  billable?: boolean,
): Promise<TimeEntry> {
  const body: { description?: string | null; billable?: boolean } = {}

  if (description !== null) {
    body.description = description
  }

  if (billable !== undefined) {
    body.billable = billable
  }

  const { data } = await client.post<Wrapped<TimeEntry>>(BOARD_API.startTask(id), body)

  return data.data
}

/** Close the caller's running entry on a task. 409 `timer_mismatch` if it moved. */
export async function stopTask(
  client: AxiosInstance,
  id: number,
  input: StopTimerInput = {},
): Promise<TimeEntry> {
  const { data } = await client.post<Wrapped<TimeEntry>>(BOARD_API.stopTask(id), input)

  return data.data
}

/**
 * Every entry logged against one task, running first and then newest.
 *
 * A caller who may not see other members' time gets their own rows, so the
 * grid renders either way and never has to ask which case it is in.
 */
export async function fetchTaskTimeLog(client: AxiosInstance, id: number): Promise<TimeEntry[]> {
  const { data } = await client.get<Wrapped<TimeEntry[]>>(BOARD_API.taskTimeLog(id))

  return data.data ?? []
}

/**
 * Apply one action to a selection of tasks.
 *
 * The endpoint is partial by design: it reports how many it changed and names
 * the ones it refused, so a locked task in the selection does not sink the
 * rest of it.
 */
export async function bulkTasks(
  client: AxiosInstance,
  input: TaskBulkInput,
): Promise<TaskBulkResult> {
  const { data } = await client.post<TaskBulkResult>(BOARD_API.bulkTasks, input)

  return { updated: data?.updated ?? [], failed: data?.failed ?? [] }
}

/**
 * Drop a task between two neighbours of a column.
 *
 * The server owns the ordering: it returns the task with the `board_position`
 * it settled on, which the board applies rather than guessing one itself.
 */
export async function moveTask(
  client: AxiosInstance,
  id: number,
  input: TaskMoveInput,
): Promise<Task> {
  const { data } = await client.post<Wrapped<Task>>(BOARD_API.moveTask(id), input)

  return data.data
}

/** One project with the totals only the detail endpoint carries. */
export async function fetchProject(client: AxiosInstance, id: number): Promise<Project> {
  const { data } = await client.get<Wrapped<Project>>(TASKS_PROJECTS_API.project(id))

  return data.data
}

export async function listProjectMembers(
  client: AxiosInstance,
  projectId: number,
): Promise<ProjectMember[]> {
  const { data } = await client.get<Wrapped<ProjectMember[]>>(BOARD_API.projectMembers(projectId))

  return data.data
}

export async function attachProjectMember(
  client: AxiosInstance,
  projectId: number,
  input: ProjectMemberInput,
): Promise<ProjectMember> {
  const { data } = await client.post<Wrapped<ProjectMember>>(
    BOARD_API.projectMembers(projectId),
    input,
  )

  return data.data
}

export async function detachProjectMember(
  client: AxiosInstance,
  projectId: number,
  userId: number,
): Promise<void> {
  await client.delete(BOARD_API.projectMember(projectId, userId))
}

/** The time logged against one project, for the read-only detail tab. */
export async function listProjectTime(
  client: AxiosInstance,
  params: TimeEntryListParams,
): Promise<Paginated<TimeEntry>> {
  const { data } = await client.get<Paginated<TimeEntry>>(BOARD_API.timeEntries, { params })

  return data
}
