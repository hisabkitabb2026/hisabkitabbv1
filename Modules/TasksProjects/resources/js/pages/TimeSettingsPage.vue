<script setup lang="ts">
import { computed } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import TaskStatusEditor from '@/components/TaskStatusEditor.vue'
import { session } from '@/stores/session'
import { minorToMajor } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'

defineProps<{
  client: AxiosInstance
  notify: Notify
  /**
   * The host router, handed to every module page by the registration wrapper.
   * This page navigates with `router-link`, which resolves against the host's
   * own router, but the prop stays declared so it is not rendered as an
   * attribute on the root element.
   */
  router: Router
}>()

interface SettingRow {
  key: string
  label: string
  value: string
}

const t = useTranslate()

const settings = computed(() => session.settings)

function onOff(value: boolean): string {
  return value ? t('tasks_projects.settings.on') : t('tasks_projects.settings.off')
}

/** The company-wide facts the general card promises: rate, week start, visibility. */
const general = computed<SettingRow[]>(() => [
  {
    key: 'default_rate',
    label: t('tasks_projects.settings.default_rate'),
    value: minorToMajor(settings.value.default_rate),
  },
  {
    key: 'week_start',
    label: t('tasks_projects.settings.week_start'),
    value: t(`tasks_projects.settings.weekday_${settings.value.week_start}`),
  },
  {
    key: 'members_see_all_time',
    label: t('tasks_projects.settings.members_see_all_time'),
    value: onOff(settings.value.members_see_all_time),
  },
])

/**
 * What the company has chosen, read back rather than edited.
 *
 * The scalars are declared in the module's PHP settings schema and edited by
 * the host's generic form, so mirroring the inputs here would give the company
 * two places to write the same value. Showing the current answers still
 * belongs on this page: it is where people come to ask what the module is
 * doing.
 */
const behaviour = computed<SettingRow[]>(() => [
  {
    key: 'rounding_direction',
    label: t('tasks_projects.settings.rounding_direction'),
    value: t(`tasks_projects.settings.rounding_direction_${settings.value.rounding_direction}`),
  },
  {
    key: 'rounding_minutes',
    label: t('tasks_projects.settings.rounding_increment'),
    value: t('tasks_projects.settings.rounding_increment_value', {
      count: settings.value.rounding_minutes,
    }),
  },
  {
    key: 'auto_start_tasks',
    label: t('tasks_projects.settings.auto_start_tasks'),
    value: onOff(settings.value.auto_start_tasks),
  },
  {
    key: 'lock_invoiced_tasks',
    label: t('tasks_projects.settings.lock_invoiced_tasks'),
    value: onOff(settings.value.lock_invoiced_tasks),
  },
  {
    key: 'hide_invoiced_on_board',
    label: t('tasks_projects.settings.hide_invoiced_on_board'),
    value: onOff(settings.value.hide_invoiced_on_board),
  },
])

const invoiceLines = computed<SettingRow[]>(() =>
  (
    [
      'invoice_project_heading',
      'invoice_task_description',
      'invoice_entry_dates',
      'invoice_entry_times',
      'invoice_entry_hours',
      'invoice_entry_descriptions',
    ] as const
  ).map((key) => ({
    key,
    label: t(`tasks_projects.settings.${key}`),
    value: onOff(settings.value[key]),
  })),
)
</script>

<template>
  <div class="space-y-6">
    <BaseSettingCard
      :title="t('tasks_projects.settings.general_title')"
      :description="t('tasks_projects.settings.general_description')"
    >
      <template #action>
        <router-link :to="PATHS.settings">
          <BaseButton variant="primary-outline" size="sm">
            <template #right="slotProps">
              <BaseIcon name="ArrowTopRightOnSquareIcon" :class="slotProps.class" />
            </template>
            {{ t('tasks_projects.settings.open_module_settings') }}
          </BaseButton>
        </router-link>
      </template>

      <dl class="divide-y divide-line-light">
        <div v-for="row in general" :key="row.key" class="flex justify-between gap-4 py-2.5">
          <dt class="text-sm text-muted">{{ row.label }}</dt>
          <dd class="text-sm font-medium text-heading">{{ row.value }}</dd>
        </div>
      </dl>
    </BaseSettingCard>

    <BaseSettingCard
      :title="t('tasks_projects.settings.behaviour_title')"
      :description="t('tasks_projects.settings.behaviour_description')"
    >
      <dl class="divide-y divide-line-light">
        <div v-for="row in behaviour" :key="row.key" class="flex justify-between gap-4 py-2.5">
          <dt class="text-sm text-muted">{{ row.label }}</dt>
          <dd class="text-sm font-medium text-heading">{{ row.value }}</dd>
        </div>
      </dl>
    </BaseSettingCard>

    <BaseSettingCard
      :title="t('tasks_projects.settings.invoice_title')"
      :description="t('tasks_projects.settings.invoice_description')"
    >
      <dl class="divide-y divide-line-light">
        <div v-for="row in invoiceLines" :key="row.key" class="flex justify-between gap-4 py-2.5">
          <dt class="text-sm text-muted">{{ row.label }}</dt>
          <dd class="text-sm font-medium text-heading">{{ row.value }}</dd>
        </div>
      </dl>
    </BaseSettingCard>

    <BaseSettingCard
      :title="t('tasks_projects.settings.statuses_title')"
      :description="t('tasks_projects.settings.statuses_description')"
    >
      <TaskStatusEditor :client="client" :notify="notify" />
    </BaseSettingCard>
  </div>
</template>
