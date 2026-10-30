<script setup lang="ts">
import { computed } from 'vue'
import type { Trip, TripPayablePayment } from '@/types/trip'
import { formatMoney } from '@/support/format'

const props = defineProps<{
  show: boolean
  payment: TripPayablePayment | null
  trip: Trip | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const paymentTypeLabel = computed<string>(() => {
  if (!props.payment) return 'Payment'
  return props.payment.type === 'advance' ? 'Advance Payment' : 'Final Settlement Payment'
})

function onPrint(): void {
  window.print()
}
</script>

<template>
  <div
    v-if="show && payment && trip"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 print:p-0 print:bg-white"
  >
    <div
      class="relative w-full max-w-2xl rounded-xl border border-line-default bg-surface p-6 shadow-2xl print:border-0 print:shadow-none print:max-w-none print:w-full print:p-8"
    >
      <!-- Action Bar (Hidden when printing) -->
      <div class="mb-4 flex items-center justify-between border-b border-line-light pb-3 print:hidden">
        <div class="flex items-center gap-2">
          <span class="rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">
            Payment Voucher
          </span>
          <span class="text-xs text-muted">Voucher #{{ payment.number ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-primary-700"
            @click="onPrint"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Voucher
          </button>
          <button
            type="button"
            class="rounded-lg p-1.5 text-muted hover:bg-surface-secondary hover:text-heading"
            aria-label="Close"
            @click="emit('close')"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Printable Voucher Content -->
      <div class="voucher-box border-2 border-slate-800 p-5 font-sans text-slate-900 print:border-2 print:border-slate-800">
        <!-- Header -->
        <div class="border-b-2 border-slate-800 pb-3 text-center">
          <h2 class="text-xl font-bold tracking-wide uppercase text-slate-900">PAYMENT VOUCHER</h2>
          <p class="text-xs uppercase tracking-wider text-slate-600 font-medium">Truck Hire & Freight Disbursement Memo</p>
        </div>

        <!-- Meta Grid -->
        <div class="mt-3 grid grid-cols-2 gap-4 text-xs border-b border-slate-300 pb-3">
          <div>
            <p><span class="font-semibold text-slate-700">Voucher No:</span> <span class="font-bold text-slate-900">{{ payment.number ?? '—' }}</span></p>
            <p class="mt-1"><span class="font-semibold text-slate-700">Payment Date:</span> {{ payment.date ?? '—' }}</p>
            <p class="mt-1"><span class="font-semibold text-slate-700">Payment Nature:</span> <span class="font-semibold uppercase text-indigo-700">{{ paymentTypeLabel }}</span></p>
          </div>
          <div class="text-right">
            <p><span class="font-semibold text-slate-700">Trip No:</span> <span class="font-bold text-slate-900">#{{ trip.trip_no }}</span></p>
            <p class="mt-1"><span class="font-semibold text-slate-700">Truck / Lorry No:</span> <span class="font-bold uppercase text-slate-900">{{ payment.lorry_no || trip.lorry_no || '—' }}</span></p>
            <p class="mt-1"><span class="font-semibold text-slate-700">Route:</span> {{ trip.from_city ?? '—' }} → {{ trip.to_city ?? '—' }}</p>
          </div>
        </div>

        <!-- Particulars Details -->
        <div class="mt-4 space-y-2 text-xs">
          <div class="flex items-start justify-between border-b border-dashed border-slate-200 py-1.5">
            <span class="font-semibold text-slate-700">Paid To (Beneficiary):</span>
            <span class="font-bold text-slate-900 text-sm">{{ payment.pay_to ?? 'Vehicle Owner / Supplier' }}</span>
          </div>

          <div class="flex items-start justify-between border-b border-dashed border-slate-200 py-1.5">
            <span class="font-semibold text-slate-700">Payment Mode:</span>
            <span class="font-medium text-slate-800">{{ payment.method ?? 'Bank Transfer / NEFT' }}</span>
          </div>

          <div v-if="payment.reference" class="flex items-start justify-between border-b border-dashed border-slate-200 py-1.5">
            <span class="font-semibold text-slate-700">Reference / UTR / Cheque No:</span>
            <span class="font-mono font-medium text-slate-800">{{ payment.reference }}</span>
          </div>

          <div v-if="payment.notes" class="flex items-start justify-between border-b border-dashed border-slate-200 py-1.5">
            <span class="font-semibold text-slate-700">Particulars / Remarks:</span>
            <span class="text-slate-800">{{ payment.notes }}</span>
          </div>
        </div>

        <!-- Big Amount Box -->
        <div class="my-5 flex items-center justify-between rounded border-2 border-slate-900 bg-slate-50 px-4 py-3">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Amount Paid</span>
          <span class="text-2xl font-extrabold text-slate-900">{{ formatMoney(payment.amount) }}</span>
        </div>

        <!-- Signatures -->
        <div class="mt-8 grid grid-cols-3 gap-4 pt-8 text-center text-xs">
          <div class="border-t border-slate-400 pt-1">
            <p class="font-medium text-slate-700">Prepared By</p>
          </div>
          <div class="border-t border-slate-400 pt-1">
            <p class="font-medium text-slate-700">Checked / Verified</p>
          </div>
          <div class="border-t border-slate-400 pt-1">
            <p class="font-medium text-slate-700">Receiver's Signature</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  .voucher-box,
  .voucher-box * {
    visibility: visible;
  }
  .voucher-box {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
