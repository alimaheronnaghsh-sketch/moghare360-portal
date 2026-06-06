ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS birth_date_jalali VARCHAR(20) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS extra_notes TEXT NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS customer_tracking_code VARCHAR(40) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;

ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS vehicle_type VARCHAR(80) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS service_type VARCHAR(120) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS model_year_type VARCHAR(20) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS model_year_value INT NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS plate_part1 VARCHAR(2) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS plate_letter VARCHAR(10) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS plate_part2 VARCHAR(3) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS plate_iran VARCHAR(2) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;

ALTER TABLE staff_users ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255) NULL;
