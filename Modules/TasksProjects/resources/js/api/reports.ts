import type { AxiosInstance } from 'axios'
import { BASE } from '@/api'
import type {
  ReportBillableRow,
  ReportCustomerRow,
  ReportMemberRow,
  ReportParams,
  ReportProjectRow,
  ReportSummary,
  ReportTotals,
} from '@/types/reports'

/** The one endpoint the reports page reads. */
export const REPORTS_API = {
  summary: `${BASE}/reports/summary`,
} as const

/**
 * The aggregates for one range, shaped the way the page renders them.
 *
 * The payload crosses a module boundary and the page cannot be checked in a
 * browser from the module's own tree, so every field is read defensively: a
 * missing figure becomes zero and a missing list becomes an empty one, which
 * renders as an empty table rather than as a blank screen.
 */
export async function fetchReportSummary(
  client: AxiosInstance,
  params: ReportParams,
): Promise<ReportSummary> {
  const { data } = await client.get<{ data?: unknown }>(REPORTS_API.summary, { params })

  return normalise(data?.data, params)
}

function normalise(payload: unknown, params: ReportParams): ReportSummary {
  const body = isRecord(payload) ? payload : {}

  return {
    from: textOr(body.from, params.from ?? ''),
    to: textOr(body.to, params.to ?? ''),
    totals: rowsOf(body.totals).map(totalsOf),
    by_project: rowsOf(body.by_project).map(
      (row): ReportProjectRow => ({
        ...totalsOf(row),
        project_id: idOr(row.project_id),
        label: textOr(row.label, ''),
      }),
    ),
    by_member: rowsOf(body.by_member).map(
      (row): ReportMemberRow => ({
        ...totalsOf(row),
        user_id: idOr(row.user_id),
        label: textOr(row.label, ''),
      }),
    ),
    by_customer: rowsOf(body.by_customer).map(
      (row): ReportCustomerRow => ({ ...totalsOf(row), customer_id: idOr(row.customer_id) }),
    ),
    by_billable: rowsOf(body.by_billable).map(
      (row): ReportBillableRow => ({ ...totalsOf(row), billable: row.billable === true }),
    ),
  }
}

function totalsOf(row: Record<string, unknown>): ReportTotals {
  return {
    currency_id: idOr(row.currency_id),
    minutes: numberOr(row.minutes),
    amount: numberOr(row.amount),
    billable_minutes: numberOr(row.billable_minutes),
    billable_amount: numberOr(row.billable_amount),
    unbilled_amount: numberOr(row.unbilled_amount),
  }
}

function rowsOf(value: unknown): Record<string, unknown>[] {
  return Array.isArray(value) ? value.filter(isRecord) : []
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null
}

function numberOr(value: unknown): number {
  return typeof value === 'number' && Number.isFinite(value) ? value : 0
}

function idOr(value: unknown): number | null {
  return typeof value === 'number' && Number.isFinite(value) ? value : null
}

function textOr(value: unknown, fallback: string): string {
  return typeof value === 'string' && value.trim() !== '' ? value : fallback
}
