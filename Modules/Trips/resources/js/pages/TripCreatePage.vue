<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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

interface AllocationRow {
  receipt_id: number | null
}

const tripDate = ref<string>(today())
const tripNumber = ref<string>('')
const customerId = ref<number | null>(null)
const notes = ref<string>('')

const receipts = ref<UnlinkedReceipt[]>([])
const allocations = ref<AllocationRow[]>([])
const isLoadingReceipts = ref<boolean>(false)
const isSaving = ref<boolean>(false)

const eligibleReceipts = computed(() => receipts.value)

async function onCustomerChange(id: number | null): Promise<void> {
  allocations.value = []
  if (!id) {
    receipts.value = []
    return
  }

  isLoadingReceipts.value = true
  try {
    receipts.value = await fetchUnlinkedReceipts(props.client, 'lr', id)
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load LR receipts.'))
    receipts.value = []
  } finally {
    isLoadingReceipts.value = false
  }
}

watch(customerId, (newId, oldId) => {
  if (oldId !== undefined && newId !== oldId) {
    void onCustomerChange(newId)
  }
})

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

function availableReceipts(selectedReceiptId: number | null | unknown): Array<{ id: number; label: string }> {
  const currentNum = selectedReceiptId
    ? Number(typeof selectedReceiptId === 'object' ? (selectedReceiptId as any).id : selectedReceiptId)
    : null

  const chosenIds = allocations.value
    .map((row) => {
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
    .filter((id): id is number => id !== null && id !== currentNum)

  return receipts.value
    .filter((receipt) => !chosenIds.includes(receipt.id))
    .map((receipt) => ({
      id: receipt.id,
      label: `${receipt.invoice_number} · ${receipt.from_city ?? '—'} → ${receipt.to_city ?? '—'} (${formatMoney(receipt.amount)})`,
    }))
}

const chosenIds = computed<number[]>(() =>
  allocations.value
    .map((row) => {
      const id = typeof row.receipt_id === 'object' && row.receipt_id !== null ? (row.receipt_id as any).id : row.receipt_id
      return id ? Number(id) : null
    })
    .filter((id): id is number => id !== null),
)

const chosenReceipts = computed<UnlinkedReceipt[]>(() =>
  allocations.value
    .map((row) => getReceiptById(row.receipt_id))
    .filter((receipt): receipt is UnlinkedReceipt => receipt !== undefined),
)

const allocatedAmount = computed<number>(() =>
  chosenReceipts.value.reduce((sum, receipt) => sum + receipt.amount, 0),
)

function addAllocation(): void {
  allocations.value.push({ receipt_id: null })
}

function removeAllocation(index: number): void {
  allocations.value.splice(index, 1)
}

function allocateOldestFirst(): void {
  allocations.value = [...receipts.value]
    .sort(
      (a, b) =>
        (a.invoice_date ?? '9999-12-31').localeCompare(b.invoice_date ?? '9999-12-31') || a.id - b.id,
    )
    .map((receipt) => ({ receipt_id: receipt.id }))
}

async function submitTripData(): Promise<void> {
  if (!customerId.value) {
    props.notify('warning', 'Please select a customer first.')
    return
  }

  if (chosenIds.value.length === 0) {
    props.notify('warning', 'Please allocate at least one LR receipt to this trip.')
    return
  }

  isSaving.value = true

  try {
    const trip = await createTripFromLrs(props.client, chosenIds.value, {
      pickup_date: tripDate.value,
      notes: notes.value,
    })

    props.notify('success', `Trip #${trip.trip_no} created successfully.`)
    props.router?.push(PATHS.trip(trip.id))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to create the trip.'))
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <BasePage class="relative trip-create">
    <form class="flex flex-col gap-4 md:gap-5" @submit.prevent="submitTripData">
      <BasePageHeader title="New Trip" phone-actions="bar">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem title="Home" to="/admin/dashboard" />
          <BaseBreadcrumbItem title="Trips" :to="PATHS.board" />
          <BaseBreadcrumbItem title="New Trip" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton :loading="isSaving" :disabled="isSaving" variant="primary" type="submit">
            <template #left="slotProps">
              <BaseIcon v-if="!isSaving" name="ArrowDownOnSquareIcon" :class="slotProps.class" />
            </template>
            Save Trip
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard container-class="p-4 md:p-5">
        <!-- Input Grid: Exactly matching PaymentCreateView.vue -->
        <BaseInputGrid>
          <BaseInputGroup label="Date" required>
            <BaseDatePicker v-model="tripDate" :calendar-button="true" calendar-button-icon="calendar" />
          </BaseInputGroup>

          <BaseInputGroup label="Trip Number" required>
            <BaseInput v-model="tripNumber" placeholder="Auto-generated" />
          </BaseInputGroup>

          <BaseInputGroup label="Customer" required>
            <BaseCustomerSelectInput
              v-model="customerId"
              placeholder="Type or click to select"
              show-action
              @update:model-value="onCustomerChange"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <!-- LR Allocations Section: Exactly matching Invoice Allocations in PaymentCreateView.vue -->
        <section class="pt-5 mt-5 border-t border-line-light">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
              <h2 class="font-semibold text-section text-heading">LR Allocations</h2>
              <p class="mt-1 text-sm text-muted">Allocate one or more open LR receipts to this trip.</p>
            </div>
            <div class="flex gap-2">
              <BaseButton
                type="button"
                size="sm"
                variant="primary-outline"
                :disabled="!eligibleReceipts.length"
                @click="allocateOldestFirst"
              >
                Allocate Oldest First
              </BaseButton>
              <BaseButton
                type="button"
                size="sm"
                variant="primary-outline"
                :disabled="!customerId || !availableReceipts(null).length"
                @click="addAllocation"
              >
                <template #left="slotProps"><BaseIcon name="PlusIcon" :class="slotProps.class" /></template>
                Add Allocation
              </BaseButton>
            </div>
          </div>

          <!-- Matching PaymentCreateView: Select a customer before allocating -->
          <div v-if="!customerId" class="p-4 text-sm rounded-lg bg-surface-secondary text-muted">
            Select a customer before allocating this trip.
          </div>

          <!-- Matching PaymentCreateView: No open invoices / no allocations yet -->
          <div
            v-else-if="!allocations.length"
            class="p-4 text-sm rounded-lg bg-surface-secondary text-muted"
            role="status"
          >
            {{ !isLoadingReceipts && !eligibleReceipts.length ? 'No open LR receipts for this customer.' : 'No allocations added yet. Click "+ Add Allocation" or "Allocate Oldest First".' }}
          </div>

          <!-- Allocation Rows: BaseMultiselect + Amount + Trash -->
          <div v-else class="space-y-3">
            <div
              v-for="(allocation, index) in allocations"
              :key="`${allocation.receipt_id}-${index}`"
              class="grid items-end gap-3 p-3 rounded-lg bg-surface-secondary md:grid-cols-[minmax(0,1fr)_12rem_auto]"
            >
              <BaseInputGroup label="LR Receipt">
                <BaseMultiselect
                  v-model="allocation.receipt_id"
                  value-prop="id"
                  track-by="id"
                  label="label"
                  :options="availableReceipts(allocation.receipt_id)"
                  :loading="isLoadingReceipts"
                  placeholder="Select an LR"
                  searchable
                />
              </BaseInputGroup>

              <BaseInputGroup label="Amount">
                <div
                  class="flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading"
                >
                  {{ allocation.receipt_id ? formatMoney(amountFor(allocation.receipt_id)) : '—' }}
                </div>
              </BaseInputGroup>

              <div class="justify-self-end">
                <BaseButton
                  type="button"
                  size="sm"
                  variant="gray"
                  aria-label="Remove allocation"
                  @click="removeAllocation(index)"
                >
                  <BaseIcon name="TrashIcon" />
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- Matching PaymentCreateView bottom summary line -->
          <div class="flex flex-wrap justify-end gap-x-8 gap-y-2 pt-4 mt-4 text-sm border-t border-line-light">
            <span class="text-muted">Total LRs: <span class="font-semibold text-heading">{{ chosenReceipts.length }}</span></span>
            <span class="text-heading font-semibold">Allocated: {{ formatMoney(allocatedAmount) }}</span>
          </div>
        </section>

        <!-- Notes: Matching PaymentCreateView.vue -->
        <BaseInputGroup label="Notes" class="mt-5">
          <BaseTextarea v-model="notes" :rows="4" placeholder="Add any notes about this trip..." />
        </BaseInputGroup>
      </BaseCard>
    </form>
  </BasePage>
</template>
