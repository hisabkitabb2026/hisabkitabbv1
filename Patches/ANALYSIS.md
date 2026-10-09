# Detailed File-by-File Analysis

## Category 1: Core Extension Infrastructure (Patch 1)

These ARE the registration API. Modules call these from their ServiceProvider
(backend) or init.ts (frontend). The host must provide them.

### `app/Support/ModuleExtensions.php` (NEW, ~300 lines)
Backend static registry. Modules register from ServiceProvider::boot():
- `registerSalesTemplate()` — declare a customer-facing template_name
- `registerSupplierTemplate()` — declare a supplier-side template_name (Lorry Receipt)
- `registerReceiptModule()` — mark a module as replacing the standard invoice
- `receiptModulesEnabled()` — whether any receipt module is active
- `registerDashboardCountProvider()` — dashboard count cards
- `registerInvoiceFilter()` / `registerEstimateFilter()` — custom query filters
- `registerSerialNumberType()` — custom serial number models (Trips)
- `registerInvoiceResourceFields()` / `registerInvoiceItemResourceFields()` / `registerEstimateItemResourceFields()` — extra API fields
- `registerInvoiceUniquenessRule()` / `registerEstimateUniquenessRule()` — number sequence rules
- `registerMenuFilter()` — hide/replace core menu entries
- `flush()` — reset for tests

### `app/Domains/Reporting/Queries/CustomerInvoiceScope.php` (NEW)
Centralised scope that decides which invoice rows are customer-facing sales.
With no receipt module enabled, standard invoices (null or `invoice1` template)
count. Once a receipt module is on, only registered sales templates count;
supplier templates (Lorry Receipt) are never customer figures. Used by
`DashboardController`, `CashflowQuery`, and `ReceivablesAgingQuery`.

### `resources/scripts/extensions/runtime.ts` (MODIFIED)
Frontend extension registry additions:
- `registerData()` — generic data-only registration
- `registerInvoiceViewMode()` / `registerEstimateViewMode()` — view mode entries
- `registerDashboardCount()` — dashboard count cards
- `registerInvoiceFormSection()` / `registerEstimateFormSection()` — form section slots
- `registerInvoiceDocumentMeta()` / `registerEstimateDocumentMeta()` — labels, columns
- `registerActiveMenuResolver()` — sidebar highlight logic
- `hasAbilities()` — Bouncer ability check for modules
- New registry fields: invoiceViewModes, estimateViewModes, dashboardCounts, invoiceDocumentMeta, estimateDocumentMeta, activeMenuResolvers

### `resources/scripts/extensions/ExtensionSlot.vue` (MODIFIED)
- Adds `invoice-form-sections` and `estimate-form-sections` named slots
- Passes `templateName` and `store` as props to slotted components

### `resources/scripts/composables/use-document-meta.ts` (NEW)
Composable that reads registered document meta for the current template_name.

## Category 2: Backend Host Hooks (Patch 2)

Existing InvoiceShelf PHP files modified to call ModuleExtensions.
All hooks are no-ops when no modules register.

| File | Hook |
|---|---|
| `DashboardController.php` | `CustomerInvoiceScope::apply()` filters invoice count, amount due, and recent due list; `ModuleExtensions::dashboardCounts()` adds module count cards |
| `CashflowQuery.php` | `CustomerInvoiceScope::apply()` excludes receipts from cashflow |
| `ReceivablesAgingQuery.php` | `CustomerInvoiceScope::apply()` excludes receipts from receivables aging |
| `SerialNumberController.php` | `ModuleExtensions::hasSerialNumberType()` for Trips serial numbers |
| `EstimatesRequest.php` | `ModuleExtensions::buildEstimateUniquenessRule()` for quotation sequences |
| `InvoicesRequest.php` | `ModuleExtensions::buildInvoiceUniquenessRule()` for receipt sequences |
| `InvoiceResource.php` | `ModuleExtensions::invoiceResourceFields()` appends tr_* fields |
| `InvoiceItemResource.php` | `ModuleExtensions::invoiceItemResourceFields()` |
| `EstimateItemResource.php` | `ModuleExtensions::estimateItemResourceFields()` |
| `CustomerPortal/InvoiceResource.php` | Same — tr_* fields for customer portal |
| `Invoice.php` (model) | `template_name` filter in `scopeApplyFilters()`: `one-time` = null/invoice1, other = exact match |
| `Estimate.php` (model) | Same `template_name` filter for estimates/quotations |
| `Customer.php` (model) | `type` filter (CONSIGNEE, CUSTOMER, etc.) |
| `BootstrapController.php` | `ModuleExtensions::applyMenuFilters()` + eager-load `owner` |
| `ImageUtils.php` | Null-safety guard in `toBase64Src()` — return `''` if path missing |
| `CustomPathGenerator.php` | `'trip' => 'Trips'` media path |
| `DashboardReceivablesTest.php` | Tests for `CustomerInvoiceScope` — receipt modules exclude standard invoices from receivables, supplier templates never count, sales counts only customer receipts |
| `DashboardPeriodTest.php` | Period filter tests adapted for receipt scope |
| `DashboardTest.php` | Dashboard rendering tests adapted for receipt scope |
| `InvoiceTest.php` | `template_name` filter test |
| `Pest.php` | `ModuleExtensions::flush()` in `afterEach` |

## Category 3: Frontend Host Hooks (Patch 3)

Existing InvoiceShelf Vue/TS files modified for module support.

### API Services
- `dashboard.service.ts` — adds `module_counts` to DashboardResponse type
- `invoice.service.ts` — adds `template_name` to InvoiceListParams

### Composables
- `use-active-menu-link.ts` — uses extensionRegistry.activeMenuResolvers for module view modes
- `use-create-actions.ts` — uses extension registry for module create actions
- `use-document-meta.ts` (NEW) — reads registered document meta

### Dashboard
- `store.ts` — adds module_counts to state
- `DashboardView.vue` — renders module count cards from registered dashboardCounts
- `ReceivablesHero.vue` — links to first registered receipt view when Invoices hidden

### Invoices
- `InvoiceIndexView.vue` — view mode switcher, template_name filtering, conditional columns (tr_paid_to), permission-missing UI, document meta labels, Lorry Receipt Total/Amount Due computed from tr_* fields (advance + net amount payable, 0 after final settlement)
- `InvoiceCreateView.vue` — ExtensionSlot, `?template=` query param
- `InvoiceDetailView.vue` — template-aware labels, sidebar filtering, breadcrumbs
- `InvoiceBasicFields.vue` — template-aware field labels
- `InvoiceDropdown.vue` — LR/Lorry receipt dropdown items
- `store.ts` — setTemplate(), template_name in state
- `ConsigneeSelectPopup.vue` (NEW) — consignee picker for LorryReceipt

### Estimates
- `EstimateIndexView.vue` — view mode switcher, template_name filtering, document meta labels
- `EstimateCreateView.vue` — ExtensionSlot, `?template=` query param
- `EstimateDetailView.vue` — template-aware labels, breadcrumbs
- `EstimateBasicFields.vue` — template-aware field labels
- `store.ts` — template_name in state

### Customer Portal
- `CustomerInvoiceDetailView.vue` — GST tax payable by display
- `CustomerInvoicesView.vue` — GST display in invoice list

### Layout / Utils
- `MobileTabBar.vue` — custom mobile tabs when receipt modules are enabled (Invoice Receipt, Lorry Receipt, Trips)
- `format-money.ts` — Indian numbering system (2-digit grouping: 12,00,000) for INR; Western 3-digit grouping for everything else

## Category 4: Environment Customizations (Patch 4)

NOT module-related. Deployment/environment-specific changes.

| File | Change | Reason |
|---|---|---|
| `.gitignore` | Track Modules/ subdirs, ignore /services | Module development |
| `.php-config/99-upload.ini` | PHP upload temp dir | Server config |
| `composer.json` | merge-dev=true, github-protocols, modules repo | Module composer merging |
| `config/database.php` | MySQL dump skip_ssl | Backup config |
| `DemoSeeder.php` | Mukesh, HisabKitabb, INR | Local demo data |
| `docker/production/Dockerfile` | Add git package | Deployment |
| `docker/production/docker-compose.*.yml` | Ports, env vars | Deployment |
| `docker-compose.ec2.yml` | EC2 config | Deployment |
| `helpers.php` | "HisabKitabb" title | Branding |
| `BaseFileUploader.vue` | Avatar alt text fix | Bug fix |
| `phpunit.xml` | Module test directories | Testing |
| `infrastructure/observability/*` | Prometheus/Grafana/ELK (14 files) | Monitoring |

