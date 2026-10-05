<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { taskLabel } from '@/stores/tasks'
import { timerStore } from '@/stores/timer'
import { useTranslate } from '@/support/i18n'
import { formatClock } from '@/support/time'

type NotifyType = 'success' | 'error' | 'warning' | 'info'

/** The aria-label the AI assistant module puts on its own launcher button. */
const AI_ASSISTANT_SELECTOR = '[aria-label="Open AI Assistant"]'

/** Settings screens run long forms the launcher would sit on top of. */
const HIDDEN_PATH_PREFIX = '/admin/settings'

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  /** False in platform administration, where no company is active. */
  enabled: boolean
  /** The host router: a module bundle cannot call `useRouter()`. */
  router: Router
}>()

const emit = defineEmits<{
  (event: 'open-task'): void
}>()

const t = useTranslate()

/** Whether the running panel is open. There is nothing to show when idle. */
const open = ref(false)

/** The current path, read from the host router and kept up to date on every navigation. */
const currentPath = ref(window.location.pathname)

/** Whether the AI assistant's launcher floats in the same corner as this one. */
const aiAssistantFloats = ref(false)

let stopWatchingRoute: (() => void) | undefined

const feedback = computed(() => ({ notify: props.notify, t }))

const runningLabel = computed<string>(() => taskLabel(timerStore.running?.task_id ?? null))

const elapsed = computed<string>(() => formatClock(timerStore.elapsedSeconds))

/** True while either timer dialog is on screen, which the launcher sits over. */
const asking = computed<boolean>(
  () => timerStore.stopPrompt !== null || timerStore.startPrompt !== null,
)

/**
 * Long settings forms, such as the status editor, run the full height of the
 * page: a fixed launcher sitting on top of them hides the last rows and the
 * save button, so the launcher steps aside there rather than everywhere.
 *
 * It steps aside for its own dialogs too, because a floating button over a
 * modal backdrop reads as something still to be pressed.
 */
const hiddenHere = computed<boolean>(
  () => asking.value || currentPath.value.startsWith(HIDDEN_PATH_PREFIX),
)

/**
 * Clear the bottom of the AI assistant's own launcher when it floats in the
 * same corner, so the two buttons do not stack on top of each other.
 */
const wrapperClass = computed<string>(() => (aiAssistantFloats.value ? 'bottom-24' : 'bottom-5'))

/**
 * Look for a launcher that is actually in the way.
 *
 * The assistant ships its launcher either in the page header or as a bubble of
 * its own, and only the second one shares this corner, so the layout is asked
 * rather than the selector believed. The look is repeated on every navigation
 * because a module registers its header action from a script the host loads
 * alongside this one, which a single look at mount time can miss.
 */
function probeAiAssistant(): void {
  aiAssistantFloats.value = Array.from(document.querySelectorAll(AI_ASSISTANT_SELECTOR)).some(
    (launcher) => window.getComputedStyle(launcher).position === 'fixed',
  )
}

watch(
  () => props.enabled,
  (enabled) => {
    if (!enabled) {
      close()
    }
  },
)

// A stopped or discarded timer leaves the panel with nothing to say.
watch(
  () => timerStore.running,
  (running) => {
    if (running === null) {
      close()
    }
  },
)

onMounted(() => {
  currentPath.value = props.router.currentRoute.value.path
  stopWatchingRoute = props.router.afterEach((to) => {
    currentPath.value = to.path
    probeAiAssistant()
  })

  probeAiAssistant()
})

onBeforeUnmount(() => {
  stopWatchingRoute?.()
})

function close(): void {
  open.value = false
}

/**
 * The circle does the obvious thing for the state it is in.
 *
 * Idle, it asks what to start: the project, the task, and what the time is
 * for. Running, it opens the panel that says what is on the clock.
 */
function press(): void {
  if (timerStore.running === null) {
    void timerStore.startWithPrompt(props.client, feedback.value)

    return
  }

  open.value = !open.value
}

function stop(): void {
  void timerStore.stopWithPrompt(props.client, feedback.value)
}

function openTask(): void {
  close()
  emit('open-task')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="enabled && !hiddenHere"
      class="fixed end-5 z-40 flex flex-col items-end gap-3"
      :class="wrapperClass"
    >
      <!-- Running: what is on the clock, and the two ways out of it. Escape closes it. -->
      <!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
      <section
        v-if="open && timerStore.running !== null"
        class="w-80 max-w-[calc(100vw-3rem)] rounded-xl border border-line-default bg-surface shadow-2xl"
        :aria-label="t('tasks_projects.timer.panel_title')"
        @keydown.esc="close"
      >
        <header class="flex items-center justify-between border-b border-line-default px-4 py-3">
          <h2 class="text-sm font-semibold text-heading">
            {{ t('tasks_projects.timer.running') }}
          </h2>

          <button
            type="button"
            class="rounded p-1 text-subtle hover:bg-hover hover:text-heading"
            :aria-label="t('tasks_projects.timer.close')"
            @click="close"
          >
            <BaseIcon name="XMarkIcon" class="h-5 w-5" />
          </button>
        </header>

        <div class="space-y-4 px-4 py-4">
          <div>
            <p class="truncate text-sm font-medium text-heading">{{ runningLabel }}</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-primary-500">{{ elapsed }}</p>
            <p v-if="timerStore.running.description" class="mt-1 text-xs text-muted">
              {{ timerStore.running.description }}
            </p>
          </div>

          <div class="flex items-center justify-between">
            <button
              type="button"
              class="text-xs text-primary-500 hover:underline"
              @click="openTask"
            >
              {{ t('tasks_projects.timer.open_task') }}
            </button>

            <BaseButton variant="primary" :disabled="timerStore.busy" @click="stop">
              <template #left="slotProps">
                <BaseIcon name="StopIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.timer.stop') }}
            </BaseButton>
          </div>
        </div>
      </section>

      <button
        type="button"
        class="flex items-center justify-center gap-2 rounded-full bg-btn-primary text-sm font-medium text-white shadow-lg hover:bg-btn-primary-hover"
        :class="timerStore.running === null ? 'h-14 w-14 p-0' : 'h-14 px-5'"
        :title="t('tasks_projects.timer.quick_start')"
        :aria-label="t('tasks_projects.timer.quick_start')"
        @click="press"
      >
        <BaseIcon :name="timerStore.running === null ? 'ClockIcon' : 'StopIcon'" class="h-5 w-5 text-white" />
        <span v-if="timerStore.running !== null" class="tabular-nums">{{ elapsed }}</span>
      </button>
    </div>
  </Teleport>
</template>
