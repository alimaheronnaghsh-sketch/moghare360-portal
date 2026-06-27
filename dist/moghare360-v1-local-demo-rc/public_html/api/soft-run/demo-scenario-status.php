<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/m360-demo-scenario-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    m360_soft_run_json(['ok' => false, 'message' => 'فقط GET مجاز است.'], 405);
    exit;
}

m360_soft_run_require_staff();

try {
    $conn = customer_core_db();
    $demo = $conn !== false ? m360_soft_run_find_demo_jobcard($conn) : null;
    $jobcardId = (int)($demo['jobcard_id'] ?? 0);
    m360_soft_run_json([
        'ok' => true,
        'read_only' => true,
        'demo_prefix' => M360_SOFT_RUN_DEMO_PREFIX,
        'demo_jobcard_id' => $jobcardId,
        'stages' => $conn !== false ? m360_demo_scenario_status($conn, $jobcardId) : [],
    ]);
} catch (Throwable) {
    m360_soft_run_json(['ok' => false, 'message' => 'بارگذاری سناریو ناموفق بود.'], 500);
}
