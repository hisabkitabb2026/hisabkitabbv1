<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { answerInvoiceNumber, invoicingStore } from '@/stores/invoicing'
import { useTranslate } from '@/support/i18n'

/**
 * The one question the invoicing sequence ever asks.
 *
 * A company that numbers its invoices itself gets no number from the host, so
 * the draft cannot be written without one. The dialog is mounted once in the
 * company layout rather than by each screen that can invoice, because the
 * sequence runs from a row, a bar, a header and a page, and none of them
 * should have to carry a dialog for it.
 */

const t = useTranslate()

const number = ref('')

const open = computed<boolean>(() => invoicingStore.prompt !== null)

const valid = computed<boolean>(() => number.value.trim() !== '')

// Opening fills the field with whatever the host did manage to suggest, so the
// common answer is "press save".
watch(
  () => invoicingStore.prompt,
  (prompt) => {
    number.value = prompt?.suggested ?? ''
  },
)

function save(): void {
  if (!valid.value) {
    return
  }

  answerInvoiceNumber(number.value.trim())
}

/** Backing out cancels the invoice; nothing has been created yet. */
function cancel(): void {
  answerInvoiceNumber(null)
}
</script>

<template>
  <BaseModal :show="open" @close="cancel">
    <template #header>
      <div class="flex w-full items-center justify-between">
        <span>{{ t('tasks_projects.billing.number.title') }}</span>
        <button
          type="button"
          class="-m-1.5 rounded-lg p-1.5 text-subtle hover:text-body focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500"
          :aria-label="t('tasks_projects.general.close')"
          @click="cancel"
        >
          <BaseIcon name="XMarkIcon" class="h-6 w-6" />
        </button>
      </div>
    </template>

    <form @submit.prevent="save">
      <div class="space-y-5 px-6 py-6">
        <p class="text-sm text-muted">
          {{ t('tasks_projects.billing.number.description') }}
        </p>

        <BaseInputGroup :label="t('tasks_projects.billing.number.label')" required>
          <BaseInput v-model="number" type="text" name="invoice_number" autocomplete="off" />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end space-x-3 border-t border-line-default px-6 py-4">
        <BaseButton type="button" variant="primary-outline" @click="cancel">
          {{ t('tasks_projects.general.cancel') }}
        </BaseButton>
        <BaseButton type="submit" variant="primary" :disabled="!valid">
          {{ t('tasks_projects.billing.number.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>
