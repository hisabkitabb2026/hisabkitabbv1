<script setup lang="ts">
/**
 * Line drawings for the module's empty screens, in the host's empty-state
 * style: thin strokes in the brand colour thinned towards the surface, on a
 * soft glow. The colours are the host's own CSS variables, so they follow
 * light and dark on any 3.x host. Rendered as the empty placeholder's
 * default slot, which every host supports.
 */
export type ModuleArtName = 'tasks' | 'project' | 'board' | 'report' | 'time'

defineProps<{
  name: ModuleArtName
}>()

let counter = 0
const glowId = `tp-empty-glow-${++counter}-${Math.random().toString(36).slice(2, 7)}`

const vars = {
  '--tp-ea-stroke': 'color-mix(in oklab, var(--color-primary-500) 72%, var(--color-surface))',
  '--tp-ea-soft': 'color-mix(in oklab, var(--color-primary-500) 14%, var(--color-surface))',
}

const paper = { fill: 'var(--color-surface)', stroke: 'var(--tp-ea-stroke)' }
const back = { fill: 'var(--tp-ea-soft)', stroke: 'var(--tp-ea-stroke)', opacity: 0.8 }
const ink = { stroke: 'var(--tp-ea-stroke)' }
const faint = { stroke: 'var(--color-line-strong)' }
const badge = { fill: 'var(--tp-ea-soft)', stroke: 'var(--tp-ea-stroke)' }
const mark = { stroke: 'var(--color-primary-600)', strokeWidth: 2 }
const dot = { fill: 'var(--color-primary-500)', stroke: 'none' }
</script>

<template>
  <svg
    viewBox="0 0 120 92"
    fill="none"
    stroke-width="1.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="overflow-visible"
    :style="vars"
    aria-hidden="true"
  >
    <defs>
      <radialGradient :id="glowId">
        <stop offset="0" :style="{ stopColor: 'var(--color-primary-500)', stopOpacity: 0.16 }" />
        <stop offset="1" :style="{ stopColor: 'var(--color-primary-500)', stopOpacity: 0 }" />
      </radialGradient>
    </defs>
    <ellipse cx="60" cy="46" rx="66" ry="54" :fill="`url(#${glowId})`" stroke="none" />

    <template v-if="name === 'tasks'">
      <rect x="32" y="10" width="54" height="70" rx="6" :style="paper" />
      <rect x="40" y="22" width="8" height="8" rx="2" :style="ink" />
      <path d="M42 26l2 2 3.5-3.5" :style="mark" />
      <path d="M54 26h24" :style="ink" />
      <rect x="40" y="38" width="8" height="8" rx="2" :style="ink" />
      <path d="M54 42h20" :style="faint" />
      <rect x="40" y="54" width="8" height="8" rx="2" :style="ink" />
      <path d="M54 58h16" :style="faint" />
      <circle cx="88" cy="70" r="12" :style="badge" />
      <path d="M88 64.5v11M82.5 70h11" :style="mark" />
    </template>

    <template v-else-if="name === 'project'">
      <path d="M24 26a5 5 0 0 1 5-5h18l6 7h33a5 5 0 0 1 5 5v33a5 5 0 0 1-5 5H29a5 5 0 0 1-5-5z" :style="paper" />
      <rect x="34" y="40" width="14" height="22" rx="3" :style="back" />
      <rect x="52" y="40" width="14" height="14" rx="3" :style="back" />
      <rect x="70" y="40" width="14" height="18" rx="3" :style="back" />
      <circle cx="90" cy="72" r="12" :style="badge" />
      <path d="M90 66.5v11M84.5 72h11" :style="mark" />
    </template>

    <template v-else-if="name === 'board'">
      <rect x="20" y="14" width="24" height="62" rx="5" :style="paper" />
      <rect x="48" y="14" width="24" height="62" rx="5" :style="paper" />
      <rect x="76" y="14" width="24" height="62" rx="5" :style="paper" />
      <rect x="25" y="24" width="14" height="10" rx="2" :style="back" />
      <rect x="53" y="24" width="14" height="10" rx="2" :style="back" />
      <path d="M26 44h12M54 44h10M82 26h12" :style="faint" stroke-dasharray="2 4" />
    </template>

    <template v-else-if="name === 'report'">
      <path d="M22 74h80" :style="faint" />
      <rect x="30" y="50" width="12" height="24" rx="2" :style="back" />
      <rect x="50" y="36" width="12" height="38" rx="2" :style="back" />
      <rect x="70" y="44" width="12" height="30" rx="2" :style="back" />
      <path d="M28 40l20-12 20 8 20-16" :style="ink" />
      <circle cx="48" cy="28" r="3" :style="dot" />
      <circle cx="88" cy="20" r="3" :style="dot" />
    </template>

    <template v-else>
      <!-- time -->
      <circle cx="54" cy="44" r="26" :style="paper" />
      <path d="M54 30v14l9 6" :style="ink" />
      <path d="M54 22v3M76 44h-3M54 66v-3M32 44h3" :style="faint" />
      <circle cx="86" cy="68" r="13" :style="badge" />
      <path d="M80.5 68l4 4 7-7.5" :style="mark" />
    </template>
  </svg>
</template>
