<script setup lang="ts">
/**
 * OfficeInvoiceFormFields — injected into the host invoice form via the
 * invoiceFormSections extension slot. Renders the Invoice Receipt consignment
 * items table when the invoice template_name is 'invoice_receipt'. The GST Tax
 * Through selector lives in the host's basic-fields box.
 * Reads/writes directly on the invoice store.
 */
import { computed } from 'vue'
import OfficeInvoiceItemsTable from './OfficeInvoiceItemsTable.vue'

const props = defineProps<{
  templateName?: string | null
  store?: Record<string, any>
}>()

const invoiceData = computed(() => props.store?.newInvoice)

const isVisible = computed(() => props.templateName === 'invoice_receipt')
</script>

<template>
  <div v-if="isVisible && invoiceData" class="space-y-6 mt-5">
    <!-- Invoice Items Table -->
    <OfficeInvoiceItemsTable
      :is-loading="false"
      :store="store"
      store-prop="newInvoice"
    />
  </div>
</template>
