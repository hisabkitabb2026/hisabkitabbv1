<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import {
  createTimeEntry,
  deleteTimeEntry,
  fetchTask,
  searchTasks,
  updateTimeEntry,
} from '@/api/time'
import { rememberTask } from '@/stores/tasks'
import { errorMessage, fieldErrors } from '@/support/errors'
import { useTranslate } from '@/support/i18n'
import {
  addMinutes,
  formatDuration,
  formatLocalDate,
  localDateOf,
  localInstant,
  localTimeOf,
  parseDuration,
} from '@/support/time'
import { toDateString } from '@/support/format'
import type { TaskSummary } from '@/types/task-summary'
import type { TimeEntry, TimeEntryInput } from '@/types/time-entry'

type NotifyType = 'success' | 'error' | 'warning' | 'info'
type EntryMode = 'duration' | 'range'

const props = defineProps<{
  show: boolean
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  /** The entry being edited, or null to log a new one. */
  entry: TimeEntry | null
  /** The day a new entry lands on, as `Y-m-d`. */
  defaultDate?: string
  /** The task a new entry is logged against, when the caller already knows it. */
  defaultTask?: TaskSummary | null
  /**
   * Opened from a task's own time log, where the task is not a choice.
   *
   * The picker becomes a label: moving an entry to another task from inside
   * that task's log is a way to lose it, and the log it would move to is one
   * click away.
   */
  lockTask?: boolean
}>()

const emit = defineEmits<{
  (event: 'close'): void
  (event: 'saved', entry: TimeEntry): void
  (event: 'deleted', entry: TimeEntry): void
}>()

/** The hour a duration-only entry is stamped with, so it never slides a day. */
const DEFAULT_START_TIME = '09:00'

/** The two ways to describe the same span of work. */
const MODES: readonly EntryMode[] = ['duration', 'range']

const t = useTranslate()

const form = reactive({
  date: '',
  mode: 'duration' as EntryMode,
  duration: '',
  start: DEFAULT_START_TIME,
  end: '',
  description: '',
  billable: true,
})

const task = ref<TaskSummary | null>(null)
const errors = ref<Record<string, string>>({})
const saving = ref(false)
const deleting = ref(false)

const isEdit = computed<boolean>(() => props.entry !== null)

/** Invoiced time belongs to the invoice: the form only ever shows it. */
const isStamped = computed<boolean>(() => props.entry?.invoice_id != null)

const title = computed<string>(() => {
  if (isStamped.value) {
    return t('tasks_projects.time.view_entry')
  }

  return isEdit.value ? t('tasks_projects.time.edit_entry') : t('tasks_projects.time.new_entry')
})

watch(
  () => props.show,
  (show) => {
    if (show) {
      reset()
    }
  },
  { immediate: true },
)

function reset(): void {
  const entry = props.entry

  errors.value = {}
  task.value = entry === null ? (props.defaultTask ?? null) : null
  form.date = entry ? localDateOf(entry.started_at) : (props.defaultDate ?? formatLocalDate(new Date()))
  form.duration = entry ? formatDuration(entry.duration_minutes) : ''
  form.start = entry?.started_at ? localTimeOf(entry.started_at) : DEFAULT_START_TIME
  form.end = entry?.ended_at ? localTimeOf(entry.ended_at) : ''
  form.description = entry?.description ?? ''
  form.billable = entry ? entry.billable : (task.value?.billable ?? true)
  form.mode = entry !== null && matchesRange(entry) ? 'range' : 'duration'

  if (form.date === '') {
    form.date = props.defaultDate ?? formatLocalDate(new Date())
  }

  if (entry !== null) {
    void loadTask(entry.task_id)
  }
}

/**
 * Whether the stored timestamps still describe the stored duration. A rounded
 * or hand-edited entry no longer does, and is shown as a plain duration.
 */
function matchesRange(entry: TimeEntry): boolean {
  if (!entry.started_at || !entry.ended_at) {
    return false
  }

  const started = new Date(entry.started_at).getTime()
  const ended = new Date(entry.ended_at).getTime()

  if (Number.isNaN(started) || Number.isNaN(ended)) {
    return false
  }

  return Math.round((ended - started) / 60000) === entry.duration_minutes
}

async function loadTask(id: number): Promise<void> {
  try {
    const loaded = await fetchTask(props.client, id)

    task.value = loaded
    rememberTask(loaded)
  } catch {
    // The picker stays empty; saving still needs a task to be chosen.
  }
}

/** The picker's option source: the server filters, so the list does not. */
async function loadTasks(query: string): Promise<TaskSummary[]> {
  try {
    const tasks = await searchTasks(props.client, query ?? '')

    tasks.forEach(rememberTask)

    return tasks
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.tasks_failed')))

    return []
  }
}

function onDate(value: string | Date): void {
  form.date = value ? toDateString(value) : ''
}

function onTaskSelected(selected: TaskSummary | null): void {
  task.value = selected

  if (selected !== null && props.entry === null) {
    form.billable = selected.billable !== false
  }
}

/** The request body, or null when the form is not ready to be sent. */
function payload(): TimeEntryInput | null {
  const messages: Record<string, string> = {}
  const chosen = task.value

  if (chosen === null || typeof chosen.id !== 'number') {
    messages.task_id = t('tasks_projects.time.task_required')
  }

  if (form.date === '') {
    messages.date = t('tasks_projects.time.date_required')
  }

  const startedAt = localInstant(form.date, form.mode === 'range' ? form.start : DEFAULT_START_TIME)

  if (startedAt === null) {
    messages.started_at = t('tasks_projects.time.range_invalid')
  }

  const minutes = form.mode === 'duration' ? parseDuration(form.duration) : null

  if (form.mode === 'duration' && minutes === null) {
    messages.duration_minutes = t('tasks_projects.time.duration_invalid')
  }

  const endedAt = form.mode === 'range' ? localInstant(form.date, form.end) : null

  if (form.mode === 'range' && (endedAt === null || startedAt === null || endedAt <= startedAt)) {
    messages.ended_at = t('tasks_projects.time.range_invalid')
  }

  errors.value = messages

  if (Object.keys(messages).length > 0 || chosen === null || startedAt === null) {
    return null
  }

  const body: TimeEntryInput = {
    task_id: chosen.id,
    started_at: startedAt,
    description: form.description.trim() || null,
    billable: form.billable,
  }

  if (form.mode === 'duration' && minutes !== null) {
    body.duration_minutes = minutes
    body.ended_at = addMinutes(startedAt, minutes)
  } else {
    body.ended_at = endedAt
  }

  return body
}

async function save(): Promise<void> {
  if (saving.value || isStamped.value) {
    return
  }

  const body = payload()

  if (body === null) {
    return
  }

  saving.value = true

  try {
    const existing = props.entry
    const saved = existing
      ? await updateTimeEntry(props.client, existing.id, body)
      : await createTimeEntry(props.client, body)

    emit('saved', saved)
  } catch (error: unknown) {
    errors.value = fieldErrors(error)
    props.notify('error', errorMessage(error, t('tasks_projects.time.save_failed')))
  } finally {
    saving.value = false
  }
}

async function remove(): Promise<void> {
  const existing = props.entry

  if (existing === null || deleting.value || isStamped.value) {
    return
  }

  if (!window.confirm(t('tasks_projects.time.delete_confirm'))) {
    return
  }

  deleting.value = true

  try {
    await deleteTimeEntry(props.client, existing.id)
    emit('deleted', existing)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.delete_failed')))
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <BaseModal :show="show" @close="emit('close')">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>{{ title }}</span>
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
        <p
          v-if="isStamped"
          class="rounded-md bg-alert-warning-bg px-3 py-2 text-sm text-alert-warning-text"
        >
          {{ t('tasks_projects.time.stamped_notice') }}
        </p>

        <BaseInputGroup
          :label="t('tasks_projects.time.fields.task')"
          :error="errors.task_id"
          required
        >
          <BaseInput v-if="lockTask" :model-value="task?.name ?? ''" type="text" disabled />

          <BaseMultiselect
            v-else
            :model-value="task"
            :options="loadTasks"
            :disabled="isStamped"
            :invalid="Boolean(errors.task_id)"
            :placeholder="t('tasks_projects.time.fields.task_placeholder')"
            :initial-search="task?.name ?? ''"
            :delay="400"
            :filter-results="false"
            value-prop="id"
            track-by="name"
            label="name"
            object
            searchable
            preserve-search
            resolve-on-load
            @update:model-value="(value: TaskSummary | null) => onTaskSelected(value)"
          />
        </BaseInputGroup>

        <BaseInputGrid>
          <BaseInputGroup :label="t('tasks_projects.time.fields.date')" :error="errors.date" required>
            <BaseDatePicker
              :model-value="form.date"
              :disabled="isStamped"
              :invalid="Boolean(errors.date)"
              @update:model-value="onDate"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('tasks_projects.time.fields.mode')">
            <div class="inline-flex overflow-hidden rounded-md border border-line-default">
              <button
                v-for="option in MODES"
                :key="option"
                type="button"
                class="px-3 py-2 text-sm"
                :class="
                  form.mode === option
                    ? 'bg-primary-500 text-white'
                    : 'bg-surface text-body hover:bg-hover'
                "
                :disabled="isStamped"
                @click="form.mode = option"
              >
                {{ t(`tasks_projects.time.mode.${option}`) }}
              </button>
            </div>
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseInputGroup
          v-if="form.mode === 'duration'"
          :label="t('tasks_projects.time.fields.duration')"
          :error="errors.duration_minutes"
          :help-text="t('tasks_projects.time.fields.duration_help')"
          required
        >
          <BaseInput
            v-model="form.duration"
            type="text"
            inputmode="text"
            placeholder="1:30"
            :disabled="isStamped"
            :invalid="Boolean(errors.duration_minutes)"
          />
        </BaseInputGroup>

        <BaseInputGrid v-else>
          <BaseInputGroup
            :label="t('tasks_projects.time.fields.start')"
            :error="errors.started_at"
            required
          >
            <BaseInput
              v-model="form.start"
              type="time"
              :disabled="isStamped"
              :invalid="Boolean(errors.started_at)"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.time.fields.end')"
            :error="errors.ended_at"
            required
          >
            <BaseInput
              v-model="form.end"
              type="time"
              :disabled="isStamped"
              :invalid="Boolean(errors.ended_at)"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseInputGroup
          :label="t('tasks_projects.time.fields.description')"
          :error="errors.description"
        >
          <BaseTextarea
            v-model="form.description"
            :row="3"
            :disabled="isStamped"
            :invalid="Boolean(errors.description)"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.time.fields.billable')" :error="errors.billable">
          <BaseSwitch v-if="!isStamped" v-model="form.billable" class="flex" />
          <span v-else class="text-sm text-muted">
            {{
              form.billable
                ? t('tasks_projects.time.billable')
                : t('tasks_projects.time.non_billable')
            }}
          </span>
        </BaseInputGroup>
      </div>

      <div class="flex items-center justify-between border-t border-line-default px-6 py-4">
        <BaseButton
          v-if="isEdit && !isStamped"
          type="button"
          variant="danger"
          size="sm"
          :loading="deleting"
          :disabled="deleting"
          @click="remove"
        >
          {{ t('tasks_projects.general.delete') }}
        </BaseButton>
        <span v-else />

        <div class="flex space-x-3">
          <BaseButton type="button" variant="primary-outline" @click="emit('close')">
            {{ isStamped ? t('tasks_projects.timer.close') : t('tasks_projects.general.cancel') }}
          </BaseButton>

          <BaseButton
            v-if="!isStamped"
            type="submit"
            variant="primary"
            :loading="saving"
            :disabled="saving"
          >
            {{ isEdit ? t('tasks_projects.general.update') : t('tasks_projects.general.save') }}
          </BaseButton>
        </div>
      </div>
    </form>
  </BaseModal>
</template>
