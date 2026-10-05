import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'
import TripCreatePage from '@/pages/TripCreatePage.vue'
import TripDetailPage from '@/pages/TripDetailPage.vue'
import TripsBoardView from '@/pages/TripsBoardView.vue'
import TripsListView from '@/pages/TripsListView.vue'
import TripsWeekView from '@/pages/TripsWeekView.vue'
import TripsPage from '@/pages/TripsPage.vue'
import { injectedPage, MODULE } from '@/support/page'

const ability = `${MODULE}:view-trip`

export function registerTripPages(extensions: InvoiceShelfExtensionApi): void {
  extensions.registerPage({
    id: 'trips',
    module: MODULE,
    path: '',
    component: injectedPage(extensions, TripsPage),
    meta: { ability, title: 'trips.title' },
    children: [
      {
        id: 'board',
        module: MODULE,
        path: '',
        component: injectedPage(extensions, TripsBoardView),
        meta: { ability, title: 'trips.title' },
      },
      {
        id: 'list',
        module: MODULE,
        path: 'list',
        component: injectedPage(extensions, TripsListView),
        meta: { ability, title: 'trips.title' },
      },
      {
        id: 'week',
        module: MODULE,
        path: 'week',
        component: injectedPage(extensions, TripsWeekView),
        meta: { ability, title: 'trips.title' },
      },
    ],
  })

  extensions.registerPage({
    id: 'new',
    module: MODULE,
    path: 'new',
    component: injectedPage(extensions, TripCreatePage),
    meta: { ability, title: 'trips.title' },
  })

  extensions.registerPage({
    id: 'trip',
    module: MODULE,
    path: ':id(\\d+)',
    component: injectedPage(extensions, TripDetailPage),
    meta: { ability, title: 'trips.title' },
  })
}

