<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/m360-demo-readiness-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    m360_soft_run_json(['ok' => false, 'message' => 'فقط GET مجاز است.'], 405);
    exit;
}

m360_soft_run_require_staff();

try {
    $conn = customer_core_db();
    $report = $conn !== false ? m360_readiness_report($conn) : m360_readiness_report(false);
    m360_soft_run_json([
        'ok' => true,
        'read_only' => true,
        'report' => $report,
        'categories' => $conn !== false ? m360_soft_run_readiness_categories($conn) : [],
        'phases' => $conn !== false ? m360_soft_run_phase_status($conn) : [],
    ]);
} catch (Throwable) {
    m360_soft_run_json(['ok' => false, 'message' => 'بارگذاری readiness ناموفق بود.'], 500);
}
