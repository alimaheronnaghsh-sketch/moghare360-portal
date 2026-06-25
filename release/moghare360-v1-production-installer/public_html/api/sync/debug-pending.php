<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config-sync.php';

    if (!function_exists('getPdo')) {
        throw new RuntimeException('تابع getPdo پیدا نشد. config-sync.php اتصال دیتابیس را مثل config اصلی سایت تعریف نکرده است.');
    }

    $pdo = getPdo();

    $tables = [
        'inventory_items_staging',
        'portal_customers_staging',
        'portal_service_requests_staging'
    ];

    $result = [];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        $exists = (bool)$stmt->fetchColumn();

        $result[$table] = [
            'exists' => $exists,
            'pending_count' => null,
            'error' => null
        ];

        if ($exists) {
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table` WHERE sync_status = 'Pending'");
                $result[$table]['pending_count'] = (int)$countStmt->fetchColumn();
            } catch (Throwable $e) {
                $result[$table]['error'] = $e->getMessage();
            }
        }
    }

    echo json_encode([
        'step' => 'S3_TABLE_CHECK',
        'ok' => true,
        'data' => $result,
        'time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'step' => 'S3_FAILED',
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}