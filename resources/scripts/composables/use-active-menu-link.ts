// HisabKitab feature

import { computed } from 'vue'
import type { RouteLocationNormalizedLoaded } from 'vue-router'
import { useGlobalStore } from '@/scripts/stores/global.store'
import { useInvoiceStore } from '@/scripts/features/company/invoices/store'
import { useEstimateStore } from '@/scripts/features/company/estimates/store'
// HisabKitab feature
import { extensionRegistry, extensionItems } from '@/scripts/extensions/runtime'

/**
 * Which main-menu entry the current route belongs to: the longest menu link
 * that is the route path or a parent of it, so `/admin/invoices/12/view` marks
 * Invoices and `/admin/settings/tax-types` marks Settings. Shared by the
 * sidebar, the phone tab bar and the More sheet so they always agree.
 */
export function useActiveMenuLink(route: RouteLocationNormalizedLoaded) {
  const globalStore = useGlobalStore()
  const invoiceStore = useInvoiceStore()
  const estimateStore = useEstimateStore()

  // HisabKitab feature — check registered invoice view modes for the current template
  function registeredInvoiceTemplate(): string | null {
    const registeredValues = new Set(
      extensionItems(extensionRegistry.invoiceViewModes.value).map((vm) => vm.value),
    )

    if (route.name === 'invoices.view' || route.name === 'invoices.edit') {
      const tpl = invoiceStore.newInvoice.template_name
      return tpl && registeredValues.has(tpl) ? tpl : null
    }

    if (route.name === 'invoices.create') {
      const tpl = route.query.template
      return typeof tpl === 'string' && registeredValues.has(tpl) ? tpl : null
    }

    return null
  }

  // HisabKitab feature — check registered estimate view modes for the current template
  function registeredEstimateTemplate(): string | null {
    const registeredValues = new Set(
      extensionItems(extensionRegistry.estimateViewModes.value).map((vm) => vm.value),
    )

    if (route.name === 'estimates.view' || route.name === 'estimates.edit') {
      const tpl = estimateStore.newEstimate.template_name
      return tpl && registeredValues.has(tpl) ? tpl : null
    }

    if (route.name === 'estimates.create') {
      const tpl = route.query.template
      return typeof tpl === 'string' && registeredValues.has(tpl) ? tpl : null
    }

    return null
  }

  const activeMenuLink = computed<string | null>(() => {
    const allLinks = globalStore.menuGroups.flat().map((item) => item.link)
    // HisabKitab feature
    const parent = route.path.startsWith('/admin/recurring-costs/')
      ? (route.query.mode === 'EXPENSE' ? '/admin/expenses' : '/admin/bills')
      : route.meta.menuParent
    if (typeof parent === 'string' && allLinks.includes(parent)) return parent

    // HisabKitab feature — registered invoice view mode
    const invoiceTemplate = registeredInvoiceTemplate()
    if (invoiceTemplate) {
      const vm = extensionItems(extensionRegistry.invoiceViewModes.value)
        .find((v) => v.value === invoiceTemplate)
      if (vm && allLinks.includes(vm.listLink)) return vm.listLink
    }

    // HisabKitab feature — registered estimate view mode
    const estimateTemplate = registeredEstimateTemplate()
    if (estimateTemplate) {
      const vm = extensionItems(extensionRegistry.estimateViewModes.value)
        .find((v) => v.value === estimateTemplate)
      if (vm && allLinks.includes(vm.listLink)) return vm.listLink
    }

    const matches = allLinks.filter(
      (url) => route.path === url || route.path.startsWith(url + '/'),
    )

    // HisabKitab feature
    const exactMatch = allLinks.find((url) => url === route.fullPath)
    if (exactMatch) return exactMatch

    return matches.sort((a, b) => b.length - a.length)[0] ?? null
  })

  function hasActiveUrl(url: string): boolean {
    return url === activeMenuLink.value
  }

  return { activeMenuLink, hasActiveUrl }
}
