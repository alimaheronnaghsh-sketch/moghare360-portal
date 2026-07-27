<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-estimate-board.php');
    exit;
}

m360_estimate_require_staff();

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$estimateId = isset($_POST['estimate_id']) ? (int)$_POST['estimate_id'] : 0;
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;

$redirect = $jobcardId > 0
    ? 'erp-estimate-detail.php?jobcard_id=' . $jobcardId
    : ($estimateId > 0
        ? 'erp-estimate-detail.php?estimate_id=' . $estimateId
        : 'erp-estimate-board.php');

$redirectWithMsg = static function (string $base, string $msg, bool $ok): string {
    return $base . (str_contains($base, '?') ? '&' : '?') . 'msg=' . rawurlencode($msg) . '&ok=' . ($ok ? '1' : '0');
};

if (!erp_csrf_validate_token(M360_ESTIMATE_CSRF, (string)($_POST['erp_csrf_token'] ?? ''))) {
    $safeRedirect = ($estimateId > 0 || $jobcardId > 0) ? $redirect : 'erp-estimate-board.php';
    header('Location: ' . $redirectWithMsg($safeRedirect, 'درخواست امنیتی نامعتبر است. لطفاً صفحه برآورد را دوباره باز کنید و اقدام را تکرار کنید.', false));
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirectWithMsg($redirect, 'اتصال برقرار نشد.', false));
    exit;
}

erp_auth_context_start();
$companyId = (int)($_SESSION['erp_company_id'] ?? 1);
$userId = (int)(erp_auth_current_user_id() ?? 0);
$roleCode = m360_staff_home_resolve_role_code($conn, $userId, $companyId);
$allowedRoles = ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER'];
if (!in_array($roleCode, $allowedRoles, true)) {
    header('Location: ' . $redirectWithMsg($redirect, 'دسترسی این اقدام برای نقش شما فعال نیست.', false));
    exit;
}

$payload = $_POST;
$result = m360_estimate_apply_action($conn, $action, $userId, $estimateId > 0 ? $estimateId : null, $jobcardId > 0 ? $jobcardId : null, $payload);

if ($result['ok'] && $action === 'create_draft' && $jobcardId > 0) {
    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est !== null) {
        $redirect = 'erp-estimate-detail.php?estimate_id=' . (int)$est['estimate_id'];
    }
}

header('Location: ' . $redirectWithMsg($redirect, (string)$result['message'], (bool)$result['ok']));
exit;
