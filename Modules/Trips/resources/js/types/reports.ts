export interface TripReportTotals {
  trip_count: number
  revenue: number
  cost: number
  profit: number
  unbilled_amount: number
}

export interface TripReportStatusRow {
  status_id: number
  status_name: string
  status_colour: string | null
  trip_count: number
  revenue: number
  cost: number
  profit: number
}

export interface TripReportCustomerRow {
  customer_id: number | null
  customer_name: string
  trip_count: number
  revenue: number
  cost: number
  profit: number
}

export interface TripReportRouteRow {
  from_city: string
  to_city: string
  route: string
  trip_count: number
  revenue: number
  cost: number
  profit: number
}

export interface TripReportSummary {
  from: string
  to: string
  totals: TripReportTotals
  by_status: TripReportStatusRow[]
  by_customer: TripReportCustomerRow[]
  by_route: TripReportRouteRow[]
}

export interface TripReportParams {
  from?: string
  to?: string
}
