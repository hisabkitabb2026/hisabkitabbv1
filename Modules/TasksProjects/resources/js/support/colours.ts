/**
 * Names for the palette swatches, so a screen reader announces "Blue" rather
 * than a hex code. Keys live under tasks_projects.general.colours.
 */
const COLOUR_NAMES: Record<string, string> = {
  '#94a3b8': 'light_grey',
  '#64748b': 'grey',
  '#2563eb': 'blue',
  '#3b82f6': 'blue',
  '#0891b2': 'teal',
  '#059669': 'green',
  '#22c55e': 'green',
  '#ca8a04': 'yellow',
  '#f59e0b': 'amber',
  '#ea580c': 'orange',
  '#dc2626': 'red',
  '#ef4444': 'red',
  '#a855f7': 'purple',
  '#7c3aed': 'violet',
}

export function colourNameKey(hex: string): string {
  return `tasks_projects.general.colours.${COLOUR_NAMES[hex.toLowerCase()] ?? 'custom'}`
}
