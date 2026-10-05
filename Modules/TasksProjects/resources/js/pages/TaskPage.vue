<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { listMembers, listProjects } from '@/api'
import { deleteTask, fetchTaskDetail, listTaskStatuses, updateTask } from '@/api/board'
import InvoicedBadge from '@/components/InvoicedBadge.vue'
import InvoiceRetryBanner from '@/components/InvoiceRetryBanner.vue'
import TaskFormModal from '@/components/TaskFormModal.vue'
import TaskRunControl from '@/components/TaskRunControl.vue'
import TimeLogGrid from '@/components/TimeLogGrid.vue'
import { customerName, ensureLoaded } from '@/stores/customers'
import { invoicingStore } from '@/stores/invoicing'
import { bumpTaskVersion, rememberTask, taskTime, taskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { formatDate, formatMinutes } from '@/support/format'
import { errorCode } from '@/support/http'
import { useTranslate } from '@/support/i18n'
import { invoiceTasks } from '@/support/invoicing'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import type { SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task, TaskInput, TaskPriority } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  router: Router
}>()

/** How many projects the form's picker asks for. */
const PROJECT_LIMIT = 100

/** Colours a priority the way the list and the board do. */
const PRIORITY_CLASS: Record<TaskPriority, string> = {
  LOW: 'bg-surface-tertiary text-muted',
  NORMAL: 'bg-primary-50 text-primary-500',
  HIGH: 'bg-alert-warning-bg text-alert-warning-text',
  URGENT: 'bg-alert-error-bg text-alert-error-text',
}

const t = useTranslate()

/**
 * The host router, held as a local.
 *
 * `push` on a prop reads to the linter as mutating an array, and the router is
 * a stable singleton the host hands every module page, so naming it once is
 * both clearer and quieter.
 */
const hostRouter = props.router

const task = ref<Task | null>(null)
const statuses = ref<TaskStatus[]>([])
const members = ref<CompanyMember[]>([])
const projects = ref<Project[]>([])
const loading = ref(true)
const savingStatus = ref(false)
const removing = ref(false)
const modalOpen = ref(false)

const taskId = computed<number>(() => Number(props.id))

const time = computed(() => taskTime(task.value))

const title = computed<string>(() => task.value?.name ?? t('tasks_projects.tasks.title'))

const statusOptions = computed<SelectOption[]>(() =>
  statuses.value.map((status) => ({ id: status.id, label: status.name })),
)

const projectOptions = computed<SelectOption[]>(() =>
  projects.value.map((project) => ({ id: project.id, label: project.name })),
)

const project = computed<Project | undefined>(() =>
  projects.value.find((record) => record.id === task.value?.project_id),
)

const assigneeName = computed<string>(() => {
  const assigneeId = task.value?.assignee_id ?? null

  if (assigneeId === null) {
    return t('tasks_projects.tasks.unassigned')
  }

  return members.value.find((member) => member.id === assigneeId)?.name ?? `#${assigneeId}`
})

/**
 * The status picker writes straight through.
 *
 * Moving a task along is the one edit people make from this screen over and
 * over, so it costs one click rather than a form: the value is applied
 * locally, sent, and rolled back if the server refuses.
 */
const statusOption = computed<SelectOption | null>({
  get: () => statusOptions.value.find((option) => option.id === task.value?.task_status_id) ?? null,
  set: (option: SelectOption | null) => {
    if (option !== null) {
      void changeStatus(option)
    }
  },
})

const invoicing = computed<boolean>(() => invoicingStore.busy)

/**
 * Whether invoicing this task would mean anything.
 *
 * Only billable time nobody has invoiced yet: a task already on an invoice, or
 * one with nothing billable behind it, keeps the button visible but refused,
 * so the header does not change shape as work is logged against it.
 */
const invoiceable = computed<boolean>(() => time.value.invoiced === 'uninvoiced')

const invoiceHint = computed<string>(() =>
  time.value.invoiced === 'invoiced'
    ? t('tasks_projects.tasks.already_invoiced')
    : t('tasks_projects.tasks.nothing_to_invoice'),
)

const priorityLabel = computed<string | null>(() =>
  task.value?.priority
    ? t(`tasks_projects.tasks.priority.${task.value.priority.toLowerCase()}`)
    : null,
)

watch(taskId, () => void load())

// Another screen wrote to this task, or a timer started or stopped on it.
watch(taskVersion, () => void load(true))

onMounted(() => {
  void load()
  void loadPickers()
})

async function load(quiet = false): Promise<void> {
  if (!Number.isInteger(taskId.value) || taskId.value <= 0) {
    return
  }

  loading.value = !quiet

  try {
    const loaded = await fetchTaskDetail(props.client, taskId.value)

    task.value = loaded
    rememberTask(loaded)

    if (loaded.customer_id !== null) {
      void ensureLoaded(props.client)
    }
  } catch (error: unknown) {
    if (!quiet) {
      props.notify('error', errorMessage(error, t('tasks_projects.tasks.detail.not_found')))
    }
  } finally {
    loading.value = false
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
  } catch {
    // The assignee and the time log fall back to ids; reading members needs
    // an ability a member may not have, and the page works without it.
    members.value = []
  }

  try {
    const response = await listProjects(props.client, {
      limit: PROJECT_LIMIT,
      status: 'ACTIVE',
      sort_by: 'name',
    })

    projects.value = response.data ?? []
  } catch {
    projects.value = []
  }
}

/** The whole task as the update endpoint wants it, with one field changed. */
function payload(record: Task, overrides: Partial<TaskInput>): TaskInput {
  return {
    name: record.name,
    task_status_id: record.task_status_id,
    project_id: record.project_id,
    customer_id: record.project_id === null ? record.customer_id : null,
    description: record.description,
    assignee_id: record.assignee_id,
    priority: record.priority,
    due_date: record.due_date,
    estimated_minutes: record.estimated_minutes,
    billable: record.billable,
    rate: record.rate,
    ...overrides,
  }
}

/**
 * What to say when the server refused.
 *
 * A locked task is refused for a reason worth naming, and the module's own
 * sentence says it better than the server's, which states the rule for whoever
 * called the API rather than for the person looking at the task.
 */
function messageFor(error: unknown, fallbackKey: string): string {
  if (errorCode(error) === 'task_locked') {
    return t('tasks_projects.tasks.locked')
  }

  return errorMessage(error, t(fallbackKey))
}

async function changeStatus(option: SelectOption): Promise<void> {
  const record = task.value

  if (record === null || savingStatus.value || record.task_status_id === option.id) {
    return
  }

  const previous = record.task_status_id

  record.task_status_id = option.id
  savingStatus.value = true

  try {
    task.value = await updateTask(props.client, record.id, payload(record, { task_status_id: option.id }))
    props.notify('success', t('tasks_projects.tasks.detail.status_saved', { name: option.label }))
    bumpTaskVersion()
  } catch (error: unknown) {
    record.task_status_id = previous
    props.notify('error', messageFor(error, 'tasks_projects.tasks.detail.status_failed'))
  } finally {
    savingStatus.value = false
  }
}

async function invoice(): Promise<void> {
  const record = task.value

  if (record === null || !invoiceable.value || invoicing.value) {
    return
  }

  await invoiceTasks(
    { client: props.client, router: hostRouter, notify: props.notify, t },
    { taskIds: [record.id] },
  )
}

/** Leave for the list, which is where a task that no longer exists belongs. */
function backToTasks(): void {
  void hostRouter.push(PATHS.tasks)
}

function onSaved(saved: Task): void {
  modalOpen.value = false
  task.value = saved
  rememberTask(saved)
  props.notify('success', t('tasks_projects.tasks.updated', { name: saved.name }))
  bumpTaskVersion()
}

async function remove(): Promise<void> {
  const record = task.value

  if (record === null || removing.value) {
    return
  }

  if (!window.confirm(t('tasks_projects.tasks.delete_confirm', { name: record.name }))) {
    return
  }

  removing.value = true

  try {
    await deleteTask(props.client, record.id)
    props.notify('success', t('tasks_projects.tasks.deleted', { name: record.name }))
    bumpTaskVersion()
    backToTasks()
  } catch (error: unknown) {
    props.notify('error', messageFor(error, 'tasks_projects.tasks.delete_failed'))
  } finally {
    removing.value = false
  }
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="title">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.tasks.title')" :to="PATHS.tasks" />
        <BaseBreadcrumbItem :title="task ? `#${task.number}` : title" to="#" active />
      </BaseBreadcrumb>

      <div v-if="task" class="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted">
        <span class="rounded-sm bg-surface-tertiary px-2 py-0.5 text-body">#{{ task.number }}</span>

        <router-link
          v-if="task.project_id"
          class="hover:text-primary-500"
          :to="PATHS.project(task.project_id)"
        >
          {{ project?.name ?? `#${task.project_id}` }}
        </router-link>
        <span v-else class="text-subtle">{{ t('tasks_projects.tasks.no_project') }}</span>

        <router-link
          v-if="task.customer_id"
          class="hover:text-primary-500"
          :to="PATHS.customer(task.customer_id)"
        >
          {{ customerName(task.customer_id) }}
        </router-link>

        <span
          v-if="priorityLabel && task.priority"
          class="rounded-full px-2 py-0.5 text-xs font-medium"
          :class="PRIORITY_CLASS[task.priority]"
        >
          {{ priorityLabel }}
        </span>

        <InvoicedBadge :state="time.invoiced" />
      </div>

      <template #actions>
        <div v-if="task" class="flex flex-wrap items-center justify-end gap-3">
          <TaskRunControl
            :client="client"
            :notify="notify"
            :task="task"
            :members="members"
            size="md"
          />

          <span
            v-if="invoicingStore.allowed"
            class="inline-flex"
            :title="invoiceable ? undefined : invoiceHint"
          >
            <BaseButton
              variant="white"
              :loading="invoicing"
              :disabled="!invoiceable || invoicing"
              @click="invoice"
            >
              <template #left="slotProps">
                <BaseIcon v-if="!invoicing" name="BanknotesIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.tasks.invoice_task') }}
            </BaseButton>
          </span>

          <BaseButton
            variant="primary-outline"
            :loading="removing"
            :disabled="removing"
            @click="remove"
          >
            {{ t('tasks_projects.general.delete') }}
          </BaseButton>

          <BaseButton variant="primary" @click="modalOpen = true">
            <template #left="slotProps">
              <BaseIcon name="PencilIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.general.edit') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <InvoiceRetryBanner :client="client" :notify="notify" />

    <div v-if="loading && task === null" class="flex justify-center py-16">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
    </div>

    <template v-else-if="task">
      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-line-default bg-surface p-5">
          <p id="task-status-label" class="text-xs font-medium tracking-wider text-muted uppercase">
            {{ t('tasks_projects.tasks.detail.status') }}
          </p>
          <div class="mt-2">
            <BaseSelectInput
              v-model="statusOption"
              aria-labelledby="task-status-label"
              :options="statusOptions"
              :disabled="savingStatus"
              label-key="label"
            />
          </div>
          <p class="mt-2 text-xs text-muted">
            {{ t('tasks_projects.tasks.detail.assignee') }}:
            <span class="text-body">{{ assigneeName }}</span>
          </p>
        </div>

        <div class="rounded-xl border border-line-default bg-surface p-5">
          <p class="text-xs font-medium tracking-wider text-muted uppercase">
            {{ t('tasks_projects.tasks.detail.logged') }}
          </p>
          <p class="mt-2 text-2xl font-semibold text-heading">
            {{ formatMinutes(time.logged_minutes) }}
          </p>
          <p class="mt-1 text-xs text-muted">
            {{ t('tasks_projects.tasks.detail.estimate') }}:
            <span class="text-body">
              {{
                task.estimated_minutes
                  ? formatMinutes(task.estimated_minutes)
                  : t('tasks_projects.tasks.detail.no_estimate')
              }}
            </span>
          </p>
        </div>

        <div class="rounded-xl border border-line-default bg-surface p-5">
          <p class="text-xs font-medium tracking-wider text-muted uppercase">
            {{ t('tasks_projects.tasks.detail.unbilled') }}
          </p>
          <p class="mt-2 text-2xl font-semibold text-heading">
            <BaseFormatMoney :amount="time.unbilled_amount" />
          </p>
          <p class="mt-1 text-xs text-muted">
            {{ formatMinutes(time.unbilled_minutes) }}
          </p>
        </div>

        <div class="rounded-xl border border-line-default bg-surface p-5">
          <p class="text-xs font-medium tracking-wider text-muted uppercase">
            {{ t('tasks_projects.tasks.detail.due_date') }}
          </p>
          <p class="mt-2 text-lg font-semibold text-heading">
            {{ task.due_date ? formatDate(task.due_date) : '-' }}
          </p>
          <p class="mt-1 text-xs text-muted">
            {{
              task.billable
                ? t('tasks_projects.tasks.billable')
                : t('tasks_projects.time.non_billable')
            }}
          </p>
        </div>
      </div>

      <div class="mt-4 rounded-xl border border-line-default bg-surface p-5">
        <p class="text-xs font-medium tracking-wider text-muted uppercase">
          {{ t('tasks_projects.tasks.detail.description') }}
        </p>
        <p v-if="task.description" class="mt-2 text-sm whitespace-pre-line text-body">
          {{ task.description }}
        </p>
        <p v-else class="mt-2 text-sm text-subtle">
          {{ t('tasks_projects.tasks.detail.no_description') }}
        </p>
      </div>

      <TimeLogGrid :client="client" :notify="notify" :task="task" :members="members" />
    </template>

    <TaskFormModal
      v-if="task"
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :task="task"
      :statuses="statuses"
      :members="members"
      :projects="projectOptions"
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="backToTasks"
    />
  </BasePage>
</template>
