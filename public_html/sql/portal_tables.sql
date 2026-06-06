CREATE TABLE IF NOT EXISTS portal_customers_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  national_code VARCHAR(40) NULL,
  mobile VARCHAR(30) NOT NULL,
  profile_photo_path VARCHAR(255) NULL,
  postal_address TEXT NULL,
  job_title VARCHAR(120) NULL,
  birth_date DATE NULL,
  extra_notes TEXT NULL,
  sync_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  synced_at DATETIME NULL,
  UNIQUE KEY ux_portal_customers_mobile (mobile),
  KEY idx_portal_customers_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS full_name VARCHAR(160) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS national_code VARCHAR(40) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS mobile VARCHAR(30) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS postal_address TEXT NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS job_title VARCHAR(120) NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS birth_date DATE NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS extra_notes TEXT NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS sync_status VARCHAR(30) NOT NULL DEFAULT 'Pending';
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS sync_error TEXT NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;
ALTER TABLE portal_customers_staging ADD COLUMN IF NOT EXISTS synced_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS portal_service_requests_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(30) NOT NULL,
  vehicle_brand VARCHAR(80) NOT NULL,
  vehicle_model VARCHAR(120) NOT NULL,
  vehicle_type VARCHAR(80) NOT NULL,
  plate_number VARCHAR(80) NULL,
  vin VARCHAR(40) NULL,
  odometer_km INT NULL,
  service_description TEXT NOT NULL,
  sync_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  synced_at DATETIME NULL,
  KEY idx_portal_requests_mobile (mobile),
  KEY idx_portal_requests_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS mobile VARCHAR(30) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS vehicle_brand VARCHAR(80) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS vehicle_model VARCHAR(120) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS vehicle_type VARCHAR(80) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS plate_number VARCHAR(80) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS vin VARCHAR(40) NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS odometer_km INT NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS service_description TEXT NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS sync_status VARCHAR(30) NOT NULL DEFAULT 'Pending';
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS sync_error TEXT NULL;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE portal_service_requests_staging ADD COLUMN IF NOT EXISTS synced_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS portal_contract_confirmations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(30) NOT NULL,
  service_request_id INT NULL,
  accepted_full_name VARCHAR(160) NOT NULL,
  is_accepted TINYINT(1) NOT NULL DEFAULT 1,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contract_mobile (mobile),
  KEY idx_contract_request (service_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
