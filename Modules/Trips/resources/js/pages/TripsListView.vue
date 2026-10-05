<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { fetchTrips } from '@/api/trips'
import type { TripCard as TripCardModel, TripStatus } from '@/types/trip'
import { errorMessage } from '@/support/http'
import { formatMoney } from '@/support/format'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import { boardParams, filterKey } from '@/support/filters'
import type { TripFilterState } from '@/support/filters'

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

const isEmpty = computed<boolean>(() => !loading.value && trips.value.length === 0)

const sortedTrips = computed(() => [...trips.value].sort((a, b) => b.trip_no - a.trip_no))

const statusName = (statusId: number): string =>
  props.statuses.find((s) => s.id === statusId)?.name ?? '—'

async function load(): Promise<void> {
  loading.value = true

  try {
    trips.value = await fetchTrips(props.client, boardParams(props.filters))
  } catch (error) {
    props.notify('error', errorMessage(error, 'Unable to load trips.'))
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

function openTrip(trip: TripCardModel): void {
  props.router?.push(PATHS.trip(trip.id))
}
</script>

<template>
  <div>
    <div v-if="loading" class="py-16 text-center text-sm text-muted">Loading trips…</div>

    <div v-else-if="isEmpty" class="rounded-2xl border border-line-default bg-surface p-12 text-center">
      <p class="text-4xl">🚛</p>
      <h2 class="mt-3 text-lg font-semibold text-heading">No trips found</h2>
      <p class="mt-1 text-sm text-muted">Try adjusting your filters, or create a new trip.</p>
    </div>

    <div v-else class="overflow-x-auto rounded-2xl border border-line-default bg-surface">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-line-default text-start text-xs uppercase text-muted">
            <th class="p-3 text-start font-medium">#</th>
            <th class="p-3 text-start font-medium">Route</th>
            <th class="p-3 text-start font-medium">Customer</th>
            <th class="p-3 text-start font-medium">Lorry</th>
            <th class="p-3 text-start font-medium">Status</th>
            <th class="p-3 text-end font-medium">Revenue</th>
            <th class="p-3 text-end font-medium">Cost</th>
            <th class="p-3 text-end font-medium">Profit</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="trip in sortedTrips"
            :key="trip.id"
            class="cursor-pointer border-b border-line-light hover:bg-hover"
            :class="{ 'opacity-50': trip.cancelled_at }"
            @click="openTrip(trip)"
          >
            <td class="p-3 font-medium text-heading">#{{ trip.trip_no }}</td>
            <td class="p-3 text-body">{{ trip.from_city ?? '—' }} → {{ trip.to_city ?? '—' }}</td>
            <td class="p-3 text-body">
              {{ trip.customers.map((c) => c.name).filter(Boolean).join(', ') || '—' }}
            </td>
            <td class="p-3 text-body">{{ trip.lorry_no ?? '—' }}</td>
            <td class="p-3 text-body">{{ statusName(trip.status_id) }}</td>
            <td class="p-3 text-end text-body">{{ formatMoney(trip.revenue) }}</td>
            <td class="p-3 text-end text-body">{{ formatMoney(trip.cost) }}</td>
            <td
              class="p-3 text-end font-semibold"
              :class="trip.profit >= 0 ? 'text-status-green' : 'text-status-red'"
            >
              {{ formatMoney(trip.profit) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
