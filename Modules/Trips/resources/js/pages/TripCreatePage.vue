<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { createTripFromLrs, fetchUnlinkedReceipts } from '@/api/trips'
import type { UnlinkedReceipt } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { formatMoney } from '@/support/format'
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

/** One allocation row, exactly like a payment allocation in PaymentCreateView. */
interface AllocationRow {
  receipt_id: number | null
}

const customerId = ref<number | null>(null)
const allowAllCustomers = ref(false)
const receipts = ref<UnlinkedReceipt[]>([])
const rows = ref<AllocationRow[]>([])
const loading = ref(false)
const saving = ref(false)

async function loadReceipts(): Promise<void> {
  if (!customerId.value && !allowAllCustomers.value) {
    receipts.value = []
    rows.value = []
    return
  }

  loading.value = true

  try {
    receipts.value = await fetchUnlinkedReceipts(props.client, 'lr', customerId.value)
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the receipts.'))
    receipts.value = []
  } finally {
    loading.value = false
  }
}

watch(customerId, (newId, oldId) => {
  if (oldId !== undefined && newId !== oldId) {
    rows.value = []
  }
  if (newId) {
    allowAllCustomers.value = false
  }
  void loadReceipts()
})

function toggleAllCustomers(): void {
  allowAllCustomers.value = !allowAllCustomers.value
  if (allowAllCustomers.value) {
    customerId.value = null
  }
  rows.value = []
  void loadReceipts()
}

function getReceiptById(id: number | null | unknown): UnlinkedReceipt | undefined {
  if (!id) return undefined
  const rawId = typeof id === 'object' && id !== null ? (id as any).id : id
  const num = Number(rawId)
  if (!num || isNaN(num)) return undefined
  return receipts.value.find((r) => r.id === num)
}

function amountFor(receiptId: number | null | unknown): number {
  return getReceiptById(receiptId)?.amount ?? 0
}

/** Options for one row's select: everything unchosen by the other rows. */
function availableReceipts(currentReceiptId: number | null | unknown): Array<{ id: number; label: string }> {
  const currentNum = currentReceiptId
    ? Number(typeof currentReceiptId === 'object' ? (currentReceiptId as any).id : currentReceiptId)
    : null

  const taken = rows.value
    .map((row) => {
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
    .filter((id): id is number => id !== null && id !== currentNum)

  return receipts.value
    .filter((receipt) => !taken.includes(receipt.id))
    .map((receipt) => ({
      id: receipt.id,
      label: `${receipt.invoice_number} · ${receipt.from_city ?? '—'} → ${receipt.to_city ?? '—'} · ${receipt.customer_name ?? 'No customer'} (${formatMoney(receipt.amount)})`,
    }))
}

const chosenIds = computed<number[]>(() =>
  rows.value
    .map((row) => {
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
    .filter((id): id is number => id !== null),
)

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

function addRow(): void {
  rows.value.push({ receipt_id: null })
}

function removeRow(index: number): void {
  rows.value.splice(index, 1)
}

function addOldestFirst(): void {
  rows.value = [...receipts.value]
    .sort(
      (a, b) =>
        (a.invoice_date ?? '9999-12-31').localeCompare(b.invoice_date ?? '9999-12-31') || a.id - b.id,
    )
    .map((receipt) => ({ receipt_id: receipt.id }))
}

async function submit(): Promise<void> {
  if (chosenIds.value.length === 0) {
    props.notify('warning', 'Please select at least one LR receipt.')
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
        <!-- Customer Selection: mirrors PaymentCreateView.vue -->
        <div class="max-w-md">
          <BaseInputGroup label="Customer" required>
            <BaseCustomerSelectInput
              v-model="customerId"
              :disabled="allowAllCustomers"
              placeholder="Select a customer"
              show-action
            />
            <div class="mt-2 flex items-center justify-between text-xs text-muted">
              <span>Select a customer to view their unlinked LR receipts.</span>
              <button
                type="button"
                class="font-medium text-primary-600 hover:text-primary-700 underline"
                @click="toggleAllCustomers"
              >
                {{ allowAllCustomers ? '← Select specific customer' : 'Or load all customers (part-load)' }}
              </button>
            </div>
          </BaseInputGroup>
        </div>

        <!-- ── LR Receipts: the allocation section (matching PaymentCreateView) ─────────────────────── -->
        <section class="mt-5 border-t border-line-light pt-5">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-section font-semibold text-heading">LR Receipts</h2>
              <p class="mt-1 text-sm text-muted">
                Choose the LR receipts this trip carries.
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
                :disabled="loading || (!customerId && !allowAllCustomers) || availableReceipts(null).length === 0"
                @click="addRow"
              >
                <template #left="slotProps"><BaseIcon name="PlusIcon" :class="slotProps.class" /></template>
                Add LR Receipt
              </BaseButton>
            </div>
          </div>

          <!-- Multi-Customer badge if part-load -->
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

          <!-- State 1: No customer selected yet -->
          <div
            v-if="!customerId && !allowAllCustomers"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
          >
            Please select a customer above to view and add LR receipts.
          </div>

          <!-- State 2: Loading receipts -->
          <div
            v-else-if="loading && receipts.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Loading LR receipts…
          </div>

          <!-- State 3: Customer selected, but has NO unlinked receipts -->
          <div
            v-else-if="receipts.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            No unlinked LR receipts found for this customer.
          </div>

          <!-- State 4: Receipts available, but no rows added yet -->
          <div
            v-else-if="rows.length === 0"
            class="rounded-lg bg-surface-secondary p-4 text-sm text-muted"
            role="status"
          >
            Click "+ Add LR Receipt" or "Add Oldest First" to add LR receipts to this trip.
          </div>

          <!-- State 5: Rows added (BaseMultiselect like PaymentCreateView) -->
          <div v-else class="space-y-3">
            <div
              v-for="(row, index) in rows"
              :key="index"
              class="grid items-end gap-3 rounded-lg bg-surface-secondary p-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]"
            >
              <BaseInputGroup label="LR Receipt">
                <BaseMultiselect
                  v-model="row.receipt_id"
                  value-prop="id"
                  track-by="id"
                  label="label"
                  :options="availableReceipts(row.receipt_id)"
                  :loading="loading"
                  placeholder="Select an LR"
                  searchable
                />
              </BaseInputGroup>

              <BaseInputGroup label="Amount">
                <div
                  class="flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading"
                >
                  {{ row.receipt_id ? formatMoney(amountFor(row.receipt_id)) : '—' }}
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
