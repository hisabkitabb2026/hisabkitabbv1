import { addDays, formatLocalDate, startOfWeek } from '@/support/time'

/**
 * The ranges the reports page offers, and the dates each one covers.
 *
 * Every boundary is a local calendar date, because a report is read in the
 * viewer's own days rather than in UTC, and is handed to the API as the
 * `Y-m-d` it takes. "This week" follows the company's week-start setting, so
 * the report and the timesheet agree about where a week begins.
 */

export const RANGE_PRESETS = [
  'THIS_WEEK',
  'THIS_MONTH',
  'LAST_MONTH',
  'THIS_QUARTER',
  'THIS_YEAR',
  'CUSTOM',
] as const

export type RangePreset = (typeof RANGE_PRESETS)[number]

export interface DateRange {
  from: string
  to: string
}

const MONTHS_PER_QUARTER = 3

/**
 * The dates a preset covers, as of `today`.
 *
 * `CUSTOM` has no dates of its own: it is what the page switches to when
 * someone picks a date by hand, so it answers the current month and the caller
 * leaves the pickers alone.
 */
export function rangeFor(preset: RangePreset, weekStart: number, today: Date = new Date()): DateRange {
  const year = today.getFullYear()
  const month = today.getMonth()

  switch (preset) {
    case 'THIS_WEEK': {
      const start = startOfWeek(today, weekStart)

      return range(start, addDays(start, 6))
    }
    case 'LAST_MONTH':
      return range(new Date(year, month - 1, 1), new Date(year, month, 0))
    case 'THIS_QUARTER': {
      const first = Math.floor(month / MONTHS_PER_QUARTER) * MONTHS_PER_QUARTER

      return range(new Date(year, first, 1), new Date(year, first + MONTHS_PER_QUARTER, 0))
    }
    case 'THIS_YEAR':
      return range(new Date(year, 0, 1), new Date(year, 12, 0))
    default:
      return range(new Date(year, month, 1), new Date(year, month + 1, 0))
  }
}

/** Minutes as a percentage of a total, clamped and never dividing by zero. */
export function shareOf(minutes: number, total: number): number {
  if (total <= 0) {
    return 0
  }

  return Math.min(100, Math.max(0, Math.round((minutes / total) * 100)))
}

function range(from: Date, to: Date): DateRange {
  return { from: formatLocalDate(from), to: formatLocalDate(to) }
}
