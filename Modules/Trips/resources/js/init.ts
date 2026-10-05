import '../css/module.css'
import { messages } from './messages'
import { registerReportPages } from './registrations/reports'
import { registerTripPages } from './registrations/trips'

/**
 * Everything the module contributes to the host, one slice per line.
 */
window.InvoiceShelf.booting((_app, _router, extensions) => {
  extensions.addMessages(messages)

  registerTripPages(extensions)
  registerReportPages(extensions)
})
