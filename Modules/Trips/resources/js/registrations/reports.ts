import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import TripReportsPage from '@/pages/TripReportsPage.vue'
import { injectedPage, MODULE } from '@/support/page'

const ability = `${MODULE}:view-trip`

export function registerReportPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.registerPage({
    id: 'reports',
    module: MODULE,
    path: 'reports',
    component: injectedPage(extensions, TripReportsPage),
    meta: { ability, title: 'Trip Reports' },
  })
}
