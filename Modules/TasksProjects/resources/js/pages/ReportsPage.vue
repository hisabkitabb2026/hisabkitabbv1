<script setup lang="ts">
import EmptyArt from '@/components/EmptyArt.vue'
import { computed, onMounted, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { listMembers } from '@/api'
import { fetchReportSummary } from '@/api/reports'
import ReportBreakdownTable from '@/components/ReportBreakdownTable.vue'
import { customerName, ensureLoaded } from '@/stores/customers'
import { session } from '@/stores/session'
import { errorMessage } from '@/support/errors'
import { formatDate, toDateString } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import { rangeFor, shareOf } from '@/support/reports'
import type { RangePreset } from '@/support/reports'
import { formatDuration } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { BreakdownRow, ReportMemberRow, ReportSummary, ReportTotals } from '@/types/reports'

interface PresetOption {
  id: RangePreset
  label: string
}

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  /** The host router. Links here go through `<router-link>`, which uses it. */
  router: Router
}>()

/** Where the report opens, and what the "clear" button goes back to. */
const DEFAULT_PRESET: RangePreset = 'THIS_MONTH'

const t = useTranslate()

const summary = ref<ReportSummary | null>(null)
const members = ref<CompanyMember[]>([])
const loading = ref(true)
const preset = ref<RangePreset>(DEFAULT_PRESET)
const from = ref('')
const to = ref('')

const presetOptions = computed<PresetOption[]>(() => [
  { id: 'THIS_WEEK', label: t('tasks_projects.reports.range.this_week') },
  { id: 'THIS_MONTH', label: t('tasks_projects.reports.range.this_month') },
  { id: 'LAST_MONTH', label: t('tasks_projects.reports.range.last_month') },
  { id: 'THIS_QUARTER', label: t('tasks_projects.reports.range.this_quarter') },
  { id: 'THIS_YEAR', label: t('tasks_projects.reports.range.this_year') },
  { id: 'CUSTOM', label: t('tasks_projects.reports.range.custom') },
])

const totals = computed<ReportTotals[]>(() => summary.value?.totals ?? [])

/** A second currency in the report earns a label on every figure that has one. */
const showCurrency = computed(() => new Set(totals.value.map((row) => row.currency_id)).size > 1)

const hasData = computed(() => totals.value.length > 0)

const projectRows = computed<BreakdownRow[]>(() =>
  (summary.value?.by_project ?? []).map((row) => ({
    ...row,
    id: `${row.project_id ?? 'none'}-${row.currency_id ?? 'base'}`,
    label:
      row.project_id === null
        ? t('tasks_projects.reports.tables.no_project')
        : row.label || `#${row.project_id}`,
  })),
)

const memberRows = computed<BreakdownRow[]>(() =>
  (summary.value?.by_member ?? []).map((row) => ({
    ...row,
    id: `${row.user_id ?? 'none'}-${row.currency_id ?? 'base'}`,
    label: memberLabel(row),
  })),
)

const customerRows = computed<BreakdownRow[]>(() =>
  (summary.value?.by_customer ?? []).map((row) => ({
    ...row,
    id: `${row.customer_id ?? 'none'}-${row.currency_id ?? 'base'}`,
    label:
      row.customer_id === null
        ? t('tasks_projects.reports.tables.no_customer')
        : customerName(row.customer_id),
  })),
)

/**
 * The billable split, in minutes across every currency.
 *
 * Minutes are the one figure that adds up across currencies, which is why the
 * bar charts time rather than money.
 */
const billableMinutes = computed(() => minutesWhere(true))

const nonBillableMinutes = computed(() => minutesWhere(false))

const splitTotal = computed(() => billableMinutes.value + nonBillableMinutes.value)

const billableShare = computed(() => shareOf(billableMinutes.value, splitTotal.value))

onMounted(() => {
  applyPreset(DEFAULT_PRESET)
  void loadMembers()
})

function applyPreset(next: RangePreset): void {
  preset.value = next

  if (next !== 'CUSTOM') {
    const range = rangeFor(next, session.settings.week_start)

    from.value = range.from
    to.value = range.to
  }

  void load()
}

/** A date picked by hand puts the range on Custom and leaves it there. */
function onFrom(value: string | Date): void {
  from.value = value ? toDateString(value) : ''
  preset.value = 'CUSTOM'
  void load()
}

function onTo(value: string | Date): void {
  to.value = value ? toDateString(value) : ''
  preset.value = 'CUSTOM'
  void load()
}

async function load(): Promise<void> {
  loading.value = true

  try {
    const report = await fetchReportSummary(props.client, rangeParams())

    summary.value = report

    // Only a report that names a contact is worth one lookup of the address book.
    if (report.by_customer.some((row) => row.customer_id !== null)) {
      void ensureLoaded(props.client)
    }
  } catch (error: unknown) {
    summary.value = null
    props.notify('error', errorMessage(error, t('tasks_projects.reports.load_failed')))
  } finally {
    loading.value = false
  }
}

/** Only the ends that are set: an empty one lets the server pick its default. */
function rangeParams(): { from?: string; to?: string } {
  const params: { from?: string; to?: string } = {}

  if (from.value !== '') {
    params.from = from.value
  }

  if (to.value !== '') {
    params.to = to.value
  }

  return params
}

async function loadMembers(): Promise<void> {
  try {
    members.value = await listMembers(props.client)
  } catch {
    // Reading members needs view-project; the rows keep the server's labels.
  }
}

/**
 * What a member is called: the company's own member list first, then the
 * label the report carried, which reads "Removed member" for a stale id.
 */
function memberLabel(row: ReportMemberRow): string {
  const known = members.value.find((member) => member.id === row.user_id)?.name ?? ''

  if (known !== '') {
    return known
  }

  if (row.label !== '') {
    return row.label
  }

  return row.user_id === null
    ? t('tasks_projects.reports.tables.unknown_member')
    : `#${row.user_id}`
}

function minutesWhere(billable: boolean): number {
  return (summary.value?.by_billable ?? [])
    .filter((row) => row.billable === billable)
    .reduce((carry, row) => carry + row.minutes, 0)
}

function currencyLabel(currencyId: number | null): string {
  return currencyId === null
    ? t('tasks_projects.reports.summary.base_currency')
    : t('tasks_projects.reports.summary.currency', { id: currencyId })
}

function presetClass(option: PresetOption): string {
  return preset.value === option.id
    ? 'border-primary-500 bg-primary-50 text-primary-500'
    : 'border-line-default bg-surface text-muted hover:text-heading'
}

function clearRange(): void {
  applyPreset(DEFAULT_PRESET)
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="t('tasks_projects.reports.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.tasks.title')" :to="PATHS.tasks" />
        <BaseBreadcrumbItem :title="t('tasks_projects.reports.title')" to="#" active />
      </BaseBreadcrumb>

      <p v-if="summary" class="mt-2 text-sm text-muted">
        {{ formatDate(summary.from) }} &ndash; {{ formatDate(summary.to) }}
      </p>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <router-link :to="PATHS.tasks">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ClipboardDocumentListIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.tasks.title') }}
            </BaseButton>
          </router-link>

          <router-link :to="PATHS.projects">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="FolderIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.projects.title') }}
            </BaseButton>
          </router-link>

          <router-link :to="PATHS.billing">
            <BaseButton variant="primary-outline">
              <template #left="slotProps">
                <BaseIcon name="BanknotesIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.billing.title') }}
            </BaseButton>
          </router-link>
        </div>
      </template>
    </BasePageHeader>

    <div class="mt-4 flex flex-wrap gap-2">
      <button
        v-for="option in presetOptions"
        :key="option.id"
        type="button"
        class="rounded-md border px-3 py-1.5 text-sm font-medium"
        :class="presetClass(option)"
        @click="applyPreset(option.id)"
      >
        {{ option.label }}
      </button>
    </div>

    <BaseFilterWrapper :show="true" row-on-xl class="mt-3" @clear="clearRange">
      <BaseInputGroup :label="t('tasks_projects.reports.range.from')" class="mt-2 flex-1">
        <BaseDatePicker :model-value="from" @update:model-value="onFrom" />
      </BaseInputGroup>

      <BaseInputGroup :label="t('tasks_projects.reports.range.to')" class="mt-2 flex-1">
        <BaseDatePicker :model-value="to" @update:model-value="onTo" />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <div v-if="loading && summary === null" class="flex justify-center py-16">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
    </div>

    <BaseEmptyPlaceholder
      v-else-if="!hasData"
      :ghost="4"
      :title="t('tasks_projects.reports.empty_title')"
      :description="t('tasks_projects.reports.empty_description')"
    >
      <EmptyArt name="report" />
    </BaseEmptyPlaceholder>

    <template v-else>
      <div
        v-for="row in totals"
        :key="row.currency_id ?? 'base'"
        class="mt-4 rounded-xl border border-line-default bg-surface p-5"
      >
        <p v-if="showCurrency" class="text-xs font-medium tracking-wider text-muted uppercase">
          {{ currencyLabel(row.currency_id) }}
        </p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4" :class="showCurrency ? 'mt-3' : ''">
          <div>
            <p class="text-xs font-medium tracking-wider text-muted uppercase">
              {{ t('tasks_projects.reports.summary.logged') }}
            </p>
            <p class="mt-1 text-2xl font-semibold text-heading">
              {{ formatDuration(row.minutes) }}
            </p>
          </div>

          <div>
            <p class="text-xs font-medium tracking-wider text-muted uppercase">
              {{ t('tasks_projects.reports.summary.billable') }}
            </p>
            <p class="mt-1 text-2xl font-semibold text-heading">
              {{ formatDuration(row.billable_minutes) }}
            </p>
          </div>

          <div>
            <p class="text-xs font-medium tracking-wider text-muted uppercase">
              {{ t('tasks_projects.reports.summary.amount') }}
            </p>
            <p class="mt-1 text-2xl font-semibold text-heading">
              <BaseFormatMoney :amount="row.amount" />
            </p>
          </div>

          <div>
            <p class="text-xs font-medium tracking-wider text-muted uppercase">
              {{ t('tasks_projects.reports.summary.unbilled') }}
            </p>
            <p class="mt-1 text-2xl font-semibold text-heading">
              <BaseFormatMoney :amount="row.unbilled_amount" />
            </p>
          </div>
        </div>
      </div>

      <section class="mt-4 rounded-xl border border-line-default bg-surface p-5">
        <p class="text-xs font-medium tracking-wider text-muted uppercase">
          {{ t('tasks_projects.reports.split.title') }}
        </p>

        <template v-if="splitTotal > 0">
          <div class="mt-3 flex h-2 w-full overflow-hidden rounded-full bg-surface-tertiary">
            <div class="h-2 bg-primary-500" :style="{ width: `${billableShare}%` }" />
          </div>

          <div class="mt-3 flex flex-wrap gap-6 text-sm">
            <span class="inline-flex items-center text-body">
              <span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-primary-500" />
              {{ t('tasks_projects.reports.split.billable') }}:
              <span class="ms-1 font-medium text-heading">
                {{ formatDuration(billableMinutes) }}
              </span>
              <span class="ms-1 text-muted">({{ billableShare }}%)</span>
            </span>

            <span class="inline-flex items-center text-body">
              <span class="me-2 inline-block h-2.5 w-2.5 rounded-full bg-surface-tertiary" />
              {{ t('tasks_projects.reports.split.non_billable') }}:
              <span class="ms-1 font-medium text-heading">
                {{ formatDuration(nonBillableMinutes) }}
              </span>
              <span class="ms-1 text-muted">({{ 100 - billableShare }}%)</span>
            </span>
          </div>
        </template>

        <p v-else class="mt-2 text-sm text-subtle">
          {{ t('tasks_projects.reports.split.nothing') }}
        </p>
      </section>

      <ReportBreakdownTable
        :title="t('tasks_projects.reports.tables.by_project')"
        :label-heading="t('tasks_projects.reports.tables.project')"
        :rows="projectRows"
        :show-currency="showCurrency"
      />

      <ReportBreakdownTable
        :title="t('tasks_projects.reports.tables.by_member')"
        :label-heading="t('tasks_projects.reports.tables.member')"
        :rows="memberRows"
        :show-currency="showCurrency"
      />

      <ReportBreakdownTable
        :title="t('tasks_projects.reports.tables.by_customer')"
        :label-heading="t('tasks_projects.reports.tables.customer')"
        :rows="customerRows"
        :show-currency="showCurrency"
      />
    </template>
  </BasePage>
</template>
