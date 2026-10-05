import { reactive } from 'vue'
import type { AxiosInstance } from 'axios'
import { fetchCurrentUserId, fetchTimeSettings } from '@/api/time'
import type { ModuleSettings, RoundingDirection } from '@/types/settings'

/**
 * What the time screens need to know about the current session.
 *
 * A module bundle runs on the host's Vue instance but not on its Pinia, so it
 * cannot read the user or company stores. The two facts the timesheet cannot
 * work without, the signed-in user's id and the company's module settings, are
 * fetched once per company and kept here, where the page, the overlay and the
 * settings editor all reach them.
 *
 * Everything degrades rather than throws: a member who may not read the module
 * settings still gets the defaults, and a missing user id only means the "My
 * time" view asks the caller to reload.
 */

export const DEFAULT_SETTINGS: ModuleSettings = {
  default_rate: 0,
  rounding_minutes: 1,
  rounding_direction: 'nearest',
  week_start: 1,
  members_see_all_time: false,
  auto_start_tasks: false,
  lock_invoiced_tasks: false,
  hide_invoiced_on_board: false,
  invoice_project_heading: false,
  invoice_task_description: true,
  invoice_entry_dates: true,
  invoice_entry_times: false,
  invoice_entry_hours: true,
  invoice_entry_descriptions: false,
  rounding_increments: [1, 5, 6, 15, 30, 60],
}

interface SessionState {
  /** True while the shell is in platform administration, where no company is active. */
  adminMode: boolean
  userId: number | null
  settings: ModuleSettings
  /** Bumped on every company change so views can key off it and start clean. */
  companySession: number
  loading: boolean
}

export const session = reactive<SessionState>({
  adminMode: false,
  userId: null,
  settings: { ...DEFAULT_SETTINGS },
  companySession: 0,
  loading: false,
})

/** Read the user id and the company settings for the active company. */
export async function refreshSession(client: AxiosInstance): Promise<void> {
  if (session.adminMode) {
    return
  }

  session.loading = true

  const [userId, settings] = await Promise.all([
    fetchCurrentUserId(client).catch((): null => null),
    fetchTimeSettings(client).catch((): null => null),
  ])

  session.userId = userId
  session.settings = normaliseSettings(settings)
  session.loading = false
}

/** Forget the previous company's answers before the next one loads. */
export function resetSession(): void {
  session.userId = null
  session.settings = { ...DEFAULT_SETTINGS }
  session.companySession += 1
  session.loading = false
}

export function setAdminMode(adminMode: boolean): void {
  session.adminMode = adminMode
}

/**
 * Settings as the module promises them, whatever the endpoint answered.
 *
 * The payload is host data crossing a module boundary, so every field is
 * checked rather than trusted: a missing settings endpoint, an older module
 * version or a 403 all end up as the documented defaults.
 */
function normaliseSettings(settings: ModuleSettings | null): ModuleSettings {
  if (settings === null || typeof settings !== 'object') {
    return { ...DEFAULT_SETTINGS }
  }

  const increments = Array.isArray(settings.rounding_increments)
    ? settings.rounding_increments.filter((value): value is number => typeof value === 'number')
    : DEFAULT_SETTINGS.rounding_increments

  return {
    default_rate: numberOr(settings.default_rate, DEFAULT_SETTINGS.default_rate),
    rounding_minutes: numberOr(settings.rounding_minutes, DEFAULT_SETTINGS.rounding_minutes),
    rounding_direction: directionOr(settings.rounding_direction),
    week_start: weekStartOr(settings.week_start),
    members_see_all_time: settings.members_see_all_time === true,
    auto_start_tasks: flagOr(settings.auto_start_tasks, DEFAULT_SETTINGS.auto_start_tasks),
    lock_invoiced_tasks: flagOr(settings.lock_invoiced_tasks, DEFAULT_SETTINGS.lock_invoiced_tasks),
    hide_invoiced_on_board: flagOr(
      settings.hide_invoiced_on_board,
      DEFAULT_SETTINGS.hide_invoiced_on_board,
    ),
    invoice_project_heading: flagOr(
      settings.invoice_project_heading,
      DEFAULT_SETTINGS.invoice_project_heading,
    ),
    invoice_task_description: flagOr(
      settings.invoice_task_description,
      DEFAULT_SETTINGS.invoice_task_description,
    ),
    invoice_entry_dates: flagOr(settings.invoice_entry_dates, DEFAULT_SETTINGS.invoice_entry_dates),
    invoice_entry_times: flagOr(settings.invoice_entry_times, DEFAULT_SETTINGS.invoice_entry_times),
    invoice_entry_hours: flagOr(settings.invoice_entry_hours, DEFAULT_SETTINGS.invoice_entry_hours),
    invoice_entry_descriptions: flagOr(
      settings.invoice_entry_descriptions,
      DEFAULT_SETTINGS.invoice_entry_descriptions,
    ),
    rounding_increments: increments.length > 0 ? increments : DEFAULT_SETTINGS.rounding_increments,
  }
}

function numberOr(value: unknown, fallback: number): number {
  return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

/**
 * A toggle the server sent, or the documented default.
 *
 * An older server omits these keys entirely, which is not the same answer as
 * "off": a missing `invoice_task_description` still means the description is
 * written, because that is what the module promises when nobody has chosen.
 */
function flagOr(value: unknown, fallback: boolean): boolean {
  return typeof value === 'boolean' ? value : fallback
}

function directionOr(value: unknown): RoundingDirection {
  return value === 'up' || value === 'down' || value === 'nearest'
    ? value
    : DEFAULT_SETTINGS.rounding_direction
}

function weekStartOr(value: unknown): number {
  return typeof value === 'number' && Number.isInteger(value) && value >= 0 && value <= 6
    ? value
    : DEFAULT_SETTINGS.week_start
}
