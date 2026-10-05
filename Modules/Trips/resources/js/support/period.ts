import { presetRange } from './date-range'
import type { Range } from './date-range'

export interface PeriodValue {
  preset: string
  from?: string | null
  to?: string | null
}

export interface PeriodPreset {
  key: string
  label: string
  range?: () => Range
}

export const CUSTOM_PERIOD = 'custom'

type Translate = (key: string) => string

export function reportPresets(t: Translate): PeriodPreset[] {
  const presets: Array<[string, string]> = [
    ['Today', 'dateRange.today'],
    ['This Week', 'dateRange.this_week'],
    ['This Month', 'dateRange.this_month'],
    ['This Quarter', 'dateRange.this_quarter'],
    ['This Year', 'dateRange.this_year'],
    ['Previous Week', 'dateRange.previous_week'],
    ['Previous Month', 'dateRange.previous_month'],
    ['Previous Quarter', 'dateRange.previous_quarter'],
    ['Previous Year', 'dateRange.previous_year'],
  ]

  return presets.map(([key, label]) => ({ key, label: t(label), range: () => presetRange(key) }))
}

export function presetValue(preset: PeriodPreset): PeriodValue {
  const range = preset.range?.()
  return { preset: preset.key, from: range?.from ?? null, to: range?.to ?? null }
}
