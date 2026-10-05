import { computed, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { AI_API } from '@/api'
import type { AiChatMessage, AiConversationSummary } from '@/types/ai'

export function useAiChat(client: AxiosInstance, notify: (type: 'error' | 'success', message: string) => void) {
  const currentConversationId = ref<number | null>(null)
  const messages = ref<AiChatMessage[]>([])
  const conversations = ref<AiConversationSummary[]>([])
  const isLoadingConversations = ref(false)
  const isLoadingConversation = ref(false)
  const isSending = ref(false)
  const lastError = ref<string | null>(null)
  const hasActiveConversation = computed(() => currentConversationId.value !== null)

  function reset(): void {
    currentConversationId.value = null
    messages.value = []
    conversations.value = []
    isLoadingConversations.value = false
    isLoadingConversation.value = false
    isSending.value = false
    lastError.value = null
  }

  function newConversation(): void {
    currentConversationId.value = null
    messages.value = []
    lastError.value = null
  }

  async function refreshConversations(): Promise<void> {
    isLoadingConversations.value = true
    try {
      const { data } = await client.get<{ conversations: AiConversationSummary[] }>(AI_API.conversations)
      conversations.value = data.conversations
    } catch {
      // Conversation history is supplementary; the chat remains usable offline from it.
    } finally {
      isLoadingConversations.value = false
    }
  }

  async function loadConversation(id: number): Promise<void> {
    isLoadingConversation.value = true
    lastError.value = null
    try {
      const { data } = await client.get<{ conversation: AiConversationSummary; messages: AiChatMessage[] }>(AI_API.conversation(id))
      currentConversationId.value = data.conversation.id
      messages.value = data.messages
    } catch (error: unknown) {
      const message = errorMessage(error, 'Unable to load this conversation.')
      lastError.value = message
      notify('error', message)
    } finally {
      isLoadingConversation.value = false
    }
  }

  async function sendMessage(text: string): Promise<void> {
    const content = text.trim()
    if (!content || isSending.value) return

    lastError.value = null
    isSending.value = true
    const optimistic: AiChatMessage = {
      id: -Date.now(),
      role: 'user',
      content,
      created_at: new Date().toISOString(),
    }
    messages.value.push(optimistic)

    try {
      const { data } = await client.post<{ conversation: AiConversationSummary; message: AiChatMessage }>(AI_API.chat, {
        conversation_id: currentConversationId.value,
        message: content,
      })
      currentConversationId.value = data.conversation.id
      messages.value = messages.value.map((message) => message.id === optimistic.id ? { ...optimistic, id: optimistic.id } : message)
      messages.value.push(data.message)
      void refreshConversations()
    } catch (error: unknown) {
      messages.value = messages.value.filter((message) => message.id !== optimistic.id)
      lastError.value = errorMessage(error, 'The assistant could not respond. Please try again.')
    } finally {
      isSending.value = false
    }
  }

  async function renameConversation(id: number, title: string): Promise<void> {
    const nextTitle = title.trim()
    if (!nextTitle) return
    try {
      await client.patch(AI_API.conversation(id), { title: nextTitle })
      const conversation = conversations.value.find((item) => item.id === id)
      if (conversation) conversation.title = nextTitle
    } catch (error: unknown) {
      notify('error', errorMessage(error, 'Unable to rename this conversation.'))
    }
  }

  async function deleteConversation(id: number): Promise<void> {
    try {
      await client.delete(AI_API.conversation(id))
      conversations.value = conversations.value.filter((item) => item.id !== id)
      if (currentConversationId.value === id) newConversation()
    } catch (error: unknown) {
      notify('error', errorMessage(error, 'Unable to delete this conversation.'))
    }
  }

  return {
    currentConversationId, messages, conversations, isLoadingConversations, isLoadingConversation,
    isSending, lastError, hasActiveConversation, reset, newConversation, refreshConversations,
    loadConversation, sendMessage, renameConversation, deleteConversation,
  }
}

export function errorMessage(error: unknown, fallback: string): string {
  if (typeof error === 'object' && error !== null && 'response' in error) {
    const response = (error as { response?: { data?: { message?: unknown } } }).response
    if (typeof response?.data?.message === 'string') return response.data.message
  }
  return error instanceof Error && error.message ? error.message : fallback
}
