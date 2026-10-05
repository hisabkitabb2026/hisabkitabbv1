import type { AxiosInstance } from 'axios'

let apiClient: AxiosInstance

export function setApiClient(client: AxiosInstance): void {
  apiClient = client
}

export const lrReceiptApi = {
  async list(params?: Record<string, unknown>) {
    const { data } = await apiClient.get('/api/v1/lr-receipts', { params })
    return data
  },

  async get(id: number) {
    const { data } = await apiClient.get(`/api/v1/lr-receipts/${id}`)
    return data
  },

  async autoFill(file: File) {
    const formData = new FormData()
    formData.append('file', file)
    const { data } = await apiClient.post('/api/v1/lr-receipts/auto-fill', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async lorryReceiptStatus(id: number) {
    const { data } = await apiClient.get(`/api/v1/lr-receipts/${id}/lorry-receipt-status`)
    return data
  },
}
