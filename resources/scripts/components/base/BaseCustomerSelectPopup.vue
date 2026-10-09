<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/scripts/stores/user.store'
import { useModalStore } from '@/scripts/stores/modal.store'
import { ABILITIES } from '@/scripts/config/abilities'
import { useCustomerStore } from '@/scripts/features/company/customers/store'
import { useInvoiceStore } from '@/scripts/features/company/invoices/store'
import { useEstimateStore } from '@/scripts/features/company/estimates/store'
import { useRecurringInvoiceStore } from '@/scripts/features/company/recurring-invoices/store'
import CustomerModal from '@/scripts/features/company/customers/components/CustomerModal.vue'
import BaseContactPicker from './BaseContactPicker.vue'

type DocumentType = 'estimate' | 'invoice' | 'recurring-invoice'

interface ValidationError {
  $message: string
}

interface Validation {
  $error: boolean
  $errors: ValidationError[]
}

interface Props {
  valid?: Validation
  customerId?: number | null
  type?: DocumentType | null
  contentLoading?: boolean
  // HisabKitab feature — consignee picker support (LR Receipt)
  label?: string | null
  consigneeMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  valid: () => ({ $error: false, $errors: [] }),
  customerId: null,
  type: null,
  contentLoading: false,
  label: null,
  consigneeMode: false,
})

const userStore = useUserStore()
const modalStore = useModalStore()
const { t } = useI18n()
const route = useRoute()

const customerStore = useCustomerStore()
const invoiceStore = useInvoiceStore()
const estimateStore = useEstimateStore()
const recurringInvoiceStore = useRecurringInvoiceStore()

const search = ref<string | null>(null)
const isSearchingCustomer = ref<boolean>(false)
// Until the first page arrives, so "no customers" does not flash on open
const isLoadingCustomers = ref<boolean>(true)

const selectedCustomer = computed(() => {
  // HisabKitab feature — consignee picker support (LR Receipt)
  if (props.consigneeMode) {
    return invoiceStore.newInvoice.consignee
  }
  switch (props.type) {
    case 'invoice':
      return invoiceStore.newInvoice.customer
    case 'estimate':
      return estimateStore.newEstimate.customer
    case 'recurring-invoice':
      return recurringInvoiceStore.newRecurringInvoice.customer
    default:
      return null
  }
})

// Fetch initial customers on setup
async function fetchInitialCustomers(): Promise<void> {
  try {
    // HisabKitab feature — Lorry Receipt: only show party-profile customers
    const params: Record<string, unknown> = {
      orderByField: '',
      orderBy: '',
    }
    if (invoiceStore.newInvoice.template_name === 'lorry_receipt') {
      params.lorry_party_only = 1
    }
    await customerStore.fetchCustomers(params)
  } finally {
    isLoadingCustomers.value = false
  }
}

// Select customer on setup if customerId is provided
if (props.customerId) {
  if (props.type === 'invoice') {
    invoiceStore.selectCustomer(props.customerId)
  } else if (props.type === 'estimate') {
    estimateStore.selectCustomer(props.customerId)
  } else if (props.type === 'recurring-invoice') {
    recurringInvoiceStore.selectCustomer(props.customerId)
  }
}

fetchInitialCustomers()

const debounceSearchCustomer = useDebounceFn(() => {
  isSearchingCustomer.value = true
  searchCustomer()
}, 500)

async function searchCustomer(): Promise<void> {
  try {
    // HisabKitab feature — Lorry Receipt: only show party-profile customers
    const params: Record<string, unknown> = {
      display_name: search.value ?? '',
      page: 1,
    }
    if (invoiceStore.newInvoice.template_name === 'lorry_receipt') {
      params.lorry_party_only = 1
    }
    await customerStore.fetchCustomers(params)
  } finally {
    isSearchingCustomer.value = false
  }
}

function selectNewCustomer(id: number): void {
  // HisabKitab feature — consignee picker support (LR Receipt)
  if (props.consigneeMode) {
    invoiceStore.selectConsignee(id)
    search.value = null
    return
  }

  const params: Record<string, unknown> = { userId: id }
  if (route.params.id) params.model_id = route.params.id

  if (props.type === 'invoice') {
    invoiceStore.getNextNumber(params, true)
    invoiceStore.selectCustomer(id)
  } else if (props.type === 'estimate') {
    estimateStore.getNextNumber(params, true)
    estimateStore.selectCustomer(id)
  } else if (props.type === 'recurring-invoice') {
    recurringInvoiceStore.selectCustomer(id)
  }

  search.value = null
}

function resetSelectedCustomer(): void {
  // HisabKitab feature — consignee picker support (LR Receipt)
  if (props.consigneeMode) {
    invoiceStore.resetSelectedConsignee()
    return
  }
  if (props.type === 'invoice') {
    invoiceStore.resetSelectedCustomer()
  } else if (props.type === 'estimate') {
    estimateStore.resetSelectedCustomer()
  } else if (props.type === 'recurring-invoice') {
    recurringInvoiceStore.resetSelectedCustomer()
  }
}

async function editCustomer(): Promise<void> {
  if (!selectedCustomer.value) return
  await customerStore.fetchCustomer(selectedCustomer.value.id)
  modalStore.openModal({
    title: t('customers.edit_customer'),
    componentName: 'CustomerModal',
  })
}

function openCustomerModal(): void {
  customerStore.resetCurrentCustomer()
  // HisabKitab feature — consignee picker support (LR Receipt)
  if (props.consigneeMode) {
    invoiceStore.isConsigneeMode = true
  }
  modalStore.openModal({
    title: t('customers.add_customer'),
    componentName: 'CustomerModal',
    variant: 'md',
  })
}

interface AddressLike {
  name?: string | null
  city?: string | null
  state?: string | null
  zip?: string | null
}

// Name, then "City, State", then the postcode; empty parts drop out
function addressLines(address: AddressLike | null | undefined): string[] {
  if (!address) {
    return []
  }

  const place = [address.city, address.state].filter(Boolean).join(', ')

  return [address.name, place, address.zip].filter(
    (line): line is string => !!line,
  )
}

const addressBlocks = computed(() => {
  const customer = selectedCustomer.value as {
    billing?: AddressLike
    shipping?: AddressLike
  } | null

  return [
    {
      key: 'billing',
      label: t('general.bill_to'),
      lines: addressLines(customer?.billing),
    },
    {
      key: 'shipping',
      label: t('general.ship_to'),
      lines: addressLines(customer?.shipping),
    },
  ].filter((block) => block.lines.length > 0)
})

const selected = computed(() =>
  selectedCustomer.value
    ? {
        id: selectedCustomer.value.id,
        name: selectedCustomer.value.name || '',
        subtitle: selectedCustomer.value.contact_name || '',
        addresses: addressBlocks.value,
      }
    : null,
)
const choices = computed(() =>
  customerStore.customers.map((customer) => ({
    id: customer.id,
    name: customer.name || '',
    subtitle: customer.contact_name || '',
  })),
)
function onSearch(value: string) {
  search.value = value
  debounceSearchCustomer()
}
</script>
<template>
  <div>
    <BaseContactPicker
      :selected="selected"
      :choices="choices"
      :label="label ?? $t('invoices.customer')"
      :placeholder="$t('customers.select_a_customer')"
      :create-label="$t('customers.add_new_customer')"
      :edit-label="$t('customers.edit_customer')"
      :empty-text="$t('customers.no_customers_found')"
      :error="valid.$error ? $t('estimates.errors.required') : ''"
      required
      :content-loading="contentLoading"
      :loading="isLoadingCustomers || isSearchingCustomer"
      :can-create="userStore.hasAbilities(ABILITIES.CREATE_CUSTOMER)"
      :can-edit="userStore.hasAbilities(ABILITIES.EDIT_CUSTOMER)"
      @search="onSearch"
      @select="selectNewCustomer"
      @clear="resetSelectedCustomer"
      @create="openCustomerModal"
      @edit="editCustomer"
    />
    <CustomerModal />
  </div>
</template>
