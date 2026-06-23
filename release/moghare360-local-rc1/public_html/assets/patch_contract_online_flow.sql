-- MOGHARE360 Contract Online Flow Patch
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

CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'accepted_terms_json', 'TEXT NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'contract_version', 'VARCHAR(40) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'accepted_national_code', 'VARCHAR(10) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'accepted_mobile', 'VARCHAR(20) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'accepted_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'service_request_code', 'VARCHAR(40) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'contract_viewed_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'contract_view_closed_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'contract_status', 'VARCHAR(40) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'contract_pdf_path', 'VARCHAR(500) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'otp_hash', 'VARCHAR(255) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'otp_sent_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'otp_verified_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'otp_expires_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'otp_attempt_count', 'INT NOT NULL DEFAULT 0');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'customer_ip', 'VARCHAR(45) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'user_agent', 'VARCHAR(255) NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'sync_status', "VARCHAR(20) NOT NULL DEFAULT 'Pending'");
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'sync_error', 'TEXT NULL');
CALL moghareh_add_col_if_missing('portal_contract_confirmations', 'updated_at', 'DATETIME NULL');

CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'jobcard_status', 'VARCHAR(40) NULL');
CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'contract_confirmed', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'request_status', 'VARCHAR(40) NULL');
CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'updated_at', 'DATETIME NULL');
CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'sync_status', "VARCHAR(30) NOT NULL DEFAULT 'Pending'");
CALL moghareh_add_col_if_missing('portal_service_requests_staging', 'sync_error', 'TEXT NULL');

DROP PROCEDURE IF EXISTS moghareh_add_col_if_missing;
