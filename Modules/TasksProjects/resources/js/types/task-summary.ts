/**
 * The few fields of a task the time screens need: enough to search for one,
 * label an entry and know whether logging against it is billable by default.
 */
export interface TaskSummary {
  id: number
  name: string
  number: number | null
  project_id: number | null
  billable: boolean
}
