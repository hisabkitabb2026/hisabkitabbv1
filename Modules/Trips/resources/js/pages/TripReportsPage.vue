<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { Router } from 'vue-router'
import { PATHS } from '@/support/page'
import type { Notify } from '@/support/page'
import { useTranslate } from '@/support/i18n'
import { useTripReportDownload } from '@/support/useTripReportDownload'
import { reportPresets, presetValue } from '@/support/period'
import type { PeriodValue } from '@/support/period'
import { defaultMonthRange } from '@/support/date-range'
import TripReportPdfPane from '@/components/TripReportPdfPane.vue'

interface ReportTypeOption {
  label: string
  value: string
}

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  router: Router
}>()

const t = useTranslate()

const presets = reportPresets(t)
const period = ref<PeriodValue>(presetValue(presets[2]))

const reportTypes: ReportTypeOption[] = [
  { label: 'By Customer Name', value: 'customer' },
  { label: 'By LR No', value: 'lr' },
  { label: 'By Invoice No', value: 'invoice' },
  { label: 'By Supplier Name', value: 'supplier' },
]

const selectedType = ref<string>('customer')
const url = ref<string | null>(null)
const pdfPane = ref<InstanceType<typeof TripReportPdfPane> | null>(null)

const initialRange = defaultMonthRange()
const fromDate = ref<string>(initialRange.from)
const toDate = ref<string>(initialRange.to)

const filterCustomer = ref<string>('')
const filterLrNo = ref<string>('')
const filterInvoiceNo = ref<string>('')
const filterSupplier = ref<string>('')

const reportUrl = computed<string | null>(() => url.value)

const dateRangeUrl = computed<string>(() => {
  let u = '/api/v1/trips/reports/pdf?from_date=' + fromDate.value + '&to_date=' + toDate.value + '&type=' + selectedType.value
  if (filterCustomer.value.trim()) u += '&customer=' + encodeURIComponent(filterCustomer.value.trim())
  if (filterLrNo.value.trim()) u += '&lr_no=' + encodeURIComponent(filterLrNo.value.trim())
  if (filterInvoiceNo.value.trim()) u += '&invoice_no=' + encodeURIComponent(filterInvoiceNo.value.trim())
  if (filterSupplier.value.trim()) u += '&supplier=' + encodeURIComponent(filterSupplier.value.trim())
  return u
})

const onDownload = useTripReportDownload(props.client, () => {
  url.value = dateRangeUrl.value
  return url.value
})

onMounted(() => {
  url.value = dateRangeUrl.value
})

watch(period, (value) => {
  if (value.from && value.to) {
    fromDate.value = value.from
    toDate.value = value.to
  }
})

watch([fromDate, toDate, selectedType, filterCustomer, filterLrNo, filterInvoiceNo, filterSupplier], () => {
  url.value = dateRangeUrl.value
})

function getReports(): boolean {
  url.value = dateRangeUrl.value
  return true
}

function viewReportsPDF(): void {
  getReports()
  void pdfPane.value?.view(url.value)
}

function backToTrips(): void {
  void props.router.push(PATHS.board)
}
</script>

<template>
  <BasePage>
    <BasePageHeader title="Trip Reports">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem title="Dashboard" to="/admin/dashboard" />
        <BaseBreadcrumbItem title="Trips" :to="PATHS.board" />
        <BaseBreadcrumbItem title="Reports" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton variant="white" @click="backToTrips">
          <template #left="slotProps">
            <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
          </template>
          Back to Trips
        </BaseButton>

        <BaseButton variant="primary" class="ms-4" @click="onDownload">
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          Download PDF
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="grid gap-8 md:grid-cols-12 pt-10">
      <div class="col-span-8 md:col-span-4">
        <BaseInputGroup label="Date Range" class="mb-6">
          <BasePeriodPicker v-model="period" :presets="presets" block position="bottom-start" />
        </BaseInputGroup>

        <BaseInputGroup label="Report Type" class="col-span-12 md:col-span-8">
          <BaseMultiselect
            v-model="selectedType"
            :options="reportTypes"
            placeholder="Select report type"
            class="mt-1"
            @update:model-value="getReports"
          />
        </BaseInputGroup>

        <BaseInputGroup label="Customer Name" class="col-span-12 md:col-span-8 mt-4">
          <BaseInput v-model="filterCustomer" placeholder="Type customer name..." @update:model-value="getReports" />
        </BaseInputGroup>

        <BaseInputGroup label="LR No" class="col-span-12 md:col-span-8">
          <BaseInput v-model="filterLrNo" placeholder="Type LR number..." @update:model-value="getReports" />
        </BaseInputGroup>

        <BaseInputGroup label="Invoice No" class="col-span-12 md:col-span-8">
          <BaseInput v-model="filterInvoiceNo" placeholder="Type invoice number..." @update:model-value="getReports" />
        </BaseInputGroup>

        <BaseInputGroup label="Supplier Name" class="col-span-12 md:col-span-8">
          <BaseInput v-model="filterSupplier" placeholder="Type supplier name..." @update:model-value="getReports" />
        </BaseInputGroup>

        <div class="hidden mt-6 md:block">
          <BaseButton
            variant="primary"
            class="justify-center w-full"
            type="submit"
            @click.prevent="getReports"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowPathIcon" :class="slotProps.class" />
            </template>
            Update Report
          </BaseButton>
        </div>
      </div>

      <div class="col-span-8">
        <TripReportPdfPane
          ref="pdfPane"
          :client="client"
          :path="reportUrl"
          class="hidden md:block"
        />

        <button
          type="button"
          class="flex items-center justify-center w-full gap-2 px-5 text-sm font-medium transition-colors rounded-lg h-11 md:hidden bg-btn-primary text-on-primary hover:bg-btn-primary-hover"
          @click="viewReportsPDF"
        >
          <BaseIcon name="DocumentTextIcon" class="w-5 h-5" />
          View PDF
        </button>
      </div>
    </div>
  </BasePage>
</template>
