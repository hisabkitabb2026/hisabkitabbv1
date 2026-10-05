<script setup lang="ts">
import type { LocationQueryRaw } from 'vue-router'
import { ROUTES } from '@/support/page'

interface View {
  id: string
  name: string
  label: string
  icon: string
}

const props = defineProps<{
  active: string
  query: LocationQueryRaw
}>()

const emit = defineEmits<{
  (event: 'select', to: { name: string; query: LocationQueryRaw }): void
}>()

const views: View[] = [
  { id: 'board', name: ROUTES.board, label: 'Board', icon: 'ViewColumnsIcon' },
  { id: 'list', name: ROUTES.list, label: 'List', icon: 'ListBulletIcon' },
  { id: 'week', name: ROUTES.week, label: 'Week', icon: 'CalendarDaysIcon' },
]

function isActive(view: View): boolean {
  return props.active === view.name || (view.id === 'board' && props.active === ROUTES.trips)
}

function select(view: View): void {
  if (!isActive(view)) {
    emit('select', { name: view.name, query: props.query })
  }
}
</script>

<template>
  <nav
    class="inline-flex overflow-hidden rounded-lg border border-line-default"
    aria-label="Trip views"
  >
    <button
      v-for="view in views"
      :key="view.id"
      type="button"
      class="flex items-center gap-1.5 border-e border-line-default px-3 py-1.5 text-sm font-medium last:border-e-0"
      :class="
        isActive(view)
          ? 'bg-primary-50 text-primary-500'
          : 'bg-surface text-muted hover:bg-hover hover:text-heading'
      "
      :aria-current="isActive(view) ? 'page' : undefined"
      @click="select(view)"
    >
      <BaseIcon :name="view.icon" class="h-4 w-4" />
      <span class="max-sm:sr-only">{{ view.label }}</span>
    </button>
  </nav>
</template>
