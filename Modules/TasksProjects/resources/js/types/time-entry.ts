/**
 * Logged time, as `TimeEntryResource` renders it.
 *
 * Durations are minutes, `rate` is minor units per hour and `amount` is the
 * money frozen on the entry when it was saved, also in minor units. An entry
 * carrying an `invoice_id` is stamped: it belongs to an invoice and the API
 * refuses to delete it.
 */
export interface TimeEntry {
  id: number
  company_id: number
  task_id: number
  project_id: number | null
  user_id: number
  started_at: string | null
  ended_at: string | null
  duration_minutes: number
  description: string | null
  billable: boolean
  rate: number
  amount: number
  currency_id: number | null
  is_running: boolean
  invoice_id: number | null
  invoice_item_id: number | null
  invoiced_at: string | null
  created_at: string | null
  updated_at: string | null
}

/** What the create and update endpoints accept. */
export interface TimeEntryInput {
  task_id: number
  user_id?: number | null
  started_at?: string | null
  ended_at?: string | null
  duration_minutes?: number | null
  description?: string | null
  billable?: boolean
}

export interface TimeEntryListParams {
  page?: number
  limit?: number
  user_id?: number
  project_id?: number
  task_id?: number
  /** `Y-m-d`, inclusive. */
  from?: string
  /** `Y-m-d`, inclusive. */
  to?: string
  billable?: boolean
  billed?: boolean
}
