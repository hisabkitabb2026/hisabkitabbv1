<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { createTripFromLrs, fetchUnlinkedReceipts } from '@/api/trips'
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
const knownReceipts = ref<Map<number, UnlinkedReceipt>>(new Map())
const customerFilter = ref<number | null>(null)
const pickupDate = ref(today())
const rows = ref<AllocationRow[]>([])
const loading = ref(false)
const saving = ref(false)

async function load(): Promise<void> {
  loading.value = true

  try {
    const receiptList = await fetchUnlinkedReceipts(props.client, 'lr', customerFilter.value)
    receipts.value = receiptList

    for (const r of receiptList) {
      knownReceipts.value.set(r.id, r)
    }

    // Start with one row ready only if rows are completely empty
    if (rows.value.length === 0 && receiptList.length > 0) {
      rows.value = [{ receipt_id: receiptList[0].id }]
    }
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the receipts.'))
  } finally {
    loading.value = false
  }
}

watch(customerFilter, () => {
  // Preserve existing rows when changing or clearing customer filter!
  void load()
})

function getReceiptById(id: number | null | unknown): UnlinkedReceipt | undefined {
  if (!id) return undefined
  const rawId = typeof id === 'object' && id !== null ? (id as any).id : id
  const num = Number(rawId)
  if (!num || isNaN(num)) return undefined
  return knownReceipts.value.get(num) ?? receipts.value.find((r) => r.id === num)
}

/** Options for one row's select: everything unchosen by the other rows. */
function rowOptions(index: number): Array<{ id: number; label: string }> {
  const currentRowId = rows.value[index]?.receipt_id
  const currentId = typeof currentRowId === 'object' && currentRowId !== null ? (currentRowId as any).id : currentRowId
  const currentNum = currentId ? Number(currentId) : null

  const taken = rows.value
    .map((row, i) => {
      if (i === index) return null
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
    .filter((id): id is number => id !== null)

  const pool = [...receipts.value]
  if (currentNum && !pool.some((r) => r.id === currentNum)) {
    const cached = knownReceipts.value.get(currentNum)
    if (cached) {
      pool.unshift(cached)
    }
  }

  return pool
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
    .map((row) => {
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
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

function amountFor(receiptId: number | null | unknown): number {
  return getReceiptById(receiptId)?.amount ?? 0
}

const chosenReceipts = computed<UnlinkedReceipt[]>(() =>
  rows.value
    .map((row) => getReceiptById(row.receipt_id))
    .filter((receipt): receipt is UnlinkedReceipt => receipt !== undefined),
)

const customerNamesOnTrip = computed<string[]>(() => {
  const names = chosenReceipts.value
    .map((r) => r.customer_name)
    .filter((name): name is string => Boolean(name && name.trim()))

  return [...new Set(names)]
})

const revenue = computed<number>(() =>
  chosenReceipts.value.reduce((sum, receipt) => sum + receipt.amount, 0),
)

const mixedRoutes = computed<boolean>(() => {
  const routes = new Set(chosenReceipts.value.map((r) => `${r.from_city}→${r.to_city}`))

  return routes.size > 1
})

onMounted(() => {
  void load()
})

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
            <p class="mt-1 text-xs text-muted">
              Optional: Select a customer to narrow the LR list, or leave empty to choose from all customers.
            </p>
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
                Choose the LR receipts this trip carries. Pick several for a shared load across customers.
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
                :disabled="loading"
                @click="addRow"
              >
                <template #left="slotProps"><BaseIcon name="PlusIcon" :class="slotProps.class" /></template>
                Add LR Receipt
              </BaseButton>
            </div>
          </div>

          <!-- Multi-Customer badge if more than 1 customer -->
          <div
            v-if="customerNamesOnTrip.length > 1"
            class="mb-3 flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 p-2.5 text-xs text-primary-800"
          >
            <span class="font-semibold">📦 Consolidated Part-Load ({{ customerNamesOnTrip.length }} Customers):</span>
            <span>{{ customerNamesOnTrip.join(', ') }}</span>
          </div>
          <div
            v-else-if="customerNamesOnTrip.length === 1"
            class="mb-3 flex items-center gap-2 rounded-lg border border-line-light bg-surface-secondary p-2 text-xs text-muted"
          >
            <span>👤 Customer: <strong class="text-heading">{{ customerNamesOnTrip[0] }}</strong></span>
          </div>

          <div v-if="loading && receipts.length === 0" class="rounded-lg bg-surface-secondary p-4 text-sm text-muted" role="status">
            Loading the receipts…
          </div>

          <div
            v-else-if="rows.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Click "+ Add LR Receipt" or "Add Oldest First" to add consignments to this trip.
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
                  value-prop="id"
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

        <!-- ── Summary ───────────────────────────────────────────────── -->
        <section class="mt-5 border-t border-line-light pt-5">
          <h2 class="mb-4 text-section font-semibold text-heading">Summary</h2>

          <div class="grid grid-cols-2 gap-3 text-center">
            <div class="rounded-lg bg-surface-secondary p-3">
              <p class="text-xs text-muted">Selected LR Receipts</p>
              <p class="text-lg font-semibold text-heading">{{ chosenReceipts.length }}</p>
            </div>
            <div class="rounded-lg bg-surface-secondary p-3">
              <p class="text-xs text-muted">Total Freight Revenue</p>
              <p class="text-lg font-semibold text-heading">{{ formatMoney(revenue) }}</p>
            </div>
          </div>
          <p class="mt-3 text-center text-xs text-muted">
            Lorry Receipt and expenses are fetched & linked on the trip details page to calculate vehicle cost and net profit.
          </p>
        </section>
      </BaseCard>
    </form>
  </BasePage>
</template>
