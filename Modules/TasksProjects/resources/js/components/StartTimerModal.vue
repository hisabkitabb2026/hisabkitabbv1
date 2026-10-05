<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { listProjects } from '@/api'
import { fetchTask, searchTasks } from '@/api/time'
import { rememberTask } from '@/stores/tasks'
import { timerStore } from '@/stores/timer'
import { errorMessage } from '@/support/errors'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type { SelectOption } from '@/types/board'
import type { TaskSummary } from '@/types/task-summary'

/**
 * Starting a timer, with room to say what it is for.
 *
 * The floating launcher used to offer a bare task search, which only worked
 * for someone who already knew the task existed. This asks the way people
 * actually think about it: the project first, then the task inside it, and if
 * the task has not been written down yet, the search box creates it.
 *
 * Mounted once in the company layout beside the stop dialog, and hidden on no
 * path: starting a clock is not a thing to be denied on a settings screen.
 */

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
}>()

const t = useTranslate()

/** The option id that means "no project", rather than a project of that id. */
const ANY_PROJECT = 0

/** The option id of the "create what I typed" row, which is not a task yet. */
const CREATE_ID = -1

/** How many projects the picker lists, matching the other project pickers. */
const PROJECT_LIMIT = 100

const TASK_LIMIT = 10

/** A task the picker can start on, or the offer to create one. */
interface TaskOption {
  id: number
  label: string
  /** The name to create with, for the create option. */
  name: string
  billable: boolean
}

const projects = ref<SelectOption[]>([])
const projectId = ref<number>(ANY_PROJECT)
const picked = ref<TaskOption | null>(null)
const description = ref('')
const billable = ref(true)

const open = computed<boolean>(() => timerStore.startPrompt !== null)

const projectOptions = computed<SelectOption[]>(() => [
  { id: ANY_PROJECT, label: t('tasks_projects.timer.any_project') },
  ...projects.value,
])

/**
 * The picker binds an option and the answer wants an id, so the id stays the
 * source of truth and the option is derived from it. That matters while the
 * project list is still loading, when the id is known and no option exists yet.
 */
const project = computed<SelectOption | null>(
  () => projectOptions.value.find((option) => option.id === projectId.value) ?? null,
)

watch(
  () => timerStore.startPrompt,
  (prompt) => {
    if (prompt === null) {
      return
    }

    projectId.value = typeof prompt.projectId === 'number' ? prompt.projectId : ANY_PROJECT
    picked.value = null
    description.value = ''
    billable.value = true

    void loadProjects()

    if (typeof prompt.taskId === 'number') {
      void preselect(prompt.taskId)
    }
  },
)

// A different project means a different list, so a pick from the old one goes.
watch(projectId, () => {
  picked.value = null
})

async function loadProjects(): Promise<void> {
  try {
    const response = await listProjects(props.client, {
      limit: PROJECT_LIMIT,
      status: 'ACTIVE',
      sort_by: 'name',
    })

    projects.value = (response.data ?? []).map((record) => ({
      id: record.id,
      label: record.name,
    }))
  } catch (error: unknown) {
    projects.value = []
    props.notify('error', errorMessage(error, t('tasks_projects.time.projects_failed')))
  }
}

/**
 * Open on a task the caller already had in mind.
 *
 * The project is set first and the task after the watchers have run, because
 * choosing a project is what clears a pick made under the previous one.
 */
async function preselect(taskId: number): Promise<void> {
  try {
    const task = await fetchTask(props.client, taskId)

    rememberTask(task)

    if (typeof task.project_id === 'number') {
      projectId.value = task.project_id
    }

    await nextTick()
    pick(optionFor(task))
  } catch {
    // The picker stays empty; starting still needs a task to be chosen.
  }
}

function optionFor(task: TaskSummary): TaskOption {
  return {
    id: task.id,
    label: typeof task.number === 'number' ? `#${task.number} ${task.name}` : task.name,
    name: task.name,
    billable: task.billable !== false,
  }
}

/**
 * The picker's option source: the server filters, so the list does not.
 *
 * A search that matches nothing is not a dead end. The typed text becomes the
 * offer to create a task of that name and start on it, which is the whole
 * reason someone opens this dialog with a name in their head and no task.
 */
async function loadTasks(query: string): Promise<TaskOption[]> {
  const text = (query ?? '').trim()

  try {
    const tasks = await searchTasks(props.client, text, {
      projectId: projectId.value === ANY_PROJECT ? null : projectId.value,
      invoiced: 0,
      limit: TASK_LIMIT,
    })

    tasks.forEach(rememberTask)

    const options = tasks.map(optionFor)

    if (options.length === 0 && text !== '') {
      options.push({
        id: CREATE_ID,
        label: t('tasks_projects.timer.create_and_start', { name: text }),
        name: text,
        billable: true,
      })
    }

    return options
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.tasks_failed')))

    return []
  }
}

function pickProject(option: SelectOption | null): void {
  projectId.value = option?.id ?? ANY_PROJECT
}

function pick(option: TaskOption | null): void {
  picked.value = option
  billable.value = option === null ? true : option.billable
}

function start(): void {
  const option = picked.value

  if (option === null) {
    return
  }

  const note = description.value.trim() || null
  const chosenProject = projectId.value === ANY_PROJECT ? null : projectId.value

  timerStore.answerStart(
    option.id === CREATE_ID
      ? { create: { name: option.name, projectId: chosenProject }, description: note, billable: billable.value }
      : { taskId: option.id, description: note, billable: billable.value },
  )
}

/** Backing out starts nothing: no task is created and no clock is opened. */
function cancel(): void {
  timerStore.answerStart(null)
}
</script>

<template>
  <BaseModal :show="open" @close="cancel">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>{{ t('tasks_projects.timer.start_title') }}</span>
        <button
          type="button"
          class="-m-1.5 rounded-lg p-1.5 text-subtle hover:text-body focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          :aria-label="t('tasks_projects.general.close')"
          @click="cancel"
        >
          <BaseIcon name="XMarkIcon" class="h-6 w-6" />
        </button>
      </div>
    </template>

    <form @submit.prevent="start">
      <div class="space-y-5 px-6 py-6">
        <BaseInputGroup :label="t('tasks_projects.time.filters.project')">
          <BaseMultiselect
            :model-value="project"
            :options="projectOptions"
            :can-clear="false"
            value-prop="id"
            track-by="label"
            label="label"
            object
            searchable
            @update:model-value="(value: SelectOption | null) => pickProject(value)"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.time.fields.task')" required>
          <BaseMultiselect
            :key="projectId"
            :model-value="picked"
            :options="loadTasks"
            :placeholder="t('tasks_projects.timer.pick_task')"
            :initial-search="picked?.label ?? ''"
            :no-results-text="t('tasks_projects.timer.no_matches')"
            :delay="400"
            :filter-results="false"
            value-prop="id"
            track-by="label"
            label="label"
            object
            searchable
            preserve-search
            resolve-on-load
            @update:model-value="(value: TaskOption | null) => pick(value)"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.time.fields.description')">
          <BaseTextarea v-model="description" :row="3" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.time.fields.billable')">
          <BaseSwitch v-model="billable" class="flex" />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end space-x-3 border-t border-line-default px-6 py-4">
        <BaseButton type="button" variant="primary-outline" @click="cancel">
          {{ t('tasks_projects.general.cancel') }}
        </BaseButton>
        <BaseButton
          type="submit"
          variant="primary"
          :disabled="picked === null || timerStore.busy"
          :loading="timerStore.busy"
        >
          <template #left="slotProps">
            <BaseIcon name="PlayIcon" :class="slotProps.class" />
          </template>
          {{ t('tasks_projects.timer.start') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>
