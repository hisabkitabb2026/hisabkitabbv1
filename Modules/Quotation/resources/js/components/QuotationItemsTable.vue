<template>
  <BaseCard class="mb-6">
    <template #header>
      <h3 class="text-base font-semibold text-heading">Quotation Items</h3>
    </template>

    <div v-if="isLoading" class="space-y-3">
      <BaseContentPlaceholders v-for="i in 2" :key="i">
        <BaseContentPlaceholdersBox :rounded="true" class="w-full" style="min-height: 60px" />
      </BaseContentPlaceholders>
    </div>

    <div v-else>
      <!-- Item rows with quotation fields -->
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

        <!-- Station Name -->
        <BaseInputGroup label="Station Name" required class="mb-4">
          <BaseInput
            v-model="item.tr_station_name"
            type="text"
            placeholder="Enter station name"
          />
        </BaseInputGroup>

        <!-- Capacity rate fields — each capacity is a labeled input where the
             user types the rate for that vehicle capacity. -->
        <div class="grid gap-x-4 gap-y-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          <BaseInputGroup
            v-for="cap in CAPACITIES"
            :key="cap.key"
            :label="cap.label"
          >
            <BaseInput
              v-model.number="item[cap.key]"
              type="number"
              placeholder="0.00"
            />
          </BaseInputGroup>
        </div>
      </div>

      <!-- Add Item button -->
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 border border-line-default rounded-lg hover:bg-surface-secondary transition-colors"
        @click="addNewItem"
      >
        <BaseIcon name="PlusIcon" class="h-4 w-4" />
        Add Item
      </button>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'

interface QuotationItem {
  id?: string | number
  name?: string | null
  quantity?: number
  price?: number
  total?: number
  discount?: number
  discount_type?: string
  discount_val?: number
  taxes?: unknown[]
  tr_station_name?: string | null
  tr_rate_9mt?: number | string | null
  tr_rate_10mt?: number | string | null
  tr_rate_12mt?: number | string | null
  tr_rate_15mt?: number | string | null
  tr_rate_18mt?: number | string | null
  tr_rate_24mt?: number | string | null
  tr_rate_30mt?: number | string | null
  amount?: number
  [key: string]: unknown
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
  itemValidationScope: 'newEstimate',
  storeProp: 'newEstimate',
})

// Each capacity is a labeled input field. The user types a rate into each one.
const CAPACITIES = [
  { key: 'tr_rate_9mt', label: '9 mt' },
  { key: 'tr_rate_10mt', label: '10 mt' },
  { key: 'tr_rate_12mt', label: '12 mt' },
  { key: 'tr_rate_15mt', label: '15 mt' },
  { key: 'tr_rate_18mt', label: '18 mt' },
  { key: 'tr_rate_24mt', label: '24 mt' },
  { key: 'tr_rate_30mt', label: '30 mt' },
] as const

const items = computed(() => {
  if (!props.store || !props.storeProp) return []
  return props.store[props.storeProp]?.items || []
})

const addNewItem = () => {
  const newItem: Record<string, unknown> = {
    id: Math.random().toString(36).substr(2, 9),
    name: '',
    quantity: 1,
    price: 0,
    total: 0,
    discount: 0,
    discount_type: 'fixed',
    discount_val: 0,
    tax: 0,
    taxes: [],
    tr_station_name: '',
    amount: 0,
  }
  for (const cap of CAPACITIES) {
    newItem[cap.key] = 0
  }
  props.store[props.storeProp].items.push(newItem)
}

const deleteItem = (itemId: number | string | undefined, index: number) => {
  items.value.splice(index, 1)
}

// Quotations are rate cards — the capacity fields hold reference rates, not
// billable amounts — so price/total stay at 0. We only sync the station name
// into the host-facing `name` field to satisfy item validation.
const syncItem = (item: QuotationItem) => {
  item.name = item.tr_station_name || 'Station'
  item.quantity = 1
  item.price = 0
  item.total = 0
  item.tax = 0
}

// Watch every item deeply and keep the host-facing name field in lockstep with
// the station name input — this satisfies the host's item validation rules.
watch(
  items,
  (list) => {
    list.forEach((item: QuotationItem) => syncItem(item))
  },
  { deep: true, immediate: true },
)
</script>
