<script setup lang="ts">
import { computed } from 'vue'
import type { AxiosInstance } from 'axios'
import { session } from '@/stores/session'
import { taskLabel, taskTime } from '@/stores/tasks'
import { timerStore, useNow } from '@/stores/timer'
import { initials } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import { formatClock, secondsBetween } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { Task, TaskRunningEntry } from '@/types/task'

const props = withDefaults(
  defineProps<{
    client: AxiosInstance
    notify: Notify
    task: Task
    /** Named so another member's running clock can say who is on it. */
    members?: CompanyMember[]
    /** `sm` on a card or a table row, `md` on the task page header. */
    size?: 'sm' | 'md'
  }>(),
  { members: () => [], size: 'sm' },
)

const t = useTranslate()

/** One shared tick drives every clock on the screen. */
const now = useNow()

const feedback = computed(() => ({ notify: props.notify, t }))

const mine = computed<boolean>(() => timerStore.isRunningOn(props.task.id))

/** The caller's clock is on some other task, so this one cannot start yet. */
const elsewhere = computed<boolean>(() => timerStore.runningTaskId !== null && !mine.value)

const elsewhereLabel = computed<string>(() => taskLabel(timerStore.runningTaskId))

/**
 * Running entries that belong to somebody else: shown, never stopped here.
 *
 * The caller's own row is matched by entry id as well as by user, because the
 * host bootstrap may not have answered yet and a null user id would otherwise
 * put the caller's own clock in the "someone else" list beside their own stop
 * button.
 */
const others = computed<TaskRunningEntry[]>(() =>
  taskTime(props.task).running.filter(
    (entry) => entry.user_id !== session.userId && entry.entry_id !== timerStore.running?.id,
  ),
)

const elapsed = computed<string>(() => formatClock(timerStore.elapsedSeconds))

const iconClass = computed<string>(() => (props.size === 'md' ? 'h-5 w-5' : 'h-4 w-4'))

const buttonClass = computed<string>(() => (props.size === 'md' ? 'p-2' : 'p-1.5'))

function memberName(userId: number): string {
  return props.members.find((member) => member.id === userId)?.name ?? `#${userId}`
}

function memberInitials(userId: number): string {
  const member = props.members.find((candidate) => candidate.id === userId)

  return member ? initials(member.name) : '?'
}

/** How long somebody else's clock has been going, in the shared tick. */
function otherElapsed(entry: TaskRunningEntry): string {
  return formatClock(secondsBetween(entry.started_at, now.value))
}

function otherTitle(entry: TaskRunningEntry): string {
  return t('tasks_projects.timer.running_by', {
    name: memberName(entry.user_id),
    time: otherElapsed(entry),
  })
}

async function start(): Promise<void> {
  const entry = await timerStore.startOnTask(props.client, props.task.id, null, feedback.value)

  if (entry !== null) {
    props.notify('success', t('tasks_projects.timer.started', { name: props.task.name }))
  }
}

/**
 * Stopping asks for the note first, naming this row's task.
 *
 * The task is named so a stale row cannot stop a clock that has since moved
 * elsewhere: the mismatch is reported before the dialog opens rather than
 * after the user has typed into it.
 */
function stop(): void {
  void timerStore.stopWithPrompt(props.client, feedback.value, { taskId: props.task.id })
}

/**
 * Close the clock wherever it is, then put it on this task.
 *
 * The same dialog runs the first half, so the time being left behind is
 * described before this task takes the clock. Backing out of it, or discarding
 * that spell, leaves this task unstarted: the answer was not "start here".
 */
async function switchHere(): Promise<void> {
  if ((await timerStore.stopWithPrompt(props.client, feedback.value)) !== null) {
    await start()
  }
}
</script>

<template>
  <div class="flex items-center gap-1.5">
    <template v-if="mine">
      <button
        type="button"
        class="rounded-md text-status-red hover:bg-hover disabled:opacity-50"
        :class="buttonClass"
        :disabled="timerStore.busy"
        :title="t('tasks_projects.timer.stop_on', { name: task.name })"
        :aria-label="t('tasks_projects.timer.stop_on', { name: task.name })"
        @click.stop="stop"
      >
        <BaseIcon name="StopIcon" :class="iconClass" />
      </button>

      <span class="font-medium tabular-nums text-primary-500" :class="size === 'md' ? 'text-base' : 'text-xs'">
        {{ elapsed }}
      </span>
    </template>

    <template v-else-if="elsewhere">
      <button
        type="button"
        class="cursor-not-allowed rounded-md text-subtle"
        :class="buttonClass"
        disabled
        :title="t('tasks_projects.timer.busy_elsewhere', { name: elsewhereLabel })"
        :aria-label="t('tasks_projects.timer.busy_elsewhere', { name: elsewhereLabel })"
      >
        <BaseIcon name="PlayIcon" :class="iconClass" />
      </button>

      <button
        type="button"
        class="rounded-md px-1.5 py-0.5 text-[11px] font-medium text-primary-500 hover:bg-hover disabled:opacity-50"
        :disabled="timerStore.busy"
        :title="t('tasks_projects.timer.stop_and_start')"
        @click.stop="switchHere"
      >
        {{ t('tasks_projects.timer.stop_and_start') }}
      </button>
    </template>

    <button
      v-else
      type="button"
      class="rounded-md text-primary-500 hover:bg-hover disabled:opacity-50"
      :class="buttonClass"
      :disabled="timerStore.busy"
      :title="t('tasks_projects.timer.start_on', { name: task.name })"
      :aria-label="t('tasks_projects.timer.start_on', { name: task.name })"
      @click.stop="start"
    >
      <BaseIcon name="PlayIcon" :class="iconClass" />
    </button>

    <span
      v-for="entry in others"
      :key="entry.entry_id"
      class="flex items-center gap-1 rounded-full bg-surface-tertiary px-1.5 py-0.5 text-[11px] text-muted"
      :title="otherTitle(entry)"
    >
      <BaseIcon name="ClockIcon" class="h-3.5 w-3.5 text-primary-500" />
      {{ memberInitials(entry.user_id) }}
    </span>
  </div>
</template>
