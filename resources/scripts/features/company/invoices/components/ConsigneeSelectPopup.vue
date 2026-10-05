<script setup lang="ts">
/**
 * ConsigneeSelectPopup — clones BaseCustomerSelectPopup but filters
 * customers by type=CONSIGNEE. Writes to tr_consignee_customer_id
 * on the invoice store.
 */
import { computed, ref } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useInvoiceStore } from '../store'
import { useCustomerStore } from '../../customers/store'
import { useModalStore } from '@/scripts/stores/modal.store'
import { useUserStore } from '@/scripts/stores/user.store'
import { ABILITIES } from '@/scripts/config/abilities'
import { customerService } from '@/scripts/api/services/customer.service'
import BaseContactPicker from '@/scripts/components/base/BaseContactPicker.vue'
import CustomerModal from '../../customers/components/CustomerModal.vue'

const invoiceStore = useInvoiceStore()
const customerStore = useCustomerStore()
const modalStore = useModalStore()
const userStore = useUserStore()

const search = ref<string | null>(null)
const isSearching = ref(false)
const isLoading = ref(true)
const consigneeList = ref<any[]>([])

const choices = computed(() =>
  consigneeList.value.map((c: any) => ({
    id: c.id,
    name: c.name || '',
    subtitle: c.contact_name || c.phone || '',
  })),
)

const consigneeAddresses = computed(() => {
  const map: Record<number, Array<{ label: string; lines: string[] }>> = {}
  for (const c of consigneeList.value) {
    map[c.id] = buildAddressBlocks(c)
  }
  return map
})

const selected = computed(() => {
  const consigneeId = invoiceStore.newInvoice.tr_consignee_customer_id
  if (!consigneeId) return null
  const c = (invoiceStore.newInvoice as any).consignee
  const match = choices.value.find((ch) => ch.id === consigneeId)
  const name = c?.name || match?.name || ''
  const subtitle = c?.contact_name || c?.phone || match?.subtitle || ''
  return {
    id: consigneeId,
    name,
    subtitle,
    addresses: consigneeAddresses.value[consigneeId] || [],
  }
})

async function fetchConsignees(searchTerm?: string) {
  isSearching.value = true
  try {
    const params: Record<string, any> = { limit: 'all', type: 'CONSIGNEE' }
    if (searchTerm) params.display_name = searchTerm
    const response = await customerService.list(params)
    consigneeList.value = response.data
  } catch {
    consigneeList.value = []
  } finally {
    isLoading.value = false
    isSearching.value = false
  }
}

interface AddressLike {
  name?: string | null
  city?: string | null
  state?: string | null
  zip?: string | null
}

function addressLines(address: AddressLike | null | undefined): string[] {
  if (!address) return []
  const place = [address.city, address.state].filter(Boolean).join(', ')
  return [address.name, place, address.zip].filter((line): line is string => !!line)
}

function buildAddressBlocks(customer: any): Array<{ label: string; lines: string[] }> {
  const blocks: Array<{ label: string; lines: string[] }> = []
  const billing = customer.billing || customer.billing_address
  const shipping = customer.shipping || customer.shipping_address
  if (billing && addressLines(billing).length) {
    blocks.push({ label: 'Bill To', lines: addressLines(billing) })
  }
  if (shipping && addressLines(shipping).length) {
    blocks.push({ label: 'Ship To', lines: addressLines(shipping) })
  }
  return blocks
}

const debounceSearch = useDebounceFn(() => {
  isSearching.value = true
  fetchConsignees(search.value ?? undefined)
}, 500)

function onSearch(value: string) {
  search.value = value
  debounceSearch()
}

function onSelect(id: number) {
  const choice = choices.value.find((c) => c.id === id)
  if (!choice) return
  invoiceStore.newInvoice.tr_consignee_customer_id = id
  ;(invoiceStore.newInvoice as any).consignee = {
    id,
    name: choice.name,
    contact_name: choice.subtitle,
  }
  search.value = null
}

function onClear() {
  invoiceStore.newInvoice.tr_consignee_customer_id = null
  ;(invoiceStore.newInvoice as any).consignee = null
}

async function editConsignee() {
  const consigneeId = invoiceStore.newInvoice.tr_consignee_customer_id
  if (!consigneeId) return
  await customerStore.fetchCustomer(consigneeId)
  modalStore.openModal({
    title: 'Edit Consignee',
    componentName: 'CustomerModal',
  })
}

function openConsigneeModal() {
  customerStore.resetCurrentCustomer()
  modalStore.openModal({
    title: 'Add New Consignee',
    componentName: 'CustomerModal',
    variant: 'md',
  })
}

const canEdit = computed(() => userStore.hasAbilities(ABILITIES.EDIT_CUSTOMER))
const canCreate = computed(() => userStore.hasAbilities(ABILITIES.CREATE_CUSTOMER))

// Fetch consignees immediately on setup (same pattern as BaseCustomerSelectPopup)
fetchConsignees()
</script>

<template>
  <div v-if="invoiceStore.newInvoice.template_name === 'lr_receipt'">
  <BaseContactPicker
    :selected="selected"
    :choices="choices"
    label="Consignee"
    placeholder="Select a consignee"
    create-label="Add New Consignee"
    edit-label="Edit Consignee"
    empty-text="No consignees found"
    :loading="isLoading || isSearching"
    :can-edit="canEdit"
    :can-create="canCreate"
    @search="onSearch"
    @select="onSelect"
    @clear="onClear"
    @edit="editConsignee"
    @create="openConsigneeModal"
  />
  <CustomerModal />
  </div>
</template>
