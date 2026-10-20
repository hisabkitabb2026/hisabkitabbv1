<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { fetchUnlinkedReceipts } from '@/api/trips'
import type { CustomerOption, UnlinkedReceipt } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { formatMoney, today } from '@/support/format'
import type { Notify } from '@/support/page'

const props = defineProps<{
  show: boolean
  client: AxiosInstance
  notify: Notify
  type?: 'lr' | 'lorry'
  multiple?: boolean
  customers?: CustomerOption[]
  tripId?: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'select', invoiceIds: number[]): void
}>()

const receipts = ref<UnlinkedReceipt[]>([])
const selected = ref<number[]>([])
const customerFilter = ref<number | null>(null)
const showAllUnlinked = ref(false)
const loading = ref(false)

const isMultiple = computed<boolean>(() => props.multiple ?? true)

async function load(): Promise<void> {
  loading.value = true

  try {
    receipts.value = await fetchUnlinkedReceipts(
      props.client,
      props.type ?? 'lr',
      customerFilter.value,
      props.tripId ?? null,
      showAllUnlinked.value,
    )
    selected.value = []
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the receipts.'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.show) {
    void load()
  }
})

watch(
  () => props.show,
  (show) => {
    if (show) {
      showAllUnlinked.value = false
      void load()
    }
  },
)

watch([customerFilter, showAllUnlinked], () => {
  void load()
})

function toggle(id: number): void {
  if (!isMultiple.value) {
    selected.value = [id]

    return
  }

  if (selected.value.includes(id)) {
    selected.value = selected.value.filter((i) => i !== id)
  } else {
    selected.value = [...selected.value, id]
  }
}

const selectedTotal = computed<number>(() =>
  receipts.value
    .filter((r) => selected.value.includes(r.id))
    .reduce((sum, r) => sum + r.amount, 0),
)

const mixedRoutes = computed<boolean>(() => {
  const chosen = receipts.value.filter((r) => selected.value.includes(r.id))
  const routes = new Set(chosen.map((r) => `${r.from_city}→${r.to_city}`))

  return routes.size > 1
})

function confirm(): void {
  if (selected.value.length === 0) {
    return
  }

  emit('select', [...selected.value])
}
</script>

<template>
  <BaseModal :show="show" @close="emit('close')">
    <template #header>
      <span>{{ type === 'lorry' ? 'Map Lorry Receipt' : 'Map LR Receipts' }}</span>
    </template>

    <template #default>
      <div class="space-y-3">
        <BaseInputGroup v-if="customers && customers.length > 0" label="Customer">
          <BaseSelectInput
            v-model="customerFilter"
            :options="[{ id: null, label: 'All customers' }, ...customers.map((c) => ({ id: c.id, label: c.name }))]"
            value-prop="id"
            label-key="label"
          />
        </BaseInputGroup>

        <div v-if="type === 'lorry' && tripId" class="flex items-center justify-between text-xs text-muted">
          <span>{{ showAllUnlinked ? 'Showing all unlinked lorry receipts' : 'Showing lorry receipts matching this trip’s LR numbers' }}</span>
          <button
            type="button"
            class="font-medium text-primary-600 hover:underline"
            @click="showAllUnlinked = !showAllUnlinked"
          >
            {{ showAllUnlinked ? 'Show matching only' : 'Show all unlinked' }}
          </button>
        </div>

        <div v-else-if="type === 'lr' && tripId" class="flex items-center justify-between text-xs text-muted">
          <span>{{ showAllUnlinked ? 'Showing all unlinked LR receipts' : 'Showing unlinked LR receipts for customers on this trip' }}</span>
          <button
            type="button"
            class="font-medium text-primary-600 hover:underline"
            @click="showAllUnlinked = !showAllUnlinked"
          >
            {{ showAllUnlinked ? 'Show trip customers only' : 'Browse all customers' }}
          </button>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-muted">Loading…</div>

        <div v-else-if="receipts.length === 0" class="rounded-lg bg-surface-secondary p-4 text-sm text-muted">
          <p v-if="type === 'lorry' && tripId && !showAllUnlinked">
            No lorry receipt found with matching bilty numbers for this trip's LRs.
            <button
              type="button"
              class="mt-1 block font-medium text-primary-600 hover:underline"
              @click="showAllUnlinked = true"
            >
              Browse all unlinked lorry receipts instead &rarr;
            </button>
          </p>
          <p v-else-if="type === 'lr' && tripId && !showAllUnlinked">
            No more unassigned LR receipts found for the customers on this trip.
            <button
              type="button"
              class="mt-1 block font-medium text-primary-600 hover:underline"
              @click="showAllUnlinked = true"
            >
              Browse all unlinked LR receipts instead &rarr;
            </button>
          </p>
          <p v-else>
            No unlinked receipts. Create one first.
          </p>
        </div>

        <ul v-else class="max-h-72 space-y-1 overflow-y-auto">
          <li v-for="receipt in receipts" :key="receipt.id">
            <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
            <label
              class="flex cursor-pointer items-center gap-3 rounded-lg border border-line-default bg-surface p-2.5 text-sm hover:bg-hover"
              :class="{ 'border-primary-500': selected.includes(receipt.id) }"
              @click.prevent="toggle(receipt.id)"
            >
              <input
                type="checkbox"
                class="accent-primary-600"
                :checked="selected.includes(receipt.id)"
                @change="toggle(receipt.id)"
              />
              <span class="min-w-0 flex-1">
                <span class="block font-medium text-heading">
                  {{ receipt.invoice_number }} · {{ receipt.from_city ?? '—' }} → {{ receipt.to_city ?? '—' }}
                </span>
                <span class="block truncate text-xs text-muted">
                  {{ receipt.customer_name ?? 'No customer' }} · {{ receipt.goods ?? '' }}
                </span>
              </span>
              <span class="text-sm font-semibold text-heading">{{ formatMoney(receipt.amount) }}</span>
            </label>
          </li>
        </ul>

        <p v-if="selected.length > 0" class="text-sm text-muted">
          Selected: {{ selected.length }} · {{ formatMoney(selectedTotal) }}
        </p>
        <p v-if="mixedRoutes" class="text-sm text-alert-warning-text">
          ⚠ Different routes selected — same vehicle?
        </p>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="primary-outline" size="sm" @click="emit('close')">Cancel</BaseButton>
        <BaseButton
          variant="primary"
          size="sm"
          :disabled="selected.length === 0"
          @click="confirm"
        >
          {{ isMultiple ? `Link ${selected.length}` : 'Link' }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>
