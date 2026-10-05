import { computed } from 'vue'
import { useUserStore } from '@/scripts/stores/user.store'
import { useCompanyStore } from '@/scripts/stores/company.store'
// HisabKitab feature
import { useGlobalStore } from '@/scripts/stores/global.store'
import { extensionRegistry, extensionItems } from '@/scripts/extensions/runtime'
import { ABILITIES } from '@/scripts/config/abilities'

export interface CreateAction {
  label: string
  icon: string
  to: string
}

const ACTIONS: Array<CreateAction & { ability: string; menuName?: string }> = [
  { label: 'invoices.new_invoice', icon: 'DocumentTextIcon', to: '/admin/invoices/create', ability: ABILITIES.CREATE_INVOICE, menuName: 'Invoices' },
  { label: 'estimates.new_estimate', icon: 'DocumentIcon', to: '/admin/estimates/create', ability: ABILITIES.CREATE_ESTIMATE, menuName: 'Estimates' },
  { label: 'payments.new_payment', icon: 'CreditCardIcon', to: '/admin/payments/create', ability: ABILITIES.CREATE_PAYMENT },
  { label: 'expenses.new_expense', icon: 'CalculatorIcon', to: '/admin/expenses/create', ability: ABILITIES.CREATE_EXPENSE },
  { label: 'customers.new_customer', icon: 'UserIcon', to: '/admin/customers/create', ability: ABILITIES.CREATE_CUSTOMER },
]

/**
 * The "New …" actions the current user may take, for the header's New menu
 * and the search palette. Empty in administration mode, which has no company
 * to create documents in.
 */
export function useCreateActions() {
  const userStore = useUserStore()
  const companyStore = useCompanyStore()
  // HisabKitab feature
  const globalStore = useGlobalStore()

  // HisabKitab feature — a core menu entry is visible unless a module filter removed it
  const visibleMenuNames = computed(() => new Set(globalStore.menuGroups.flat().map((m) => m.name)))

  const createActions = computed<CreateAction[]>(() => {
    if (companyStore.isAdminMode) {
      return []
    }

    const actions: CreateAction[] = []

    for (const action of ACTIONS) {
      if (!userStore.hasAbilities(action.ability)) continue
      // HisabKitab feature — hide invoice/estimate create actions when their menu is hidden
      if (action.menuName && !visibleMenuNames.value.has(action.menuName)) continue
      actions.push({ label: action.label, icon: action.icon, to: action.to })

      // HisabKitab feature — insert module-registered receipt create actions
      // right after their parent document type
      if (action.menuName === 'Invoices') {
        for (const vm of extensionItems(extensionRegistry.invoiceViewModes.value)) {
          if (vm.ability && !userStore.hasAbilities(vm.ability)) continue
          actions.push({ label: `New ${vm.label}`, icon: vm.icon, to: `/admin/${vm.createLink}` })
        }
      } else if (action.menuName === 'Estimates') {
        for (const vm of extensionItems(extensionRegistry.estimateViewModes.value)) {
          if (vm.ability && !userStore.hasAbilities(vm.ability)) continue
          actions.push({ label: `New ${vm.label}`, icon: vm.icon, to: `/admin/${vm.createLink}` })
        }
      }
    }

    // HisabKitab feature — when the Invoices menu is hidden, the receipt create
    // actions still need to appear, so add them here too
    if (!visibleMenuNames.value.has('Invoices')) {
      for (const vm of extensionItems(extensionRegistry.invoiceViewModes.value)) {
        if (vm.ability && !userStore.hasAbilities(vm.ability)) continue
        actions.push({ label: `New ${vm.label}`, icon: vm.icon, to: `/admin/${vm.createLink}` })
      }
    }
    if (!visibleMenuNames.value.has('Estimates')) {
      for (const vm of extensionItems(extensionRegistry.estimateViewModes.value)) {
        if (vm.ability && !userStore.hasAbilities(vm.ability)) continue
        actions.push({ label: `New ${vm.label}`, icon: vm.icon, to: `/admin/${vm.createLink}` })
      }
    }

    return actions
  })

  return { createActions }
}
