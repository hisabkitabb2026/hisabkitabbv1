<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { fetchTaskTimeLog } from '@/api/board'
import { deleteTimeEntry } from '@/api/time'
import TimeEntryModal from '@/components/TimeEntryModal.vue'
import { bumpTaskVersion, taskVersion } from '@/stores/tasks'
import { timerStore, useNow } from '@/stores/timer'
import { errorMessage } from '@/support/errors'
import { formatDate } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import {
  formatClock,
  formatDuration,
  localDateOf,
  localTimeOf,
  secondsBetween,
} from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { Task } from '@/types/task'
import type { TaskSummary } from '@/types/task-summary'
import type { TimeEntry } from '@/types/time-entry'

const props = withDefaults(
  defineProps<{
    client: AxiosInstance
    notify: Notify
    task: Task
    members?: CompanyMember[]
  }>(),
  { members: () => [] },
)

const t = useTranslate()

/** One shared tick drives every running row. */
const now = useNow()

const entries = ref<TimeEntry[]>([])
const loading = ref(false)
const modalOpen = ref(false)
const editing = ref<TimeEntry | null>(null)

/** What the modal preselects when a row is added from here. */
const summary = computed<TaskSummary>(() => ({
  id: props.task.id,
  name: props.task.name,
  number: props.task.number,
  project_id: props.task.project_id,
  billable: props.task.billable,
}))

/**
 * Logging by hand while the clock is running would describe the same minutes
 * twice, so the button waits for the timer to stop.
 */
const addDisabled = computed<boolean>(() => timerStore.isRunningOn(props.task.id))

const feedback = computed(() => ({ notify: props.notify, t }))

/**
 * Whether a running row is the caller's own clock.
 *
 * Another member's running entry is shown here and never stopped from this
 * screen: their clock is theirs to close.
 */
function isMine(entry: TimeEntry): boolean {
  return entry.is_running && timerStore.running?.id === entry.id
}

/** Stop from the log, naming the task, through the same dialog as everywhere. */
function stop(): void {
  void timerStore.stopWithPrompt(props.client, feedback.value, { taskId: props.task.id })
}

watch(() => props.task.id, () => void load(), { immediate: true })

// A start or a stop anywhere writes an entry against this task.
watch(taskVersion, () => void load())

async function load(): Promise<void> {
  loading.value = true

  try {
    entries.value = await fetchTaskTimeLog(props.client, props.task.id)
  } catch (error: unknown) {
    entries.value = []
    props.notify('error', errorMessage(error, t('tasks_projects.tasks.time_log.load_failed')))
  } finally {
    loading.value = false
  }
}

/** Invoiced time belongs to its invoice: the row opens read-only. */
function isStamped(entry: TimeEntry): boolean {
  return entry.invoice_id !== null
}

function memberName(userId: number): string {
  return props.members.find((member) => member.id === userId)?.name ?? `#${userId}`
}

/** A running row counts up; a closed one shows the minutes it recorded. */
function durationOf(entry: TimeEntry): string {
  return entry.is_running
    ? formatClock(secondsBetween(entry.started_at, now.value))
    : formatDuration(entry.duration_minutes)
}

function openCreate(): void {
  if (addDisabled.value) {
    return
  }

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
  bumpTaskVersion()
}

function onDeleted(): void {
  modalOpen.value = false
  editing.value = null
  props.notify('success', t('tasks_projects.time.deleted'))
  bumpTaskVersion()
}

async function remove(entry: TimeEntry): Promise<void> {
  if (isStamped(entry)) {
    props.notify('warning', t('tasks_projects.tasks.time_log.stamped_delete'))

    return
  }

  if (!window.confirm(t('tasks_projects.time.delete_confirm'))) {
    return
  }

  try {
    await deleteTimeEntry(props.client, entry.id)
    props.notify('success', t('tasks_projects.time.deleted'))
    bumpTaskVersion()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.delete_failed')))
  }
}
</script>

<template>
  <section class="mt-6 rounded-xl border border-line-default bg-surface">
    <header class="flex items-center justify-between border-b border-line-light px-5 py-3">
      <h2 class="text-sm font-semibold text-heading">
        {{ t('tasks_projects.tasks.time_log.title') }}
        <BaseSpinner v-if="loading" class="ms-2 inline-block h-4 w-4 text-primary-500" />
      </h2>

      <span :title="addDisabled ? t('tasks_projects.tasks.time_log.add_disabled') : undefined">
        <BaseButton variant="primary-outline" size="sm" :disabled="addDisabled" @click="openCreate">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('tasks_projects.tasks.time_log.add_item') }}
        </BaseButton>
      </span>
    </header>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-surface-secondary text-xs tracking-wide text-muted uppercase">
          <tr>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.start_date') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.start_time') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.end_date') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.end_time') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.duration') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.description') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.billable') }}
            </th>
            <th class="px-4 py-2 text-start font-medium">
              {{ t('tasks_projects.tasks.time_log.columns.member') }}
            </th>
            <th class="px-4 py-2 text-end font-medium">
              {{ t('tasks_projects.general.actions') }}
            </th>
          </tr>
        </thead>

        <tbody>
          <!-- A click on the row edits it; the keyboard's way in is the row's Edit menu item -->
          <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
          <tr
            v-for="entry in entries"
            :key="entry.id"
            class="border-t border-line-light"
            :class="entry.is_running ? 'bg-primary-50' : 'cursor-pointer hover:bg-hover'"
            @click="openEdit(entry)"
          >
            <td class="px-4 py-2.5 whitespace-nowrap">
              {{ formatDate(localDateOf(entry.started_at)) || '-' }}
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
              {{ localTimeOf(entry.started_at) || '-' }}
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap">
              <span v-if="entry.is_running" class="text-primary-500">
                {{ t('tasks_projects.tasks.time_log.running') }}
              </span>
              <span v-else>{{ formatDate(localDateOf(entry.ended_at)) || '-' }}</span>
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
              {{ entry.is_running ? '-' : localTimeOf(entry.ended_at) || '-' }}
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
              <span :class="entry.is_running ? 'font-medium text-primary-500' : ''">
                {{ durationOf(entry) }}
              </span>
            </td>
            <td class="max-w-64 truncate px-4 py-2.5">{{ entry.description || '-' }}</td>
            <td class="px-4 py-2.5">
              <BaseIcon
                v-if="entry.billable"
                name="CheckCircleIcon"
                class="h-5 w-5 text-status-green"
              />
              <span v-else class="text-subtle">-</span>
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap">{{ memberName(entry.user_id) }}</td>
            <td class="px-4 py-2.5 text-end whitespace-nowrap" @click.stop>
              <span
                v-if="isStamped(entry)"
                class="text-xs text-muted"
                :title="t('tasks_projects.tasks.time_log.stamped_delete')"
              >
                {{ t('tasks_projects.tasks.time_log.stamped') }}
              </span>

              <BaseDropdown v-else-if="!entry.is_running" :label="t('tasks_projects.general.actions')">
                <template #activator>
                  <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-muted" />
                </template>

                <BaseDropdownItem @click="openEdit(entry)">
                  <BaseIcon
                    name="PencilIcon"
                    class="me-3 h-5 w-5 text-subtle group-hover:text-muted"
                  />
                  {{ t('tasks_projects.general.edit') }}
                </BaseDropdownItem>

                <BaseDropdownItem @click="remove(entry)">
                  <BaseIcon
                    name="TrashIcon"
                    class="me-3 h-5 w-5 text-subtle group-hover:text-muted"
                  />
                  {{ t('tasks_projects.general.delete') }}
                </BaseDropdownItem>
              </BaseDropdown>

              <BaseButton
                v-else-if="isMine(entry)"
                variant="white"
                size="sm"
                :disabled="timerStore.busy"
                @click="stop"
              >
                <template #left="slotProps">
                  <BaseIcon name="StopIcon" :class="slotProps.class" />
                </template>
                {{ t('tasks_projects.timer.stop') }}
              </BaseButton>

              <span v-else class="text-xs text-primary-500">
                {{ t('tasks_projects.tasks.time_log.running') }}
              </span>
            </td>
          </tr>

          <tr v-if="entries.length === 0 && !loading">
            <td colspan="9" class="px-4 py-8 text-center text-sm text-subtle">
              {{ t('tasks_projects.tasks.time_log.empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <TimeEntryModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :entry="editing"
      :default-task="summary"
      lock-task
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="onDeleted"
    />
  </section>
</template>
