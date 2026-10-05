<template>
  <BasePage class="relative estimate-create-page">
    <form @submit.prevent="submitForm">
      <!-- HisabKitab feature -->
      <BasePageHeader :help="$t('page_help.estimates')" :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <!-- HisabKitab feature — breadcrumb from registered document meta -->
          <BaseBreadcrumbItem
            :title="currentDocMeta?.labelPlural ?? $t('estimates.estimate', 2)"
            :to="currentDocMeta ? currentDocMeta.listLink : '/admin/estimates'"
          />
          <BaseBreadcrumbItem
            v-if="isEdit"
            :title="isQuotation ? `Edit ${currentDocMeta?.label ?? 'Quotation'}` : $t('estimates.edit_estimate')"
            to="#"
            active
          />
          <!-- HisabKitab feature -->
          <BaseBreadcrumbItem
            v-else
            :title="isQuotation ? `New ${currentDocMeta?.label ?? 'Quotation'}` : $t('estimates.new_estimate')"
            to="#"
            active
          />
        </BaseBreadcrumb>

        <!-- Phones get these in the bottom bar instead -->
        <template v-if="!isPhone" #actions>
          <router-link
            v-if="isEdit"
            :to="`/estimates/pdf/${estimateStore.newEstimate.unique_hash}`"
            target="_blank"
            class="inline-flex rounded-lg me-3"
          >
            <BaseButton tag="span" variant="primary-outline">
              <span class="flex">
                {{ $t('general.view_pdf') }}
              </span>
            </BaseButton>
          </router-link>

          <BaseButton
            :loading="isSaving"
            :disabled="isSaving"
            :content-loading="isLoadingContent"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                :class="slotProps.class"
                name="ArrowDownOnSquareIcon"
              />
            </template>
            <!-- HisabKitab feature — save label from registered document meta -->
            {{ isQuotation ? `Save ${currentDocMeta?.label ?? 'Quotation'}` : $t('estimates.save_estimate') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <DocumentFormActionBar
        :total="estimateStore.getTotal"
        :currency="estimateStore.newEstimate.selectedCurrency"
        :save-label="isQuotation ? `Save ${currentDocMeta?.label ?? 'Quotation'}` : $t('estimates.save_estimate')"
        :saving="isSaving"
        :loading="isLoadingContent"
        :pdf-url="isEdit ? `/estimates/pdf/${estimateStore.newEstimate.unique_hash}` : null"
      />

      <!-- Select Customer & Basic Fields -->
      <EstimateBasicFields
        :v="v$"
        :is-loading="isLoadingContent"
        :is-edit="isEdit"
      />

      <BaseScrollPane>
        <!-- HisabKitab feature -->
        <ExtensionSlot
          name="estimate-form-sections"
          :template-name="estimateStore.newEstimate.template_name"
          :store="estimateStore"
        />

        <!-- Estimate Items — hidden for the Quotation module, which uses its own
             redesigned consignment-style items table instead of line items -->
        <DocumentItemsTable
          v-if="!isQuotation"
          :currency="estimateStore.newEstimate.selectedCurrency"
          :is-loading="isLoadingContent"
          :item-validation-scope="estimateValidationScope"
          :tax-included-setting="companyStore.selectedCompanySettings.tax_included"
          :store="estimateStore"
          store-prop="newEstimate"
        />

        <!-- Estimate Footer Section -->
        <div
          class="block mt-10 estimate-foot lg:flex lg:justify-between lg:items-start"
        >
          <div class="relative w-full lg:w-1/2">
            <!-- Estimate Custom Notes -->
            <DocumentNotes
              :store="estimateStore"
              store-prop="newEstimate"
              :fields="estimateNoteFieldList"
              type="Estimate"
            />

            <!-- Estimate Template Button -->
            <TemplateSelectButton
              :store="estimateStore"
              store-prop="newEstimate"
              :is-mark-as-default="isMarkAsDefault"
            />
            <SelectTemplateModal />
          </div>

          <DocumentTotals
            :currency="estimateStore.newEstimate.selectedCurrency"
            :is-loading="isLoadingContent"
            :store="estimateStore"
            store-prop="newEstimate"
            tax-popup-type="estimate"
          />
        </div>
      </BaseScrollPane>
    </form>
  </BasePage>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import cloneDeep from 'lodash/cloneDeep'
import {
  required,
  maxLength,
  helpers,
  requiredIf,
  decimal,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useEstimateStore } from '../store'
import { useCompanyStore } from '@/scripts/stores/company.store'
import { useNotificationStore } from '@/scripts/stores/notification.store'
import { useBreakpoints } from '@/scripts/composables/use-breakpoints'
// HisabKitab feature
import { useEstimateDocumentMeta } from '@/scripts/composables/use-document-meta'
import {
  handleApiError,
  getErrorTranslationKey,
} from '@/scripts/utils/error-handling'
import EstimateBasicFields from '../components/EstimateBasicFields.vue'
import ExtensionSlot from '@/scripts/extensions/ExtensionSlot.vue'
import {
  DocumentItemsTable,
  DocumentFormActionBar,
  DocumentTotals,
  DocumentNotes,
  TemplateSelectButton,
  SelectTemplateModal,
} from '../../../shared/document-form'

const estimateStore = useEstimateStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { isPhone } = useBreakpoints()

const estimateValidationScope = 'newEstimate'
const isSaving = ref<boolean>(false)
const isMarkAsDefault = ref<boolean>(false)

const estimateNoteFieldList = ref<string[]>(['customer', 'company', 'estimate'])

const isLoadingContent = computed<boolean>(
  () => estimateStore.isFetchingInitialSettings,
)

// HisabKitab feature — document meta from registered estimate view modes
const { currentDocMeta } = useEstimateDocumentMeta(() => estimateStore.newEstimate.template_name)

const isQuotation = computed<boolean>(() => currentDocMeta.value !== null)

const isEdit = computed<boolean>(() => route.name === 'estimates.edit')

// HisabKitab feature — page title from registered document meta
const pageTitle = computed<string>(() => {
  if (currentDocMeta.value) {
    return isEdit.value ? `Edit ${currentDocMeta.value.label}` : `New ${currentDocMeta.value.label}`
  }
  return isEdit.value ? t('estimates.edit_estimate') : t('estimates.new_estimate')
})

const rules = {
  estimate_date: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  estimate_number: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  reference_number: {
    maxLength: helpers.withMessage(t('validation.price_maxlength'), maxLength(255)),
  },
  customer_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: requiredIf(() => estimateStore.showExchangeRate),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => estimateStore.newEstimate),
  { $scope: estimateValidationScope },
)

// Initialization
estimateStore.resetCurrentEstimate()
v$.value.$reset

// HisabKitab feature
if (route.query.template) {
  estimateStore.setTemplate(route.query.template as string)
}

estimateStore.fetchEstimateInitialSettings(
  isEdit.value,
  { id: route.params.id as string, query: route.query as Record<string, string> },
).then(() => {
  // HisabKitab feature
  if (route.query.template) {
    estimateStore.setTemplate(route.query.template as string)
  }
})

watch(
  () => estimateStore.newEstimate.customer,
  (newVal) => {
    if (newVal && (newVal as Record<string, unknown>).currency) {
      estimateStore.newEstimate.selectedCurrency = (
        newVal as Record<string, unknown>
      ).currency as Record<string, unknown>
    }
  },
)

async function submitForm(): Promise<void> {
  v$.value.$touch()

  if (v$.value.$invalid) {
    // The first invalid field, often the customer, takes focus on its own
    notificationStore.showNotification({ type: 'error', message: t('general.check_highlighted_fields') })
    return
  }

  isSaving.value = true

  try {
    const data: Record<string, unknown> = {
      ...cloneDeep(estimateStore.newEstimate),
      sub_total: Math.round(estimateStore.getSubTotal),
      total: Math.round(estimateStore.getTotal),
      tax: Math.round(estimateStore.getTotalTax),
    }

    const items = data.items as Array<Record<string, unknown>>
    if (data.discount_per_item === 'YES') {
      items.forEach((item, index) => {
        if (item.discount_type === 'fixed') {
          items[index].discount = Math.round((item.discount as number) * 100)
        }
      })
    } else {
      if (data.discount_type === 'fixed') {
        data.discount = Math.round((data.discount as number) * 100)
      }
    }

    const taxes = data.taxes as Array<Record<string, unknown>>
    if (data.tax_per_item !== 'YES' && taxes.length) {
      data.tax_type_ids = taxes.map((tax) => tax.tax_type_id)
    }

    const action = isEdit.value
      ? estimateStore.updateEstimate
      : estimateStore.addEstimate

    const res = await action(data)
    if (res.data.data) {
      router.push(`/admin/estimates/${res.data.data.id}/view`)
    }
  } catch (err: unknown) {
    const normalized = handleApiError(err)
    const translationKey = getErrorTranslationKey(normalized.message)

    notificationStore.showNotification({
      type: 'error',
      message: translationKey ? t(translationKey) : normalized.message,
    })
  } finally {
    isSaving.value = false
  }
}
</script>
