/**
 * The read-only aggregates `GET reports/summary` answers with.
 *
 * Every row is reported per `currency_id` and nothing is converted: a project
 * inherits its customer's currency and an internal project has none, so a row
 * never adds two currencies together. Durations are whole minutes and amounts
 * are integer minor units, the same convention the rest of the module uses.
 */

/** The figures every breakdown row carries, whatever it is broken down by. */
export interface ReportTotals {
  currency_id: number | null
  minutes: number
  amount: number
  billable_minutes: number
  billable_amount: number
  /** Billable and not yet stamped with an invoice. */
  unbilled_amount: number
}

export interface ReportProjectRow extends ReportTotals {
  /** Null for time logged against a task that belongs to no project. */
  project_id: number | null
  label: string
}

export interface ReportMemberRow extends ReportTotals {
  user_id: number | null
  /** The server's own label, which reads "Removed member" for a stale id. */
  label: string
}

export interface ReportCustomerRow extends ReportTotals {
  /** Null for internal work, which never reaches the billing screen. */
  customer_id: number | null
}

export interface ReportBillableRow extends ReportTotals {
  billable: boolean
}

export interface ReportSummary {
  /** `Y-m-d`, inclusive, echoed back so the page shows the range it charted. */
  from: string
  to: string
  totals: ReportTotals[]
  by_project: ReportProjectRow[]
  by_member: ReportMemberRow[]
  by_customer: ReportCustomerRow[]
  by_billable: ReportBillableRow[]
}

export interface ReportParams {
  /** `Y-m-d`, inclusive. Both default to the current month on the server. */
  from?: string
  to?: string
}

/** One breakdown row with its name already resolved, ready for a table. */
export interface BreakdownRow extends ReportTotals {
  /** Unique within its table, so the table can key its rows. */
  id: string
  label: string
}
