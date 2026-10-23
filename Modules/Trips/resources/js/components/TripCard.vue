<script setup lang="ts">
import { computed, ref } from 'vue'
import type { TripCard, TripStatus } from '@/types/trip'
import { formatMoney } from '@/support/format'

const props = defineProps<{
  trip: TripCard
  statuses: TripStatus[]
}>()

const emit = defineEmits<{
  (e: 'open', trip: TripCard): void
  (e: 'move', trip: TripCard, statusId: number): void
  (e: 'cancel', trip: TripCard): void
}>()

const menuOpen = ref(false)

const route = computed<string>(() => {
  const from = props.trip.from_city ?? '—'
  const to = props.trip.to_city ?? '—'

  return `${from} → ${to}`
})

const customerLine = computed<string>(() => {
  const names = props.trip.customers.map((c) => c.name).filter(Boolean) as string[]

  if (names.length === 0) {
    return 'No customer'
  }

  return names.length === 1 ? names[0] : `${names[0]} +${names.length - 1} more`
})

const profitClass = computed<string>(() =>
  props.trip.profit >= 0 ? 'text-status-green' : 'text-status-red',
)

function toggleMenu(): void {
  menuOpen.value = !menuOpen.value
}

function onMove(statusId: number): void {
  menuOpen.value = false
  emit('move', props.trip, statusId)
}

function onCancel(): void {
  menuOpen.value = false
  emit('cancel', props.trip)
}
</script>

<template>
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
  <article
    class="cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover"
    :class="{ 'opacity-50': trip.cancelled_at }"
    @click="emit('open', trip)"
  >
    <div class="flex items-start justify-between gap-2">
      <p class="text-sm font-medium text-heading">
        #{{ trip.trip_no }}
        <span v-if="trip.lr_numbers && trip.lr_numbers.length" class="text-xs font-normal text-muted">
          [{{ trip.lr_numbers.join(', ') }}]
        </span>
        · {{ route }}
      </p>
      <div class="relative">
        <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
        <button
          type="button"
          class="rounded px-1 text-muted hover:text-heading"
          aria-label="Trip menu"
          @click.stop="toggleMenu"
        >
          ⋯
        </button>
        <div
          v-if="menuOpen"
          class="absolute end-0 z-20 mt-1 w-44 rounded-lg border border-line-default bg-surface p-1 shadow-lg"
        >
          <button
            type="button"
            class="block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover"
            @click="menuOpen = false; emit('open', trip)"
          >
            Open trip
          </button>
          <p class="px-2 pt-1.5 pb-0.5 text-xs font-medium text-muted">Move to</p>
          <button
            v-for="status in statuses.filter((s) => s.id !== trip.status_id)"
            :key="status.id"
            type="button"
            class="block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover"
            @click="onMove(status.id)"
          >
            {{ status.name }}
          </button>
          <button
            v-if="!trip.cancelled_at"
            type="button"
            class="mt-1 block w-full rounded px-2 py-1.5 text-start text-sm text-status-red hover:bg-hover"
            @click="onCancel"
          >
            Cancel trip
          </button>
        </div>
      </div>
    </div>

    <p class="mt-1 text-sm text-muted">{{ customerLine }}</p>

    <p v-if="trip.lorry_no" class="mt-1 text-xs text-muted">🚛 {{ trip.lorry_no }}</p>

    <div class="mt-2 flex items-center justify-between gap-2">
      <span class="text-xs text-muted">
        {{ trip.lr_count }} LR{{ trip.lr_count === 1 ? '' : 's' }} · {{ formatMoney(trip.revenue) }}
      </span>
      <span class="text-sm font-semibold" :class="profitClass">
        {{ formatMoney(trip.profit) }}
      </span>
    </div>

    <div class="mt-2.5 flex items-center justify-between border-t border-line-light pt-2 text-xs">
      <div class="flex items-center gap-1.5">
        <span
          v-if="trip.payment_status === 'PAID'"
          class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
        >
          ● Payment Received
        </span>
        <span
          v-else-if="trip.payment_status === 'PARTIALLY_PAID'"
          class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"
        >
          ◐ Partially Paid
        </span>
        <span
          v-else
          class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20"
        >
          ○ Payment Pending
        </span>
      </div>
      <span v-if="trip.customer_paid > 0" class="text-[11px] text-muted">
        Recvd: <span class="font-medium text-heading">{{ formatMoney(trip.customer_paid) }}</span>
      </span>
    </div>
  </article>
</template>
