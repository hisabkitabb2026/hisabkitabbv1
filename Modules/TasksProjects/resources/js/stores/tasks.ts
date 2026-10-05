import { reactive, ref } from 'vue'
import type { Ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { fetchTask } from '@/api/time'
import type { Task, TaskTime } from '@/types/task'
import type { TaskSummary } from '@/types/task-summary'

/**
 * A name for every task id the time screens display.
 *
 * `TimeEntryResource` carries `task_id` and nothing else, so a timesheet row,
 * the header chip and the entry editor would all show a bare number. The cache
 * fills in the names, once per task per company session, and a task that
 * cannot be read keeps its id as the label rather than blanking the row.
 */

const names = reactive<Record<number, string>>({})
const pending = new Set<number>()

/** How many name lookups may be in flight at once. */
const BATCH_SIZE = 5

/**
 * What a task's time block says when the server did not send one.
 *
 * Every reader goes through `taskTime()`, so a row from an older server still
 * renders: it just reports nothing logged rather than blanking the column.
 */
const EMPTY_TIME: TaskTime = {
  logged_minutes: 0,
  billable_minutes: 0,
  unbilled_minutes: 0,
  unbilled_amount: 0,
  invoiced: 'none',
  running: [],
}

/**
 * How many writes have happened, for the lists and boards to watch.
 *
 * A task written on one screen changes what another shows: starting a clock on
 * the task page changes the row on the list behind it, and a bulk status
 * change moves cards on the board. Rather than wiring every screen to every
 * other, each one watches this counter and refetches.
 */
const version = ref(0)

/**
 * Time blocks a screen has written ahead of the server's answer.
 *
 * Pressing play has to look instant, but the row the button sits in came from
 * a list request that will not be repeated for a second or two. The override
 * is merged over whatever the payload carried and is dropped when the fresh
 * answer arrives.
 */
const timePatches = reactive<Record<number, Partial<TaskTime>>>({})

/** The cached name, or a stable `#id` placeholder to render meanwhile. */
export function taskLabel(id: number | null): string {
  if (id === null) {
    return ''
  }

  return names[id] ?? `#${id}`
}

/** Remember a task the caller already holds, so no lookup is needed. */
export function rememberTask(task: TaskSummary | Task | null | undefined): void {
  if (task && typeof task.id === 'number' && typeof task.name === 'string') {
    names[task.id] = task.name
  }
}

/**
 * Make sure every id given has a name, fetching the ones that do not.
 *
 * Failures are swallowed on purpose: a missing name is cosmetic, and the
 * timesheet must render even when one task has been deleted under it.
 */
export async function ensureTaskNames(client: AxiosInstance, ids: number[]): Promise<void> {
  const wanted = [...new Set(ids)].filter(
    (id) => typeof id === 'number' && names[id] === undefined && !pending.has(id),
  )

  for (const id of wanted) {
    pending.add(id)
  }

  for (let index = 0; index < wanted.length; index += BATCH_SIZE) {
    await Promise.all(
      wanted.slice(index, index + BATCH_SIZE).map(async (id) => {
        try {
          rememberTask(await fetchTask(client, id))
        } catch {
          // A task that cannot be read keeps its id as its label.
        } finally {
          pending.delete(id)
        }
      }),
    )
  }
}

/** The counter every list and board watches to know a refetch is due. */
export const taskVersion: Ref<number> = version

/** Say that a task was written, so every open list and board reloads. */
export function bumpTaskVersion(): void {
  version.value += 1
}

/** Show a task's time as something else until the server confirms it. */
export function patchTime(taskId: number, partial: Partial<TaskTime>): void {
  timePatches[taskId] = { ...(timePatches[taskId] ?? {}), ...partial }
}

/** Forget one optimistic patch, because a fresh payload has replaced it. */
export function clearTimePatch(taskId: number): void {
  delete timePatches[taskId]
}

/**
 * A task's time summary: what the payload carried, under what a screen has
 * written optimistically, over the empty summary an older server implies.
 */
export function taskTime(task: Pick<Task, 'id' | 'time'> | null | undefined): TaskTime {
  if (!task || typeof task.id !== 'number') {
    return EMPTY_TIME
  }

  const sent = task.time ?? EMPTY_TIME

  return {
    ...EMPTY_TIME,
    ...sent,
    running: Array.isArray(sent.running) ? sent.running : [],
    ...(timePatches[task.id] ?? {}),
  }
}

/** Drop everything: task ids belong to one company. */
export function resetTaskNames(): void {
  for (const key of Object.keys(names)) {
    delete names[Number(key)]
  }

  for (const key of Object.keys(timePatches)) {
    delete timePatches[Number(key)]
  }

  pending.clear()
}
