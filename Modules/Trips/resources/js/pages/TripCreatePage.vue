<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { createTripFromLrs, fetchLorryMatch, fetchUnlinkedReceipts } from '@/api/trips'
import type { UnlinkedReceipt } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { formatMoney, today } from '@/support/format'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'

/** The host router, handed in by injectedPage. */
interface RouterLike {
  push(path: string): unknown
}

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  router?: RouterLike
}>()

/** One allocation row, exactly like a payment allocation. */
interface AllocationRow {
  receipt_id: number | null
}

const receipts = ref<UnlinkedReceipt[]>([])
const customerFilter = ref<number | null>(null)
const pickupDate = ref(today())
const rows = ref<AllocationRow[]>([])
const loading = ref(false)
const saving = ref(false)

/** The lorry receipt the backend would auto-match for the current selection. */
const lorryMatch = ref<UnlinkedReceipt | null>(null)
const lorryMatchLoading = ref(false)

async function load(): Promise<void> {
  loading.value = true

  try {
    const receiptList = await fetchUnlinkedReceipts(props.client, 'lr', customerFilter.value)
    receipts.value = receiptList

    // Start with one row ready, like the payments form feels ready.
    if (rows.value.length === 0 && receiptList.length > 0) {
      rows.value = [{ receipt_id: receiptList[0].id }]
    }
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the receipts.'))
  } finally {
    loading.value = false
  }
}

watch(customerFilter, (value) => {
  if (value === null) {
    receipts.value = []
    rows.value = []
    lorryMatch.value = null

    return
  }

  void load()
})

/** Options for one row's select: everything unchosen by the other rows. */
function rowOptions(index: number): Array<{ id: number; label: string }> {
  const taken = rows.value
    .map((row, i) => (i !== index ? row.receipt_id : null))
    .filter((id): id is number => id !== null)

  return receipts.value
    .filter((receipt) => !taken.includes(receipt.id))
    .map((receipt) => ({
      id: receipt.id,
      label: `${receipt.invoice_number} · ${receipt.from_city ?? '—'} → ${receipt.to_city ?? '—'} · ${receipt.customer_name ?? 'No customer'}`,
    }))
}

/** Is there a receipt left to start a new row with? */
const hasSpareReceipt = computed<boolean>(() => {
  const taken = chosenIds.value

  return receipts.value.some((receipt) => !taken.includes(receipt.id))
})

const chosenIds = computed<number[]>(() =>
  rows.value
    .map((row) => row.receipt_id)
    .filter((id): id is number => id !== null),
)

function addRow(): void {
  const next = receipts.value.find((receipt) => !chosenIds.value.includes(receipt.id))

  rows.value.push({ receipt_id: next?.id ?? null })
}

function removeRow(index: number): void {
  rows.value.splice(index, 1)
}

/**
 * The payments page's "Allocate Oldest First", our way: a lorry carries every
 * unlinked LR on it, so take them all — oldest receipt date first.
 */
function addOldestFirst(): void {
  rows.value = [...receipts.value]
    .sort(
      (a, b) =>
        (a.invoice_date ?? '9999-12-31').localeCompare(b.invoice_date ?? '9999-12-31') || a.id - b.id,
    )
    .map((receipt) => ({ receipt_id: receipt.id }))
}

function amountFor(receiptId: number | null): number {
  return receipts.value.find((receipt) => receipt.id === receiptId)?.amount ?? 0
}

const chosenReceipts = computed<UnlinkedReceipt[]>(() =>
  rows.value
    .map((row) => receipts.value.find((receipt) => receipt.id === row.receipt_id))
    .filter((receipt): receipt is UnlinkedReceipt => receipt !== undefined),
)

const revenue = computed<number>(() =>
  chosenReceipts.value.reduce((sum, receipt) => sum + receipt.amount, 0),
)

const cost = computed<number>(() => lorryMatch.value?.amount ?? 0)

const profit = computed<number>(() => revenue.value - cost.value)

const mixedRoutes = computed<boolean>(() => {
  const routes = new Set(chosenReceipts.value.map((r) => `${r.from_city}→${r.to_city}`))

  return routes.size > 1
})

/** Ask the backend which lorry receipt the bilty numbers point at. */
async function refreshLorryMatch(): Promise<void> {
  if (chosenIds.value.length === 0) {
    lorryMatch.value = null

    return
  }

  lorryMatchLoading.value = true

  try {
    const matches = await fetchLorryMatch(props.client, chosenIds.value)
    lorryMatch.value = matches[0] ?? null
  } catch {
    lorryMatch.value = null
  } finally {
    lorryMatchLoading.value = false
  }
}

let matchTimer: ReturnType<typeof setTimeout> | null = null

watch(chosenIds, () => {
  if (matchTimer) {
    clearTimeout(matchTimer)
  }

  // Debounce: the user may add several rows quickly.
  matchTimer = setTimeout(() => {
    void refreshLorryMatch()
  }, 400)
}, { deep: true })

async function submit(): Promise<void> {
  if (chosenIds.value.length === 0) {
    props.notify('warning', 'Add at least one LR receipt.')

    return
  }

  saving.value = true

  try {
    const trip = await createTripFromLrs(props.client, chosenIds.value)

    props.notify('success', `Trip #${trip.trip_no} created.`)
    props.router?.push(PATHS.trip(trip.id))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to create the trip.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BasePage class="relative">
    <form class="flex flex-col gap-4 md:gap-5" @submit.prevent="submit">
      <BasePageHeader title="New Trip">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem title="Home" to="/admin/dashboard" />
          <BaseBreadcrumbItem title="Trips" :to="PATHS.board" />
          <BaseBreadcrumbItem title="New Trip" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton :loading="saving" :disabled="saving" variant="primary" type="submit">
            Create Trip
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard container-class="p-4 md:p-5">
        <BaseInputGrid>
          <BaseInputGroup label="Customer (filter)">
            <BaseCustomerSelectInput
              v-model="customerFilter"
              can-deselect
              placeholder="All customers"
            />
          </BaseInputGroup>

          <BaseInputGroup label="Pickup date">
            <BaseInput v-model="pickupDate" type="date" />
          </BaseInputGroup>
        </BaseInputGrid>

        <!-- ── LR Receipts: the allocation section ─────────────────────── -->
        <section class="mt-5 border-t border-line-light pt-5">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-section font-semibold text-heading">LR Receipts</h2>
              <p class="mt-1 text-sm text-muted">
                Choose the LR receipts this trip carries. Pick several for a shared load.
              </p>
            </div>
            <div class="flex gap-2">
              <BaseButton
                type="button"
                size="sm"
                variant="primary-outline"
                :disabled="loading || receipts.length === 0"
                @click="addOldestFirst"
              >
                Add Oldest First
              </BaseButton>
              <BaseButton
                type="button"
                size="sm"
                variant="primary-outline"
                :disabled="loading || !hasSpareReceipt"
                @click="addRow"
              >
                <template #left="slotProps"><BaseIcon name="PlusIcon" :class="slotProps.class" /></template>
                Add LR Receipt
              </BaseButton>
            </div>
          </div>

          <div
            v-if="customerFilter === null"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Select a customer first — the LR receipts for that customer will appear here.
          </div>

          <div v-else-if="loading" class="rounded-lg bg-surface-secondary p-4 text-sm text-muted" role="status">
            Loading the receipts…
          </div>

          <div
            v-else-if="receipts.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            No unlinked LR receipts. Create one first, then start the trip.
          </div>

          <div
            v-else-if="rows.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Add the LR receipts this trip carries.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(row, index) in rows"
              :key="index"
              class="grid items-end gap-3 rounded-lg bg-surface-secondary p-3 md:grid-cols-[minmax(0,1fr)_10rem_auto]"
            >
              <BaseInputGroup label="LR Receipt">
                <BaseSelectInput
                  v-model="row.receipt_id"
                  :options="rowOptions(index)"
                  label-key="label"
                  placeholder="Select an LR receipt"
                />
              </BaseInputGroup>

              <BaseInputGroup label="Amount">
                <div
                  class="flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading"
                >
                  {{ formatMoney(amountFor(row.receipt_id)) }}
                </div>
              </BaseInputGroup>

              <div class="justify-self-end">
                <BaseButton
                  type="button"
                  size="sm"
                  variant="gray"
                  aria-label="Remove LR receipt"
                  @click="removeRow(index)"
                >
                  <BaseIcon name="TrashIcon" />
                </BaseButton>
              </div>
            </div>
          </div>

          <p v-if="mixedRoutes" class="mt-3 text-sm text-alert-warning-text">
            ⚠ Different routes selected — same vehicle?
          </p>

          <div class="mt-4 flex flex-wrap justify-end gap-x-8 gap-y-2 border-t border-line-light pt-4 text-sm">
            <span class="text-muted">
              Receipts: <span class="font-semibold text-heading">{{ chosenReceipts.length }}</span>
            </span>
            <span class="text-muted">
              Revenue: <span class="font-semibold text-heading">{{ formatMoney(revenue) }}</span>
            </span>
          </div>
        </section>

        <!-- ── Lorry Receipt: auto-matched, never picked by hand ────────── -->
        <section class="mt-5 border-t border-line-light pt-5">
          <div class="mb-4">
            <h2 class="text-section font-semibold text-heading">Lorry Receipt</h2>
            <p class="mt-1 text-sm text-muted">
              Auto-matched from the bilty numbers on the selected LR receipts — no need to pick it.
            </p>
          </div>

          <div
            v-if="customerFilter === null"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Select a customer first — the lorry receipt follows from the LR receipts.
          </div>

          <div
            v-else-if="lorryMatchLoading"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Finding the lorry receipt…
          </div>

          <div
            v-else-if="chosenIds.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Select an LR receipt first — the lorry receipt follows from its bilty numbers.
          </div>

          <div
            v-else-if="!lorryMatch"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            No lorry receipt lists these bilty numbers yet. The trip starts without one; link it
            later from the trip page.
          </div>

          <div
            v-else
            class="flex items-center justify-between gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-semibold text-heading">
                🚛 {{ lorryMatch.invoice_number }}
                <span class="ms-2 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700">
                  Auto-matched
                </span>
              </p>
              <p class="mt-0.5 truncate text-xs text-muted">
                {{ lorryMatch.from_city ?? '—' }} → {{ lorryMatch.to_city ?? '—' }}
                <span v-if="lorryMatch.customer_name"> · {{ lorryMatch.customer_name }}</span>
              </p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-heading">{{ formatMoney(lorryMatch.amount) }}</p>
          </div>
        </section>

        <!-- ── The money ───────────────────────────────────────────────── -->
        <section class="mt-5 border-t border-line-light pt-5">
          <h2 class="mb-4 text-section font-semibold text-heading">The Money</h2>

          <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg bg-surface-secondary p-3">
              <p class="text-xs text-muted">Revenue</p>
              <p class="text-lg font-semibold text-heading">{{ formatMoney(revenue) }}</p>
            </div>
            <div class="rounded-lg bg-surface-secondary p-3">
              <p class="text-xs text-muted">Cost</p>
              <p class="text-lg font-semibold text-heading">{{ formatMoney(cost) }}</p>
            </div>
            <div class="rounded-lg bg-surface-secondary p-3">
              <p class="text-xs text-muted">Profit</p>
              <p
                class="text-lg font-semibold"
                :class="profit >= 0 ? 'text-status-green' : 'text-status-red'"
              >
                {{ formatMoney(profit) }}
              </p>
            </div>
          </div>
        </section>
      </BaseCard>
    </form>
  </BasePage>
</template>
