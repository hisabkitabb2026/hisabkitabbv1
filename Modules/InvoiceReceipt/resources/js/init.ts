import '../css/module.css'

import type { AxiosInstance } from 'axios'
import type { Component } from 'vue'

import OfficeInvoiceFormFields from './components/OfficeInvoiceFormFields.vue'

interface ComponentExtensionContribution {
  id: string
  component: Component
  priority?: number
  visible?: () => boolean
  props?: Record<string, unknown>
}

interface ExtensionApi {
  readonly client: AxiosInstance
  registerInvoiceFormSection(contribution: ComponentExtensionContribution): () => void
  registerInvoiceViewMode(contribution: {
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
  registerInvoiceDocumentMeta(contribution: {
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
  ;(window as any).__invoiceReceiptClient = extensions.client

  extensions.addMessages({
    en: {
      invoice_receipt: {
        title: 'Invoice Receipts',
        gst_tax_through: 'GST Tax Through',
        invoice_receipt_settings: 'Invoice Receipt Settings',
      },
    },
  })

  // Inject office invoice fields into host invoice form when template_name = 'invoice_receipt'
  extensions.registerInvoiceFormSection({
    id: 'office-invoice-fields',
    component: OfficeInvoiceFormFields,
  })

  // Register view mode in the host invoice index
  extensions.registerInvoiceViewMode({
    id: 'invoice-receipt-view-mode',
    value: 'invoice_receipt',
    label: 'Invoice Receipt',
    icon: 'DocumentTextIcon',
    createLink: 'invoices/create?template=invoice_receipt',
    listLink: '/admin/invoices?view=invoice_receipt',
    ability: 'invoice-receipt:view-invoice-receipt',
  })

  // Register document meta for breadcrumbs and labels
  extensions.registerInvoiceDocumentMeta({
    id: 'invoice-receipt-doc-meta',
    templateName: 'invoice_receipt',
    label: 'Invoice Receipt',
    labelPlural: 'Invoice Receipts',
    listLink: '/admin/invoices?view=invoice_receipt',
    // HisabKitab feature — per-template field labels
    dateLabel: 'Receipt Date',
    numberLabel: 'Receipt No.',
  })
})
