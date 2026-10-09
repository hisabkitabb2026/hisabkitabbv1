import '../css/module.css'

import type { AxiosInstance } from 'axios'
import type { Component } from 'vue'

import LorryReceiptFormFields from './components/LorryReceiptFormFields.vue'
import CustomerLorryReceiptsView from './pages/CustomerLorryReceiptsView.vue'

interface PageContribution {
  id: string
  module: string
  path: string
  component: Component
  meta?: Record<string, unknown>
}

interface ComponentExtensionContribution {
  id: string
  component: Component
  priority?: number
  visible?: () => boolean
  props?: Record<string, unknown>
}

interface ExtensionApi {
  readonly client: AxiosInstance
  readonly hasAbilities: (ability: string | string[]) => boolean
  registerPage(contribution: PageContribution): () => void
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
    // HisabKitab feature — extra table columns for this view mode
    columns?: Array<{ key: string; label: string }>
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
  registerDashboardCount(contribution: {
    id: string
    key: string
    label: string
    to: string
    value: number
    priority?: number
    visible?: () => boolean
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
  // Expose the API client globally so LorryReceiptFormFields (which runs
  // inside the host invoice form via extension slot) can make API calls
  // to fetch/create LorryPartyProfile records.
  ;(window as any).__lorryReceiptClient = extensions.client
  // Ability check for the signed-in member, used to hide party-profile write
  // controls (Add New / Edit) without the module's manage ability.
  ;(window as any).__lorryReceiptCan = extensions.hasAbilities

  extensions.addMessages({
    en: {
      lorry_receipt: {
        title: 'Lorry Receipts',
        challan_no: 'Challan No.',
        party_name: 'Party Name',
        owner_details: 'Owner Details',
        driver_details: 'Driver Details',
        broker_details: 'Broker Details',
        section_c: 'Section C: Advance Payment',
        section_e: 'Section E: Final Settlement',
        contract_no: 'Contract No',
        lorry_no: 'Lorry No',
        paid_to: 'Paid To',
        lorry_hire_amount: 'Lorry Hire Amount',
        advance_amount: 'Advance Amount',
        received_bilties: 'Received Bilties (Docket Numbers)',
        net_amount_payable: 'Net Amount Payable',
        party_profiles: 'Party Profiles',
        my_receipts: 'My Lorry Receipts',
      },
    },
  })

  // Inject transport fields into host invoice form when template_name = 'lorry_receipt'
  extensions.registerInvoiceFormSection({
    id: 'lorry-receipt-fields',
    component: LorryReceiptFormFields,
  })

  // Register view mode in the host invoice index
  extensions.registerInvoiceViewMode({
    id: 'lorry-receipt-view-mode',
    value: 'lorry_receipt',
    label: 'Lorry Receipt',
    icon: 'TruckIcon',
    createLink: 'invoices/create?template=lorry_receipt',
    listLink: '/admin/invoices?view=lorry_receipt',
    ability: 'lorry-receipt:view-lorry-receipt',
    // HisabKitab feature — extra columns for the lorry receipt list view
    columns: [{ key: 'tr_paid_to', label: 'Paid To' }],
  })

  // Register document meta for breadcrumbs and labels
  extensions.registerInvoiceDocumentMeta({
    id: 'lorry-receipt-doc-meta',
    templateName: 'lorry_receipt',
    label: 'Lorry Receipt',
    labelPlural: 'Lorry Receipts',
    listLink: '/admin/invoices?view=lorry_receipt',
    // HisabKitab feature — per-template field labels
    dateLabel: 'Challan Date',
    numberLabel: 'Challan No.',
  })

  // Customer portal page
  extensions.registerPage({
    id: 'customer-lorry-receipts',
    module: 'lorry-receipt',
    path: 'customer',
    component: CustomerLorryReceiptsView,
    meta: { title: 'My Lorry Receipts' },
  })
})
