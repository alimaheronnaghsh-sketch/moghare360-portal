<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    m360_mgmt_json_response(['ok' => false, 'message' => 'فقط GET مجاز است.'], 405);
    exit;
}

m360_mgmt_require_staff();

$period = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'today';
if (!isset(M360_MGMT_PERIOD_LABELS_FA[$period])) {
    $period = 'today';
}

try {
    $conn = customer_core_db();
    if ($conn === false) {
        m360_mgmt_json_response(['ok' => false, 'message' => 'اتصال پایگاه داده برقرار نشد.'], 503);
        exit;
    }
    $payload = [
        'ok' => true,
        'read_only' => true,
        'period' => $period,
        'cards' => m360_mgmt_dashboard_cards($conn, $period),
        'kpi' => m360_mgmt_kpi_full($conn, $period),
    ];
    m360_mgmt_json_response($payload);
} catch (Throwable) {
    m360_mgmt_json_response(['ok' => false, 'message' => 'بارگذاری KPI ناموفق بود.'], 500);
}
