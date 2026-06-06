SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS staff_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_name VARCHAR(120) NOT NULL DEFAULT 'کاربر',
  is_master_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  profile_photo_path VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_col_if_missing_inventory_patch;

DELIMITER //
CREATE PROCEDURE add_col_if_missing_inventory_patch(
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

CALL add_col_if_missing_inventory_patch('inventory_items_staging', 'row_side', '`row_side` VARCHAR(10) NULL');
CALL add_col_if_missing_inventory_patch('inventory_movements_staging', 'row_side', '`row_side` VARCHAR(10) NULL');

DROP PROCEDURE IF EXISTS add_col_if_missing_inventory_patch;

DELETE FROM staff_users
WHERE username IN ('yazdani', 'salimi');

INSERT INTO staff_users
(full_name, username, password_hash, role_name, is_master_admin, is_active, created_at, updated_at)
VALUES
(
  'یزدانی',
  'yazdani',
  '$2y$12$KkugJc95UleI9ZjW7nEwpu20ql8R5rRvJXr2D7xETq8gfZ0vJoP7i',
  'انبار - فقط ثبت کالا بدون مبلغ و ریال',
  0,
  1,
  NOW(),
  NOW()
),
(
  'سلیمی',
  'salimi',
  '$2y$12$ZItZ4O4wItEnCnW0De7k1Ocwlc3A3pZmnMfbU8Pf45VjJdcxstg1W',
  'انبار - فقط ثبت کالا بدون مبلغ و ریال',
  0,
  1,
  NOW(),
  NOW()
);

SELECT id, full_name, username, role_name, is_master_admin, is_active
FROM staff_users
WHERE username IN ('yazdani', 'salimi')
ORDER BY username;
