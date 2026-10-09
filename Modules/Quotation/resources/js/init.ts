import '../css/module.css'

import type { AxiosInstance } from 'axios'
import type { Component } from 'vue'

import QuotationFormFields from './components/QuotationFormFields.vue'

interface ComponentExtensionContribution {
  id: string
  component: Component
  priority?: number
  visible?: () => boolean
  props?: Record<string, unknown>
}

interface ExtensionApi {
  readonly client: AxiosInstance
  registerEstimateFormSection(contribution: ComponentExtensionContribution): () => void
  registerEstimateViewMode(contribution: {
    id: string
    value: string
    label: string
    icon: string
    createLink: string
    listLink: string
    ability?: string
    priority?: number
    visible?: () => boolean
  }): () => void
  registerEstimateDocumentMeta(contribution: {
    id: string
    templateName: string
    label: string
    labelPlural: string
    listLink: string
    priority?: number
    // HisabKitab feature — per-template field labels
    dateLabel?: string
    numberLabel?: string
  }): () => void
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
  }
}

window.InvoiceShelf.booting((_app, _router, extensions) => {
  extensions.addMessages({
    en: {
      quotation: {
        title: 'Quotations',
        quotation_items: 'Quotation Items',
        station_name: 'Station Name',
        capacity: 'Capacity',
        rate: 'Rate',
        amount: 'Amount',
        add_item: 'Add Item',
        quotation_settings: 'Quotation Settings',
      },
    },
  })

  // Inject the redesigned items table into the host estimate form when
  // template_name = 'quotation'.
  extensions.registerEstimateFormSection({
    id: 'quotation-fields',
    component: QuotationFormFields,
  })

  // Register view mode in the host estimate index
  extensions.registerEstimateViewMode({
    id: 'quotation-view-mode',
    value: 'quotation',
    label: 'Quotation',
    icon: 'DocumentTextIcon',
    createLink: 'estimates/create?template=quotation',
    listLink: '/admin/estimates?view=quotation',
    ability: 'quotation:view-quotation',
  })

  // Register document meta for breadcrumbs and labels
  extensions.registerEstimateDocumentMeta({
    id: 'quotation-doc-meta',
    templateName: 'quotation',
    label: 'Quotation',
    labelPlural: 'Quotations',
    listLink: '/admin/estimates?view=quotation',
    // HisabKitab feature — per-template field labels
    dateLabel: 'Quotation Date',
    numberLabel: 'Quotation No.',
  })
})
