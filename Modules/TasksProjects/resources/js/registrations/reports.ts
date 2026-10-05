import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import { reportMessages } from '@/messages/reports'
import ReportsPage from '@/pages/ReportsPage.vue'
import { resetCustomers } from '@/stores/customers'
import { injectedPage } from '@/support/page'

const MODULE = 'tasks-projects'

/**
 * The reports page, and the one piece of lifecycle it owns.
 *
 * Registered from here rather than from `init.ts` so that a slice of the
 * module owns one file: adding a screen never means editing the same lines
 * another slice is editing. The strings come along for the ride, because the
 * host merges message bundles recursively.
 *
 * The page asks only for `view-own-time`. A caller without `view-all-time`
 * still gets a report; the endpoint narrows it to their own time rather than
 * refusing them, so gating on the wider ability would hide a screen that works.
 */
export function registerReportPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.addMessages(reportMessages)

  extensions.registerPage({
    id: 'reports',
    module: MODULE,
    path: 'reports',
    component: injectedPage(extensions, ReportsPage),
    meta: {
      ability: `${MODULE}:view-own-time`,
      title: 'tasks_projects.reports.title',
    },
  })

  // Contact ids belong to one company, so the map goes with the company.
  extensions.on('company:changing', () => {
    resetCustomers()
  })
}
