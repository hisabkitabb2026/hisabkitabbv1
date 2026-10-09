<template>
  <BaseCard class="mb-6">
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-heading">Consignment Items</h3>
        <span class="text-xs text-muted">
          Type Consignment No to autofill details from LR Receipt
        </span>
      </div>
    </template>

    <div v-if="isLoading" class="space-y-3">
      <BaseContentPlaceholders v-for="i in 2" :key="i">
        <BaseContentPlaceholdersBox :rounded="true" class="w-full" style="min-height: 60px" />
      </BaseContentPlaceholders>
    </div>

    <div v-else>
      <!-- Item rows with transport fields -->
      <div
        v-for="(item, index) in items"
        :key="item.id ?? index"
        class="border border-line-light rounded-lg p-4 mb-4 relative"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-muted">Item {{ index + 1 }}</span>
            <span
              v-if="lookupStatus[index]?.type === 'success'"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-status-green/10 text-status-green"
            >
              <BaseIcon name="CheckCircleIcon" class="h-3.5 w-3.5" />
              LR Linked
            </span>
          </div>
          <button
            v-if="items.length > 1"
            type="button"
            class="text-alert-error-text hover:text-alert-error-text cursor-pointer"
            @click="deleteItem(item.id, index)"
          >
            <BaseIcon name="TrashIcon" class="h-5 w-5" />
          </button>
        </div>

        <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
          <!-- Consignment No with Autocomplete & Auto-fetch -->
          <div class="relative">
            <BaseInputGroup label="Consignment No" required>
              <BaseInput
                :model-value="item.tr_consignment_number"
                type="text"
                placeholder="Enter consignment number"
                :loading="loadingStates[index] || false"
                loading-position="right"
                @update:model-value="(val: string) => onConsignmentInput(item, index, val)"
                @blur="onConsignmentBlur(item, index)"
                @keydown.enter.prevent="onConsignmentEnter(item, index)"
              />
            </BaseInputGroup>

            <!-- Status hint -->
            <div v-if="lookupStatus[index]" class="mt-1 flex items-center gap-1.5 text-xs">
              <span
                v-if="lookupStatus[index]?.type === 'success'"
                class="text-status-green flex items-center gap-1"
              >
                <BaseIcon name="CheckCircleIcon" class="h-3.5 w-3.5" />
                {{ lookupStatus[index]?.message }}
              </span>
              <span
                v-else-if="lookupStatus[index]?.type === 'not_found'"
                class="text-muted flex items-center gap-1"
              >
                <BaseIcon name="InformationCircleIcon" class="h-3.5 w-3.5" />
                {{ lookupStatus[index]?.message }}
              </span>
            </div>

            <!-- Suggestions Dropdown -->
            <div
              v-if="suggestions[index] && suggestions[index].length > 0"
              class="absolute z-50 left-0 right-0 mt-1 bg-surface border border-line-default rounded-lg shadow-xl max-h-56 overflow-y-auto"
            >
              <div
                v-for="sug in suggestions[index]"
                :key="sug.id"
                class="px-3 py-2 cursor-pointer hover:bg-hover border-b border-line-light last:border-b-0 text-sm transition-colors"
                @mousedown.prevent="applyConsignmentData(item, sug, index)"
              >
                <div class="flex items-center justify-between font-semibold text-heading">
                  <span>LR #{{ sug.consignment_number }}</span>
                  <span class="text-xs font-normal text-muted">{{ sug.consignment_date || '' }}</span>
                </div>
                <div class="text-xs text-muted flex items-center gap-2 mt-0.5">
                  <span>{{ sug.from_code || '-' }} &rarr; {{ sug.to_code || '-' }}</span>
                  <span v-if="sug.truck_no">&bull; {{ sug.truck_no }}</span>
                  <span v-if="sug.rate">&bull; Rate: ₹{{ sug.rate }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Consignment Date -->
          <BaseInputGroup label="Consignment Date">
            <BaseInput
              v-model="item.tr_consignment_date"
              type="date"
            />
          </BaseInputGroup>

          <!-- Party Inv No -->
          <BaseInputGroup label="Party Inv No">
            <BaseInput
              v-model="item.tr_party_inv_no"
              type="text"
              placeholder="Party invoice number"
            />
          </BaseInputGroup>

          <!-- From (Origin) -->
          <BaseInputGroup label="From">
            <BaseInput
              v-model="item.tr_from_name"
              type="text"
              placeholder="Origin"
            />
          </BaseInputGroup>

          <!-- To (Destination) -->
          <BaseInputGroup label="Destination">
            <BaseInput
              v-model="item.tr_to_name"
              type="text"
              placeholder="Destination"
            />
          </BaseInputGroup>

          <!-- Vehicle No -->
          <BaseInputGroup label="Vehicle No">
            <BaseInput
              v-model="item.tr_truck_no"
              type="text"
              placeholder="Truck/vehicle number"
            />
          </BaseInputGroup>

          <!-- Pkg (Package Type) -->
          <BaseInputGroup label="Pkg">
            <BaseInput
              v-model="item.tr_pkg_weight"
              type="text"
              placeholder="Package type"
            />
          </BaseInputGroup>

          <!-- Weight -->
          <BaseInputGroup label="Weight">
            <BaseInput
              v-model="item.tr_charged_weight"
              type="text"
              placeholder="Shipment weight"
            />
          </BaseInputGroup>

          <!-- Rate -->
          <BaseInputGroup label="Rate">
            <BaseInput
              v-model.number="item.tr_rate"
              type="number"
              placeholder="Freight rate"
            />
          </BaseInputGroup>

          <!-- Other Charge -->
          <BaseInputGroup label="Other Charge">
            <BaseInput
              v-model.number="item.tr_other_charge"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>

          <!-- LR Charge -->
          <BaseInputGroup label="LR Charge">
            <BaseInput
              v-model.number="item.tr_lr_charge"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>

          <!-- DD Charge -->
          <BaseInputGroup label="DD Charge">
            <BaseInput
              v-model.number="item.tr_dd_charge"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>

          <!-- Amount (Auto-calculated, read-only) -->
          <BaseInputGroup label="Amount" class="md:col-span-2 xl:col-span-1">
            <BaseInput
              :model-value="calculateAmount(item)"
              disabled
              class="bg-surface-muted font-semibold"
            />
          </BaseInputGroup>
        </div>
      </div>

      <!-- Add New Item button -->
      <button
        type="button"
        class="flex items-center justify-center w-full px-6 py-3 text-base border border-dashed border-line-default rounded-lg cursor-pointer text-primary-500 hover:bg-hover"
        @click="addNewItem"
      >
        <BaseIcon name="PlusIcon" class="h-5 w-5 mr-2" />
        Add Item
      </button>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { InvoiceItem } from '@/scripts/types/domain/invoice'

interface TransportItem extends Partial<InvoiceItem> {
  tr_consignment_number?: string | null
  tr_consignment_date?: string | null
  tr_party_inv_no?: string | null
  tr_from_name?: string | null
  tr_to_name?: string | null
  tr_truck_no?: string | null
  tr_pkg_weight?: string | null
  tr_charged_weight?: string | null
  tr_rate?: string | number | null
  tr_other_charge?: string | number | null
  tr_lr_charge?: string | number | null
  tr_dd_charge?: string | number | null
}

interface ConsignmentData {
  id: number
  consignment_number: string
  consignment_date?: string | null
  party_inv_no?: string | null
  from_code?: string | null
  to_code?: string | null
  truck_no?: string | null
  pkg?: string | null
  weight?: string | null
  rate?: number | string | null
  other_charge?: number | string | null
  lr_charge?: number | string | null
  dd_charge?: number | string | null
  customer_id?: number | null
  customer?: {
    id: number
    name: string
    contact_name?: string | null
    currency_id?: number | null
    billing?: { name?: string | null; city?: string | null; state?: string | null; zip?: string | null } | null
    shipping?: { name?: string | null; city?: string | null; state?: string | null; zip?: string | null } | null
  } | null
  consignee_customer_id?: number | null
  consignee?: {
    id: number
    name: string
    contact_name?: string | null
    phone?: string | null
  } | null
  gst_tax_payable_by?: string | null
  tr_gst_payable_by?: string | null
}

interface Props {
  isLoading?: boolean
  itemValidationScope?: string
  store?: Record<string, any>
  storeProp?: string
  isEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  itemValidationScope: 'newInvoice',
  storeProp: 'newInvoice',
})

const items = computed(() => {
  if (!props.store || !props.storeProp) return []
  return props.store[props.storeProp]?.items || []
})

const loadingStates = ref<Record<number, boolean>>({})
const suggestions = ref<Record<number, ConsignmentData[]>>({})
const lookupStatus = ref<Record<number, { type: 'success' | 'not_found'; message: string } | null>>({})
const debounceTimers: Record<number, any> = {}

function getApiClient() {
  return (
    (window as any).__invoiceReceiptClient ||
    (window as any).__lrReceiptClient ||
    (window as any).axios ||
    window.axios
  )
}

const addNewItem = () => {
  const newItem: TransportItem = {
    id: Math.random().toString(36).substr(2, 9),
    // Standard fields the host requires (validation + totals). Amount drives
    // price/total below; quantity stays 1 so total === amount.
    name: '',
    quantity: 1,
    price: 0,
    total: 0,
    discount: 0,
    discount_type: 'fixed',
    discount_val: 0,
    taxes: [],
    tr_consignment_number: '',
    tr_consignment_date: '',
    tr_party_inv_no: '',
    tr_from_name: '',
    tr_to_name: '',
    tr_truck_no: '',
    tr_pkg_weight: '',
    tr_charged_weight: '',
    tr_rate: 0,
    tr_other_charge: 0,
    tr_lr_charge: 0,
    tr_dd_charge: 0,
    amount: 0,
  }
  props.store[props.storeProp].items.push(newItem)
}

const deleteItem = (itemId: number | string | undefined, index: number) => {
  if (debounceTimers[index]) {
    clearTimeout(debounceTimers[index])
    delete debounceTimers[index]
  }
  delete loadingStates.value[index]
  delete suggestions.value[index]
  delete lookupStatus.value[index]
  items.value.splice(index, 1)
}

const getNum = (value: string | number | null | undefined): number => {
  const num = Number(value)
  return isNaN(num) ? 0 : num
}

const calculateAmount = (item: TransportItem): number => {
  const rate = getNum(item.tr_rate)
  const otherCharge = getNum(item.tr_other_charge)
  const lrCharge = getNum(item.tr_lr_charge)
  const ddCharge = getNum(item.tr_dd_charge)
  return rate + otherCharge + lrCharge + ddCharge
}

// Amount is entered in major units (rupees). The host stores money in minor
// units and computes the document subtotal from item.total (= price × quantity),
// so mirror the amount into price/total in minor units and keep quantity at 1.
// name/price/quantity also satisfy the host's item validation rules.
const syncItem = (item: TransportItem) => {
  const amount = calculateAmount(item)
  item.amount = amount

  const minor = Math.round(amount * 100)
  item.price = minor
  item.total = minor
  item.quantity = 1
  item.name = item.tr_consignment_number || 'Consignment'
}

function applyConsignmentData(item: TransportItem, data: ConsignmentData, index: number) {
  item.tr_consignment_number = data.consignment_number
  if (data.consignment_date) item.tr_consignment_date = data.consignment_date
  if (data.party_inv_no) item.tr_party_inv_no = data.party_inv_no
  if (data.from_code) item.tr_from_name = data.from_code
  if (data.to_code) item.tr_to_name = data.to_code
  if (data.truck_no) item.tr_truck_no = data.truck_no
  if (data.pkg) item.tr_pkg_weight = data.pkg
  if (data.weight) item.tr_charged_weight = data.weight
  if (data.rate !== undefined && data.rate !== null) item.tr_rate = Number(data.rate)
  if (data.other_charge !== undefined && data.other_charge !== null) item.tr_other_charge = Number(data.other_charge)
  if (data.lr_charge !== undefined && data.lr_charge !== null) item.tr_lr_charge = Number(data.lr_charge)
  if (data.dd_charge !== undefined && data.dd_charge !== null) item.tr_dd_charge = Number(data.dd_charge)

  syncItem(item)

  // Auto-select customer if not yet selected on the invoice
  if (props.store?.newInvoice) {
    const currentCustId =
      props.store.newInvoice.customer_id ||
      props.store.newInvoice.customer?.id
    if (!currentCustId && data.customer_id) {
      if (data.customer) {
        props.store.newInvoice.customer = data.customer
        props.store.newInvoice.customer_id = data.customer_id
        if (data.customer.currency_id) {
          props.store.newInvoice.currency_id = data.customer.currency_id
        }
      } else {
        props.store.newInvoice.customer_id = data.customer_id
      }

      if (typeof props.store.selectCustomer === 'function') {
        props.store.selectCustomer(data.customer_id).catch(() => {
          // Handled silently
        })
      }
    }

    // Auto-select consignee if not yet selected and available
    if (!props.store.newInvoice.tr_consignee_customer_id && data.consignee_customer_id) {
      props.store.newInvoice.tr_consignee_customer_id = data.consignee_customer_id
      if (data.consignee) {
        props.store.newInvoice.consignee = data.consignee
      }
    }

    // Auto-fill GST Tax Payable By if present
    const gstVal = data.gst_tax_payable_by || data.tr_gst_payable_by
    if (gstVal) {
      if (!props.store.newInvoice.tr_gst_payable_by) {
        props.store.newInvoice.tr_gst_payable_by = gstVal
      }
      if (!props.store.newInvoice.gst_tax_payable_by) {
        props.store.newInvoice.gst_tax_payable_by = gstVal
      }
      if (Array.isArray(props.store.newInvoice.customFields)) {
        for (const cf of props.store.newInvoice.customFields) {
          const lbl = (cf.label || '').toLowerCase().trim()
          if (lbl === 'gst tax payable by' || lbl === 'gst tax through' || lbl === 'gst payable by') {
            cf.value = gstVal
          }
        }
      }
    }
  }

  suggestions.value[index] = []
  lookupStatus.value[index] = {
    type: 'success',
    message: `Autofilled from LR Receipt #${data.consignment_number}`,
  }
}

async function fetchConsignmentExact(item: TransportItem, index: number, number: string) {
  const trimmed = number.trim()
  if (!trimmed) return

  const client = getApiClient()
  if (!client) return

  loadingStates.value[index] = true
  try {
    const res = await client.get(`/api/v1/invoice-receipts/consignments/${encodeURIComponent(trimmed)}`)
    if (res.data?.data) {
      applyConsignmentData(item, res.data.data, index)
    } else {
      lookupStatus.value[index] = {
        type: 'not_found',
        message: 'No LR Receipt found',
      }
    }
  } catch (_e) {
    lookupStatus.value[index] = {
      type: 'not_found',
      message: 'No LR Receipt found',
    }
  } finally {
    loadingStates.value[index] = false
  }
}

function onConsignmentInput(item: TransportItem, index: number, val: string) {
  item.tr_consignment_number = val

  if (debounceTimers[index]) {
    clearTimeout(debounceTimers[index])
  }

  const trimmed = (val || '').trim()
  if (!trimmed) {
    suggestions.value[index] = []
    lookupStatus.value[index] = null
    return
  }

  debounceTimers[index] = setTimeout(async () => {
    const client = getApiClient()
    if (!client) return

    loadingStates.value[index] = true
    try {
      const res = await client.get('/api/v1/invoice-receipts/consignments/search', {
        params: { query: trimmed },
      })
      const list: ConsignmentData[] = res.data?.data || []
      suggestions.value[index] = list

      // If exact match found in list, auto-apply it!
      const exactMatch = list.find(
        (c) => c.consignment_number.toLowerCase() === trimmed.toLowerCase(),
      )
      if (exactMatch) {
        applyConsignmentData(item, exactMatch, index)
      } else if (list.length === 0) {
        lookupStatus.value[index] = {
          type: 'not_found',
          message: 'No LR Receipt found',
        }
      }
    } catch (_err) {
      // Fallback silently
    } finally {
      loadingStates.value[index] = false
    }
  }, 350)
}

function onConsignmentBlur(item: TransportItem, index: number) {
  // Allow time for suggestion mousedown to fire
  setTimeout(() => {
    suggestions.value[index] = []
    if (item.tr_consignment_number && !lookupStatus.value[index]) {
      fetchConsignmentExact(item, index, item.tr_consignment_number)
    }
  }, 200)
}

function onConsignmentEnter(item: TransportItem, index: number) {
  if (suggestions.value[index]?.length) {
    applyConsignmentData(item, suggestions.value[index][0], index)
  } else if (item.tr_consignment_number) {
    fetchConsignmentExact(item, index, item.tr_consignment_number)
  }
}

// BaseInput only emits `update:modelValue`, so per-field @input handlers don't
// fire. Watch every item deeply instead and keep the host-facing fields
// (name/price/total/amount) in lockstep with the transport inputs — this drives
// the document Sub Amount and keeps it equal to the row Amounts.
watch(
  items,
  (list) => {
    list.forEach((item: TransportItem) => syncItem(item))
  },
  { deep: true, immediate: true },
)
</script>
