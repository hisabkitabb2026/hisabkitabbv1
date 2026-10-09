# Host File Patches — Rebase on Upstream InvoiceShelf 3.x

## Why Patches?

Every host file change is either (1) the extension API itself or (2) a hook
point in an existing InvoiceShelf file that calls the API. A module cannot
define the API it calls — the host must provide it. These patches capture all
host-side changes so they can be re-applied after syncing upstream.

## Directory Structure

```
patches/
├── backend/
│   ├── 01-extension-system.patch   (2 files)  — ModuleExtensions.php + CustomerInvoiceScope.php (NEW)
│   └── 02-host-hooks.patch          (20 files) — PHP hook points in app/ + tests/
├── frontend/
│   ├── 01-extension-system.patch   (3 files)  — runtime.ts, ExtensionSlot.vue, use-document-meta.ts
│   └── 02-host-hooks.patch          (29 files) — Vue/TS hook points in resources/scripts/
├── migrations/
│   └── 01-add-tax-id.patch          (1 file)   — tax_id column on addresses table
├── environment/
│   └── 01-deployment.patch          (24 files) — docker, config, branding, observability
├── README.md                        — this file
└── ANALYSIS.md                      — detailed file-by-file breakdown
```

## Application Order (After Upstream Sync)

```bash
git apply patches/backend/01-extension-system.patch
git apply patches/backend/02-host-hooks.patch
git apply patches/frontend/01-extension-system.patch
git apply patches/frontend/02-host-hooks.patch
git apply patches/migrations/01-add-tax-id.patch
# Optional — only for our deployment:
git apply patches/environment/01-deployment.patch

# Reinstall dependencies + migrate + build
composer install && pnpm install
php artisan migrate --force && pnpm build
```

## Where Does a New Host File Change Go?

| File type | Patch folder | Patch file |
|---|---|---|
| New PHP file (extension API) | `backend/` | `01-extension-system.patch` |
| Modified PHP file (hook point) | `backend/` | `02-host-hooks.patch` |
| New Vue/TS file (extension API) | `frontend/` | `01-extension-system.patch` |
| Modified Vue/TS file (hook point) | `frontend/` | `02-host-hooks.patch` |
| New migration | `migrations/` | new `NN-name.patch` |
| Config / docker / branding | `environment/` | `01-deployment.patch` |

After editing a host file, re-run the corresponding `git diff` command below
to regenerate that patch. All patches are `git diff invoiceshelf/3.x` output.

## How to Regenerate Patches

```bash
# Backend 01 — extension system (NEW files)
git add -N app/Domains/Reporting/Queries/CustomerInvoiceScope.php
git diff invoiceshelf/3.x -- app/Support/ModuleExtensions.php app/Domains/Reporting/Queries/CustomerInvoiceScope.php > patches/backend/01-extension-system.patch

# Backend 02 — host hooks (modified PHP files)
git diff invoiceshelf/3.x -- \
  app/Domains/Contacts/Http/Requests/CustomerRequest.php \
  app/Domains/Contacts/Http/Resources/AddressResource.php \
  app/Domains/Contacts/Models/Customer.php \
  app/Domains/Reporting/Http/Controllers/Company/DashboardController.php \
  app/Domains/Reporting/Queries/CashflowQuery.php \
  app/Domains/Reporting/Queries/ReceivablesAgingQuery.php \
  app/Domains/Sales/Application/SerialNumberService.php \
  app/Domains/Sales/Http/Controllers/Company/SerialNumberController.php \
  app/Domains/Sales/Http/Requests/EstimatesRequest.php \
  app/Domains/Sales/Http/Requests/InvoicesRequest.php \
  app/Domains/Sales/Http/Resources/CustomerPortal/InvoiceResource.php \
  app/Domains/Sales/Http/Resources/EstimateItemResource.php \
  app/Domains/Sales/Http/Resources/InvoiceItemResource.php \
  app/Domains/Sales/Http/Resources/InvoiceResource.php \
  app/Domains/Sales/Models/Estimate.php \
  app/Domains/Sales/Models/Invoice.php \
  app/Platform/Operations/Http/Company/BootstrapController.php \
  app/Platform/Pdf/Rendering/ImageUtils.php \
  app/Support/Media/CustomPathGenerator.php \
  tests/Feature/Admin/DashboardPeriodTest.php \
  tests/Feature/Admin/DashboardReceivablesTest.php \
  tests/Feature/Admin/DashboardTest.php \
  tests/Feature/Admin/InvoiceTest.php \
  tests/Pest.php \
  > patches/backend/02-host-hooks.patch

# Frontend 01 — extension system
git diff invoiceshelf/3.x -- \
  resources/scripts/composables/use-document-meta.ts \
  resources/scripts/extensions/ExtensionSlot.vue \
  resources/scripts/extensions/runtime.ts \
  > patches/frontend/01-extension-system.patch

# Frontend 02 — host hooks (modified Vue/TS files)
git diff invoiceshelf/3.x -- \
  resources/scripts/api/services/dashboard.service.ts \
  resources/scripts/api/services/invoice.service.ts \
  resources/scripts/components/base/BaseCustomerSelectPopup.vue \
  resources/scripts/components/base/BaseFileUploader.vue \
  resources/scripts/composables/use-active-menu-link.ts \
  resources/scripts/composables/use-create-actions.ts \
  resources/scripts/features/company/customers/components/CustomerModal.vue \
  resources/scripts/features/company/customers/store.ts \
  resources/scripts/features/company/dashboard/components/ReceivablesHero.vue \
  resources/scripts/features/company/dashboard/store.ts \
  resources/scripts/features/company/dashboard/views/DashboardView.vue \
  resources/scripts/features/company/estimates/components/EstimateBasicFields.vue \
  resources/scripts/features/company/estimates/store.ts \
  resources/scripts/features/company/estimates/views/EstimateCreateView.vue \
  resources/scripts/features/company/estimates/views/EstimateDetailView.vue \
  resources/scripts/features/company/estimates/views/EstimateIndexView.vue \
  resources/scripts/features/company/invoices/components/InvoiceBasicFields.vue \
  resources/scripts/features/company/invoices/components/InvoiceDropdown.vue \
  resources/scripts/features/company/invoices/store.ts \
  resources/scripts/features/company/invoices/views/InvoiceCreateView.vue \
  resources/scripts/features/company/invoices/views/InvoiceDetailView.vue \
  resources/scripts/features/company/invoices/views/InvoiceIndexView.vue \
  resources/scripts/features/company/settings/components/EstimatesTab.vue \
  resources/scripts/features/company/settings/components/InvoicesTab.vue \
  resources/scripts/features/customer-portal/views/CustomerInvoiceDetailView.vue \
  resources/scripts/features/customer-portal/views/CustomerInvoicesView.vue \
  resources/scripts/layouts/partials/MobileTabBar.vue \
  resources/scripts/stores/global.store.ts \
  resources/scripts/utils/format-money.ts \
  > patches/frontend/02-host-hooks.patch

# Migrations
git diff invoiceshelf/3.x -- database/migrations/2026_10_07_000000_add_tax_id_to_addresses_table.php > patches/migrations/01-add-tax-id.patch

# Environment
git diff invoiceshelf/3.x -- \
  .gitignore .php-config/99-upload.ini app/Platform/Operations/helpers.php \
  composer.json config/database.php database/seeders/DemoSeeder.php \
  docker-compose.ec2.yml docker/production/Dockerfile \
  docker/production/docker-compose.mysql.yml docker/production/docker-compose.sqlite.yml \
  infrastructure/observability/ phpunit.xml \
  > patches/environment/01-deployment.patch
```

## Why Not Per-Module Patches?

Many host files are shared across modules (e.g., `InvoiceIndexView.vue` has
changes for LorryReceipt, LrReceipt, AccessRequest, and the core extension
system). Per-module patches would conflict. The extension system is generic —
hooks are no-ops when no modules register, so one combined patch is
conflict-free.

## Detailed Analysis

See `ANALYSIS.md` for the full file-by-file breakdown.
