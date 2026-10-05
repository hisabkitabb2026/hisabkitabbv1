<script setup lang="ts">
import { computed } from 'vue'
import { useTranslate } from '@/support/i18n'
import { formatDuration } from '@/support/time'
import type { BreakdownRow } from '@/types/reports'

const props = defineProps<{
  title: string
  /** What the first column is called: Project, Member or Customer. */
  labelHeading: string
  rows: BreakdownRow[]
  /** Set when the report spans more than one currency, which earns a column. */
  showCurrency: boolean
}>()

const t = useTranslate()

/**
 * The table reads a local array, so it sorts in the browser: these are the
 * handful of rows one range produced, not a page of a larger list.
 *
 * `BaseTable` snapshots its columns when it mounts, so the currency column
 * appearing later needs the table itself to be rebuilt, which the key below
 * does.
 */
const columns = computed(() => [
  {
    key: 'label',
    label: props.labelHeading,
    thClass: 'extra',
    tdClass: 'font-medium text-heading',
  },
  ...(props.showCurrency
    ? [{ key: 'currency_id', label: t('tasks_projects.reports.tables.currency') }]
    : []),
  { key: 'minutes', label: t('tasks_projects.reports.tables.logged'), dataType: 'numeric' },
  {
    key: 'billable_minutes',
    label: t('tasks_projects.reports.tables.billable'),
    dataType: 'numeric',
  },
  { key: 'amount', label: t('tasks_projects.reports.tables.amount'), dataType: 'numeric' },
  {
    key: 'unbilled_amount',
    label: t('tasks_projects.reports.tables.unbilled'),
    dataType: 'numeric',
  },
])

const tableKey = computed(() => (props.showCurrency ? 'currency' : 'plain'))
</script>

<template>
  <section class="mt-6">
    <h3 class="text-sm font-semibold tracking-wider text-muted uppercase">{{ title }}</h3>

    <div class="relative table-container">
      <BaseTable :key="tableKey" :data="rows" :columns="columns" class="mt-2">
        <template #cell-currency_id="{ row }">
          <span v-if="row.data.currency_id === null" class="text-subtle">-</span>
          <span v-else>#{{ row.data.currency_id }}</span>
        </template>

        <template #cell-minutes="{ row }">{{ formatDuration(row.data.minutes) }}</template>

        <template #cell-billable_minutes="{ row }">
          {{ formatDuration(row.data.billable_minutes) }}
        </template>

        <template #cell-amount="{ row }">
          <BaseFormatMoney :amount="row.data.amount" />
        </template>

        <template #cell-unbilled_amount="{ row }">
          <BaseFormatMoney :amount="row.data.unbilled_amount" />
        </template>
      </BaseTable>
    </div>
  </section>
</template>
