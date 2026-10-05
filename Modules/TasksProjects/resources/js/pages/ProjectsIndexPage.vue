<script setup lang="ts">
import EmptyArt from '@/components/EmptyArt.vue'
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { archiveProject, deleteProject, listProjects, sortParams, unarchiveProject } from '@/api'
import type { ProjectSortKey, SortParams, TableSort } from '@/api'
import ProjectFormModal from '@/components/ProjectFormModal.vue'
import { customerName, ensureLoaded } from '@/stores/customers'
import { errorMessage } from '@/support/errors'
import { formatDate } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { PATHS } from '@/support/page'
import type { Project, ProjectListParams, ProjectStatus } from '@/types/project'

type NotifyType = 'success' | 'error' | 'warning' | 'info'
type StatusFilter = ProjectStatus | 'ALL'

interface StatusOption {
  id: StatusFilter
  label: string
}

interface TablePagination {
  totalPages: number
  currentPage: number
  totalCount: number
  limit: number
}

interface TableResult {
  data: Project[]
  pagination: TablePagination
}

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  /** The host router. Links here go through `<router-link>`, which uses it. */
  router: Router
}>()

const PER_PAGE = 10
const SEARCH_DEBOUNCE_MS = 350

/** Which API sort key each sortable column asks the endpoint for. */
const SORT_KEYS: Record<string, ProjectSortKey> = {
  name: 'name',
  status: 'status',
  default_rate: 'default_rate',
  due_date: 'due_date',
}

const t = useTranslate()

const tableRef = ref<{ refresh: (preservePage?: boolean) => void } | null>(null)
const showFilters = ref(false)
const isFetching = ref(true)
const totalCount = ref(0)
const modalOpen = ref(false)
const editing = ref<Project | null>(null)
const busyId = ref<number | null>(null)

const filters = reactive<{ search: string; status: StatusFilter }>({
  search: '',
  status: 'ACTIVE',
})

const statusOptions = computed<StatusOption[]>(() => [
  { id: 'ACTIVE', label: t('tasks_projects.projects.status.active') },
  { id: 'ARCHIVED', label: t('tasks_projects.projects.status.archived') },
  { id: 'ALL', label: t('tasks_projects.projects.status.all') },
])

/**
 * The select binds whole option objects, so the selected one has to come back
 * out of the same array for the list to mark it as chosen.
 */
const statusOption = computed<StatusOption>({
  get: () => statusOptions.value.find((option) => option.id === filters.status) ?? statusOptions.value[0],
  set: (option: StatusOption) => {
    filters.status = option.id
  },
})

const columns = computed(() => [
  { key: 'name', label: t('tasks_projects.projects.columns.name'), sortable: true, sortBy: 'name', thClass: 'extra', tdClass: 'font-medium text-heading' },
  { key: 'status', label: t('tasks_projects.projects.columns.status'), sortable: true, sortBy: 'status' },
  { key: 'customer', label: t('tasks_projects.projects.columns.customer'), sortable: false },
  { key: 'default_rate', label: t('tasks_projects.projects.columns.default_rate'), sortable: true, sortBy: 'default_rate' },
  { key: 'due_date', label: t('tasks_projects.projects.columns.due_date'), sortable: true, sortBy: 'due_date' },
  { key: 'actions', label: t('tasks_projects.general.actions'), sortable: false, tdClass: 'text-end text-sm font-medium' },
])

const hasFilters = computed(() => filters.search.trim() !== '' || filters.status !== 'ACTIVE')

const showEmptyScreen = computed(() => !isFetching.value && totalCount.value === 0 && !hasFilters.value)

let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => refreshTable(), SEARCH_DEBOUNCE_MS)
  },
)

watch(() => filters.status, () => refreshTable())

onBeforeUnmount(() => clearTimeout(searchTimer))

async function fetchProjects({ page, sort }: { page: number; sort?: TableSort }): Promise<TableResult> {
  const order: SortParams<ProjectSortKey> = sortParams(sort, SORT_KEYS)
  const params: ProjectListParams & SortParams<ProjectSortKey> = { page, limit: PER_PAGE, ...order }

  if (filters.status !== 'ALL') {
    params.status = filters.status
  }

  if (filters.search.trim() !== '') {
    params.search = filters.search.trim()
  }

  isFetching.value = true

  try {
    const response = await listProjects(props.client, params)

    totalCount.value = response.meta.total

    // Only a page that shows a contact is worth one lookup of the address book.
    if (response.data.some((project) => project.customer_id !== null)) {
      void ensureLoaded(props.client)
    }

    return {
      data: response.data,
      pagination: {
        totalPages: response.meta.last_page,
        currentPage: response.meta.current_page,
        totalCount: response.meta.total,
        limit: response.meta.per_page,
      },
    }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.projects.load_failed')))

    return {
      data: [],
      pagination: { totalPages: 1, currentPage: 1, totalCount: 0, limit: PER_PAGE },
    }
  } finally {
    isFetching.value = false
  }
}

function refreshTable(preservePage = false): void {
  tableRef.value?.refresh(preservePage)
}

function toggleFilter(): void {
  if (showFilters.value) {
    clearFilters()
  }

  showFilters.value = !showFilters.value
}

function clearFilters(): void {
  filters.search = ''
  filters.status = 'ACTIVE'
}

function openCreate(): void {
  editing.value = null
  modalOpen.value = true
}

function openEdit(project: Project): void {
  editing.value = project
  modalOpen.value = true
}

function onSaved(project: Project): void {
  const message = editing.value
    ? t('tasks_projects.projects.updated', { name: project.name })
    : t('tasks_projects.projects.created', { name: project.name })

  modalOpen.value = false
  editing.value = null
  props.notify('success', message)
  refreshTable()
}

async function onArchive(project: Project): Promise<void> {
  busyId.value = project.id

  try {
    if (project.status === 'ARCHIVED') {
      await unarchiveProject(props.client, project.id)
      props.notify('success', t('tasks_projects.projects.unarchived', { name: project.name }))
    } else {
      await archiveProject(props.client, project.id)
      props.notify('success', t('tasks_projects.projects.archived', { name: project.name }))
    }

    refreshTable(true)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.projects.save_failed')))
  } finally {
    busyId.value = null
  }
}

async function onDelete(project: Project): Promise<void> {
  if (!window.confirm(t('tasks_projects.projects.delete_confirm', { name: project.name }))) {
    return
  }

  busyId.value = project.id

  try {
    await deleteProject(props.client, project.id)
    props.notify('success', t('tasks_projects.projects.deleted', { name: project.name }))
    refreshTable(true)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.projects.delete_failed')))
  } finally {
    busyId.value = null
  }
}

/**
 * The host badge carries its own colour classes and its stylesheet is loaded
 * again after the module's, so the override has to be important to hold.
 */
function statusClass(status: ProjectStatus): string {
  return status === 'ACTIVE'
    ? 'bg-primary-50! text-primary-500!'
    : 'bg-surface-tertiary! text-muted!'
}

function statusLabel(status: ProjectStatus): string {
  return status === 'ACTIVE'
    ? t('tasks_projects.projects.status.active')
    : t('tasks_projects.projects.status.archived')
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="t('tasks_projects.projects.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.projects.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <router-link :to="PATHS.tasks">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ClipboardDocumentListIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.tasks.title') }}
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

          <router-link :to="PATHS.billing">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="BanknotesIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.billing.title') }}
            </BaseButton>
          </router-link>

          <BaseButton variant="primary-outline" @click="toggleFilter">
            {{ t('tasks_projects.general.filter') }}
            <template #right="slotProps">
              <BaseIcon v-if="!showFilters" name="FunnelIcon" :class="slotProps.class" />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <BaseButton variant="primary" @click="openCreate">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.projects.new_project') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-3" @clear="clearFilters">
      <BaseInputGroup :label="t('tasks_projects.general.search')" class="mt-2 flex-1">
        <BaseInput
          v-model="filters.search"
          type="text"
          name="search"
          autocomplete="off"
          :placeholder="t('tasks_projects.projects.search_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="t('tasks_projects.projects.columns.status')" class="mt-2 flex-1">
        <BaseSelectInput v-model="statusOption" :options="statusOptions" label-key="label" />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :ghost="5"
      :title="t('tasks_projects.projects.empty_title')"
      :description="t('tasks_projects.projects.empty_description')"
    >
      <EmptyArt name="project" />

      <template #actions>
        <BaseButton variant="primary" @click="openCreate">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('tasks_projects.projects.new_project') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable ref="tableRef" :data="fetchProjects" :columns="columns" class="mt-3">
        <template #cell-name="{ row }">
          <div class="flex items-center">
            <span
              class="me-3 inline-block h-2.5 w-2.5 shrink-0 rounded-full"
              :class="row.data.colour ? '' : 'bg-line-default'"
              :style="row.data.colour ? { backgroundColor: row.data.colour } : undefined"
            />
            <span>
              <router-link
                class="hover:text-primary-500"
                :to="PATHS.project(row.data.id)"
              >
                {{ row.data.name }}
              </router-link>
              <span v-if="row.data.identifier" class="block text-xs font-normal text-muted">
                {{ row.data.identifier }}
              </span>
            </span>
          </div>
        </template>

        <template #cell-status="{ row }">
          <BaseBadge class="rounded-full" :class="statusClass(row.data.status)">
            {{ statusLabel(row.data.status) }}
          </BaseBadge>
        </template>

        <template #cell-customer="{ row }">
          <span v-if="row.data.customer_id">{{ customerName(row.data.customer_id) }}</span>
          <span v-else class="text-subtle">{{ t('tasks_projects.projects.internal') }}</span>
        </template>

        <template #cell-default_rate="{ row }">
          <BaseFormatMoney v-if="row.data.default_rate !== null" :amount="row.data.default_rate" />
          <span v-else class="text-subtle">-</span>
        </template>

        <template #cell-due_date="{ row }">
          <span v-if="row.data.due_date">{{ formatDate(row.data.due_date) }}</span>
          <span v-else class="text-subtle">-</span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown
            :content-loading="busyId === row.data.id"
            :label="t('tasks_projects.general.actions_for', { name: row.data.name })"
          >
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-muted" />
            </template>

            <BaseDropdownItem @click="openEdit(row.data)">
              <BaseIcon name="PencilIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.edit') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="onArchive(row.data)">
              <BaseIcon
                :name="row.data.status === 'ARCHIVED' ? 'ArrowPathIcon' : 'ArchiveBoxIcon'"
                class="me-3 h-5 w-5 text-subtle group-hover:text-muted"
              />
              {{
                row.data.status === 'ARCHIVED'
                  ? t('tasks_projects.projects.unarchive')
                  : t('tasks_projects.projects.archive')
              }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="onDelete(row.data)">
              <BaseIcon name="TrashIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <ProjectFormModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :project="editing"
      @close="modalOpen = false"
      @saved="onSaved"
    />
  </BasePage>
</template>
