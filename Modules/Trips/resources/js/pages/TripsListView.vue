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

type SortField =
  | 'trip_no'
  | 'route'
  | 'customer'
  | 'lorry'
  | 'status'
  | 'revenue'
  | 'actual_revenue'
  | 'tds_amount'
  | 'cost'
  | 'profit'

const sortField = ref<SortField>('trip_no')
const sortOrder = ref<'asc' | 'desc'>('desc')

function toggleSort(field: SortField): void {
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortOrder.value = ['revenue', 'actual_revenue', 'tds_amount', 'cost', 'profit', 'trip_no'].includes(field)
      ? 'desc'
      : 'asc'
  }
  currentPage.value = 1
}

const isEmpty = computed<boolean>(() => !loading.value && trips.value.length === 0)

const sortedTrips = computed(() => {
  const list = [...trips.value]
  const field = sortField.value
  const order = sortOrder.value === 'asc' ? 1 : -1

  return list.sort((a, b) => {
    let comparison = 0

    switch (field) {
      case 'trip_no':
        comparison = a.trip_no - b.trip_no
        break

      case 'route': {
        const routeA = `${a.from_city ?? ''} ${a.to_city ?? ''}`.trim().toLowerCase()
        const routeB = `${b.from_city ?? ''} ${b.to_city ?? ''}`.trim().toLowerCase()
        comparison = routeA.localeCompare(routeB)
        break
      }

      case 'customer': {
        const custA = (a.customers[0]?.name ?? '').toLowerCase()
        const custB = (b.customers[0]?.name ?? '').toLowerCase()
        comparison = custA.localeCompare(custB)
        break
      }

      case 'lorry': {
        const lorryA = (a.lorry_no ?? '').toLowerCase()
        const lorryB = (b.lorry_no ?? '').toLowerCase()
        comparison = lorryA.localeCompare(lorryB)
        break
      }

      case 'status':
        comparison = a.status_id - b.status_id
        break

      case 'revenue':
        comparison = a.revenue - b.revenue
        break

      case 'actual_revenue':
        comparison = a.actual_revenue - b.actual_revenue
        break

      case 'tds_amount':
        comparison = a.tds_amount - b.tds_amount
        break

      case 'cost':
        comparison = a.cost - b.cost
        break

      case 'profit':
        comparison = a.profit - b.profit
        break
    }

    if (comparison === 0) {
      return b.trip_no - a.trip_no
    }

    return comparison * order
  })
})

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
              <th class="p-3 text-start font-medium" :class="{ 'text-heading font-semibold': sortField === 'trip_no' }">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none"
                  @click="toggleSort('trip_no')"
                >
                  <span>#</span>
                  <BaseIcon
                    v-if="sortField === 'trip_no'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-start font-medium" :class="{ 'text-heading font-semibold': sortField === 'route' }">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none"
                  @click="toggleSort('route')"
                >
                  <span>Route</span>
                  <BaseIcon
                    v-if="sortField === 'route'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-start font-medium" :class="{ 'text-heading font-semibold': sortField === 'customer' }">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none"
                  @click="toggleSort('customer')"
                >
                  <span>Customer</span>
                  <BaseIcon
                    v-if="sortField === 'customer'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-start font-medium" :class="{ 'text-heading font-semibold': sortField === 'lorry' }">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none"
                  @click="toggleSort('lorry')"
                >
                  <span>Lorry</span>
                  <BaseIcon
                    v-if="sortField === 'lorry'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-start font-medium" :class="{ 'text-heading font-semibold': sortField === 'status' }">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none"
                  @click="toggleSort('status')"
                >
                  <span>Status</span>
                  <BaseIcon
                    v-if="sortField === 'status'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-end font-medium" :class="{ 'text-heading font-semibold': sortField === 'revenue' }">
                <button
                  type="button"
                  class="inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto"
                  @click="toggleSort('revenue')"
                >
                  <span>Billed Rev</span>
                  <BaseIcon
                    v-if="sortField === 'revenue'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-end font-medium" :class="{ 'text-heading font-semibold': sortField === 'actual_revenue' }">
                <button
                  type="button"
                  class="inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto"
                  @click="toggleSort('actual_revenue')"
                >
                  <span>Actual Rev (Bank)</span>
                  <BaseIcon
                    v-if="sortField === 'actual_revenue'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-end font-medium" :class="{ 'text-heading font-semibold': sortField === 'tds_amount' }">
                <button
                  type="button"
                  class="inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto"
                  @click="toggleSort('tds_amount')"
                >
                  <span>TDS</span>
                  <BaseIcon
                    v-if="sortField === 'tds_amount'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-end font-medium" :class="{ 'text-heading font-semibold': sortField === 'cost' }">
                <button
                  type="button"
                  class="inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto"
                  @click="toggleSort('cost')"
                >
                  <span>Cost</span>
                  <BaseIcon
                    v-if="sortField === 'cost'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
              <th class="p-3 text-end font-medium" :class="{ 'text-heading font-semibold': sortField === 'profit' }">
                <button
                  type="button"
                  class="inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto"
                  @click="toggleSort('profit')"
                >
                  <span>Profit</span>
                  <BaseIcon
                    v-if="sortField === 'profit'"
                    :name="sortOrder === 'asc' ? 'ChevronUpIcon' : 'ChevronDownIcon'"
                    class="w-3.5 h-3.5 text-primary-600"
                  />
                </button>
              </th>
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
              <td class="p-3 font-medium text-heading">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span>#{{ trip.trip_no }}</span>
                  <span
                    v-if="trip.lr_numbers && trip.lr_numbers.length > 1"
                    class="inline-flex items-center rounded-md bg-primary-50 px-1.5 py-0.5 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20"
                    title="Consolidated Multi-LR Load"
                  >
                    LR: {{ trip.lr_numbers.join(', ') }}
                  </span>
                  <span
                    v-else-if="trip.lr_numbers && trip.lr_numbers.length === 1 && String(trip.trip_no) !== String(trip.lr_numbers[0])"
                    class="text-xs font-normal text-muted"
                  >
                    [LR: {{ trip.lr_numbers[0] }}]
                  </span>
                </div>
              </td>
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
