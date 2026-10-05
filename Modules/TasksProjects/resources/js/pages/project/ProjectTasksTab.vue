<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { listMembers } from '@/api'
import { listTaskStatuses } from '@/api/board'
import TaskFilters from '@/components/TaskFilters.vue'
import TaskList from '@/components/TaskList.vue'
import { errorMessage } from '@/support/errors'
import { EMPTY_FILTERS } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { TaskStatus } from '@/types/task-status'

const props = defineProps<{
  /** The route param, which arrives as a string. */
  id: string
  client: AxiosInstance
  notify: Notify
  /** The host router, handed down so a row can invoice and navigate. */
  router: Router
  project: Project | null
}>()

const emit = defineEmits<{ (event: 'refresh'): void }>()

const t = useTranslate()

const listRef = ref<{ openCreate: () => void } | null>(null)
const statuses = ref<TaskStatus[]>([])
const members = ref<CompanyMember[]>([])

/**
 * The tab's own filters, kept out of the address bar.
 *
 * The Tasks screen puts its filters in the URL because a view switch has to
 * carry them; a project tab has nowhere to switch to, and writing them into
 * the project's URL would make every shared project link carry somebody
 * else's search.
 */
const filters = ref<TaskFilterState>({ ...EMPTY_FILTERS })

const projectId = computed<number>(() => props.project?.id ?? Number(props.id))

/** The list is fixed to this project, so the picker offers only it. */
const projects = computed<Project[]>(() => (props.project === null ? [] : [props.project]))

onMounted(() => void loadPickers())

async function loadPickers(): Promise<void> {
  try {
    statuses.value = await listTaskStatuses(props.client)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.task_statuses.load_failed')))
  }

  try {
    members.value = await listMembers(props.client)
  } catch {
    // The assignee column falls back to ids rather than blanking the row.
    members.value = []
  }
}

/** A new or deleted task changes the counts the overview shows. */
function onChanged(): void {
  emit('refresh')
}
</script>

<template>
  <div class="py-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <TaskFilters
        v-model="filters"
        class="flex-1"
        :projects="projects"
        :members="members"
        :statuses="statuses"
        lock-project
      />

      <BaseButton variant="primary" @click="listRef?.openCreate()">
        <template #left="slotProps">
          <BaseIcon name="PlusIcon" :class="slotProps.class" />
        </template>
        {{ t('tasks_projects.tasks.new_task') }}
      </BaseButton>
    </div>

    <TaskList
      ref="listRef"
      :client="client"
      :notify="notify"
      :router="router"
      :filters="filters"
      :statuses="statuses"
      :members="members"
      :projects="projects"
      :project-id="projectId"
      @changed="onChanged"
    />
  </div>
</template>
