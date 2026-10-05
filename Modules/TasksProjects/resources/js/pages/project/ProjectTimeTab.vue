<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { listMembers } from '@/api'
import { listProjectTime, listTasks } from '@/api/board'
import { deleteTimeEntry } from '@/api/time'
import TimeEntryModal from '@/components/TimeEntryModal.vue'
import { bumpTaskVersion, taskVersion } from '@/stores/tasks'
import { useNow } from '@/stores/timer'
import { errorMessage } from '@/support/errors'
import { formatDate, formatMinutes } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import { formatClock, secondsBetween } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task } from '@/types/task'
import type { TimeEntry, TimeEntryListParams } from '@/types/time-entry'

interface TablePagination {
  totalPages: number
  currentPage: number
  totalCount: number
  limit: number
}

interface TableResult {
  data: TimeEntry[]
  pagination: TablePagination
}

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  project: Project | null
}>()

const emit = defineEmits<{ (event: 'refresh'): void }>()

const PER_PAGE = 15

/** How many task names one page of entries can need. */
const TASK_LIMIT = 100

const t = useTranslate()

/** One shared tick drives every running row. */
const now = useNow()

const tableRef = ref<{ refresh: (preservePage?: boolean) => void } | null>(null)
const members = ref<CompanyMember[]>([])
const tasks = ref<Task[]>([])
const modalOpen = ref(false)
const editing = ref<TimeEntry | null>(null)

const projectId = computed(() => props.project?.id ?? Number(props.id))

const columns = computed(() => [
  { key: 'started_at', label: t('tasks_projects.project.time.columns.date'), sortable: false },
  { key: 'user', label: t('tasks_projects.project.time.columns.member'), sortable: false },
  { key: 'task', label: t('tasks_projects.project.time.columns.task'), sortable: false, thClass: 'extra' },
  { key: 'duration_minutes', label: t('tasks_projects.project.time.columns.minutes'), sortable: false },
  { key: 'billable', label: t('tasks_projects.project.time.columns.billable'), sortable: false },
  { key: 'amount', label: t('tasks_projects.project.time.columns.amount'), sortable: false },
  {
    key: 'actions',
    label: t('tasks_projects.general.actions'),
    sortable: false,
    tdClass: 'text-end text-sm font-medium',
  },
])

onMounted(() => {
  void loadNames()
})

// A timer that started or stopped anywhere wrote a row into this project.
watch(taskVersion, () => tableRef.value?.refresh(true))

/** The member and task names the table shows beside every entry. */
async function loadNames(): Promise<void> {
  try {
    members.value = await listMembers(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.members_failed')))
  }

  try {
    const response = await listTasks(props.client, {
      project_id: projectId.value,
      limit: TASK_LIMIT,
    })

    tasks.value = response.data ?? []
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.load_failed')))
  }
}

async function fetchEntries({ page }: { page: number }): Promise<TableResult> {
  const params: TimeEntryListParams = { page, limit: PER_PAGE, project_id: projectId.value }

  try {
    const response = await listProjectTime(props.client, params)

    return {
      data: response.data ?? [],
      pagination: {
        totalPages: response.meta?.last_page ?? 1,
        currentPage: response.meta?.current_page ?? 1,
        totalCount: response.meta?.total ?? 0,
        limit: response.meta?.per_page ?? PER_PAGE,
      },
    }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.project.time.load_failed')))

    return {
      data: [],
      pagination: { totalPages: 1, currentPage: 1, totalCount: 0, limit: PER_PAGE },
    }
  }
}

function memberName(userId: number): string {
  return (
    members.value.find((member) => member.id === userId)?.name ??
    t('tasks_projects.project.time.removed_member')
  )
}

function taskName(taskId: number): string {
  return tasks.value.find((task) => task.id === taskId)?.name ?? `#${taskId}`
}

/** A running row counts up; a closed one shows the minutes it recorded. */
function durationOf(entry: TimeEntry): string {
  return entry.is_running
    ? formatClock(secondsBetween(entry.started_at, now.value))
    : formatMinutes(entry.duration_minutes)
}

function openCreate(): void {
  editing.value = null
  modalOpen.value = true
}

function openEdit(entry: TimeEntry): void {
  if (entry.is_running) {
    return
  }

  editing.value = entry
  modalOpen.value = true
}

function onSaved(): void {
  const message = editing.value
    ? t('tasks_projects.time.updated')
    : t('tasks_projects.time.created')

  modalOpen.value = false
  editing.value = null
  props.notify('success', message)
  tableRef.value?.refresh(true)
  bumpTaskVersion()
  emit('refresh')
}

function onDeleted(): void {
  modalOpen.value = false
  editing.value = null
  props.notify('success', t('tasks_projects.time.deleted'))
  tableRef.value?.refresh(true)
  bumpTaskVersion()
  emit('refresh')
}

async function remove(entry: TimeEntry): Promise<void> {
  if (!window.confirm(t('tasks_projects.time.delete_confirm'))) {
    return
  }

  try {
    await deleteTimeEntry(props.client, entry.id)
    props.notify('success', t('tasks_projects.time.deleted'))
    tableRef.value?.refresh(true)
    bumpTaskVersion()
    emit('refresh')
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.delete_failed')))
  }
}
</script>

<template>
  <div class="py-4">
    <div class="flex justify-end">
      <BaseButton variant="primary" @click="openCreate">
        <template #left="slotProps">
          <BaseIcon name="PlusIcon" :class="slotProps.class" />
        </template>
        {{ t('tasks_projects.project.time.add_entry') }}
      </BaseButton>
    </div>

    <div class="relative mt-3 table-container">
      <BaseTable ref="tableRef" :data="fetchEntries" :columns="columns">
        <template #cell-started_at="{ row }">
          {{ row.data.started_at ? formatDate(row.data.started_at) : '-' }}
        </template>

        <template #cell-user="{ row }">{{ memberName(row.data.user_id) }}</template>

        <template #cell-task="{ row }">
          <span class="font-medium text-heading">{{ taskName(row.data.task_id) }}</span>
          <span v-if="row.data.description" class="block text-xs text-muted">
            {{ row.data.description }}
          </span>
        </template>

        <template #cell-duration_minutes="{ row }">
          <span
            class="tabular-nums"
            :class="row.data.is_running ? 'font-medium text-primary-500' : ''"
          >
            {{ durationOf(row.data) }}
          </span>
        </template>

        <template #cell-billable="{ row }">
          <BaseIcon
            v-if="row.data.billable"
            name="CheckCircleIcon"
            class="h-5 w-5 text-status-green"
          />
          <span v-else class="text-subtle">-</span>
        </template>

        <template #cell-amount="{ row }">
          <BaseFormatMoney :amount="row.data.amount" />
        </template>

        <template #cell-actions="{ row }">
          <span v-if="row.data.is_running" class="text-xs text-primary-500">
            {{ t('tasks_projects.project.time.running') }}
          </span>

          <BaseDropdown v-else :label="t('tasks_projects.general.actions')">
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-muted" />
            </template>

            <BaseDropdownItem @click="openEdit(row.data)">
              <BaseIcon name="PencilIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.edit') }}
            </BaseDropdownItem>

            <BaseDropdownItem v-if="row.data.invoice_id === null" @click="remove(row.data)">
              <BaseIcon name="TrashIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <TimeEntryModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :entry="editing"
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="onDeleted"
    />
  </div>
</template>
