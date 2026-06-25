-- MOGHARE360 MASTER SQL - run in phpMyAdmin on database moghareh_portal
SET NAMES utf8mb4;



-- =========================================================
-- portal_tables.sql
-- =========================================================

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



-- =========================================================
-- otp_verifications.sql
-- =========================================================

CREATE TABLE IF NOT EXISTS otp_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(30) NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  purpose VARCHAR(50) NOT NULL,
  attempt_count INT NOT NULL DEFAULT 0,
  is_used TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  KEY idx_otp_mobile_purpose (mobile, purpose),
  KEY idx_otp_created_at (created_at),
  KEY idx_otp_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- vehicle_lookups.sql
-- =========================================================

CREATE TABLE IF NOT EXISTS vehicle_lookups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(120) NOT NULL,
  vehicle_type VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY ux_vehicle_lookup (brand, model, vehicle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO vehicle_lookups (brand, model, vehicle_type, is_active) VALUES
('BMW', 'F10', 'سدان', 1),
('BMW', 'F25', 'شاسی بلند', 1),
('BMW', 'F30', 'سدان', 1),
('BMW', 'E60', 'سدان', 1),
('BMW', 'G12', 'سدان', 1),
('BMW', 'G30', 'سدان', 1),
('Mercedes-Benz', 'C Class', 'سدان', 1),
('Mercedes-Benz', 'E Class', 'سدان', 1),
('Mercedes-Benz', 'S Class', 'سدان', 1),
('Mercedes-Benz', 'GLC', 'شاسی بلند', 1),
('Mercedes-Benz', 'GLE', 'شاسی بلند', 1),
('Porsche', 'Cayenne', 'شاسی بلند', 1),
('Porsche', 'Macan', 'شاسی بلند', 1),
('Porsche', 'Panamera', 'سدان', 1),
('Volkswagen', 'Tiguan', 'شاسی بلند', 1),
('Volkswagen', 'Touareg', 'شاسی بلند', 1),
('Volvo', 'XC90', 'شاسی بلند', 1),
('Volvo', 'XC60', 'شاسی بلند', 1),
('Audi', 'A4', 'سدان', 1),
('Audi', 'A6', 'سدان', 1),
('Audi', 'Q5', 'شاسی بلند', 1),
('Audi', 'Q7', 'شاسی بلند', 1),
('Other', 'سایر', 'سایر', 1);



-- =========================================================
-- staff_users.sql
-- =========================================================

CREATE TABLE IF NOT EXISTS staff_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_name VARCHAR(80) NOT NULL DEFAULT 'مدیر سیستم',
  is_master_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO staff_users (full_name, username, password_hash, role_name, is_master_admin, is_active)
VALUES ('مدیر سیستم', 'admin', '$2y$10$Xtc1rQnRiibXIsu/yRkCbe9m9xbyjXETeXjLsXQ05KMNNBhxH8fku', 'مدیر سیستم', 1, 1)
ON DUPLICATE KEY UPDATE
  role_name = 'مدیر سیستم',
  is_master_admin = 1,
  is_active = 1,
  updated_at = NOW();



-- =========================================================
-- patch_1600.sql
-- =========================================================

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



-- =========================================================
-- patch_meeting.sql
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_items_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(200) NOT NULL,
  main_category VARCHAR(150) NULL,
  manufacturer VARCHAR(150) NULL,
  oem_code VARCHAR(100) NULL,
  internal_code VARCHAR(100) NULL,
  item_quality VARCHAR(100) NULL,
  unit_name VARCHAR(50) NULL,
  warehouse_location VARCHAR(150) NULL,
  minimum_stock DECIMAL(18,2) NULL,
  initial_stock DECIMAL(18,2) NULL,
  purchase_price DECIMAL(18,2) NULL,
  suggested_sale_price DECIMAL(18,2) NULL,
  description TEXT NULL,
  photo_path VARCHAR(500) NULL,
  technical_status VARCHAR(100) NULL,
  created_by_staff_id INT NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_inventory_item_name (item_name),
  INDEX idx_inventory_oem_code (oem_code),
  INDEX idx_inventory_internal_code (internal_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_name VARCHAR(80) NOT NULL DEFAULT 'مدیر سیستم',
  is_master_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  profile_photo_path VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS moghareh_add_column_if_missing;

DELIMITER //
CREATE PROCEDURE moghareh_add_column_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_column_name VARCHAR(64),
  IN p_column_definition TEXT
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table_name
  ) AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table_name AND COLUMN_NAME = p_column_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN ', p_column_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

CALL moghareh_add_column_if_missing('staff_users', 'profile_photo_path', '`profile_photo_path` VARCHAR(500) NULL');

CALL moghareh_add_column_if_missing('portal_customers_staging', 'profile_photo_path', '`profile_photo_path` VARCHAR(500) NULL');
CALL moghareh_add_column_if_missing('portal_customers_staging', 'birth_date_jalali', '`birth_date_jalali` VARCHAR(20) NULL');
CALL moghareh_add_column_if_missing('portal_customers_staging', 'extra_notes', '`extra_notes` TEXT NULL');
CALL moghareh_add_column_if_missing('portal_customers_staging', 'customer_tracking_code', '`customer_tracking_code` VARCHAR(40) NULL');
CALL moghareh_add_column_if_missing('portal_customers_staging', 'updated_at', '`updated_at` DATETIME NULL');

CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'vehicle_type', '`vehicle_type` VARCHAR(80) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'service_type', '`service_type` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'model_year_type', '`model_year_type` VARCHAR(20) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'model_year_value', '`model_year_value` VARCHAR(10) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'plate_part1', '`plate_part1` VARCHAR(2) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'plate_letter', '`plate_letter` VARCHAR(10) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'plate_part2', '`plate_part2` VARCHAR(3) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'plate_iran', '`plate_iran` VARCHAR(2) NULL');
CALL moghareh_add_column_if_missing('portal_service_requests_staging', 'updated_at', '`updated_at` DATETIME NULL');

DROP PROCEDURE moghareh_add_column_if_missing;

INSERT INTO staff_users (full_name, username, password_hash, role_name, is_master_admin, is_active, created_at, updated_at)
VALUES
('مدیر کل', 'manager', '$2y$10$HsXR22HiVjMNoutr1Bu7sOQ.TcgF8M.726pkDg12xVExaN7Zrh6gy', 'مدیر کل - فقط مشاهده و گزارش', 0, 1, NOW(), NOW()),
('امیر علی', 'amir', '$2y$10$Y59Y7XNI7zZSo1UwQZt2wuK5IQhD6vpqoVzqfNj5dVMreQ3qpy7dy', 'مالک / مدیر ارشد - اصلاحات کامل', 1, 1, NOW(), NOW()),
('کاربر ثبت ۱', 'reception1', '$2y$10$yu.OwPFFvyrSnLQTAhCDiuhoHHC4ijnogDPQ51SAdr3rKcnAc98YG', 'ثبت اطلاعات', 0, 1, NOW(), NOW()),
('کاربر ثبت ۲', 'reception2', '$2y$10$yu.OwPFFvyrSnLQTAhCDiuhoHHC4ijnogDPQ51SAdr3rKcnAc98YG', 'ثبت اطلاعات', 0, 1, NOW(), NOW()),
('کاربر ثبت ۳', 'reception3', '$2y$10$yu.OwPFFvyrSnLQTAhCDiuhoHHC4ijnogDPQ51SAdr3rKcnAc98YG', 'ثبت اطلاعات', 0, 1, NOW(), NOW()),
('کنترل انبار و قیمت خرید', 'warehouse_price', '$2y$10$lMpFmIkU5NpPnVxQgJ4sT.zzeR6V8XzO9v/Tm3r2EECB0DH5J8Mme', 'انبار / کنترل قیمت خرید', 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  password_hash = VALUES(password_hash),
  role_name = VALUES(role_name),
  is_master_admin = VALUES(is_master_admin),
  is_active = VALUES(is_active),
  updated_at = NOW();
