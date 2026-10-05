<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { LocationQueryRaw, Router } from 'vue-router'
import { listMembers, listProjects } from '@/api'
import { listTaskStatuses } from '@/api/board'
import InvoiceRetryBanner from '@/components/InvoiceRetryBanner.vue'
import TaskFilters from '@/components/TaskFilters.vue'
import TaskFormModal from '@/components/TaskFormModal.vue'
import ViewSwitcher from '@/components/ViewSwitcher.vue'
import { bumpTaskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { filterQuery, readFilters, sameFilters } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import { useTranslate } from '@/support/i18n'
import { PATHS, ROUTES } from '@/support/page'
import type { Notify } from '@/support/page'
import type { SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  /** The host router: a module bundle cannot call `useRouter()`. */
  router: Router
}>()

/** How many projects the picker asks for. Archived ones are left out. */
const PROJECT_LIMIT = 100

const t = useTranslate()

/**
 * The host router, held as a local.
 *
 * `push` on a prop reads to the linter as mutating an array, and the router is
 * a stable singleton the host hands every module page, so naming it once is
 * both clearer and quieter.
 */
const hostRouter = props.router

const statuses = ref<TaskStatus[]>([])
const members = ref<CompanyMember[]>([])
const projects = ref<Project[]>([])
const modalOpen = ref(false)

/**
 * The address bar is the filter state.
 *
 * Every view reads the same four values, so keeping them in the URL is what
 * makes a view switch, a reload, a back button and a pasted link all show the
 * same screen. Nothing is mirrored into a local ref, which is what would let
 * the two disagree.
 */
const currentRoute = computed(() => props.router.currentRoute.value)

const filters = computed<TaskFilterState>(() => readFilters(currentRoute.value.query))

const query = computed<LocationQueryRaw>(() => filterQuery(filters.value))

const activeRoute = computed<string>(() => String(currentRoute.value.name ?? ''))

const projectOptions = computed<SelectOption[]>(() =>
  projects.value.map((project) => ({ id: project.id, label: project.name })),
)

/**
 * Land on the List view when the parent route itself is the target.
 *
 * A link to the module root resolves to the index child on its own, but
 * navigating to the parent by name matches the parent alone and would leave
 * the screen with an empty body.
 */
watch(activeRoute, (name) => ensureView(name))

onMounted(() => {
  ensureView(activeRoute.value)
  void loadPickers()
})

function ensureView(name: string): void {
  if (name === ROUTES.tasks) {
    void hostRouter.replace({ name: ROUTES.list, query: query.value })
  }
}

async function loadPickers(): Promise<void> {
  try {
    statuses.value = await listTaskStatuses(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.task_statuses.load_failed')))
  }

  try {
    members.value = await listMembers(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.members_failed')))
  }

  try {
    const response = await listProjects(props.client, {
      limit: PROJECT_LIMIT,
      status: 'ACTIVE',
      sort_by: 'name',
    })

    projects.value = response.data ?? []
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.projects_failed')))
  }
}

/** Write the filters back to the URL, without stacking history entries. */
function applyFilters(next: TaskFilterState): void {
  if (sameFilters(next, filters.value)) {
    return
  }

  void hostRouter.replace({
    name: activeRoute.value === ROUTES.tasks ? ROUTES.list : activeRoute.value,
    query: filterQuery(next),
  })
}

function switchView(to: { name: string; query: LocationQueryRaw }): void {
  void hostRouter.push(to)
}

function onSaved(task: Task): void {
  modalOpen.value = false
  props.notify('success', t('tasks_projects.tasks.created', { name: task.name }))
  bumpTaskVersion()
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="t('tasks_projects.tasks.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.tasks.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-3">
          <ViewSwitcher :active="activeRoute" :query="query" @select="switchView" />

          <router-link :to="PATHS.projects">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="FolderIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.projects.title') }}
            </BaseButton>
          </router-link>

          <router-link :to="PATHS.reports">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ChartBarIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.reports.title') }}
            </BaseButton>
          </router-link>

          <BaseButton variant="primary" @click="modalOpen = true">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.tasks.new_task') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <InvoiceRetryBanner :client="client" :notify="notify" />

    <TaskFilters
      :model-value="filters"
      :projects="projects"
      :members="members"
      :statuses="statuses"
      @update:model-value="applyFilters"
    />

    <router-view
      :filters="filters"
      :statuses="statuses"
      :members="members"
      :projects="projects"
    />

    <TaskFormModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :task="null"
      :statuses="statuses"
      :members="members"
      :projects="projectOptions"
      :defaults="{ project_id: filters.project === '' ? null : Number(filters.project) }"
      compact
      @close="modalOpen = false"
      @saved="onSaved"
    />
  </BasePage>
</template>
