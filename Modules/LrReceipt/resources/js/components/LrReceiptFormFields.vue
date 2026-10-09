<script setup lang="ts">
/**
 * LrReceiptFormFields — injected into the host invoice form via the
 * invoiceFormSections extension slot. Renders LR Receipt transport fields
 * (Trip Details, Consignment Details, Freight Details) when the invoice
 * template_name is 'lr_receipt'. Reads/writes directly on the invoice store.
 */
import { computed, watch } from 'vue'

const props = defineProps<{
  templateName?: string | null
  store?: Record<string, any>
}>()

const invoiceData = computed(() => props.store?.newInvoice)

const isVisible = computed(() => props.templateName === 'lr_receipt')

// LR Receipt charge keys for net amount auto-calculation
const chargeKeys = ['tr_basic_freight', 'tr_local_collection', 'tr_door_delivery', 'tr_hamali', 'tr_docket_charge', 'tr_other_charge', 'tr_fov']

function getFieldValue(key: string): number {
  const val = invoiceData.value?.[key]
  if (!val) return 0
  const num = Number(val)
  return isNaN(num) ? 0 : num
}

function recalcNetAmount(): void {
  if (!invoiceData.value) return
  const sum = chargeKeys.reduce((acc, key) => acc + getFieldValue(key), 0)
  invoiceData.value.tr_net_amount = String(sum)
  syncNetAmountToItems(sum)
}

// The host's Sub Total is the sum of the invoice items, so the freight
// charges (whose sum is the Net Amount) are mirrored into a single
// "Freight" line item. That makes Sub Total equal the Net Amount and the
// Total Amount come out correctly (plus any taxes the document carries).
function syncNetAmountToItems(net: number): void {
  const items = invoiceData.value?.items
  if (!items || !Array.isArray(items)) return

  // Host store expects prices in cents; net amount is in rupees
  const cents = Math.round(net * 100)

  // Update the first existing item (the form always has at least one row)
  if (items.length > 0) {
    const firstItem = items[0]
    firstItem.name = firstItem.name || 'Freight'
    firstItem.quantity = 1
    firstItem.price = cents
    firstItem.total = cents
    return
  }

  // No items at all — add one
  items.push({
    id: `tr-freight-${Date.now()}`,
    invoice_id: null,
    item_id: null,
    name: 'Freight',
    description: 'LR Receipt freight charges',
    quantity: 1,
    price: cents,
    discount_type: 'fixed',
    discount_val: 0,
    discount: 0,
    total: cents,
    totalTax: 0,
    totalSimpleTax: 0,
    totalCompoundTax: 0,
    tax: 0,
    taxes: [],
    unit_name: null,
  })
}

// Watch charge fields for changes
watch(
  () => chargeKeys.map((key) => invoiceData.value?.[key]),
  () => recalcNetAmount(),
  { deep: true },
)

const paymentOptions = ['To Pay', 'To Be Billed', 'Paid']
const gstOptions = ['Consignor', 'Consignee']
const podOptions = ['YES', 'NO']
</script>

<template>
  <div v-if="isVisible && invoiceData" class="space-y-6 mt-5">
    <!-- Trip Details -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Trip Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="From">
          <BaseInput v-model="invoiceData.tr_from_name" placeholder="Origin" />
        </BaseInputGroup>
        <BaseInputGroup label="To">
          <BaseInput v-model="invoiceData.tr_to_name" placeholder="Destination" />
        </BaseInputGroup>
        <BaseInputGroup label="Truck No">
          <BaseInput v-model="invoiceData.tr_truck_no" placeholder="Vehicle number" />
        </BaseInputGroup>
        <BaseInputGroup label="Mode of Payment">
          <BaseSelectInput
            v-model="invoiceData.tr_mode_of_payment"
            :options="paymentOptions.map(opt => ({ id: opt, label: opt }))"
            value-prop="id"
            label-key="label"
            placeholder="Select..."
          />
        </BaseInputGroup>
        <BaseInputGroup label="GST Payable By">
          <BaseSelectInput
            v-model="invoiceData.tr_gst_payable_by"
            :options="gstOptions.map(opt => ({ id: opt, label: opt }))"
            value-prop="id"
            label-key="label"
            placeholder="Select..."
          />
        </BaseInputGroup>
        <BaseInputGroup label="Time">
          <BaseInput v-model="invoiceData.tr_time" type="time" />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- Consignment Details -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Consignment Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Description of Goods" class="md:col-span-2 xl:col-span-3">
          <BaseTextarea v-model="invoiceData.tr_description_goods" rows="2" />
        </BaseInputGroup>
        <BaseInputGroup label="HSN Code">
          <BaseInput v-model="invoiceData.tr_hsn_code" />
        </BaseInputGroup>
        <BaseInputGroup label="E-way Bill No">
          <BaseInput v-model="invoiceData.tr_eway_bill_no" />
        </BaseInputGroup>
        <BaseInputGroup label="Actual Weight">
          <BaseInput v-model="invoiceData.tr_actual_weight" />
        </BaseInputGroup>
        <BaseInputGroup label="Charged Weight">
          <BaseInput v-model="invoiceData.tr_charged_weight" />
        </BaseInputGroup>
        <BaseInputGroup label="Party Invoice No.">
          <BaseInput v-model="invoiceData.tr_party_invoice_no" placeholder="Party invoice number" />
        </BaseInputGroup>
        <BaseInputGroup label="No of Articles">
          <BaseInput v-model="invoiceData.tr_no_of_articles" />
        </BaseInputGroup>
        <BaseInputGroup label="Packing">
          <BaseInput v-model="invoiceData.tr_packing" />
        </BaseInputGroup>
        <BaseInputGroup label="Delivery At">
          <BaseInput v-model="invoiceData.tr_delivery_at" />
        </BaseInputGroup>
        <BaseInputGroup label="Goods Value">
          <BaseInput v-model="invoiceData.tr_goods_value" />
        </BaseInputGroup>
        <BaseInputGroup label="POD Required">
          <BaseSelectInput
            v-model="invoiceData.tr_pod_required"
            :options="podOptions.map(opt => ({ id: opt, label: opt }))"
            value-prop="id"
            label-key="label"
            placeholder="Select..."
          />
        </BaseInputGroup>
      </div>
    </BaseCard>

    <!-- Freight Details -->
    <BaseCard class="mb-4">
      <template #header>
        <h3 class="text-base font-semibold text-heading">Freight Details</h3>
      </template>
      <div class="grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputGroup label="Basic Freight">
          <BaseInput v-model="invoiceData.tr_basic_freight" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Hamali">
          <BaseInput v-model="invoiceData.tr_hamali" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="FOV">
          <BaseInput v-model="invoiceData.tr_fov" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Local Collection">
          <BaseInput v-model="invoiceData.tr_local_collection" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Door Delivery">
          <BaseInput v-model="invoiceData.tr_door_delivery" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Docket Charge">
          <BaseInput v-model="invoiceData.tr_docket_charge" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Other Charge">
          <BaseInput v-model="invoiceData.tr_other_charge" type="number" />
        </BaseInputGroup>
        <BaseInputGroup label="Net Amount">
          <BaseInput v-model="invoiceData.tr_net_amount" type="number" readonly />
        </BaseInputGroup>
      </div>
    </BaseCard>
  </div>
</template>
