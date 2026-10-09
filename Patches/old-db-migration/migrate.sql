-- ============================================================
-- OLD DB → New DB Migration Script
-- Source: ssgls_old (imported from ssgls_backup_06-10-2026_02-00.sql)
-- Target: invoiceshelf_production (fresh, post reset:app + module:migrate)
--
-- PREREQUISITES:
--   1. php artisan reset:app --force
--   2. Enable + migrate all modules (see README.md Section 1.1)
--   3. Truncate demo data (see README.md Section 1.1 step 4)
--   4. mysql -u root -e "CREATE DATABASE ssgls_old"
--   5. mysql -u root ssgls_old < ssgls_backup_06-10-2026_02-00.sql
--
-- USAGE:
--   mysql -u root invoiceshelf_production < migrate.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Users
INSERT INTO users (id, name, email, phone, password, role, remember_token,
  facebook_id, google_id, github_id, contact_name, company_name, website,
  enable_portal, currency_id, created_at, updated_at, creator_id)
SELECT id, name, email, phone, password, role, remember_token,
  facebook_id, google_id, github_id, contact_name, company_name, website,
  enable_portal, currency_id, created_at, updated_at, creator_id
FROM ssgls_old.users
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Step 2: Companies (map gstin → vat_id if vat_id is empty)
INSERT INTO companies (id, name, logo, unique_hash, vat_id, tax_id,
  created_at, updated_at, slug, owner_id)
SELECT id, name, logo, unique_hash,
  COALESCE(NULLIF(vat_id, ''), gstin) AS vat_id,
  tax_id, created_at, updated_at, slug, owner_id
FROM ssgls_old.companies
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Step 3: user_company
INSERT INTO user_company (id, user_id, company_id, created_at, updated_at)
SELECT id, user_id, company_id, created_at, updated_at
FROM ssgls_old.user_company;

-- Step 4: company_settings
INSERT INTO company_settings (id, `option`, value, company_id, created_at, updated_at)
SELECT id, `option`, value, company_id, created_at, updated_at
FROM ssgls_old.company_settings;

-- Step 5: settings
INSERT INTO settings (id, `option`, value, created_at, updated_at)
SELECT id, `option`, value, created_at, updated_at
FROM ssgls_old.settings
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Step 6: user_settings
INSERT INTO user_settings (id, `key`, value, user_id, created_at, updated_at)
SELECT id, `key`, value, user_id, created_at, updated_at
FROM ssgls_old.user_settings;

-- Step 7: payment_methods
INSERT INTO payment_methods (id, name, company_id, created_at, updated_at,
  driver, type, settings, active, use_test_env)
SELECT id, name, company_id, created_at, updated_at,
  driver, type, settings, active, use_test_env
FROM ssgls_old.payment_methods;

-- Step 8: file_disks
INSERT INTO file_disks (id, name, type, driver, set_as_default, credentials,
  created_at, updated_at)
SELECT id, name, type, driver, set_as_default, credentials, created_at, updated_at
FROM ssgls_old.file_disks;

-- Step 9: units
INSERT INTO units (id, name, company_id, created_at, updated_at)
SELECT id, name, company_id, created_at, updated_at
FROM ssgls_old.units;

-- Step 10: tax_types (set transaction_type = 'sales')
INSERT INTO tax_types (id, name, calculation_type, percent, fixed_amount,
  compound_tax, collective_tax, description, company_id, created_at, updated_at,
  type, transaction_type)
SELECT id, name, calculation_type, percent, fixed_amount,
  compound_tax, collective_tax, description, company_id, created_at, updated_at,
  type, 'sales'
FROM ssgls_old.tax_types;

-- Step 11: customers (drop type, updated_by columns)
INSERT INTO customers (id, prefix, name, email, phone, password, remember_token,
  facebook_id, google_id, github_id, tax_id, contact_name, company_name, website,
  enable_portal, currency_id, company_id, creator_id, created_at, updated_at)
SELECT id, prefix, name, email, phone, password, remember_token,
  facebook_id, google_id, github_id, tax_id, contact_name, company_name, website,
  enable_portal, currency_id, company_id, creator_id, created_at, updated_at
FROM ssgls_old.customers;

-- Step 12: addresses
INSERT INTO addresses (id, name, address_street_1, address_street_2, city, state,
  country_id, zip, phone, fax, type, user_id, created_at, updated_at,
  company_id, customer_id)
SELECT id, name, address_street_1, address_street_2, city, state,
  country_id, zip, phone, fax, type, user_id, created_at, updated_at,
  company_id, customer_id
FROM ssgls_old.addresses;

-- Step 13: items
INSERT INTO items (id, name, description, price, company_id, unit_id,
  created_at, updated_at, creator_id, currency_id, tax_per_item)
SELECT id, name, description, price, company_id, unit_id,
  created_at, updated_at, creator_id, currency_id, tax_per_item
FROM ssgls_old.items;

-- Step 14: expense_categories
INSERT INTO expense_categories (id, name, description, company_id, created_at, updated_at)
SELECT id, name, description, company_id, created_at, updated_at
FROM ssgls_old.expense_categories;

-- Step 15: tr_lorry_party_profiles (from lorry_party_profiles)
INSERT INTO tr_lorry_party_profiles (
  id, company_id, customer_id, type, code, name, address, phone,
  bank_account_no, licence_no, licence_date, licence_issued_by,
  rto_address, valid_up_to, place, destination_broker_name,
  destination_broker_address, created_at, updated_at, advice_no, advice_date
)
SELECT
  id, company_id, customer_id, type, code, name, address, phone,
  bank_account_no, licence_no, NULLIF(licence_date, ''),
  licence_issued_by, rto_address, NULLIF(valid_up_to, ''),
  place, destination_broker_name, destination_broker_address,
  created_at, updated_at, advice_no, NULLIF(advice_date, '')
FROM ssgls_old.lorry_party_profiles;
-- Step 16: invoices (map consignee_customer_id → tr_consignee_customer_id)
INSERT INTO invoices (
  id, sequence_number, customer_sequence_number, invoice_date, due_date,
  invoice_number, reference_number, status, paid_status, tax_per_item,
  discount_per_item, notes, discount_type, discount, discount_val,
  sub_total, total, tax, due_amount, sent, viewed, unique_hash,
  company_id, created_at, updated_at, creator_id, template_name,
  tr_consignee_customer_id, customer_id, recurring_invoice_id,
  exchange_rate, base_discount_val, base_sub_total, base_total, base_tax,
  base_due_amount, currency_id, sales_tax_type, sales_tax_address_type,
  overdue, tax_included
)
SELECT
  id, sequence_number, customer_sequence_number, invoice_date, due_date,
  invoice_number, reference_number, status, paid_status, tax_per_item,
  discount_per_item, notes, discount_type, discount, discount_val,
  sub_total, total, tax, due_amount, sent, viewed, unique_hash,
  company_id, created_at, updated_at, creator_id, template_name,
  consignee_customer_id, customer_id, recurring_invoice_id,
  exchange_rate, base_discount_val, base_sub_total, base_total, base_tax,
  base_due_amount, currency_id, sales_tax_type, sales_tax_address_type,
  overdue, tax_included
FROM ssgls_old.invoices;

-- Step 17: Merge lorry_receipts data into invoices.tr_* columns
-- Old DB's invoices.lorry_receipt_id was never populated, so match by row number
-- ordered by created_at (both tables have 115 rows with unique timestamps, ~1s apart)
UPDATE invoices i
JOIN (
  SELECT ri.id as invoice_id, lr.*
  FROM (
    SELECT id, ROW_NUMBER() OVER (ORDER BY created_at) as rn
    FROM ssgls_old.invoices
    WHERE template_name = 'lorry_receipt'
  ) ri
  JOIN (
    SELECT *, ROW_NUMBER() OVER (ORDER BY created_at) as rn
    FROM ssgls_old.lorry_receipts
  ) lr ON ri.rn = lr.rn
) lr ON i.id = lr.invoice_id
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
  i.tr_final_cash_cheque_on = lr.final_cash_cheque_on,
  i.tr_final_bank           = lr.final_bank,
  i.tr_final_rupees_only    = lr.final_rupees_only,
  i.tr_final_passed_by      = lr.final_passed_by,
  i.tr_final_certified_by   = lr.final_certified_by,
  i.tr_final_prepared_by    = lr.final_prepared_by,
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
  i.tr_balance_payable_at   = lr.balance_payable_at,
  i.tr_balance_rupees       = lr.balance_rupees,
  i.tr_balance_amount       = lr.balance_amount,
  i.tr_balance_rupees_only  = lr.balance_rupees_only,
  i.tr_hire_passed_by       = lr.hire_passed_by,
  i.tr_hire_certified_by    = lr.hire_certified_by,
  i.tr_hire_prepared_by     = lr.hire_prepared_by,
  i.tr_advance_received_by  = lr.advance_received_by,
  i.tr_loading_remarks      = lr.loading_remarks,
  i.tr_loaded_by            = lr.loaded_by,
  i.tr_final_paid_to        = lr.final_paid_to,
  i.tr_less_advance_other_branch_amount = lr.less_advance_other_branch_amount,
  i.tr_less_deduction_claims_amount     = lr.less_deduction_claims_amount,
  i.tr_total_less_amount    = lr.total_less_amount,
  i.tr_final_total_extra    = lr.final_total_extra_amount,
  i.tr_grand_total          = lr.grand_total_amount,
  i.tr_gross_hire_rupees    = lr.gross_hire_rupees,
  i.tr_dest_broker_name     = lr.destination_broker_name,
  i.tr_dest_broker_address  = lr.destination_broker_address,
  i.tr_financer_name        = lr.financer_name,
  i.tr_financer_address     = lr.financer_address,
  i.tr_advice_date          = lr.advice_date
WHERE i.template_name = 'lorry_receipt';

-- Step 18: invoice_items (map consignment_number → tr_consignment_number)
INSERT INTO invoice_items (
  id, name, description, discount_type, price, quantity, unit_name,
  discount, discount_val, tax, total, invoice_id, item_id, company_id,
  created_at, updated_at, recurring_invoice_id, base_price, exchange_rate,
  base_discount_val, base_tax, base_total, tr_consignment_number
)
SELECT
  id, name, description, discount_type, price, quantity, unit_name,
  discount, discount_val, tax, total, invoice_id, item_id, company_id,
  created_at, updated_at, recurring_invoice_id, base_price, exchange_rate,
  base_discount_val, base_tax, base_total, consignment_number
FROM ssgls_old.invoice_items;

-- Step 19: estimates
INSERT INTO estimates (
  id, sequence_number, customer_sequence_number, estimate_date, expiry_date,
  estimate_number, status, reference_number, tax_per_item, discount_per_item,
  notes, discount, discount_type, discount_val, sub_total, total, tax,
  unique_hash, company_id, created_at, updated_at, creator_id, template_name,
  customer_id, exchange_rate, base_discount_val, base_sub_total, base_total,
  base_tax, currency_id, sales_tax_type, sales_tax_address_type, tax_included
)
SELECT
  id, sequence_number, customer_sequence_number, estimate_date, expiry_date,
  estimate_number, status, reference_number, tax_per_item, discount_per_item,
  notes, discount, discount_type, discount_val, sub_total, total, tax,
  unique_hash, company_id, created_at, updated_at, creator_id, template_name,
  customer_id, exchange_rate, base_discount_val, base_sub_total, base_total,
  base_tax, currency_id, sales_tax_type, sales_tax_address_type, tax_included
FROM ssgls_old.estimates;

-- Step 20: estimate_items
INSERT INTO estimate_items (
  id, name, description, discount_type, quantity, unit_name, discount,
  discount_val, price, tax, total, item_id, estimate_id, company_id,
  created_at, updated_at, exchange_rate, base_discount_val, base_price,
  base_tax, base_total
)
SELECT
  id, name, description, discount_type, quantity, unit_name, discount,
  discount_val, price, tax, total, item_id, estimate_id, company_id,
  created_at, updated_at, exchange_rate, base_discount_val, base_price,
  base_tax, base_total
FROM ssgls_old.estimate_items;

-- Step 21: expenses (drop auto_generated, invoice_id, payment_id, updated_by)
INSERT INTO expenses (
  id, expense_date, expense_number, attachment_receipt, amount, notes,
  expense_category_id, company_id, created_at, updated_at, creator_id,
  customer_id, exchange_rate, base_amount, currency_id, payment_method_id
)
SELECT
  id, expense_date, expense_number, attachment_receipt, amount, notes,
  expense_category_id, company_id, created_at, updated_at, creator_id,
  customer_id, exchange_rate, base_amount, currency_id, payment_method_id
FROM ssgls_old.expenses;

-- Step 22: customer_payments (from old payments table)
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

-- Step 23: customer_payment_allocations
INSERT INTO customer_payment_allocations (
  payment_id, invoice_id, amount, base_amount, created_at, updated_at
)
SELECT
  p.id, p.invoice_id, p.amount, p.base_amount, p.created_at, p.updated_at
FROM ssgls_old.payments p
WHERE p.invoice_id IS NOT NULL;

-- Step 24: taxes
INSERT INTO taxes (
  id, tax_type_id, invoice_id, estimate_id, invoice_item_id, estimate_item_id,
  item_id, company_id, name, calculation_type, amount, percent, fixed_amount,
  compound_tax, created_at, updated_at, exchange_rate, base_amount,
  currency_id, recurring_invoice_id
)
SELECT
  id, tax_type_id, invoice_id, estimate_id, invoice_item_id, estimate_item_id,
  item_id, company_id, name, calculation_type, amount, percent, fixed_amount,
  compound_tax, created_at, updated_at, exchange_rate, base_amount,
  currency_id, recurring_invoice_id
FROM ssgls_old.taxes;

-- Step 25: transactions
INSERT INTO transactions (
  id, transaction_id, unique_hash, type, status, transaction_date,
  company_id, invoice_id, created_at, updated_at
)
SELECT
  id, transaction_id, unique_hash, type, status, transaction_date,
  company_id, invoice_id, created_at, updated_at
FROM ssgls_old.transactions;

-- Step 26: recurring_invoices
INSERT INTO recurring_invoices (
  id, starts_at, send_automatically, customer_id, company_id, status,
  next_invoice_at, creator_id, frequency, limit_by, limit_count, limit_date,
  currency_id, exchange_rate, tax_per_item, discount_per_item, notes,
  discount_type, discount, discount_val, sub_total, total, tax, template_name,
  due_amount, created_at, updated_at, sales_tax_type, sales_tax_address_type,
  tax_included
)
SELECT
  id, starts_at, send_automatically, customer_id, company_id, status,
  next_invoice_at, creator_id, frequency, limit_by, limit_count, limit_date,
  currency_id, exchange_rate, tax_per_item, discount_per_item, notes,
  discount_type, discount, discount_val, sub_total, total, tax, template_name,
  due_amount, created_at, updated_at, sales_tax_type, sales_tax_address_type,
  tax_included
FROM ssgls_old.recurring_invoices;

-- Step 27: custom_fields (set placement = 'internal')
INSERT INTO custom_fields (
  id, name, slug, label, model_type, type, placeholder, options,
  boolean_answer, date_answer, time_answer, string_answer, number_answer,
  date_time_answer, is_required, `order`, company_id, created_at, updated_at,
  placement
)
SELECT
  id, name, slug, label, model_type, type, placeholder, options,
  boolean_answer, date_answer, time_answer, string_answer, number_answer,
  date_time_answer, is_required, `order`, company_id, created_at, updated_at,
  'internal'
FROM ssgls_old.custom_fields;

-- Step 28: custom_field_values (update morph types)
INSERT INTO custom_field_values (
  id, custom_field_valuable_type, custom_field_valuable_id, type,
  boolean_answer, date_answer, time_answer, string_answer, number_answer,
  date_time_answer, custom_field_id, company_id, created_at, updated_at
)
SELECT
  id,
  CASE custom_field_valuable_type
    WHEN 'App\\Models\\Invoice' THEN 'invoice'
    WHEN 'App\\Models\\InvoiceItem' THEN 'invoice_item'
    ELSE custom_field_valuable_type
  END,
  custom_field_valuable_id, type,
  boolean_answer, date_answer, time_answer, string_answer, number_answer,
  date_time_answer, custom_field_id, company_id, created_at, updated_at
FROM ssgls_old.custom_field_values;

-- Step 29: media (update morph types)
INSERT INTO media (
  id, model_type, model_id, collection_name, name, file_name, mime_type,
  disk, size, manipulations, custom_properties, responsive_images,
  order_column, created_at, updated_at, uuid, conversions_disk,
  generated_conversions
)
SELECT
  id,
  CASE model_type
    WHEN 'App\\Models\\Company' THEN 'company'
    WHEN 'App\\Models\\Invoice' THEN 'invoice'
    WHEN 'App\\Models\\User' THEN 'user'
    ELSE model_type
  END,
  model_id, collection_name, name, file_name, mime_type,
  disk, size, manipulations, custom_properties, responsive_images,
  order_column, created_at, updated_at, uuid, conversions_disk,
  generated_conversions
FROM ssgls_old.media;

-- Step 30: email_logs
INSERT INTO email_logs (id, `from`, `to`, subject, body, mailable_type,
  mailable_id, created_at, updated_at, token)
SELECT id, `from`, `to`, subject, body, mailable_type,
  mailable_id, created_at, updated_at, token
FROM ssgls_old.email_logs;

-- Step 31: exchange_rate_providers
INSERT INTO exchange_rate_providers (id, driver, `key`, currencies,
  driver_config, active, company_id, created_at, updated_at)
SELECT id, driver, `key`, currencies, driver_config, active, company_id,
  created_at, updated_at
FROM ssgls_old.exchange_rate_providers;

-- Step 32: exchange_rate_logs
INSERT INTO exchange_rate_logs (id, company_id, base_currency_id,
  currency_id, exchange_rate, created_at, updated_at)
SELECT id, company_id, base_currency_id, currency_id, exchange_rate,
  created_at, updated_at
FROM ssgls_old.exchange_rate_logs;

-- Step 33: notes
INSERT INTO notes (id, type, name, notes, created_at, updated_at, company_id,
  is_default)
SELECT id, type, name, notes, created_at, updated_at, company_id, is_default
FROM ssgls_old.notes;

-- Step 34: assigned_roles (update entity_type App\Models\User → user)
INSERT INTO assigned_roles (role_id, entity_id, entity_type, restricted_to_id,
  restricted_to_type, scope)
SELECT role_id, entity_id, 'user', restricted_to_id, restricted_to_type, scope
FROM ssgls_old.assigned_roles;

-- Step 35: Update morph map types in abilities
UPDATE abilities SET entity_type = 'customer'     WHERE entity_type = 'App\\Models\\Customer';
UPDATE abilities SET entity_type = 'item'         WHERE entity_type = 'App\\Models\\Item';
UPDATE abilities SET entity_type = 'invoice'      WHERE entity_type = 'App\\Models\\Invoice';
UPDATE abilities SET entity_type = 'estimate'     WHERE entity_type = 'App\\Models\\Estimate';
UPDATE abilities SET entity_type = 'expense'      WHERE entity_type = 'App\\Models\\Expense';
UPDATE abilities SET entity_type = 'payment'      WHERE entity_type = 'App\\Models\\Payment';
UPDATE abilities SET entity_type = 'recurring_invoice' WHERE entity_type = 'App\\Models\\RecurringInvoice';
UPDATE abilities SET entity_type = 'tax_type'     WHERE entity_type = 'App\\Models\\TaxType';
UPDATE abilities SET entity_type = 'note'         WHERE entity_type = 'App\\Models\\Note';
UPDATE abilities SET entity_type = 'custom_field' WHERE entity_type = 'App\\Models\\CustomField';
UPDATE abilities SET entity_type = 'exchange_rate_provider' WHERE entity_type = 'App\\Models\\ExchangeRateProvider';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE. Run post-migration steps:
--   php artisan roles:apply-preset-defaults
--   php artisan optimize:clear
--   See README.md Section 10 for full verification
-- ============================================================


