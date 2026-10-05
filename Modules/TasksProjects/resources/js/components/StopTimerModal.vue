<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { session } from '@/stores/session'
import { taskLabel } from '@/stores/tasks'
import { timerStore } from '@/stores/timer'
import { useTranslate } from '@/support/i18n'
import { formatClock, formatDuration, roundMinutes } from '@/support/time'

/**
 * The one question every stop control asks.
 *
 * Stopping used to write the interval with whatever was typed at start, which
 * for most people was nothing, so the time log filled up with unnamed hours.
 * The dialog asks for the note while the work is still in mind, and shows what
 * the entry is about to say after the company's rounding, so the number in the
 * timesheet is never a surprise.
 *
 * It is mounted once in the company layout, because the chip, the launcher, a
 * task row, a card, the task page and the time log all stop the same timer and
 * none of them should carry a dialog for it. It is deliberately not hidden on
 * any path: a clock that is running on a settings screen still has to be
 * stoppable there.
 */

const t = useTranslate()

const description = ref('')
const billable = ref(true)
/** True once Discard has been pressed and is waiting to be confirmed. */
const confirming = ref(false)

const open = computed<boolean>(() => timerStore.stopPrompt !== null)

const label = computed<string>(() => taskLabel(timerStore.stopPrompt?.entry.task_id ?? null))

const elapsed = computed<string>(() => formatClock(timerStore.elapsedSeconds))

/** The increment the company bills in, which the preview has to honour. */
const increment = computed<number>(() => session.settings.rounding_minutes)

/** What the saved entry will read, once the server has rounded the minutes. */
const saved = computed<string>(() =>
  formatDuration(
    roundMinutes(
      Math.round(timerStore.elapsedSeconds / 60),
      increment.value,
      session.settings.rounding_direction,
    ),
  ),
)

const preview = computed<string>(() =>
  increment.value > 1
    ? t('tasks_projects.timer.saved_as', { duration: saved.value, increment: increment.value })
    : t('tasks_projects.timer.saved_as_exact', { duration: saved.value }),
)

// Opening fills the fields from the entry that is running, so the common
// answer is to add a sentence and press save.
watch(
  () => timerStore.stopPrompt,
  (prompt) => {
    description.value = prompt?.entry.description ?? ''
    billable.value = prompt?.entry.billable !== false
    confirming.value = false
  },
)

function save(): void {
  timerStore.answerStop({
    action: 'save',
    description: description.value.trim() || null,
    billable: billable.value,
  })
}

function discard(): void {
  timerStore.answerStop({ action: 'discard' })
}

/** Backing out leaves the clock running; nothing has been written. */
function cancel(): void {
  timerStore.answerStop(null)
}
</script>

<template>
  <BaseModal :show="open" @close="cancel">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>{{ t('tasks_projects.timer.stop_title') }}</span>
        <button
          type="button"
          class="-m-1.5 rounded-lg p-1.5 text-subtle hover:text-body focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          :aria-label="t('tasks_projects.general.close')"
          @click="cancel"
        >
          <BaseIcon name="XMarkIcon" class="h-6 w-6" />
        </button>
      </div>
    </template>

    <form @submit.prevent="save">
      <div class="space-y-5 px-6 py-6">
        <div>
          <p class="truncate text-sm font-medium text-heading">{{ label }}</p>
          <p class="mt-1 text-3xl font-semibold tabular-nums text-primary-500">{{ elapsed }}</p>
          <p class="mt-1 text-xs text-muted">{{ preview }}</p>
        </div>

        <BaseInputGroup :label="t('tasks_projects.time.fields.description')">
          <!--
            No autofocus: the dialog already places focus itself, and asking
            for it again only makes the browser log that it refused.
          -->
          <BaseTextarea v-model="description" :row="3" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.time.fields.billable')">
          <BaseSwitch v-model="billable" class="flex" />
        </BaseInputGroup>
      </div>

      <div class="border-t border-line-default px-6 py-4">
        <!-- Discarding is one press away from losing real work, so it asks. -->
        <div v-if="confirming" class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-sm text-body">
            {{ t('tasks_projects.timer.discard_ask', { duration: elapsed }) }}
          </p>

          <div class="flex space-x-3">
            <BaseButton type="button" variant="primary-outline" @click="confirming = false">
              {{ t('tasks_projects.general.cancel') }}
            </BaseButton>
            <BaseButton
              type="button"
              variant="danger"
              :disabled="timerStore.busy"
              @click="discard"
            >
              {{ t('tasks_projects.timer.discard') }}
            </BaseButton>
          </div>
        </div>

        <div v-else class="flex items-center justify-between">
          <BaseButton
            type="button"
            variant="white"
            :disabled="timerStore.busy"
            @click="confirming = true"
          >
            {{ t('tasks_projects.timer.discard') }}
          </BaseButton>

          <div class="flex space-x-3">
            <BaseButton type="button" variant="primary-outline" @click="cancel">
              {{ t('tasks_projects.general.cancel') }}
            </BaseButton>
            <BaseButton
              type="submit"
              variant="primary"
              :loading="timerStore.busy"
              :disabled="timerStore.busy"
            >
              <template #left="slotProps">
                <BaseIcon name="StopIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.timer.save_and_stop') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </form>
  </BaseModal>
</template>
