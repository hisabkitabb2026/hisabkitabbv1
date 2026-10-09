<template>
  <BasePage>
    <!-- HisabKitab feature -->
    <BasePageHeader :help="$t('page_help.estimates')" :title="$t('estimates.estimate', 2)">
      <template v-if="estimateViews.length > 1" #title-suffix>
        <BaseViewSwitcher
          :model-value="viewMode"
          primary-value="estimates"
          :label="$t('estimates.estimate', 2)"
          :options="estimateViews"
          @update:model-value="setViewMode"
        />
      </template>
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('estimates.estimate', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="estimateStore.totalEstimateCount"
          variant="primary-outline"
          :aria-expanded="showFilters"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              :class="slotProps.class"
              name="FunnelIcon"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link v-if="canCreate" :to="newEstimateLink" class="inline-flex rounded-lg ms-4">
          <BaseButton tag="span" variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            <!-- HisabKitab feature - label from registered view mode -->
            {{ currentDocMeta ? `New ${currentDocMeta.label}` : $t('estimates.new_estimate') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="$t('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('estimates.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          searchable
          :placeholder="$t('general.select_a_status')"
          @update:model-value="setActiveTab"
          @remove="clearStatusSearch()"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <div
        class="hidden w-4 h-px mb-5 shrink-0 bg-line-strong xl:block"
      />

      <BaseInputGroup :label="$t('general.to')">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('estimates.estimate_number')">
        <BaseInput v-model="filters.estimate_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Empty State -->
    <!-- HisabKitab feature -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      art="estimate"
      :ghost="6"
      :title="currentDocMeta ? `No ${currentDocMeta.labelPlural.toLowerCase()} yet` : $t('estimates.no_estimates')"
      :description="currentDocMeta ? `Create a ${currentDocMeta.label.toLowerCase()} with station-wise capacity rates.` : $t('estimates.empty_description')"
    >
      <template v-if="canCreate" #actions>
        <BaseButton
          variant="primary"
          @click="$router.push(newEstimateLink)"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          <!-- HisabKitab feature - label from registered view mode -->
          {{ currentDocMeta ? `New ${currentDocMeta.label}` : $t('estimates.add_new_estimate') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Table -->
    <div v-show="!showEmptyScreen" class="relative flex flex-col gap-4 table-container">
      <BaseTabGroup @change="setStatusFilter">
        <BaseTab :title="$t('general.all')" filter="" />
        <BaseTab :title="$t('general.draft')" filter="DRAFT" />
        <BaseTab :title="$t('general.sent')" filter="SENT" />
      </BaseTabGroup>

      <BaseTable
        ref="tableRef"
        :key="tableKey"
        :no-results-message="$t('estimates.no_matching_estimates')"
        :data="fetchData"
        :columns="estimateColumns"
        :placeholder-count="estimateStore.totalEstimateCount >= 20 ? 10 : 5"
        :row-to="estimateLink"
        :selected-count="canDelete ? estimateStore.selectedEstimates.length : 0"
      >
        <template #bulk-actions>
          <BaseButton size="xs" variant="white" @click="removeMultipleEstimates">
            <template #left="slotProps">
              <BaseIcon name="TrashIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.delete') }}
          </BaseButton>
        </template>

        <template #header>
          <div class="absolute items-center start-6 top-3.5 select-none">
            <BaseCheckbox
              v-model="estimateStore.selectAllField"
              :aria-label="$t('general.select_all')"
              variant="primary"
              @change="estimateStore.selectAllEstimates"
            />
          </div>
        </template>

        <template #cell-checkbox="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :aria-label="$t('general.select_named', { name: row.data.estimate_number })"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-estimate_date="{ row }">
          {{ row.data.formatted_estimate_date }}
        </template>

        <template #cell-estimate_number="{ row }">
          <router-link
            :to="{ path: `estimates/${row.data.id}/view` }"
            class="font-medium text-primary-600 hover:text-primary-700"
          >
            {{ row.data.estimate_number }}
          </router-link>
        </template>

        <template #cell-name="{ row }">
          <router-link
            v-if="row.data.customer?.id"
            :to="`/admin/customers/${row.data.customer.id}/view`"
            class="font-medium text-heading hover:text-primary-600"
          >
            {{ row.data.customer.name }}
          </router-link>
          <span v-else>{{ row.data.customer?.name ?? '-' }}</span>
        </template>

        <template #cell-status="{ row }">
          <BaseEstimateStatusBadge :status="row.data.status" class="px-3 py-1">
            <BaseEstimateStatusLabel :status="row.data.status" />
          </BaseEstimateStatusBadge>
        </template>

        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.customer.currency"
          />
        </template>

        <template v-if="hasAtLeastOneAbility" #cell-actions="{ row }">
          <EstimateDropdown
            :row="row.data"
            :table="tableRef"
            :can-edit="canEdit"
            :can-view="canView"
            :can-create="canCreate"
            :can-delete="canDelete"
            :can-send="canSend"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>

  <SendEstimateModal />
</template>

<script setup lang="ts">
import type { ColumnDef } from '@/scripts/components/table/DataTable.vue'
// HisabKitab feature
import BaseViewSwitcher, { type ViewSwitcherOption } from '@/scripts/components/base/BaseViewSwitcher.vue'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { debouncedWatch } from '@vueuse/core'
import { useEstimateStore } from '../store'
import EstimateDropdown from '../components/EstimateDropdown.vue'
import SendEstimateModal from '../components/SendEstimateModal.vue'
import { useUserStore } from '../../../../stores/user.store'
import { useDialogStore } from '../../../../stores/dialog.store'
import { useGlobalStore } from '../../../../stores/global.store'
import type { Estimate } from '../../../../types/domain/estimate'
// HisabKitab feature
import { extensionRegistry, extensionItems } from '@/scripts/extensions/runtime'

interface Props {
  canCreate?: boolean
  canEdit?: boolean
  canView?: boolean
  canDelete?: boolean
  canSend?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canCreate: false,
  canEdit: false,
  canView: false,
  canDelete: false,
  canSend: false,
})

const ABILITIES = {
  CREATE: 'create-estimate',
  EDIT: 'edit-estimate',
  VIEW: 'view-estimate',
  DELETE: 'delete-estimate',
  SEND: 'send-estimate',
} as const

const estimateStore = useEstimateStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()
const globalStore = useGlobalStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

// ----------------------------------------------------------------
// View mode toggle (module-driven via extensionRegistry)
// ----------------------------------------------------------------

type EstimateViewMode = string

// HisabKitab feature - registered estimate view modes from modules, filtered by ability
const registeredViewModes = computed(() =>
  extensionItems(extensionRegistry.estimateViewModes.value).filter(
    (vm) => !vm.ability || userStore.hasAbilities(vm.ability),
  ),
)

const registeredViewModeValues = computed(() =>
  new Set(registeredViewModes.value.map((vm) => vm.value)),
)

// HisabKitab feature - document meta for the current view mode
const currentDocMeta = computed(() => {
  const items = extensionItems(extensionRegistry.estimateDocumentMeta.value)
  return items.find((m) => m.templateName === viewMode.value) ?? null
})

function initialViewMode(): EstimateViewMode {
  // HisabKitab feature - check registered view modes dynamically
  const vm = registeredViewModes.value.find((v) => v.value === route.query.view)
  if (vm) return vm.value
  return 'estimates'
}

const viewMode = ref<EstimateViewMode>(initialViewMode())

// HisabKitab feature - view switcher options from registered view modes
const estimateViews = computed<ViewSwitcherOption[]>(() => [
  { value: 'estimates', label: t('estimates.title'), icon: 'DocumentTextIcon' },
  ...registeredViewModes.value.map((vm) => ({
    value: vm.value,
    label: vm.label,
    icon: vm.icon,
  })),
])

// HisabKitab feature - label from registered view mode
const viewModeLabel = computed<string>(() => {
  const vm = registeredViewModes.value.find((v) => v.value === viewMode.value)
  if (vm) return `${vm.label}s`
  return t('estimates.title')
})

// HisabKitab feature - create link from registered view mode
const newEstimateLink = computed<string>(() => {
  const vm = registeredViewModes.value.find((v) => v.value === viewMode.value)
  if (vm) return vm.createLink
  return 'estimates/create'
})

function setViewMode(mode: EstimateViewMode): void {
  viewMode.value = mode
  // HisabKitab feature - reflect registered view modes in the URL
  if (mode !== 'estimates') {
    router.replace({ query: { view: mode } })
  } else if (route.query.view) {
    router.replace({ query: {} })
  }
  // Force the table to re-mount and re-fetch with the new template_name filter.
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.estimate_number = ''
  estimateStore.selectedEstimates = []
  estimateStore.selectAllField = false
  tableKey.value += 1
  isRequestOngoing.value = true
}

// HisabKitab feature - data-driven route-based view mode detection
function updateViewModeFromRoute(): void {
  const vm = registeredViewModes.value.find((v) => v.value === route.query.view)
  const newMode = vm ? vm.value : 'estimates'
  if (viewMode.value !== newMode) {
    viewMode.value = newMode
    filters.customer_id = ''
    filters.status = ''
    filters.from_date = ''
    filters.to_date = ''
    filters.estimate_number = ''
    estimateStore.selectedEstimates = []
    estimateStore.selectAllField = false
    tableKey.value += 1
    isRequestOngoing.value = true
  }
}

onMounted(() => {
  updateViewModeFromRoute()
})

// Watch for route query changes (when navigating between estimate types via
// the sidebar - Estimates ↔ Quotation share the same path).
watch(
  () => route.query.view,
  () => {
    updateViewModeFromRoute()
  },
)

const tableRef = ref<{ refresh: () => void } | null>(null)
const tableKey = ref<number>(0)
const showFilters = ref<boolean>(false)
const isRequestOngoing = ref<boolean>(true)
const activeTab = ref<string>('general.draft')

interface StatusOption {
  label: string
  value: string
}

const statusOptions = ref<StatusOption[]>([
  { label: t('estimates.draft'), value: 'DRAFT' },
  { label: t('estimates.sent'), value: 'SENT' },
  { label: t('estimates.viewed'), value: 'VIEWED' },
  { label: t('estimates.expired'), value: 'EXPIRED' },
  { label: t('estimates.accepted'), value: 'ACCEPTED' },
  { label: t('estimates.rejected'), value: 'REJECTED' },
])

interface EstimateFilters {
  customer_id: string | number
  status: string
  from_date: string
  to_date: string
  estimate_number: string
}

const filters = reactive<EstimateFilters>({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  estimate_number: '',
})

const showEmptyScreen = computed<boolean>(
  () => !estimateStore.totalEstimateCount && !isRequestOngoing.value,
)

const selectField = computed<number[]>({
  get: () => estimateStore.selectedEstimates,
  set: (val: number[]) => {
    estimateStore.selectEstimate(val)
  },
})

const canCreate = computed<boolean>(() => {
  return props.canCreate || userStore.hasAbilities(ABILITIES.CREATE)
})

const canEdit = computed<boolean>(() => {
  return props.canEdit || userStore.hasAbilities(ABILITIES.EDIT)
})

const canView = computed<boolean>(() => {
  return props.canView || userStore.hasAbilities(ABILITIES.VIEW)
})

const canDelete = computed<boolean>(() => {
  return props.canDelete || userStore.hasAbilities(ABILITIES.DELETE)
})

const canSend = computed<boolean>(() => {
  return props.canSend || userStore.hasAbilities(ABILITIES.SEND)
})

const hasAtLeastOneAbility = computed<boolean>(() => {
  return canCreate.value || canEdit.value || canView.value || canSend.value
})

type TableColumn = Omit<ColumnDef, 'label'> & { label?: string }

const estimateColumns = computed<TableColumn[]>(() => [
  {
    key: 'checkbox',
    thClass: 'extra w-10 pe-0',
    sortable: false,
    tdClass: 'font-medium text-heading pe-0',
  },
  {
    key: 'estimate_date',
    label: t('estimates.date'),
    thClass: 'extra',
    tdClass: 'font-medium text-muted',
    mobile: 'subtitle',
  },
  {
    key: 'estimate_number',
    // HisabKitab feature - column label from registered document meta
    label: currentDocMeta.value?.numberLabel ?? (currentDocMeta.value?.label ? `${currentDocMeta.value.label} No.` : t('estimates.number', 2)),
    mobile: 'subtitle',
  },
  { key: 'name', label: t('estimates.customer'), mobile: 'title' },
  { key: 'status', label: t('estimates.status'), mobile: 'badge' },
  {
    key: 'total',
    label: t('estimates.total'),
    tdClass: 'font-medium text-heading',
    align: 'end',
    mobile: 'trailing',
  },
  {
    key: 'actions',
    tdClass: 'text-end text-sm font-medium ps-0',
    thClass: 'text-end ps-0',
    sortable: false,
    mobile: 'actions',
  },
])

function estimateLink(row: { id?: number | string }): string {
  return `/admin/estimates/${row.id}/view`
}

debouncedWatch(filters, () => setFilters(), { debounce: 500 })

onUnmounted(() => {
  if (estimateStore.selectAllField) {
    estimateStore.selectAllEstimates()
  }
})

function clearStatusSearch(): void {
  filters.status = ''
  refreshTable()
}

function refreshTable(): void {
  tableRef.value?.refresh()
}

interface FetchParams {
  page: number
  filter: Record<string, unknown>
  sort: { fieldName?: string; order?: string }
}

interface FetchResult {
  data: Estimate[]
  pagination: {
    totalPages: number
    currentPage: number
    totalCount: number
    limit: number
  }
}

async function fetchData({ page, sort }: FetchParams): Promise<FetchResult> {
  const data = {
    customer_id: filters.customer_id ? Number(filters.customer_id) : undefined,
    status: filters.status || undefined,
    from_date: filters.from_date || undefined,
    to_date: filters.to_date || undefined,
    estimate_number: filters.estimate_number || undefined,
    // HisabKitab feature - template_name from registered view mode, or 'estimates'
    template_name: registeredViewModeValues.value.has(viewMode.value)
      ? viewMode.value
      : 'estimates',
    orderByField: sort.fieldName || 'created_at',
    orderBy: (sort.order || 'desc') as 'asc' | 'desc',
    page,
  }

  isRequestOngoing.value = true
  const response = await estimateStore.fetchEstimates(data)
  isRequestOngoing.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function setStatusFilter(val: { title: string }): void {
  if (activeTab.value === val.title) return
  activeTab.value = val.title

  switch (val.title) {
    case t('general.draft'):
      filters.status = 'DRAFT'
      break
    case t('general.sent'):
      filters.status = 'SENT'
      break
    default:
      filters.status = ''
      break
  }
}

function setFilters(): void {
  estimateStore.$patch((state) => {
    state.selectedEstimates = []
    state.selectAllField = false
  })
  tableKey.value += 1
  refreshTable()
}

function clearFilter(): void {
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.estimate_number = ''
  activeTab.value = t('general.all')
}

function toggleFilter(): void {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function removeMultipleEstimates(): void {
  dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('estimates.confirm_delete'),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  }).then(async (res: boolean) => {
    if (res) {
      const response = await estimateStore.deleteMultipleEstimates()
      if (response.data) {
        refreshTable()
        estimateStore.$patch((state) => {
          state.selectedEstimates = []
          state.selectAllField = false
        })
      }
    }
  })
}

function setActiveTab(val: string): void {
  const tabMap: Record<string, string> = {
    DRAFT: t('general.draft'),
    SENT: t('general.sent'),
    VIEWED: t('estimates.viewed'),
    EXPIRED: t('estimates.expired'),
    ACCEPTED: t('estimates.accepted'),
    REJECTED: t('estimates.rejected'),
  }
  activeTab.value = tabMap[val] ?? t('general.all')
}
</script>
