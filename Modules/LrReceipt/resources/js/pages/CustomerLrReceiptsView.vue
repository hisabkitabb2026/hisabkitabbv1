<script setup lang="ts">
/**
 * Customer portal page: lists the authenticated customer's LR Receipts.
 * Registered as a module page at /admin/modules/lr-receipt/customer.
 */
import { ref, onMounted } from 'vue'
import type { AxiosInstance } from 'vue'

const props = defineProps<{
  client: AxiosInstance
}>()

interface LrReceipt {
  id: number
  invoice_number: string
  invoice_date: string
  status: string
  total: number
  customer?: { name: string }
  consigneeCustomer?: { name: string }
}

const receipts = ref<LrReceipt[]>([])
const loading = ref(true)
const totalCount = ref(0)

onMounted(async () => {
  try {
    const response = await props.client.get('/api/v1/customer/lr-receipts')
    receipts.value = response.data.data || []
    totalCount.value = response.data.meta?.lrReceiptTotalCount || 0
  } catch {
    receipts.value = []
  } finally {
    loading.value = false
  }
})

function formatDate(date: string): string {
  if (!date) return '—'
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-heading">My LR Receipts</h1>
      <p class="text-sm text-muted mt-1">Your consignment dockets and their delivery status</p>
    </div>

    <div v-if="loading" class="text-center py-8 text-muted">Loading...</div>

    <div v-else-if="receipts.length === 0" class="text-center py-8 text-muted">
      No LR Receipts found.
    </div>

    <div v-else class="bg-surface border border-line-default rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-surface-secondary border-b border-line-default">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-muted">Docket No</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Consignor</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Consignee</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Date</th>
            <th class="px-4 py-3 text-left font-medium text-muted">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="receipt in receipts"
            :key="receipt.id"
            class="border-b border-line-light hover:bg-surface-secondary"
          >
            <td class="px-4 py-3 font-medium text-heading">{{ receipt.invoice_number }}</td>
            <td class="px-4 py-3 text-body">{{ receipt.customer?.name || '—' }}</td>
            <td class="px-4 py-3 text-body">{{ receipt.consigneeCustomer?.name || '—' }}</td>
            <td class="px-4 py-3 text-body">{{ formatDate(receipt.invoice_date) }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600">{{ receipt.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
