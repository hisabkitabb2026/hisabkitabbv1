<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import {
  cancelTrip,
  changeTripStatus,
  fetchDocumentBlob,
  fetchInvoiceReceipts,
  fetchStatuses,
  fetchTrip,
  linkReceipt,
  removePod,
  unlinkReceipt,
  uploadPod,
} from '@/api/trips'
import type { Trip, TripDocument, TripStatus } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { formatMoney } from '@/support/format'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import LrPickerModal from '@/components/LrPickerModal.vue'

/** The host router, handed in by injectedPage. */
interface RouterLike {
  push: (to: string) => void
}

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  router?: RouterLike
}>()

const trip = ref<Trip | null>(null)
const loading = ref(true)
const statuses = ref<TripStatus[]>([])
const changingStatus = ref(false)

const lrPickerOpen = ref(false)
const lorryPickerOpen = ref(false)

const fetchingInvoice = ref(false)
const podUploadingCategory = ref<string | null>(null)
const podFileInput = ref<HTMLInputElement | null>(null)
const podPendingCategory = ref<string>('')

const tripId = computed<number>(() => Number(props.id))

const statusSelectValue = computed<number | null>({
  get: () => trip.value?.status.id ?? null,
  set: (value: number | null) => {
    if (value !== null) {
      void onStatusChange(value)
    }
  },
})

const lrReceipts = computed(() => trip.value?.receipts.filter((r) => r.type === 'lr') ?? [])
const lorryReceipt = computed(() => trip.value?.receipts.find((r) => r.type === 'lorry') ?? null)
const invoiceReceipts = computed(() => trip.value?.receipts.filter((r) => r.type === 'invoice') ?? [])

/** Document slots grouped by role. */
const DOCUMENT_SLOTS: Array<{ role: string; label: string; slots: Array<{ key: string; label: string }> }> = [
  {
    role: 'driver',
    label: 'Driver Documents',
    slots: [
      { key: 'driver_aadhar', label: 'Aadhar Card' },
      { key: 'driver_license', label: 'Driving Licence' },
      { key: 'driver_pan', label: 'PAN Card' },
    ],
  },
  {
    role: 'owner',
    label: 'Owner Documents',
    slots: [
      { key: 'owner_aadhar', label: 'Aadhar Card' },
      { key: 'owner_pan', label: 'PAN Card' },
      { key: 'owner_rc', label: 'Vehicle RC' },
    ],
  },
  {
    role: 'broker',
    label: 'Broker Documents',
    slots: [
      { key: 'broker_aadhar', label: 'Aadhar Card' },
      { key: 'broker_pan', label: 'PAN Card' },
    ],
  },
]

/** Look up a document by its category slot key. */
function docByCategory(category: string): TripDocument | null {
  return trip.value?.documents.find((d) => d.category === category) ?? null
}

/**
 * Object URLs for document blobs. The showDocument route sits behind
 * auth:sanctum, so <img src> and <a href> get 403. We fetch each document
 * as a blob via the authenticated client and create a local object URL.
 * Keyed by media ID.
 */
const docUrls = ref<Record<number, string>>({})

function revokeDocUrls(): void {
  for (const url of Object.values(docUrls.value)) {
    URL.revokeObjectURL(url)
  }
  docUrls.value = {}
}

async function loadDocUrls(): Promise<void> {
  if (!trip.value) {
    return
  }

  revokeDocUrls()

  for (const doc of trip.value.documents) {
    try {
      const blob = await fetchDocumentBlob(props.client, trip.value.id, doc.id)
      docUrls.value[doc.id] = URL.createObjectURL(blob)
    } catch {
      // Leave the slot without a preview — the upload itself succeeded.
    }
  }
}

/** Open a document in a new tab from its cached blob URL. */
function openDoc(mediaId: number): void {
  const url = docUrls.value[mediaId]

  if (url) {
    window.open(url, '_blank', 'noopener')
  }
}

watch(
  () => trip.value?.documents,
  () => {
    void loadDocUrls()
  },
)

async function load(): Promise<void> {
  loading.value = true

  try {
    trip.value = await fetchTrip(props.client, tripId.value)
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the trip.'))
  } finally {
    loading.value = false
  }
}

async function loadStatuses(): Promise<void> {
  try {
    statuses.value = await fetchStatuses(props.client)
  } catch {
    // Non-fatal: the dropdown just stays empty.
  }
}

async function onStatusChange(statusId: number): Promise<void> {
  if (!trip.value || changingStatus.value) {
    return
  }

  changingStatus.value = true

  try {
    trip.value = await changeTripStatus(props.client, trip.value.id, statusId)
    props.notify('success', 'Status updated.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to change the status.'))
  } finally {
    changingStatus.value = false
  }
}

/** Re-fetch the trip when the tab becomes visible again, so a status change on the board is reflected. */
function onVisibilityChange(): void {
  if (!document.hidden) {
    void load()
  }
}

onMounted(() => {
  void load()
  void loadStatuses()
  document.addEventListener('visibilitychange', onVisibilityChange)
})

onUnmounted(() => {
  revokeDocUrls()
  document.removeEventListener('visibilitychange', onVisibilityChange)
})

async function onCancel(): Promise<void> {
  if (!trip.value || !window.confirm('Cancel this trip?')) {
    return
  }

  try {
    trip.value = await cancelTrip(props.client, trip.value.id)
    props.notify('success', 'Trip cancelled.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to cancel the trip.'))
  }
}

async function onLinkLrs(invoiceIds: number[]): Promise<void> {
  if (!trip.value) {
    return
  }

  lrPickerOpen.value = false

  try {
    for (const invoiceId of invoiceIds) {
      trip.value = await linkReceipt(props.client, trip.value.id, invoiceId, 'lr')
    }

    props.notify('success', 'LR receipt linked.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to link the receipt.'))
  }
}

async function onLinkLorry(invoiceIds: number[]): Promise<void> {
  if (!trip.value || invoiceIds.length === 0) {
    return
  }

  lorryPickerOpen.value = false

  try {
    trip.value = await linkReceipt(props.client, trip.value.id, invoiceIds[0], 'lorry')
    props.notify('success', 'Lorry receipt linked.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to link the receipt.'))
  }
}

async function onUnlink(receiptId: number): Promise<void> {
  if (!trip.value) {
    return
  }

  try {
    trip.value = await unlinkReceipt(props.client, trip.value.id, receiptId)
    props.notify('success', 'Receipt unlinked.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to unlink the receipt.'))
  }
}

async function onFetchInvoiceReceipt(): Promise<void> {
  if (!trip.value) {
    return
  }

  fetchingInvoice.value = true

  try {
    trip.value = await fetchInvoiceReceipts(props.client, trip.value.id)
    props.notify('success', 'Invoice receipt fetched and linked.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'No invoice receipt found for these LR numbers.'))
  } finally {
    fetchingInvoice.value = false
  }
}

function triggerPodUpload(category: string): void {
  podPendingCategory.value = category
  podFileInput.value?.click()
}

async function onPodFileChange(event: Event): Promise<void> {
  if (!trip.value) {
    return
  }

  const target = event.target as HTMLInputElement
  const files = target.files

  if (!files || files.length === 0) {
    return
  }

  const category = podPendingCategory.value
  podUploadingCategory.value = category

  try {
    trip.value = await uploadPod(props.client, trip.value.id, files[0], category)
    props.notify('success', 'Document uploaded.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to upload the document.'))
  } finally {
    podUploadingCategory.value = null
    target.value = ''
  }
}

async function onRemovePod(mediaId: number): Promise<void> {
  if (!trip.value) {
    return
  }

  try {
    trip.value = await removePod(props.client, trip.value.id, mediaId)
    props.notify('success', 'Document removed.')
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to remove the document.'))
  }
}

function openInvoice(invoiceId: number): void {
  props.router?.push(PATHS.invoiceView(invoiceId))
}

function backToBoard(): void {
  props.router?.push(PATHS.board)
}
</script>

<template>
  <div class="min-h-screen bg-surface-secondary">
    <div class="mx-auto max-w-5xl p-4 lg:p-6">
      <div v-if="loading" class="py-16 text-center text-sm text-muted">Loading the trip…</div>

      <template v-else-if="trip">
        <!-- Header -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <button type="button" class="text-sm text-muted hover:text-heading" @click="backToBoard">
              ← Trips
            </button>
            <h1 class="text-xl font-semibold text-heading">
              Trip #{{ trip.trip_no }} · {{ trip.from_city ?? '—' }} → {{ trip.to_city ?? '—' }}
            </h1>
          </div>

          <div class="flex items-center gap-2">
            <select
              v-if="statuses.length > 0 && !trip.cancelled_at"
              v-model="statusSelectValue"
              :disabled="changingStatus"
              class="rounded-full border-0 px-3 py-1 text-xs font-semibold text-white outline-none"
              :style="{ backgroundColor: trip.status.colour ?? '#94a3b8' }"
            >
              <option
                v-for="status in statuses"
                :key="status.id"
                :value="status.id"
                class="bg-surface text-body"
              >
                {{ status.name }}
              </option>
            </select>
            <span
              v-else
              class="rounded-full px-3 py-1 text-xs font-semibold text-white"
              :style="{ backgroundColor: trip.status.colour ?? '#94a3b8' }"
            >
              {{ trip.status.name }}
            </span>
            <BaseButton
              v-if="!trip.cancelled_at"
              size="sm"
              variant="danger"
              @click="onCancel"
            >
              Cancel Trip
            </BaseButton>
          </div>
        </div>

        <div v-if="trip.cancelled_at" class="mb-4 rounded-lg bg-alert-error-bg p-3 text-sm text-alert-error-text">
          This trip is cancelled.
        </div>

        <div class="space-y-4">
          <!-- ④ Documents -->
          <BaseSettingCard
            title="Documents"
            description="LR receipts, lorry receipt, invoice receipt and trip documents for this trip."
          >
            <div class="space-y-2">
              <div
                v-for="receipt in lrReceipts"
                :key="receipt.id"
                class="flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
              >
                <div class="min-w-0">
                  <p class="font-medium text-heading">📄 {{ receipt.invoice_number }}</p>
                  <p class="truncate text-xs text-muted">
                    {{ receipt.customer_name ?? 'No customer' }} · {{ formatMoney(receipt.amount) }}
                  </p>
                </div>
                <div class="flex shrink-0 gap-1">
                  <BaseButton size="sm" variant="primary-outline" @click="openInvoice(receipt.invoice_id)">
                    View
                  </BaseButton>
                  <BaseButton
                    v-if="!receipt.billed_invoice_id"
                    size="sm"
                    variant="danger-outline"
                    @click="onUnlink(receipt.id)"
                  >
                    Unlink
                  </BaseButton>
                </div>
              </div>

              <p v-if="lrReceipts.length === 0" class="text-sm text-muted">No LR receipts linked yet.</p>

              <div class="flex gap-2 pt-1">
                <BaseButton size="sm" variant="primary-outline" @click="lrPickerOpen = true">
                  + Link LR
                </BaseButton>
              </div>
            </div>

            <!-- Lorry Receipt -->
            <div class="mt-4 border-t border-line-light pt-3">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Lorry Receipt</p>
              <div
                v-if="lorryReceipt"
                class="flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
              >
                <div class="min-w-0">
                  <p class="font-medium text-heading">🚛 {{ lorryReceipt.invoice_number }}</p>
                  <p class="truncate text-xs text-muted">
                    {{ lorryReceipt.customer_name ?? 'No customer' }} · {{ formatMoney(lorryReceipt.amount) }}
                  </p>
                </div>
                <div class="flex shrink-0 gap-1">
                  <BaseButton size="sm" variant="primary-outline" @click="openInvoice(lorryReceipt.invoice_id)">
                    View
                  </BaseButton>
                  <BaseButton
                    v-if="!lorryReceipt.billed_invoice_id"
                    size="sm"
                    variant="danger-outline"
                    @click="onUnlink(lorryReceipt.id)"
                  >
                    Unlink
                  </BaseButton>
                </div>
              </div>
              <div v-else class="flex items-center gap-2">
                <BaseButton size="sm" variant="primary-outline" @click="lorryPickerOpen = true">
                  + Link Lorry Receipt
                </BaseButton>
              </div>
            </div>

            <!-- Invoice Receipt (fetched from bilty numbers) -->
            <div class="mt-4 border-t border-line-light pt-3">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Invoice Receipt</p>
              <div
                v-for="receipt in invoiceReceipts"
                :key="receipt.id"
                class="flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
              >
                <div class="min-w-0">
                  <p class="font-medium text-heading">🧾 {{ receipt.invoice_number }}</p>
                  <p class="truncate text-xs text-muted">
                    {{ receipt.customer_name ?? 'No customer' }} · {{ formatMoney(receipt.amount) }}
                  </p>
                </div>
                <div class="flex shrink-0 gap-1">
                  <BaseButton size="sm" variant="primary-outline" @click="openInvoice(receipt.invoice_id)">
                    View
                  </BaseButton>
                  <BaseButton
                    v-if="!receipt.billed_invoice_id"
                    size="sm"
                    variant="danger-outline"
                    @click="onUnlink(receipt.id)"
                  >
                    Unlink
                  </BaseButton>
                </div>
              </div>
              <div v-if="invoiceReceipts.length === 0" class="flex items-center gap-2">
                <BaseButton
                  size="sm"
                  variant="primary-outline"
                  :disabled="fetchingInvoice || lrReceipts.length === 0"
                  @click="onFetchInvoiceReceipt"
                >
                  {{ fetchingInvoice ? 'Fetching…' : 'Fetch Invoice Receipt' }}
                </BaseButton>
                <span v-if="lrReceipts.length === 0" class="text-xs text-muted">Link an LR first.</span>
              </div>
            </div>

            <!-- Trip Documents -->
            <div class="mt-4 border-t border-line-light pt-3">
              <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Trip Documents</p>

              <input
                ref="podFileInput"
                type="file"
                class="hidden"
                accept="image/*,application/pdf"
                @change="onPodFileChange"
              />

              <div
                v-for="group in DOCUMENT_SLOTS"
                :key="group.role"
                class="mb-4 last:mb-0"
              >
                <p class="mb-2 text-sm font-semibold text-heading">{{ group.label }}</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  <div
                    v-for="slot in group.slots"
                    :key="slot.key"
                    class="flex flex-col"
                  >
                    <p class="mb-1.5 text-xs font-medium text-muted">{{ slot.label }}</p>

                    <!-- Uploaded: image preview or file icon -->
                    <div
                      v-if="docByCategory(slot.key)"
                      class="group relative flex h-28 items-center justify-center overflow-hidden rounded-lg border border-line-default bg-surface-secondary"
                    >
                      <img
                        v-if="docByCategory(slot.key)!.is_image && docUrls[docByCategory(slot.key)!.id]"
                        :src="docUrls[docByCategory(slot.key)!.id]"
                        :alt="slot.label"
                        class="h-full w-full object-cover"
                      />
                      <div
                        v-else-if="!docByCategory(slot.key)!.is_image"
                        class="flex flex-col items-center gap-1 text-subtle"
                      >
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-xs">PDF</span>
                      </div>
                      <div v-else class="flex items-center justify-center text-subtle">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                      </div>

                      <!-- Hover overlay with actions -->
                      <div class="absolute inset-0 flex items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                        <BaseButton size="sm" variant="primary" @click="openDoc(docByCategory(slot.key)!.id)">
                          View
                        </BaseButton>
                        <button
                          type="button"
                          class="flex h-7 w-7 items-center justify-center rounded-full bg-surface text-status-red shadow"
                          aria-label="Remove"
                          @click="onRemovePod(docByCategory(slot.key)!.id)"
                        >
                          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- Empty: upload dropzone -->
                    <button
                      v-else
                      type="button"
                      class="flex h-28 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line-strong bg-surface-secondary text-subtle transition-colors hover:border-primary-400 hover:text-primary-500"
                      :disabled="podUploadingCategory === slot.key"
                      @click="triggerPodUpload(slot.key)"
                    >
                      <template v-if="podUploadingCategory === slot.key">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span class="text-xs">Uploading…</span>
                      </template>
                      <template v-else>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="text-xs">Upload</span>
                      </template>
                    </button>

                    <!-- File name + replace for uploaded -->
                    <div v-if="docByCategory(slot.key)" class="mt-1 flex items-center justify-between gap-1">
                      <span class="min-w-0 truncate text-xs text-muted">{{ docByCategory(slot.key)!.file_name }}</span>
                      <button
                        type="button"
                        class="shrink-0 text-xs text-primary-500 hover:underline"
                        @click="triggerPodUpload(slot.key)"
                      >
                        Replace
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- The Money — directly below the documents -->
            <div class="mt-4 border-t border-line-light pt-3">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">The Money</p>

              <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-surface-secondary p-3">
                  <p class="text-xs text-muted">Revenue</p>
                  <p class="text-lg font-semibold text-heading">{{ formatMoney(trip.money.revenue) }}</p>
                </div>
                <div class="rounded-lg bg-surface-secondary p-3">
                  <p class="text-xs text-muted">Cost</p>
                  <p class="text-lg font-semibold text-heading">{{ formatMoney(trip.money.cost) }}</p>
                </div>
                <div class="rounded-lg bg-surface-secondary p-3">
                  <p class="text-xs text-muted">Profit</p>
                  <p
                    class="text-lg font-semibold"
                    :class="trip.money.profit >= 0 ? 'text-status-green' : 'text-status-red'"
                  >
                    {{ formatMoney(trip.money.profit) }}
                  </p>
                </div>
              </div>

              <!-- From Customer (receivable) -->
              <div v-if="trip.money.receivable.length > 0" class="mt-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">From Customer</p>
                <div
                  v-for="rec in trip.money.receivable"
                  :key="rec.invoice_id"
                  class="mb-2 rounded-lg border border-line-light p-2.5 text-sm"
                >
                  <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-medium text-heading">🧾 {{ rec.invoice_number }}</p>
                      <p class="truncate text-xs text-muted">
                        {{ rec.customer_name ?? 'No customer' }} · Total {{ formatMoney(rec.total) }} · Due {{ formatMoney(rec.due_amount) }}
                      </p>
                    </div>
                    <span
                      class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                      :class="{
                        'bg-status-red/10 text-status-red': rec.paid_status === 'UNPAID',
                        'bg-status-yellow/10 text-status-yellow': rec.paid_status === 'PARTIALLY_PAID',
                        'bg-status-green/10 text-status-green': rec.paid_status === 'PAID',
                      }"
                    >
                      {{ rec.paid_status }}
                    </span>
                  </div>
                  <div v-if="rec.payments.length > 0" class="mt-2 space-y-1">
                    <div
                      v-for="pay in rec.payments"
                      :key="pay.id"
                      class="flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
                    >
                      <span class="text-muted">{{ pay.number ?? '—' }} · {{ pay.date ?? '—' }}</span>
                      <span class="font-medium text-heading">{{ formatMoney(pay.amount) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- To Owner (payable) -->
              <div v-if="trip.money.payable.length > 0" class="mt-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">To Owner</p>
                <div
                  v-for="payable in trip.money.payable"
                  :key="payable.invoice_number"
                  class="mb-2 rounded-lg border border-line-light p-2.5 text-sm"
                >
                  <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-medium text-heading">🚛 {{ payable.bill_number ?? payable.invoice_number }}</p>
                      <p class="truncate text-xs text-muted">
                        {{ payable.supplier_name ?? 'Unknown supplier' }}<span v-if="payable.bill_reference"> · {{ payable.bill_reference }}</span>
                      </p>
                    </div>
                    <span
                      v-if="payable.bill_status"
                      class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                      :class="{
                        'bg-status-red/10 text-status-red': payable.bill_status === 'UNPAID',
                        'bg-status-yellow/10 text-status-yellow': payable.bill_status === 'PARTIAL',
                        'bg-status-green/10 text-status-green': payable.bill_status === 'SETTLED',
                      }"
                    >
                      {{ payable.bill_status }}
                    </span>
                  </div>
                  <p v-if="payable.bill_total !== null" class="mt-1 text-xs text-muted">
                    Total {{ formatMoney(payable.bill_total) }} · Due {{ formatMoney(payable.bill_due ?? 0) }}
                  </p>
                  <div v-if="payable.payments.length > 0" class="mt-2 space-y-1">
                    <div
                      v-for="pay in payable.payments"
                      :key="pay.id"
                      class="flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
                    >
                      <span class="text-muted">
                        {{ pay.type === 'advance' ? 'Advance' : 'Final' }} · {{ pay.date ?? '—' }}<span v-if="pay.reference"> · {{ pay.reference }}</span>
                      </span>
                      <span class="font-medium text-heading">{{ formatMoney(pay.amount) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-3 flex items-center justify-between rounded-lg border border-line-light p-2.5 text-sm">
                <span class="text-muted">
                  Company paid {{ formatMoney(trip.money.owner_paid) }} · balance
                  <span class="font-semibold text-heading">{{ formatMoney(trip.money.owner_balance) }}</span>
                </span>
                <span
                  v-if="trip.money.bill_status"
                  class="rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="{
                    'bg-status-red/10 text-status-red': trip.money.bill_status === 'UNPAID',
                    'bg-status-yellow/10 text-status-yellow': trip.money.bill_status === 'PARTIAL',
                    'bg-status-green/10 text-status-green': trip.money.bill_status === 'SETTLED',
                  }"
                  :title="`Bill ${trip.money.bill_number}`"
                >
                  {{ trip.money.bill_status }}
                </span>
              </div>
            </div>
          </BaseSettingCard>
        </div>
      </template>
    </div>

    <LrPickerModal
      :show="lrPickerOpen"
      :client="client"
      :notify="notify"
      multiple
      @close="lrPickerOpen = false"
      @select="onLinkLrs"
    />

    <LrPickerModal
      :show="lorryPickerOpen"
      :client="client"
      :notify="notify"
      type="lorry"
      :multiple="false"
      @close="lorryPickerOpen = false"
      @select="onLinkLorry"
    />
  </div>
</template>
