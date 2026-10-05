import type { AxiosInstance } from 'axios'
import type { Trip, TripCard, TripStatus, UnlinkedReceipt } from '@/types/trip'

const BASE = '/api/v1/trips'

export interface BoardFilters {
  search?: string
  customer_id?: number | null
  status_id?: number | null
  unbilled_only?: boolean
}

export async function fetchBoard(
  client: AxiosInstance,
  filters: BoardFilters = {},
): Promise<{ statuses: TripStatus[]; trips: TripCard[] }> {
  const [tripsResponse, statusesResponse] = await Promise.all([
    client.get(BASE, { params: filters }),
    client.get(`${BASE}/statuses`),
  ])

  return {
    statuses: statusesResponse.data.data,
    trips: tripsResponse.data.data,
  }
}

/** Only the trip cards, for when the parent already loaded statuses. */
export async function fetchTrips(
  client: AxiosInstance,
  filters: BoardFilters = {},
): Promise<TripCard[]> {
  const { data } = await client.get(BASE, { params: filters })

  return data.data
}

/** Only the statuses, for the parent page's filter bar. */
export async function fetchStatuses(client: AxiosInstance): Promise<TripStatus[]> {
  const { data } = await client.get(`${BASE}/statuses`)

  return data.data
}

export async function fetchTrip(client: AxiosInstance, id: number): Promise<Trip> {
  const { data } = await client.get(`${BASE}/${id}`)

  return data.data
}

export async function createTripFromLrs(
  client: AxiosInstance,
  invoiceIds: number[],
): Promise<Trip> {
  const { data } = await client.post(`${BASE}/from-lrs`, { invoice_ids: invoiceIds })

  return data.data
}

export async function updateTrip(client: AxiosInstance, id: number, payload: object): Promise<Trip> {
  const { data } = await client.put(`${BASE}/${id}`, payload)

  return data.data
}

export async function moveTrip(
  client: AxiosInstance,
  id: number,
  statusId: number,
  position: number | null,
): Promise<TripCard> {
  const { data } = await client.post(`${BASE}/${id}/move`, {
    status_id: statusId,
    position,
  })

  return data.data
}

export async function cancelTrip(client: AxiosInstance, id: number): Promise<Trip> {
  const { data } = await client.post(`${BASE}/${id}/cancel`)

  return data.data
}

/**
 * Change a trip's status from the detail page. Returns the full Trip so the
 * detail page (status pill, money) refreshes in one call.
 */
export async function changeTripStatus(
  client: AxiosInstance,
  id: number,
  statusId: number,
): Promise<Trip> {
  const { data } = await client.patch(`${BASE}/${id}/status`, { status_id: statusId })

  return data.data
}

export async function deleteTrip(client: AxiosInstance, id: number): Promise<void> {
  await client.delete(`${BASE}/${id}`)
}

export async function fetchUnlinkedReceipts(
  client: AxiosInstance,
  type: 'lr' | 'lorry' = 'lr',
  customerId: number | null = null,
): Promise<UnlinkedReceipt[]> {
  const { data } = await client.get(`${BASE}/unlinked-lrs`, {
    params: { type, customer_id: customerId },
  })

  return data.data
}

/**
 * Preview: which lorry receipt would be auto-matched for these LR receipts?
 * The bilty numbers on the lorry receipt tell us which lorry carried the load.
 */
export async function fetchLorryMatch(
  client: AxiosInstance,
  invoiceIds: number[],
): Promise<UnlinkedReceipt[]> {
  if (invoiceIds.length === 0) {
    return []
  }

  const { data } = await client.post(`${BASE}/lorry-match`, { invoice_ids: invoiceIds })

  return data.data
}

export async function linkReceipt(
  client: AxiosInstance,
  tripId: number,
  invoiceId: number,
  type: 'lr' | 'lorry' | 'invoice',
): Promise<Trip> {
  const { data } = await client.post(`${BASE}/${tripId}/receipts`, {
    invoice_id: invoiceId,
    type,
  })

  return data.data
}

export async function fetchInvoiceReceipts(client: AxiosInstance, tripId: number): Promise<Trip> {
  const { data } = await client.post(`${BASE}/${tripId}/fetch-invoice-receipt`)

  return data.data
}

export async function uploadPod(
  client: AxiosInstance,
  tripId: number,
  file: File,
  category: string,
): Promise<Trip> {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('category', category)

  const { data } = await client.post(`${BASE}/${tripId}/pod`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return data.data
}

export async function removePod(client: AxiosInstance, tripId: number, mediaId: number): Promise<Trip> {
  const { data } = await client.delete(`${BASE}/${tripId}/pod/${mediaId}`)

  return data.data
}

/**
 * Fetch a POD document as a blob via the authenticated client. The route sits
 * behind auth:sanctum, so a direct <img src> or <a href> gets 403. The caller
 * creates an object URL from the returned blob.
 */
export async function fetchDocumentBlob(
  client: AxiosInstance,
  tripId: number,
  mediaId: number,
): Promise<Blob> {
  const { data } = await client.get(`${BASE}/${tripId}/pod/${mediaId}`, {
    responseType: 'blob',
  })

  return data
}

export async function unlinkReceipt(
  client: AxiosInstance,
  tripId: number,
  receiptId: number,
): Promise<Trip> {
  const { data } = await client.delete(`${BASE}/${tripId}/receipts/${receiptId}`)

  return data.data
}

export async function addExpense(
  client: AxiosInstance,
  tripId: number,
  payload: object,
): Promise<Trip> {
  const { data } = await client.post(`${BASE}/${tripId}/expenses`, payload)

  return data.data
}

export async function removeExpense(
  client: AxiosInstance,
  tripId: number,
  expenseId: number,
): Promise<Trip> {
  const { data } = await client.delete(`${BASE}/${tripId}/expenses/${expenseId}`)

  return data.data
}

/** The host's customers, for the pickers. */
export async function fetchCustomers(client: AxiosInstance): Promise<{ id: number; name: string }[]> {
  const { data } = await client.get('/api/v1/customers', { params: { limit: 'all' } })

  return data.data
}

/** The LorryReceipt module's party profiles, for the owner/driver/broker picks. */
export async function fetchParties(client: AxiosInstance): Promise<{ id: number; name: string; type: string }[]> {
  const { data } = await client.get('/api/v1/lorry-receipts/lorry-party-profiles')

  return data.data
}
