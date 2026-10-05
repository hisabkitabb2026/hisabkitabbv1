<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import Sortable from 'sortablejs'
import type { SortableEvent } from 'sortablejs'
import { cancelTrip, fetchTrips, moveTrip } from '@/api/trips'
import type { TripCard as TripCardModel, TripStatus } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import { boardParams, filterKey } from '@/support/filters'
import type { TripFilterState } from '@/support/filters'
import TripCard from '@/components/TripCard.vue'

interface RouterLike {
  push: (to: string) => void
}

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  router?: RouterLike
  filters: TripFilterState
  statuses: TripStatus[]
  loading?: boolean
}>()

const trips = ref<TripCardModel[]>([])
const loading = ref(true)

const sortables = new Map<number, Sortable>()
const columnElements = new Map<number, HTMLElement>()

const columns = computed<{ status: TripStatus; cards: TripCardModel[] }[]>(() =>
  props.statuses.map((status) => ({
    status,
    cards: trips.value
      .filter((trip) => trip.status_id === status.id)
      .sort((a, b) => a.board_position - b.board_position),
  })),
)

const isEmpty = computed<boolean>(() => !loading.value && trips.value.length === 0)

async function load(): Promise<void> {
  loading.value = true

  try {
    trips.value = await fetchTrips(props.client, boardParams(props.filters))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load the board.'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})

watch(
  () => filterKey(props.filters),
  () => void load(),
)

onBeforeUnmount(() => {
  for (const sortable of sortables.values()) {
    sortable.destroy()
  }

  sortables.clear()
  columnElements.clear()
})

function columnElement(statusId: number): (el: unknown) => void {
  return (el: unknown): void => {
    if (!(el instanceof HTMLElement)) {
      sortables.get(statusId)?.destroy()
      sortables.delete(statusId)
      columnElements.delete(statusId)

      return
    }

    if (columnElements.get(statusId) === el) {
      return
    }

    sortables.get(statusId)?.destroy()

    columnElements.set(statusId, el)
    sortables.set(
      statusId,
      Sortable.create(el, {
        group: 'trips-board',
        animation: 150,
        onEnd: (event) => void onDrop(event),
      }),
    )
  }
}

/**
 * Put the dragged card back where it started.
 *
 * Sortable moves the node itself, which would leave Vue's list out of step
 * with the DOM. Undoing the move first makes the model the only writer: the
 * API response below re-renders the card in its new home.
 */
function restoreDom(event: SortableEvent): void {
  const item = event.item
  const oldIndex = event.oldIndex ?? 0

  item.parentNode?.removeChild(item)
  event.from.insertBefore(item, event.from.children[oldIndex] ?? null)
}

async function onDrop(event: SortableEvent): Promise<void> {
  const tripId = Number((event.item as HTMLElement).dataset.tripId)
  const toId = Number((event.to as HTMLElement).dataset.statusId)

  restoreDom(event)

  if (!tripId || Number.isNaN(toId)) {
    return
  }

  const siblings = trips.value
    .filter((trip) => trip.status_id === toId && trip.id !== tripId)
    .sort((a, b) => a.board_position - b.board_position)

  const before = siblings[event.newIndex - 1]
  const after = siblings[event.newIndex]

  const position =
    before && after
      ? (before.board_position + after.board_position) / 2
      : after
        ? after.board_position - 0.5
        : before
          ? before.board_position + 0.5
          : 1

  try {
    const card = await moveTrip(props.client, tripId, toId, position)
    trips.value = trips.value.map((trip) => (trip.id === tripId ? card : trip))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to move the trip.'))
    await load()
  }
}

function openTrip(trip: TripCardModel): void {
  props.router?.push(PATHS.trip(trip.id))
}

async function onMove(trip: TripCardModel, statusId: number): Promise<void> {
  try {
    const card = await moveTrip(props.client, trip.id, statusId, null)
    trips.value = trips.value.map((t) => (t.id === trip.id ? card : t))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to move the trip.'))
  }
}

async function onCancelTrip(trip: TripCardModel): Promise<void> {
  if (!window.confirm('Cancel this trip? It stays on the board, greyed out, and can never be billed.')) {
    return
  }

  try {
    await cancelTrip(props.client, trip.id)
    props.notify('success', 'Trip cancelled.')
    await load()
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to cancel the trip.'))
  }
}
</script>

<template>
  <div>
    <div v-if="loading" class="py-16 text-center text-sm text-muted">Loading the board…</div>

    <div v-else-if="isEmpty" class="rounded-2xl border border-line-default bg-surface p-12 text-center">
      <p class="text-4xl">🚛</p>
      <h2 class="mt-3 text-lg font-semibold text-heading">No trips yet</h2>
      <p class="mt-1 text-sm text-muted">
        Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one.
      </p>
    </div>

    <template v-else>
      <div class="flex items-stretch gap-4 overflow-x-auto pb-4">
        <section
          v-for="column in columns"
          :key="column.status.id"
          class="flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-line-default bg-surface shadow-sm"
        >
          <div class="h-1 w-full shrink-0" :style="{ backgroundColor: column.status.colour ?? '#94a3b8' }" />
          <header class="flex shrink-0 items-center gap-2 border-b border-line-default bg-surface-secondary/60 px-3 py-2.5">
            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: column.status.colour ?? '#94a3b8' }" />
            <span class="truncate text-sm font-semibold text-heading">{{ column.status.name }}</span>
            <span class="ml-auto shrink-0 rounded-full bg-surface-tertiary px-2 py-0.5 text-xs font-medium text-muted">
              {{ column.cards.length }}
            </span>
          </header>
          <div
            :ref="columnElement(column.status.id)"
            :data-status-id="column.status.id"
            class="min-h-44 flex-1 space-y-2 bg-surface-tertiary/40 p-2"
          >
            <div v-for="trip in column.cards" :key="trip.id" :data-trip-id="trip.id">
              <TripCard :trip="trip" :statuses="statuses" @open="openTrip" @move="onMove" @cancel="onCancelTrip" />
            </div>
            <p v-if="column.cards.length === 0" class="rounded-lg border border-dashed border-line-default py-10 text-center text-xs text-muted">
              Drop trips here
            </p>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

