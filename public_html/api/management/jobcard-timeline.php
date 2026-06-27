<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-jobcard-timeline-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    m360_mgmt_json_response(['ok' => false, 'message' => 'فقط GET مجاز است.'], 405);
    exit;
}

m360_mgmt_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
if ($jobcardId < 1) {
    m360_mgmt_json_response(['ok' => false, 'message' => 'jobcard_id الزامی است.'], 400);
    exit;
}

try {
    $conn = customer_core_db();
    if ($conn === false) {
        m360_mgmt_json_response(['ok' => false, 'message' => 'اتصال پایگاه داده برقرار نشد.'], 503);
        exit;
    }
    $data = m360_timeline_build($conn, $jobcardId);
    m360_mgmt_json_response([
        'ok' => true,
        'read_only' => true,
        'jobcard_id' => $jobcardId,
        'jobcard' => $data['jobcard'],
        'events' => $data['events'],
        'milestones_fa' => M360_TIMELINE_MILESTONES_FA,
    ]);
} catch (Throwable) {
    m360_mgmt_json_response(['ok' => false, 'message' => 'بارگذاری timeline ناموفق بود.'], 500);
}
