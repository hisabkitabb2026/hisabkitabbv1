import { defineComponent, h } from 'vue'
import type { Component } from 'vue'
import type { AxiosInstance } from 'axios'
import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'

export type NotifyType = 'success' | 'error' | 'warning' | 'info'

export type Notify = (type: NotifyType, message: string) => void

/** The module.json slug, which every registered path hangs off. */
export const MODULE = 'trips'

const ROOT = `/admin/modules/${MODULE}`

/** Where each screen lives. */
export const PATHS = {
  board: ROOT,
  list: `${ROOT}/list`,
  week: `${ROOT}/week`,
  new: `${ROOT}/new`,
  trip: (id: number | string): string => `${ROOT}/${id}`,
  reports: `${ROOT}/reports`,
  invoiceView: (id: number | string): string => `/admin/invoices/${id}/view`,
  invoiceCreate: (template: string): string => `/admin/invoices/create?template=${template}`,
} as const

/** The names the host gives the module's routes, for navigating by name. */
export const ROUTES = {
  trips: `extension.page.${MODULE}.trips`,
  board: `extension.page.${MODULE}.trips.board`,
  list: `extension.page.${MODULE}.trips.list`,
  week: `extension.page.${MODULE}.trips.week`,
  trip: `extension.page.${MODULE}.trip`,
  reports: `extension.page.${MODULE}.reports`,
} as const

/**
 * Hand a page the host services it cannot reach on its own: the client, the
 * notifier and the router arrive as props.
 */
export function injectedPage(extensions: InvoiceShelfExtensionApi, page: Component): Component {
  return defineComponent({
    setup: (_props, { attrs }) => () =>
      h(page, {
        ...attrs,
        client: extensions.client,
        notify: (type: NotifyType, message: string): void => {
          extensions.notify(type, message)
        },
        router: extensions.router,
      }),
  })
}
