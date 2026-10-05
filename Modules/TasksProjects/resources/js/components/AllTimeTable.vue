<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { TIME_PAGE_SIZE, listTimeEntries } from '@/api/time'
import { ensureTaskNames, taskLabel } from '@/stores/tasks'
import { errorMessage } from '@/support/errors'
import { formatDate, toDateString } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { formatDuration, localDateOf } from '@/support/time'
import type { CompanyMember } from '@/types/member'
import type { TimeEntry, TimeEntryListParams } from '@/types/time-entry'

type NotifyType = 'success' | 'error' | 'warning' | 'info'
type BillingFilter = 'ALL' | 'BILLED' | 'UNBILLED'

interface Option {
  id: number | BillingFilter
  label: string
}

interface TablePagination {
  totalPages: number
  currentPage: number
  totalCount: number
  count: number
  limit: number
}

const props = defineProps<{
  client: AxiosInstance
  notify: (type: NotifyType, message: string) => void
  members: CompanyMember[]
  /**
   * Who and what the screen above is filtered to.
   *
   * The member and project pickers live on the Tasks header now, shared by
   * every view, so the table follows them rather than offering a second pair
   * that could disagree with the first.
   */
  memberId: number | null
  projectId: number | null
  /** Bumped by the page whenever an entry was saved elsewhere. */
  reloadToken: number
}>()

const emit = defineEmits<{
  (event: 'edit', entry: TimeEntry): void
  (event: 'delete', entry: TimeEntry): void
}>()

const t = useTranslate()

const tableRef = ref<{ refresh: (preservePage?: boolean) => void } | null>(null)

const filters = reactive<{
  from: string
  to: string
  billing: BillingFilter
}>({
  from: '',
  to: '',
  billing: 'ALL',
})

const billingOptions = computed<Option[]>(() => [
  { id: 'ALL', label: t('tasks_projects.time.filters.all') },
  { id: 'BILLED', label: t('tasks_projects.time.billed') },
  { id: 'UNBILLED', label: t('tasks_projects.time.unbilled') },
])

const billingOption = computed<Option>({
  get: () => optionFor(billingOptions.value, filters.billing),
  set: (option: Option) => {
    filters.billing = typeof option.id === 'string' ? option.id : 'ALL'
  },
})

const columns = computed(() => [
  { key: 'date', label: t('tasks_projects.time.columns.date'), sortable: false },
  { key: 'member', label: t('tasks_projects.time.columns.member'), sortable: false },
  {
    key: 'task',
    label: t('tasks_projects.time.columns.task'),
    sortable: false,
    tdClass: 'font-medium text-heading',
  },
  { key: 'description', label: t('tasks_projects.time.columns.description'), sortable: false },
  { key: 'duration', label: t('tasks_projects.time.columns.duration'), sortable: false },
  { key: 'billable', label: t('tasks_projects.time.columns.billable'), sortable: false },
  { key: 'amount', label: t('tasks_projects.time.columns.amount'), sortable: false },
  {
    key: 'actions',
    label: t('tasks_projects.general.actions'),
    sortable: false,
    tdClass: 'text-end text-sm font-medium',
  },
])

watch(filters, () => refresh())

watch([() => props.memberId, () => props.projectId], () => refresh())

watch(() => props.reloadToken, () => refresh(true))

function optionFor(options: Option[], id: number | BillingFilter): Option {
  return options.find((option) => option.id === id) ?? options[0]
}

function refresh(preservePage = false): void {
  tableRef.value?.refresh(preservePage)
}

function clearFilters(): void {
  filters.from = ''
  filters.to = ''
  filters.billing = 'ALL'
}

function onFrom(value: string | Date): void {
  filters.from = value ? toDateString(value) : ''
}

function onTo(value: string | Date): void {
  filters.to = value ? toDateString(value) : ''
}

/**
 * Who logged the row. A member who has left the company keeps their entries,
 * so an id with no member is named rather than blanked; an empty member list
 * means the caller may not read them, which is not the same thing.
 */
function memberName(userId: number): string {
  const member = props.members.find((candidate) => candidate.id === userId)

  if (member !== undefined) {
    return member.name
  }

  return props.members.length === 0 ? `#${userId}` : t('tasks_projects.time.unknown_member')
}

async function fetchEntries({ page }: { page: number }): Promise<{
  data: TimeEntry[]
  pagination: TablePagination
}> {
  const params: TimeEntryListParams = { page, limit: TIME_PAGE_SIZE }

  if (props.memberId !== null) {
    params.user_id = props.memberId
  }

  if (props.projectId !== null) {
    params.project_id = props.projectId
  }

  if (filters.from !== '') {
    params.from = filters.from
  }

  if (filters.to !== '') {
    params.to = filters.to
  }

  if (filters.billing !== 'ALL') {
    params.billed = filters.billing === 'BILLED'
  }

  try {
    const response = await listTimeEntries(props.client, params)
    const rows = response.data ?? []

    void ensureTaskNames(
      props.client,
      rows.map((entry) => entry.task_id).filter((id): id is number => typeof id === 'number'),
    )

    return { data: rows, pagination: paginationOf(response.meta, rows.length) }
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.time.load_failed')))

    return { data: [], pagination: paginationOf(null, 0) }
  }
}

function paginationOf(
  meta: { last_page: number; current_page: number; total: number; per_page: number } | null,
  count: number,
): TablePagination {
  return {
    totalPages: meta?.last_page ?? 1,
    currentPage: meta?.current_page ?? 1,
    totalCount: meta?.total ?? count,
    count,
    limit: meta?.per_page ?? TIME_PAGE_SIZE,
  }
}
</script>

<template>
  <section>
    <BaseFilterWrapper show row-on-xl class="mt-3" @clear="clearFilters">
      <BaseInputGroup :label="t('tasks_projects.time.filters.from')" class="mt-2 flex-1">
        <BaseDatePicker :model-value="filters.from" @update:model-value="onFrom" />
      </BaseInputGroup>

      <BaseInputGroup :label="t('tasks_projects.time.filters.to')" class="mt-2 flex-1">
        <BaseDatePicker :model-value="filters.to" @update:model-value="onTo" />
      </BaseInputGroup>

      <BaseInputGroup :label="t('tasks_projects.time.filters.billing')" class="mt-2 flex-1">
        <BaseSelectInput v-model="billingOption" :options="billingOptions" label-key="label" />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <div class="relative table-container">
      <BaseTable ref="tableRef" :data="fetchEntries" :columns="columns" class="mt-3">
        <template #cell-date="{ row }">
          {{ formatDate(localDateOf(row.data.started_at)) }}
        </template>

        <template #cell-member="{ row }">
          {{ memberName(row.data.user_id) }}
        </template>

        <template #cell-task="{ row }">
          {{ taskLabel(row.data.task_id) }}
        </template>

        <template #cell-description="{ row }">
          <span class="block max-w-64 truncate">{{ row.data.description || '-' }}</span>
        </template>

        <template #cell-duration="{ row }">
          <span class="tabular-nums">{{ formatDuration(row.data.duration_minutes) }}</span>
        </template>

        <template #cell-billable="{ row }">
          <BaseBadge
            class="rounded-full"
            :class="
              row.data.billable
                ? 'bg-primary-50! text-primary-500!'
                : 'bg-surface-tertiary! text-muted!'
            "
          >
            {{
              row.data.billable
                ? t('tasks_projects.time.billable')
                : t('tasks_projects.time.non_billable')
            }}
          </BaseBadge>
        </template>

        <template #cell-amount="{ row }">
          <BaseFormatMoney v-if="row.data.billable" :amount="row.data.amount" />
          <span v-else class="text-subtle">-</span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown :label="$t('tasks_projects.general.actions')">
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-muted" />
            </template>

            <BaseDropdownItem @click="emit('edit', row.data)">
              <BaseIcon name="PencilIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.edit') }}
            </BaseDropdownItem>

            <BaseDropdownItem v-if="row.data.invoice_id === null" @click="emit('delete', row.data)">
              <BaseIcon name="TrashIcon" class="me-3 h-5 w-5 text-subtle group-hover:text-muted" />
              {{ t('tasks_projects.general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </section>
</template>
