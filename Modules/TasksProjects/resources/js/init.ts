import '../css/module.css'
import { messages } from './messages'
import { registerBillingPages } from './registrations/billing'
import { registerProjectPages } from './registrations/projects'
import { registerReportPages } from './registrations/reports'
import { registerTaskPages } from './registrations/tasks'
import { registerTimeTracking } from './registrations/time'

/**
 * Everything the module contributes to the host, one slice per line.
 *
 * Each slice owns its own file: its pages, its strings and whatever lifecycle
 * it needs. Adding a screen never means editing the lines another slice is
 * editing, and the shared page wrapper lives in `support/page.ts` so no slice
 * carries its own copy.
 */
window.InvoiceShelf.booting((_app, _router, extensions) => {
  extensions.addMessages(messages)

  registerTaskPages(extensions)
  registerProjectPages(extensions)
  registerTimeTracking(extensions)
  registerBillingPages(extensions)
  registerReportPages(extensions)
})
