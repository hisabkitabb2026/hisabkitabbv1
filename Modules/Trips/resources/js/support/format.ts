/** Minor units to the major-unit string a number input shows. */
export function minorToMajor(amount: number | null): string {
  return amount === null ? '' : String(amount / 100)
}

/** A typed major-unit amount back to integer minor units. */
export function majorToMinor(value: string): number | null {
  const amount = Number(value)

  return value.trim() === '' || Number.isNaN(amount) ? null : Math.round(amount * 100)
}

/** Integer minor units as a locale currency string, e.g. "8,000.00". */
export function formatMoney(amount: number | null): string {
  return ((amount ?? 0) / 100).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

/** A `Y-m-d` date in the viewer's locale. */
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

/** A timestamp as the viewer's locale date-time. */
export function formatDateTime(value: string | null): string {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/** Today as the `Y-m-d` the API takes. */
export function today(): string {
  const now = new Date()

  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}
