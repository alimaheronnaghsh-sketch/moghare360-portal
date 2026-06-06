-- MOGHARE360 Customer Intake Workflow Patch
-- Target DB: moghareh_portal

ALTER TABLE portal_customers_staging
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS birth_date_jalali VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS profile_completed_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS customer_tracking_code VARCHAR(40) NULL,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending';

ALTER TABLE portal_service_requests_staging
  ADD COLUMN IF NOT EXISTS service_type VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS customer_priority VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS jobcard_code VARCHAR(40) NULL,
  ADD COLUMN IF NOT EXISTS request_status VARCHAR(40) NULL,
  ADD COLUMN IF NOT EXISTS contract_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS intake_channel VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS manager_override_needed TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS manager_override_note TEXT NULL,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS sync_status VARCHAR(30) NOT NULL DEFAULT 'Pending';

ALTER TABLE portal_contract_confirmations
  ADD COLUMN IF NOT EXISTS accepted_terms_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS contract_version VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS accepted_national_code VARCHAR(10) NULL,
  ADD COLUMN IF NOT EXISTS accepted_mobile VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS accepted_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS service_request_code VARCHAR(40) NULL,
  ADD COLUMN IF NOT EXISTS is_manager_override TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS manager_override_by VARCHAR(120) NULL;

ALTER TABLE portal_customers_staging
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE portal_service_requests_staging
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE portal_contract_confirmations
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
