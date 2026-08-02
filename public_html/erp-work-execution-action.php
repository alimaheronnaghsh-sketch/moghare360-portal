<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-work-execution-board.php');
    exit;
}

m360_work_require_staff();
erp_csrf_require_valid(M360_WORK_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$postedTeam = strtoupper(trim((string)($_POST['team_code'] ?? $_POST['unit'] ?? '')));
$redirectUnit = m360_fulljob_is_valid_team_code($postedTeam) ? ('&unit=' . rawurlencode($postedTeam)) : '';
$redirect = 'erp-work-execution-detail.php?jobcard_id=' . $jobcardId . $redirectUnit;

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
$roleCode = m360_staff_home_require_role_code($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$userId = (int)(erp_auth_current_user_id() ?? 0);
$actor = ['user_id' => $userId, 'role_code' => (string)$roleCode];

// Only Hall Manager / Owner may send to QC — unit completion must not go direct to QC.
if ($action === 'ready_for_qc') {
    if (!m360_fulljob_role_can_hall((string)$roleCode)) {
        http_response_code(403);
        header('Location: ' . $redirect . '&msg=' . rawurlencode('فقط مدیر سالن مجاز به ارسال پرونده به کنترل کیفیت است.') . '&ok=0');
        exit;
    }
    $jcForQc = m360_fulljob_fetch_jobcard($conn, $jobcardId) ?? [];
    if (!m360_fulljob_can_render_ready_for_qc((string)$roleCode, $jcForQc)) {
        http_response_code(403);
        header('Location: ' . $redirect . '&msg=' . rawurlencode('پرونده هنوز در وضعیت بازبینی مدیر سالن برای ارسال به کنترل کیفیت نیست.') . '&ok=0');
        exit;
    }
}

$completionCtx = null;
if ($action === 'complete_technical_work') {
    $completionCtx = m360_fulljob_validate_unit_completion_context($conn, $jobcardId, $postedTeam, $actor);
    if (empty($completionCtx['ok'])) {
        http_response_code((int)($completionCtx['http'] ?? 422));
        header('Location: ' . $redirect . '&msg=' . rawurlencode((string)$completionCtx['message']) . '&ok=0');
        exit;
    }
    $postedTeam = (string)$completionCtx['team_code'];
    $redirect = 'erp-work-execution-detail.php?jobcard_id=' . $jobcardId . '&unit=' . rawurlencode($postedTeam);
}

$result = m360_work_apply_action($conn, $jobcardId, $action, $_POST, $userId);

if (!empty($result['ok']) && $action === 'complete_technical_work' && is_array($completionCtx)) {
    $hallReturn = m360_fulljob_return_unit_completion_to_hall($conn, $jobcardId, (string)$completionCtx['team_code'], $userId);
    if (empty($hallReturn['ok'])) {
        header('Location: ' . $redirect . '&msg=' . rawurlencode((string)$hallReturn['message']) . '&ok=0');
        exit;
    }
    $msg = (string)$hallReturn['message'];
    if (!empty($completionCtx['inferred'])) {
        $msg .= ' (واحد از تنها تخصیص فعال استنتاج شد.)';
    }
    $result['message'] = $msg;
}

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
