<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config-sync.php';

    if (!function_exists('getPdo')) {
        throw new RuntimeException('تابع getPdo پیدا نشد.');
    }

    $pdo = getPdo();

    $data = [
        'customers' => [],
        'service_requests' => [],
        'contracts' => [],
        'inventory_items' => [],
        'inventory_movements' => []
    ];

    $stmt = $pdo->query("
        SELECT *
        FROM inventory_items_staging
        WHERE sync_status = 'Pending'
        ORDER BY id ASC
        LIMIT 100
    ");

    $data['inventory_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => $data,
        'time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}