<script setup lang="ts">
import EmptyArt from '@/components/EmptyArt.vue'
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { sortParams } from '@/api'
import type { SortParams, TableSort } from '@/api'
import { bulkTasks, deleteTask, listTasks } from '@/api/board'
import type { TaskSortKey } from '@/api/board'
import BulkActionBar from '@/components/BulkActionBar.vue'
import InvoicedBadge from '@/components/InvoicedBadge.vue'
import TaskFormModal from '@/components/TaskFormModal.vue'
import type { TaskDefaults } from '@/components/TaskFormModal.vue'
import TaskRunControl from '@/components/TaskRunControl.vue'
import { invoicingStore } from '@/stores/invoicing'
import { bumpTaskVersion, taskTime, taskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { filterKey, taskListParams } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import { formatDate, isOverdue } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { invoiceTasks } from '@/support/invoicing'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import { formatDuration } from '@/support/time'
import type { SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task, TaskListParams } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

interface TablePagination {
  totalPages: number
  currentPage: number
  totalCount: number
  limit: number
}

interface TableResult {
  data: Task[]
  pagination: TablePagination
}

const props = withDefaults(
  defineProps<{
    client: AxiosInstance
    notify: Notify
    /** The host router: invoicing lands on the host's invoice page. */
    router: Router
    /** What the screen above is filtered to. */
    filters: TaskFilterState
    statuses: TaskStatus[]
    members: CompanyMember[]
    projects: Project[]
    /** Set on a project page: the list is fixed to it and so is a new task. */
    projectId?: number | null
  }>(),
  { projectId: null },
)

const emit = defineEmits<{
  /** A task was created, changed or deleted, so any totals above are stale. */
  (event: 'changed'): void
}>()

const PER_PAGE = 10

/** Which API sort key each sortable column asks the endpoint for. */
const SORT_KEYS: Record<string, TaskSortKey> = {
  number: 'number',
  name: 'name',
}

/**
 * Narrower cells than the host table's own.
 *
 * A task row carries nine facts, where a host list carries six, and at the
 * host's `px-6` the table is some three hundred pixels wider than the content
 * area on a 1280 screen: the row menu ends up past the right edge, behind a
 * horizontal scrollbar nobody looks for. Half the padding fits the same
 * columns on the page and leaves the rows just as readable.
 */
const TH_CLASS =
  'whitespace-nowrap px-3 py-3 text-start text-xs font-medium text-muted uppercase tracking-wider'

const TD_CLASS = 'px-3 py-4 text-sm text-muted whitespace-nowrap'

const t = useTranslate()

const tableRef = ref<{ refresh: (preservePage?: boolean) => void } | null>(null)
const isFetching = ref(true)
const totalCount = ref(0)
const rows = ref<Task[]>([])
const selected = ref<number[]>([])
const bulkBusy = ref(false)
const modalOpen = ref(false)
const editing = ref<Task | null>(null)
const defaults = ref<TaskDefaults>({})
const busyId = ref<number | null>(null)

/** The form wants a picker's options; the screen above holds the records. */
const projectOptions = computed<SelectOption[]>(() =>
  props.projects.map((project) => ({ id: project.id, label: project.name })),
)

const invoicing = computed<boolean>(() => invoicingStore.busy)

const canInvoice = computed<boolean>(() => invoicingStore.allowed)

const filtered = computed<boolean>(
  () =>
    props.filters.search !== '' ||
    props.filters.status !== '' ||
    props.filters.user !== '' ||
    (props.projectId === null && props.filters.project !== ''),
)

const showEmptyScreen = computed(() => !isFetching.value && totalCount.value === 0 && !filtered.value)

/**
 * The columns, each in the module's own narrower cell.
 *
 * The due date rides under the task name rather than in a column of its own:
 * it is a detail of the task, it is empty on most rows, and a tenth column is
 * what pushed the table off the page.
 */
const columns = computed(() =>
  [
    { key: 'select', label: '', sortable: false, tdClass: 'w-8' },
    { key: 'number', label: t('tasks_projects.tasks.columns.number'), sortable: true, sortBy: 'number', tdClass: 'text-muted' },
    { key: 'name', label: t('tasks_projects.tasks.columns.name'), sortable: true, sortBy: 'name', thClass: 'extra', tdClass: 'font-medium text-heading' },
    { key: 'status', label: t('tasks_projects.tasks.columns.status'), sortable: false },
    { key: 'assignee', label: t('tasks_projects.tasks.columns.assignee'), sortable: false },
    { key: 'logged', label: t('tasks_projects.tasks.columns.logged'), sortable: false },
    { key: 'unbilled', label: t('tasks_projects.tasks.columns.unbilled'), sortable: false },
    { key: 'invoiced', label: t('tasks_projects.tasks.columns.invoiced'), sortable: false },
    { key: 'timer', label: t('tasks_projects.tasks.columns.timer'), sortable: false },
    { key: 'actions', label: t('tasks_projects.general.actions'), sortable: false, tdClass: 'text-end text-sm font-medium' },
  ].map((column) => ({ defaultThClass: TH_CLASS, defaultTdClass: TD_CLASS, ...column })),
)

// The screen above owns the filters; a change to them is a new first page.
watch(() => filterKey(props.filters), () => refresh())

watch(() => props.projectId, () => refresh())

// A write anywhere, including a timer that stopped on another screen.
watch(taskVersion, () => refresh(true))

async function fetchTasks({ page, sort }: { page: number; sort?: TableSort }): Promise<TableResult> {
  const order: SortParams<TaskSortKey> = sortParams(sort, SORT_KEYS)
  const params: TaskListParams & SortParams<TaskSortKey> = {
    page,
    limit: PER_PAGE,
    ...taskListParams(props.filters, { projectId: props.projectId }),
    ...order,
  }

  isFetching.value = true

  try {
    const response = await listTasks(props.client, params)
    const data = response.data ?? []
    const meta = response.meta

    totalCount.value = meta?.total ?? data.length
    rows.value = data
    // A row that left the page cannot stay in the selection the bar acts on.
    selected.value = selected.value.filter((id) => data.some((task) => task.id === id))

    return {
      data,
      pagination: {
        totalPages: meta?.last_page ?? 1,
        currentPage: meta?.current_page ?? 1,
        totalCount: meta?.total ?? data.length,
        limit: meta?.per_page ?? PER_PAGE,
      },
    }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.load_failed')))
    rows.value = []

    return {
      data: [],
      pagination: { totalPages: 1, currentPage: 1, totalCount: 0, limit: PER_PAGE },
    }
  } finally {
    isFetching.value = false
  }
}

function refresh(preservePage = false): void {
  tableRef.value?.refresh(preservePage)
}

function isSelected(task: Task): boolean {
  return selected.value.includes(task.id)
}

function toggle(task: Task): void {
  selected.value = isSelected(task)
    ? selected.value.filter((id) => id !== task.id)
    : [...selected.value, task.id]
}

function selectPage(): void {
  selected.value = rows.value.map((task) => task.id)
}

function clearSelection(): void {
  selected.value = []
}

function openCreate(): void {
  editing.value = null
  defaults.value = { project_id: props.projectId }
  modalOpen.value = true
}

function openEdit(task: Task): void {
  editing.value = task
  defaults.value = {}
  modalOpen.value = true
}

function onSaved(task: Task): void {
  const message = editing.value
    ? t('tasks_projects.tasks.updated', { name: task.name })
    : t('tasks_projects.tasks.created', { name: task.name })

  modalOpen.value = false
  editing.value = null
  props.notify('success', message)
  bumpTaskVersion()
  emit('changed')
}

function onDeleted(task: Task): void {
  modalOpen.value = false
  editing.value = null
  props.notify('success', t('tasks_projects.tasks.deleted', { name: task.name }))
  bumpTaskVersion()
  emit('changed')
}

function statusFor(task: Task): TaskStatus | null {
  return props.statuses.find((status) => status.id === task.task_status_id) ?? null
}

function assigneeName(task: Task): string {
  if (task.assignee_id === null) {
    return t('tasks_projects.tasks.unassigned')
  }

  return props.members.find((record) => record.id === task.assignee_id)?.name ?? `#${task.assignee_id}`
}

function loggedOf(task: Task): string {
  return formatDuration(taskTime(task).logged_minutes)
}

/**
 * Whether invoicing this row would mean anything.
 *
 * Only a task with billable time nobody has invoiced yet: one already on an
 * invoice, and one with no billable time behind it, both leave the entry in the
 * menu but greyed, with a title that says which of the two it is.
 */
function invoiceable(task: Task): boolean {
  return taskTime(task).invoiced === 'uninvoiced'
}

function invoiceHint(task: Task): string {
  return taskTime(task).invoiced === 'invoiced'
    ? t('tasks_projects.tasks.already_invoiced')
    : t('tasks_projects.tasks.nothing_to_invoice')
}

const deps = computed(() => ({
  client: props.client,
  router: props.router,
  notify: props.notify,
  t,
}))

async function onInvoiceTask(task: Task): Promise<void> {
  if (invoicing.value) {
    return
  }

  busyId.value = task.id

  try {
    await invoiceTasks(deps.value, { taskIds: [task.id] })
  } finally {
    busyId.value = null
  }
}

/**
 * Invoice the selection as one invoice.
 *
 * The server refuses a selection spanning two customers, which is the whole
 * point of sending the ids together rather than looping: one invoice, or a
 * message naming how many customers were mixed.
 */
async function onBulkInvoice(): Promise<void> {
  if (invoicing.value || selected.value.length === 0) {
    return
  }

  if (await invoiceTasks(deps.value, { taskIds: [...selected.value] })) {
    clearSelection()
  }
}

async function onDelete(task: Task): Promise<void> {
  if (!window.confirm(t('tasks_projects.tasks.delete_confirm', { name: task.name }))) {
    return
  }

  busyId.value = task.id

  try {
    await deleteTask(props.client, task.id)
    props.notify('success', t('tasks_projects.tasks.deleted', { name: task.name }))
    bumpTaskVersion()
    emit('changed')
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.delete_failed')))
  } finally {
    busyId.value = null
  }
}

async function onBulkStatus(statusId: number): Promise<void> {
  await runBulk({ action: 'status', ids: [...selected.value], task_status_id: statusId }, 'applied')
}

async function onBulkDelete(): Promise<void> {
  const count = selected.value.length

  if (!window.confirm(t('tasks_projects.tasks.bulk.delete_confirm', { count }))) {
    return
  }

  await runBulk({ action: 'delete', ids: [...selected.value] }, 'deleted')
}

/**
 * Apply one bulk action and say what actually happened.
 *
 * The endpoint is partial on purpose, so a locked task in the selection is
 * reported rather than swallowed: "12 updated, 2 refused" is the truth, and
 * "done" would not be.
 */
async function runBulk(
  input: Parameters<typeof bulkTasks>[1],
  success: 'applied' | 'deleted',
): Promise<void> {
  if (bulkBusy.value || input.ids.length === 0) {
    return
  }

  bulkBusy.value = true

  try {
    const result = await bulkTasks(props.client, input)

    if (result.failed.length > 0) {
      props.notify(
        'warning',
        t('tasks_projects.tasks.bulk.partial', {
          count: result.updated.length,
          failed: result.failed.length,
          ids: result.failed.map((entry) => `#${entry.id}`).join(', '),
        }),
      )
    } else if (result.updated.length === 0) {
      props.notify('warning', t('tasks_projects.tasks.bulk.nothing'))
    } else {
      props.notify(
        'success',
        t(`tasks_projects.tasks.bulk.${success}`, { count: result.updated.length }),
      )
    }

    clearSelection()
    bumpTaskVersion()
    emit('changed')
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.bulk.failed')))
  } finally {
    bulkBusy.value = false
  }
}

defineExpose({ openCreate, refresh })
</script>

<template>
  <div>
    <BulkActionBar
      :count="selected.length"
      :statuses="statuses"
      :busy="bulkBusy"
      :invoicing="invoicing"
      :can-invoice="canInvoice"
      @status="onBulkStatus"
      @delete="onBulkDelete"
      @invoice="onBulkInvoice"
      @clear="clearSelection"
      @select-page="selectPage"
    />

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :ghost="6"
      :title="t('tasks_projects.tasks.empty_title')"
      :description="t('tasks_projects.tasks.empty_description')"
    >
      <EmptyArt name="tasks" />

      <template #actions>
        <BaseButton variant="primary" @click="openCreate">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('tasks_projects.tasks.new_task') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <BaseTable ref="tableRef" :data="fetchTasks" :columns="columns" class="mt-3">
        <template #cell-select="{ row }">
          <BaseCheckbox
            :model-value="isSelected(row.data)"
            :aria-label="row.data.name"
            @change="toggle(row.data)"
          />
        </template>

        <template #cell-number="{ row }">#{{ row.data.number }}</template>

        <template #cell-name="{ row }">
          <router-link class="hover:text-primary-500" :to="PATHS.task(row.data.id)">
            {{ row.data.name }}
          </router-link>

          <span
            v-if="row.data.due_date"
            class="mt-0.5 block text-xs font-normal"
            :class="
              isOverdue(row.data.due_date) && !row.data.closed_at
                ? 'font-medium text-status-red'
                : 'text-muted'
            "
          >
            {{ t('tasks_projects.tasks.columns.due_date') }}: {{ formatDate(row.data.due_date) }}
          </span>
        </template>

        <template #cell-status="{ row }">
          <span class="inline-flex items-center whitespace-nowrap">
            <span
              class="me-2 inline-block h-2.5 w-2.5 shrink-0 rounded-full"
              :class="statusFor(row.data)?.colour ? '' : 'bg-line-default'"
              :style="
                statusFor(row.data)?.colour
                  ? { backgroundColor: statusFor(row.data)?.colour }
                  : undefined
              "
            />
            {{ statusFor(row.data)?.name ?? '-' }}
          </span>
        </template>

        <template #cell-assignee="{ row }">
          <span :class="row.data.assignee_id === null ? 'text-subtle' : ''">
            {{ assigneeName(row.data) }}
          </span>
        </template>

        <template #cell-logged="{ row }">
          <span class="tabular-nums">{{ loggedOf(row.data) }}</span>
        </template>

        <template #cell-unbilled="{ row }">
          <BaseFormatMoney
            v-if="taskTime(row.data).unbilled_amount > 0"
            :amount="taskTime(row.data).unbilled_amount"
          />
          <span v-else class="text-subtle">-</span>
        </template>

        <template #cell-invoiced="{ row }">
          <InvoicedBadge :state="taskTime(row.data).invoiced" />
        </template>

        <template #cell-timer="{ row }">
          <TaskRunControl
            :client="client"
            :notify="notify"
            :task="row.data"
            :members="members"
          />
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

            <template v-if="canInvoice">
              <BaseDropdownItem
                v-if="invoiceable(row.data) && !invoicing"
                @click="onInvoiceTask(row.data)"
              >
                <BaseIcon
                  name="BanknotesIcon"
                  class="me-3 h-5 w-5 text-subtle group-hover:text-muted"
                />
                {{ t('tasks_projects.tasks.invoice_task') }}
              </BaseDropdownItem>

              <div
                v-else
                class="group flex cursor-not-allowed items-center px-4 py-2 text-sm font-normal text-subtle"
                :title="invoicing ? t('tasks_projects.billing.busy') : invoiceHint(row.data)"
              >
                <BaseIcon name="BanknotesIcon" class="me-3 h-5 w-5 text-subtle" />
                {{ t('tasks_projects.tasks.invoice_task') }}
              </div>
            </template>

            <BaseDropdownItem @click="onDelete(row.data)">
              <BaseIcon name="TrashIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>

    <TaskFormModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :task="editing"
      :statuses="statuses"
      :members="members"
      :projects="projectOptions"
      :defaults="defaults"
      :lock-project="projectId !== null"
      :compact="editing === null"
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="onDeleted"
    />
  </div>
</template>
