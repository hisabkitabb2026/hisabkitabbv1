<script setup lang="ts">
import { ref } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import TaskList from '@/components/TaskList.vue'
import type { TaskFilterState } from '@/support/filters'
import type { Notify } from '@/support/page'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { TaskStatus } from '@/types/task-status'

/**
 * The List view of the Tasks screen.
 *
 * Everything it renders comes from the screen above: the pickers are loaded
 * once there and handed to every view, so switching between List, Board and
 * Week costs one request for the rows and none for the filters.
 */
defineProps<{
  client: AxiosInstance
  notify: Notify
  /** The host router, handed down so a row can invoice and navigate. */
  router: Router
  filters: TaskFilterState
  statuses: TaskStatus[]
  members: CompanyMember[]
  projects: Project[]
}>()

const listRef = ref<{ openCreate: () => void } | null>(null)

defineExpose({ openCreate: () => listRef.value?.openCreate() })
</script>

<template>
  <TaskList
    ref="listRef"
    :client="client"
    :notify="notify"
    :router="router"
    :filters="filters"
    :statuses="statuses"
    :members="members"
    :projects="projects"
  />
</template>
