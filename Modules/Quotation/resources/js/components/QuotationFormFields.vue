<script setup lang="ts">
/**
 * QuotationFormFields — injected into the host estimate form via the
 * estimateFormSections extension slot. Renders the redesigned Quotation items
 * table (Station Name + capacity dropdown) when the estimate template_name is
 * 'quotation'. Reads/writes directly on the estimate store.
 */
import { computed } from 'vue'
import QuotationItemsTable from './QuotationItemsTable.vue'

const props = defineProps<{
  templateName?: string | null
  store?: Record<string, any>
}>()

const estimateData = computed(() => props.store?.newEstimate)

const isVisible = computed(() => props.templateName === 'quotation')
</script>

<template>
  <div v-if="isVisible && estimateData" class="space-y-6 mt-5">
    <!-- Quotation Items Table -->
    <QuotationItemsTable
      :is-loading="false"
      :store="store"
      store-prop="newEstimate"
    />
  </div>
</template>
