<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-qc-board.php');
    exit;
}

m360_qc_require_staff();
erp_csrf_require_valid(M360_QC_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$qcCheckId = isset($_POST['qc_check_id']) ? (int)$_POST['qc_check_id'] : 0;
$redirect = 'erp-qc-detail.php?jobcard_id=' . $jobcardId;
if ($qcCheckId > 0) {
    $redirect .= '&qc_check_id=' . $qcCheckId;
}

if ($jobcardId < 1 || $action === '') {
    header('Location: erp-qc-board.php?msg=' . rawurlencode('درخواست نامعتبر است.') . '&ok=0');
    exit;
}

$qcPerm = m360_ws_qc_permission_for_action($action);
if ($qcPerm === null) {
    // delivery_ready / hold / cancel remain gated by queue view (no silent elevate to approve)
    m360_ws_require('workshop.qc.queue.view', $jobcardId);
} else {
    m360_ws_require($qcPerm, $jobcardId);
}
if (in_array($action, ['qc_failed', 'rework_required'], true)) {
    $reason = trim((string)($_POST['failure_reason'] ?? $_POST['return_reason'] ?? ''));
    if ($reason === '') {
        m360_am_forbidden('برای برگشت از کنترل کیفیت، ذکر دلیل به فارسی الزامی است.');
    }
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
m360_staff_home_require_role_code($conn, ['OWNER', 'SYSTEM_ADMIN', 'QC']);
$userId = (int)(erp_auth_current_user_id() ?? 0);

$result = m360_qc_apply_action($conn, $jobcardId, $action, $_POST, $userId, $qcCheckId > 0 ? $qcCheckId : null);

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
