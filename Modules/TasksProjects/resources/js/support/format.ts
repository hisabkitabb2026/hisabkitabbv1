/**
 * Conversions between what the API stores and what a form shows.
 *
 * The API keeps money in integer minor units and durations in minutes; the
 * form asks for major units and hours, which is what people type.
 */

/** Minor units to the major-unit string a number input shows. */
export function minorToMajor(amount: number | null): string {
  return amount === null ? '' : String(amount / 100)
}

/** A typed major-unit amount back to integer minor units. */
export function majorToMinor(value: string): number | null {
  const amount = Number(value)

  return value.trim() === '' || Number.isNaN(amount) ? null : Math.round(amount * 100)
}

export function minutesToHours(minutes: number | null): string {
  return minutes === null ? '' : String(minutes / 60)
}

export function hoursToMinutes(value: string): number | null {
  const hours = Number(value)

  return value.trim() === '' || Number.isNaN(hours) ? null : Math.round(hours * 60)
}

/**
 * A `Y-m-d` date in the viewer's locale. The date is a calendar date rather
 * than an instant, so it is read and printed in UTC and never shifts a day.
 */
export function formatDate(value: string | null): string {
  if (!value) {
    return ''
  }

  const [year, month, day] = value.slice(0, 10).split('-').map(Number)

  if (!year || !month || !day) {
    return value
  }

  return new Date(Date.UTC(year, month - 1, day)).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: 'UTC',
  })
}

/** What a date picker hands back, normalised to the `Y-m-d` the API takes. */
export function toDateString(value: string | Date): string {
  if (typeof value === 'string') {
    return value.slice(0, 10)
  }

  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')

  return `${value.getFullYear()}-${month}-${day}`
}

/** Minutes as the hours and minutes a timesheet reads back, such as "2h 30m". */
export function formatMinutes(minutes: number | null): string {
  const total = Math.max(0, Math.round(minutes ?? 0))
  const hours = Math.floor(total / 60)
  const rest = total % 60

  if (hours === 0) {
    return `${rest}m`
  }

  return rest === 0 ? `${hours}h` : `${hours}h ${rest}m`
}

/** The one or two letters an avatar chip shows for a person. */
export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean)

  if (parts.length === 0) {
    return '?'
  }

  const first = parts[0].charAt(0)
  const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : ''

  return (first + last).toUpperCase()
}

/** Whether a `Y-m-d` date has already passed, compared in the viewer's day. */
export function isOverdue(value: string | null): boolean {
  if (!value) {
    return false
  }

  const today = new Date()
  const stamp = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  return value.slice(0, 10) < stamp
}
