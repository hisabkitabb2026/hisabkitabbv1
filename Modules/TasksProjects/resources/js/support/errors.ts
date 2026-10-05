/**
 * Reading a Laravel error response without importing axios at runtime.
 *
 * The module bundle runs inside the host page and receives the host's own
 * axios instance, so errors are checked structurally rather than with
 * `axios.isAxiosError`.
 */

interface ApiErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

function responseBody(error: unknown): ApiErrorBody | null {
  if (typeof error !== 'object' || error === null) {
    return null
  }

  const response = (error as { response?: { data?: unknown } }).response

  if (typeof response?.data !== 'object' || response.data === null) {
    return null
  }

  return response.data as ApiErrorBody
}

/** The server's message, or the caller's fallback when there is none. */
export function errorMessage(error: unknown, fallback: string): string {
  const message = responseBody(error)?.message

  return typeof message === 'string' && message !== '' ? message : fallback
}

/** The first validation message per field of a 422 response. */
export function fieldErrors(error: unknown): Record<string, string> {
  const errors = responseBody(error)?.errors
  const messages: Record<string, string> = {}

  if (typeof errors !== 'object' || errors === null) {
    return messages
  }

  for (const [field, list] of Object.entries(errors)) {
    if (Array.isArray(list) && typeof list[0] === 'string') {
      messages[field] = list[0]
    }
  }

  return messages
}
