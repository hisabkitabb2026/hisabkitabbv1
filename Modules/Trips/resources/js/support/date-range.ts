export interface Range {
  from: string
  to: string
}

const FMT = (d: Date): string =>
  d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')

function startOfDay(d: Date): Date {
  const r = new Date(d)
  r.setHours(0, 0, 0, 0)
  return r
}

function endOfDay(d: Date): Date {
  const r = new Date(d)
  r.setHours(23, 59, 59, 999)
  return r
}

function startOfWeek(d: Date): Date {
  const r = startOfDay(d)
  const day = r.getDay() // 0=Sun..6=Sat; ISO week starts Monday
  const diff = (day + 6) % 7
  r.setDate(r.getDate() - diff)
  return r
}

function endOfWeek(d: Date): Date {
  const start = startOfWeek(d)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  return endOfDay(end)
}

function startOfMonth(d: Date): Date {
  return startOfDay(new Date(d.getFullYear(), d.getMonth(), 1))
}

function endOfMonth(d: Date): Date {
  return endOfDay(new Date(d.getFullYear(), d.getMonth() + 1, 0))
}

function startOfQuarter(d: Date): Date {
  const q = Math.floor(d.getMonth() / 3) * 3
  return startOfDay(new Date(d.getFullYear(), q, 1))
}

function endOfQuarter(d: Date): Date {
  const q = Math.floor(d.getMonth() / 3) * 3
  return endOfDay(new Date(d.getFullYear(), q + 3, 0))
}

function startOfYear(d: Date): Date {
  return startOfDay(new Date(d.getFullYear(), 0, 1))
}

function endOfYear(d: Date): Date {
  return endOfDay(new Date(d.getFullYear(), 12, 0))
}

type Unit = 'isoWeek' | 'month' | 'quarter' | 'year'

const START: Record<Unit, (d: Date) => Date> = {
  isoWeek: startOfWeek,
  month: startOfMonth,
  quarter: startOfQuarter,
  year: startOfYear,
}

const END: Record<Unit, (d: Date) => Date> = {
  isoWeek: endOfWeek,
  month: endOfMonth,
  quarter: endOfQuarter,
  year: endOfYear,
}

function thisRange(unit: Unit, now: Date): Range {
  return { from: FMT(START[unit](now)), to: FMT(END[unit](now)) }
}

function prevRange(unit: Unit, now: Date): Range {
  const d = new Date(now)
  if (unit === 'isoWeek') d.setDate(d.getDate() - 7)
  else if (unit === 'month') d.setMonth(d.getMonth() - 1)
  else if (unit === 'quarter') d.setMonth(d.getMonth() - 3)
  else d.setFullYear(d.getFullYear() - 1)
  return { from: FMT(START[unit](d)), to: FMT(END[unit](d)) }
}

export function defaultMonthRange(): Range {
  return thisRange('month', new Date())
}

export function presetRange(key: string): Range {
  const now = new Date()

  switch (key) {
    case 'This Week':
      return thisRange('isoWeek', now)
    case 'This Month':
      return thisRange('month', now)
    case 'This Quarter':
      return thisRange('quarter', now)
    case 'This Year':
      return thisRange('year', now)
    case 'Previous Week':
      return prevRange('isoWeek', now)
    case 'Previous Month':
      return prevRange('month', now)
    case 'Previous Quarter':
      return prevRange('quarter', now)
    case 'Previous Year':
      return prevRange('year', now)
    default:
      return { from: FMT(now), to: FMT(now) }
  }
}
