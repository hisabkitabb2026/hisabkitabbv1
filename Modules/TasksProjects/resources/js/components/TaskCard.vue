<script setup lang="ts">
import { computed } from 'vue'
import type { AxiosInstance } from 'axios'
import InvoicedBadge from '@/components/InvoicedBadge.vue'
import TaskRunControl from '@/components/TaskRunControl.vue'
import { customerName } from '@/stores/customers'
import { taskTime } from '@/stores/tasks'
import { formatDate, initials, isOverdue } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import { formatDuration } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task, TaskPriority } from '@/types/task'

const props = withDefaults(
  defineProps<{
    client: AxiosInstance
    notify: Notify
    task: Task
    projects?: Project[]
    members?: CompanyMember[]
  }>(),
  { projects: () => [], members: () => [] },
)

const emit = defineEmits<{
  (event: 'open', task: Task): void
}>()

/** Colours a priority the way the list does, so both screens read alike. */
const PRIORITY_CLASS: Record<TaskPriority, string> = {
  LOW: 'bg-surface-tertiary text-muted',
  NORMAL: 'bg-primary-50 text-primary-500',
  HIGH: 'bg-alert-warning-bg text-alert-warning-text',
  URGENT: 'bg-alert-error-bg text-alert-error-text',
}

const t = useTranslate()

const time = computed(() => taskTime(props.task))

const project = computed<Project | undefined>(() =>
  props.projects.find((record) => record.id === props.task.project_id),
)

/** The short badge on the card: the identifier, or the name when there is none. */
const projectLabel = computed<string | null>(() => {
  if (props.task.project_id === null) {
    return null
  }

  return project.value?.identifier || project.value?.name || null
})

/**
 * What the badge says in full: the project, and the contact the work is billed
 * to when the module knows its name. The card itself stays short.
 */
const projectTooltip = computed<string>(() =>
  [project.value?.name, customerName(props.task.customer_id)].filter(Boolean).join(' · '),
)

const assignee = computed<CompanyMember | undefined>(() =>
  props.members.find((member) => member.id === props.task.assignee_id),
)

const assigneeInitials = computed<string | null>(() => {
  if (props.task.assignee_id === null) {
    return null
  }

  return assignee.value ? initials(assignee.value.name) : `#${props.task.assignee_id}`
})

const assigneeName = computed<string>(() =>
  assignee.value?.name ??
  (props.task.assignee_id === null
    ? t('tasks_projects.tasks.unassigned')
    : `#${props.task.assignee_id}`),
)

const logged = computed<string>(() => formatDuration(time.value.logged_minutes))

function priorityLabel(priority: TaskPriority): string {
  return t(`tasks_projects.tasks.priority.${priority.toLowerCase()}`)
}
</script>

<template>
  <!-- A click anywhere opens the card; the keyboard's way in is the title button -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
  <article
    class="cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover"
    @click="emit('open', task)"
  >
    <div class="flex items-start justify-between gap-2">
      <p class="text-sm font-medium text-heading">
        <button
          type="button"
          class="rounded-sm text-start focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          @click.stop="emit('open', task)"
        >
          {{ task.name }}
        </button>
      </p>
      <span
        v-if="task.priority"
        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
        :class="PRIORITY_CLASS[task.priority]"
      >
        {{ priorityLabel(task.priority) }}
      </span>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted">
      <span>#{{ task.number }}</span>
      <span
        v-if="projectLabel"
        class="rounded-sm bg-surface-tertiary px-1.5 py-0.5 text-[11px] text-body"
        :title="projectTooltip"
      >
        {{ projectLabel }}
      </span>
      <InvoicedBadge :state="time.invoiced" />
    </div>

    <div class="mt-3 flex items-center justify-between gap-2">
      <TaskRunControl
        :client="client"
        :notify="notify"
        :task="task"
        :members="members"
        @click.stop
      />

      <span v-if="time.logged_minutes > 0" class="text-xs tabular-nums text-muted">
        {{ logged }}
      </span>
    </div>

    <div class="mt-2 flex items-center justify-between">
      <span
        v-if="task.due_date"
        class="text-xs"
        :class="
          isOverdue(task.due_date) && !task.closed_at ? 'font-medium text-status-red' : 'text-muted'
        "
      >
        {{ formatDate(task.due_date) }}
      </span>
      <span v-else class="text-xs text-subtle">-</span>

      <span
        v-if="assigneeInitials"
        class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-50 text-[11px] font-semibold text-primary-500"
        :title="assigneeName"
      >
        {{ assigneeInitials }}
      </span>
    </div>
  </article>
</template>
