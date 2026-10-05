<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { listAllTimeEntries } from '@/api/time'
import { ensureTaskNames, taskLabel } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { useTranslate } from '@/support/i18n'
import {
  addDays,
  dayLabel,
  formatDuration,
  formatLocalDate,
  isToday,
  localDateOf,
  startOfWeek,
  weekDays,
} from '@/support/time'
import type { TimeEntry, TimeEntryListParams } from '@/types/time-entry'

type NotifyType = 'success' | 'error' | 'warning' | 'info'

interface DayColumn {
  key: string
  weekday: string
  day: string
  today: boolean
  entries: TimeEntry[]
  minutes: number
}

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  /** Whose week this is. Null while the host bootstrap has not answered. */
  userId: number | null
  /** The project the screen above is filtered to, or null for all of them. */
  projectId?: number | null
  /** 0 for Sunday through 6 for Saturday, from the module settings. */
  weekStart: number
  /** Bumped by the page whenever an entry was saved elsewhere. */
  reloadToken: number
}>()

const emit = defineEmits<{
  (event: 'add', date: string): void
  (event: 'edit', entry: TimeEntry): void
}>()

const t = useTranslate()

const anchor = ref<Date>(startOfWeek(new Date(), props.weekStart))
const entries = ref<TimeEntry[]>([])
const loading = ref(false)

const days = computed<Date[]>(() => weekDays(anchor.value))

const rangeLabel = computed<string>(() => {
  const first = days.value[0]
  const last = days.value[days.value.length - 1]

  return `${dayLabel(first).day} - ${dayLabel(last).day}`
})

const columns = computed<DayColumn[]>(() =>
  days.value.map((date) => {
    const key = formatLocalDate(date)
    const ofDay = entries.value.filter((entry) => localDateOf(entry.started_at) === key)
    const labels = dayLabel(date)

    return {
      key,
      weekday: labels.weekday,
      day: labels.day,
      today: isToday(date),
      entries: ofDay,
      minutes: totalOf(ofDay),
    }
  }),
)

const weekMinutes = computed<number>(() => totalOf(entries.value))

const isEmpty = computed<boolean>(() => !loading.value && entries.value.length === 0)

watch(
  () => props.weekStart,
  (weekStart) => {
    anchor.value = startOfWeek(anchor.value, weekStart)
  },
)

watch(
  [anchor, () => props.userId, () => props.projectId, () => props.reloadToken],
  () => void load(),
  { immediate: true },
)

function totalOf(list: TimeEntry[]): number {
  return list.reduce((sum, entry) => sum + (entry.duration_minutes ?? 0), 0)
}

async function load(): Promise<void> {
  if (props.userId === null) {
    entries.value = []

    return
  }

  loading.value = true

  try {
    const params: TimeEntryListParams = {
      user_id: props.userId,
      from: formatLocalDate(days.value[0]),
      to: formatLocalDate(days.value[days.value.length - 1]),
    }

    if (props.projectId) {
      params.project_id = props.projectId
    }

    const loaded = await listAllTimeEntries(props.client, params)

    entries.value = loaded
    void ensureTaskNames(
      props.client,
      loaded.map((entry) => entry.task_id).filter((id): id is number => typeof id === 'number'),
    )
  } catch (error: unknown) {
    entries.value = []
    props.notify('error', errorMessage(error, t('tasks_projects.time.load_failed')))
  } finally {
    loading.value = false
  }
}

function move(weeks: number): void {
  anchor.value = addDays(anchor.value, weeks * 7)
}

function thisWeek(): void {
  anchor.value = startOfWeek(new Date(), props.weekStart)
}
</script>

<template>
  <section>
    <header class="mt-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <BaseButton
          variant="white"
          size="sm"
          :title="t('tasks_projects.time.previous_week')"
          :aria-label="t('tasks_projects.time.previous_week')"
          @click="move(-1)"
        >
          <BaseIcon name="ChevronLeftIcon" class="h-4 w-4" />
        </BaseButton>

        <BaseButton variant="white" size="sm" @click="thisWeek">
          {{ t('tasks_projects.time.this_week') }}
        </BaseButton>

        <BaseButton
          variant="white"
          size="sm"
          :title="t('tasks_projects.time.next_week')"
          :aria-label="t('tasks_projects.time.next_week')"
          @click="move(1)"
        >
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4" />
        </BaseButton>

        <span class="ms-1 text-sm text-muted">{{ rangeLabel }}</span>
      </div>

      <div class="flex items-center gap-2 text-sm">
        <span class="text-muted">{{ t('tasks_projects.time.week_total') }}</span>
        <span class="text-lg font-semibold tabular-nums text-heading">
          {{ formatDuration(weekMinutes) }}
        </span>
        <BaseSpinner v-if="loading" class="h-4 w-4 text-primary-500" />
      </div>
    </header>

    <p v-if="userId === null" class="mt-6 text-sm text-muted">
      {{ t('tasks_projects.time.unknown_user') }}
    </p>

    <div v-else class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
      <article
        v-for="column in columns"
        :key="column.key"
        class="flex min-h-40 flex-col rounded-xl border bg-surface p-3"
        :class="column.today ? 'border-primary-400' : 'border-line-default'"
      >
        <header class="flex items-baseline justify-between">
          <div>
            <p class="text-xs font-semibold tracking-wide text-heading uppercase">
              {{ column.weekday }}
            </p>
            <p class="text-xs text-muted">{{ column.day }}</p>
          </div>

          <span class="text-sm font-medium tabular-nums text-heading">
            {{ formatDuration(column.minutes) }}
          </span>
        </header>

        <ul class="mt-3 flex-1 space-y-2">
          <li v-for="entry in column.entries" :key="entry.id">
            <button
              type="button"
              class="w-full rounded-md border border-line-light px-2 py-2 text-start hover:bg-hover"
              @click="emit('edit', entry)"
            >
              <span class="flex items-center justify-between gap-2">
                <span class="truncate text-xs font-medium text-heading">
                  {{ taskLabel(entry.task_id) }}
                </span>
                <span class="shrink-0 text-xs tabular-nums text-muted">
                  {{ formatDuration(entry.duration_minutes) }}
                </span>
              </span>

              <span v-if="entry.description" class="mt-1 block truncate text-xs text-muted">
                {{ entry.description }}
              </span>

              <span class="mt-1 flex items-center gap-1">
                <span
                  class="inline-block h-1.5 w-1.5 rounded-full"
                  :class="entry.billable ? 'bg-status-green' : 'bg-line-strong'"
                />
                <span class="text-[11px] text-subtle">
                  {{
                    entry.billable
                      ? t('tasks_projects.time.billable')
                      : t('tasks_projects.time.non_billable')
                  }}
                </span>
                <span v-if="entry.invoice_id !== null" class="text-[11px] text-subtle">
                  - {{ t('tasks_projects.time.billed') }}
                </span>
              </span>
            </button>
          </li>

          <li v-if="column.entries.length === 0" class="py-2 text-xs text-subtle">
            {{ t('tasks_projects.time.no_entries') }}
          </li>
        </ul>

        <button
          type="button"
          class="mt-2 flex items-center justify-center gap-1 rounded-md border border-dashed border-line-default py-1.5 text-xs text-muted hover:bg-hover hover:text-heading"
          @click="emit('add', column.key)"
        >
          <BaseIcon name="PlusIcon" class="h-4 w-4" />
          {{ t('tasks_projects.time.add_entry') }}
        </button>
      </article>
    </div>

    <p v-if="isEmpty && userId !== null" class="mt-4 text-center text-sm text-subtle">
      {{ t('tasks_projects.time.empty_description') }}
    </p>
  </section>
</template>
