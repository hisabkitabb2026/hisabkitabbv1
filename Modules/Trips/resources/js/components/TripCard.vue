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
        #{{ trip.trip_no }} · {{ route }}
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
  </article>
</template>
