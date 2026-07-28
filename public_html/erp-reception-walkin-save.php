<?php
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-walkin-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>روش نامعتبر</title><link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css"></head><body class="m360-public-shell m360-rw-page"><div class="m360-wrap m360-rw-wrap"><section class="m360-rw-alert">این مسیر فقط با ارسال فرم مجاز است.</section><a class="m360-rw-btn" href="erp-reception-walkin-create.php">بازگشت</a></div></body></html>';
    exit;
}

$conn = customer_core_db();
if (!is_resource($conn)) {
    m360_walkin_set_flash(['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.']);
    header('Location: erp-reception-walkin-create.php');
    exit;
}

$actor = m360_walkin_require_actor($conn);
if (!$actor['ok']) {
    if ((int)$actor['status'] === 401) {
        header('Location: staff-login.php');
        exit;
    }
    http_response_code((int)$actor['status']);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>دسترسی غیرمجاز</title><link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css"></head><body class="m360-public-shell m360-rw-page"><div class="m360-wrap m360-rw-wrap"><section class="m360-rw-alert">' . m360_rw_h($actor['message']) . '</section><a class="m360-rw-btn" href="erp-reception-workbench.php">بازگشت</a></div></body></html>';
    exit;
}

$csrfToken = isset($_POST['erp_csrf_token']) ? (string)$_POST['erp_csrf_token'] : null;
if (!m360_reception_csrf_is_valid($csrfToken)) {
    m360_reception_render_action_error_page('csrf', 0, 'intake', 'vehicle');
    exit;
}

$result = m360_walkin_create($conn, $_POST);
if (!$result['ok']) {
    m360_walkin_set_flash(['ok' => false, 'message' => $result['message']]);
    header('Location: erp-reception-walkin-create.php');
    exit;
}

$requestId = (int)$result['online_request_id'];
// After Stage 1–4 create: hand off to canonical intake-file Stage 5 engine.
header(
    'Location: erp-reception-intake-file.php?online_request_id=' . $requestId
    . '&active_step=condition&walkin_created=1&ok=1#section-condition-photos'
);
exit;
