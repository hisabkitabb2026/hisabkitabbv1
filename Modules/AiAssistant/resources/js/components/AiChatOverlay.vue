<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { useAiChat } from '@/composables/useAiChat'
import type { AiConversationSummary } from '@/types/ai'

const props = defineProps<{
  client: AxiosInstance
  enabled: boolean
  notify: (type: 'success' | 'error' | 'warning' | 'info', message: string) => void
}>()

const open = defineModel<boolean>('open', { default: false })
const chat = useAiChat(props.client, props.notify)
const prompt = ref('')
const messagesEl = ref<HTMLElement | null>(null)
const mobileHistoryOpen = ref(false)
const editingId = ref<number | null>(null)
const editingTitle = ref('')

watch(open, (isOpen) => {
  if (isOpen && props.enabled) void chat.refreshConversations()
})

watch(() => props.enabled, (enabled) => {
  if (!enabled) {
    open.value = false
    chat.reset()
  }
})

watch(() => chat.messages.value.length, async () => {
  await nextTick()
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
})

function close(): void {
  open.value = false
  mobileHistoryOpen.value = false
}

async function selectConversation(conversation: AiConversationSummary): Promise<void> {
  await chat.loadConversation(conversation.id)
  mobileHistoryOpen.value = false
}

async function send(): Promise<void> {
  const content = prompt.value
  prompt.value = ''
  await chat.sendMessage(content)
}

function beginRename(conversation: AiConversationSummary, event: MouseEvent): void {
  event.stopPropagation()
  editingId.value = conversation.id
  editingTitle.value = conversation.title ?? ''
}

async function commitRename(): Promise<void> {
  if (editingId.value !== null) await chat.renameConversation(editingId.value, editingTitle.value)
  editingId.value = null
  editingTitle.value = ''
}

async function removeConversation(conversation: AiConversationSummary, event: MouseEvent): Promise<void> {
  event.stopPropagation()
  if (window.confirm(`Delete “${conversation.title ?? 'Untitled conversation'}”?`)) {
    await chat.deleteConversation(conversation.id)
  }
}

function startNewConversation(): void {
  chat.newConversation()
  mobileHistoryOpen.value = false
}

function onPromptKeydown(event: KeyboardEvent): void {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void send()
  }
}

function renderedAssistantMessage(content: string | null): string {
  return DOMPurify.sanitize(marked.parse(content ?? '', { async: false }) as string)
}
</script>

<template>
  <Teleport to="body">
    <button v-if="open" type="button" class="fixed inset-0 z-40 cursor-default bg-black/20" aria-label="Close AI Assistant" @click="close" />
    <aside v-if="open" class="fixed inset-y-0 right-0 z-50 flex w-full bg-surface shadow-2xl sm:w-[480px] lg:w-[640px]" aria-label="AI Assistant">
      <section class="hidden w-52 shrink-0 border-r border-line-default bg-surface-secondary sm:flex sm:flex-col">
        <div class="border-b border-line-default p-3">
          <button type="button" class="w-full rounded-md bg-btn-primary px-3 py-2 text-xs font-medium text-white hover:bg-btn-primary-hover" @click="startNewConversation">
            + New conversation
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
          <p v-if="chat.isLoadingConversations.value && !chat.conversations.value.length" class="p-2 text-xs text-muted">Loading history…</p>
          <p v-else-if="!chat.conversations.value.length" class="p-2 text-xs text-muted">No conversations yet.</p>
          <ul v-else class="space-y-1">
            <li v-for="conversation in chat.conversations.value" :key="conversation.id">
              <form v-if="editingId === conversation.id" class="flex gap-1 p-1" @submit.prevent="commitRename">
                <input v-model="editingTitle" class="min-w-0 flex-1 rounded border border-line-default bg-surface px-2 py-1 text-xs text-body" aria-label="Conversation title" autofocus @keydown.esc="editingId = null">
                <button type="submit" class="rounded px-1 text-xs text-primary-500 hover:bg-hover" aria-label="Save conversation title">Save</button>
              </form>
              <div v-else class="group flex items-center gap-1 rounded text-sm hover:bg-hover" :class="chat.currentConversationId.value === conversation.id ? 'bg-hover-strong font-semibold text-heading' : 'text-body'">
                <button type="button" class="min-w-0 flex-1 truncate px-2 py-2 text-left" @click="selectConversation(conversation)">{{ conversation.title || 'Untitled conversation' }}</button>
                <span class="hidden shrink-0 group-hover:flex">
                  <button type="button" class="px-1 text-muted hover:text-heading" aria-label="Rename conversation" @click="beginRename(conversation, $event)">✎</button>
                  <button type="button" class="px-1 text-muted hover:text-alert-error-text" aria-label="Delete conversation" @click="removeConversation(conversation, $event)">×</button>
                </span>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <section class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-14 items-center justify-between border-b border-line-default px-3">
          <div class="flex items-center gap-2">
            <button type="button" class="rounded p-1 text-muted hover:bg-hover hover:text-heading sm:hidden" aria-label="Conversation history" @click="mobileHistoryOpen = !mobileHistoryOpen">
              <BaseIcon name="Bars3Icon" class="h-5 w-5" />
            </button>
            <BaseIcon name="SparklesIcon" class="h-5 w-5 text-primary-500" />
            <h2 class="text-sm font-semibold text-heading">AI Assistant</h2>
          </div>
          <div class="flex items-center gap-1">
            <button type="button" class="rounded p-2 text-muted hover:bg-hover hover:text-heading" aria-label="New conversation" title="New conversation" @click="startNewConversation">
              <BaseIcon name="PlusIcon" class="h-4 w-4" />
            </button>
            <button type="button" class="rounded p-2 text-muted hover:bg-hover hover:text-heading" aria-label="Close AI Assistant" @click="close">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>
        </header>

        <div v-if="mobileHistoryOpen" class="border-b border-line-default bg-surface-secondary p-2 sm:hidden">
          <button type="button" class="mb-2 w-full rounded-md bg-btn-primary px-3 py-2 text-xs font-medium text-white" @click="startNewConversation">+ New conversation</button>
          <div class="max-h-48 overflow-y-auto">
            <button v-for="conversation in chat.conversations.value" :key="conversation.id" type="button" class="block w-full truncate rounded px-2 py-2 text-left text-sm text-body hover:bg-hover" @click="selectConversation(conversation)">{{ conversation.title || 'Untitled conversation' }}</button>
            <p v-if="!chat.conversations.value.length" class="p-2 text-xs text-muted">No conversations yet.</p>
          </div>
        </div>

        <main ref="messagesEl" class="flex-1 space-y-3 overflow-y-auto p-4" aria-live="polite">
          <div v-if="chat.isLoadingConversation.value" class="py-12 text-center text-sm text-muted">Loading conversation…</div>
          <div v-else-if="!chat.messages.value.length" class="mx-auto mt-16 max-w-xs text-center text-sm text-muted">
            <BaseIcon name="SparklesIcon" class="mx-auto mb-3 h-10 w-10 text-subtle" />
            <p>Ask for help with invoices, customers, or your business.</p>
          </div>
          <article v-for="message in chat.messages.value" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
            <p v-if="message.role === 'user'" class="max-w-[85%] whitespace-pre-wrap break-words rounded-lg bg-primary-500 px-4 py-2 text-sm text-white">{{ message.content }}</p>
            <!-- eslint-disable-next-line vue/no-v-html -- AI Markdown is sanitized with DOMPurify before rendering. -->
            <div v-else class="max-w-[85%] break-words rounded-lg bg-surface-tertiary px-4 py-2 text-sm text-body [&_a]:text-primary-500 [&_a]:underline [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-3 [&_pre]:mt-3 [&_pre]:overflow-x-auto [&_pre]:rounded [&_pre]:bg-surface [&_pre]:p-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5" v-html="renderedAssistantMessage(message.content)" />
          </article>
          <div v-if="chat.isSending.value" class="inline-flex rounded-lg bg-surface-tertiary px-4 py-2 text-sm italic text-muted">Thinking…</div>
          <p v-if="chat.lastError.value" class="rounded bg-alert-error-bg p-3 text-xs text-alert-error-text">{{ chat.lastError.value }}</p>
        </main>

        <form class="flex items-end gap-2 border-t border-line-default p-3" @submit.prevent="send">
          <textarea v-model="prompt" rows="2" class="min-h-11 flex-1 resize-none rounded-md border border-line-default bg-surface px-3 py-2 text-sm text-body outline-none focus:ring-1 focus:ring-primary-500" placeholder="Ask about your business…" :disabled="!enabled || chat.isSending.value" @keydown="onPromptKeydown" />
          <button type="submit" class="rounded-md bg-btn-primary px-3 py-2 text-sm font-medium text-white hover:bg-btn-primary-hover disabled:cursor-not-allowed disabled:opacity-50" :disabled="!enabled || chat.isSending.value || !prompt.trim()">{{ chat.isSending.value ? 'Sending…' : 'Send' }}</button>
        </form>
      </section>
    </aside>
  </Teleport>
</template>
