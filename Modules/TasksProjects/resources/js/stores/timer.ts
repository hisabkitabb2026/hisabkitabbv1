import { computed, onScopeDispose, reactive } from 'vue'
import type { ComputedRef } from 'vue'
import type { AxiosInstance } from 'axios'
import { createTask, startTask, stopTask } from '@/api/board'
import { discardTimer, fetchTimer, startTimer, stopTimer } from '@/api/time'
import { errorMessage } from '@/support/errors'
import { errorCode, isConflict } from '@/support/http'
import { formatDuration, secondsBetween } from '@/support/time'
import type { Translate } from '@/support/i18n'
import type { TimeEntry } from '@/types/time-entry'
import type {
  StartAnswer,
  StartPreset,
  StopAnswer,
  StopPrompt,
  StopTimerInput,
} from '@/types/timer'
import { bumpTaskVersion, ensureTaskNames, patchTime, rememberTask, taskLabel } from './tasks'

/**
 * The running timer, shared by the header chip, the quick-start launcher, the
 * task rows and the time log.
 *
 * A module bundle has no Pinia, so this is a plain reactive singleton. The
 * elapsed time is recomputed from `started_at` on every tick rather than
 * counted up, so a throttled background tab, a sleeping laptop and a clock
 * correction all land on the right number at the next tick.
 *
 * Nothing here throws at a caller: a failed refresh leaves the chip hidden
 * rather than breaking the header it renders in.
 */

type NotifyType = 'success' | 'error' | 'warning' | 'info'

/**
 * How a caller wants failures reported. The translator comes from the calling
 * component, because a store cannot reach the host's i18n on its own.
 */
export interface TimerFeedback {
  notify: (type: NotifyType, message: string) => void
  t: Translate
}

/** The running entry the stop dialog is asking about, and who is waiting. */
interface StopPromptState extends StopPrompt {
  resolve: (answer: StopAnswer | null) => void
}

/** What the start dialog opened with, and who is waiting for its answer. */
interface StartPromptState extends StartPreset {
  resolve: (answer: StartAnswer | null) => void
}

interface TimerState {
  running: TimeEntry | null
  /** True while a start, stop or discard is in flight, to disable the buttons. */
  busy: boolean
  /**
   * The two questions the timer asks, promise-driven like the invoicing store.
   *
   * Every stop control on the screen asks the same question, and the dialog
   * that answers is mounted once in the company layout rather than by each of
   * them, so a header chip, a task row and a time log row all reach it without
   * carrying a dialog of their own.
   */
  stopPrompt: StopPromptState | null
  startPrompt: StartPromptState | null
}

const state = reactive<TimerState>({
  running: null,
  busy: false,
  stopPrompt: null,
  startPrompt: null,
})

/**
 * One clock for every live duration on the screen.
 *
 * A board can show a dozen running rows; one interval driving one ref keeps
 * that at a single wake-up per second, and only the components that read it
 * re-render. It runs while anything is subscribed and while the caller's own
 * timer is going, and stops as soon as neither is true.
 */
const clock = reactive({ now: Date.now() })

let ticker: ReturnType<typeof setInterval> | undefined
let subscribers = 0

function tick(): void {
  clock.now = Date.now()
}

function subscribeClock(): void {
  subscribers += 1

  if (ticker === undefined) {
    tick()
    ticker = setInterval(tick, 1000)
  }
}

function unsubscribeClock(): void {
  subscribers = Math.max(0, subscribers - 1)

  if (subscribers === 0 && ticker !== undefined) {
    clearInterval(ticker)
    ticker = undefined
  }
}

/**
 * Read the shared clock for as long as this scope lives.
 *
 * Call it from `setup` in any component that renders a live duration; the
 * subscription is released when the component goes away.
 */
export function useNow(): ComputedRef<number> {
  subscribeClock()
  onScopeDispose(unsubscribeClock, true)

  return computed(() => clock.now)
}

/** Whether the store itself is holding the clock open for its own entry. */
let holdingClock = false

/** Adopt a payload as the running entry, or clear the clock when it is null. */
function adopt(entry: TimeEntry | null, client?: AxiosInstance): void {
  state.running = entry && typeof entry.id === 'number' ? entry : null

  if (state.running === null) {
    if (holdingClock) {
      holdingClock = false
      unsubscribeClock()
    }

    return
  }

  if (!holdingClock) {
    holdingClock = true
    subscribeClock()
  }

  if (client && typeof state.running.task_id === 'number') {
    void ensureTaskNames(client, [state.running.task_id])
  }
}

function report(feedback: TimerFeedback | undefined, error: unknown, key: string): void {
  feedback?.notify('error', errorMessage(error, feedback.t(key)))
}

/** Say that the caller's clock is now on this task, before the list agrees. */
function claim(entry: TimeEntry): void {
  patchTime(entry.task_id, {
    running: [{ entry_id: entry.id, user_id: entry.user_id, started_at: entry.started_at }],
  })
}

/**
 * Write down a task the start dialog only has a name for.
 *
 * The clock is started separately rather than relying on `auto_start_tasks`,
 * because the company may not have that switch on; when it does, the start
 * that follows lands on the timer the server already opened.
 */
async function createTaskToTime(
  client: AxiosInstance,
  create: { name: string; projectId: number | null },
  feedback?: TimerFeedback,
): Promise<number | null> {
  try {
    const task = await createTask(client, { name: create.name, project_id: create.projectId })

    rememberTask(task)
    bumpTaskVersion()

    return typeof task.id === 'number' ? task.id : null
  } catch (error: unknown) {
    report(feedback, error, 'tasks_projects.tasks.save_failed')

    return null
  }
}

export const timerStore = {
  /** The running entry, or null when the clock is not running. */
  get running(): TimeEntry | null {
    return state.running
  },

  /** The task the caller's clock is on, or null when it is not running. */
  get runningTaskId(): number | null {
    const taskId = state.running?.task_id

    return typeof taskId === 'number' ? taskId : null
  },

  /** Seconds since the running entry started, recomputed every second. */
  get elapsedSeconds(): number {
    return state.running === null ? 0 : secondsBetween(state.running.started_at, clock.now)
  },

  /** True while a timer request is in flight. */
  get busy(): boolean {
    return state.busy
  },

  /** The running entry the stop dialog is open on, or null when it is closed. */
  get stopPrompt(): StopPromptState | null {
    return state.stopPrompt
  },

  /** What the start dialog is open with, or null when it is closed. */
  get startPrompt(): StartPromptState | null {
    return state.startPrompt
  },

  /** Whether the caller's own clock is on this task. */
  isRunningOn(taskId: number): boolean {
    return state.running !== null && state.running.task_id === taskId
  },

  /** Read the caller's running entry from the server. */
  async refresh(client: AxiosInstance): Promise<void> {
    try {
      adopt(await fetchTimer(client), client)
    } catch {
      // The header chip stays hidden rather than reporting a background read.
      adopt(null)
    }
  },

  /**
   * Start the clock on a task.
   *
   * A 409 means another tab got there first, which is not an error the user
   * caused: it is reported and the real running entry is read back.
   */
  async start(
    client: AxiosInstance,
    taskId: number,
    description: string | null = null,
    feedback?: TimerFeedback,
  ): Promise<TimeEntry | null> {
    if (state.busy) {
      return null
    }

    state.busy = true

    try {
      const entry = await startTimer(client, { task_id: taskId, description })

      adopt(entry, client)
      claim(entry)
      bumpTaskVersion()

      return entry
    } catch (error: unknown) {
      if (isConflict(error)) {
        feedback?.notify('warning', feedback.t('tasks_projects.timer.already_running'))
        await this.refresh(client)
      } else {
        report(feedback, error, 'tasks_projects.timer.start_failed')
      }

      return null
    } finally {
      state.busy = false
    }
  },

  /**
   * Start the clock through the task's own route.
   *
   * Same effect as `start`, but the server answers `timer_already_running`
   * when the caller's clock is on a different task, which the run control
   * turns into "stop that one and start this one" rather than a dead end.
   */
  async startOnTask(
    client: AxiosInstance,
    taskId: number,
    description: string | null = null,
    feedback?: TimerFeedback,
    billable?: boolean,
  ): Promise<TimeEntry | null> {
    if (state.busy) {
      return null
    }

    state.busy = true

    try {
      const entry = await startTask(client, taskId, description, billable)

      adopt(entry, client)
      claim(entry)
      bumpTaskVersion()

      return entry
    } catch (error: unknown) {
      if (errorCode(error) === 'timer_already_running') {
        feedback?.notify('warning', feedback.t('tasks_projects.timer.already_running'))
        await this.refresh(client)
      } else {
        report(feedback, error, 'tasks_projects.timer.start_failed')
      }

      return null
    } finally {
      state.busy = false
    }
  },

  /**
   * Close the running entry and answer the completed one.
   *
   * `details` is what the stop dialog collected. What it leaves out the server
   * leaves alone, so a stop with nothing to say keeps the start's own note.
   */
  async stop(
    client: AxiosInstance,
    feedback?: TimerFeedback,
    details?: StopTimerInput,
  ): Promise<TimeEntry | null> {
    if (state.busy || state.running === null) {
      return null
    }

    const taskId = state.running.task_id

    state.busy = true

    try {
      const entry = await stopTimer(client, details)

      adopt(null)
      patchTime(taskId, { running: [] })
      bumpTaskVersion()

      return entry
    } catch (error: unknown) {
      report(feedback, error, 'tasks_projects.timer.stop_failed')
      await this.refresh(client)

      return null
    } finally {
      state.busy = false
    }
  },

  /**
   * Close the caller's entry on one named task.
   *
   * The task is named so a stale row cannot stop a timer that has since moved
   * elsewhere: the server answers `timer_mismatch` and the row reloads.
   */
  async stopOnTask(
    client: AxiosInstance,
    taskId: number,
    feedback?: TimerFeedback,
    details?: StopTimerInput,
  ): Promise<TimeEntry | null> {
    if (state.busy) {
      return null
    }

    state.busy = true

    try {
      const entry = await stopTask(client, taskId, details)

      adopt(null)
      patchTime(taskId, { running: [] })
      bumpTaskVersion()

      return entry
    } catch (error: unknown) {
      if (errorCode(error) === 'timer_mismatch') {
        feedback?.notify('warning', feedback.t('tasks_projects.timer.mismatch'))
      } else {
        report(feedback, error, 'tasks_projects.timer.stop_failed')
      }

      await this.refresh(client)

      return null
    } finally {
      state.busy = false
    }
  },

  /**
   * Ask what to do with the running timer and wait for the answer.
   *
   * A second ask cancels the first, which cannot happen while one dialog is
   * mounted but keeps the promise from being dropped if it ever did.
   */
  askStop(): Promise<StopAnswer | null> {
    this.answerStop(null)

    const entry = state.running

    if (entry === null) {
      return Promise.resolve(null)
    }

    return new Promise<StopAnswer | null>((resolve) => {
      state.stopPrompt = { entry, resolve }
    })
  },

  /** Hand the waiting stop its answer, or null when the user backed out. */
  answerStop(answer: StopAnswer | null): void {
    const prompt = state.stopPrompt

    if (prompt === null) {
      return
    }

    state.stopPrompt = null
    prompt.resolve(answer)
  },

  /** Ask which task to start on, and with what, then wait for the answer. */
  askStart(preset: StartPreset = {}): Promise<StartAnswer | null> {
    this.answerStart(null)

    return new Promise<StartAnswer | null>((resolve) => {
      state.startPrompt = { ...preset, resolve }
    })
  },

  /** Hand the waiting start its answer, or null when the user backed out. */
  answerStart(answer: StartAnswer | null): void {
    const prompt = state.startPrompt

    if (prompt === null) {
      return
    }

    state.startPrompt = null
    prompt.resolve(answer)
  },

  /**
   * The one way a timer is ever stopped: ask, then do what was asked.
   *
   * Every stop control goes through here, so the dialog, the discard and the
   * one success message are written once rather than by each caller. Cancel is
   * a real answer: the clock keeps running and nothing is written.
   *
   * `taskId` names the task the caller believes is running, so a stale row
   * cannot stop a clock that has since moved; the mismatch is reported before
   * the dialog opens rather than after the user has typed into it.
   */
  async stopWithPrompt(
    client: AxiosInstance,
    feedback?: TimerFeedback,
    opts: { taskId?: number } = {},
  ): Promise<TimeEntry | null> {
    const running = state.running

    if (running === null) {
      return null
    }

    const taskId = opts.taskId

    if (typeof taskId === 'number' && running.task_id !== taskId) {
      feedback?.notify('warning', feedback.t('tasks_projects.timer.mismatch'))
      await this.refresh(client)

      return null
    }

    // Read before the stop, because the entry is gone by the time it lands.
    const name = taskLabel(running.task_id)
    const answer = await this.askStop()

    if (answer === null) {
      return null
    }

    if (answer.action === 'discard') {
      if (await this.discard(client, feedback)) {
        feedback?.notify('success', feedback.t('tasks_projects.timer.discarded'))
      }

      return null
    }

    const details: StopTimerInput = {
      description: answer.description,
      billable: answer.billable,
    }

    const entry =
      typeof taskId === 'number'
        ? await this.stopOnTask(client, taskId, feedback, details)
        : await this.stop(client, feedback, details)

    if (entry !== null) {
      feedback?.notify(
        'success',
        feedback.t('tasks_projects.timer.stopped', {
          name,
          duration: formatDuration(entry.duration_minutes),
        }),
      )
    }

    return entry
  },

  /**
   * The one way a timer is ever started from a launcher: ask, then start.
   *
   * A task the dialog had to create is created first and timed second, so the
   * answer "start on something I have just thought of" costs one dialog rather
   * than a form and a search.
   */
  async startWithPrompt(
    client: AxiosInstance,
    feedback?: TimerFeedback,
    preset: StartPreset = {},
  ): Promise<TimeEntry | null> {
    const answer = await this.askStart(preset)

    if (answer === null) {
      return null
    }

    const taskId =
      'taskId' in answer ? answer.taskId : await createTaskToTime(client, answer.create, feedback)

    if (taskId === null) {
      return null
    }

    const entry = await this.startOnTask(
      client,
      taskId,
      answer.description,
      feedback,
      answer.billable,
    )

    if (entry !== null) {
      feedback?.notify(
        'success',
        feedback.t('tasks_projects.timer.started', { name: taskLabel(taskId) }),
      )
    }

    return entry
  },

  /** Throw the running entry away without recording any time. */
  async discard(client: AxiosInstance, feedback?: TimerFeedback): Promise<boolean> {
    if (state.busy || state.running === null) {
      return false
    }

    const taskId = state.running.task_id

    state.busy = true

    try {
      await discardTimer(client)
      adopt(null)
      patchTime(taskId, { running: [] })
      bumpTaskVersion()

      return true
    } catch (error: unknown) {
      report(feedback, error, 'tasks_projects.timer.discard_failed')
      await this.refresh(client)

      return false
    } finally {
      state.busy = false
    }
  },

  /** Forget the clock, for a company switch or a sign-out. */
  reset(): void {
    this.answerStop(null)
    this.answerStart(null)
    state.busy = false
    adopt(null)
  },
}
