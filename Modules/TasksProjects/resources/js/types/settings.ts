/** How a stopped entry's minutes are rounded to the increment. */
export type RoundingDirection = 'nearest' | 'up' | 'down'

/** The module's per-company settings, as the settings endpoint renders them. */
export interface ModuleSettings {
  /** Minor units per hour. */
  default_rate: number
  rounding_minutes: number
  rounding_direction: RoundingDirection
  week_start: number
  members_see_all_time: boolean
  /** Start the creator's timer as soon as a task is created. */
  auto_start_tasks: boolean
  /** Refuse edits to a task whose time is already on an invoice. */
  lock_invoiced_tasks: boolean
  /** Keep invoiced tasks off the board. */
  hide_invoiced_on_board: boolean
  /** What an invoice line built from a task carries. */
  invoice_project_heading: boolean
  invoice_task_description: boolean
  invoice_entry_dates: boolean
  invoice_entry_times: boolean
  invoice_entry_hours: boolean
  invoice_entry_descriptions: boolean
  rounding_increments: number[]
}
