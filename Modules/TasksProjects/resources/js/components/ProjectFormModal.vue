<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { createProject, listCustomers, updateProject } from '@/api'
import { errorMessage, fieldErrors } from '@/support/errors'
import {
  hoursToMinutes,
  majorToMinor,
  minorToMajor,
  minutesToHours,
  toDateString,
} from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { colourNameKey } from '@/support/colours'
import type { Customer } from '@/types/api'
import type { Project, ProjectInput } from '@/types/project'

type NotifyType = 'success' | 'error' | 'warning' | 'info'

interface CustomerOption {
  id: number
  label: string
}

const props = defineProps<{
  show: boolean
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  /** The project being edited, or null to create one. */
  project: Project | null
}>()

const emit = defineEmits<{
  (event: 'close'): void
  (event: 'saved', project: Project): void
}>()

/** Swatches a project can be tagged with. Stored as written, at most 16 chars. */
const COLOURS = ['#2563eb', '#0891b2', '#059669', '#ca8a04', '#ea580c', '#dc2626', '#7c3aed', '#64748b']

const t = useTranslate()

const form = reactive({
  name: '',
  identifier: '',
  description: '',
  colour: '',
  /** Major units, as typed. Converted to minor units on save. */
  defaultRate: '',
  /** Hours, as typed. Converted to minutes on save. */
  budgetHours: '',
  dueDate: '',
})

const customer = ref<CustomerOption | null>(null)
const customers = ref<CustomerOption[]>([])
const customersLoaded = ref(false)
const errors = ref<Record<string, string>>({})
const saving = ref(false)

const isEdit = computed(() => props.project !== null)

const title = computed(() =>
  isEdit.value ? t('tasks_projects.projects.edit_project') : t('tasks_projects.projects.new_project'),
)

watch(
  () => props.show,
  (show) => {
    if (show) {
      reset()
      void loadCustomers()
    }
  },
  { immediate: true },
)

function reset(): void {
  const project = props.project

  form.name = project?.name ?? ''
  form.identifier = project?.identifier ?? ''
  form.description = project?.description ?? ''
  form.colour = project?.colour ?? ''
  form.defaultRate = minorToMajor(project?.default_rate ?? null)
  form.budgetHours = minutesToHours(project?.budget_minutes ?? null)
  form.dueDate = project?.due_date ?? ''
  errors.value = {}
  customer.value = findCustomer(project?.customer_id ?? null)
}

function findCustomer(id: number | null): CustomerOption | null {
  return id === null ? null : (customers.value.find((option) => option.id === id) ?? null)
}

function customerLabel(record: Customer): string {
  return record.display_name || record.name || `#${record.id}`
}

async function loadCustomers(): Promise<void> {
  if (customersLoaded.value) {
    return
  }

  try {
    const records = await listCustomers(props.client)

    customers.value = records.map((record) => ({ id: record.id, label: customerLabel(record) }))
    customersLoaded.value = true
    customer.value = findCustomer(props.project?.customer_id ?? null)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.projects.customers_failed')))
  }
}

function payload(): ProjectInput {
  return {
    name: form.name.trim(),
    customer_id: customer.value?.id ?? null,
    identifier: form.identifier.trim() || null,
    description: form.description.trim() || null,
    colour: form.colour || null,
    default_rate: majorToMinor(form.defaultRate),
    budget_minutes: hoursToMinutes(form.budgetHours),
    due_date: form.dueDate || null,
  }
}

function onDueDate(value: string | Date): void {
  form.dueDate = value ? toDateString(value) : ''
}

async function save(): Promise<void> {
  if (saving.value) {
    return
  }

  if (form.name.trim() === '') {
    errors.value = { name: t('tasks_projects.projects.name_required') }

    return
  }

  saving.value = true
  errors.value = {}

  try {
    const existing = props.project
    const project = existing
      ? await updateProject(props.client, existing.id, payload())
      : await createProject(props.client, payload())

    emit('saved', project)
  } catch (error: unknown) {
    errors.value = fieldErrors(error)
    props.notify('error', errorMessage(error, t('tasks_projects.projects.save_failed')))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal :show="show" @close="emit('close')">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>{{ title }}</span>
        <button
          type="button"
          class="-m-1.5 rounded-lg p-1.5 text-subtle hover:text-body focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          :aria-label="t('tasks_projects.general.close')"
          @click="emit('close')"
        >
          <BaseIcon name="XMarkIcon" class="h-6 w-6" />
        </button>
      </div>
    </template>

    <form @submit.prevent="save">
      <div class="space-y-5 px-6 py-6">
        <BaseInputGrid>
          <BaseInputGroup
            :label="t('tasks_projects.projects.fields.name')"
            :error="errors.name"
            required
          >
            <BaseInput v-model="form.name" :invalid="Boolean(errors.name)" type="text" />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.projects.fields.identifier')"
            :error="errors.identifier"
            :help-text="t('tasks_projects.projects.fields.identifier_help')"
          >
            <BaseInput
              v-model="form.identifier"
              :invalid="Boolean(errors.identifier)"
              type="text"
              maxlength="32"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.projects.fields.customer')"
            :error="errors.customer_id"
            :help-text="t('tasks_projects.projects.fields.customer_help')"
          >
            <BaseSelectInput
              v-model="customer"
              :options="customers"
              :placeholder="t('tasks_projects.projects.fields.customer_placeholder')"
              label-key="label"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="t('tasks_projects.projects.fields.due_date')" :error="errors.due_date">
            <BaseDatePicker :model-value="form.dueDate" @update:model-value="onDueDate" />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.projects.fields.default_rate')"
            :error="errors.default_rate"
            :help-text="t('tasks_projects.projects.fields.default_rate_help')"
          >
            <BaseInput
              v-model="form.defaultRate"
              :invalid="Boolean(errors.default_rate)"
              type="number"
              step="0.01"
              min="0"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="t('tasks_projects.projects.fields.budget_hours')"
            :error="errors.budget_minutes"
          >
            <BaseInput
              v-model="form.budgetHours"
              :invalid="Boolean(errors.budget_minutes)"
              type="number"
              step="0.25"
              min="0"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseInputGroup :label="t('tasks_projects.projects.fields.colour')" :error="errors.colour">
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="option in COLOURS"
              :key="option"
              type="button"
              class="h-7 w-7 rounded-full border-2 transition"
              :class="form.colour === option ? 'border-heading' : 'border-line-default'"
              :style="{ backgroundColor: option }"
              :aria-label="t(colourNameKey(option))"
              :aria-pressed="form.colour === option"
              @click="form.colour = form.colour === option ? '' : option"
            />
            <button
              type="button"
              class="rounded-md border border-line-default px-2 py-1 text-xs text-muted hover:bg-hover"
              @click="form.colour = ''"
            >
              {{ t('tasks_projects.projects.fields.colour_none') }}
            </button>
          </div>
        </BaseInputGroup>

        <BaseInputGroup
          :label="t('tasks_projects.projects.fields.description')"
          :error="errors.description"
        >
          <BaseTextarea v-model="form.description" :row="3" :invalid="Boolean(errors.description)" />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end space-x-3 border-t border-line-default px-6 py-4">
        <BaseButton type="button" variant="primary-outline" @click="emit('close')">
          {{ t('tasks_projects.general.cancel') }}
        </BaseButton>
        <BaseButton type="submit" variant="primary" :loading="saving" :disabled="saving">
          {{ isEdit ? t('tasks_projects.general.update') : t('tasks_projects.general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>
