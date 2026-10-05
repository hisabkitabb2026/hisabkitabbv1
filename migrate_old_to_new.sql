-- Migration Script: ssgls_old -> invoiceshelf_production
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- STEP 1: Add company 2
INSERT INTO invoiceshelf_production.companies (id, name, logo, unique_hash, vat_id, tax_id, enrollment_no, created_at, updated_at, slug, owner_id, billing_branch, document_identity)
SELECT id, name, logo, unique_hash, vat_id, tax_id, enrollment_no, created_at, updated_at, slug, owner_id, billing_branch_name_address, NULL FROM ssgls_old.companies WHERE id = 2;

-- STEP 2: Add users 3,4,6,7
INSERT INTO invoiceshelf_production.users (id, name, email, phone, password, role, remember_token, facebook_id, google_id, github_id, contact_name, company_name, website, enable_portal, currency_id, created_at, updated_at, creator_id)
SELECT id, name, email, phone, password, role, remember_token, facebook_id, google_id, github_id, contact_name, company_name, website, enable_portal, currency_id, created_at, updated_at, creator_id FROM ssgls_old.users WHERE id IN (3,4,6,7);

-- STEP 3: user_company
INSERT INTO invoiceshelf_production.user_company (id, user_id, company_id, created_at, updated_at)
SELECT id, user_id, company_id, created_at, updated_at FROM ssgls_old.user_company WHERE id NOT IN (SELECT id FROM invoiceshelf_production.user_company);

-- STEP 4: user_settings
INSERT INTO invoiceshelf_production.user_settings (id, `key`, value, user_id, created_at, updated_at)
SELECT id, `key`, value, user_id, created_at, updated_at FROM ssgls_old.user_settings WHERE id NOT IN (SELECT id FROM invoiceshelf_production.user_settings);

-- STEP 5: company_settings for company 2
INSERT INTO invoiceshelf_production.company_settings (id, `option`, value, company_id, created_at, updated_at)
SELECT id, `option`, value, company_id, created_at, updated_at FROM ssgls_old.company_settings WHERE company_id = 2;

-- STEP 6: customers (skip updated_by, add bank_account_no=NULL)
INSERT INTO invoiceshelf_production.customers (id, prefix, name, email, phone, password, remember_token, facebook_id, google_id, github_id, tax_id, contact_name, company_name, website, enable_portal, currency_id, company_id, creator_id, created_at, updated_at, type, bank_account_no)
SELECT id, prefix, name, email, phone, password, remember_token, facebook_id, google_id, github_id, tax_id, contact_name, company_name, website, enable_portal, currency_id, company_id, creator_id, created_at, updated_at, type, NULL FROM ssgls_old.customers;

-- STEP 7: addresses (identical schema)
INSERT INTO invoiceshelf_production.addresses (id, name, address_street_1, address_street_2, city, state, country_id, zip, phone, fax, type, user_id, created_at, updated_at, company_id, customer_id)
SELECT id, name, address_street_1, address_street_2, city, state, country_id, zip, phone, fax, type, user_id, created_at, updated_at, company_id, customer_id FROM ssgls_old.addresses;

-- STEP 8: payment_methods (skip creator_id, updated_by)
INSERT INTO invoiceshelf_production.payment_methods (id, name, company_id, created_at, updated_at, driver, type, settings, active, use_test_env)
SELECT id, name, company_id, created_at, updated_at, driver, type, settings, active, use_test_env FROM ssgls_old.payment_methods WHERE id NOT IN (SELECT id FROM invoiceshelf_production.payment_methods);

-- STEP 9: units (skip creator_id, updated_by)
INSERT INTO invoiceshelf_production.units (id, name, company_id, created_at, updated_at)
SELECT id, name, company_id, created_at, updated_at FROM ssgls_old.units WHERE id NOT IN (SELECT id FROM invoiceshelf_production.units);

-- STEP 10: expense_categories (skip creator_id, updated_by)
INSERT INTO invoiceshelf_production.expense_categories (id, name, description, company_id, created_at, updated_at)
SELECT id, name, description, company_id, created_at, updated_at FROM ssgls_old.expense_categories WHERE id NOT IN (SELECT id FROM invoiceshelf_production.expense_categories);

-- STEP 11: items (skip updated_by, add truck_type=NULL)
INSERT INTO invoiceshelf_production.items (id, name, description, price, quantity, unit_id, tax_type_id, company_id, created_at, updated_at, sale_price, truck_type)
SELECT id, name, description, price, quantity, unit_id, tax_type_id, company_id, created_at, updated_at, sale_price, NULL FROM ssgls_old.items;

-- STEP 12: custom_fields (add placement=NULL, validation=NULL)
INSERT INTO invoiceshelf_production.custom_fields (id, name, slug, label, model_type, type, placeholder, options, boolean_answer, date_answer, time_answer, string_answer, number_answer, date_time_answer, is_required, `order`, company_id, created_at, updated_at, placement, validation)
SELECT id, name, slug, label, model_type, type, placeholder, options, boolean_answer, date_answer, time_answer, string_answer, number_answer, date_time_answer, is_required, `order`, company_id, created_at, updated_at, NULL, NULL FROM ssgls_old.custom_fields;

-- STEP 13: custom_field_values (convert morph types: App\Models\Invoice -> invoice, App\Models\InvoiceItem -> invoice_item)
INSERT INTO invoiceshelf_production.custom_field_values (id, custom_field_valuable_type, custom_field_valuable_id, type, boolean_answer, date_answer, time_answer, string_answer, number_answer, date_time_answer, custom_field_id, company_id, created_at, updated_at)
SELECT id, CASE custom_field_valuable_type WHEN 'App\\Models\\Invoice' THEN 'invoice' WHEN 'App\\Models\\InvoiceItem' THEN 'invoice_item' ELSE custom_field_valuable_type END, custom_field_valuable_id, type, boolean_answer, date_answer, time_answer, string_answer, number_answer, date_time_answer, custom_field_id, company_id, created_at, updated_at FROM ssgls_old.custom_field_values;

-- STEP 14: estimates (common columns)
INSERT INTO invoiceshelf_production.estimates (id, sequence_number, customer_sequence_number, estimate_date, expiry_date, estimate_number, status, reference_number, tax_per_item, discount_per_item, notes, discount, discount_type, discount_val, sub_total, total, tax, unique_hash, company_id, created_at, updated_at, creator_id, template_name, customer_id, exchange_rate, base_discount_val, base_sub_total, base_total, base_tax, currency_id, sales_tax_type, sales_tax_address_type, tax_included)
SELECT id, sequence_number, customer_sequence_number, estimate_date, expiry_date, estimate_number, status, reference_number, tax_per_item, discount_per_item, notes, discount, discount_type, discount_val, sub_total, total, tax, unique_hash, company_id, created_at, updated_at, creator_id, template_name, customer_id, exchange_rate, base_discount_val, base_sub_total, base_total, base_tax, currency_id, sales_tax_type, sales_tax_address_type, tax_included FROM ssgls_old.estimates;

-- STEP 15: estimate_items (common columns)
INSERT INTO invoiceshelf_production.estimate_items (id, name, description, discount_type, quantity, unit_name, discount, discount_val, price, tax, total, item_id, estimate_id, company_id, created_at, updated_at, exchange_rate, base_discount_val, base_price, base_tax, base_total)
SELECT id, name, description, discount_type, quantity, unit_name, discount, discount_val, price, tax, total, item_id, estimate_id, company_id, created_at, updated_at, exchange_rate, base_discount_val, base_price, base_tax, base_total FROM ssgls_old.estimate_items;

-- STEP 16: invoices (common columns, type=INVOICE)
INSERT INTO invoiceshelf_production.invoices (id, sequence_number, customer_sequence_number, invoice_date, due_date, invoice_number, reference_number, status, type, paid_status, tax_per_item, discount_per_item, notes, discount_type, discount, discount_val, sub_total, total, tax, due_amount, sent, viewed, unique_hash, company_id, created_at, updated_at, creator_id, template_name, customer_id, consignee_customer_id, recurring_invoice_id, exchange_rate, base_discount_val, base_sub_total, base_total, base_tax, base_due_amount, currency_id, sales_tax_type, sales_tax_address_type, overdue, tax_included)
SELECT id, sequence_number, customer_sequence_number, invoice_date, due_date, invoice_number, reference_number, status, 'INVOICE', paid_status, tax_per_item, discount_per_item, notes, discount_type, discount, discount_val, sub_total, total, tax, due_amount, sent, viewed, unique_hash, company_id, created_at, updated_at, creator_id, template_name, customer_id, consignee_customer_id, recurring_invoice_id, exchange_rate, base_discount_val, base_sub_total, base_total, base_tax, base_due_amount, currency_id, sales_tax_type, sales_tax_address_type, overdue, tax_included FROM ssgls_old.invoices;

-- STEP 17: invoice_items (common columns)
INSERT INTO invoiceshelf_production.invoice_items (id, name, description, discount_type, price, quantity, unit_name, discount, discount_val, tax, total, invoice_id, item_id, company_id, created_at, updated_at, recurring_invoice_id, base_price, exchange_rate, base_discount_val, base_tax, base_total)
SELECT id, name, description, discount_type, price, quantity, unit_name, discount, discount_val, tax, total, invoice_id, item_id, company_id, created_at, updated_at, recurring_invoice_id, base_price, exchange_rate, base_discount_val, base_tax, base_total FROM ssgls_old.invoice_items;

-- STEP 18: lorry_receipts -> new invoices with tr_* columns
INSERT INTO invoiceshelf_production.invoices (
    company_id, creator_id, unique_hash, template_name, type, status, paid_status, tax_per_item, discount_per_item, discount_type, discount, discount_val, sub_total, total, tax, due_amount, sent, viewed, overdue, tax_included, exchange_rate, base_discount_val, base_sub_total, base_total, base_tax, base_due_amount, currency_id, invoice_date, invoice_number, created_at, updated_at,
    customer_id, owner_customer_id, driver_customer_id, broker_customer_id,
    contract_no, from_code, from_name, to_code, to_name, truck_no, no_of_pages, no_of_packages, actual_weight, charged_weight, rate, distance_kms, regd_at, body_type, make, vehicle_model, colour, chasis_no, engine_no, fitness_validity, road_permit_no, permit_date, permit_valid_in, permit_status_upto, insured_with, insurance_division_no, insurance_certificate_no, insurance_valid_upto, owner_code, owner_name, owner_address, owner_phone, owner_bank_account_no, financer_name, financer_address, driver_name, driver_address, driver_place, driver_licence_no, driver_licence_date, driver_licence_issued_by, driver_rto_address, driver_valid_up_to, driver_bank_account_no, broker_name, broker_address, broker_phone_no, broker_bank_account_no, advice_no, advice_date, destination_broker_name, destination_broker_address, paid_to, lorry_hire_amount, other_charges_amount, gross_hire_rupees, gross_hire_amount, advance_cash_cheque_no, advance_on, advance_bank, advance_amount, balance_payable_at, balance_payable_code, balance_rupees, balance_amount, balance_rupees_only, hire_passed_by, hire_certified_by, hire_prepared_by, advance_received_by, loading_remarks, loaded_by, final_paid_to, detention_amount, extra_hire_amount, final_other_amount, final_total_extra_amount, grand_total_amount, less_advance_other_branch_amount, less_deduction_claims_amount, total_less_amount, final_balance_paid_at, final_balance_code, final_balance_on, net_amount_payable, final_cash_cheque_no, final_cash_cheque_on, final_bank, final_rupees_only, final_passed_by, final_certified_by, final_prepared_by, final_payment_received_by, received_no_bilties,
    tr_contract_no, tr_lorry_no, tr_paid_to, tr_lorry_hire_amount, tr_other_charges_amount, tr_advance_amount, tr_advance_cash_cheque_no, tr_advance_on, tr_advance_bank, tr_received_no_bilties, tr_detention_amount, tr_extra_hire_amount, tr_final_other_amount, tr_final_balance_paid_at, tr_final_balance_on, tr_net_amount_payable, tr_final_cash_cheque_no, tr_final_bank, tr_owner_name, tr_owner_address, tr_owner_phone, tr_owner_bank_account_no, tr_driver_name, tr_driver_address, tr_driver_licence_no, tr_broker_name, tr_broker_address, tr_broker_phone, tr_owner_customer_id, tr_driver_customer_id, tr_broker_customer_id, tr_no_of_pages, tr_no_of_packages, tr_regd_at, tr_body_type, tr_make, tr_vehicle_model, tr_colour, tr_chasis_no, tr_engine_no, tr_balance_payable_at, tr_loaded_by, tr_final_paid_to, tr_less_advance_other_branch_amount, tr_less_deduction_claims_amount, tr_driver_licence_date, tr_driver_rto_address, tr_driver_valid_up_to, tr_driver_bank_account_no, tr_advice_date, tr_broker_bank_account_no
)
SELECT
    lr.company_id, lr.creator_id, lr.unique_hash, 'lorry_receipt', 'INVOICE', 'COMPLETED', 'UNPAID', 'NO', 'NO', 'FIXED', 0, 0, COALESCE(lr.gross_hire_amount,0), COALESCE(lr.grand_total_amount,lr.gross_hire_amount,0), 0, COALESCE(lr.grand_total_amount,lr.gross_hire_amount,0), 0, 0, 0, 0, 1, 0, COALESCE(lr.gross_hire_amount,0), COALESCE(lr.grand_total_amount,lr.gross_hire_amount,0), 0, COALESCE(lr.grand_total_amount,lr.gross_hire_amount,0), 17, COALESCE(lr.date_created,lr.created_at), CONCAT('LR-',LPAD(lr.id,4,'0')), lr.created_at, lr.updated_at,
    lr.owner_customer_id, lr.owner_customer_id, lr.driver_customer_id, lr.broker_customer_id,
    lr.contract_no, lr.from_code, lr.from_name, lr.to_code, lr.to_name, lr.lorry_no, lr.no_of_pages, lr.no_of_pkgs, lr.actual_weight, lr.charge_weight, lr.rate, lr.distance_kms, lr.regd_at, lr.body_type, lr.make, lr.vehicle_model, lr.colour, lr.chasis_no, lr.engine_no, lr.fitness_validity, lr.road_permit_no, lr.permit_date, lr.permit_valid_in, lr.permit_status_upto, lr.insured_with, lr.insurance_division_no, lr.insurance_certificate_no, lr.insurance_valid_upto, lr.owner_code, lr.owner_name, lr.owner_address, lr.owner_phone, lr.owner_bank_account_no, lr.financer_name, lr.financer_address, lr.driver_name, lr.driver_address, lr.driver_place, lr.driver_licence_no, lr.driver_licence_date, lr.driver_licence_issued_by, lr.driver_rto_address, lr.driver_valid_up_to, lr.driver_bank_account_no, lr.broker_name, lr.broker_address, lr.broker_phone, lr.broker_bank_account_no, lr.advice_no, lr.advice_date, lr.destination_broker_name, lr.destination_broker_address, lr.paid_to, lr.lorry_hire_amount, lr.other_charges_amount, lr.gross_hire_rupees, lr.gross_hire_amount, lr.advance_cash_cheque_no, lr.advance_on, lr.advance_bank, lr.advance_amount, lr.balance_payable_at, lr.balance_payable_code, lr.balance_rupees, lr.balance_amount, lr.balance_rupees_only, lr.hire_passed_by, lr.hire_certified_by, lr.hire_prepared_by, lr.advance_received_by, lr.loading_remarks, lr.loaded_by, lr.final_paid_to, lr.detention_amount, lr.extra_hire_amount, lr.final_other_amount, lr.final_total_extra_amount, lr.grand_total_amount, lr.less_advance_other_branch_amount, lr.less_deduction_claims_amount, lr.total_less_amount, lr.final_balance_paid_at, lr.final_balance_code, lr.final_balance_on, lr.net_amount_payable, lr.final_cash_cheque_no, lr.final_cash_cheque_on, lr.final_bank, lr.final_rupees_only, lr.final_passed_by, lr.final_certified_by, lr.final_prepared_by, lr.final_payment_received_by, lr.received_no_bilties,
    lr.contract_no, lr.lorry_no, lr.paid_to, lr.lorry_hire_amount, lr.other_charges_amount, lr.advance_amount, lr.advance_cash_cheque_no, lr.advance_on, lr.advance_bank, lr.received_no_bilties, lr.detention_amount, lr.extra_hire_amount, lr.final_other_amount, lr.final_balance_paid_at, lr.final_balance_on, lr.net_amount_payable, lr.final_cash_cheque_no, lr.final_bank, lr.owner_name, lr.owner_address, lr.owner_phone, lr.owner_bank_account_no, lr.driver_name, lr.driver_address, lr.driver_licence_no, lr.broker_name, lr.broker_address, lr.broker_phone, lr.owner_customer_id, lr.driver_customer_id, lr.broker_customer_id, lr.no_of_pages, lr.no_of_pkgs, lr.regd_at, lr.body_type, lr.make, lr.vehicle_model, lr.colour, lr.chasis_no, lr.engine_no, lr.balance_payable_at, lr.loaded_by, lr.final_paid_to, lr.less_advance_other_branch_amount, lr.less_deduction_claims_amount, lr.driver_licence_date, lr.driver_rto_address, lr.driver_valid_up_to, lr.driver_bank_account_no, lr.advice_date, lr.broker_bank_account_no
FROM ssgls_old.lorry_receipts lr;

-- STEP 19: lorry_party_profiles -> tr_lorry_party_profiles
INSERT INTO invoiceshelf_production.tr_lorry_party_profiles (id, company_id, customer_id, type, code, name, address, phone, bank_account_no, licence_no, licence_date, licence_issued_by, rto_address, valid_up_to, place, destination_broker_name, destination_broker_address, created_at, updated_at, advice_no, advice_date, status)
SELECT id, company_id, customer_id, type, code, name, address, phone, bank_account_no, licence_no, licence_date, licence_issued_by, rto_address, valid_up_to, place, destination_broker_name, destination_broker_address, created_at, updated_at, advice_no, advice_date, 'active' FROM ssgls_old.lorry_party_profiles;

-- STEP 20: payments -> customer_payments + customer_payment_allocations
INSERT INTO invoiceshelf_production.customer_payments (id, sequence_number, customer_sequence_number, payment_number, payment_date, notes, amount, unique_hash, company_id, payment_method_id, created_at, updated_at, creator_id, customer_id, exchange_rate, base_amount, currency_id, transaction_id)
SELECT id, sequence_number, customer_sequence_number, payment_number, payment_date, notes, amount, unique_hash, company_id, payment_method_id, created_at, updated_at, creator_id, customer_id, exchange_rate, base_amount, currency_id, transaction_id FROM ssgls_old.payments;

INSERT INTO invoiceshelf_production.customer_payment_allocations (payment_id, invoice_id, amount, base_amount, created_at, updated_at)
SELECT id, invoice_id, amount, base_amount, created_at, updated_at FROM ssgls_old.payments;

-- STEP 21: media (convert morph types: App\Models\Company -> company, App\Models\User -> user, App\Models\Invoice -> invoice)
INSERT INTO invoiceshelf_production.media (id, model_type, model_id, collection_name, name, file_name, mime_type, disk, size, manipulations, custom_properties, responsive_images, order_column, created_at, updated_at, uuid, conversions_disk, generated_conversions)
SELECT id, CASE model_type WHEN 'App\\Models\\Company' THEN 'company' WHEN 'App\\Models\\User' THEN 'user' WHEN 'App\\Models\\Invoice' THEN 'invoice' ELSE model_type END, model_id, collection_name, name, file_name, mime_type, disk, size, manipulations, custom_properties, responsive_images, order_column, created_at, updated_at, uuid, conversions_disk, generated_conversions FROM ssgls_old.media WHERE id NOT IN (SELECT id FROM invoiceshelf_production.media);

-- STEP 22: email_logs (convert mailable_type: App\Models\Invoice -> invoice)
INSERT INTO invoiceshelf_production.email_logs (id, `from`, `to`, subject, body, mailable_type, mailable_id, created_at, updated_at, token)
SELECT id, `from`, `to`, subject, body, CASE mailable_type WHEN 'App\\Models\\Invoice' THEN 'invoice' ELSE mailable_type END, mailable_id, created_at, updated_at, token FROM ssgls_old.email_logs WHERE id NOT IN (SELECT id FROM invoiceshelf_production.email_logs);

-- STEP 23: exchange_rate_logs (identical schema)
INSERT INTO invoiceshelf_production.exchange_rate_logs (id, company_id, base_currency_id, currency_id, exchange_rate, created_at, updated_at)
SELECT id, company_id, base_currency_id, currency_id, exchange_rate, created_at, updated_at FROM ssgls_old.exchange_rate_logs WHERE id NOT IN (SELECT id FROM invoiceshelf_production.exchange_rate_logs);

-- STEP 24: Update auto-increment values
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.companies);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.companies AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.users);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.users AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.customers);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.customers AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.addresses);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.addresses AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.invoices);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.invoices AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.invoice_items);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.invoice_items AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.custom_fields);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.custom_fields AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.custom_field_values);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.custom_field_values AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.tr_lorry_party_profiles);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.tr_lorry_party_profiles AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.customer_payments);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.customer_payments AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.customer_payment_allocations);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.customer_payment_allocations AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM invoiceshelf_production.media);
SET @sql = CONCAT('ALTER TABLE invoiceshelf_production.media AUTO_INCREMENT = ', @max_id); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- Migration Complete
