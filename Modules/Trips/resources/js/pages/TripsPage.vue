<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { LocationQueryRaw, Router } from 'vue-router'
import { fetchStatuses } from '@/api/trips'
import TripFilters from '@/components/TripFilters.vue'
import TripViewSwitcher from '@/components/TripViewSwitcher.vue'
import { errorMessage } from '@/support/http'
import { filterQuery, readFilters, sameFilters } from '@/support/filters'
import type { TripFilterState } from '@/support/filters'
import { PATHS, ROUTES } from '@/support/page'
import type { Notify } from '@/support/page'
import type { TripStatus } from '@/types/trip'

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  router: Router
}>()

const hostRouter = props.router

const statuses = ref<TripStatus[]>([])
const loading = ref(true)

const currentRoute = computed(() => props.router.currentRoute.value)

const filters = computed<TripFilterState>(() => readFilters(currentRoute.value.query))

const query = computed<LocationQueryRaw>(() => filterQuery(filters.value))

const activeRoute = computed<string>(() => String(currentRoute.value.name ?? ''))

watch(activeRoute, (name) => ensureView(name))

onMounted(() => {
  ensureView(activeRoute.value)
  void loadPickers()
})

function ensureView(name: string): void {
  if (name === ROUTES.trips) {
    void hostRouter.replace({ name: ROUTES.board, query: query.value })
  }
}

async function loadPickers(): Promise<void> {
  loading.value = true

  try {
    statuses.value = await fetchStatuses(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, 'Unable to load statuses.'))
  } finally {
    loading.value = false
  }
}

function applyFilters(next: TripFilterState): void {
  if (sameFilters(next, filters.value)) {
    return
  }

  void hostRouter.replace({
    name: activeRoute.value === ROUTES.trips ? ROUTES.board : activeRoute.value,
    query: filterQuery(next),
  })
}

function switchView(to: { name: string; query: LocationQueryRaw }): void {
  void hostRouter.push(to)
}

function openNewTrip(): void {
  void hostRouter.push(PATHS.new)
}
</script>

<template>
  <BasePage>
    <BasePageHeader title="Trips">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem title="Dashboard" to="/admin/dashboard" />
        <BaseBreadcrumbItem title="Trips" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-3">
          <TripViewSwitcher :active="activeRoute" :query="query" @select="switchView" />

          <router-link :to="PATHS.reports">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ChartBarIcon" :class="slotProps.class" />
              </template>
              Reports
            </BaseButton>
          </router-link>

          <BaseButton variant="primary" @click="openNewTrip">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            New Trip
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <TripFilters
      :model-value="filters"
      :statuses="statuses"
      @update:model-value="applyFilters"
    />

    <router-view
      :filters="filters"
      :statuses="statuses"
      :loading="loading"
    />
  </BasePage>
</template>
