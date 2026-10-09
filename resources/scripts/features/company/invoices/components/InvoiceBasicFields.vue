<template>
  <div class="grid grid-cols-12 gap-4 mt-5 mb-6 md:gap-8 md:mb-8">
    <div class="col-span-12 lg:col-span-6 pe-0 space-y-4">
      <!-- HisabKitab feature — Lorry Receipt: customer list is filtered
           server-side to only party-profile-linked customers -->
      <BaseCustomerSelectPopup
        :valid="v.customer_id"
        :content-loading="isLoading"
        type="invoice"
        :label="isLrReceipt ? 'Consignor' : null"
      />
      <!-- HisabKitab feature — consignee picker (LR Receipt only) -->
      <BaseCustomerSelectPopup
        v-if="isLrReceipt"
        :content-loading="isLoading"
        type="invoice"
        consignee-mode
        label="Consignee"
      />
    </div>

    <RecurringFields
      v-if="isRecurring"
      :is-loading="isLoading"
      :is-edit="isEdit"
      :custom-fields="customFields"
      :custom-field-scope="customFieldScope"
    />

    <BaseInputGrid
      v-else
      class="col-span-12 p-4 border lg:col-span-6 glass rounded-xl md:p-5 self-start"
    >
      <BaseInputGroup
        :label="currentDocMeta?.dateLabel ?? $t('invoices.invoice_date')"
        :content-loading="isLoading"
        required
        :error="v.invoice_date.$error && v.invoice_date.$errors[0].$message"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.invoice_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
          :enable-time="enableTime"
          :time24hr="time24h"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('invoices.due_date')"
        :content-loading="isLoading"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.due_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="currentDocMeta?.numberLabel ?? $t('invoices.invoice_number')"
        :content-loading="isLoading"
        :error="v.invoice_number.$error && v.invoice_number.$errors[0].$message"
        required
      >
        <BaseInput
          v-model="invoiceStore.newInvoice.invoice_number"
          :content-loading="isLoading"
          @input="v.invoice_number.$touch()"
        />
      </BaseInputGroup>

      <ExchangeRateConverter
        :store="invoiceStore"
        store-prop="newInvoice"
        :v="v"
        :is-loading="isLoading"
        :is-edit="isEdit"
        :customer-currency="invoiceStore.newInvoice.currency_id"
      />

      <!-- HisabKitab feature — custom fields removed for transport receipts
           (all fields now live in the module form sections). -->
      <CustomFieldInput
        v-for="field in customFields"
        v-if="!isTransportReceipt"
        :key="field.id"
        :custom-field-scope="customFieldScope"
        :field="field"
      />
    </BaseInputGrid>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ExchangeRateConverter } from '../../../shared/document-form'
import { useInvoiceStore } from '../store'
import RecurringFields from './RecurringFields.vue'
import CustomFieldInput from '@/scripts/features/shared/custom-fields/CustomFieldInput.vue'
import { useCustomFields } from '@/scripts/features/shared/custom-fields/use-custom-fields'
// HisabKitab feature — per-template field labels
import { useDocumentMeta } from '@/scripts/composables/use-document-meta'

interface ValidationField {
  $error: boolean
  $errors: Array<{ $message: string }>
  $touch: () => void
}

interface Props {
  v: Record<string, ValidationField>
  isLoading?: boolean
  isEdit?: boolean
  isRecurring?: boolean
  companySettings?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  isEdit: false,
  isRecurring: false,
  companySettings: () => ({}),
})

const invoiceStore = useInvoiceStore()
const customFieldScope = 'newInvoice'

const customFields = useCustomFields({
  store: invoiceStore,
  storeProp: 'newInvoice',
  type: 'Invoice',
  isEdit: () => props.isEdit === true,
})


const enableTime = computed<boolean>(() => {
  return props.companySettings?.invoice_use_time === 'YES'
})

const time24h = computed<boolean>(() => {
  const format = props.companySettings?.carbon_time_format ?? ''
  return format.indexOf('H') > -1
})

// HisabKitab feature — consignee picker (LR Receipt only)
const isLrReceipt = computed<boolean>(
  () => invoiceStore.newInvoice.template_name === 'lr_receipt',
)

// HisabKitab feature — transport receipts have their own fields, hide custom fields
const isTransportReceipt = computed<boolean>(() => {
  const t = invoiceStore.newInvoice.template_name
  return t === 'lr_receipt' || t === 'lorry_receipt' || t === 'invoice_receipt'
})

// HisabKitab feature — per-template field labels from registered document meta
const { currentDocMeta } = useDocumentMeta(() => invoiceStore.newInvoice.template_name ?? undefined)

const dateLabel = computed<string>(() => currentDocMeta.value?.dateLabel ?? 'invoices.invoice_date')
const numberLabel = computed<string>(() => currentDocMeta.value?.numberLabel ?? 'invoices.invoice_number')
</script>
