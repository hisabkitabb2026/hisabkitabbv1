<script setup lang="ts">
import { computed, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { clearStamp, invoicingStore } from '@/stores/invoicing'
import { useTranslate } from '@/support/i18n'
import { invoiceViewPath, retryStamp } from '@/support/invoicing'
import type { Notify } from '@/support/page'

/**
 * The one failure of invoicing that leaves work behind.
 *
 * The invoice was created and its time entries were not stamped, so the hours
 * still read as unbilled and would go onto a second invoice if nobody said
 * otherwise. Until the stamp lands, the module refuses to start another
 * invoice, and this is the way out: `billing/confirm` is idempotent, so
 * pressing retry can only ever finish the invoice that already exists.
 */

const props = defineProps<{
  client: AxiosInstance
  notify: Notify
}>()

const t = useTranslate()

const busy = ref(false)

const pending = computed(() => invoicingStore.pending)

async function retry(): Promise<void> {
  if (busy.value) {
    return
  }

  busy.value = true

  try {
    await retryStamp(props.client, props.notify, t)
  } finally {
    busy.value = false
  }
}

/**
 * Let go of an invoice that can never be stamped.
 *
 * An invoice deleted in the host, or one whose entries were removed, would
 * otherwise keep the module locked out of invoicing for the rest of the
 * session. Dismissing is deliberate and says so: the time stays unbilled.
 */
function dismiss(): void {
  if (window.confirm(t('tasks_projects.billing.retry.dismiss_confirm'))) {
    clearStamp()
  }
}
</script>

<template>
  <div
    v-if="pending"
    class="mt-4 rounded-xl border border-status-yellow bg-surface p-5"
    role="alert"
  >
    <p class="text-sm font-semibold text-heading">
      {{ t('tasks_projects.billing.retry.title') }}
    </p>
    <p class="mt-1 text-sm text-muted">
      {{ t('tasks_projects.billing.retry.description', { number: pending.invoiceNumber }) }}
    </p>

    <div class="mt-4 flex flex-wrap items-center gap-3">
      <BaseButton variant="primary" :loading="busy" :disabled="busy" @click="retry">
        {{ t('tasks_projects.billing.retry.action') }}
      </BaseButton>

      <router-link :to="invoiceViewPath(pending.invoiceId)">
        <BaseButton variant="white">
          {{ t('tasks_projects.billing.retry.open_invoice') }}
        </BaseButton>
      </router-link>

      <button
        type="button"
        class="text-sm font-medium text-muted hover:underline"
        @click="dismiss"
      >
        {{ t('tasks_projects.billing.retry.dismiss') }}
      </button>
    </div>
  </div>
</template>
