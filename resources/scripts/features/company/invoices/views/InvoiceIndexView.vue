<!-- HisabKitab feature -->
<template>
  <BasePage>
    <!-- HisabKitab feature -->
    <BasePageHeader :title="$t('invoices.title')" :help="$t(viewMode === 'recurring' ? 'page_help.recurring_invoices' : 'page_help.invoices')" :help-title="$t(viewMode === 'recurring' ? 'recurring_invoices.title' : 'invoices.title')">
      <template v-if="invoiceViews.length > 1" #title-suffix>
        <BaseViewSwitcher
          :model-value="viewMode"
          :primary-value="invoicePrimaryValue"
          :label="$t('invoices.title')"
          :options="invoiceViews"
          @update:model-value="setViewMode"
        />
      </template>
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('invoices.invoice', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="viewMode !== 'recurring' ? invoiceStore.invoiceTotalCount : recurringInvoiceStore.totalRecurringInvoices"
          variant="primary-outline"
          :aria-expanded="showFilters"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link
          v-if="canCreate"
          :to="newInvoiceLink"
          class="inline-flex rounded-lg"
        >
          <BaseButton tag="span" variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('invoices.new_invoice') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Filters (one-time) -->
    <BaseFilterWrapper
      v-show="showFilters && viewMode !== 'recurring'"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup v-if="userStore.hasAbilities('view-customer')" :label="$t('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('invoices.status')">
        <BaseMultiselect
          v-model="filters.status"
          :groups="true"
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

      <BaseInputGroup :label="$t('invoices.invoice_number')">
        <BaseInput v-model="filters.invoice_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Filters (recurring) -->
    <BaseFilterWrapper
      v-show="showFilters && viewMode === 'recurring'"
      @clear="clearRecurringFilter"
    >
      <BaseInputGroup v-if="userStore.hasAbilities('view-customer')" :label="$t('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="recurringFilters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('recurring_invoices.status')">
        <BaseMultiselect
          v-model="recurringFilters.status"
          :options="recurringStatusList"
          searchable
          :placeholder="$t('general.select_a_status')"
          @update:model-value="setRecurringActiveTab"
          @remove="clearRecurringStatusSearch()"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker
          v-model="recurringFilters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <div
        class="hidden w-4 h-px mb-5 shrink-0 bg-line-strong xl:block"
      />

      <BaseInputGroup :label="$t('general.to')">
        <BaseDatePicker
          v-model="recurringFilters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- HisabKitab feature -->
    <div
      v-if="receiptPermissionMissing"
      class="flex flex-col items-center justify-center gap-4 py-16 text-center"
    >
      <span class="flex items-center justify-center w-14 h-14 rounded-full bg-surface-secondary">
        <BaseIcon name="LockClosedIcon" class="w-7 h-7 text-muted" />
      </span>
      <div>
        <h2 class="text-lg font-semibold text-heading">{{ viewModeLabel }}s</h2>
        <p class="max-w-md mt-1 text-sm text-muted">
          You don't have permission to view {{ viewModeLabel }}s. Ask the company owner for access.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <BaseButton variant="primary" @click="openRequestModal">
          <template #left="slotProps">
            <BaseIcon name="EnvelopeIcon" :class="slotProps.class" />
          </template>
          Request Access
        </BaseButton>
        <BaseButton variant="white" @click="copyRequestMessage">
          <template #left="slotProps">
            <BaseIcon name="ClipboardDocumentIcon" :class="slotProps.class" />
          </template>
          Copy Request
        </BaseButton>
      </div>
    </div>

    <!-- One-time invoices section (also used for LR Receipt & Lorry Receipt modes) -->
    <template v-if="viewMode !== 'recurring' && !receiptPermissionMissing">
      <!-- Empty State -->
      <BaseEmptyPlaceholder
        v-show="showEmptyScreen"
        art="invoice"
        :ghost="6"
        :title="$t('invoices.no_invoices')"
        :description="$t('invoices.empty_description')"
      >
        <template v-if="canCreate" #actions>
          <BaseButton
            variant="primary"
            @click="$router.push(newInvoiceLink)"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('invoices.add_new_invoice') }}
          </BaseButton>
        </template>
      </BaseEmptyPlaceholder>

      <!-- Table -->
      <div v-show="!showEmptyScreen" class="relative flex flex-col gap-4 table-container">
        <BaseTabGroup @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="$t('general.draft')" filter="DRAFT" />
          <BaseTab :title="$t('general.sent')" filter="SENT" />
          <BaseTab :title="$t('general.due')" filter="DUE" />
        </BaseTabGroup>

        <BaseTable
          ref="tableRef"
          :key="tableKey"
          :no-results-message="$t('invoices.no_matching_invoices')"
          :data="fetchData"
          :columns="invoiceColumns"
          :placeholder-count="invoiceStore.invoiceTotalCount >= 20 ? 10 : 5"
          :row-to="invoiceLink"
          :selected-count="canDelete ? invoiceStore.selectedInvoices.length : 0"
        >
          <template #bulk-actions>
            <BaseButton size="xs" variant="white" @click="removeMultipleInvoices">
              <template #left="slotProps">
                <BaseIcon name="TrashIcon" :class="slotProps.class" />
              </template>
              {{ $t('general.delete') }}
            </BaseButton>
          </template>

          <template #header>
            <div class="absolute items-center start-6 top-3.5 select-none">
              <BaseCheckbox
                v-model="invoiceStore.selectAllField"
                :aria-label="$t('general.select_all')"
                variant="primary"
                @change="invoiceStore.selectAllInvoices"
              />
            </div>
          </template>

          <template #cell-checkbox="{ row }">
            <div class="relative block">
              <BaseCheckbox
                :id="row.id"
                v-model="selectField"
                :aria-label="$t('general.select_named', { name: row.data.invoice_number })"
                :value="row.data.id"
              />
            </div>
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

          <template #cell-invoice_number="{ row }">
            <router-link
              :to="{ path: `invoices/${row.data.id}/view` }"
              class="font-medium text-primary-600 hover:text-primary-700"
            >
              {{ row.data.invoice_number }}
            </router-link>
            <span
              v-if="row.data.type === 'CREDIT_NOTE'"
              class="inline-block ms-2 px-2 py-0.5 text-xs font-medium rounded-md bg-status-red-bg text-status-red"
            >
              {{ $t('invoices.credit_note') }}
            </span>
          </template>

          <!-- HisabKitab feature — Paid To cell for lorry_receipt view -->
          <template #cell-tr_paid_to="{ row }">
            <span class="text-sm text-heading">{{ row.data.tr_paid_to ?? '-' }}</span>
          </template>

          <template #cell-invoice_date="{ row }">
            {{ row.data.formatted_invoice_date }}
          </template>

          <template #cell-total="{ row }">
            <BaseFormatMoney
              :amount="row.data.total"
              :currency="row.data.customer.currency"
            />
          </template>

          <template #cell-status="{ row }">
            <BaseInvoiceStatusBadge :status="row.data.status">
              <BaseInvoiceStatusLabel :status="row.data.status" />
            </BaseInvoiceStatusBadge>
          </template>

          <template #cell-due_amount="{ row }">
            <div class="flex items-center justify-between gap-3">
              <BaseFormatMoney
                :amount="row.data.due_amount"
                :currency="row.data.currency"
              />

              <BasePaidStatusBadge
                v-if="row.data.overdue"
                status="OVERDUE"
              >
                {{ $t('invoices.overdue') }}
              </BasePaidStatusBadge>

              <!-- An invoice reversed in full by credit notes is settled but
                   NOT genuinely paid: show a distinct "Cancelled" badge
                   instead of the generic paid badge so the two can't be
                   confused. -->
              <span
                v-if="row.data.type !== 'CREDIT_NOTE' && row.data.credited_status === 'FULL'"
                class="inline-block px-2 py-0.5 text-xs font-medium rounded-md bg-status-yellow-bg text-status-yellow whitespace-nowrap"
              >
                {{ $t('invoices.cancelled') }}
              </span>

              <BasePaidStatusBadge
                v-else
                :status="row.data.paid_status"
              >
                <BaseInvoiceStatusLabel :status="row.data.paid_status" />
              </BasePaidStatusBadge>

              <!-- A partly credited invoice still has a real paid status, so
                   this badge sits ALONGSIDE it rather than replacing it. -->
              <span
                v-if="row.data.type !== 'CREDIT_NOTE' && row.data.credited_status === 'PARTIAL'"
                class="inline-block px-2 py-0.5 text-[11px] font-medium rounded-md bg-status-yellow-bg text-status-yellow whitespace-nowrap"
              >
                {{ $t('invoices.partially_credited') }}
              </span>
            </div>
          </template>

          <!-- Phones: the one status that matters at a glance -->
          <template #cell-mobile_status="{ row }">
            <BasePaidStatusBadge v-if="row.data.overdue" status="OVERDUE">
              {{ $t('invoices.overdue') }}
            </BasePaidStatusBadge>
            <BaseInvoiceStatusBadge v-else-if="row.data.status === 'DRAFT'" status="DRAFT">
              <BaseInvoiceStatusLabel status="DRAFT" />
            </BaseInvoiceStatusBadge>
            <BasePaidStatusBadge v-else :status="row.data.paid_status">
              <BaseInvoiceStatusLabel :status="row.data.paid_status" />
            </BasePaidStatusBadge>
          </template>

          <template v-if="hasAtLeastOneAbility" #cell-actions="{ row }">
            <InvoiceDropdown
              :row="row.data"
              :table="tableRef"
              :can-edit="canEdit"
              :can-view="canView"
              :can-create="canCreate"
              :can-delete="canDelete"
              :can-send="canSend"
              :can-create-payment="canCreatePayment"
              :can-create-estimate="canCreateEstimate"
            />
          </template>
        </BaseTable>
      </div>
    </template>

    <!-- Recurring invoices section -->
    <template v-else-if="canViewRecurring">
      <!-- Empty State -->
      <BaseEmptyPlaceholder
        v-show="showRecurringEmptyScreen"
        art="recurring"
        :ghost="6"
        :title="$t('recurring_invoices.no_invoices')"
        :description="$t('recurring_invoices.empty_description')"
      >
        <template v-if="canCreate" #actions>
          <BaseButton
            variant="primary"
            @click="$router.push('/admin/invoices/create?recurring=1')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('recurring_invoices.add_new_invoice') }}
          </BaseButton>
        </template>
      </BaseEmptyPlaceholder>

      <div v-show="!showRecurringEmptyScreen" class="relative flex flex-col gap-4 table-container">
        <BaseTabGroup @change="setRecurringStatusFilter">
          <BaseTab :title="$t('recurring_invoices.all')" filter="ALL" />
          <BaseTab :title="$t('recurring_invoices.active')" filter="ACTIVE" />
          <BaseTab :title="$t('recurring_invoices.on_hold')" filter="ON_HOLD" />
        </BaseTabGroup>

        <BaseTable
          ref="recurringTableRef"
          :no-results-message="$t('recurring_invoices.no_matching_invoices')"
          :data="fetchRecurringData"
          :columns="recurringColumns"
          :placeholder-count="recurringInvoiceStore.totalRecurringInvoices >= 20 ? 10 : 5"
          :row-to="recurringInvoiceLink"
          :selected-count="canRecurringDelete ? recurringInvoiceStore.selectedRecurringInvoices.length : 0"
        >
          <template #bulk-actions>
            <BaseButton size="xs" variant="white" @click="removeMultipleRecurringInvoices">
              <template #left="slotProps">
                <BaseIcon name="TrashIcon" :class="slotProps.class" />
              </template>
              {{ $t('general.delete') }}
            </BaseButton>
          </template>

          <template #header>
            <div class="absolute items-center start-6 top-3.5 select-none">
              <BaseCheckbox
                v-model="recurringInvoiceStore.selectAllField"
                :aria-label="$t('general.select_all')"
                variant="primary"
                @change="recurringInvoiceStore.selectAllRecurringInvoices"
              />
            </div>
          </template>

          <template #cell-checkbox="{ row }">
            <div class="relative block">
              <BaseCheckbox
                :id="row.id"
                v-model="recurringSelectField"
                :aria-label="$t('general.select_named', { name: row.data.customer?.name ?? row.data.id })"
                :value="row.data.id"
              />
            </div>
          </template>

          <!-- starts_at column -->
          <template #cell-starts_at="{ row }">
            {{ row.data.formatted_starts_at }}
          </template>

          <!-- customer column -->
          <template #cell-customer="{ row }">
            <router-link
              v-if="row.data.customer?.id"
              :to="`/admin/customers/${row.data.customer.id}/view`"
              class="flex flex-col"
            >
              <span class="font-medium text-primary-500 hover:text-primary-600">
                {{ row.data.customer.name }}
              </span>
              <span v-if="row.data.customer.contact_name" class="text-xs text-subtle">
                {{ row.data.customer.contact_name }}
              </span>
            </router-link>
            <span v-else>-</span>
          </template>

          <!-- frequency column -->
          <template #cell-frequency="{ row }">
            {{ getFrequencyLabel(row.data.frequency) }}
          </template>

          <!-- status column -->
          <template #cell-status="{ row }">
            <BaseRecurringInvoiceStatusBadge :status="row.data.status" class="px-3 py-1">
              <BaseRecurringInvoiceStatusLabel :status="row.data.status" />
            </BaseRecurringInvoiceStatusBadge>
          </template>

          <!-- total column -->
          <template #cell-total="{ row }">
            <BaseFormatMoney
              :amount="row.data.total"
              :currency="row.data.customer?.currency"
            />
          </template>

          <!-- actions column -->
          <template v-if="hasRecurringAtLeastOneAbility" #cell-actions="{ row }">
            <RecurringInvoiceDropdown
              :row="row.data"
              :table="recurringTableRef"
              :can-edit="canRecurringEdit"
              :can-view="canRecurringView"
              :can-delete="canRecurringDelete"
            />
          </template>
        </BaseTable>
      </div>
    </template>
  </BasePage>

  <SendInvoiceModal />
  <CreditNoteModal />
</template>

<script setup lang="ts">
import BaseViewSwitcher, { type ViewSwitcherOption } from '@/scripts/components/base/BaseViewSwitcher.vue'
import type { ColumnDef } from '@/scripts/components/table/DataTable.vue'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { debouncedWatch } from '@vueuse/core'
import { useInvoiceStore } from '../store'
import { useRecurringInvoiceStore } from '../../recurring-invoices/store'
import InvoiceDropdown from '../components/InvoiceDropdown.vue'
import SendInvoiceModal from '../components/SendInvoiceModal.vue'
import CreditNoteModal from '../components/CreditNoteModal.vue'
import RecurringInvoiceDropdown from '../../recurring-invoices/components/RecurringInvoiceDropdown.vue'
import { useUserStore } from '../../../../stores/user.store'
import { useGlobalStore } from '../../../../stores/global.store'
import { useDialogStore } from '../../../../stores/dialog.store'
import { useCompanyStore } from '../../../../stores/company.store'
import { useNotificationStore } from '../../../../stores/notification.store'
// HisabKitab feature
import { extensionRegistry, extensionItems } from '@/scripts/extensions/runtime'
import { useDocumentMeta } from '@/scripts/composables/use-document-meta'
import type { Invoice } from '../../../../types/domain/invoice'
import type { RecurringInvoice } from '../../../../types/domain/recurring-invoice'

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
  CREATE: 'create-invoice',
  EDIT: 'edit-invoice',
  VIEW: 'view-invoice',
  DELETE: 'delete-invoice',
  SEND: 'send-invoice',
} as const

const RECURRING_ABILITIES = {
  CREATE: 'create-recurring-invoice',
  EDIT: 'edit-recurring-invoice',
  VIEW: 'view-recurring-invoice',
  DELETE: 'delete-recurring-invoice',
} as const

const invoiceStore = useInvoiceStore()
const recurringInvoiceStore = useRecurringInvoiceStore()
const userStore = useUserStore()
const globalStore = useGlobalStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

// ----------------------------------------------------------------
// Recurring invoice ability
// ----------------------------------------------------------------

const canViewRecurring = computed<boolean>(() => {
  return userStore.hasAbilities(RECURRING_ABILITIES.VIEW)
})

// ----------------------------------------------------------------
// View mode toggle
// ----------------------------------------------------------------

// HisabKitab feature — view modes are now module-driven via extensionRegistry
type InvoiceViewMode = string

// HisabKitab feature — registered view modes from modules, filtered by ability
const registeredViewModes = computed(() =>
  extensionItems(extensionRegistry.invoiceViewModes.value).filter(
    (vm) => !vm.ability || userStore.hasAbilities(vm.ability),
  ),
)

const registeredViewModeValues = computed(() =>
  new Set(registeredViewModes.value.map((vm) => vm.value)),
)

// HisabKitab feature — document meta (labels) for the current view mode
const { currentDocMeta } = useDocumentMeta(() => viewMode.value)

// True when the current view mode is a receipt type the member may not see.
const receiptPermissionMissing = computed<boolean>(() => {
  if (viewMode.value === 'one-time' || viewMode.value === 'recurring') return false
  return !registeredViewModeValues.value.has(viewMode.value)
})

// "Request access" message the member can mail or paste to the company owner.
const requestAccessMessage = computed<string>(() => {
  const companyName = companyStore.selectedCompany?.name ?? ''
  const label = viewModeLabel.value
  const userName = userStore.currentUser?.name ?? ''
  return [
    'Hello,',
    '',
    `I need access to ${label}s in ${companyName}.`,
    '',
    'Could you grant me the permission to view them? You can do this under Settings → Roles → my role.',
    '',
    'Thank you,',
    userName,
  ].join('\n')
})

// HisabKitab feature — access request modal is handled by the AccessRequest module
// via a companyLayoutOverlay. The host just dispatches a DOM event.
function openRequestModal(): void {
  window.dispatchEvent(new CustomEvent('access-request:open', {
    detail: {
      subject: `Request: Access to ${viewModeLabel.value}s`,
      message: requestAccessMessage.value,
      recipient: companyStore.selectedCompany?.owner?.email ?? 'the company owner',
    },
  }))
}

async function copyRequestMessage(): Promise<void> {
  try {
    await navigator.clipboard.writeText(requestAccessMessage.value)
    notificationStore.showNotification({ type: 'success', message: 'Request copied — paste it to the owner in chat or email' })
  } catch {
    notificationStore.showNotification({ type: 'error', message: 'Could not copy the request' })
  }
}

// The ?view= query param must win at setup time: the table fetches its first
// page in a child onMounted hook that runs before this view's onMounted, so
// viewMode has to be correct here or the first fetch uses the wrong
// template_name filter and the list comes up empty until a manual refresh.
function initialViewMode(): InvoiceViewMode {
  // HisabKitab feature — check registered view modes dynamically
  const vm = registeredViewModes.value.find((v) => v.value === route.query.view)
  if (vm) return vm.value

  switch (route.query.view) {
    case 'recurring':
      return canViewRecurring.value ? 'recurring' : 'one-time'
    default: {
      // HisabKitab feature — when the core Invoices menu is hidden, default to
      // the first registered receipt view instead of 'one-time'
      if (!invoiceMenuVisible.value && registeredViewModes.value.length > 0) {
        return registeredViewModes.value[0].value as InvoiceViewMode
      }
      const storedViewMode = localStorage.getItem('invoiceViewMode') as InvoiceViewMode | null
      return storedViewMode === 'recurring' && !canViewRecurring.value
        ? 'one-time'
        : (storedViewMode ?? 'one-time')
    }
  }
}

const viewMode = ref<InvoiceViewMode>(initialViewMode())

// HisabKitab feature — label from registered view mode, or i18n for built-in modes
const viewModeLabel = computed(() => {
  if (viewMode.value === 'recurring') return t('recurring_invoices.recurring')
  const vm = registeredViewModes.value.find((v) => v.value === viewMode.value)
  if (vm) return vm.label
  return t('invoices.one_time')
})

// HisabKitab feature — create link from registered view mode
const newInvoiceLink = computed(() => {
  if (viewMode.value === 'recurring') return 'invoices/create?recurring=1'
  const vm = registeredViewModes.value.find((v) => v.value === viewMode.value)
  if (vm) return vm.createLink
  return 'invoices/create'
})

function updateViewModeFromRoute(): void {
  if (route.query.view === 'recurring' && canViewRecurring.value) {
    viewMode.value = 'recurring'
    localStorage.setItem('invoiceViewMode', 'recurring')
  } else {
    // HisabKitab feature — check registered view modes dynamically
    const vm = registeredViewModes.value.find((v) => v.value === route.query.view)
    if (vm) {
      viewMode.value = vm.value
      localStorage.setItem('invoiceViewMode', vm.value)
    } else if (!route.query.view) {
      // HisabKitab feature — when the core Invoices menu is hidden, default to
      // the first registered receipt view instead of 'one-time'
      if (!invoiceMenuVisible.value && registeredViewModes.value.length > 0) {
        viewMode.value = registeredViewModes.value[0].value as InvoiceViewMode
        localStorage.setItem('invoiceViewMode', viewMode.value)
      } else {
        viewMode.value = 'one-time'
        localStorage.setItem('invoiceViewMode', 'one-time')
      }
    }
  }
}

onMounted(() => {
  updateViewModeFromRoute()
  recurringInvoiceStore.initFrequencies(t)
})

// HisabKitab feature — view switcher options from registered view modes.
// When a module has replaced the core Invoices menu (e.g. InvoiceReceipt),
// hide the one-time/recurring options and show only the registered receipt views.
const invoiceMenuVisible = computed(() =>
  globalStore.menuGroups.flat().some((m) => m.name === 'Invoices'),
)

// HisabKitab feature — primary value for the view switcher: 'one-time' when the
// core Invoices menu is visible, otherwise the first registered receipt view.
const invoicePrimaryValue = computed(() =>
  invoiceMenuVisible.value
    ? 'one-time'
    : (registeredViewModes.value[0]?.value ?? 'one-time'),
)

const invoiceViews = computed<ViewSwitcherOption[]>(() => [
  ...(invoiceMenuVisible.value
    ? [{value: 'one-time', label: t('view_switcher.one_time'), icon: 'DocumentTextIcon'}]
    : []),
  ...(invoiceMenuVisible.value && canViewRecurring.value
    ? [{value: 'recurring', label: t('view_switcher.recurring'), icon: 'ArrowPathIcon'}]
    : []),
  ...registeredViewModes.value.map((vm) => ({
    value: vm.value,
    label: vm.label,
    icon: vm.icon,
  })),
])

// HisabKitab feature
watch(
  () => route.query.view,
  () => {
    updateViewModeFromRoute()
    if (viewMode.value !== 'recurring') {
      filters.customer_id = ''
      filters.status = ''
      filters.from_date = ''
      filters.to_date = ''
      filters.invoice_number = ''
      invoiceStore.selectedInvoices = []
      invoiceStore.selectAllField = false
      tableKey.value += 1
      isRequestOngoing.value = true
    }
  },
)

function setViewMode(mode: string): void {
  if (mode === 'recurring' && !canViewRecurring.value) return
  viewMode.value = mode
  localStorage.setItem('invoiceViewMode', mode)

  // Only 'recurring' mode is reflected in the URL (onMounted reads it to
  // restore state on page reload). LR/Lorry modes are view-only filters —
  // changing the URL for them causes a route re-evaluation that flickers
  // the table, so we skip router.replace entirely for non-recurring modes.
  if (mode === 'recurring') {
    router.replace({ query: { view: 'recurring' } })
  } else if (route.query.view) {
    // Clear the ?view=recurring query when switching away from recurring
    router.replace({ query: {} })
  }

  // HisabKitab feature
  if (mode !== 'recurring') {
    filters.customer_id = ''
    filters.status = ''
    filters.from_date = ''
    filters.to_date = ''
    filters.invoice_number = ''
    invoiceStore.selectedInvoices = []
    invoiceStore.selectAllField = false
    tableKey.value += 1
    isRequestOngoing.value = true
  }
}

// ----------------------------------------------------------------
// One-time invoice state
// ----------------------------------------------------------------

const tableRef = ref<{ refresh: () => void } | null>(null)
const tableKey = ref<number>(0)
const showFilters = ref<boolean>(false)
const isRequestOngoing = ref<boolean>(true)
const activeTab = ref<string>('general.draft')

interface StatusOption {
  label: string
  value: string
}

interface StatusGroup {
  label: string
  options: StatusOption[]
}

const statusOptions = ref<StatusGroup[]>([
  {
    label: t('invoices.status'),
    options: [
      { label: t('general.draft'), value: 'DRAFT' },
      { label: t('general.due'), value: 'DUE' },
      { label: t('general.sent'), value: 'SENT' },
      { label: t('invoices.viewed'), value: 'VIEWED' },
      { label: t('invoices.completed'), value: 'COMPLETED' },
    ],
  },
  {
    label: t('invoices.paid_status'),
    options: [
      { label: t('invoices.unpaid'), value: 'UNPAID' },
      { label: t('invoices.paid'), value: 'PAID' },
      { label: t('invoices.partially_paid'), value: 'PARTIALLY_PAID' },
    ],
  },
])

interface InvoiceFilters {
  customer_id: string | number
  status: string
  from_date: string
  to_date: string
  invoice_number: string
}

const filters = reactive<InvoiceFilters>({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  invoice_number: '',
})

const showEmptyScreen = computed<boolean>(
  () => !invoiceStore.invoiceTotalCount && !isRequestOngoing.value,
)

const selectField = computed<number[]>({
  get: () => invoiceStore.selectedInvoices,
  set: (value: number[]) => {
    invoiceStore.selectInvoice(value)
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

const canCreatePayment = computed<boolean>(() => {
  return userStore.hasAbilities('create-payment')
})

const canCreateEstimate = computed<boolean>(() => {
  return userStore.hasAbilities('create-estimate')
})

const hasAtLeastOneAbility = computed<boolean>(() => {
  return canDelete.value || canEdit.value || canView.value || canSend.value
})

type TableColumn = Omit<ColumnDef, 'label'> & { label?: string }

const invoiceColumns = computed<TableColumn[]>(() => [
  {
    key: 'checkbox',
    thClass: 'extra w-10',
    tdClass: 'font-medium text-heading',
    placeholderClass: 'w-10',
    sortable: false,
  },
  {
    key: 'invoice_date',
    label: t('invoices.date'),
    thClass: 'extra',
    mobile: 'subtitle',
  },
  // HisabKitab feature — column labels from registered document meta
  { key: 'invoice_number', label: currentDocMeta.value?.label ? `${currentDocMeta.value.label} No.` : t('invoices.number'), mobile: 'subtitle' },
  { key: 'name', label: currentDocMeta.value?.labelPlural ? currentDocMeta.value.labelPlural.replace(/s$/, '') : t('invoices.customer'), mobile: 'title' },
  // HisabKitab feature — Paid To column for lorry_receipt view
  ...(viewMode.value === 'lorry_receipt'
    ? [{ key: 'tr_paid_to', label: 'Paid To' }]
    : []),
  { key: 'status', label: t('invoices.status') },
  {
    key: 'due_amount',
    label: t('dashboard.recent_invoices_card.amount_due'),
  },
  {
    key: 'total',
    label: t('invoices.total'),
    tdClass: 'font-medium text-heading',
    align: 'end',
    mobile: 'trailing',
  },
  { key: 'mobile_status', hidden: true, sortable: false, mobile: 'badge' },
  {
    key: 'actions',
    tdClass: 'text-end text-sm font-medium w-12',
    thClass: 'text-end',
    sortable: false,
    mobile: 'actions',
  },
])

function invoiceLink(row: { id?: number | string }): string {
  return `/admin/invoices/${row.id}/view`
}

debouncedWatch(filters, () => setFilters(), { debounce: 500 })

onUnmounted(() => {
  if (invoiceStore.selectAllField) {
    invoiceStore.selectAllInvoices()
  }
  if (recurringInvoiceStore.selectAllField) {
    recurringInvoiceStore.selectAllRecurringInvoices()
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
  data: Invoice[]
  pagination: {
    totalPages: number
    currentPage: number
    totalCount: number
    limit: number
  }
}

async function fetchData({ page, sort }: FetchParams): Promise<FetchResult> {
  // HisabKitab feature
  if (receiptPermissionMissing.value) {
    return { data: [], pagination: { totalPages: 0, currentPage: 1, totalCount: 0, limit: 0 } }
  }

  const data = {
    customer_id: filters.customer_id ? Number(filters.customer_id) : undefined,
    status: filters.status || undefined,
    from_date: filters.from_date || undefined,
    to_date: filters.to_date || undefined,
    invoice_number: filters.invoice_number || undefined,
    // HisabKitab feature — template_name from registered view mode, or 'one-time'
    template_name: registeredViewModeValues.value.has(viewMode.value)
      ? viewMode.value
      : 'one-time',
    orderByField: sort.fieldName || 'created_at',
    orderBy: (sort.order || 'desc') as 'asc' | 'desc',
    page,
  }

  isRequestOngoing.value = true
  const response = await invoiceStore.fetchInvoices(data)
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
    case t('general.due'):
      filters.status = 'DUE'
      break
    default:
      filters.status = ''
      break
  }
}

function setFilters(): void {
  invoiceStore.$patch((state) => {
    state.selectedInvoices = []
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
  filters.invoice_number = ''
  activeTab.value = t('general.all')
}

function removeMultipleInvoices(): void {
  dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('invoices.confirm_delete'),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  }).then(async (res: boolean) => {
    if (res) {
      const response = await invoiceStore.deleteMultipleInvoices()
      if (response.data.success) {
        refreshTable()
        invoiceStore.$patch((state) => {
          state.selectedInvoices = []
          state.selectAllField = false
        })
      }
    }
  })
}

function toggleFilter(): void {
  if (showFilters.value) {
    if (viewMode.value === 'one-time') {
      clearFilter()
    } else {
      clearRecurringFilter()
    }
  }
  showFilters.value = !showFilters.value
}

function setActiveTab(val: string): void {
  const tabMap: Record<string, string> = {
    DRAFT: t('general.draft'),
    SENT: t('general.sent'),
    DUE: t('general.due'),
    COMPLETED: t('invoices.completed'),
    PAID: t('invoices.paid'),
    UNPAID: t('invoices.unpaid'),
    PARTIALLY_PAID: t('invoices.partially_paid'),
    VIEWED: t('invoices.viewed'),
  }
  activeTab.value = tabMap[val] ?? t('general.all')
}

// ----------------------------------------------------------------
// Recurring invoice state
// ----------------------------------------------------------------

const recurringTableRef = ref<{ refresh: () => void } | null>(null)
const isRecurringRequestOngoing = ref<boolean>(true)
const recurringActiveTab = ref<string>('recurring_invoices.all')

const recurringStatusList = ref<StatusOption[]>([
  { label: t('recurring_invoices.active'), value: 'ACTIVE' },
  { label: t('recurring_invoices.on_hold'), value: 'ON_HOLD' },
  { label: t('recurring_invoices.all'), value: 'ALL' },
])

interface RecurringInvoiceFilters {
  customer_id: string | number
  status: string
  from_date: string
  to_date: string
}

const recurringFilters = reactive<RecurringInvoiceFilters>({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
})

const showRecurringEmptyScreen = computed<boolean>(
  () =>
    !recurringInvoiceStore.totalRecurringInvoices &&
    !isRecurringRequestOngoing.value,
)

const recurringSelectField = computed<number[]>({
  get: () => recurringInvoiceStore.selectedRecurringInvoices,
  set: (value: number[]) => {
    recurringInvoiceStore.selectRecurringInvoice(value)
  },
})

const canRecurringEdit = computed<boolean>(() => {
  return userStore.hasAbilities(RECURRING_ABILITIES.EDIT)
})

const canRecurringView = computed<boolean>(() => {
  return userStore.hasAbilities(RECURRING_ABILITIES.VIEW)
})

const canRecurringDelete = computed<boolean>(() => {
  return userStore.hasAbilities(RECURRING_ABILITIES.DELETE)
})

const hasRecurringAtLeastOneAbility = computed<boolean>(() => {
  return canRecurringDelete.value || canRecurringEdit.value || canRecurringView.value
})

const recurringColumns = computed<TableColumn[]>(() => [
  {
    key: 'checkbox',
    thClass: 'extra',
    tdClass: 'font-medium text-heading',
    sortable: false,
  },
  {
    key: 'starts_at',
    label: t('recurring_invoices.starts_at'),
    thClass: 'extra',
    tdClass: 'font-medium',
    mobile: 'subtitle',
  },
  { key: 'customer', label: t('invoices.customer'), mobile: 'title' },
  {
    key: 'frequency',
    label: t('recurring_invoices.frequency.title'),
    mobile: 'subtitle',
  },
  { key: 'status', label: t('invoices.status'), mobile: 'badge' },
  {
    key: 'total',
    label: t('invoices.total'),
    align: 'end',
    mobile: 'trailing',
  },
  {
    key: 'actions',
    label: t('recurring_invoices.action'),
    tdClass: 'text-end text-sm font-medium',
    thClass: 'text-end',
    sortable: false,
    mobile: 'actions',
  },
])

function recurringInvoiceLink(row: { id?: number | string }): string {
  return `/admin/recurring-invoices/${row.id}/view`
}

debouncedWatch(recurringFilters, () => setRecurringFilters(), { debounce: 500 })

interface RecurringFetchResult {
  data: RecurringInvoice[]
  pagination: {
    totalPages: number
    currentPage: number
    totalCount: number
    limit: number
  }
}

async function fetchRecurringData({
  page,
  sort,
}: FetchParams): Promise<RecurringFetchResult> {
  const data = {
    customer_id: recurringFilters.customer_id
      ? Number(recurringFilters.customer_id)
      : undefined,
    status: recurringFilters.status || undefined,
    from_date: recurringFilters.from_date || undefined,
    to_date: recurringFilters.to_date || undefined,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isRecurringRequestOngoing.value = true
  const response = await recurringInvoiceStore.fetchRecurringInvoices(
    data as never,
  )
  isRecurringRequestOngoing.value = false

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

function getFrequencyLabel(frequencyFormat: string): string {
  const frequencyObj = recurringInvoiceStore.frequencies.find(
    (f) => f.value === frequencyFormat,
  )
  return frequencyObj ? frequencyObj.label : `CUSTOM: ${frequencyFormat}`
}

function refreshRecurringTable(): void {
  recurringTableRef.value?.refresh()
}

function setRecurringStatusFilter(val: { title: string }): void {
  if (recurringActiveTab.value === val.title) return
  recurringActiveTab.value = val.title

  switch (val.title) {
    case t('recurring_invoices.active'):
      recurringFilters.status = 'ACTIVE'
      break
    case t('recurring_invoices.on_hold'):
      recurringFilters.status = 'ON_HOLD'
      break
    default:
      recurringFilters.status = ''
      break
  }
}

function setRecurringFilters(): void {
  recurringInvoiceStore.$patch((state) => {
    state.selectedRecurringInvoices = []
    state.selectAllField = false
  })
  refreshRecurringTable()
}

function clearRecurringFilter(): void {
  recurringFilters.customer_id = ''
  recurringFilters.status = ''
  recurringFilters.from_date = ''
  recurringFilters.to_date = ''
  recurringActiveTab.value = t('recurring_invoices.all')
}

function clearRecurringStatusSearch(): void {
  recurringFilters.status = ''
  refreshRecurringTable()
}

function setRecurringActiveTab(val: string): void {
  const tabMap: Record<string, string> = {
    ACTIVE: t('recurring_invoices.active'),
    ON_HOLD: t('recurring_invoices.on_hold'),
    ALL: t('recurring_invoices.all'),
  }
  recurringActiveTab.value = tabMap[val] ?? t('recurring_invoices.all')
}

function removeMultipleRecurringInvoices(): void {
  dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('invoices.confirm_delete'),
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  }).then(async (res: boolean) => {
    if (res) {
      const response =
        await recurringInvoiceStore.deleteMultipleRecurringInvoices()
      if (response.data.success) {
        refreshRecurringTable()
        recurringInvoiceStore.$patch((state) => {
          state.selectedRecurringInvoices = []
          state.selectAllField = false
        })
      }
    }
  })
}
</script>
