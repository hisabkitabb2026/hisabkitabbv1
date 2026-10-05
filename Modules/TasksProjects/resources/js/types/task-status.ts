/** One board column, as `TaskStatusResource` renders it. */
export interface TaskStatus {
  id: number
  company_id: number
  name: string
  colour: string | null
  /** Column order on the board. */
  position: number
  /** Where a task lands when none is named. */
  is_default: boolean
  /** Counts as done, and stamps the task's `closed_at`. */
  is_closed: boolean
  created_at: string | null
  updated_at: string | null
}

/** What the create and update endpoints accept. */
export interface TaskStatusInput {
  name?: string
  colour?: string | null
  is_default?: boolean
  is_closed?: boolean
}
