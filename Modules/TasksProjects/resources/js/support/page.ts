import { defineComponent, h } from 'vue'
import type { Component } from 'vue'
import type { InvoiceShelfExtensionApi } from '@invoiceshelf/modules/frontend'

export type NotifyType = 'success' | 'error' | 'warning' | 'info'

export type Notify = (type: NotifyType, message: string) => void

/** The module.json slug, which every registered path and route name hangs off. */
export const MODULE = 'tasks-projects'

const ROOT = `/admin/modules/${MODULE}`

/**
 * Where each screen lives.
 *
 * Breadcrumbs and cross-screen links are absolute, because a module page is
 * mounted under the host's `admin` route and a relative link would resolve
 * against whatever the user happened to arrive from.
 */
export const PATHS = {
  tasks: ROOT,
  board: `${ROOT}/board`,
  week: `${ROOT}/week`,
  task: (id: number | string): string => `${ROOT}/tasks/${id}`,
  projects: `${ROOT}/projects`,
  project: (id: number | string): string => `${ROOT}/projects/${id}`,
  reports: `${ROOT}/reports`,
  billing: `${ROOT}/billing`,
  settings: '/admin/settings/modules',
  customer: (id: number): string => `/admin/customers/${id}/view`,
} as const

/** The names the host gives the module's routes, for navigating by name. */
export const ROUTES = {
  tasks: `extension.page.${MODULE}.tasks`,
  list: `extension.page.${MODULE}.tasks.list`,
  board: `extension.page.${MODULE}.tasks.board`,
  week: `extension.page.${MODULE}.tasks.week`,
  task: `extension.page.${MODULE}.task`,
  projects: `extension.page.${MODULE}.projects`,
  project: `extension.page.${MODULE}.project`,
} as const

/**
 * Hand a page the host services it cannot reach on its own.
 *
 * A module bundle runs on the host's Vue instance but not on its Pinia or
 * router injections, so the client, the notifier and the router arrive as
 * props. Route params arrive as attrs, because the host registers module pages
 * with `props: true`, and a tab page also receives whatever its parent passes
 * through `<router-view>`.
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
