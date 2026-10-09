<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { AxiosInstance } from 'axios'

const props = defineProps<{ client: AxiosInstance }>()

interface LorryReceipt {
  id: number
  invoice_number: string
  invoice_date: string
  status: string
  total: number
  tr_owner_name?: string
  tr_driver_name?: string
  tr_broker_name?: string
  tr_lorry_no?: string
  tr_from_name?: string
  tr_to_name?: string
}

const receipts = ref<LorryReceipt[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await props.client.get('/api/v1/customer/lorry-receipts')
    receipts.value = data.data || []
  } catch { receipts.value = [] }
  finally { loading.value = false }
})

function formatDate(date: string): string {
  return date ? new Date(date).toLocaleDateString() : '—'
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-heading">My Lorry Receipts</h1>
      <p class="text-sm text-muted mt-1">Truck payment records where you are the owner, driver, or broker</p>
    </div>
    <div v-if="loading" class="text-center py-8 text-muted">Loading...</div>
    <div v-else-if="receipts.length === 0" class="text-center py-8 text-muted">No lorry receipts found.</div>
    <div v-else class="bg-surface border border-line-default rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-surface-secondary border-b border-line-default">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-muted">Receipt No</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Truck No</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Owner</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Route</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Date</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in receipts" :key="r.id" class="border-b border-line-light">
            <td class="px-4 py-3 font-medium text-heading">{{ r.invoice_number }}</td>
            <td class="px-4 py-3 text-body">{{ r.tr_lorry_no || '—' }}</td>
            <td class="px-4 py-3 text-body">{{ r.tr_owner_name || '—' }}</td>
            <td class="px-4 py-3 text-body">{{ r.tr_from_name || '—' }} → {{ r.tr_to_name || '—' }}</td>
            <td class="px-4 py-3 text-body">{{ formatDate(r.invoice_date) }}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600">{{ r.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
