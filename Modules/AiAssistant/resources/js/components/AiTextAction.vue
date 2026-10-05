<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import type { RichEditorContext } from '@invoiceshelf/modules/frontend'
import { AI_API } from '@/api'
import { errorMessage } from '@/composables/useAiChat'

const props = defineProps<{
  client: AxiosInstance
  context: RichEditorContext
  enabled: boolean
  notify: (type: 'success' | 'error' | 'warning' | 'info', message: string) => void
}>()

const open = ref(false)
const instruction = ref('')
const useContext = ref(false)
const generatedText = ref('')
const loading = ref(false)
const error = ref('')
const canApply = computed(() => generatedText.value.trim().length > 0)

watch(open, (isOpen) => {
  if (!isOpen) {
    instruction.value = ''
    useContext.value = false
    generatedText.value = ''
    error.value = ''
  }
})

async function generate(): Promise<void> {
  if (!instruction.value.trim() || loading.value) return
  loading.value = true
  error.value = ''
  generatedText.value = ''
  try {
    const { data } = await props.client.post<{ text?: string; error?: string; message?: string }>(AI_API.generate, {
      prompt: instruction.value.trim(),
      context: useContext.value ? props.context.getHtml() : undefined,
    })
    if (data.text) {
      generatedText.value = data.text
    } else {
      error.value = data.message ?? data.error ?? 'The assistant could not generate text.'
    }
  } catch (caught: unknown) {
    error.value = errorMessage(caught, 'The assistant could not generate text.')
  } finally {
    loading.value = false
  }
}

function insert(): void {
  props.context.insertContent(generatedText.value)
  props.notify('success', 'AI text inserted.')
  open.value = false
}

function replace(): void {
  props.context.replaceContent(generatedText.value)
  props.notify('success', 'Editor content replaced with AI text.')
  open.value = false
}
</script>

<template>
  <button type="button" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs text-body hover:bg-hover disabled:cursor-not-allowed disabled:opacity-50" :disabled="!enabled" title="Generate with AI" @click="open = true">
    <BaseIcon name="SparklesIcon" class="h-4 w-4 text-primary-500" /> AI write
  </button>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-black/30 p-4" role="presentation" @mousedown.self="open = false">
      <section class="w-full max-w-xl overflow-hidden rounded-lg bg-surface shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="ai-generate-title">
        <header class="flex items-center justify-between border-b border-line-default px-5 py-4">
          <div class="flex items-center gap-2"><BaseIcon name="SparklesIcon" class="h-5 w-5 text-primary-500" /><h2 id="ai-generate-title" class="font-semibold text-heading">Generate text</h2></div>
          <button type="button" class="rounded p-1 text-muted hover:bg-hover hover:text-heading" aria-label="Close" :disabled="loading" @click="open = false"><BaseIcon name="XMarkIcon" class="h-5 w-5" /></button>
        </header>
        <form class="space-y-4 p-5" @submit.prevent="generate">
          <label class="block text-sm font-medium text-heading" for="ai-prompt">What would you like to write?</label>
          <textarea id="ai-prompt" v-model="instruction" rows="3" class="w-full rounded-md border border-line-default bg-surface px-3 py-2 text-sm text-body outline-none focus:ring-1 focus:ring-primary-500" placeholder="For example: Write a polite payment reminder" :disabled="loading" autofocus />
          <label v-if="context.getHtml()" class="flex cursor-pointer items-start gap-2 text-sm text-body"><input v-model="useContext" type="checkbox" class="mt-1"> <span>Use the current editor content as context<br><span class="text-xs text-muted">The content helps the assistant match the document’s tone and details.</span></span></label>
          <p v-if="error" class="rounded bg-alert-error-bg p-3 text-sm text-alert-error-text">{{ error }}</p>
          <section v-if="generatedText" class="rounded-md border border-line-default bg-surface-secondary p-3"><p class="mb-2 text-xs font-medium text-muted">Preview</p><p class="max-h-48 overflow-y-auto whitespace-pre-wrap break-words text-sm text-body">{{ generatedText }}</p></section>
        </form>
        <footer class="flex flex-wrap justify-end gap-2 border-t border-line-default p-4">
          <button type="button" class="rounded px-3 py-2 text-sm text-body hover:bg-hover" :disabled="loading" @click="open = false">Cancel</button>
          <button v-if="canApply" type="button" class="rounded border border-line-default px-3 py-2 text-sm text-body hover:bg-hover" :disabled="loading" @click="replace">Replace</button>
          <button v-if="canApply" type="button" class="rounded border border-line-default px-3 py-2 text-sm text-body hover:bg-hover" :disabled="loading" @click="generate">Regenerate</button>
          <button v-if="canApply" type="button" class="rounded bg-btn-primary px-3 py-2 text-sm font-medium text-white hover:bg-btn-primary-hover" :disabled="loading" @click="insert">Insert</button>
          <button v-else type="button" class="rounded bg-btn-primary px-3 py-2 text-sm font-medium text-white hover:bg-btn-primary-hover disabled:cursor-not-allowed disabled:opacity-50" :disabled="loading || !instruction.trim()" @click="generate">{{ loading ? 'Generating…' : 'Generate' }}</button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>
