import type { AxiosInstance } from 'axios'
import type { TripReportParams, TripReportSummary } from '@/types/reports'

const BASE = '/api/v1/trips'

export async function fetchReportSummary(
  client: AxiosInstance,
  params: TripReportParams,
): Promise<TripReportSummary> {
  const { data } = await client.get(`${BASE}/reports/summary`, { params })

  return data.data
}
