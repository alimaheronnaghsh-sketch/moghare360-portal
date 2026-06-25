<?php
declare(strict_types=1);

/*
  MOGHARE360 Inventory Role Fix
  Run once, then delete this file.
  URL:
  /fix-inventory-users-access.php?key=FIX-INV-USERS-360
*/

require __DIR__ . '/config.php';

$key = $_GET['key'] ?? '';
if ($key !== 'FIX-INV-USERS-360') {
    http_response_code(403);
    exit('Forbidden');
}

try {
    $pdo = getPdo();

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $users = [
        ['یزدانی', 'yazdani', 'Yazdani@1984'],
        ['سلیمی', 'salimi', 'Salimi@2020'],
        ['جعفر', 'jafar', 'Jafar@360'],
        ['سهیل', 'soheil', 'Soheil@360'],
        ['امید', 'omid', 'Omid@360'],
    ];

    $role = 'انبار - فقط ثبت کالا بدون مبلغ و ریال';

    $stmt = $pdo->prepare("
        INSERT INTO staff_users
        (full_name, username, password_hash, role_name, is_master_admin, is_active, created_at, updated_at)
        VALUES
        (?, ?, ?, ?, 0, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          full_name = VALUES(full_name),
          password_hash = VALUES(password_hash),
          role_name = VALUES(role_name),
          is_master_admin = 0,
          is_active = 1,
          updated_at = NOW()
    ");

    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $role]);
    }

    echo '<pre style="direction:ltr;text-align:left;font-family:Consolas,monospace">';
    echo "INVENTORY USERS RESET OK\n\n";
    echo "yazdani / Yazdani@1984\n";
    echo "salimi / Salimi@2020\n";
    echo "jafar / Jafar@360\n";
    echo "soheil / Soheil@360\n";
    echo "omid / Omid@360\n\n";

    $check = $pdo->query("
        SELECT id, full_name, username, role_name, is_master_admin, is_active, LENGTH(password_hash) AS hash_len
        FROM staff_users
        WHERE username IN ('yazdani','salimi','jafar','soheil','omid')
        ORDER BY username
    ")->fetchAll();

    print_r($check);
    echo '</pre>';

} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="direction:ltr;text-align:left">';
    echo 'ERROR: ' . $e->getMessage();
    echo '</pre>';
}
