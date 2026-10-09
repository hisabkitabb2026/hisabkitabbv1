<script setup lang="ts">
/**
 * PermissionMissingUI — rendered by the host's InvoiceIndexView via the
 * invoice-permission-missing extension slot when the member lacks the
 * ability for the current receipt view mode. Shows a lock icon, the
 * view-mode label, and Request Access / Copy Request buttons.
 */
import { computed } from 'vue'

const props = defineProps<{
  viewModeLabel?: string
}>()

const label = computed(() => props.viewModeLabel ?? 'Receipt')

const requestMessage = computed(() => {
  return [
    'Hello,',
    '',
    `I need access to ${label.value}s.`,
    '',
    'Could you grant me the permission to view them? You can do this under Settings → Roles → my role.',
    '',
    'Thank you,',
  ].join('\n')
})

function openRequestModal(): void {
  window.dispatchEvent(new CustomEvent('access-request:open', {
    detail: {
      subject: `Request: Access to ${label.value}s`,
      message: requestMessage.value,
      recipient: null,
    },
  }))
}

async function copyRequestMessage(): Promise<void> {
  try {
    await navigator.clipboard.writeText(requestMessage.value)
    window.dispatchEvent(new CustomEvent('access-request:success', {
      detail: 'Request copied — paste it to the owner in chat or email',
    }))
  } catch {
    window.dispatchEvent(new CustomEvent('access-request:error', {
      detail: 'Could not copy the request',
    }))
  }
}
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
    <span class="flex items-center justify-center w-14 h-14 rounded-full bg-surface-secondary">
      <BaseIcon name="LockClosedIcon" class="w-7 h-7 text-muted" />
    </span>
    <div>
      <h2 class="text-lg font-semibold text-heading">{{ label }}s</h2>
      <p class="max-w-md mt-1 text-sm text-muted">
        You don't have permission to view {{ label }}s. Ask the company owner for access.
      </p>
    </div>
    <div class="flex items-center gap-2">
      <BaseButton variant="primary" @click="openRequestModal">
        <template #left="slotProps">
          <BaseIcon name="EnvelopeIcon" :class="slotProps.class" />
        </template>
        Request Access
      </BaseButton>
      <BaseButton variant="white" @click="copyRequestMessage">
        <template #left="slotProps">
          <BaseIcon name="ClipboardDocumentIcon" :class="slotProps.class" />
        </template>
        Copy Request
      </BaseButton>
    </div>
  </div>
</template>
