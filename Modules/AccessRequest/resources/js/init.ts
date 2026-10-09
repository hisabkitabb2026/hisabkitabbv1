import '../css/module.css'

import type { AxiosInstance } from 'axios'
import type { Component } from 'vue'
import { defineComponent, h } from 'vue'
import AccessRequestOverlay from './components/AccessRequestOverlay.vue'
import PermissionMissingUI from './components/PermissionMissingUI.vue'

interface ComponentExtensionContribution {
  id: string
  component: unknown
  priority?: number
  visible?: () => boolean
  props?: Record<string, unknown>
}

interface ExtensionApi {
  readonly client: AxiosInstance
  registerCompanyLayoutOverlay(contribution: ComponentExtensionContribution): () => void
  // HisabKitab feature — permission-missing UI for receipt view modes
  registerInvoicePermissionMissing(contribution: ComponentExtensionContribution): () => void
  addMessages(messages: Record<string, Record<string, unknown>>): void
  notify(type: 'success' | 'error' | 'warning' | 'info', message: string): void
}

declare global {
  interface Window {
    InvoiceShelf: {
      booting: (
        callback: (app: unknown, router: unknown, extensions: ExtensionApi) => void,
      ) => void
    }
    __accessRequestClient?: AxiosInstance
  }
}

window.InvoiceShelf.booting((_app, _router, extensions) => {
  // Expose the API client so the overlay component can make API calls
  // without importing the host's axios instance.
  window.__accessRequestClient = extensions.client

  // Register the overlay that renders the access request modal.
  // The host dispatches a 'access-request:open' DOM event with
  // { subject, message, recipient } to trigger it.
  extensions.registerCompanyLayoutOverlay({
    id: 'access-request-overlay',
    component: defineComponent({
      setup: () => () => h(AccessRequestOverlay),
    }),
  })

  // Listen for success/error events from the overlay and show notifications
  window.addEventListener('access-request:success', (event) => {
    const detail = (event as CustomEvent).detail as string
    extensions.notify('success', detail)
  })

  window.addEventListener('access-request:error', (event) => {
    const detail = (event as CustomEvent).detail as string
    extensions.notify('error', detail)
  })

  // HisabKitab feature — register the permission-missing UI component
  // rendered by the host's InvoiceIndexView when the member lacks a receipt ability.
  extensions.registerInvoicePermissionMissing({
    id: 'access-request-permission-missing',
    component: PermissionMissingUI as unknown as Component,
  })
})
