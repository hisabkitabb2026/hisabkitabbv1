<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useTranslate } from '@/support/i18n'
import { EMPTY_FILTERS, hasFilters } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { TaskStatus } from '@/types/task-status'

/** A picker option whose id is the string the query string carries. */
interface FilterOption {
  id: string
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue: TaskFilterState
    projects: Project[]
    members: CompanyMember[]
    statuses: TaskStatus[]
    /** A project page fixes the project, so its picker is left out. */
    lockProject?: boolean
  }>(),
  { lockProject: false },
)

const emit = defineEmits<{
  (event: 'update:modelValue', filters: TaskFilterState): void
}>()

/** How long the typing has to stop before the list is asked again. */
const SEARCH_DEBOUNCE_MS = 350

const t = useTranslate()

const search = ref(props.modelValue.search)

let searchTimer: ReturnType<typeof setTimeout> | undefined

const projectOptions = computed<FilterOption[]>(() => [
  { id: '', label: t('tasks_projects.tasks.filters.all_projects') },
  ...props.projects.map((project) => ({ id: String(project.id), label: project.name })),
])

const memberOptions = computed<FilterOption[]>(() => [
  { id: '', label: t('tasks_projects.tasks.filters.all_members') },
  ...props.members.map((member) => ({ id: String(member.id), label: member.name })),
])

/**
 * Columns and invoicing states in one picker.
 *
 * "Uninvoiced" is the question people actually ask of a task list, and it cuts
 * across the columns rather than being one of them, so it sits in the same
 * control instead of adding a second one nobody would find.
 */
const statusOptions = computed<FilterOption[]>(() => [
  { id: '', label: t('tasks_projects.tasks.filters.all_statuses') },
  ...props.statuses.map((status) => ({ id: String(status.id), label: status.name })),
  { id: 'uninvoiced', label: t('tasks_projects.tasks.uninvoiced') },
  { id: 'invoiced', label: t('tasks_projects.tasks.invoiced') },
])

const projectOption = computed<FilterOption>({
  get: () => optionFor(projectOptions.value, props.modelValue.project),
  set: (option: FilterOption | null) => apply({ project: option?.id ?? '' }),
})

const memberOption = computed<FilterOption>({
  get: () => optionFor(memberOptions.value, props.modelValue.user),
  set: (option: FilterOption | null) => apply({ user: option?.id ?? '' }),
})

const statusOption = computed<FilterOption>({
  get: () => optionFor(statusOptions.value, props.modelValue.status),
  set: (option: FilterOption | null) => apply({ status: option?.id ?? '' }),
})

const showClear = computed<boolean>(() => hasFilters(props.modelValue))

// A view switch or a back button rewrites the query, so the box follows it.
watch(
  () => props.modelValue.search,
  (value) => {
    if (value !== search.value) {
      search.value = value
    }
  },
)

watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => apply({ search: value.trim() }), SEARCH_DEBOUNCE_MS)
})

onBeforeUnmount(() => clearTimeout(searchTimer))

function optionFor(options: FilterOption[], id: string): FilterOption {
  return options.find((option) => option.id === id) ?? options[0]
}

function apply(partial: Partial<TaskFilterState>): void {
  emit('update:modelValue', { ...props.modelValue, ...partial })
}

function clear(): void {
  search.value = ''
  emit('update:modelValue', { ...EMPTY_FILTERS })
}
</script>

<template>
  <div class="mt-4 flex flex-wrap items-end gap-3">
    <label v-if="!lockProject" class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">
        {{ t('tasks_projects.tasks.filters.project') }}
      </span>
      <BaseSelectInput v-model="projectOption" :options="projectOptions" label-key="label" />
    </label>

    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">
        {{ t('tasks_projects.tasks.filters.member') }}
      </span>
      <BaseSelectInput v-model="memberOption" :options="memberOptions" label-key="label" />
    </label>

    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">
        {{ t('tasks_projects.tasks.filters.status') }}
      </span>
      <BaseSelectInput v-model="statusOption" :options="statusOptions" label-key="label" />
    </label>

    <label class="min-w-44 flex-1">
      <span class="mb-1 block text-xs font-medium text-muted">
        {{ t('tasks_projects.tasks.filters.search') }}
      </span>
      <BaseInput
        v-model="search"
        type="text"
        name="search"
        autocomplete="off"
        :placeholder="t('tasks_projects.tasks.search_placeholder')"
      />
    </label>

    <button
      v-if="showClear"
      type="button"
      class="pb-2 text-sm font-medium text-primary-500 hover:underline"
      @click="clear"
    >
      {{ t('tasks_projects.tasks.bulk.clear') }}
    </button>
  </div>
</template>
