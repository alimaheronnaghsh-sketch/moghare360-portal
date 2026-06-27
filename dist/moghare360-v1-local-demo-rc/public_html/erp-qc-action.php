<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';

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

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = (int)(erp_auth_current_user_id() ?? 0);

$result = m360_qc_apply_action($conn, $jobcardId, $action, $_POST, $userId, $qcCheckId > 0 ? $qcCheckId : null);

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
