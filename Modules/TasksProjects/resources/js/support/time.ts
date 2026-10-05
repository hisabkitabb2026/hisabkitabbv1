/**
 * Clocks, durations and weeks.
 *
 * The API stores instants in UTC and durations in whole minutes; the timesheet
 * talks in the viewer's own day, so every conversion here goes through the
 * browser's local time zone and never through a string comparison of two
 * differently offset timestamps.
 */

import type { RoundingDirection } from '@/types/settings'

const MINUTES_PER_HOUR = 60
const SECONDS_PER_MINUTE = 60
const DAYS_PER_WEEK = 7

/** Seconds as `h:mm:ss`, which is what a running timer shows. */
export function formatClock(seconds: number): string {
  const total = Number.isFinite(seconds) && seconds > 0 ? Math.floor(seconds) : 0
  const hours = Math.floor(total / (SECONDS_PER_MINUTE * MINUTES_PER_HOUR))
  const minutes = Math.floor((total % (SECONDS_PER_MINUTE * MINUTES_PER_HOUR)) / SECONDS_PER_MINUTE)
  const rest = total % SECONDS_PER_MINUTE

  return `${hours}:${pad(minutes)}:${pad(rest)}`
}

/** Minutes as `h:mm`, which is how a logged duration is written and typed. */
export function formatDuration(minutes: number | null): string {
  const total = minutes !== null && Number.isFinite(minutes) && minutes > 0 ? Math.round(minutes) : 0

  return `${Math.floor(total / MINUTES_PER_HOUR)}:${pad(total % MINUTES_PER_HOUR)}`
}

/**
 * A typed duration back to minutes.
 *
 * Both notations people actually use are accepted: `1:30` and the decimal
 * `1.5`. Anything else answers null so the caller can mark the field invalid
 * rather than silently logging zero.
 */
export function parseDuration(value: string): number | null {
  const text = value.trim()

  if (text === '') {
    return null
  }

  const clock = /^(\d+):([0-5]?\d)$/.exec(text)

  if (clock) {
    return Number(clock[1]) * MINUTES_PER_HOUR + Number(clock[2])
  }

  if (!/^\d+([.,]\d+)?$/.test(text)) {
    return null
  }

  const hours = Number(text.replace(',', '.'))

  return Number.isNaN(hours) ? null : Math.round(hours * MINUTES_PER_HOUR)
}

/**
 * A duration rounded the way the server will round it.
 *
 * This mirrors `Application\Rounding::roundMinutes` so the stop dialog can
 * promise what the entry is about to say. Zero stays zero whichever way the
 * company rounds; `nearest` bills a spell shorter than one increment as a
 * whole one, and `down` is the one direction that may answer zero for real
 * work. An increment that is not a positive number rounds to the minute, which
 * is the server's own fallback rather than a crash on the screen.
 */
export function roundMinutes(
  minutes: number,
  increment: number,
  direction: RoundingDirection = 'nearest',
): number {
  const step = Number.isFinite(increment) && increment >= 1 ? Math.floor(increment) : 1
  const total = Number.isFinite(minutes) ? Math.floor(minutes) : 0

  if (total <= 0) {
    return 0
  }

  if (direction === 'up') {
    return Math.ceil(total / step) * step
  }

  if (direction === 'down') {
    return Math.floor(total / step) * step
  }

  return total < step ? step : Math.round(total / step) * step
}

/** The local calendar date of an instant, as the `Y-m-d` the API takes. */
export function localDateOf(instant: string | null): string {
  const date = parseInstant(instant)

  return date === null ? '' : formatLocalDate(date)
}

/** The local wall-clock time of an instant, as the `HH:MM` an input shows. */
export function localTimeOf(instant: string | null): string {
  const date = parseInstant(instant)

  return date === null ? '' : `${pad(date.getHours())}:${pad(date.getMinutes())}`
}

/**
 * A local date and an optional `HH:MM` back to the instant the API stores.
 *
 * The pair is read as local wall-clock time, so an entry typed as "the 3rd,
 * 09:00" stays on the 3rd at 09:00 for the person who typed it whatever their
 * offset is. A duration-only entry gets a default hour rather than midnight,
 * which would land on the previous day for anyone east of UTC.
 */
export function localInstant(date: string, time = '09:00'): string | null {
  const day = parseDateString(date)
  const clock = /^(\d{1,2}):([0-5]\d)$/.exec(time.trim())

  if (day === null || clock === null) {
    return null
  }

  const hours = Number(clock[1])

  if (hours > 23) {
    return null
  }

  day.setHours(hours, Number(clock[2]), 0, 0)

  return day.toISOString()
}

/** The same instant moved by whole minutes, for the end of a typed duration. */
export function addMinutes(instant: string, minutes: number): string {
  const date = new Date(instant)

  date.setTime(date.getTime() + minutes * SECONDS_PER_MINUTE * 1000)

  return date.toISOString()
}

/**
 * The first day of the week `date` falls in.
 *
 * `weekStart` is the company setting, 0 for Sunday through 6 for Saturday; an
 * out-of-range value falls back to Monday rather than shifting the grid.
 */
export function startOfWeek(date: Date, weekStart: number): Date {
  const first = Number.isInteger(weekStart) && weekStart >= 0 && weekStart <= 6 ? weekStart : 1
  const start = startOfDay(date)
  const shift = (start.getDay() - first + DAYS_PER_WEEK) % DAYS_PER_WEEK

  start.setDate(start.getDate() - shift)

  return start
}

/** The seven days of the week beginning at `start`. */
export function weekDays(start: Date): Date[] {
  return Array.from({ length: DAYS_PER_WEEK }, (_unused, index) => addDays(start, index))
}

export function addDays(date: Date, days: number): Date {
  const shifted = startOfDay(date)

  shifted.setDate(shifted.getDate() + days)

  return shifted
}

/** A `Date` as the `Y-m-d` the API takes, in local time. */
export function formatLocalDate(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/** The weekday and day-of-month a column of the week grid is labelled with. */
export function dayLabel(date: Date): { weekday: string; day: string } {
  return {
    weekday: date.toLocaleDateString(undefined, { weekday: 'short' }),
    day: date.toLocaleDateString(undefined, { day: 'numeric', month: 'short' }),
  }
}

/** Whether a date is today, so the grid can mark the column. */
export function isToday(date: Date): boolean {
  return formatLocalDate(date) === formatLocalDate(new Date())
}

/** Seconds elapsed since an instant, never negative and never NaN. */
export function secondsSince(instant: string | null): number {
  return secondsBetween(instant, Date.now())
}

/**
 * The same, measured against a caller-supplied instant.
 *
 * Every live clock on a screen reads one shared "now", so the rows tick
 * together and one timer drives the whole page instead of one per row.
 */
export function secondsBetween(instant: string | null, now: number): number {
  const start = parseInstant(instant)

  if (start === null) {
    return 0
  }

  return Math.max(0, Math.floor((now - start.getTime()) / 1000))
}

function parseInstant(instant: string | null): Date | null {
  if (!instant) {
    return null
  }

  const date = new Date(instant)

  return Number.isNaN(date.getTime()) ? null : date
}

/** A `Y-m-d` at local midnight. Anything else answers null. */
function parseDateString(value: string): Date | null {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim())

  if (match === null) {
    return null
  }

  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 0, 0, 0, 0)

  return Number.isNaN(date.getTime()) ? null : date
}

function startOfDay(date: Date): Date {
  const copy = new Date(date.getTime())

  copy.setHours(0, 0, 0, 0)

  return copy
}

function pad(value: number): string {
  return String(value).padStart(2, '0')
}
