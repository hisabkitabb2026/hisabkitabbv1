import type { AxiosInstance } from 'axios'

let apiClient: AxiosInstance

export function setApiClient(client: AxiosInstance): void {
  apiClient = client
}

export const lorryReceiptApi = {
  async list(params?: Record<string, unknown>) {
    const { data } = await apiClient.get('/api/v1/lorry-receipts', { params })
    return data
  },

  async get(id: number) {
    const { data } = await apiClient.get(`/api/v1/lorry-receipts/${id}`)
    return data
  },
}

export const lorryPartyProfileApi = {
  async list(params?: Record<string, unknown>) {
    const { data } = await apiClient.get('/api/v1/lorry-receipts/lorry-party-profiles', { params })
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await apiClient.post('/api/v1/lorry-receipts/lorry-party-profiles', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await apiClient.put(`/api/v1/lorry-receipts/lorry-party-profiles/${id}`, payload)
    return data
  },

  async delete(id: number) {
    const { data } = await apiClient.delete(`/api/v1/lorry-receipts/lorry-party-profiles/${id}`)
    return data
  },
}
