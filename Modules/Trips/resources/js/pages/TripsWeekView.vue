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

const weekStart = ref(startOfWeek(new Date()))

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const days = computed(() => {
  return DAY_NAMES.map((name, i) => {
    const date = new Date(weekStart.value)
    date.setDate(date.getDate() + i)
    const dateStr = formatDate(date)
    const dayTrips = trips.value.filter((t) => t.pickup_date === dateStr)

    return {
      name,
      date,
      dateStr,
      isToday: dateStr === formatDate(new Date()),
      trips: dayTrips,
      revenue: dayTrips.reduce((sum, t) => sum + t.revenue, 0),
    }
  })
})

const unscheduled = computed(() => trips.value.filter((t) => !t.pickup_date))

const weekLabel = computed(() => {
  const start = weekStart.value
  const end = new Date(start)
  end.setDate(end.getDate() + 6)
  const fmt = (d: Date): string =>
    d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })

  return `${fmt(start)} – ${fmt(end)}`
})

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

function prevWeek(): void {
  const d = new Date(weekStart.value)
  d.setDate(d.getDate() - 7)
  weekStart.value = d
}

function nextWeek(): void {
  const d = new Date(weekStart.value)
  d.setDate(d.getDate() + 7)
  weekStart.value = d
}

function thisWeek(): void {
  weekStart.value = startOfWeek(new Date())
}

function openTrip(trip: TripCardModel): void {
  props.router?.push(PATHS.trip(trip.id))
}

function startOfWeek(date: Date): Date {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - d.getDay())

  return d
}

function formatDate(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
</script>


<template>
  <div>
    <div class="mb-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <BaseButton size="sm" variant="white" @click="prevWeek">←</BaseButton>
        <span class="text-sm font-semibold text-heading">{{ weekLabel }}</span>
        <BaseButton size="sm" variant="white" @click="nextWeek">→</BaseButton>
        <BaseButton size="sm" variant="white" @click="thisWeek">Today</BaseButton>
      </div>
      <span class="text-sm text-muted">{{ trips.length }} trips this week</span>
    </div>

    <div v-if="loading" class="py-16 text-center text-sm text-muted">Loading the week…</div>

    <template v-else>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-7">
        <div
          v-for="day in days"
          :key="day.dateStr"
          class="flex min-h-48 flex-col rounded-xl border bg-surface p-2"
          :class="day.isToday ? 'border-primary-400' : 'border-line-default'"
        >
          <div class="mb-2 flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-muted">{{ day.name }}</p>
              <p class="text-lg font-semibold" :class="day.isToday ? 'text-primary-500' : 'text-heading'">
                {{ day.date.getDate() }}
              </p>
            </div>
            <span v-if="day.trips.length > 0" class="rounded-full bg-surface-tertiary px-2 py-0.5 text-xs text-muted">
              {{ day.trips.length }}
            </span>
          </div>

          <div class="flex-1 space-y-2">
            <div
              v-for="trip in day.trips"
              :key="trip.id"
              class="cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover"
              @click="openTrip(trip)"
            >
              <p class="truncate text-xs font-medium text-heading">#{{ trip.trip_no }} · {{ trip.from_city ?? '—' }} → {{ trip.to_city ?? '—' }}</p>
              <p class="mt-0.5 text-xs text-muted">
                {{ trip.lorry_no ?? 'No lorry' }} · {{ formatMoney(trip.revenue) }}
              </p>
            </div>
          </div>

          <p v-if="day.trips.length > 0" class="mt-2 border-t border-line-light pt-1 text-xs text-muted">
            {{ formatMoney(day.revenue) }}
          </p>
        </div>
      </div>

      <div v-if="unscheduled.length > 0" class="mt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">
          Unscheduled ({{ unscheduled.length }})
        </p>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="trip in unscheduled"
            :key="trip.id"
            class="cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover"
            @click="openTrip(trip)"
          >
            <p class="text-xs font-medium text-heading">#{{ trip.trip_no }}</p>
            <p class="text-xs text-muted">{{ trip.from_city ?? '—' }} → {{ trip.to_city ?? '—' }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
