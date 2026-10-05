import type { AxiosInstance } from 'axios'

export function useTripReportDownload(
  client: AxiosInstance,
  resolvePath: () => string | null,
): () => void {
  async function download(): Promise<void> {
    const path = resolvePath()
    if (!path) return

    try {
      const response = await client.get<Blob>(path + '&download=true', { responseType: 'blob' })
      const url = URL.createObjectURL(response.data)
      const a = document.createElement('a')
      a.href = url
      a.download = 'trip-report.pdf'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      setTimeout(() => URL.revokeObjectURL(url), 5_000)
    } catch {
      // silently fail
    }
  }

  return () => { void download() }
}
