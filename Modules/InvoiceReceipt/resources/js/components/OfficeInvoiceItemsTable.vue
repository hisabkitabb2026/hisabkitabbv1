<template>
  <BaseCard class="mb-6">
    <template #header>
      <h3 class="text-base font-semibold text-heading">Consignment Items</h3>
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
          <span class="text-sm font-semibold text-muted">Item {{ index + 1 }}</span>
          <button
            v-if="items.length > 1"
            type="button"
            class="text-alert-error-text hover:text-alert-error-text"
            @click="deleteItem(item.id, index)"
          >
            <BaseIcon name="TrashIcon" class="h-5 w-5" />
          </button>
        </div>

        <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
          <!-- Consignment No -->
          <BaseInputGroup label="Consignment No" required>
            <BaseInput
              v-model="item.consignment_number"
              type="text"
              placeholder="Enter consignment number"
            />
          </BaseInputGroup>

          <!-- Consignment Date -->
          <BaseInputGroup label="Consignment Date">
            <BaseInput
              v-model="item.consignment_date"
              type="date"
            />
          </BaseInputGroup>

          <!-- Party Inv No -->
          <BaseInputGroup label="Party Inv No">
            <BaseInput
              v-model="item.party_inv_no"
              type="text"
              placeholder="Party invoice number"
            />
          </BaseInputGroup>

          <!-- From (Origin) -->
          <BaseInputGroup label="From">
            <BaseInput
              v-model="item.from_code"
              type="text"
              placeholder="Origin code"
            />
          </BaseInputGroup>

          <!-- To (Destination) -->
          <BaseInputGroup label="Destination">
            <BaseInput
              v-model="item.to_code"
              type="text"
              placeholder="Destination code"
            />
          </BaseInputGroup>

          <!-- Vehicle No -->
          <BaseInputGroup label="Vehicle No">
            <BaseInput
              v-model="item.truck_no"
              type="text"
              placeholder="Truck/vehicle number"
            />
          </BaseInputGroup>

          <!-- Pkg (Package Type) -->
          <BaseInputGroup label="Pkg">
            <BaseInput
              v-model="item.pkg"
              type="text"
              placeholder="Package type"
            />
          </BaseInputGroup>

          <!-- Weight -->
          <BaseInputGroup label="Weight">
            <BaseInput
              v-model="item.weight"
              type="text"
              placeholder="Shipment weight"
            />
          </BaseInputGroup>

          <!-- Rate -->
          <BaseInputGroup label="Rate">
            <BaseInput
              v-model.number="item.rate"
              type="number"
              placeholder="Freight rate"
            />
          </BaseInputGroup>

          <!-- Other Charge -->
          <BaseInputGroup label="Other Charge">
            <BaseInput
              v-model.number="item.other_charge"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>

          <!-- LR Charge -->
          <BaseInputGroup label="LR Charge">
            <BaseInput
              v-model.number="item.lr_charge"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>

          <!-- DD Charge -->
          <BaseInputGroup label="DD Charge">
            <BaseInput
              v-model.number="item.dd_charge"
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
import { computed, watch } from 'vue'
import type { InvoiceItem } from '@/scripts/types/domain/invoice'

interface TransportItem extends Partial<InvoiceItem> {
  consignment_number?: string | null
  consignment_date?: string | null
  party_inv_no?: string | null
  from_code?: string | null
  to_code?: string | null
  truck_no?: string | null
  pkg?: string | null
  weight?: string | null
  rate?: string | number | null
  other_charge?: string | number | null
  lr_charge?: string | number | null
  dd_charge?: string | number | null
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
    consignment_number: '',
    consignment_date: '',
    party_inv_no: '',
    from_code: '',
    to_code: '',
    truck_no: '',
    pkg: '',
    weight: '',
    rate: 0,
    other_charge: 0,
    lr_charge: 0,
    dd_charge: 0,
    amount: 0,
  }
  props.store[props.storeProp].items.push(newItem)
}

const deleteItem = (itemId: number | string | undefined, index: number) => {
  items.value.splice(index, 1)
}

const getNum = (value: string | number | null | undefined): number => {
  const num = Number(value)
  return isNaN(num) ? 0 : num
}

const calculateAmount = (item: TransportItem): number => {
  const rate = getNum(item.rate)
  const otherCharge = getNum(item.other_charge)
  const lrCharge = getNum(item.lr_charge)
  const ddCharge = getNum(item.dd_charge)
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
  item.name = item.consignment_number || 'Consignment'
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
