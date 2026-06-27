<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-bottleneck-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    m360_mgmt_json_response(['ok' => false, 'message' => 'فقط GET مجاز است.'], 405);
    exit;
}

m360_mgmt_require_staff();

try {
    $conn = customer_core_db();
    if ($conn === false) {
        m360_mgmt_json_response(['ok' => false, 'message' => 'اتصال پایگاه داده برقرار نشد.'], 503);
        exit;
    }
    m360_mgmt_json_response([
        'ok' => true,
        'read_only' => true,
        'summary' => m360_bottleneck_summary($conn),
        'stuck' => m360_bottleneck_stuck_list($conn, 50),
    ]);
} catch (Throwable) {
    m360_mgmt_json_response(['ok' => false, 'message' => 'بارگذاری گلوگاه ناموفق بود.'], 500);
}
