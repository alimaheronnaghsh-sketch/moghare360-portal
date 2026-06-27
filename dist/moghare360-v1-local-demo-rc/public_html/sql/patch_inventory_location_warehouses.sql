SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warehouse_name VARCHAR(150) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(150) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 100,
  requires_vehicle_identity TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_name VARCHAR(50) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_item_qualities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quality_name VARCHAR(80) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warehouse_id INT NOT NULL,
  floor_code VARCHAR(10) NOT NULL,
  row_code VARCHAR(10) NOT NULL,
  rack_code VARCHAR(10) NOT NULL,
  section_code VARCHAR(10) NOT NULL,
  location_code VARCHAR(120) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_location_warehouse (warehouse_id),
  INDEX idx_location_code (location_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(40) NULL UNIQUE,
  operation_type VARCHAR(50) NULL,
  item_name VARCHAR(200) NOT NULL,
  technical_code VARCHAR(100) NOT NULL,
  quality_level VARCHAR(50) NOT NULL,
  category_id INT NULL,
  category_name VARCHAR(150) NULL,
  engine_number VARCHAR(80) NULL,
  body_number VARCHAR(80) NULL,
  oem_code VARCHAR(100) NULL,
  internal_code VARCHAR(100) NULL,
  barcode VARCHAR(100) NULL,
  unit_id INT NULL,
  unit_name VARCHAR(50) NULL,
  quantity DECIMAL(18,2) NULL,
  minimum_stock DECIMAL(18,2) NULL,
  warehouse_id INT NULL,
  warehouse_name VARCHAR(150) NULL,
  floor_code VARCHAR(10) NULL,
  row_code VARCHAR(10) NULL,
  rack_code VARCHAR(10) NULL,
  section_code VARCHAR(10) NULL,
  location_code VARCHAR(120) NULL,
  purchase_price DECIMAL(18,2) NULL,
  suggested_sale_price DECIMAL(18,2) NULL,
  description TEXT NULL,
  receipt_photo_path VARCHAR(500) NULL,
  item_photo_path VARCHAR(500) NULL,
  technical_status VARCHAR(80) NULL,
  workflow_status VARCHAR(80) NULL,
  created_by_staff_id INT NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_inventory_receipt_number (receipt_number),
  INDEX idx_inventory_item_name (item_name),
  INDEX idx_inventory_technical_code (technical_code),
  INDEX idx_inventory_location_code (location_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movement_type VARCHAR(50) NOT NULL,
  inventory_item_id INT NULL,
  receipt_number VARCHAR(40) NULL,
  item_name VARCHAR(200) NULL,
  technical_code VARCHAR(100) NULL,
  quantity DECIMAL(18,2) NOT NULL DEFAULT 0,
  warehouse_id INT NULL,
  warehouse_name VARCHAR(150) NULL,
  floor_code VARCHAR(10) NULL,
  row_code VARCHAR(10) NULL,
  rack_code VARCHAR(10) NULL,
  section_code VARCHAR(10) NULL,
  location_code VARCHAR(120) NULL,
  purchase_price DECIMAL(18,2) NULL,
  receipt_photo_path VARCHAR(500) NULL,
  notes TEXT NULL,
  created_by_staff_id INT NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_inventory_movement_type (movement_type),
  INDEX idx_inventory_movement_receipt (receipt_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_inventory_col_if_missing;

DELIMITER //
CREATE PROCEDURE add_inventory_col_if_missing(
  IN p_table VARCHAR(64),
  IN p_col VARCHAR(64),
  IN p_def TEXT
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
  )
  AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_def);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

CALL add_inventory_col_if_missing('inventory_items_staging', 'receipt_number', '`receipt_number` VARCHAR(40) NULL UNIQUE');
CALL add_inventory_col_if_missing('inventory_items_staging', 'operation_type', '`operation_type` VARCHAR(50) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'technical_code', '`technical_code` VARCHAR(100) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'quality_level', '`quality_level` VARCHAR(50) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'category_id', '`category_id` INT NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'category_name', '`category_name` VARCHAR(150) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'engine_number', '`engine_number` VARCHAR(80) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'body_number', '`body_number` VARCHAR(80) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'warehouse_id', '`warehouse_id` INT NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'warehouse_name', '`warehouse_name` VARCHAR(150) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'floor_code', '`floor_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'row_code', '`row_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'rack_code', '`rack_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'section_code', '`section_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'location_code', '`location_code` VARCHAR(120) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'receipt_photo_path', '`receipt_photo_path` VARCHAR(500) NULL');
CALL add_inventory_col_if_missing('inventory_items_staging', 'item_photo_path', '`item_photo_path` VARCHAR(500) NULL');

CALL add_inventory_col_if_missing('inventory_movements_staging', 'warehouse_name', '`warehouse_name` VARCHAR(150) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'floor_code', '`floor_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'row_code', '`row_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'rack_code', '`rack_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'section_code', '`section_code` VARCHAR(10) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'location_code', '`location_code` VARCHAR(120) NULL');
CALL add_inventory_col_if_missing('inventory_movements_staging', 'receipt_photo_path', '`receipt_photo_path` VARCHAR(500) NULL');

DROP PROCEDURE IF EXISTS add_inventory_col_if_missing;

INSERT IGNORE INTO inventory_warehouses (warehouse_name, sort_order) VALUES
('انبار مجموعه', 1),
('انبار صحرا', 2),
('انبار نیک‌اندیش', 3);

INSERT IGNORE INTO inventory_categories (category_name, sort_order, requires_vehicle_identity) VALUES
('موتور و گیربکس', 1, 1),
('تعلیق و زیروبند', 2, 1),
('مصرفی و سرویس دوره‌ای', 3, 1),
('کولر و رادیاتور', 4, 1),
('برق و الکترونیک', 5, 1),
('بدنه و رنگ', 6, 1),
('آپشن و ارتقا', 7, 1),
('مبلمان داخلی', 8, 1),
('سایر', 9, 1);

INSERT IGNORE INTO inventory_units (unit_name) VALUES
('عدد'), ('ست'), ('جفت'), ('لیتر'), ('کیلوگرم'), ('متر'), ('بسته');

INSERT IGNORE INTO inventory_item_qualities (quality_name, sort_order) VALUES
('شرکتی', 1),
('اصلی', 2),
('استوک', 3);

INSERT IGNORE INTO inventory_locations
(warehouse_id, floor_code, row_code, rack_code, section_code, location_code)
SELECT w.id, f.floor_code, r.row_code, k.rack_code, s.section_code,
       CONCAT(w.warehouse_name, '-', f.floor_code, '-R', r.row_code, '-Q', k.rack_code, '-B', s.section_code)
FROM inventory_warehouses w
JOIN (
  SELECT '-2' floor_code UNION ALL SELECT '-1' UNION ALL SELECT 'G'
  UNION ALL SELECT '1' UNION ALL SELECT '2' UNION ALL SELECT '3'
) f
JOIN (
  SELECT '1' row_code UNION ALL SELECT '2' UNION ALL SELECT '3' UNION ALL SELECT '4' UNION ALL SELECT '5'
  UNION ALL SELECT '6' UNION ALL SELECT '7' UNION ALL SELECT '8' UNION ALL SELECT '9' UNION ALL SELECT '10'
) r
JOIN (
  SELECT '1' rack_code UNION ALL SELECT '2' UNION ALL SELECT '3' UNION ALL SELECT '4' UNION ALL SELECT '5'
) k
JOIN (
  SELECT '1' section_code UNION ALL SELECT '2' UNION ALL SELECT '3' UNION ALL SELECT '4' UNION ALL SELECT '5'
  UNION ALL SELECT '6' UNION ALL SELECT '7' UNION ALL SELECT '8' UNION ALL SELECT '9' UNION ALL SELECT '10'
) s;

SELECT 'Inventory warehouse/location patch applied successfully' AS result;
