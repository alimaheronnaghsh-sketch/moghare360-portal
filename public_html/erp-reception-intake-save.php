<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2C — Reception intake completion save (POST only).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-reception-online-requests.php');
    exit;
}

m360_reception_require_staff();

$requestId = isset($_POST['online_request_id']) ? (int)$_POST['online_request_id'] : 0;
if ($requestId < 1 && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
}

$csrfToken = isset($_POST['erp_csrf_token']) ? (string)$_POST['erp_csrf_token'] : null;
if (!m360_reception_csrf_is_valid($csrfToken)) {
    m360_reception_render_action_error_page('csrf', $requestId);
    exit;
}

$actionType = trim((string)($_POST['action_type'] ?? ''));

if ($requestId < 1) {
    header('Location: erp-reception-online-requests.php?msg=' . rawurlencode('شناسه درخواست نامعتبر است.') . '&ok=0');
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . m360_rw_intake_save_redirect_url($requestId, 'اتصال به پایگاه داده برقرار نشد.', false, $_POST));
    exit;
}

try {
    $result = m360_rw_intake_process_save($conn, $requestId, $actionType, $_POST, $_FILES);
    header('Location: ' . m360_rw_intake_save_redirect_url($requestId, (string)$result['message'], (bool)$result['ok'], $_POST));
} catch (Throwable) {
    header('Location: ' . m360_rw_intake_save_redirect_url($requestId, M360_RW_INTAKE_SAVE_GENERIC_ERROR_FA, false, $_POST));
}
exit;
