/**
 * ChangeName module — frontend init.
 *
 * Overrides all user-visible "InvoiceShelf" strings with "HisabKitab":
 *
 * 1. i18n strings — via the extension API's addMessages hook. The host's
 *    mergeMessageObjects function recursively merges locale trees, so only
 *    the leaf keys provided here are replaced.
 *
 * 2. SVG wordmark — the MainLogo component draws "InvoiceShelf" as vector
 *    path data (not text). We inject CSS to hide the wordmark path immediately
 *    (prevents flash), then use a MutationObserver to replace it with a
 *    <text> element saying "HisabKitab" after Vue renders.
 *
 * 3. Hardcoded text — "Pair this InvoiceShelf instance" in ModuleIndexView
 *    is not an i18n string. We replace "InvoiceShelf" in text nodes via the
 *    same observer.
 *
 * 4. Accessibility — alt attributes and aria-labels are updated.
 *
 * This script is only loaded when the module is enabled (the Registry only
 * registers scripts for enabled modules), so disabling the module restores
 * the original "InvoiceShelf" branding everywhere.
 */

interface ExtensionApi {
  addMessages(messages: Record<string, Record<string, unknown>>): void
}

declare global {
  interface Window {
    InvoiceShelf: {
      booting: (
        callback: (app: unknown, router: unknown, extensions: ExtensionApi) => void,
      ) => void
    }
  }
}

// --- Constants ---
const SVG_NS = 'http://www.w3.org/2000/svg'
const OLD_NAME = 'InvoiceShelf'
const NEW_NAME = 'HisabKitab'

// --- CSS: hide the wordmark path immediately to prevent flash ---
const styleEl = document.createElement('style')
styleEl.textContent = `svg[aria-label="${OLD_NAME}"] > path{opacity:0!important}`
document.head.appendChild(styleEl)

// --- DOM rebranding via MutationObserver ---
let scheduled = false

function process(): void {
  scheduled = false

  // 1. Replace MainLogo SVG wordmark with <text> element
  document
    .querySelectorAll<SVGSVGElement>(`svg[aria-label="${OLD_NAME}"]:not([data-rebranded])`)
    .forEach((svg) => {
      svg.querySelectorAll(':scope > path').forEach((p) => p.remove())
      const text = document.createElementNS(SVG_NS, 'text')
      text.setAttribute('x', '60')
      text.setAttribute('y', '33')
      text.setAttribute('fill', 'currentColor')
      text.setAttribute('font-size', '24')
      text.setAttribute('font-weight', '600')
      text.setAttribute('font-family', 'Poppins, Geologica, sans-serif')
      text.textContent = NEW_NAME
      svg.appendChild(text)
      svg.setAttribute('aria-label', NEW_NAME)
      svg.setAttribute('data-rebranded', '')
    })

  // 2. Update img alt attributes
  document.querySelectorAll(`img[alt="${OLD_NAME}"]`).forEach((img) => img.setAttribute('alt', NEW_NAME))
  document.querySelectorAll(`img[alt="${OLD_NAME} Logo"]`).forEach((img) => img.setAttribute('alt', `${NEW_NAME} Logo`))

  // 3. Replace "InvoiceShelf" in text nodes (e.g. "Pair this InvoiceShelf instance")
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
    acceptNode(node: Text): number {
      return node.textContent?.includes(OLD_NAME) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
    },
  })
  const textNodes: Text[] = []
  let n: Node | null
  while ((n = walker.nextNode())) textNodes.push(n as Text)
  textNodes.forEach((tn) => {
    if (tn.textContent) tn.textContent = tn.textContent.replaceAll(OLD_NAME, NEW_NAME)
  })
}

function schedule(): void {
  if (scheduled) return
  scheduled = true
  requestAnimationFrame(process)
}

window.InvoiceShelf.booting((_app, _router, extensions) => {
  // --- i18n string overrides ---
  extensions.addMessages({
    en: {
      client: {
        connect_description: 'Enter the address of the HisabKitab server you want to use.',
        app_lock_reason: 'Unlock HisabKitab',
        app_lock_locked_heading: 'HisabKitab is locked',
      },
      mcp: {
        consent: {
          subtitle: '{client} is asking to use your HisabKitab account.',
        },
        connected_apps: {
          description:
            'AI assistants you have connected to HisabKitab. Each works in one of your companies, with the access you gave it.',
          connect_description:
            'Add this server to an assistant that supports MCP. It opens HisabKitab in your browser, where you sign in and choose the company and what the assistant may do.',
        },
        admin: {
          description:
            'Let AI assistants such as Claude or ChatGPT work with HisabKitab through the Model Context Protocol (MCP). Each user connects their own assistant and chooses the company and what the assistant may do.',
          insecure_description:
            'Assistants refuse servers without HTTPS, and sign-in tokens would travel unencrypted. Serve HisabKitab over HTTPS before allowing assistants.',
        },
      },
      settings: {
        company_info: {
          section_description:
            'Information about your company that will be displayed on invoices, estimates and other documents created by HisabKitab.',
        },
        exchange_rate: {
          description:
            'Please enter exchange rate of all the currencies mentioned below to help HisabKitab properly calculate the amounts in {currency}.',
        },
        tax_types: {
          description:
            'You can add or Remove Taxes as you please. HisabKitab supports Taxes on Individual Items as well as on the invoice.',
        },
        update_app: {
          description:
            'You can easily update HisabKitab by checking for a new update by clicking the button below',
          client_description:
            'Check the server this app is connected to for a new HisabKitab release and install it. The app itself is updated through your device\'s app store.',
        },
        disk: {
          description:
            'By default, HisabKitab will use your local disk for saving backups, avatar and other image files. You can configure more than one disk drivers like DigitalOcean, S3 and Dropbox according to your preference.',
          confirm_delete:
            'Your existing files & folders in the specified disk will not be affected but your disk configuration will be deleted from HisabKitab',
        },
      },
      wizard: {
        install_language: {
          description: 'Select language wizard to install HisabKitab',
        },
        verify_domain: {
          desc: 'HisabKitab uses Session based authentication which requires domain verification for security purposes. Please enter the domain on which you will be accessing your web application.',
        },
        req: {
          system_req_desc:
            'HisabKitab has a few server requirements. Make sure that your server has the required php version and all the extensions mentioned below.',
        },
      },
      demo: {
        banner:
          'This is the HisabKitab demo. Everyone shares it, and changes are wiped at every reset.',
      },
    },
  })

  // --- Start DOM rebranding observer ---
  const start = () => {
    const observer = new MutationObserver(schedule)
    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    })
    process()
  }

  if (document.body) {
    start()
  } else {
    document.addEventListener('DOMContentLoaded', start)
  }
})
