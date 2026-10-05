<script setup lang="ts">
import EmptyArt from '@/components/EmptyArt.vue'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import Sortable from 'sortablejs'
import type { SortableEvent } from 'sortablejs'
import { fetchBoard, moveTask } from '@/api/board'
import TaskCard from '@/components/TaskCard.vue'
import TaskFormModal from '@/components/TaskFormModal.vue'
import type { TaskDefaults } from '@/components/TaskFormModal.vue'
import { ensureLoaded } from '@/stores/customers'
import { session } from '@/stores/session'
import { bumpTaskVersion, taskTime, taskVersion } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { idOf, isInvoicedFilter } from '@/support/filters'
import type { TaskFilterState } from '@/support/filters'
import { useTranslate } from '@/support/i18n'
import type { Notify } from '@/support/page'
import type { BoardColumn, BoardParams, SelectOption } from '@/types/board'
import type { CompanyMember } from '@/types/member'
import type { Project } from '@/types/project'
import type { Task } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  filters: TaskFilterState
  statuses: TaskStatus[]
  members: CompanyMember[]
  projects: Project[]
}>()

const t = useTranslate()

const columns = ref<BoardColumn[]>([])
const loading = ref(true)
const modalOpen = ref(false)
const editing = ref<Task | null>(null)
const defaults = ref<TaskDefaults>({})

/**
 * One Sortable per column, keyed by status id.
 *
 * The column element is keyed by the same id in the template, so an instance
 * outlives every board refresh and is rebuilt only when its element really is
 * a new one.
 */
const sortables = new Map<number, Sortable>()
const columnElements = new Map<number, HTMLElement>()

/**
 * A drag ends with a click on the card that was dragged, which would open the
 * form on top of the move. The flag is cleared a tick after the drop, once
 * that click has been and gone.
 */
let dragging = false

const projectOptions = computed<SelectOption[]>(() =>
  props.projects.map((project) => ({ id: project.id, label: project.name })),
)

const isEmpty = computed(() => !loading.value && columns.value.length === 0)

/**
 * Whether anything was left out of the board on purpose.
 *
 * Hiding invoiced work is a setting rather than a filter, so the board says so
 * once instead of leaving people to wonder where a card went.
 */
const hidesInvoiced = computed<boolean>(() => session.settings.hide_invoiced_on_board)

/** The cards of one column, after the two client-side rules the board applies. */
function cardsOf(column: BoardColumn): Task[] {
  return (column.tasks ?? []).filter((task) => {
    const state = taskTime(task).invoiced

    if (hidesInvoiced.value && state === 'invoiced') {
      return false
    }

    if (isInvoicedFilter(props.filters.status)) {
      return props.filters.status === 'invoiced' ? state === 'invoiced' : state !== 'invoiced'
    }

    return true
  })
}

// Only the two filters the endpoint takes; the rest are applied to the cards.
watch(
  () => `${props.filters.project}|${props.filters.user}`,
  () => void load(),
  { immediate: true },
)

watch(taskVersion, () => void load())

onBeforeUnmount(() => {
  for (const sortable of sortables.values()) {
    sortable.destroy()
  }

  sortables.clear()
  columnElements.clear()
})

async function load(): Promise<void> {
  const params: BoardParams = {}
  const projectId = idOf(props.filters.project)
  const assigneeId = idOf(props.filters.user)

  if (projectId !== null) {
    params.project_id = projectId
  }

  if (assigneeId !== null) {
    params.assignee_id = assigneeId
  }

  loading.value = true

  try {
    columns.value = await fetchBoard(props.client, params)

    // Only a board that shows a contact is worth one lookup of the address book.
    if (columns.value.some((column) => column.tasks.some((task) => task.customer_id !== null))) {
      void ensureLoaded(props.client)
    }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.board.load_failed')))
  } finally {
    loading.value = false
  }
}

/**
 * Attach a Sortable to a column list, and let go of the one it replaces.
 *
 * Vue calls this on every render of the v-for, so the element is compared
 * before anything is torn down.
 */
function setColumnRef(statusId: number, element: unknown): void {
  const list = element instanceof HTMLElement ? element : null

  if (columnElements.get(statusId) === list) {
    return
  }

  sortables.get(statusId)?.destroy()
  sortables.delete(statusId)
  columnElements.delete(statusId)

  if (list === null) {
    return
  }

  columnElements.set(statusId, list)
  sortables.set(
    statusId,
    Sortable.create(list, {
      group: 'tasks',
      animation: 150,
      draggable: '[data-task-id]',
      ghostClass: 'opacity-40',
      onStart: (): void => {
        dragging = true
      },
      onEnd: (event: SortableEvent): void => {
        void onDrop(event)
        setTimeout(() => {
          dragging = false
        })
      },
    }),
  )
}

/**
 * Put the dragged card back where it started.
 *
 * Sortable moves the node itself, which would leave Vue's list out of step
 * with the DOM. Undoing the move first makes the model the only writer: the
 * splices below re-render the card in its new home.
 */
function restoreDom(event: SortableEvent): void {
  const item = event.item
  const oldIndex = event.oldIndex ?? 0

  item.parentNode?.removeChild(item)
  event.from.insertBefore(item, event.from.children[oldIndex] ?? null)
}

function columnFor(statusId: number): BoardColumn | undefined {
  return columns.value.find((column) => column.status.id === statusId)
}

async function onDrop(event: SortableEvent): Promise<void> {
  const fromId = Number((event.from as HTMLElement).dataset.statusId)
  const toId = Number((event.to as HTMLElement).dataset.statusId)
  const oldIndex = event.oldIndex ?? 0
  const newIndex = event.newIndex ?? 0

  restoreDom(event)

  if (Number.isNaN(fromId) || Number.isNaN(toId)) {
    return
  }

  if (fromId === toId && oldIndex === newIndex) {
    return
  }

  const from = columnFor(fromId)
  const to = columnFor(toId)

  if (!from || !to) {
    return
  }

  // The drag reports positions in the rendered list, which leaves out the
  // cards a setting or a filter is hiding, so every index is translated back
  // to the column's own array before anything is moved.
  const task = cardsOf(from)[oldIndex]
  const fromIndex = task ? from.tasks.findIndex((record) => record.id === task.id) : -1

  if (!task || fromIndex === -1) {
    return
  }

  const previous = { from: [...from.tasks], to: [...to.tasks] }

  from.tasks.splice(fromIndex, 1)

  const visible = cardsOf(to)
  const before = visible[newIndex - 1] ?? null
  const after = visible[newIndex] ?? null
  const target = before === null ? 0 : to.tasks.findIndex((record) => record.id === before.id) + 1

  to.tasks.splice(target, 0, task)

  try {
    const moved = await moveTask(props.client, task.id, {
      task_status_id: toId,
      before_id: before?.id ?? null,
      after_id: after?.id ?? null,
    })

    Object.assign(task, moved)

    if (fromId !== toId) {
      props.notify(
        'success',
        t('tasks_projects.board.moved', { name: task.name, status: to.status.name }),
      )
    }
  } catch (error: unknown) {
    from.tasks = previous.from
    to.tasks = previous.to
    props.notify('error', errorMessage(error, t('tasks_projects.board.move_failed')))
  }
}

/** A new card, in the column its button sits in or in the default one. */
function openCreate(status: TaskStatus | null): void {
  const chosen = status ?? props.statuses.find((record) => record.is_default) ?? props.statuses[0]

  editing.value = null
  defaults.value = {
    task_status_id: chosen?.id ?? null,
    project_id: idOf(props.filters.project),
  }
  modalOpen.value = true
}

function openEdit(task: Task): void {
  if (dragging) {
    return
  }

  editing.value = task
  defaults.value = {}
  modalOpen.value = true
}

function onSaved(task: Task): void {
  const message = editing.value
    ? t('tasks_projects.tasks.updated', { name: task.name })
    : t('tasks_projects.tasks.created', { name: task.name })

  modalOpen.value = false
  editing.value = null
  props.notify('success', message)
  bumpTaskVersion()
}

function onDeleted(task: Task): void {
  modalOpen.value = false
  editing.value = null
  props.notify('success', t('tasks_projects.tasks.deleted', { name: task.name }))
  bumpTaskVersion()
}

defineExpose({ openCreate: () => openCreate(null) })
</script>

<template>
  <section class="mt-4">
    <p v-if="hidesInvoiced" class="mb-3 text-xs text-subtle">
      {{ t('tasks_projects.board.hidden_invoiced') }}
    </p>

    <div v-if="loading && columns.length === 0" class="flex justify-center py-16">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
    </div>

    <BaseEmptyPlaceholder
      v-else-if="isEmpty"
      :title="t('tasks_projects.task_statuses.none')"
      :description="t('tasks_projects.tasks.empty_description')"
    >
      <EmptyArt name="board" />
    </BaseEmptyPlaceholder>

    <!-- A board with many statuses scrolls sideways, but the four a company
         starts with have to fit the page: a column sliced by the right edge
         reads as a broken screen rather than as something to scroll. -->
    <div v-else class="flex items-start gap-4 overflow-x-auto pb-4">
      <section
        v-for="column in columns"
        :key="column.status.id"
        class="w-64 shrink-0 rounded-xl border border-line-default bg-surface-secondary"
      >
        <header class="flex items-center justify-between border-b border-line-light px-3 py-2.5">
          <div class="flex items-center">
            <span
              class="me-2 inline-block h-2.5 w-2.5 shrink-0 rounded-full"
              :class="column.status.colour ? '' : 'bg-line-default'"
              :style="column.status.colour ? { backgroundColor: column.status.colour } : undefined"
            />
            <h3 class="text-sm font-semibold text-heading">{{ column.status.name }}</h3>
            <span class="ms-2 text-xs text-muted">{{ cardsOf(column).length }}</span>
          </div>

          <button
            type="button"
            class="rounded-md p-1 text-subtle hover:bg-hover hover:text-body"
            :aria-label="t('tasks_projects.tasks.new_task')"
            :title="t('tasks_projects.tasks.new_task')"
            @click="openCreate(column.status)"
          >
            <BaseIcon name="PlusIcon" class="h-4 w-4" />
          </button>
        </header>

        <div
          :ref="(element) => setColumnRef(column.status.id, element)"
          :data-status-id="column.status.id"
          class="min-h-20 space-y-2 px-3 pt-3"
        >
          <TaskCard
            v-for="task in cardsOf(column)"
            :key="task.id"
            :data-task-id="task.id"
            :client="client"
            :notify="notify"
            :task="task"
            :projects="projects"
            :members="members"
            @open="openEdit"
          />
        </div>

        <p v-if="cardsOf(column).length === 0" class="px-3 pt-2 text-xs text-subtle">
          {{ t('tasks_projects.board.empty_column') }}
        </p>

        <div class="px-3 pt-2 pb-3">
          <button
            type="button"
            class="w-full rounded-md border border-dashed border-line-default py-1.5 text-xs text-muted hover:bg-hover hover:text-body"
            @click="openCreate(column.status)"
          >
            + {{ t('tasks_projects.tasks.new_task') }}
          </button>
        </div>
      </section>
    </div>

    <TaskFormModal
      :show="modalOpen"
      :client="client"
      :notify="notify"
      :task="editing"
      :statuses="statuses"
      :members="members"
      :projects="projectOptions"
      :defaults="defaults"
      :compact="editing === null"
      @close="modalOpen = false"
      @saved="onSaved"
      @deleted="onDeleted"
    />
  </section>
</template>
