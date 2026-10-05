import type { TimeEntry } from './time-entry'

/**
 * The timer endpoint answers with the caller's running entry or with null, so
 * the payload is wrapped rather than a bare resource.
 */
export interface RunningTimer {
  data: TimeEntry | null
}

/** What `timer/start` accepts. `billable` overrides the task's own flag. */
export interface StartTimerInput {
  task_id: number
  description?: string | null
  billable?: boolean
}

/**
 * What a stop may carry.
 *
 * Every key is optional and an omitted one is left alone by the server, so a
 * stop with nothing to say keeps whatever the start recorded.
 */
export interface StopTimerInput {
  description?: string | null
  billable?: boolean
}

/** What the caller wanted a running timer to do, once the dialog answered. */
export type StopAnswer =
  | { action: 'save'; description: string | null; billable: boolean }
  | { action: 'discard' }

/** The running entry the stop dialog is asking about. */
export interface StopPrompt {
  entry: TimeEntry
}

/**
 * What the start dialog answered: an existing task, or a task to create first.
 *
 * Creating is part of the answer rather than a separate step, because "start
 * the clock on something I have not written down yet" is one intention and the
 * dialog should not make the user leave to satisfy it.
 */
export type StartAnswer =
  | { taskId: number; description: string | null; billable: boolean }
  | {
      create: { name: string; projectId: number | null }
      description: string | null
      billable: boolean
    }

/** What a caller already knows when it opens the start dialog. */
export interface StartPreset {
  taskId?: number
  projectId?: number
}
