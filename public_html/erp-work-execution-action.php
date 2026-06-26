<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-work-execution-board.php');
    exit;
}

m360_work_require_staff();
erp_csrf_require_valid(M360_WORK_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$redirect = 'erp-work-execution-detail.php?jobcard_id=' . $jobcardId;

if ($jobcardId < 1 || $action === '') {
    header('Location: erp-work-execution-board.php?msg=' . rawurlencode('درخواست نامعتبر است.') . '&ok=0');
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = (int)(erp_auth_current_user_id() ?? 0);

$result = m360_work_apply_action($conn, $jobcardId, $action, $_POST, $userId);

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
