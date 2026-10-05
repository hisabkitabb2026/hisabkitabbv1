import { getCurrentInstance } from 'vue'

export type Translate = (key: string, named?: Record<string, unknown>) => string

/**
 * The host's `$t` for use outside a template.
 *
 * A module bundle cannot call `useI18n()`, because it would look the composer
 * up through its own injection symbols, so the translator is read off the host
 * app's global properties instead. Call this during `setup`.
 */
export function useTranslate(): Translate {
  const translate = getCurrentInstance()?.appContext.config.globalProperties.$t as
    | Translate
    | undefined

  return translate ?? ((key: string): string => key)
}
