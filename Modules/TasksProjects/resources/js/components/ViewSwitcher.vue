<script setup lang="ts">
import { computed } from 'vue'
import type { LocationQueryRaw } from 'vue-router'
import { useTranslate } from '@/support/i18n'
import { ROUTES } from '@/support/page'

interface View {
  id: string
  name: string
  label: string
  icon: string
}

const props = defineProps<{
  /** The route name the Tasks screen is currently showing. */
  active: string
  /** The filters, so a view change keeps them. */
  query: LocationQueryRaw
}>()

const emit = defineEmits<{
  (event: 'select', to: { name: string; query: LocationQueryRaw }): void
}>()

const t = useTranslate()

const views = computed<View[]>(() => [
  {
    id: 'list',
    name: ROUTES.list,
    label: t('tasks_projects.tasks.views.list'),
    icon: 'ListBulletIcon',
  },
  {
    id: 'board',
    name: ROUTES.board,
    label: t('tasks_projects.tasks.views.board'),
    icon: 'ViewColumnsIcon',
  },
  {
    id: 'week',
    name: ROUTES.week,
    label: t('tasks_projects.tasks.views.week'),
    icon: 'CalendarDaysIcon',
  },
])

/**
 * The index child is what a link to the module root resolves to, but a visit
 * to the parent route itself is the same screen, so both light the List tab.
 */
function isActive(view: View): boolean {
  return props.active === view.name || (view.id === 'list' && props.active === ROUTES.tasks)
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
    :aria-label="t('tasks_projects.tasks.views.title')"
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
