/** The project as `ProjectResource` renders it. Money is integer minor units. */

export type ProjectStatus = 'ACTIVE' | 'ARCHIVED'

export interface ProjectTaskTotals {
  total: number
  open: number
  closed: number
}

export interface ProjectTotals {
  tasks: ProjectTaskTotals
  logged_minutes: number
  billable_minutes: number
  billable_amount: number
  unbilled_amount: number
  currency_id: number | null
}

export interface Project {
  id: number
  company_id: number
  customer_id: number | null
  name: string
  identifier: string | null
  description: string | null
  colour: string | null
  status: ProjectStatus
  currency_id: number | null
  /** Minor units per hour. */
  default_rate: number | null
  budget_minutes: number | null
  due_date: string | null
  creator_id: number | null
  is_internal: boolean
  created_at: string | null
  updated_at: string | null
  /** Only the detail endpoint carries these. */
  totals?: ProjectTotals
}

/** What the create and update endpoints accept. */
export interface ProjectInput {
  name: string
  customer_id: number | null
  identifier: string | null
  description: string | null
  colour: string | null
  currency_id?: number | null
  default_rate: number | null
  budget_minutes: number | null
  due_date: string | null
}

export interface ProjectListParams {
  page?: number
  limit?: number
  /** Omitted when the filter is "all". */
  status?: ProjectStatus
  search?: string
}
