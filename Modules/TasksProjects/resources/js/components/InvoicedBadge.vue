<script setup lang="ts">
import { computed } from 'vue'
import { useTranslate } from '@/support/i18n'
import type { TaskInvoiceState } from '@/types/task'

const props = withDefaults(
  defineProps<{
    /** What the task's time summary says about its billable time. */
    state?: TaskInvoiceState
  }>(),
  { state: 'none' },
)

const t = useTranslate()

/**
 * A task with nothing billable to say shows nothing.
 *
 * The badge marks a state worth acting on: money already invoiced, or money
 * waiting to be. A task nobody has logged billable time against is neither,
 * and a badge reading "none" would only add noise to every row.
 */
const visible = computed<boolean>(() => props.state === 'invoiced' || props.state === 'uninvoiced')

const label = computed<string>(() =>
  props.state === 'invoiced'
    ? t('tasks_projects.tasks.invoiced')
    : t('tasks_projects.tasks.uninvoiced'),
)

/**
 * The host badge carries its own colour classes and its stylesheet is loaded
 * after the module's, so the override has to be important to hold.
 */
const tone = computed<string>(() =>
  props.state === 'invoiced'
    ? 'bg-alert-success-bg! text-alert-success-text!'
    : 'bg-alert-warning-bg! text-alert-warning-text!',
)
</script>

<template>
  <BaseBadge v-if="visible" class="rounded-full whitespace-nowrap" :class="tone">
    {{ label }}
  </BaseBadge>
</template>
