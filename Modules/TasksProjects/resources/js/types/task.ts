/** A task as `TaskResource` renders it. Money is integer minor units. */

export const TASK_PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT'] as const

export type TaskPriority = (typeof TASK_PRIORITIES)[number]

/** Whether any of a task's billable time has reached an invoice. */
export type TaskInvoiceState = 'none' | 'uninvoiced' | 'invoiced'

/** One entry whose clock is running right now, whoever started it. */
export interface TaskRunningEntry {
  entry_id: number
  user_id: number
  started_at: string | null
}

/**
 * The time summary the API attaches to a task.
 *
 * Every screen that shows a task shows its time, so the totals ride along with
 * the row rather than costing a request each. An older server answers without
 * the block, so every read of it is guarded.
 */
export interface TaskTime {
  logged_minutes: number
  billable_minutes: number
  unbilled_minutes: number
  /** Minor units, in the currency the entries were logged in. */
  unbilled_amount: number
  invoiced: TaskInvoiceState
  running: TaskRunningEntry[]
}

export interface Task {
  id: number
  company_id: number
  project_id: number | null
  /** Denormalised from the project, or set directly on a standalone task. */
  customer_id: number | null
  task_status_id: number
  /** A per-company sequence, for referring to a task in an email. */
  number: number
  name: string
  description: string | null
  assignee_id: number | null
  priority: TaskPriority | null
  due_date: string | null
  estimated_minutes: number | null
  billable: boolean
  /** Minor units per hour, overriding the member and project rates. */
  rate: number | null
  /** Fractional board order, kept as a string so no float rewrites it. */
  board_position: string
  closed_at: string | null
  creator_id: number | null
  created_at: string | null
  updated_at: string | null
  /** Absent on a server that predates the time summary. */
  time?: TaskTime
}

/**
 * What the create and update endpoints accept.
 *
 * `task_status_id` is never null: the update rule takes an integer, and the
 * form always has a column selected.
 */
export interface TaskInput {
  name: string
  task_status_id: number
  project_id: number | null
  customer_id: number | null
  description: string | null
  assignee_id: number | null
  priority: TaskPriority | null
  due_date: string | null
  estimated_minutes: number | null
  billable: boolean
  rate: number | null
}

/**
 * The least a task can be created with.
 *
 * The start dialog creates a task out of the name someone typed into its
 * search box, so everything else is left to the server: the default column,
 * the billable flag and the number all come from the company's own settings.
 */
export interface TaskQuickInput {
  name: string
  project_id: number | null
}

export interface TaskListParams {
  page?: number
  limit?: number
  project_id?: number
  assignee_id?: number
  task_status_id?: number
  customer_id?: number
  search?: string
  /** 1 for tasks already on an invoice, 0 for the ones still waiting. */
  invoiced?: 0 | 1
}

/** Where a dragged card landed: its new column and the two tasks around it. */
export interface TaskMoveInput {
  task_status_id: number
  before_id: number | null
  after_id: number | null
}

/** What `POST tasks/bulk` does to the selection. */
export type TaskBulkAction = 'status' | 'delete'

export interface TaskBulkInput {
  action: TaskBulkAction
  ids: number[]
  /** Required by the `status` action, ignored by the others. */
  task_status_id?: number
}

/** A task the bulk endpoint refused, and why. */
export interface TaskBulkFailure {
  id: number
  reason: string
}

/** The ids the bulk endpoint actually changed. */
export interface TaskBulkResult {
  updated: number[]
  failed: TaskBulkFailure[]
}
