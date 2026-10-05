<script setup lang="ts">
import EmptyArt from '@/components/EmptyArt.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { fetchUnbilledTime, listUnbilledCustomers } from '@/api/billing'
import type { UnbilledRange } from '@/api/billing'
import InvoiceRetryBanner from '@/components/InvoiceRetryBanner.vue'
import { customerName, ensureLoaded } from '@/stores/customers'
import { invoicingStore } from '@/stores/invoicing'
import { errorMessage } from '@/support/errors'
import { formatDate, formatMinutes, toDateString } from '@/support/format'
import { useTranslate } from '@/support/i18n'
import { invoiceTasks } from '@/support/invoicing'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import type {
  BillingGrouping,
  UnbilledCustomer,
  UnbilledEntry,
  UnbilledGroup,
  UnbilledTime,
} from '@/types/billing'

/**
 * Unbilled time, read the other way round.
 *
 * Invoicing normally starts where the work is: a task row, a task page, a
 * project. This screen exists for the month-end question those cannot answer,
 * "who owes me for what, across everything", and it is the only place a single
 * entry can be left off an invoice. It is linked from Reports and the Projects
 * header rather than the sidebar, because it is a report with a button, not a
 * place to live.
 */

interface GroupingOption {
  id: BillingGrouping
  label: string
}

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  /** The host router: the sequence navigates to the created invoice. */
  router: Router
}>()

const GROUPINGS: BillingGrouping[] = ['task', 'project', 'member', 'summary']

const t = useTranslate()

const loading = ref(true)
const customers = ref<UnbilledCustomer[]>([])
const range = reactive<{ from: string; to: string }>({ from: '', to: '' })

const chosen = ref<UnbilledCustomer | null>(null)
const unbilled = ref<UnbilledTime | null>(null)
const loadingEntries = ref(false)
const grouping = ref<BillingGrouping>('task')
const selected = ref<number[]>([])

const busy = computed<boolean>(() => invoicingStore.busy)

const groupingOptions = computed<GroupingOption[]>(() =>
  GROUPINGS.map((id) => ({ id, label: t(`tasks_projects.billing.entries.group_by.${id}`) })),
)

const groupingOption = computed<GroupingOption>({
  get: () =>
    groupingOptions.value.find((option) => option.id === grouping.value) ?? groupingOptions.value[0],
  set: (option: GroupingOption) => {
    grouping.value = option.id
  },
})

/**
 * The entries this screen is about.
 *
 * A card names a customer *and* a currency, because money in two denominations
 * cannot be added up and an invoice is written in one. So the screen narrows to
 * the currency that was clicked; the grouped rows already arrive split the same
 * way, which is what keeps the selection something `prepare` will accept.
 */
const visibleEntries = computed<UnbilledEntry[]>(() =>
  (unbilled.value?.entries ?? []).filter(
    (entry) => entry.currency_id === (chosen.value?.currency_id ?? null),
  ),
)

const entryById = computed<Record<number, UnbilledEntry>>(() => {
  const map: Record<number, UnbilledEntry> = {}

  for (const entry of visibleEntries.value) {
    map[entry.id] = entry
  }

  return map
})

const groups = computed<UnbilledGroup[]>(() =>
  (unbilled.value?.groups?.[grouping.value] ?? []).filter(
    (group) => group.currency_id === (chosen.value?.currency_id ?? null),
  ),
)

const totalEntries = computed<number>(() => visibleEntries.value.length)

const allSelected = computed<boolean>(
  () => totalEntries.value > 0 && selected.value.length === totalEntries.value,
)

const selectedMinutes = computed<number>(() =>
  selected.value.reduce((sum, id) => sum + (entryById.value[id]?.minutes ?? 0), 0),
)

const selectedAmount = computed<number>(() =>
  selected.value.reduce((sum, id) => sum + (entryById.value[id]?.amount ?? 0), 0),
)

const rangeParams = computed<UnbilledRange>(() => {
  const params: UnbilledRange = {}

  if (range.from !== '') {
    params.from = range.from
  }

  if (range.to !== '') {
    params.to = range.to
  }

  return params
})

watch(() => [range.from, range.to], () => void reload())

onMounted(() => void load())

async function load(): Promise<void> {
  loading.value = true

  await Promise.all([loadCustomers(), ensureLoaded(props.client)])

  loading.value = false
}

/** A new range re-reads the cards, and the open customer's rows with them. */
async function reload(): Promise<void> {
  await loadCustomers()

  if (chosen.value !== null) {
    await openCustomer(chosen.value)
  }
}

async function loadCustomers(): Promise<void> {
  try {
    customers.value = await listUnbilledCustomers(props.client, rangeParams.value)
  } catch (error: unknown) {
    customers.value = []
    props.notify('error', errorMessage(error, t('tasks_projects.billing.customer.load_failed')))
  }
}

function label(customerId: number): string {
  return customerName(customerId)
}

function clearRange(): void {
  range.from = ''
  range.to = ''
}

function onFrom(value: string | Date): void {
  range.from = value ? toDateString(value) : ''
}

function onTo(value: string | Date): void {
  range.to = value ? toDateString(value) : ''
}

async function openCustomer(customer: UnbilledCustomer): Promise<void> {
  chosen.value = customer
  loadingEntries.value = true
  unbilled.value = null
  selected.value = []

  try {
    unbilled.value = await fetchUnbilledTime(props.client, customer.customer_id, rangeParams.value)
    selected.value = visibleEntries.value.map((entry) => entry.id)
  } catch (error: unknown) {
    props.notify('error', errorMessage(error, t('tasks_projects.billing.entries.load_failed')))
  } finally {
    loadingEntries.value = false
  }
}

function back(): void {
  chosen.value = null
  unbilled.value = null
  selected.value = []
  void loadCustomers()
}

function isSelected(entryId: number): boolean {
  return selected.value.includes(entryId)
}

function toggleEntry(entryId: number): void {
  selected.value = isSelected(entryId)
    ? selected.value.filter((id) => id !== entryId)
    : [...selected.value, entryId]
}

function toggleAll(): void {
  selected.value = allSelected.value ? [] : visibleEntries.value.map((entry) => entry.id)
}

function groupSelected(group: UnbilledGroup): boolean {
  return group.entry_ids.length > 0 && group.entry_ids.every((id) => isSelected(id))
}

function toggleGroup(group: UnbilledGroup): void {
  if (groupSelected(group)) {
    selected.value = selected.value.filter((id) => !group.entry_ids.includes(id))

    return
  }

  const missing = group.entry_ids.filter((id) => !isSelected(id))
  selected.value = [...selected.value, ...missing]
}

/** Entries in the order the group lists them, for the rows under its header. */
function groupEntries(group: UnbilledGroup): UnbilledEntry[] {
  return group.entry_ids
    .map((id) => entryById.value[id])
    .filter((entry): entry is UnbilledEntry => entry !== undefined)
}

/**
 * Hand the ticked entries to the same sequence every other screen runs.
 *
 * The grouping is the one thing this screen knows that a task row does not, so
 * it is the only argument that travels with the ids.
 */
async function createInvoice(): Promise<void> {
  if (selected.value.length === 0) {
    props.notify('warning', t('tasks_projects.billing.entries.none_selected'))

    return
  }

  const created = await invoiceTasks(
    { client: props.client, router: props.router, notify: props.notify, t },
    { entryIds: [...selected.value], grouping: grouping.value },
  )

  if (!created) {
    await reload()
  }
}
</script>

<template>
  <BasePage>
    <BasePageHeader :title="t('tasks_projects.billing.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('tasks_projects.general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="t('tasks_projects.tasks.title')" :to="PATHS.tasks" />
        <BaseBreadcrumbItem :title="t('tasks_projects.billing.title')" to="#" active />
      </BaseBreadcrumb>

      <p class="mt-2 text-sm text-muted">{{ t('tasks_projects.billing.subtitle') }}</p>

      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-3">
          <router-link :to="PATHS.reports">
            <BaseButton variant="white">
              <template #left="slotProps">
                <BaseIcon name="ChartBarIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.reports.title') }}
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
        </div>
      </template>
    </BasePageHeader>

    <InvoiceRetryBanner :client="client" :notify="notify" />

    <div class="mt-4 flex flex-wrap items-end gap-4">
      <BaseInputGroup :label="t('tasks_projects.billing.customer.from')" class="w-full sm:w-48">
        <BaseDatePicker :model-value="range.from" @update:model-value="onFrom" />
      </BaseInputGroup>

      <BaseInputGroup :label="t('tasks_projects.billing.customer.to')" class="w-full sm:w-48">
        <BaseDatePicker :model-value="range.to" @update:model-value="onTo" />
      </BaseInputGroup>

      <BaseButton
        v-if="range.from !== '' || range.to !== ''"
        variant="primary-outline"
        @click="clearRange"
      >
        {{ t('tasks_projects.billing.customer.clear_range') }}
      </BaseButton>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <BaseSpinner class="h-8 w-8 text-primary-500" />
    </div>

    <!-- Who has time waiting -->
    <section v-else-if="chosen === null" class="mt-6">
      <h2 class="text-base font-semibold text-heading">
        {{ t('tasks_projects.billing.customer.title') }}
      </h2>
      <p class="mt-1 text-sm text-muted">
        {{ t('tasks_projects.billing.customer.description') }}
      </p>

      <div
        v-if="customers.length > 0"
        class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
      >
        <button
          v-for="row in customers"
          :key="`${row.customer_id}-${row.currency_id ?? 'none'}`"
          type="button"
          class="rounded-xl border border-line-default bg-surface p-5 text-start transition hover:border-primary-500"
          @click="openCustomer(row)"
        >
          <p class="text-sm font-semibold text-heading">{{ label(row.customer_id) }}</p>
          <p class="mt-1 text-xs text-muted">
            {{ t('tasks_projects.billing.customer.entries', { count: row.entries }) }}
            &middot;
            {{ formatMinutes(row.minutes) }}
          </p>
          <p class="mt-3 text-xl font-semibold text-heading">
            <BaseFormatMoney :amount="row.amount" />
          </p>
        </button>
      </div>

      <BaseEmptyPlaceholder
        v-else
        :ghost="3"
        :title="t('tasks_projects.billing.customer.empty_title')"
        :description="t('tasks_projects.billing.customer.empty_description')"
      >
        <EmptyArt name="time" />
      </BaseEmptyPlaceholder>
    </section>

    <!-- Which entries of that customer -->
    <section v-else class="mt-6">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 class="text-base font-semibold text-heading">
            {{ t('tasks_projects.billing.entries.title') }}
          </h2>
          <p class="mt-1 text-sm text-muted">{{ label(chosen.customer_id) }}</p>
        </div>

        <BaseInputGroup :label="t('tasks_projects.billing.entries.grouping')" class="w-full sm:w-56">
          <BaseSelectInput v-model="groupingOption" :options="groupingOptions" label-key="label" />
        </BaseInputGroup>
      </div>

      <div v-if="loadingEntries" class="flex justify-center py-16">
        <BaseSpinner class="h-8 w-8 text-primary-500" />
      </div>

      <template v-else-if="totalEntries > 0">
        <div
          class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line-default bg-surface-secondary px-4 py-3"
        >
          <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-heading">
            <input
              type="checkbox"
              class="h-4 w-4 cursor-pointer rounded border-line-strong"
              :checked="allSelected"
              @change="toggleAll"
            >
            {{ t('tasks_projects.billing.entries.select_all') }}
          </label>

          <p class="text-sm text-muted">
            {{
              t('tasks_projects.billing.entries.selected', {
                count: selected.length,
                total: totalEntries,
              })
            }}
          </p>
        </div>

        <div
          v-for="group in groups"
          :key="`${group.label}-${group.key ?? 'none'}-${group.currency_id ?? 'none'}`"
          class="mt-4 overflow-hidden rounded-xl border border-line-default"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-3 bg-surface-secondary px-4 py-3"
          >
            <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-heading">
              <input
                type="checkbox"
                class="h-4 w-4 cursor-pointer rounded border-line-strong"
                :checked="groupSelected(group)"
                @change="toggleGroup(group)"
              >
              {{ group.label }}
            </label>

            <p class="text-sm text-muted">
              {{ formatMinutes(group.minutes) }}
              &middot;
              <BaseFormatMoney :amount="group.amount" />
            </p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto">
              <thead class="bg-surface text-xs tracking-wider text-muted uppercase">
                <tr>
                  <th class="w-10" />
                  <th class="px-4 py-2 text-start font-medium">
                    {{ t('tasks_projects.billing.entries.columns.date') }}
                  </th>
                  <th class="px-4 py-2 text-start font-medium">
                    {{ t('tasks_projects.billing.entries.columns.task') }}
                  </th>
                  <th class="px-4 py-2 text-start font-medium">
                    {{ t('tasks_projects.billing.entries.columns.project') }}
                  </th>
                  <th class="px-4 py-2 text-start font-medium">
                    {{ t('tasks_projects.billing.entries.columns.member') }}
                  </th>
                  <th class="px-4 py-2 text-end font-medium">
                    {{ t('tasks_projects.billing.entries.columns.duration') }}
                  </th>
                  <th class="px-4 py-2 text-end font-medium">
                    {{ t('tasks_projects.billing.entries.columns.amount') }}
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-line-default bg-surface text-sm">
                <tr v-for="entry in groupEntries(group)" :key="entry.id">
                  <td class="ps-4">
                    <input
                      type="checkbox"
                      class="h-4 w-4 cursor-pointer rounded border-line-strong"
                      :aria-label="t('tasks_projects.general.select_named', { name: entry.description || formatDate(entry.date) })"
                      :checked="isSelected(entry.id)"
                      @change="toggleEntry(entry.id)"
                    >
                  </td>
                  <td class="px-4 py-2 whitespace-nowrap text-muted">
                    {{ formatDate(entry.date) }}
                  </td>
                  <td class="px-4 py-2">
                    <router-link class="text-heading hover:text-primary-500" :to="PATHS.task(entry.task_id)">
                      {{ entry.task_name }}
                    </router-link>
                    <span class="block text-xs text-subtle">
                      {{ entry.description || t('tasks_projects.billing.entries.no_description') }}
                    </span>
                  </td>
                  <td class="px-4 py-2 text-muted">{{ entry.project_name ?? '-' }}</td>
                  <td class="px-4 py-2 text-muted">{{ entry.user_name }}</td>
                  <td class="px-4 py-2 text-end whitespace-nowrap text-muted">
                    {{ formatMinutes(entry.minutes) }}
                  </td>
                  <td class="px-4 py-2 text-end whitespace-nowrap text-heading">
                    <BaseFormatMoney :amount="entry.amount" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
          <p class="text-sm font-medium text-heading">
            {{
              t('tasks_projects.billing.entries.selected_total', {
                hours: formatMinutes(selectedMinutes),
              })
            }}
            &middot;
            <BaseFormatMoney :amount="selectedAmount" />
          </p>

          <div class="flex items-center gap-3">
            <BaseButton variant="primary-outline" :disabled="busy" @click="back">
              {{ t('tasks_projects.billing.back') }}
            </BaseButton>

            <BaseButton
              variant="primary"
              :loading="busy"
              :disabled="busy || selected.length === 0"
              @click="createInvoice"
            >
              <template #left="slotProps">
                <BaseIcon v-if="!busy" name="DocumentPlusIcon" :class="slotProps.class" />
              </template>
              {{ t('tasks_projects.billing.create') }}
            </BaseButton>
          </div>
        </div>
      </template>

      <BaseEmptyPlaceholder
        v-else
        :ghost="5"
        :title="t('tasks_projects.billing.entries.empty_title')"
        :description="t('tasks_projects.billing.entries.empty_description')"
      >
        <EmptyArt name="time" />

        <template #actions>
          <BaseButton variant="primary" @click="back">
            {{ t('tasks_projects.billing.back') }}
          </BaseButton>
        </template>
      </BaseEmptyPlaceholder>
    </section>
  </BasePage>
</template>
