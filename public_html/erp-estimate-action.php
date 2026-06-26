<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-estimate-board.php');
    exit;
}

m360_estimate_require_staff();
erp_csrf_require_valid(M360_ESTIMATE_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$estimateId = isset($_POST['estimate_id']) ? (int)$_POST['estimate_id'] : 0;
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;

$redirect = $estimateId > 0
    ? 'erp-estimate-detail.php?estimate_id=' . $estimateId
    : 'erp-estimate-detail.php?jobcard_id=' . $jobcardId;

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = (int)(erp_auth_current_user_id() ?? 0);

$payload = $_POST;
$result = m360_estimate_apply_action($conn, $action, $userId, $estimateId > 0 ? $estimateId : null, $jobcardId > 0 ? $jobcardId : null, $payload);

if ($result['ok'] && $action === 'create_draft' && $jobcardId > 0) {
    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est !== null) {
        $redirect = 'erp-estimate-detail.php?estimate_id=' . (int)$est['estimate_id'];
    }
}

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
