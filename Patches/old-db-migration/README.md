# OLD DB → New DB Migration Guide

> **Source:** `ssgls_backup_06-10-2026_02-00.sql` (MySQL dump of the old `ssgls` database)
> **Target:** Fresh `invoiceshelf_production` database (post `php artisan reset:app --force` + module migrations)
> **Date documented:** 2026-10-08

This guide is the canonical reference for migrating data from the old SSGLS
database into the new HisabKitabb (InvoiceShelf v3) schema. Follow the steps
in order. Every table, column mapping, and edge case is documented below.

---

## 1. Prerequisites

### 1.1 Prepare the target database

```bash
# 1. Fresh DB (drops all tables, re-runs core migrations, seeds demo data)
php artisan reset:app --force

# 2. Enable all modules
php artisan module:enable LorryReceipt
php artisan module:enable LrReceipt
php artisan module:enable AiAssistant
php artisan module:enable ChangeName
php artisan module:enable InvoiceReceipt
php artisan module:enable Quotation
php artisan module:enable Trips
php artisan module:enable TasksProjects

# 3. Run module migrations (adds tr_* columns, tr_lorry_party_profiles, etc.)
php artisan module:migrate LorryReceipt --force
php artisan module:migrate LrReceipt --force
php artisan module:migrate AiAssistant --force
php artisan module:migrate ChangeName --force
php artisan module:migrate Quotation --force
php artisan module:migrate Trips --force
php artisan module:migrate TasksProjects --force

# 4. Clear seeded demo data (we want a clean target, not demo data)
php artisan tinker --execute="
  DB::statement('SET FOREIGN_KEY_CHECKS=0');
  foreach (['invoices','invoice_items','estimates','estimate_items','customers','expenses','items','custom_fields','custom_field_values','addresses','units','payment_methods','notes','media','email_logs','exchange_rate_logs','file_disks','transactions','recurring_invoices','customer_payments','customer_payment_allocations','tr_lorry_party_profiles','tr_banks'] as \$t) {
    DB::table(\$t)->truncate();
  }
  DB::table('users')->where('email', '!=', 'admin@invoiceshelf.com')->delete();
  DB::table('companies')->where('slug', '!=', 'xyz')->delete();
  DB::statement('SET FOREIGN_KEY_CHECKS=1');
"
```

### 1.2 Import the old DB into a temporary database

```bash
mysql -u root -e "CREATE DATABASE ssgls_old"
mysql -u root ssgls_old < /home/mukesh_sm/ssgls/Modules/ssgls_backup_06-10-2026_02-00.sql
```

All migration SQL below assumes the old data is in database `ssgls_old` and
the new data goes into `invoiceshelf_production` (the default connection).

---

## 2. Table Inventory

### 2.1 Tables in OLD but NOT in NEW (must be transformed or dropped)

| Old Table | Rows | Action |
|---|---|---|
| `lorry_receipts` | 115 | Merge into `invoices` with `template_name='lorry_receipt'`, map columns to `tr_*` |
| `lorry_party_profiles` | 302 | Migrate to `tr_lorry_party_profiles` |
| `payments` | 9 | Split into `customer_payments` + `customer_payment_allocations` |
| `transport_invoices` | 0 | **Skip** (no data, table deleted) |
| `transport_invoice_rows` | 0 | **Skip** (no data, table deleted) |
| `chatbot_histories` | 0 | **Skip** (no data, table deleted) |

### 2.2 Tables in NEW but NOT in OLD (leave empty or seed separately)

| New Table | Notes |
|---|---|
| `bills`, `bill_items` | Purchasing module — no old data |
| `suppliers` | Purchasing module — no old data |
| `supplier_payments`, `supplier_credits`, `supplier_refunds` | Purchasing module |
| `supplier_payment_allocations`, `supplier_credit_allocations`, `supplier_credit_items` | Purchasing module |
| `customer_payments`, `customer_payment_allocations` | Replaces old `payments` table |
| `company_invitations` | New feature |
| `impersonation_logs` | New feature |
| `legacy_payment_links` | Migration helper table |
| `marketplace_credentials`, `marketplace_operations` | Marketplace feature |
| `mcp_activity`, `mcp_connections` | MCP server feature |
| `oauth_access_tokens`, `oauth_auth_codes`, `oauth_clients`, `oauth_refresh_tokens` | OAuth (Passport) |
| `recurrence_occurrences` | Recurring invoice tracking |
| `recurring_costs` | Recurring expenses |
| `role_presets` | Role preset feature |
| `tr_lorry_party_profiles` | Replaces old `lorry_party_profiles` |
| `tr_banks` | LorryReceipt module |
| `trips`, `trip_statuses`, `trip_receipts`, `trip_events`, `trip_expenses` | Trips module |
| `tp_projects`, `tp_project_members`, `tp_task_statuses`, `tp_tasks`, `tp_time_entries` | TasksProjects module |
| `ai_conversations`, `ai_messages` | AiAssistant module |

### 2.3 Tables in BOTH (direct migration with column mapping)

See [Section 4](#4-column-level-differences-for-shared-tables) for column-by-column mapping.

---

## 3. Migration Order (Dependency-Safe)

**Run in this exact order** to respect foreign key relationships:

1. `currencies` (reference data, already seeded — skip)
2. `countries` (reference data, already seeded — skip)
3. `users` (keep existing admin, add old users)
4. `companies` (keep existing, add old companies)
5. `user_company` (link users to companies)
6. `company_settings` (per-company configuration)
7. `settings` (global settings)
8. `user_settings` (per-user preferences)
9. `payment_methods` (company payment methods)
10. `file_disks` (file storage config)
11. `units` (measurement units)
12. `tax_types` (tax type definitions)
13. `customers` (customer records)
14. `addresses` (customer/company addresses)
15. `items` (product/service catalog)
16. `expense_categories` (expense categories)
17. `tr_lorry_party_profiles` (party profiles — needed before invoices)
18. `tr_banks` (bank master — needed before invoices)
19. `invoices` (all invoice types: office_invoice, lr_receipt, lorry_receipt)
20. `invoice_items` (invoice line items)
21. `estimates` (quotations/estimates)
22. `estimate_items` (estimate line items)
23. `expenses` (expense records)
24. `customer_payments` (payment headers — from old payments table)
25. `customer_payment_allocations` (payment → invoice links)
26. `taxes` (tax records for invoices/estimates)
27. `transactions` (invoice transactions)
28. `recurring_invoices` (recurring invoice schedules)
29. `custom_fields` (custom field definitions)
30. `custom_field_values` (custom field answers)
31. `media` (uploaded files/logos)
32. `email_logs` (sent email log)
33. `exchange_rate_providers` (exchange rate config)
34. `exchange_rate_logs` (exchange rate history)
35. `notes` (note library)
36. `abilities` (Bouncer abilities — re-seed, don't copy)
37. `roles` (Bouncer roles — re-seed, don't copy)
38. `assigned_roles` (role assignments)
39. `permissions` (Bouncer permissions)
40. `modules` (module registry — already set up)

---

## 4. Column-Level Differences for Shared Tables

For each table that exists in both old and new DB, the columns that differ
are listed. Columns not mentioned are identical and can be copied directly.

### 4.1 `users`
Identical schema. Copy directly.

### 4.2 `companies`

| Old Column | New Column | Notes |
|---|---|---|
| `billing_branch_name_address` | — | **Dropped.** Store in `company_settings` if needed. |
| `enrollment_no` | — | **Dropped.** Store in `company_settings` if needed. |
| `gstin` | — | **Dropped.** Use `vat_id` instead (already exists). |
| `pan_no` | — | **Dropped.** Store in `company_settings` if needed. |
| `tagline` | — | **Dropped.** Store in `company_settings` if needed. |
| `top_heading` | — | **Dropped.** Store in `company_settings` if needed. |

**Migration:** Copy `id, name, logo, unique_hash, vat_id, tax_id, created_at, updated_at, slug, owner_id` directly. Map old `gstin` → `vat_id` if `vat_id` is NULL.

### 4.3 `customers`

| Old Column | New Column | Notes |
|---|---|---|
| `type` | — | **Dropped.** Was used for party type (OWNER, DRIVER, BROKER). Now handled by `tr_lorry_party_profiles.type`. |
| `updated_by` | — | **Dropped.** Audit column removed. |

**Migration:** Copy all columns except `type` and `updated_by`.

### 4.4 `invoices`

| Old Column | New Column | Notes |
|---|---|---|
| — | `type` | **New.** Set to `NULL` or `invoice` for all old records. |
| — | `related_invoice_id` | **New.** Set to `NULL` (no credit notes in old DB). |
| — | `credit_reason` | **New.** Set to `NULL`. |
| `consignee_customer_id` | `tr_consignee_customer_id` | **Renamed.** Map directly. |
| `lorry_receipt_id` | — | **Dropped.** Lorry receipt data now lives in `tr_*` columns on the same row. |
| `date_created` | — | **Dropped.** Use `created_at`. |
| `date_modified` | — | **Dropped.** Use `updated_at`. |
| `modified_dates` | — | **Dropped.** |
| `amount_debit` | — | **Dropped.** LR receipt debit/credit tracking removed. |
| `amount_credit` | — | **Dropped.** |
| `amount_debit_date` | — | **Dropped.** |
| `amount_credit_date` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |

**`template_name` values in old DB:**
- `office_invoice` (87 rows) — regular invoices
- `lr_receipt` (258 rows) — LR receipts
- `lorry_receipt` (115 rows) — lorry receipts (linked to `lorry_receipts` table via `lorry_receipt_id`)

**For lorry_receipt invoices:** After copying the invoice row, merge data from
the corresponding `lorry_receipts` row into the `tr_*` columns. See [Section 5](#5-lorry_receipts--invoices-migration).

### 4.5 `invoice_items`

| Old Column | New Column | Notes |
|---|---|---|
| `consignment_number` | `tr_consignment_number` | **Renamed.** Map directly. |
| — | `source_invoice_item_id` | **New.** Set to `NULL` (credit note support). |

### 4.6 `estimates`

| Old Column | New Column | Notes |
|---|---|---|
| `updated_by` | — | **Dropped.** |
| — | `tr_quotation_subject` | **New** (Quotation module). Set to `NULL`. |
| — | `tr_validity_days` | **New.** Set to `NULL`. |
| — | `tr_payment_terms` | **New.** Set to `NULL`. |
| — | `tr_delivery_terms` | **New.** Set to `NULL`. |

### 4.7 `estimate_items`

| Old Column | New Column | Notes |
|---|---|---|
| `truck_type` | — | **Dropped.** No direct equivalent. |
| `weight` | — | **Dropped.** No direct equivalent. |
| — | `tr_station_name` | **New** (Quotation module). Set to `NULL`. |
| — | `tr_rate_9mt` through `tr_rate_30mt` | **New.** Set to `NULL`. |

### 4.8 `addresses`

| Old Column | New Column | Notes |
|---|---|---|
| — | `tax_id` | **New.** Set to `NULL` (old DB doesn't have it). |

### 4.9 `expenses`

| Old Column | New Column | Notes |
|---|---|---|
| `auto_generated` | — | **Dropped.** |
| `invoice_id` | — | **Dropped.** |
| `payment_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |
| — | `supplier_id` | **New.** Set to `NULL` (purchasing module). |

### 4.10 `expense_categories`

| Old Column | New Column | Notes |
|---|---|---|
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |

### 4.11 `items`

| Old Column | New Column | Notes |
|---|---|---|
| `updated_by` | — | **Dropped.** |

### 4.12 `tax_types`

| Old Column | New Column | Notes |
|---|---|---|
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |
| — | `transaction_type` | **New.** Set to `'sales'` (default). |

### 4.13 `taxes`

| Old Column | New Column | Notes |
|---|---|---|
| — | `expense_id` | **New.** Set to `NULL`. |

### 4.14 `units`

| Old Column | New Column | Notes |
|---|---|---|
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |

### 4.15 `notes`

| Old Column | New Column | Notes |
|---|---|---|
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |

### 4.16 `payment_methods`

| Old Column | New Column | Notes |
|---|---|---|
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |

### 4.17 `recurring_invoices`

| Old Column | New Column | Notes |
|---|---|---|
| `updated_by` | — | **Dropped.** |
| — | `last_error` | **New.** Set to `NULL`. |
| — | `notify_creator` | **New.** Set to `0`. |

### 4.18 `custom_fields`

| Old Column | New Column | Notes |
|---|---|---|
| — | `placement` | **New.** Set to `'internal'` (default). |
| — | `validation` | **New.** Set to `NULL`. |

### 4.19 All other shared tables

These tables are **identical** in old and new schema — copy directly:
`abilities`, `assigned_roles`, `company_settings`, `currencies`, `countries`,
`custom_field_values`, `email_logs`, `exchange_rate_logs`, `exchange_rate_providers`,
`file_disks`, `media`, `permissions`, `roles`, `settings`, `transactions`,
`user_company`, `user_settings`.

---

## 5. `lorry_receipts` → `invoices` Migration

The old `lorry_receipts` table (115 rows) stored lorry receipt data separately.
In the new schema, this data lives directly on the `invoices` row in `tr_*`
columns. Each old `lorry_receipts` row has a corresponding `invoices` row
(linked via `invoices.lorry_receipt_id = lorry_receipts.id`).

### 5.1 Field mapping: `lorry_receipts` → `invoices.tr_*`

| Old `lorry_receipts` column | New `invoices` column |
|---|---|
| `owner_customer_id` | `tr_owner_customer_id` |
| `driver_customer_id` | `tr_driver_customer_id` |
| `broker_customer_id` | `tr_broker_customer_id` |
| `contract_no` | `tr_contract_no` |
| `from_code` | `tr_from_code` |
| `from_name` | `tr_from_name` |
| `to_code` | `tr_to_code` |
| `to_name` | `tr_to_name` |
| `lorry_no` | `tr_lorry_no` |
| `no_of_pages` | `tr_no_of_pages` |
| `no_of_pkgs` | `tr_no_of_packages` |
| `actual_weight` | `tr_actual_weight` |
| `charge_weight` | `tr_charged_weight` |
| `regd_at` | `tr_regd_at` |
| `body_type` | `tr_body_type` |
| `make` | `tr_make` |
| `vehicle_model` | `tr_vehicle_model` |
| `colour` | `tr_colour` |
| `chasis_no` | `tr_chasis_no` |
| `engine_no` | `tr_engine_no` |
| `paid_to` | `tr_paid_to` |
| `lorry_hire_amount` | `tr_lorry_hire_amount` |
| `other_charges_amount` | `tr_other_charges_amount` |
| `advance_amount` | `tr_advance_amount` |
| `advance_cash_cheque_no` | `tr_advance_cash_cheque_no` |
| `advance_on` | `tr_advance_on` |
| `advance_bank` | `tr_advance_bank` |
| `received_no_bilties` | `tr_received_no_bilties` |
| `detention_amount` | `tr_detention_amount` |
| `extra_hire_amount` | `tr_extra_hire_amount` |
| `final_other_amount` | `tr_final_other_amount` |
| `final_balance_paid_at` | `tr_final_balance_paid_at` |
| `final_balance_on` | `tr_final_balance_on` |
| `net_amount_payable` | `tr_net_amount_payable` |
| `final_cash_cheque_no` | `tr_final_cash_cheque_no` |
| `final_bank` | `tr_final_bank` |
| `final_payment_received_by` | `tr_final_payment_received_by` |
| `owner_name` | `tr_owner_name` |
| `owner_address` | `tr_owner_address` |
| `owner_phone` | `tr_owner_phone` |
| `owner_bank_account_no` | `tr_owner_bank_account_no` |
| `driver_name` | `tr_driver_name` |
| `driver_address` | `tr_driver_address` |
| `driver_licence_no` | `tr_driver_licence_no` |
| `driver_licence_date` | `tr_driver_licence_date` |
| `driver_licence_issued_by` | `tr_driver_licence_issued_by` |
| `driver_rto_address` | `tr_driver_rto_address` |
| `driver_valid_up_to` | `tr_driver_valid_up_to` |
| `driver_bank_account_no` | `tr_driver_bank_account_no` |
| `driver_place` | `tr_driver_place` |
| `broker_name` | `tr_broker_name` |
| `broker_address` | `tr_broker_address` |
| `broker_phone` | `tr_broker_phone` |
| `broker_bank_account_no` | `tr_broker_bank_account_no` |
| `balance_payable_code` | `tr_balance_payable_code` |
| `balance_payable_at` | `tr_balance_payable_at` |
| `balance_rupees` | `tr_balance_rupees` |
| `balance_rupees_only` | `tr_balance_rupees_only` |
| `gross_hire_amount` | `tr_gross_hire_amount` |
| `gross_hire_rupees` | `tr_gross_hire_rupees` |
| `hire_prepared_by` | `tr_hire_prepared_by` |
| `loaded_by` | `tr_loaded_by` |
| `final_paid_to` | `tr_final_paid_to` |
| `less_advance_other_branch_amount` | `tr_less_advance_other_branch_amount` |
| `less_deduction_claims_amount` | `tr_less_deduction_claims_amount` |
| `advice_no` | `tr_advice_no` |
| `advice_date` | `tr_advice_date` |
| `destination_broker_name` | `tr_dest_broker_name` |
| `destination_broker_address` | `tr_dest_broker_address` |
| `financer_name` | `tr_financer_name` |
| `financer_address` | `tr_financer_address` |

### 5.2 Columns in old `lorry_receipts` with NO new equivalent (dropped)

These columns have no `tr_*` counterpart in the new `invoices` table. Their data
is lost unless stored elsewhere:

- `challan_no`, `rate`, `distance_kms`, `fitness_validity`,
  `insurance_valid_upto`, `insurance_certificate_no`, `insurance_division_no`,
  `insured_with`, `road_permit_no`, `permit_status_upto`, `permit_valid_in`,
  `permit_date`, `owner_code`, `loading_remarks`, `advance_received_by`,
  `final_cash_cheque_on`, `final_rupees_only`, `final_passed_by`,
  `final_certified_by`, `final_prepared_by`, `final_total_extra_amount`,
  `final_balance_code`, `balance_amount`, `grand_total_amount`,
  `hire_passed_by`, `hire_certified_by`, `owner_pan_no`, `broker_pan_no`,
  `date_created`, `date_modified`, `modified_dates`

### 5.3 Migration SQL

```sql
-- After invoices are copied, merge lorry_receipts data into tr_* columns
UPDATE invoices i
JOIN ssgls_old.lorry_receipts lr ON i.lorry_receipt_id = lr.id
SET
  i.tr_owner_customer_id     = lr.owner_customer_id,
  i.tr_driver_customer_id    = lr.driver_customer_id,
  i.tr_broker_customer_id    = lr.broker_customer_id,
  i.tr_contract_no           = lr.contract_no,
  i.tr_from_code             = lr.from_code,
  i.tr_from_name             = lr.from_name,
  i.tr_to_code               = lr.to_code,
  i.tr_to_name               = lr.to_name,
  i.tr_lorry_no              = lr.lorry_no,
  i.tr_no_of_pages           = lr.no_of_pages,
  i.tr_no_of_packages        = lr.no_of_pkgs,
  i.tr_actual_weight         = lr.actual_weight,
  i.tr_charged_weight        = lr.charge_weight,
  i.tr_regd_at               = lr.regd_at,
  i.tr_body_type             = lr.body_type,
  i.tr_make                  = lr.make,
  i.tr_vehicle_model         = lr.vehicle_model,
  i.tr_colour                = lr.colour,
  i.tr_chasis_no            = lr.chasis_no,
  i.tr_engine_no            = lr.engine_no,
  i.tr_paid_to              = lr.paid_to,
  i.tr_lorry_hire_amount    = lr.lorry_hire_amount,
  i.tr_other_charges_amount = lr.other_charges_amount,
  i.tr_advance_amount       = lr.advance_amount,
  i.tr_advance_cash_cheque_no = lr.advance_cash_cheque_no,
  i.tr_advance_on           = lr.advance_on,
  i.tr_advance_bank          = lr.advance_bank,
  i.tr_received_no_bilties  = lr.received_no_bilties,
  i.tr_detention_amount     = lr.detention_amount,
  i.tr_extra_hire_amount    = lr.extra_hire_amount,
  i.tr_final_other_amount   = lr.final_other_amount,
  i.tr_final_balance_paid_at = lr.final_balance_paid_at,
  i.tr_final_balance_on     = lr.final_balance_on,
  i.tr_net_amount_payable   = lr.net_amount_payable,
  i.tr_final_cash_cheque_no = lr.final_cash_cheque_no,
  i.tr_final_bank           = lr.final_bank,
  i.tr_final_payment_received_by = lr.final_payment_received_by,
  i.tr_owner_name           = lr.owner_name,
  i.tr_owner_address        = lr.owner_address,
  i.tr_owner_phone          = lr.owner_phone,
  i.tr_owner_bank_account_no = lr.owner_bank_account_no,
  i.tr_driver_name          = lr.driver_name,
  i.tr_driver_address       = lr.driver_address,
  i.tr_driver_licence_no    = lr.driver_licence_no,
  i.tr_driver_licence_date  = lr.driver_licence_date,
  i.tr_driver_licence_issued_by = lr.driver_licence_issued_by,
  i.tr_driver_rto_address   = lr.driver_rto_address,
  i.tr_driver_valid_up_to   = lr.driver_valid_up_to,
  i.tr_driver_bank_account_no = lr.driver_bank_account_no,
  i.tr_driver_place         = lr.driver_place,
  i.tr_broker_name          = lr.broker_name,
  i.tr_broker_address       = lr.broker_address,
  i.tr_broker_phone         = lr.broker_phone,
  i.tr_broker_bank_account_no = lr.broker_bank_account_no,
  i.tr_balance_payable_code = lr.balance_payable_code,
  i.tr_balance_payable_at   = lr.balance_payable_at,
  i.tr_balance_rupees       = lr.balance_rupees,
  i.tr_balance_rupees_only  = lr.balance_rupees_only,
  i.tr_gross_hire_amount    = lr.gross_hire_amount,
  i.tr_gross_hire_rupees    = lr.gross_hire_rupees,
  i.tr_hire_prepared_by     = lr.hire_prepared_by,
  i.tr_loaded_by            = lr.loaded_by,
  i.tr_final_paid_to        = lr.final_paid_to,
  i.tr_less_advance_other_branch_amount = lr.less_advance_other_branch_amount,
  i.tr_less_deduction_claims_amount     = lr.less_deduction_claims_amount,
  i.tr_advice_no            = lr.advice_no,
  i.tr_advice_date          = lr.advice_date,
  i.tr_dest_broker_name     = lr.destination_broker_name,
  i.tr_dest_broker_address  = lr.destination_broker_address,
  i.tr_financer_name        = lr.financer_name,
  i.tr_financer_address     = lr.financer_address
WHERE i.template_name = 'lorry_receipt';
```

---

## 6. `lorry_party_profiles` → `tr_lorry_party_profiles` Migration

The old `lorry_party_profiles` table (302 rows) maps to the new
`tr_lorry_party_profiles` table.

### 6.1 Field mapping

| Old column | New column | Notes |
|---|---|---|
| `id` | `id` | Same |
| `company_id` | `company_id` | Same |
| `customer_id` | `customer_id` | Same |
| `type` | `type` | Same (OWNER, DRIVER, BROKER) |
| `code` | `code` | Same |
| `name` | `name` | Same |
| `address` | `address` | Same |
| `phone` | `phone` | Same |
| `bank_account_no` | `bank_account_no` | Same |
| `licence_no` | `licence_no` | Same |
| `licence_date` | `licence_date` | Old: varchar → New: date. Cast on insert. |
| `licence_issued_by` | `licence_issued_by` | Same |
| `rto_address` | `rto_address` | Same |
| `valid_up_to` | `valid_up_to` | Old: varchar → New: date. Cast on insert. |
| `place` | `place` | Same |
| `destination_broker_name` | `destination_broker_name` | Same |
| `destination_broker_address` | `destination_broker_address` | Same |
| `created_at` | `created_at` | Same |
| `updated_at` | `updated_at` | Same |
| `advice_no` | `advice_no` | Same |
| `advice_date` | `advice_date` | Old: varchar → New: date. Cast on insert. |
| `creator_id` | — | **Dropped.** |
| `updated_by` | — | **Dropped.** |
| `financer_name` | — | **Dropped.** (now on invoices.tr_financer_name) |
| `financer_address` | — | **Dropped.** |
| `rc_front_path` | — | **Dropped.** (file uploads) |
| `rc_back_path` | — | **Dropped.** |
| `pan_front_path` | — | **Dropped.** |
| `insurance_path` | — | **Dropped.** |
| `license_front_path` | — | **Dropped.** |
| `license_back_path` | — | **Dropped.** |
| `pan_front_path_broker` | — | **Dropped.** |
| — | `alternate_phone` | **New.** Set to `NULL`. |
| — | `pan_number` | **New.** Set to `NULL`. |
| — | `gstin` | **New.** Set to `NULL`. |
| — | `bank_name` | **New.** Set to `NULL`. |
| — | `bank_account_holder_name` | **New.** Set to `NULL`. |
| — | `ifsc_code` | **New.** Set to `NULL`. |
| — | `upi_id` | **New.** Set to `NULL`. |
| — | `status` | **New.** Set to `'active'`. |
| — | `notes` | **New.** Set to `NULL`. |
| — | `supplier_id` | **New.** Set to `NULL`. |

### 6.2 Migration SQL

```sql
INSERT INTO tr_lorry_party_profiles (
  id, company_id, customer_id, type, code, name, address, phone,
  bank_account_no, licence_no, licence_date, licence_issued_by,
  rto_address, valid_up_to, place, destination_broker_name,
  destination_broker_address, created_at, updated_at, advice_no, advice_date
)
SELECT
  id, company_id, customer_id, type, code, name, address, phone,
  bank_account_no, licence_no,
    NULLIF(licence_date, '') AS licence_date,
  licence_issued_by,
  rto_address,
    NULLIF(valid_up_to, '') AS valid_up_to,
  place, destination_broker_name, destination_broker_address,
  created_at, updated_at, advice_no,
    NULLIF(advice_date, '') AS advice_date
FROM ssgls_old.lorry_party_profiles;
```

---

## 7. `payments` → `customer_payments` + `customer_payment_allocations` Migration

The old `payments` table (9 rows) is split into two new tables:
- `customer_payments` — the payment header (amount, date, method, etc.)
- `customer_payment_allocations` — links a payment to one or more invoices

### 7.1 `payments` → `customer_payments` mapping

| Old `payments` column | New `customer_payments` column | Notes |
|---|---|---|
| `id` | `id` | Same |
| `sequence_number` | `sequence_number` | Same |
| `customer_sequence_number` | `customer_sequence_number` | Same |
| `payment_number` | `payment_number` | Same |
| `payment_date` | `payment_date` | Same |
| `notes` | `notes` | Same |
| `amount` | `amount` | Same |
| `unique_hash` | `unique_hash` | Same |
| `company_id` | `company_id` | Same |
| `payment_method_id` | `payment_method_id` | Same |
| `created_at` | `created_at` | Same |
| `updated_at` | `updated_at` | Same |
| `creator_id` | `creator_id` | Same |
| `customer_id` | `customer_id` | Same |
| `exchange_rate` | `exchange_rate` | Same |
| `base_amount` | `base_amount` | Same |
| `currency_id` | `currency_id` | Same |
| `transaction_id` | `transaction_id` | Same |
| `tds_amount` | — | **Dropped.** No equivalent. |
| `deduction_amount` | — | **Dropped.** |
| `invoice_paid_status` | — | **Dropped.** |
| `invoice_id` | — | Moved to `customer_payment_allocations`. |
| `updated_by` | — | **Dropped.** |

### 7.2 `payments` → `customer_payment_allocations` mapping

| Old `payments` column | New `customer_payment_allocations` column |
|---|---|
| `id` | `payment_id` |
| `invoice_id` | `invoice_id` |
| `amount` | `amount` |
| `base_amount` | `base_amount` |
| (auto) | `id` (auto-increment) |
| `created_at` | `created_at` |
| `updated_at` | `updated_at` |

### 7.3 Migration SQL

```sql
-- Step 1: Copy payment headers
INSERT INTO customer_payments (
  id, sequence_number, customer_sequence_number, payment_number,
  payment_date, notes, amount, unique_hash, company_id,
  payment_method_id, created_at, updated_at, creator_id,
  customer_id, exchange_rate, base_amount, currency_id, transaction_id
)
SELECT
  id, sequence_number, customer_sequence_number, payment_number,
  payment_date, notes, amount, unique_hash, company_id,
  payment_method_id, created_at, updated_at, creator_id,
  customer_id, exchange_rate, base_amount, currency_id, transaction_id
FROM ssgls_old.payments
WHERE invoice_id IS NOT NULL;

-- Step 2: Create allocations (one per payment → invoice link)
INSERT INTO customer_payment_allocations (
  payment_id, invoice_id, amount, base_amount, created_at, updated_at
)
SELECT
  p.id, p.invoice_id, p.amount, p.base_amount, p.created_at, p.updated_at
FROM ssgls_old.payments p
WHERE p.invoice_id IS NOT NULL;
```

---

## 8. Morph Map Type Updates

The new schema uses **stable aliases** (e.g. `invoice`, `customer`) instead of
fully-qualified class names (e.g. `App\Models\Invoice`) in morph map columns.
The `stabilize_model_type_aliases` migration handles this for new data, but
**imported old data must be updated manually**.

### 8.1 Old → New morph map type mapping

| Old value (App\Models\X) | New alias |
|---|---|
| `App\Models\Customer` | `customer` |
| `App\Models\Item` | `item` |
| `App\Models\Invoice` | `invoice` |
| `App\Models\InvoiceItem` | `invoice_item` |
| `App\Models\Estimate` | `estimate` |
| `App\Models\EstimateItem` | `estimate_item` |
| `App\Models\Expense` | `expense` |
| `App\Models\Payment` | `payment` |
| `App\Models\RecurringInvoice` | `recurring_invoice` |
| `App\Models\TaxType` | `tax_type` |
| `App\Models\Note` | `note` |
| `App\Models\CustomField` | `custom_field` |
| `App\Models\ExchangeRateProvider` | `exchange_rate_provider` |
| `App\Models\Company` | `company` |
| `App\Models\User` | `user` |

### 8.2 Tables and columns affected

| Table | Column |
|---|---|
| `media` | `model_type` |
| `email_logs` | `mailable_type` |
| `notifications` | `notifiable_type` |
| `personal_access_tokens` | `tokenable_type` |
| `custom_field_values` | `custom_field_valuable_type` |
| `abilities` | `entity_type` |
| `assigned_roles` | `entity_type`, `restricted_to_type` |
| `permissions` | `entity_type` |

### 8.3 Update SQL

```sql
-- media
UPDATE media SET model_type = 'company'  WHERE model_type = 'App\\Models\\Company';
UPDATE media SET model_type = 'invoice'  WHERE model_type = 'App\\Models\\Invoice';
UPDATE media SET model_type = 'user'     WHERE model_type = 'App\\Models\\User';

-- custom_field_values
UPDATE custom_field_values SET custom_field_valuable_type = 'invoice'
  WHERE custom_field_valuable_type = 'App\\Models\\Invoice';
UPDATE custom_field_values SET custom_field_valuable_type = 'invoice_item'
  WHERE custom_field_valuable_type = 'App\\Models\\InvoiceItem';

-- abilities
UPDATE abilities SET entity_type = 'customer'     WHERE entity_type = 'App\\Models\\Customer';
UPDATE abilities SET entity_type = 'item'         WHERE entity_type = 'App\\Models\\Item';
UPDATE abilities SET entity_type = 'invoice'      WHERE entity_type = 'App\\Models\\Invoice';
UPDATE abilities SET entity_type = 'estimate'     WHERE entity_type = 'App\\Models\\Estimate';
UPDATE abilities SET entity_type = 'expense'      WHERE entity_type = 'App\\Models\\Expense';
UPDATE abilities SET entity_type = 'payment'      WHERE entity_type = 'App\\Models\\Payment';
UPDATE abilities SET entity_type = 'recurring_invoice' WHERE entity_type = 'App\\Models\\RecurringInvoice';
UPDATE abilities SET entity_type = 'tax_type'    WHERE entity_type = 'App\\Models\\TaxType';
UPDATE abilities SET entity_type = 'note'         WHERE entity_type = 'App\\Models\\Note';
UPDATE abilities SET entity_type = 'custom_field' WHERE entity_type = 'App\\Models\\CustomField';
UPDATE abilities SET entity_type = 'exchange_rate_provider' WHERE entity_type = 'App\\Models\\ExchangeRateProvider';

-- assigned_roles
UPDATE assigned_roles SET entity_type = 'user' WHERE entity_type = 'App\\Models\\User';
```

> **Note:** The `stabilize_model_type_aliases` migration (already run in the
> new DB) converts `App\Models\X` → alias. If you re-run it after import, it
> will handle this automatically. But running the UPDATE statements above is
> safer and more explicit.

---

## 9. Bouncer (Abilities, Roles, Permissions)

The old DB uses the old model namespaces in `abilities.entity_type`. The new
DB's `stabilize_model_type_aliases` migration already converted the seeded
abilities to aliases. **Do not copy old abilities/roles/permissions directly**
— instead:

1. Keep the new DB's seeded abilities/roles (they have the correct aliases).
2. Only copy `assigned_roles` from the old DB (after updating `entity_type`
   from `App\Models\User` → `user`).
3. Re-run `php artisan roles:apply-preset-defaults` to sync role abilities.

```sql
-- Copy role assignments (after updating entity_type)
INSERT INTO assigned_roles (role_id, entity_id, entity_type, restricted_to_id, restricted_to_type, scope)
SELECT role_id, entity_id, 'user', restricted_to_id, restricted_to_type, scope
FROM ssgls_old.assigned_roles;
```

---

## 10. Post-Migration Steps

After all data is imported:

```bash
# 1. Fix auto-increment counters
mysql -e "
  SELECT CONCAT('ALTER TABLE ', table_name, ' AUTO_INCREMENT = 1;')
  FROM information_schema.tables
  WHERE table_schema = 'invoiceshelf_production'
  AND auto_increment IS NOT NULL;
" | mysql invoiceshelf_production

# 2. Re-run role preset defaults
php artisan roles:apply-preset-defaults

# 3. Clear all caches
php artisan optimize:clear

# 4. Verify the app boots
php artisan serve &
sleep 2
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000
# Expected: 200
kill %1

# 5. Verify data counts
php artisan tinker --execute="
  echo 'Users: ' . DB::table('users')->count() . PHP_EOL;
  echo 'Companies: ' . DB::table('companies')->count() . PHP_EOL;
  echo 'Customers: ' . DB::table('customers')->count() . PHP_EOL;
  echo 'Invoices: ' . DB::table('invoices')->count() . PHP_EOL;
  echo '  office_invoice: ' . DB::table('invoices')->where('template_name', 'office_invoice')->count() . PHP_EOL;
  echo '  lr_receipt: ' . DB::table('invoices')->where('template_name', 'lr_receipt')->count() . PHP_EOL;
  echo '  lorry_receipt: ' . DB::table('invoices')->where('template_name', 'lorry_receipt')->count() . PHP_EOL;
  echo 'Invoice Items: ' . DB::table('invoice_items')->count() . PHP_EOL;
  echo 'Estimates: ' . DB::table('estimates')->count() . PHP_EOL;
  echo 'Payments: ' . DB::table('customer_payments')->count() . PHP_EOL;
  echo 'Party Profiles: ' . DB::table('tr_lorry_party_profiles')->count() . PHP_EOL;
"
```

### Expected counts (from old DB)

| Table | Expected count |
|---|---|
| users | 5 |
| companies | 2 |
| customers | 613 |
| invoices | 460 |
| invoices (office_invoice) | 87 |
| invoices (lr_receipt) | 258 |
| invoices (lorry_receipt) | 115 |
| invoice_items | 494 |
| estimates | 3 |
| estimate_items | 4 |
| expenses | 4 |
| items | 2 |
| custom_fields | 207 |
| custom_field_values | 930 |
| addresses | 585 |
| units | 22 |
| payment_methods | 10 |
| media | 33 |
| email_logs | 6 |
| file_disks | 2 |
| exchange_rate_logs | 3 |
| customer_payments | 9 |
| tr_lorry_party_profiles | 302 |

---

## 11. Old DB Data Summary

| Table | Rows | Notes |
|---|---|---|
| `invoices` | 460 | 87 office_invoice, 258 lr_receipt, 115 lorry_receipt |
| `invoice_items` | 494 | |
| `lorry_receipts` | 115 | Merged into invoices.tr_* |
| `lorry_party_profiles` | 302 | Migrated to tr_lorry_party_profiles |
| `customers` | 613 | |
| `addresses` | 585 | |
| `payments` | 9 | Split into customer_payments + allocations |
| `estimates` | 3 | |
| `estimate_items` | 4 | |
| `expenses` | 4 | |
| `items` | 2 | |
| `custom_fields` | 207 | |
| `custom_field_values` | 930 | |
| `units` | 22 | |
| `payment_methods` | 10 | |
| `media` | 33 | |
| `email_logs` | 6 | |
| `file_disks` | 2 | |
| `exchange_rate_logs` | 3 | |
| `users` | 5 | |
| `companies` | 2 | |
| `transport_invoices` | 0 | Skip (no data) |
| `transport_invoice_rows` | 0 | Skip (no data) |
| `chatbot_histories` | 0 | Skip (no data) |
| `taxes` | 0 | |
| `tax_types` | 0 | |
| `transactions` | 0 | |
| `recurring_invoices` | 0 | |
| `notes` | 0 | |

---

## 12. Quick Reference: Complete Migration Script

See `migrate.sql` in this directory for a single runnable SQL script that
executes the full migration in dependency order.
