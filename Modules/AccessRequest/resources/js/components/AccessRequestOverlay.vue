<template>
  <div v-if="visible">
    <BaseModal :show="showModal" closable @close="closeModal">
      <template #header>
        Request Access
      </template>

      <form @submit.prevent>
        <div class="px-8 py-8 sm:p-6">
          <BaseInputGrid layout="one-column">
            <BaseInputGroup label="To">
              <BaseInput :model-value="recipient" type="text" disabled />
            </BaseInputGroup>

            <BaseInputGroup label="Subject" required>
              <BaseInput v-model="form.subject" type="text" />
            </BaseInputGroup>

            <BaseInputGroup label="Message" required>
              <BaseTextarea v-model="form.message" rows="7" />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <div class="flex justify-end gap-2 px-8 py-8 sm:px-6 sm:py-6 bg-surface-secondary">
          <BaseButton variant="white" @click="closeModal">
            Cancel
          </BaseButton>
          <BaseButton :disabled="sending" variant="primary" @click="send">
            <template #left="slotProps">
              <BaseIcon name="PaperAirplaneIcon" :class="slotProps.class" />
            </template>
            Send
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'

interface AccessRequestEvent extends CustomEvent {
  detail: {
    subject: string
    message: string
    recipient: string
  }
}

const visible = ref(false)
const showModal = ref(false)
const sending = ref(false)
const recipient = ref('the company owner')

const form = reactive({
  subject: '',
  message: '',
})

// The host injects the API client via window, since the module runs in its own
// bundle and cannot import the host's axios instance directly.
function getClient(): { post: (url: string, data: unknown) => Promise<{ data: { success?: boolean; error?: string } }> } {
  return (window as any).__accessRequestClient
}

function openHandler(event: Event): void {
  const e = event as AccessRequestEvent
  form.subject = e.detail.subject
  form.message = e.detail.message
  recipient.value = e.detail.recipient
  visible.value = true
  showModal.value = true
}

function closeModal(): void {
  showModal.value = false
}

async function send(): Promise<void> {
  if (!form.subject.trim() || !form.message.trim()) return

  sending.value = true
  try {
    const client = getClient()
    if (!client) {
      console.error('AccessRequest: API client not available')
      return
    }
    const response = await client.post('/api/v1/access-request', {
      subject: form.subject,
      message: form.message,
    })
    if (response.data?.error) {
      window.dispatchEvent(new CustomEvent('access-request:error', { detail: response.data.error }))
    } else {
      showModal.value = false
      window.dispatchEvent(new CustomEvent('access-request:success', { detail: 'Request sent to the company owner' }))
    }
  } catch {
    window.dispatchEvent(new CustomEvent('access-request:error', { detail: 'Could not send the request' }))
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  window.addEventListener('access-request:open', openHandler as EventListener)
})

onUnmounted(() => {
  window.removeEventListener('access-request:open', openHandler as EventListener)
})
</script>
