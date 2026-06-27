SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_items_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(40) NULL,
  item_name VARCHAR(200) NOT NULL,
  main_category VARCHAR(150) NULL,
  manufacturer_brand VARCHAR(150) NULL,
  technical_code VARCHAR(120) NULL,
  oem_code VARCHAR(120) NULL,
  internal_code VARCHAR(120) NULL,
  barcode VARCHAR(120) NULL,
  vehicle_brand VARCHAR(80) NULL,
  vehicle_model VARCHAR(120) NULL,
  unit_name VARCHAR(50) NULL,
  item_quality VARCHAR(100) NULL,
  compatibility_status VARCHAR(100) NULL,
  warehouse_code VARCHAR(80) NULL,
  warehouse_name VARCHAR(150) NULL,
  warehouse_location_code VARCHAR(100) NULL,
  warehouse_location_name VARCHAR(150) NULL,
  workflow_status VARCHAR(80) NOT NULL DEFAULT 'Draft',
  technical_validation_status VARCHAR(100) NOT NULL DEFAULT 'در انتظار تایید فنی',
  quantity DECIMAL(18,2) NULL,
  counted_quantity DECIMAL(18,2) NULL,
  minimum_stock DECIMAL(18,2) NULL,
  purchase_price_rial DECIMAL(18,2) NULL,
  suggested_sale_price DECIMAL(18,2) NULL,
  description TEXT NULL,
  receipt_photo_path VARCHAR(500) NULL,
  item_photo_path VARCHAR(500) NULL,
  created_by_staff_id INT NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  sync_error TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_inventory_receipt (receipt_number),
  INDEX idx_inventory_item_name (item_name),
  INDEX idx_inventory_technical_code (technical_code),
  INDEX idx_inventory_oem_code (oem_code),
  INDEX idx_inventory_internal_code (internal_code),
  INDEX idx_inventory_barcode (barcode),
  INDEX idx_inventory_vehicle (vehicle_brand, vehicle_model),
  INDEX idx_inventory_workflow (workflow_status),
  INDEX idx_inventory_sync (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements_staging (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movement_type VARCHAR(40) NOT NULL,
  receipt_number VARCHAR(40) NULL,
  inventory_item_id INT NULL,
  item_name VARCHAR(200) NOT NULL,
  technical_code VARCHAR(120) NULL,
  oem_code VARCHAR(120) NULL,
  internal_code VARCHAR(120) NULL,
  quantity DECIMAL(18,2) NOT NULL DEFAULT 0,
  unit_name VARCHAR(50) NULL,
  source_location VARCHAR(150) NULL,
  destination_location VARCHAR(150) NULL,
  purchase_price_rial DECIMAL(18,2) NULL,
  related_jobcard VARCHAR(80) NULL,
  movement_note TEXT NULL,
  receipt_photo_path VARCHAR(500) NULL,
  created_by_staff_id INT NULL,
  sync_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_movement_type_date (movement_type, created_at),
  INDEX idx_movement_receipt (receipt_number),
  INDEX idx_movement_item (inventory_item_id)
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

CALL moghareh_add_column_if_missing('inventory_items_staging', 'receipt_number', '`receipt_number` VARCHAR(40) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'manufacturer_brand', '`manufacturer_brand` VARCHAR(150) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'technical_code', '`technical_code` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'oem_code', '`oem_code` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'internal_code', '`internal_code` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'barcode', '`barcode` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'vehicle_brand', '`vehicle_brand` VARCHAR(80) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'vehicle_model', '`vehicle_model` VARCHAR(120) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'compatibility_status', '`compatibility_status` VARCHAR(100) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'warehouse_code', '`warehouse_code` VARCHAR(80) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'warehouse_name', '`warehouse_name` VARCHAR(150) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'warehouse_location_code', '`warehouse_location_code` VARCHAR(100) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'warehouse_location_name', '`warehouse_location_name` VARCHAR(150) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'workflow_status', '`workflow_status` VARCHAR(80) NOT NULL DEFAULT ''Draft''');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'technical_validation_status', '`technical_validation_status` VARCHAR(100) NOT NULL DEFAULT ''در انتظار تایید فنی''');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'quantity', '`quantity` DECIMAL(18,2) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'counted_quantity', '`counted_quantity` DECIMAL(18,2) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'purchase_price_rial', '`purchase_price_rial` DECIMAL(18,2) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'receipt_photo_path', '`receipt_photo_path` VARCHAR(500) NULL');
CALL moghareh_add_column_if_missing('inventory_items_staging', 'item_photo_path', '`item_photo_path` VARCHAR(500) NULL');

DROP PROCEDURE IF EXISTS moghareh_add_column_if_missing;

SELECT 'patch_stockcenter_inventory.sql applied' AS result;
