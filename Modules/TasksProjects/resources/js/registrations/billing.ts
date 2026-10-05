import { defineComponent, h } from 'vue'
import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import InvoiceNumberModal from '@/components/InvoiceNumberModal.vue'
import { billingMessages } from '@/messages/billing'
import UnbilledTimePage from '@/pages/UnbilledTimePage.vue'
import { resetInvoicing } from '@/stores/invoicing'
import { MODULE, injectedPage } from '@/support/page'

/**
 * What invoicing contributes to the host.
 *
 * Invoicing itself has no screen: it is an action on a task row, a bulk bar, a
 * task page and a project header, and `support/invoicing.ts` is the whole of
 * it. Two things still have to be mounted somewhere, so they are mounted here:
 *
 * - The unbilled time page, for the month-end question the task screens cannot
 *   answer. It keeps the `billing` id and path the wizard had, so a bookmark
 *   and the module's own registered ability both still resolve.
 * - The invoice number dialog, once, in the company layout. The sequence can
 *   run from any screen, and none of them should carry a dialog for a question
 *   only a hand-numbering company is ever asked.
 *
 * Registered from here rather than from `init.ts` so that a slice of the
 * module owns one file: adding a screen never means editing the lines another
 * slice is editing.
 */
export function registerBillingPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.addMessages(billingMessages)

  extensions.registerPage({
    id: 'billing',
    module: MODULE,
    path: 'billing',
    component: injectedPage(extensions, UnbilledTimePage),
    meta: {
      ability: `${MODULE}:invoice-tasks`,
      title: 'tasks_projects.billing.title',
    },
  })

  extensions.registerCompanyLayoutOverlay({
    id: `${MODULE}.invoice-number`,
    component: defineComponent({
      setup: () => () => h(InvoiceNumberModal),
    }),
  })

  // A half-finished invoice and a refused ability both belong to one company.
  extensions.on('company:changing', () => {
    resetInvoicing()
  })
}
