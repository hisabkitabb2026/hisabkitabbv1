<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useTranslate } from '@/support/i18n'
import type { SelectOption } from '@/types/board'
import type { TaskStatus } from '@/types/task-status'

const props = withDefaults(
  defineProps<{
    /** How many tasks the selection holds. The bar hides at zero. */
    count: number
    statuses: TaskStatus[]
    /** True while a bulk request is in flight. */
    busy: boolean
    /** True while the invoicing sequence is running, anywhere in the module. */
    invoicing?: boolean
    /** False once the server has refused the ability to invoice. */
    canInvoice?: boolean
  }>(),
  { invoicing: false, canInvoice: true },
)

const emit = defineEmits<{
  (event: 'status', statusId: number): void
  (event: 'delete'): void
  (event: 'invoice'): void
  (event: 'clear'): void
  (event: 'select-page'): void
}>()

const t = useTranslate()

const status = ref<SelectOption | null>(null)

const statusOptions = computed<SelectOption[]>(() =>
  props.statuses.map((record) => ({ id: record.id, label: record.name })),
)

// The picker is an action, not a setting: it forgets what was chosen so the
// next selection starts from "move to" rather than from the last column used.
watch(status, (option) => {
  if (option !== null) {
    emit('status', option.id)
    status.value = null
  }
})
</script>

<template>
  <div
    v-if="count > 0"
    class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-2.5"
  >
    <span class="text-sm font-medium text-primary-700">
      {{ t('tasks_projects.tasks.bulk.selected', { count }) }}
    </span>

    <div class="min-w-48">
      <BaseSelectInput
        v-model="status"
        :options="statusOptions"
        :disabled="busy"
        :placeholder="t('tasks_projects.tasks.bulk.change_status')"
        :aria-label="t('tasks_projects.tasks.bulk.change_status')"
        label-key="label"
      />
    </div>

    <BaseButton variant="primary-outline" size="sm" :disabled="busy" @click="emit('delete')">
      <template #left="slotProps">
        <BaseIcon name="TrashIcon" :class="slotProps.class" />
      </template>
      {{ t('tasks_projects.tasks.bulk.delete') }}
    </BaseButton>

    <BaseButton
      v-if="canInvoice"
      variant="primary-outline"
      size="sm"
      :loading="invoicing"
      :disabled="busy || invoicing"
      @click="emit('invoice')"
    >
      <template #left="slotProps">
        <BaseIcon v-if="!invoicing" name="BanknotesIcon" :class="slotProps.class" />
      </template>
      {{ t('tasks_projects.tasks.bulk.invoice') }}
    </BaseButton>

    <button
      type="button"
      class="ms-auto text-sm font-medium text-primary-600 hover:underline"
      @click="emit('select-page')"
    >
      {{ t('tasks_projects.tasks.bulk.select_page') }}
    </button>

    <button
      type="button"
      class="text-sm font-medium text-primary-600 hover:underline"
      @click="emit('clear')"
    >
      {{ t('tasks_projects.tasks.bulk.clear') }}
    </button>
  </div>
</template>
