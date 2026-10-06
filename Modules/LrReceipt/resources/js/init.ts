import '../css/module.css'

import type { AxiosInstance } from 'axios'
import type { Component } from 'vue'

import LrReceiptFormFields from './components/LrReceiptFormFields.vue'
import CustomerLrReceiptsView from './pages/CustomerLrReceiptsView.vue'

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
  }): () => void
  registerInvoiceDocumentMeta(contribution: {
    id: string
    templateName: string
    label: string
    labelPlural: string
    listLink: string
    priority?: number
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
  // HisabKitab feature — expose API client for consignee picker
  ;(window as any).__lrReceiptClient = extensions.client
  extensions.addMessages({
    en: {
      lr_receipt: {
        title: 'LR Receipts',
        docket_no: 'Docket No.',
        consignor: 'Consignor',
        consignee: 'Consignee',
        trip_details: 'Trip Details',
        consignment_details: 'Consignment Details',
        freight_details: 'Freight Details',
        from: 'From',
        to: 'To',
        truck_no: 'Truck No',
        mode_of_payment: 'Mode of Payment',
        gst_payable_by: 'GST Payable By',
        description_of_goods: 'Description of Goods',
        hsn_code: 'HSN Code',
        eway_bill_no: 'E-way Bill No',
        actual_weight: 'Actual Weight',
        charged_weight: 'Charged Weight',
        party_invoice_no: 'Party Invoice No.',
        no_of_articles: 'No of Articles',
        packing: 'Packing',
        basic_freight: 'Basic Freight',
        hamali: 'Hamali',
        fov: 'FOV',
        local_collection: 'Local Collection',
        door_delivery: 'Door Delivery',
        docket_charge: 'Docket Charge',
        other_charge: 'Other Charge',
        net_amount: 'Net Amount',
        auto_fill: 'Auto-Fill from Photo',
        my_receipts: 'My LR Receipts',
      },
    },
  })

  // Inject transport fields into host invoice form when template_name = 'lr_receipt'
  extensions.registerInvoiceFormSection({
    id: 'lr-receipt-fields',
    component: LrReceiptFormFields,
  })

  // Register view mode in the host invoice index
  extensions.registerInvoiceViewMode({
    id: 'lr-receipt-view-mode',
    value: 'lr_receipt',
    label: 'LR Receipt',
    icon: 'ClipboardDocumentListIcon',
    createLink: 'invoices/create?template=lr_receipt',
    listLink: '/admin/invoices?view=lr_receipt',
    ability: 'lr-receipt:view-lr-receipt',
  })

  // Register document meta for breadcrumbs and labels
  extensions.registerInvoiceDocumentMeta({
    id: 'lr-receipt-doc-meta',
    templateName: 'lr_receipt',
    label: 'LR Receipt',
    labelPlural: 'LR Receipts',
    listLink: '/admin/invoices?view=lr_receipt',
  })

  // Customer portal page
  extensions.registerPage({
    id: 'customer-lr-receipts',
    module: 'lr-receipt',
    path: 'customer',
    component: CustomerLrReceiptsView,
    meta: { title: 'My LR Receipts' },
  })
})
