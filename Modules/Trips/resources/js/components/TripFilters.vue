<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { EMPTY_FILTERS, hasFilters } from '@/support/filters'
import type { TripFilterState } from '@/support/filters'
import type { TripStatus } from '@/types/trip'

interface FilterOption {
  id: string
  label: string
}

const props = defineProps<{
  modelValue: TripFilterState
  statuses: TripStatus[]
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', filters: TripFilterState): void
}>()

const SEARCH_DEBOUNCE_MS = 350

const search = ref(props.modelValue.search)

let searchTimer: ReturnType<typeof setTimeout> | undefined

const statusOptions = computed<FilterOption[]>(() => [
  { id: '', label: 'All statuses' },
  ...props.statuses.map((status) => ({ id: String(status.id), label: status.name })),
])

const statusOption = computed<FilterOption>({
  get: () => optionFor(statusOptions.value, props.modelValue.status),
  set: (option: FilterOption | null) => apply({ status: option?.id ?? '' }),
})

const showClear = computed<boolean>(() => hasFilters(props.modelValue))

watch(
  () => props.modelValue.search,
  (value) => {
    if (value !== search.value) {
      search.value = value
    }
  },
)

watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => apply({ search: value.trim() }), SEARCH_DEBOUNCE_MS)
})

onBeforeUnmount(() => clearTimeout(searchTimer))

function optionFor(options: FilterOption[], id: string): FilterOption {
  return options.find((option) => option.id === id) ?? options[0]
}

function apply(partial: Partial<TripFilterState>): void {
  emit('update:modelValue', { ...props.modelValue, ...partial })
}

function toggleUnbilled(): void {
  apply({ unbilled: props.modelValue.unbilled === '1' ? '' : '1' })
}

function clear(): void {
  search.value = ''
  emit('update:modelValue', { ...EMPTY_FILTERS })
}
</script>

<template>
  <div class="mt-4 flex flex-wrap items-end gap-3">
    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">Status</span>
      <BaseSelectInput v-model="statusOption" :options="statusOptions" label-key="label" />
    </label>

    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">Customer</span>
      <BaseCustomerSelectInput
        :model-value="modelValue.customer ? Number(modelValue.customer) : null"
        can-deselect
        placeholder="All customers"
        @update:model-value="(v: number | null) => apply({ customer: v ? String(v) : '' })"
      />
    </label>

    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">Search</span>
      <BaseInput
        v-model="search"
        type="text"
        name="search"
        autocomplete="off"
        placeholder="Search route, lorry, goods..."
      />
    </label>

    <label class="flex items-center gap-1.5 pb-2.5 text-sm text-body">
      <input
        type="checkbox"
        class="accent-primary-600"
        :checked="modelValue.unbilled === '1'"
        @change="toggleUnbilled"
      />
      Unbilled only
    </label>

    <button
      v-if="showClear"
      type="button"
      class="pb-2.5 text-sm font-medium text-primary-500 hover:underline"
      @click="clear"
    >
      Clear
    </button>
  </div>
</template>
