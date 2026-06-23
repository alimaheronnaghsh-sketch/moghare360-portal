-- MOGHARE360 Customer Update Fix Patch
-- Target DB: moghareh_portal

DROP PROCEDURE IF EXISTS moghareh_add_col_if_missing;
DELIMITER $$
CREATE PROCEDURE moghareh_add_col_if_missing(
    IN p_table_name VARCHAR(128),
    IN p_column_name VARCHAR(128),
    IN p_column_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
            '` ADD COLUMN `', REPLACE(p_column_name, '`', '``'),
            '` ', p_column_definition
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL moghareh_add_col_if_missing('portal_customers_staging', 'first_name', 'VARCHAR(100) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'last_name', 'VARCHAR(100) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'full_name', 'VARCHAR(220) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'national_code', 'VARCHAR(10) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'profile_photo_path', 'VARCHAR(500) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'postal_address', 'TEXT NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'job_title', 'VARCHAR(120) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'birth_date', 'VARCHAR(50) NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'profile_completed_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_customers_staging', 'sync_status', "VARCHAR(20) NOT NULL DEFAULT 'Pending'");
CALL moghareh_add_col_if_missing('portal_customers_staging', 'updated_at', 'DATETIME NULL');

DROP PROCEDURE IF EXISTS moghareh_add_col_if_missing;
