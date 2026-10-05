<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { createTask, deleteTask, updateTask } from '@/api/board'
import { customerName, ensureLoaded } from '@/stores/customers'
import { errorMessage, fieldErrors } from '@/support/errors'
import { errorCode } from '@/support/http'
import {
  hoursToMinutes,
  majorToMinor,
  minorToMajor,
  minutesToHours,
  toDateString,
} from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type { SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import { TASK_PRIORITIES } from '@/types/task'
import type { Task, TaskInput } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

/** What a card or a "New task" button preselects for a fresh task. */
export interface TaskDefaults {
  project_id?: number | null
  task_status_id?: number | null
}

const props = defineProps<{
  show: boolean
  client: AxiosInstance
  notify: Notify
  /** The task being edited, or null to create one. */
  task: Task | null
  statuses: TaskStatus[]
  members: CompanyMember[]
  projects: SelectOption[]
  /** Preselections for a new task, such as the column its button sits in. */
  defaults?: TaskDefaults
  /** A project page fixes the project, so the picker is hidden. */
  lockProject?: boolean
  /**
   * Show the six fields a task is usually created with, and put the rest
   * behind a disclosure.
   *
   * Creating a task should cost a name and a column; a description, an
   * estimate and a rate override are things people come back to fill in, and
   * asking for them up front is what made the old drawer read as a form to be
   * completed rather than a box to be typed into.
   */
  compact?: boolean
}>()

const emit = defineEmits<{
  (event: 'close'): void
  (event: 'saved', task: Task): void
  (event: 'deleted', task: Task): void
}>()

const t = useTranslate()

const form = reactive({
  name: '',
  description: '',
  /** Hours, as typed. Converted to minutes on save. */
  estimateHours: '',
  /** Major units, as typed. Converted to minor units on save. */
  rate: '',
  dueDate: '',
  billable: true,
})

const status = ref<SelectOption | null>(null)
const projectId = ref<number | null>(null)
const assignee = ref<SelectOption | null>(null)
const priority = ref<SelectOption | null>(null)
const customerId = ref<number | null>(null)
const errors = ref<Record<string, string>>({})
const saving = ref(false)
const removing = ref(false)
const expanded = ref(false)

/** Whether the fields behind the compact mode's disclosure are on show. */
const showAllFields = computed<boolean>(() => !props.compact || expanded.value)

/**
 * The picker binds an option, the payload wants an id.
 *
 * Keeping the id as the source of truth matters on a project page, where the
 * project is fixed, the picker is hidden and the options were never loaded.
 */
const project = computed<SelectOption | null>({
  get: () => optionFor(props.projects, projectId.value),
  set: (option: SelectOption | null) => {
    projectId.value = option?.id ?? null
  },
})

const isEdit = computed(() => props.task !== null)

const title = computed(() =>
  isEdit.value ? t('tasks_projects.tasks.edit_task') : t('tasks_projects.tasks.new_task'),
)

const statusOptions = computed<SelectOption[]>(() =>
  props.statuses.map((record) => ({ id: record.id, label: record.name })),
)

const memberOptions = computed<SelectOption[]>(() =>
  props.members.map((member) => ({ id: member.id, label: member.name })),
)

/**
 * The priority list is a fixed enum, so the option ids are its positions and
 * the label comes from the catalogue rather than from the stored value.
 */
const priorityOptions = computed<SelectOption[]>(() =>
  TASK_PRIORITIES.map((name, index) => ({
    id: index,
    label: t(`tasks_projects.tasks.priority.${name.toLowerCase()}`),
  })),
)

watch(
  () => props.show,
  (show) => {
    if (show) {
      reset()
    }
  },
  { immediate: true },
)

function optionFor(options: SelectOption[], id: number | null): SelectOption | null {
  return id === null ? null : (options.find((option) => option.id === id) ?? null)
}

function reset(): void {
  const task = props.task

  form.name = task?.name ?? ''
  form.description = task?.description ?? ''
  form.estimateHours = minutesToHours(task?.estimated_minutes ?? null)
  form.rate = minorToMajor(task?.rate ?? null)
  form.dueDate = task?.due_date ?? ''
  form.billable = task?.billable ?? true

  const defaultStatus = props.statuses.find((record) => record.is_default) ?? props.statuses[0]
  const statusId = task?.task_status_id ?? props.defaults?.task_status_id ?? defaultStatus?.id ?? null

  status.value = optionFor(statusOptions.value, statusId)
  projectId.value = task?.project_id ?? props.defaults?.project_id ?? null
  assignee.value = optionFor(memberOptions.value, task?.assignee_id ?? null)
  priority.value = task?.priority
    ? (priorityOptions.value[TASK_PRIORITIES.indexOf(task.priority)] ?? null)
    : null
  customerId.value = task?.customer_id ?? null
  errors.value = {}
  expanded.value = false

  // The field names the contact rather than its id, and the map is shared with
  // every other screen, so the first form to need it is the one that asks.
  if (customerId.value !== null) {
    void ensureLoaded(props.client)
  }
}

function onDueDate(value: string | Date): void {
  form.dueDate = value ? toDateString(value) : ''
}

function payload(): TaskInput | null {
  const statusId = status.value?.id ?? null

  if (statusId === null) {
    return null
  }

  const chosen = priority.value === null ? null : TASK_PRIORITIES[priority.value.id]

  return {
    name: form.name.trim(),
    task_status_id: statusId,
    project_id: projectId.value,
    customer_id: projectId.value === null ? customerId.value : null,
    description: form.description.trim() || null,
    assignee_id: assignee.value?.id ?? null,
    priority: chosen,
    due_date: form.dueDate || null,
    estimated_minutes: hoursToMinutes(form.estimateHours),
    billable: form.billable,
    rate: majorToMinor(form.rate),
  }
}

async function save(): Promise<void> {
  if (saving.value) {
    return
  }

  if (form.name.trim() === '') {
    errors.value = { name: t('tasks_projects.tasks.name_required') }

    return
  }

  const input = payload()

  if (input === null) {
    props.notify('error', t('tasks_projects.task_statuses.none'))

    return
  }

  saving.value = true
  errors.value = {}

  try {
    const existing = props.task
    const task = existing
      ? await updateTask(props.client, existing.id, input)
      : await createTask(props.client, input)

    emit('saved', task)
  } catch (error: unknown) {
    errors.value = fieldErrors(error)
    props.notify('error', messageFor(error, 'save_failed'))
  } finally {
    saving.value = false
  }
}

/**
 * What to say when a save or a delete is refused.
 *
 * A locked task is refused for a reason worth naming rather than as a generic
 * failure, so the caller understands that the invoice, not a bug, is in the
 * way. The module's own sentence wins over the server's, which states the rule
 * for whoever called the API rather than for the person at the form.
 */
function messageFor(error: unknown, key: 'save_failed' | 'delete_failed'): string {
  if (errorCode(error) === 'task_locked') {
    return t('tasks_projects.tasks.locked')
  }

  return errorMessage(error, t(`tasks_projects.tasks.${key}`))
}

async function remove(): Promise<void> {
  const task = props.task

  if (task === null || removing.value) {
    return
  }

  if (!window.confirm(t('tasks_projects.tasks.delete_confirm', { name: task.name }))) {
    return
  }

  removing.value = true

  try {
    await deleteTask(props.client, task.id)
    emit('deleted', task)
  } catch (error: unknown) {
    props.notify('error', messageFor(error, 'delete_failed'))
  } finally {
    removing.value = false
  }
}
</script>

<template>
  <BaseModal :show="show" @close="emit('close')">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>
          {{ title }}
          <span v-if="task" class="ms-2 text-sm font-normal text-muted">#{{ task.number }}</span>
        </span>
        <button
          type="button"
          class="-m-1.5 rounded-lg p-1.5 text-subtle hover:text-body focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          :aria-label="t('tasks_projects.general.close')"
          @click="emit('close')"
        >
          <BaseIcon name="XMarkIcon" class="h-6 w-6" />
        </button>
      </div>
    </template>

    <form @submit.prevent="save">
      <div class="space-y-5 px-6 py-6">
        <BaseInputGroup
          :label="t('tasks_projects.tasks.fields.name')"
          :error="errors.name"
          required
        >
          <BaseInput v-model="form.name" :invalid="Boolean(errors.name)" type="text" />
        </BaseInputGroup>

        <BaseInputGrid>
          <BaseInputGroup
            v-if="!lockProject"
            :label="t('tasks_projects.tasks.fields.project')"
            :error="errors.project_id"
            :help-text="t('tasks_projects.tasks.fields.project_help')"
          >
            <BaseSelectInput
              v-model="project"
              :options="projects"
              :placeholder="t('tasks_projects.tasks.fields.project_placeholder')"
              label-key="label"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="customerId !== null"
            :label="t('tasks_projects.tasks.fields.customer')"
            :help-text="t('tasks_projects.tasks.fields.customer_help')"
          >
            <BaseInput :model-value="customerName(customerId)" type="text" disabled />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.tasks.fields.status')"
            :error="errors.task_status_id"
          >
            <BaseSelectInput v-model="status" :options="statusOptions" label-key="label" />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.tasks.fields.assignee')"
            :error="errors.assignee_id"
          >
            <BaseSelectInput
              v-model="assignee"
              :options="memberOptions"
              :placeholder="t('tasks_projects.tasks.fields.assignee_placeholder')"
              label-key="label"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="showAllFields"
            :label="t('tasks_projects.tasks.fields.priority')"
            :error="errors.priority"
          >
            <BaseSelectInput
              v-model="priority"
              :options="priorityOptions"
              :placeholder="t('tasks_projects.tasks.fields.priority_placeholder')"
              label-key="label"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.tasks.fields.due_date')"
            :error="errors.due_date"
          >
            <BaseDatePicker :model-value="form.dueDate" @update:model-value="onDueDate" />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="showAllFields"
            :label="t('tasks_projects.tasks.fields.estimate_hours')"
            :error="errors.estimated_minutes"
          >
            <BaseInput
              v-model="form.estimateHours"
              :invalid="Boolean(errors.estimated_minutes)"
              type="number"
              step="0.25"
              min="0"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="showAllFields"
            :label="t('tasks_projects.tasks.fields.rate')"
            :error="errors.rate"
            :help-text="t('tasks_projects.tasks.fields.rate_help')"
          >
            <BaseInput
              v-model="form.rate"
              :invalid="Boolean(errors.rate)"
              type="number"
              step="0.01"
              min="0"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseInputGroup :label="t('tasks_projects.tasks.fields.billable')" :error="errors.billable">
          <BaseSwitch v-model="form.billable" class="mt-1" />
        </BaseInputGroup>

        <BaseInputGroup
          v-if="showAllFields"
          :label="t('tasks_projects.tasks.fields.description')"
          :error="errors.description"
        >
          <BaseTextarea
            v-model="form.description"
            :row="3"
            :invalid="Boolean(errors.description)"
          />
        </BaseInputGroup>

        <button
          v-if="compact"
          type="button"
          class="flex items-center gap-1 text-sm font-medium text-primary-500 hover:underline"
          @click="expanded = !expanded"
        >
          <BaseIcon :name="expanded ? 'ChevronUpIcon' : 'ChevronDownIcon'" class="h-4 w-4" />
          {{
            expanded
              ? t('tasks_projects.tasks.fewer_fields')
              : t('tasks_projects.tasks.all_fields')
          }}
        </button>
      </div>

      <div class="flex items-center justify-between border-t border-line-default px-6 py-4">
        <BaseButton
          v-if="isEdit"
          type="button"
          variant="danger"
          :loading="removing"
          :disabled="removing"
          @click="remove"
        >
          {{ t('tasks_projects.general.delete') }}
        </BaseButton>
        <span v-else />

        <div class="flex space-x-3">
          <BaseButton type="button" variant="primary-outline" @click="emit('close')">
            {{ t('tasks_projects.general.cancel') }}
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving" :disabled="saving">
            {{ isEdit ? t('tasks_projects.general.update') : t('tasks_projects.general.save') }}
          </BaseButton>
        </div>
      </div>
    </form>
  </BaseModal>
</template>
