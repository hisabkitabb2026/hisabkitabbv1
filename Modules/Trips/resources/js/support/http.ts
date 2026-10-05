/**
 * The status of a failed request, read structurally.
 */
export function errorStatus(error: unknown): number | null {
  if (typeof error !== 'object' || error === null) {
    return null
  }

  const status = (error as { response?: { status?: unknown } }).response?.status

  return typeof status === 'number' ? status : null
}

/** The first validation message of a failed request, for a toast. */
export function errorMessage(error: unknown, fallback: string): string {
  if (typeof error === 'object' && error !== null) {
    const data = (error as { response?: { data?: unknown } }).response?.data

    if (typeof data === 'object' && data !== null) {
      const errors = (data as { errors?: unknown }).errors

      if (typeof errors === 'object' && errors !== null) {
        const first = Object.values(errors as Record<string, unknown>)[0]

        if (Array.isArray(first) && typeof first[0] === 'string') {
          return first[0]
        }
      }

      const message = (data as { message?: unknown }).message

      if (typeof message === 'string' && message !== '') {
        return message
      }
    }
  }

  return fallback
}
