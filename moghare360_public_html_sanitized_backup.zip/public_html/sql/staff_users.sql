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
