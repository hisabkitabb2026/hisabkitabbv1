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
import TripTablePagination from '@/components/TripTablePagination.vue'
import type { PaginationInfo } from '@/components/TripTablePagination.vue'

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
const currentPage = ref(1)
const perPage = ref(10)

const isEmpty = computed<boolean>(() => !loading.value && trips.value.length === 0)

const sortedTrips = computed(() => [...trips.value].sort((a, b) => b.trip_no - a.trip_no))

const totalCount = computed(() => sortedTrips.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalCount.value / perPage.value)))

const paginatedTrips = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return sortedTrips.value.slice(start, start + perPage.value)
})

const paginationInfo = computed<PaginationInfo>(() => ({
  currentPage: currentPage.value,
  totalPages: totalPages.value,
  totalCount: totalCount.value,
  count: paginatedTrips.value.length,
  limit: perPage.value,
}))

function onPageChange(page: number): void {
  currentPage.value = page
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const statusName = (statusId: number): string =>
  props.statuses.find((s) => s.id === statusId)?.name ?? '—'

const statusColour = (statusId: number): string =>
  props.statuses.find((s) => s.id === statusId)?.colour ?? '#6366f1'

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
  () => {
    currentPage.value = 1
    void load()
  },
)

watch(totalPages, (max) => {
  if (currentPage.value > max) {
    currentPage.value = max
  }
})

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

    <div v-else class="rounded-2xl border border-line-default bg-surface overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-line-default text-start text-xs uppercase text-muted">
              <th class="p-3 text-start font-medium">#</th>
              <th class="p-3 text-start font-medium">Route</th>
              <th class="p-3 text-start font-medium">Customer</th>
              <th class="p-3 text-start font-medium">Lorry</th>
              <th class="p-3 text-start font-medium">Status</th>
              <th class="p-3 text-end font-medium">Billed Rev</th>
              <th class="p-3 text-end font-medium">Actual Rev (Bank)</th>
              <th class="p-3 text-end font-medium">TDS</th>
              <th class="p-3 text-end font-medium">Cost</th>
              <th class="p-3 text-end font-medium">Profit</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="trip in paginatedTrips"
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
              <td class="p-3 text-body">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                  :style="{ backgroundColor: statusColour(trip.status_id) + '20', color: statusColour(trip.status_id) }"
                >
                  {{ statusName(trip.status_id) }}
                </span>
              </td>
              <td class="p-3 text-end text-body font-medium">
                {{ trip.revenue > 0 ? formatMoney(trip.revenue) : '—' }}
              </td>
              <td class="p-3 text-end font-medium text-emerald-600">
                {{ trip.actual_revenue > 0 ? formatMoney(trip.actual_revenue) : (trip.revenue > 0 ? formatMoney(0) : '—') }}
              </td>
              <td class="p-3 text-end font-medium text-indigo-600">
                {{ trip.tds_amount > 0 ? formatMoney(trip.tds_amount) : (trip.revenue > 0 || trip.actual_revenue > 0 ? formatMoney(0) : '—') }}
              </td>
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

      <TripTablePagination
        :pagination="paginationInfo"
        @page-change="onPageChange"
      />
    </div>
  </div>
</template>
