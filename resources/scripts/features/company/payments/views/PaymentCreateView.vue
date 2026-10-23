<template>
  <BasePage class="relative payment-create">
    <form class="flex flex-col gap-4 md:gap-5" @submit.prevent="submitPaymentData">
      <!-- On phones Save moves to the bottom bar, still submitting this form -->
      <BasePageHeader :help="$t('page_help.payments')" :title="pageTitle" phone-actions="bar">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <BaseBreadcrumbItem :title="$t('payments.payment', 2)" to="/admin/payments" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton :loading="isSaving" :disabled="isSaving" :content-loading="isLoadingContent" variant="primary" type="submit">
            <template #left="slotProps">
              <BaseIcon v-if="!isSaving" name="ArrowDownOnSquareIcon" :class="slotProps.class" />
            </template>
            {{ isEdit ? $t('payments.update_payment') : $t('payments.save_payment') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard container-class="p-4 md:p-5">
        <BaseInputGrid>
          <BaseInputGroup :label="$t('payments.date')" :content-loading="isLoadingContent" required>
            <BaseDatePicker v-model="paymentStore.currentPayment.payment_date" :content-loading="isLoadingContent" :calendar-button="true" calendar-button-icon="calendar" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('payments.payment_number')" :content-loading="isLoadingContent" required>
            <BaseInput v-model="paymentStore.currentPayment.payment_number" :content-loading="isLoadingContent" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('payments.customer')" :content-loading="isLoadingContent" required>
            <BaseCustomerSelectInput
              v-if="!isLoadingContent"
              v-model="paymentStore.currentPayment.customer_id"
              :content-loading="isLoadingContent"
              :placeholder="$t('customers.select_a_customer')"
              show-action
              @update:model-value="onManualCustomerSelect"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('payments.amount')" :content-loading="isLoadingContent" required>
            <BaseMoney
              :key="String(paymentStore.currentPayment.currency)"
              v-model="amount"
              :currency="paymentStore.currentPayment.currency"
              :content-loading="isLoadingContent"
            />
          </BaseInputGroup>

          <BaseInputGroup label="TDS Deducted (Sec 194C / Tax Credit)" :content-loading="isLoadingContent">
            <div class="space-y-1.5">
              <BaseMoney
                :key="'tds-' + String(paymentStore.currentPayment.currency)"
                v-model="tdsAmount"
                :currency="paymentStore.currentPayment.currency"
                :content-loading="isLoadingContent"
              />
              <div class="flex items-center gap-1.5 text-xs">
                <span class="text-muted">Auto Calculate:</span>
                <button
                  type="button"
                  class="px-2 py-0.5 font-medium rounded border border-line-light bg-surface-secondary text-primary-600 hover:bg-primary-50 hover:border-primary-300 transition-colors"
                  title="Calculate 1% TDS on invoice total"
                  @click="calculateTdsPercentage(1)"
                >
                  1%
                </button>
                <button
                  type="button"
                  class="px-2 py-0.5 font-medium rounded border border-line-light bg-surface-secondary text-primary-600 hover:bg-primary-50 hover:border-primary-300 transition-colors"
                  title="Calculate 2% TDS on invoice total"
                  @click="calculateTdsPercentage(2)"
                >
                  2%
                </button>
                <button
                  v-if="tdsAmount > 0"
                  type="button"
                  class="px-2 py-0.5 font-medium rounded border border-line-light bg-surface-secondary text-muted hover:text-status-red transition-colors"
                  title="Clear TDS"
                  @click="tdsAmount = 0"
                >
                  Clear
                </button>
              </div>
            </div>
          </BaseInputGroup>

          <ExchangeRateConverter
            :store="exchangeRateStore"
            store-prop="currentPayment"
            :v="{ exchange_rate: { $error: false, $errors: [], $touch: () => {} } }"
            :is-loading="isLoadingContent"
            :is-edit="isEdit"
            :customer-currency="paymentStore.currentPayment.currency_id"
          />

          <BaseInputGroup :content-loading="isLoadingContent" :label="$t('payments.payment_mode')">
            <BaseMultiselect
              v-model="paymentStore.currentPayment.payment_method_id"
              :content-loading="isLoadingContent"
              label="name"
              value-prop="id"
              track-by="name"
              :options="paymentStore.paymentModes"
              :placeholder="$t('payments.select_payment_mode')"
              searchable
            />
          </BaseInputGroup>

          <!-- Custom fields join the form's own grid rather than forming a
               band of their own; they are attributes like the rest. -->
          <CustomFieldInput
            v-for="field in customFields"
            :key="field.id"
            :custom-field-scope="customFieldValidationScope"
            :field="field"
          />
        </BaseInputGrid>

        <section class="pt-5 mt-5 border-t border-line-light">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
              <h2 class="font-semibold text-section text-heading">{{ $t('payments.allocations') }}</h2>
              <p class="mt-1 text-sm text-muted">{{ $t('payments.allocations_description') }}</p>
            </div>
            <div class="flex gap-2">
              <BaseButton type="button" size="sm" variant="primary-outline" :disabled="!eligibleInvoices.length || !amount" @click="allocateOldestFirst">
                {{ $t('payments.allocate_oldest_first') }}
              </BaseButton>
              <BaseButton type="button" size="sm" variant="primary-outline" :disabled="!paymentStore.currentPayment.customer_id || !availableInvoices(0).length" @click="addAllocation">
                <template #left="slotProps"><BaseIcon name="PlusIcon" :class="slotProps.class" /></template>
                {{ $t('payments.add_allocation') }}
              </BaseButton>
            </div>
          </div>

          <div v-if="!paymentStore.currentPayment.customer_id" class="p-4 text-sm rounded-lg bg-surface-secondary text-muted">
            {{ $t('payments.select_customer_to_allocate') }}
          </div>

          <!-- Nothing open to apply it to: say so rather than offer buttons that do nothing -->
          <div v-else-if="!paymentStore.currentPayment.allocations.length" class="p-4 text-sm rounded-lg bg-surface-secondary text-muted" role="status">
            {{ !isLoadingInvoices && !eligibleInvoices.length ? $t('payments.no_open_invoices') : $t('payments.no_allocations') }}
          </div>

          <div v-else class="space-y-3">
            <div v-for="(allocation, index) in paymentStore.currentPayment.allocations" :key="`${allocation.invoice_id}-${index}`" class="grid items-end gap-3 p-3 rounded-lg bg-surface-secondary md:grid-cols-[minmax(0,1.5fr)_11rem_11rem_8rem_auto]">
              <BaseInputGroup :label="$t('payments.invoice')">
                <BaseMultiselect
                  v-model="allocation.invoice_id"
                  value-prop="id"
                  track-by="invoice_number"
                  label="display_label"
                  :options="availableInvoices(allocation.invoice_id)"
                  :loading="isLoadingInvoices"
                  :placeholder="$t('invoices.select_invoice')"
                  searchable
                  @update:model-value="onInvoiceSelect(index, $event)"
                />
              </BaseInputGroup>
              <BaseInputGroup :label="$t('payments.amount') + ' (Bank)'">
                <BaseMoney :model-value="allocation.amount / 100" :currency="paymentStore.currentPayment.currency" @update:model-value="setAllocationAmount(index, $event)" />
              </BaseInputGroup>
              <BaseInputGroup label="TDS Deducted">
                <BaseMoney :model-value="(allocation.tds_amount ?? 0) / 100" :currency="paymentStore.currentPayment.currency" @update:model-value="setAllocationTds(index, $event)" />
              </BaseInputGroup>
              <div class="flex flex-col justify-end pb-2">
                <span class="text-xs text-muted mb-1">Total Settled</span>
                <span class="text-sm font-semibold text-heading whitespace-nowrap">
                  <BaseFormatMoney :amount="allocation.amount + (allocation.tds_amount ?? 0)" :currency="paymentStore.currentPayment.currency" />
                </span>
              </div>
              <div class="justify-self-end pb-1">
                <BaseButton type="button" size="sm" variant="gray" :aria-label="$t('payments.remove_allocation')" @click="removeAllocation(index)">
                  <BaseIcon name="TrashIcon" />
                </BaseButton>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap justify-between items-center gap-x-8 gap-y-2 pt-4 mt-4 text-sm border-t border-line-light">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-muted">
              <span>Bank: <strong class="text-heading"><BaseFormatMoney :amount="paymentStore.currentPayment.amount" :currency="paymentStore.currentPayment.currency" /></strong></span>
              <span v-if="paymentStore.currentPayment.tds_amount">| TDS: <strong class="text-primary-600"><BaseFormatMoney :amount="paymentStore.currentPayment.tds_amount" :currency="paymentStore.currentPayment.currency" /></strong></span>
              <span>| Total Settled: <strong class="text-emerald-600 dark:text-emerald-400 font-semibold"><BaseFormatMoney :amount="totalSettlementAmount" :currency="paymentStore.currentPayment.currency" /></strong></span>
            </div>
            <div class="flex items-center gap-6">
              <span class="text-muted">{{ $t('payments.allocated') }}: <BaseFormatMoney :amount="allocatedAmount" :currency="paymentStore.currentPayment.currency" /></span>
              <span :class="unallocatedAmount < 0 ? 'text-status-red' : 'text-heading'">{{ $t('payments.unapplied_credit') }}: <BaseFormatMoney :amount="Math.max(unallocatedAmount, 0)" :currency="paymentStore.currentPayment.currency" /></span>
            </div>
          </div>
        </section>

        <BaseInputGroup :label="$t('estimates.notes')" class="mt-5">
          <BaseCustomInput v-model="paymentStore.currentPayment.notes" :content-loading="isLoadingContent" :fields="paymentFields" />
        </BaseInputGroup>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { usePaymentStore } from '../store'
import CustomFieldInput from '@/scripts/features/shared/custom-fields/CustomFieldInput.vue'
import { useCustomFields } from '@/scripts/features/shared/custom-fields/use-custom-fields'
import { useCompanyStore } from '../../../../stores/company.store'
import { useNotificationStore } from '../../../../stores/notification.store'
import { handleApiError, getErrorTranslationKey } from '../../../../utils/error-handling'
import { invoiceService } from '../../../../api/services/invoice.service'
import { customerService } from '../../../../api/services/customer.service'
import { ExchangeRateConverter } from '../../../shared/document-form'
import type { Invoice } from '../../../../types/domain/invoice'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const customFieldValidationScope = 'customFields'

const paymentStore = usePaymentStore()
const exchangeRateStore = paymentStore as unknown as Record<string, unknown> & { showExchangeRate: boolean }
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const isSaving = ref(false)
const isLoadingInvoices = ref(false)
const invoiceList = ref<Invoice[]>([])
const paymentFields = ref(['customer', 'company', 'customerCustom', 'payment', 'paymentCustom'])

const amount = computed<number>({
  get: () => paymentStore.currentPayment.amount / 100,
  set: (value) => { paymentStore.currentPayment.amount = Math.round(value * 100) },
})

const tdsAmount = computed<number>({
  get: () => (paymentStore.currentPayment.tds_amount ?? 0) / 100,
  set: (value) => {
    paymentStore.currentPayment.tds_amount = Math.round(value * 100)
    if (paymentStore.currentPayment.allocations.length === 1) {
      paymentStore.currentPayment.allocations[0].tds_amount = paymentStore.currentPayment.tds_amount
    }
  },
})

const totalSettlementAmount = computed(() => {
  return paymentStore.currentPayment.amount + (paymentStore.currentPayment.tds_amount ?? 0)
})

const allocatedAmount = computed(() =>
  paymentStore.currentPayment.allocations.reduce((total, allocation) => total + allocation.amount, 0),
)

const allocatedTds = computed(() =>
  paymentStore.currentPayment.allocations.reduce((total, allocation) => total + (allocation.tds_amount ?? 0), 0),
)

const unallocatedAmount = computed(() => paymentStore.currentPayment.amount - allocatedAmount.value)
const unallocatedTds = computed(() => (paymentStore.currentPayment.tds_amount ?? 0) - allocatedTds.value)
const isLoadingContent = computed(() => paymentStore.isFetchingInitialData)
const isEdit = computed(() => route.name === 'payments.edit')

const customFields = useCustomFields({
  store: paymentStore,
  storeProp: 'currentPayment',
  type: 'Payment',
  isEdit: () => isEdit.value,
})
const pageTitle = computed(() => isEdit.value ? t('payments.edit_payment') : t('payments.new_payment'))

paymentStore.resetCurrentPayment()

if (route.query.customer) paymentStore.currentPayment.customer_id = Number(route.query.customer)

paymentStore.fetchPaymentInitialData(
  isEdit.value,
  { id: isEdit.value ? String(route.params.id) : undefined },
  companyStore.selectedCompanyCurrency ?? undefined,
)

if (route.params.id && !isEdit.value) void setInvoiceFromUrl()

watch(
  () => paymentStore.currentPayment.customer_id,
  (customerId, previousCustomerId) => {
    if (!customerId) return
    if (previousCustomerId && customerId !== previousCustomerId) paymentStore.currentPayment.allocations = []
    void onCustomerChange(customerId)
  },
  { immediate: true },
)

function calculateTdsPercentage(percentage: number): void {
  let targetPaise = 0

  if (paymentStore.currentPayment.allocations.length > 0) {
    const firstAlloc = paymentStore.currentPayment.allocations[0]
    const invoice = invoiceList.value.find((inv) => inv.id === firstAlloc.invoice_id)
    if (invoice) {
      targetPaise = invoice.due_amount || invoice.total
    }
  }

  if (!targetPaise && eligibleInvoices.value.length === 1) {
    targetPaise = eligibleInvoices.value[0].due_amount || eligibleInvoices.value[0].total
  }

  if (!targetPaise && paymentStore.currentPayment.amount > 0) {
    targetPaise = Math.round(paymentStore.currentPayment.amount / (1 - (percentage / 100)))
  }

  if (targetPaise <= 0) return

  const calculatedTds = Math.round(targetPaise * (percentage / 100))
  const netReceived = targetPaise - calculatedTds

  paymentStore.currentPayment.tds_amount = calculatedTds

  if (paymentStore.currentPayment.amount === 0 || paymentStore.currentPayment.amount === targetPaise) {
    paymentStore.currentPayment.amount = netReceived
  }

  if (paymentStore.currentPayment.allocations.length === 1) {
    paymentStore.currentPayment.allocations[0].amount = paymentStore.currentPayment.amount
    paymentStore.currentPayment.allocations[0].tds_amount = calculatedTds
  }
}

async function setInvoiceFromUrl(): Promise<void> {
  try {
    const invoice = (await invoiceService.get(Number(route.params.id))).data
    paymentStore.currentPayment.customer_id = invoice.customer_id
    paymentStore.currentPayment.allocations = [{
      invoice_id: invoice.id,
      amount: invoice.due_amount,
      tds_amount: 0,
    }]
    if (paymentStore.currentPayment.amount === 0) paymentStore.currentPayment.amount = invoice.due_amount
  } catch {
    // The normal form remains usable if the originating invoice no longer exists.
  }
}

async function onCustomerChange(customerId: number): Promise<void> {
  isLoadingInvoices.value = true
  try {
    const [invoiceResponse, customerResponse] = await Promise.all([
      invoiceService.list({ customer_id: customerId, limit: 'all' } as never),
      customerService.get(customerId),
    ])
    invoiceList.value = [...(invoiceResponse.data as unknown as Invoice[])]
    const customer = customerResponse.data
    paymentStore.currentPayment.customer = customer
    paymentStore.currentPayment.selectedCustomer = customer
    if (customer.currency) {
      paymentStore.currentPayment.currency = customer.currency
      paymentStore.currentPayment.currency_id = customer.currency.id
    }
  } catch {
    invoiceList.value = []
  } finally {
    isLoadingInvoices.value = false
  }
}

function availableInvoices(selectedInvoiceId: number): Array<Invoice & { display_label: string }> {
  const chosenInvoiceIds = paymentStore.currentPayment.allocations
    .map(({ invoice_id }) => invoice_id)
    .filter((id) => id !== selectedInvoiceId)
  return invoiceList.value
    .filter((invoice) => isEligibleInvoice(invoice) || (selectedInvoiceId > 0 && invoice.id === selectedInvoiceId))
    .filter((invoice) => !chosenInvoiceIds.includes(invoice.id))
    .map((invoice) => {
      const dueFormatted = (invoice.due_amount / 100).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
      return {
        ...invoice,
        display_label: `${invoice.invoice_number} (Due: ₹${dueFormatted})`,
      }
    })
}

// Invoices this payment can still be applied to
const eligibleInvoices = computed<Invoice[]>(() => invoiceList.value.filter(isEligibleInvoice))

function isEligibleInvoice(invoice: Invoice): boolean {
  if (invoice.type !== 'INVOICE' || invoice.status === 'DRAFT' || invoice.due_amount <= 0) {
    return false
  }
  // HisabKitab: Only allow standard Invoices and Invoice Receipt template; exclude lr_receipt and lorry_receipt
  const template = invoice.template_name
  if (template === 'lr_receipt' || template === 'lorry_receipt') {
    return false
  }
  return true
}

function onManualCustomerSelect(): void {
  paymentStore.currentPayment.allocations = []
  paymentStore.currentPayment.amount = 0
  paymentStore.currentPayment.tds_amount = 0
  const params: Record<string, unknown> = { userId: paymentStore.currentPayment.customer_id }
  if (route.params.id) params.model_id = route.params.id
  void paymentStore.getNextNumber(params, true)
}

function onInvoiceSelect(index: number, invoiceId: number | null): void {
  const allocation = paymentStore.currentPayment.allocations[index]
  if (!allocation || !invoiceId) return

  const selectedInvoice = invoiceList.value.find((inv) => inv.id === invoiceId)
  if (!selectedInvoice) return

  if (!allocation.amount) {
    const remainingToAllocate = Math.max(0, paymentStore.currentPayment.amount - allocatedAmount.value)
    allocation.amount = remainingToAllocate > 0
      ? Math.min(remainingToAllocate, selectedInvoice.due_amount)
      : selectedInvoice.due_amount
  }

  if (!allocation.tds_amount && (paymentStore.currentPayment.tds_amount ?? 0) > 0) {
    const remainingTds = Math.max(0, (paymentStore.currentPayment.tds_amount ?? 0) - allocatedTds.value)
    const neededForInvoice = Math.max(0, selectedInvoice.due_amount - allocation.amount)
    allocation.tds_amount = Math.min(remainingTds, neededForInvoice)
  }
}

function addAllocation(): void {
  const firstInvoice = availableInvoices(0)[0]
  if (firstInvoice) {
    paymentStore.currentPayment.allocations.push({
      invoice_id: firstInvoice.id,
      amount: 0,
      tds_amount: 0,
    })
  }
}

function removeAllocation(index: number): void {
  paymentStore.currentPayment.allocations.splice(index, 1)
}

function setAllocationAmount(index: number, value: number): void {
  const allocation = paymentStore.currentPayment.allocations[index]
  if (allocation) allocation.amount = Math.round(value * 100)
}

function setAllocationTds(index: number, value: number): void {
  const allocation = paymentStore.currentPayment.allocations[index]
  if (allocation) {
    allocation.tds_amount = Math.round(value * 100)
    const sumTds = paymentStore.currentPayment.allocations.reduce((t, a) => t + (a.tds_amount ?? 0), 0)
    if ((paymentStore.currentPayment.tds_amount ?? 0) < sumTds) {
      paymentStore.currentPayment.tds_amount = sumTds
    }
  }
}

function allocateOldestFirst(): void {
  let remainingBank = paymentStore.currentPayment.amount
  let remainingTds = paymentStore.currentPayment.tds_amount ?? 0
  const allocations: Array<{ invoice_id: number; amount: number; tds_amount: number }> = []

  for (const invoice of [...invoiceList.value]
    .filter(isEligibleInvoice)
    .sort((a, b) => (a.due_date ?? '9999-12-31').localeCompare(b.due_date ?? '9999-12-31') || a.id - b.id)) {
    if (remainingBank <= 0 && remainingTds <= 0) break

    const allocatedBank = Math.min(remainingBank, invoice.due_amount)
    remainingBank -= allocatedBank

    const remainingInvoiceDue = invoice.due_amount - allocatedBank
    const allocatedTds = Math.min(remainingTds, remainingInvoiceDue)
    remainingTds -= allocatedTds

    if (allocatedBank > 0 || allocatedTds > 0) {
      allocations.push({
        invoice_id: invoice.id,
        amount: allocatedBank,
        tds_amount: allocatedTds,
      })
    }
  }
  paymentStore.currentPayment.allocations = allocations
}

async function submitPaymentData(): Promise<void> {
  if (unallocatedAmount.value < 0) {
    notificationStore.showNotification({ type: 'error', message: t('payments.allocation_exceeds_amount') })
    return
  }
  if (unallocatedTds.value < 0) {
    notificationStore.showNotification({ type: 'error', message: 'Allocated TDS exceeds total TDS deducted' })
    return
  }

  isSaving.value = true
  const current = paymentStore.currentPayment
  const data = {
    ...(isEdit.value ? { id: current.id } : {}),
    payment_date: current.payment_date,
    payment_number: current.payment_number,
    customer_id: current.customer_id,
    amount: current.amount,
    tds_amount: current.tds_amount ?? 0,
    payment_method_id: current.payment_method_id,
    notes: current.notes,
    currency_id: current.currency_id,
    exchange_rate: current.exchange_rate,
    customFields: current.customFields,
    fields: current.fields,
    allocations: current.allocations
      .filter(({ invoice_id, amount, tds_amount }) => invoice_id && (amount > 0 || (tds_amount ?? 0) > 0))
      .map(({ invoice_id, amount, tds_amount }) => ({
        invoice_id,
        amount,
        tds_amount: tds_amount ?? 0,
      })),
  }

  try {
    const response = await (isEdit.value ? paymentStore.updatePayment(data) : paymentStore.addPayment(data))
    await router.push(`/admin/payments/${response.data.data.id}/view`)
  } catch (error) {
    const normalized = handleApiError(error)
    const translationKey = getErrorTranslationKey(normalized.message)
    notificationStore.showNotification({ type: 'error', message: translationKey ? t(translationKey) : normalized.message })
  } finally {
    isSaving.value = false
  }
}

onBeforeUnmount(() => {
  paymentStore.resetCurrentPayment()
  invoiceList.value = []
})
</script>
