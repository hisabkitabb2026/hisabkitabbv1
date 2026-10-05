<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import {
  createTaskStatus,
  deleteTaskStatus,
  listTaskStatuses,
  reorderTaskStatuses,
  updateTaskStatus,
} from '@/api/time'
import { errorMessage } from '@/support/errors'
import { isForbidden } from '@/support/http'
import { useTranslate } from '@/support/i18n'
import { colourNameKey } from '@/support/colours'
import type { TaskStatus, TaskStatusInput } from '@/types/task-status'

type NotifyType = 'success' | 'error' | 'warning' | 'info'

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
}>()

/** The swatches a column can be tagged with. Stored as written, at most 16 chars. */
const COLOURS = ['#94a3b8', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#a855f7', '#0891b2', '#64748b']

const t = useTranslate()

const statuses = ref<TaskStatus[]>([])
const loading = ref(true)
const forbidden = ref(false)
const busy = ref(false)
const editingId = ref<number | null>(null)
const adding = ref(false)

const draft = reactive({
  name: '',
  colour: '',
  is_default: false,
  is_closed: false,
})

const isEmpty = computed<boolean>(() => !loading.value && statuses.value.length === 0)

onMounted(() => void load())

async function load(): Promise<void> {
  loading.value = true

  try {
    statuses.value = await listTaskStatuses(props.client)
    forbidden.value = false
  } catch (error: unknown) {
    statuses.value = []
    forbidden.value = isForbidden(error)

    if (!forbidden.value) {
      props.notify('error', errorMessage(error, t('tasks_projects.settings.load_failed')))
    }
  } finally {
    loading.value = false
  }
}

function startEdit(status: TaskStatus): void {
  adding.value = false
  editingId.value = status.id
  draft.name = status.name
  draft.colour = status.colour ?? ''
  draft.is_default = status.is_default
  draft.is_closed = status.is_closed
}

function startAdd(): void {
  editingId.value = null
  adding.value = true
  draft.name = ''
  draft.colour = COLOURS[0]
  draft.is_default = false
  draft.is_closed = false
}

function cancel(): void {
  editingId.value = null
  adding.value = false
}

function payload(): TaskStatusInput {
  return {
    name: draft.name.trim(),
    colour: draft.colour || null,
    is_default: draft.is_default,
    is_closed: draft.is_closed,
  }
}

async function save(): Promise<void> {
  if (busy.value) {
    return
  }

  if (draft.name.trim() === '') {
    props.notify('error', t('tasks_projects.settings.status_name_required'))

    return
  }

  const id = editingId.value
  const name = draft.name.trim()

  busy.value = true

  try {
    if (id === null) {
      await createTaskStatus(props.client, payload())
      props.notify('success', t('tasks_projects.settings.status_created', { name }))
    } else {
      await updateTaskStatus(props.client, id, payload())
      props.notify('success', t('tasks_projects.settings.status_updated', { name }))
    }

    cancel()
    await load()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.settings.save_failed')))
  } finally {
    busy.value = false
  }
}

/** A column in use answers 422 with the reason, which is worth reading out. */
async function remove(status: TaskStatus): Promise<void> {
  if (busy.value || !window.confirm(t('tasks_projects.settings.status_delete_confirm', { name: status.name }))) {
    return
  }

  busy.value = true

  try {
    await deleteTaskStatus(props.client, status.id)
    props.notify('success', t('tasks_projects.settings.status_deleted', { name: status.name }))
    cancel()
    await load()
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.settings.delete_failed')))
  } finally {
    busy.value = false
  }
}

async function move(index: number, delta: number): Promise<void> {
  const target = index + delta

  if (busy.value || target < 0 || target >= statuses.value.length) {
    return
  }

  const ordered = [...statuses.value]

  ordered.splice(target, 0, ...ordered.splice(index, 1))
  statuses.value = ordered
  busy.value = true

  try {
    statuses.value = await reorderTaskStatuses(
      props.client,
      ordered.map((status) => status.id),
    )
    props.notify('success', t('tasks_projects.settings.status_reordered'))
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.settings.reorder_failed')))
    await load()
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div>
    <p v-if="forbidden" class="text-sm text-muted">
      {{ t('tasks_projects.settings.forbidden') }}
    </p>

    <div v-else>
      <div v-if="loading" class="flex items-center gap-2 text-sm text-muted">
        <BaseSpinner class="h-4 w-4 text-primary-500" />
      </div>

      <p v-else-if="isEmpty" class="text-sm text-muted">
        {{ t('tasks_projects.settings.no_statuses') }}
      </p>

      <ul v-else class="divide-y divide-line-light">
        <li v-for="(status, index) in statuses" :key="status.id" class="py-3">
          <!-- Inline editor for the row being changed. -->
          <div v-if="editingId === status.id" class="space-y-3">
            <BaseInputGroup :label="t('tasks_projects.settings.status_name')" required>
              <BaseInput v-model="draft.name" type="text" maxlength="255" />
            </BaseInputGroup>

            <BaseInputGroup :label="t('tasks_projects.settings.colour')">
              <div class="flex flex-wrap items-center gap-2">
                <button
                  v-for="option in COLOURS"
                  :key="option"
                  type="button"
                  class="h-7 w-7 rounded-full border-2 transition"
                  :class="draft.colour === option ? 'border-heading' : 'border-line-default'"
                  :style="{ backgroundColor: option }"
                  :aria-label="t(colourNameKey(option))"
                  :aria-pressed="draft.colour === option"
                  @click="draft.colour = option"
                />
                <button
                  type="button"
                  class="rounded-md border border-line-default px-2 py-1 text-xs text-muted hover:bg-hover"
                  @click="draft.colour = ''"
                >
                  {{ t('tasks_projects.settings.colour_none') }}
                </button>
              </div>
            </BaseInputGroup>

            <div class="flex flex-wrap items-center gap-6">
              <label class="flex items-center gap-2 text-sm text-body">
                <BaseSwitch v-model="draft.is_default" class="flex" />
                {{ t('tasks_projects.settings.is_default') }}
              </label>

              <label class="flex items-center gap-2 text-sm text-body">
                <BaseSwitch v-model="draft.is_closed" class="flex" />
                {{ t('tasks_projects.settings.is_closed') }}
              </label>
            </div>

            <div class="flex gap-3">
              <BaseButton variant="primary" size="sm" :disabled="busy" @click="save">
                {{ t('tasks_projects.general.save') }}
              </BaseButton>
              <BaseButton variant="primary-outline" size="sm" @click="cancel">
                {{ t('tasks_projects.general.cancel') }}
              </BaseButton>
            </div>
          </div>

          <!-- The resting row. -->
          <div v-else class="flex items-center gap-3">
            <span
              class="inline-block h-3 w-3 shrink-0 rounded-full"
              :class="status.colour ? '' : 'bg-line-default'"
              :style="status.colour ? { backgroundColor: status.colour } : undefined"
            />

            <span class="min-w-0 flex-1 truncate text-sm font-medium text-heading">
              {{ status.name }}
            </span>

            <BaseBadge v-if="status.is_default" class="rounded-full bg-primary-50! text-primary-500!">
              {{ t('tasks_projects.settings.is_default') }}
            </BaseBadge>

            <BaseBadge v-if="status.is_closed" class="rounded-full bg-surface-tertiary! text-muted!">
              {{ t('tasks_projects.settings.is_closed') }}
            </BaseBadge>

            <div class="flex items-center gap-1">
              <button
                type="button"
                class="rounded p-1 text-subtle hover:bg-hover hover:text-heading disabled:opacity-40"
                :disabled="busy || index === 0"
                :title="t('tasks_projects.settings.move_up')"
                :aria-label="t('tasks_projects.settings.move_up')"
                @click="move(index, -1)"
              >
                <BaseIcon name="ChevronUpIcon" class="h-4 w-4" />
              </button>

              <button
                type="button"
                class="rounded p-1 text-subtle hover:bg-hover hover:text-heading disabled:opacity-40"
                :disabled="busy || index === statuses.length - 1"
                :title="t('tasks_projects.settings.move_down')"
                :aria-label="t('tasks_projects.settings.move_down')"
                @click="move(index, 1)"
              >
                <BaseIcon name="ChevronDownIcon" class="h-4 w-4" />
              </button>

              <button
                type="button"
                class="rounded p-1 text-subtle hover:bg-hover hover:text-heading"
                :title="t('tasks_projects.general.edit')"
                :aria-label="t('tasks_projects.general.edit')"
                @click="startEdit(status)"
              >
                <BaseIcon name="PencilIcon" class="h-4 w-4" />
              </button>

              <button
                type="button"
                class="rounded p-1 text-subtle hover:bg-hover hover:text-alert-error-text"
                :disabled="busy"
                :title="t('tasks_projects.general.delete')"
                :aria-label="t('tasks_projects.general.delete')"
                @click="remove(status)"
              >
                <BaseIcon name="TrashIcon" class="h-4 w-4" />
              </button>
            </div>
          </div>
        </li>
      </ul>

      <!-- The new column, written in the same shape as an edited one. -->
      <div v-if="adding" class="mt-4 space-y-3 rounded-lg border border-line-default p-3">
        <BaseInputGroup :label="t('tasks_projects.settings.status_name')" required>
          <BaseInput v-model="draft.name" type="text" maxlength="255" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('tasks_projects.settings.colour')">
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="option in COLOURS"
              :key="option"
              type="button"
              class="h-7 w-7 rounded-full border-2 transition"
              :class="draft.colour === option ? 'border-heading' : 'border-line-default'"
              :style="{ backgroundColor: option }"
              :aria-label="t(colourNameKey(option))"
              :aria-pressed="draft.colour === option"
              @click="draft.colour = option"
            />
          </div>
        </BaseInputGroup>

        <div class="flex flex-wrap items-center gap-6">
          <label class="flex items-center gap-2 text-sm text-body">
            <BaseSwitch v-model="draft.is_default" class="flex" />
            {{ t('tasks_projects.settings.is_default') }}
          </label>

          <label class="flex items-center gap-2 text-sm text-body">
            <BaseSwitch v-model="draft.is_closed" class="flex" />
            {{ t('tasks_projects.settings.is_closed') }}
          </label>
        </div>

        <div class="flex gap-3">
          <BaseButton variant="primary" size="sm" :disabled="busy" @click="save">
            {{ t('tasks_projects.general.save') }}
          </BaseButton>
          <BaseButton variant="primary-outline" size="sm" @click="cancel">
            {{ t('tasks_projects.general.cancel') }}
          </BaseButton>
        </div>
      </div>

      <BaseButton v-else-if="!loading" variant="primary-outline" size="sm" class="mt-4" @click="startAdd">
        <template #left="slotProps">
          <BaseIcon name="PlusIcon" :class="slotProps.class" />
        </template>
        {{ t('tasks_projects.settings.add_status') }}
      </BaseButton>
    </div>
  </div>
</template>
