<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { deleteTimeEntry, listTimeEntries } from '@/api/time'
import AllTimeTable from '@/components/AllTimeTable.vue'
import TimeEntryModal from '@/components/TimeEntryModal.vue'
import WeekTimesheet from '@/components/WeekTimesheet.vue'
import { refreshSession, session } from '@/stores/session'
import { bumpTaskVersion, taskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { idOf } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import { formatLocalDate } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { TaskStatus } from '@/types/task-status'
import type { TimeEntry } from '@/types/time-entry'

type TimeTab = 'MINE' | 'ALL'

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  filters: TaskFilterState
  statuses: TaskStatus[]
  members: CompanyMember[]
  projects: Project[]
}>()

/** How many rows the "can I see other members" probe asks for. */
const PROBE_LIMIT = 5

const t = useTranslate()

const tab = ref<TimeTab>('MINE')
const canSeeAll = ref(false)
const modalOpen = ref(false)
const editing = ref<TimeEntry | null>(null)
const modalDate = ref(formatLocalDate(new Date()))
const reloadToken = ref(0)

const weekStart = computed<number>(() => session.settings.week_start)

const projectId = computed<number | null>(() => idOf(props.filters.project))

/**
 * Whose week the grid shows.
 *
 * The member filter is the Tasks screen's, shared by all three views, so
 * picking a colleague here is the same gesture as narrowing the list to them.
 * With nobody picked it is the caller's own week, which is what a timesheet is
 * for.
 */
const userId = computed<number | null>(() => idOf(props.filters.user) ?? session.userId)

onMounted(() => void load())

// Stopping a timer writes an entry into whichever day it belongs to.
watch(taskVersion, () => {
  reloadToken.value += 1
})

async function load(): Promise<void> {
  if (session.userId === null) {
    await refreshSession(props.client)
  }

  canSeeAll.value = session.settings.members_see_all_time || (await seesOtherMembers())
}

/**
 * Whether the "All time" tab is worth showing.
 *
 * The API narrows the list to the caller's own rows when they may not see more,
 * so asking for a page without a `user_id` filter and finding someone else's
 * entry is the honest answer. It needs no extra endpoint and cannot lie the
 * other way: a tab is only offered when rows really do come back.
 */
async function seesOtherMembers(): Promise<boolean> {
  try {
    const response = await listTimeEntries(props.client, { limit: PROBE_LIMIT })

    return (response.data ?? []).some((entry) => entry.user_id !== session.userId)
  } catch {
    return false
  }
}

function openCreate(date?: string): void {
  editing.value = null
  modalDate.value = date ?? formatLocalDate(new Date())
  modalOpen.value = true
}

function openEdit(entry: TimeEntry): void {
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
  reloadToken.value += 1
  bumpTaskVersion()
}

function onDeleted(): void {
  modalOpen.value = false
  editing.value = null
  props.notify('success', t('tasks_projects.time.deleted'))
  reloadToken.value += 1
  bumpTaskVersion()
}

async function removeEntry(entry: TimeEntry): Promise<void> {
  if (!window.confirm(t('tasks_projects.time.delete_confirm'))) {
    return
  }

  try {
    await deleteTimeEntry(props.client, entry.id)
    props.notify('success', t('tasks_projects.time.deleted'))
    reloadToken.value += 1
    bumpTaskVersion()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.delete_failed')))
  }
}

function tabClass(value: TimeTab): string {
  return tab.value === value
    ? 'border-primary-500 text-primary-500'
    : 'border-transparent text-muted hover:border-line-strong hover:text-heading'
}
</script>

<template>
  <section>
    <nav v-if="canSeeAll" class="mt-4 flex gap-6 border-b border-line-default">
      <button
        type="button"
        class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium"
        :class="tabClass('MINE')"
        @click="tab = 'MINE'"
      >
        {{ t('tasks_projects.time.my_time') }}
      </button>

      <button
        type="button"
        class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium"
        :class="tabClass('ALL')"
        @click="tab = 'ALL'"
      >
        {{ t('tasks_projects.time.all_time') }}
      </button>
    </nav>

    <WeekTimesheet
      v-if="tab === 'MINE'"
      :client="client"
      :notify="notify"
      :user-id="userId"
      :project-id="projectId"
      :week-start="weekStart"
      :reload-token="reloadToken"
      @add="openCreate"
      @edit="openEdit"
    />

    <AllTimeTable
      v-else
      :client="client"
      :notify="notify"
      :members="members"
      :member-id="idOf(filters.user)"
      :project-id="projectId"
      :reload-token="reloadToken"
      @edit="openEdit"
      @delete="removeEntry"
    />

    <TimeEntryModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :entry="editing"
      :default-date="modalDate"
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="onDeleted"
    />
  </section>
</template>
