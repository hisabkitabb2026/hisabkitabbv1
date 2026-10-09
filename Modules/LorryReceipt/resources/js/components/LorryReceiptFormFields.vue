<script setup lang="ts">
/**
 * LorryReceiptFormFields — injected into the host invoice form via the
 * invoiceFormSections extension slot. Renders Lorry Receipt transport fields
 * when the invoice template_name is 'lorry_receipt'.
 *
 * Sections:
 *   1. Party Selection (Owner/Driver/Broker popups — auto-fills detail sections)
 *   2. Trip Details
 *   3. Vehicle Details
 *   4. Hire Particulars (Section C — Advance)
 *   5. Final Payment Details (Section E — Final Settlement)
 *   6. Owner Details (auto-filled by party selector, editable)
 *   7. Driver Details (auto-filled by party selector, editable)
 *   8. Broker Details (auto-filled by party selector, editable)
 *   9. Received Bilties
 */
import { computed, watch, ref, shallowRef, onMounted } from 'vue'
import type { AxiosInstance } from 'vue'
import LorryPartySelectPopup, { type LorryPartyProfile } from './LorryPartySelectPopup.vue'

const props = defineProps<{ templateName?: string | null; store?: Record<string, any> }>()

const invoiceData = computed(() => props.store?.newInvoice)
const isVisible = computed(() => props.templateName === 'lorry_receipt')

// Get the Axios client from the global extensions client (available on window)
const apiClient = shallowRef<AxiosInstance | null>(null)

function getClient(): AxiosInstance {
  if (apiClient.value) return apiClient.value
  // The host exposes the extensions API on window during module boot.
  // The client is also available via the module's init.ts setApiClient.
  // We use a fallback: access from the global scope if set.
  const w = window as any
  if (w.__lorryReceiptClient) {
    apiClient.value = w.__lorryReceiptClient
    return apiClient.value
  }
  // Fallback: create a minimal client using the host's axios
  throw new Error('API client not initialized for LorryReceipt module')
}

const balancePayableAtOptions = ['UMB', 'VAPI']

// --- Payment Methods (reusing host's /api/v1/payment-methods) ---
interface PaymentMethod {
  id: number
  name: string
}
const paymentMethods = ref<PaymentMethod[]>([])

async function fetchPaymentMethods(): Promise<void> {
  try {
    const client = getClient()
    const { data } = await client.get('/api/v1/payment-methods', { params: { limit: 'all' } })
    paymentMethods.value = data?.data ?? []
  } catch {
    paymentMethods.value = []
  }
}

// --- Banks (server-side storage via /api/v1/lorry-receipts/banks) ---
interface Bank {
  id: number
  name: string
}
const banks = ref<Bank[]>([])

async function fetchBanks(): Promise<void> {
  try {
    const client = getClient()
    const { data } = await client.get('/api/v1/lorry-receipts/banks')
    banks.value = data?.data ?? []
  } catch {
    banks.value = []
  }
}

// --- Party Profile auto-fill mappings ---
// Maps LorryPartyProfile fields → invoice tr_ columns
const ownerMapping: Array<[string, keyof LorryPartyProfile]> = [
  ['tr_owner_name', 'name'],
  ['tr_owner_address', 'address'],
  ['tr_owner_phone', 'phone'],
  ['tr_owner_bank_account_no', 'bank_account_no'],
  ['tr_owner_pan_no', 'pan_number'],
]

const driverMapping: Array<[string, keyof LorryPartyProfile]> = [
  ['tr_driver_name', 'name'],
  ['tr_driver_address', 'address'],
  ['tr_driver_licence_no', 'licence_no'],
  ['tr_driver_licence_date', 'licence_date'],
  ['tr_driver_rto_address', 'rto_address'],
  ['tr_driver_valid_up_to', 'valid_up_to'],
  ['tr_driver_bank_account_no', 'bank_account_no'],
]

const brokerMapping: Array<[string, keyof LorryPartyProfile]> = [
  ['tr_broker_name', 'name'],
  ['tr_broker_address', 'address'],
  ['tr_broker_pan_no', 'pan_number'],
  ['tr_advice_date', 'advice_date'],
  ['tr_broker_phone', 'phone'],
  ['tr_broker_bank_account_no', 'bank_account_no'],
]

function fillFields(mapping: Array<[string, keyof LorryPartyProfile]>, profile: LorryPartyProfile | null): void {
  if (!invoiceData.value) return
  mapping.forEach(([prop, key]) => {
    ;(invoiceData.value as Record<string, unknown>)[prop] = (profile?.[key] as string) || ''
  })
}

// Build a display profile from the invoice's tr_ fields so the party popups
// show the saved Owner/Driver/Broker when editing an existing receipt.
function profileFromMapping(mapping: Array<[string, keyof LorryPartyProfile]>): LorryPartyProfile | null {
  if (!invoiceData.value) return null
  const record = invoiceData.value as Record<string, unknown>
  const profile: Record<string, unknown> = {}
  for (const [prop, key] of mapping) {
    profile[key] = record[prop] ?? ''
  }
  if (!profile.name) return null
  return { id: 0, type: '', ...profile } as LorryPartyProfile
}

// Display always follows the current field values; the id comes from the
// stored profile link (tr_*_profile_id, saved with the receipt), so the
// popup's Edit button works on a freshly loaded receipt too.
function withProfileId(
  mapping: Array<[string, keyof LorryPartyProfile]>,
  profileId: unknown,
): LorryPartyProfile | null {
  const pseudo = profileFromMapping(mapping)
  if (!pseudo) return null
  const id = Number(profileId) || 0
  return id ? { ...pseudo, id } : pseudo
}

const currentOwner = computed<LorryPartyProfile | null>(() => withProfileId(ownerMapping, (invoiceData.value as Record<string, unknown> | undefined)?.tr_owner_profile_id))
const currentDriver = computed<LorryPartyProfile | null>(() => withProfileId(driverMapping, (invoiceData.value as Record<string, unknown> | undefined)?.tr_driver_profile_id))
const currentBroker = computed<LorryPartyProfile | null>(() => withProfileId(brokerMapping, (invoiceData.value as Record<string, unknown> | undefined)?.tr_broker_profile_id))

function onOwnerSelect(profile: LorryPartyProfile): void {
  if (invoiceData.value) {
    invoiceData.value.tr_owner_profile_id = profile.id
  }
  fillFields(ownerMapping, profile)
  // Also set Paid To / Final Paid To to owner name
  if (invoiceData.value) {
    invoiceData.value.tr_paid_to = profile.name
    invoiceData.value.tr_final_paid_to = profile.name
  }
}

function onOwnerClear(): void {
  if (invoiceData.value) {
    invoiceData.value.tr_owner_profile_id = null
  }
  fillFields(ownerMapping, null)
}

function onDriverSelect(profile: LorryPartyProfile): void {
  if (invoiceData.value) {
    invoiceData.value.tr_driver_profile_id = profile.id
  }
  fillFields(driverMapping, profile)
}

function onDriverClear(): void {
  if (invoiceData.value) {
    invoiceData.value.tr_driver_profile_id = null
  }
  fillFields(driverMapping, null)
}

function onBrokerSelect(profile: LorryPartyProfile): void {
  if (invoiceData.value) {
    invoiceData.value.tr_broker_profile_id = profile.id
  }
  fillFields(brokerMapping, profile)
}

function onBrokerClear(): void {
  if (invoiceData.value) {
    invoiceData.value.tr_broker_profile_id = null
  }
  fillFields(brokerMapping, null)
}

// --- Net Amount Payable + Sub Total auto-calculation ---
// Sub Total = Lorry Hire + Other Charges - Advance Paid + Detention + Extra Hire + Other - Less Adv other branch - Less Deduction
// This value is synced to invoiceData.sub_total and invoiceData.total so the
// host's DocumentTotals section reflects the correct amount.
function recalcAmounts(): void {
  if (!invoiceData.value) return
  const hire = Number(invoiceData.value.tr_lorry_hire_amount) || 0
  const other = Number(invoiceData.value.tr_other_charges_amount) || 0
  const advance = Number(invoiceData.value.tr_advance_amount) || 0
  const detention = Number(invoiceData.value.tr_detention_amount) || 0
  const extraHire = Number(invoiceData.value.tr_extra_hire_amount) || 0
  const finalOther = Number(invoiceData.value.tr_final_other_amount) || 0
  const lessAdv = Number(invoiceData.value.tr_less_advance_other_branch_amount) || 0
  const lessDed = Number(invoiceData.value.tr_less_deduction_claims_amount) || 0

  const netPayable = hire + other + detention + extraHire + finalOther - advance - lessAdv - lessDed
  invoiceData.value.tr_net_amount_payable = String(netPayable)

  // Sync to invoice sub_total and total (in cents) so the host's
  // DocumentTotals section shows the correct amount live.
  invoiceData.value.sub_total = Math.round(netPayable * 100)
  invoiceData.value.total = Math.round(netPayable * 100)
}

watch(
  () => [
    invoiceData.value?.tr_lorry_hire_amount,
    invoiceData.value?.tr_other_charges_amount,
    invoiceData.value?.tr_advance_amount,
    invoiceData.value?.tr_detention_amount,
    invoiceData.value?.tr_extra_hire_amount,
    invoiceData.value?.tr_final_other_amount,
    invoiceData.value?.tr_less_advance_other_branch_amount,
    invoiceData.value?.tr_less_deduction_claims_amount,
  ],
  () => recalcAmounts(),
)

onMounted(() => {
  fetchPaymentMethods()
  fetchBanks()
})
</script>

<template>
  <div v-if="isVisible && invoiceData" class="space-y-6 mt-5">

    <!-- 1. Party Selection -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Select Party (Owner / Driver / Broker)</h3>
      </template>
      <p class="text-sm text-muted mb-4">Select a party profile to auto-fill the Owner, Driver, and Broker details below.</p>
      <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
        <LorryPartySelectPopup
          :client="getClient()"
          type="OWNER"
          label="Owner"
          :model-value="currentOwner"
          @select="onOwnerSelect"
          @clear="onOwnerClear"
        />
        <LorryPartySelectPopup
          :client="getClient()"
          type="DRIVER"
          label="Driver"
          :model-value="currentDriver"
          @select="onDriverSelect"
          @clear="onDriverClear"
        />
        <LorryPartySelectPopup
          :client="getClient()"
          type="BROKER"
          label="Broker"
          :model-value="currentBroker"
          @select="onBrokerSelect"
          @clear="onBrokerClear"
        />
      </div>
    </BaseCard>

    <!-- 2. Trip Details -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Trip Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="From">
          <BaseInput v-model="invoiceData.tr_from_name" />
        </BaseInputGroup>
        <BaseInputGroup label="To">
          <BaseInput v-model="invoiceData.tr_to_name" />
        </BaseInputGroup>
        <BaseInputGroup label="No Of Pages">
          <BaseInput v-model="invoiceData.tr_no_of_pages" />
        </BaseInputGroup>
        <BaseInputGroup label="No Of Packages">
          <BaseInput v-model="invoiceData.tr_no_of_packages" />
        </BaseInputGroup>
        <BaseInputGroup label="Actual Weight">
          <BaseInput v-model="invoiceData.tr_actual_weight" />
        </BaseInputGroup>
        <BaseInputGroup label="Charge Weight">
          <BaseInput v-model="invoiceData.tr_charged_weight" />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- 3. Vehicle Details -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Vehicle Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Lorry No">
          <BaseInput v-model="invoiceData.tr_lorry_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Regd at">
          <BaseInput v-model="invoiceData.tr_regd_at" />
        </BaseInputGroup>
        <BaseInputGroup label="Body Type">
          <BaseInput v-model="invoiceData.tr_body_type" />
        </BaseInputGroup>
        <BaseInputGroup label="Make">
          <BaseInput v-model="invoiceData.tr_make" />
        </BaseInputGroup>
        <BaseInputGroup label="Model">
          <BaseInput v-model="invoiceData.tr_vehicle_model" />
        </BaseInputGroup>
        <BaseInputGroup label="Colour">
          <BaseInput v-model="invoiceData.tr_colour" />
        </BaseInputGroup>
        <BaseInputGroup label="Chasis No">
          <BaseInput v-model="invoiceData.tr_chasis_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Engine No">
          <BaseInput v-model="invoiceData.tr_engine_no" />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- 4. Hire Particulars (Section C — Advance) -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Hire Particulars</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Paid To">
          <BaseInput v-model="invoiceData.tr_paid_to" placeholder="Auto-filled from Owner selection" />
        </BaseInputGroup>
        <BaseInputGroup label="Lorry Hire">
          <BaseInput v-model="invoiceData.tr_lorry_hire_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Add Other Charges">
          <BaseInput v-model="invoiceData.tr_other_charges_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Advance Payment Mode">
          <BaseSelectInput
            v-model="invoiceData.tr_advance_payment_method_id"
            :options="paymentMethods.map(pm => ({ id: pm.id, label: pm.name }))"
            value-prop="id"
            label-key="label"
            placeholder="Select Payment Mode"
          />
        </BaseInputGroup>
        <BaseInputGroup label="Advance On">
          <BaseInput v-model="invoiceData.tr_advance_on" type="date" />
        </BaseInputGroup>
        <BaseInputGroup label="Bank">
          <BaseSelectInput
            v-model="invoiceData.tr_advance_bank"
            :options="banks.map(b => ({ id: b.name, label: b.name }))"
            value-prop="id"
            label-key="label"
            placeholder="Select Bank"
          />
        </BaseInputGroup>
        <BaseInputGroup label="Advance Paid Rs">
          <BaseInput v-model="invoiceData.tr_advance_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Balance Payable at">
          <BaseSelectInput
            v-model="invoiceData.tr_balance_payable_at"
            :options="balancePayableAtOptions.map(o => ({ id: o, label: o }))"
            value-prop="id"
            label-key="label"
            placeholder="Select..."
          />
        </BaseInputGroup>
        <BaseInputGroup label="Loaded By">
          <BaseInput v-model="invoiceData.tr_loaded_by" />
        </BaseInputGroup>

      </div>
    </BaseCard>

    <!-- 5. Final Payment Details (Section E — Final Settlement) -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Final Payment Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Final Paid To">
          <BaseInput v-model="invoiceData.tr_final_paid_to" />
        </BaseInputGroup>
        <BaseInputGroup label="Add Detention Rs.">
          <BaseInput v-model="invoiceData.tr_detention_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Extra Hire Rs">
          <BaseInput v-model="invoiceData.tr_extra_hire_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Other Rs">
          <BaseInput v-model="invoiceData.tr_final_other_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Less Adv. at other branch">
          <BaseInput v-model="invoiceData.tr_less_advance_other_branch_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Less Deduction for Claims">
          <BaseInput v-model="invoiceData.tr_less_deduction_claims_amount" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Final Balance Amount Paid at">
          <BaseSelectInput
            v-model="invoiceData.tr_final_balance_paid_at"
            :options="balancePayableAtOptions.map(o => ({ id: o, label: o }))"
            value-prop="id"
            label-key="label"
            placeholder="Select..."
          />
        </BaseInputGroup>
        <BaseInputGroup label="Final Balance Date">
          <BaseInput v-model="invoiceData.tr_final_balance_on" type="date" />
        </BaseInputGroup>
        <BaseInputGroup label="Final Payment Mode">
          <BaseSelectInput
            v-model="invoiceData.tr_final_payment_method_id"
            :options="paymentMethods.map(pm => ({ id: pm.id, label: pm.name }))"
            value-prop="id"
            label-key="label"
            placeholder="Select Payment Mode"
          />
        </BaseInputGroup>
        <BaseInputGroup label="Final Bank">
          <BaseSelectInput
            v-model="invoiceData.tr_final_bank"
            :options="banks.map(b => ({ id: b.name, label: b.name }))"
            value-prop="id"
            label-key="label"
            placeholder="Select Bank"
          />
        </BaseInputGroup>
        <BaseInputGroup label="Net Amount Payable">
          <BaseInput v-model="invoiceData.tr_net_amount_payable" type="number" readonly />
        </BaseInputGroup>

      </div>
    </BaseCard>

    <!-- 6. Owner Details (auto-filled by party selector, still editable) -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Owner Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Owner Name">
          <BaseInput v-model="invoiceData.tr_owner_name" />
        </BaseInputGroup>
        <BaseInputGroup label="Owner Phone No">
          <BaseInput v-model="invoiceData.tr_owner_phone" />
        </BaseInputGroup>
        <BaseInputGroup label="Owner Bank Account No">
          <BaseInput v-model="invoiceData.tr_owner_bank_account_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Owner PAN No">
          <BaseInput v-model="invoiceData.tr_owner_pan_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Owner Address" class="md:col-span-2 xl:col-span-3">
          <BaseTextarea v-model="invoiceData.tr_owner_address" rows="2" />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- 7. Driver Details (auto-filled by party selector, still editable) -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Driver Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Driver Name">
          <BaseInput v-model="invoiceData.tr_driver_name" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver Licence No">
          <BaseInput v-model="invoiceData.tr_driver_licence_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver Licence Date">
          <BaseInput v-model="invoiceData.tr_driver_licence_date" type="date" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver RTO">
          <BaseInput v-model="invoiceData.tr_driver_rto_address" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver Valid Up To">
          <BaseInput v-model="invoiceData.tr_driver_valid_up_to" type="date" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver Bank Account No">
          <BaseInput v-model="invoiceData.tr_driver_bank_account_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Driver Address" class="md:col-span-2 xl:col-span-3">
          <BaseTextarea v-model="invoiceData.tr_driver_address" rows="2" />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- 8. Broker Details (auto-filled by party selector, still editable) -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Broker Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Broker Name">
          <BaseInput v-model="invoiceData.tr_broker_name" />
        </BaseInputGroup>
        <BaseInputGroup label="Broker Phone No">
          <BaseInput v-model="invoiceData.tr_broker_phone" />
        </BaseInputGroup>
        <BaseInputGroup label="Broker Pan No">
          <BaseInput v-model="invoiceData.tr_broker_pan_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Advice Date">
          <BaseInput v-model="invoiceData.tr_advice_date" type="date" />
        </BaseInputGroup>
        <BaseInputGroup label="Broker Bank Account No">
          <BaseInput v-model="invoiceData.tr_broker_bank_account_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Broker Address" class="md:col-span-2 xl:col-span-3">
          <BaseTextarea v-model="invoiceData.tr_broker_address" rows="2" />
        </BaseInputGroup>
      </div>
    </BaseCard>



  </div>
</template>
