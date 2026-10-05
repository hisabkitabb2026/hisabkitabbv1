<script setup lang="ts">
import { computed } from 'vue'
import type { AxiosInstance } from 'axios'
import { taskLabel } from '@/stores/tasks'
import { timerStore } from '@/stores/timer'
import { useTranslate } from '@/support/i18n'
import { formatClock } from '@/support/time'

type NotifyType = 'success' | 'error' | 'warning' | 'info'

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
}>()

const emit = defineEmits<{
  (event: 'open'): void
}>()

const t = useTranslate()

const label = computed<string>(() => taskLabel(timerStore.running?.task_id ?? null))

const elapsed = computed<string>(() => formatClock(timerStore.elapsedSeconds))

/**
 * Stopping asks before it writes.
 *
 * The chip is the most convenient stop button on the screen, which is exactly
 * why it should not be the one that silently files an hour with no note on it.
 * The dialog, the discard and the message all live in the store.
 */
function stop(): void {
  void timerStore.stopWithPrompt(props.client, { notify: props.notify, t })
}
</script>

<template>
  <li v-if="timerStore.running !== null" class="relative float-start m-0 ms-2">
    <div
      class="flex h-8 items-center gap-2 rounded-lg bg-white/20 px-2 text-sm text-white md:h-9 md:px-3"
      :title="t('tasks_projects.timer.running')"
    >
      <span class="inline-block h-2 w-2 shrink-0 animate-pulse rounded-full bg-white" />

      <button
        type="button"
        class="hidden max-w-32 truncate hover:underline lg:block"
        :aria-label="t('tasks_projects.timer.open_task')"
        :title="t('tasks_projects.timer.open_task')"
        @click="emit('open')"
      >
        {{ label }}
      </button>

      <span class="font-medium tabular-nums">{{ elapsed }}</span>

      <button
        type="button"
        class="rounded p-1 hover:bg-white/20 disabled:opacity-50"
        :disabled="timerStore.busy"
        :title="t('tasks_projects.timer.stop')"
        :aria-label="t('tasks_projects.timer.stop')"
        @click="stop"
      >
        <BaseIcon name="StopIcon" class="h-4 w-4 text-white" />
      </button>
    </div>
  </li>
</template>
