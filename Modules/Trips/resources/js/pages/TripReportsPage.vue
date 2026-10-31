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

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
  router: Router
}>()

const t = useTranslate()

const presets = reportPresets(t)
const period = ref<PeriodValue>(presetValue(presets[2]))

const tabKeys = ['customer', 'lr'] as const
type TabKey = (typeof tabKeys)[number]

const tabTitles: Record<TabKey, string> = {
  customer: 'By Customer',
  lr: 'By LR',
}

const selectedType = ref<TabKey>('customer')
const tabIndex = computed(() => Math.max(0, tabKeys.indexOf(selectedType.value)))

function selectTab(tab: { title: string }): void {
  const found = (Object.keys(tabTitles) as TabKey[]).find(
    (k) => tabTitles[k] === tab.title,
  )
  if (found && found !== selectedType.value) {
    selectedType.value = found
  }
}

const url = ref<string | null>(null)
const pdfPane = ref<InstanceType<typeof TripReportPdfPane> | null>(null)

const initialRange = defaultMonthRange()
const fromDate = ref<string>(initialRange.from)
const toDate = ref<string>(initialRange.to)

const filterCustomer = ref<string>('')
const filterLrNo = ref<string>('')

const reportUrl = computed<string | null>(() => url.value)

const dateRangeUrl = computed<string>(() => {
  let u = '/api/v1/trips/reports/pdf?from_date=' + fromDate.value + '&to_date=' + toDate.value + '&type=' + selectedType.value
  if (filterCustomer.value.trim()) u += '&customer=' + encodeURIComponent(filterCustomer.value.trim())
  if (filterLrNo.value.trim()) u += '&lr_no=' + encodeURIComponent(filterLrNo.value.trim())
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

watch([fromDate, toDate, selectedType, filterCustomer, filterLrNo], () => {
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

    <BaseTabGroup
      :key="selectedType"
      :default-index="tabIndex"
      class="p-2"
      @change="selectTab"
    >
      <BaseTab
        v-for="key in tabKeys"
        :key="key"
        :title="tabTitles[key]"
        tab-panel-container="px-0 py-0"
      />
    </BaseTabGroup>

    <div class="grid gap-8 md:grid-cols-12 pt-6">
      <div class="col-span-12 md:col-span-4 space-y-4">
        <BaseInputGroup label="Date Range">
          <BasePeriodPicker v-model="period" :presets="presets" block position="bottom-start" />
        </BaseInputGroup>

        <BaseInputGroup label="Filter By Customer">
          <BaseInput v-model="filterCustomer" placeholder="Type customer name..." @update:model-value="getReports" />
        </BaseInputGroup>

        <BaseInputGroup label="Filter By LR No">
          <BaseInput v-model="filterLrNo" placeholder="Type LR number..." @update:model-value="getReports" />
        </BaseInputGroup>

        <div class="hidden pt-2 md:block">
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

      <div class="col-span-12 md:col-span-8">
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
