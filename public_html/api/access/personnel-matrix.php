<?php
declare(strict_types=1);

/**
 * Personnel access matrix JSON API — load matrix / apply / propose / review.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$m360AmApiRequire = static function (string $fileName): void {
    $root = dirname(__DIR__, 2);
    $candidates = [
        $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname($root) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            require_once $candidate;
            return;
        }
    }
    throw new RuntimeException('Required file not found: ' . $fileName);
};

$m360AmApiRequire('erp-auth-context.php');
$m360AmApiRequire('m360-access-matrix-helper.php');
// CSRF: use erp-csrf.php tokens (loaded via matrix → access-mgmt → customer-core).
// Do not require erp-csrf-helper.php (symbol collision with customer-core stubs).

erp_auth_require_login();
$conn = m360_am_db();
$actorId = m360_am_actor_user_id();

if ($conn === false || $actorId < 1) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'نشست معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!m360_am_can_manage_matrix($conn, $actorId)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'مجوز مدیریت ماتریس دسترسی را ندارید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$companyId = 1;
$mem = m360_am_one($conn, 'SELECT company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1', [$actorId]);
if ($mem) {
    $companyId = (int)$mem['company_id'];
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $module = trim((string)($_GET['module'] ?? ''));
    $q = trim((string)($_GET['q'] ?? ''));
    $unit = trim((string)($_GET['unit'] ?? ''));
    $personnel = m360_am_personnel_rows($conn, ['q' => $q, 'unit' => $unit]);
    foreach ($personnel as &$p) {
        $p['matrix'] = m360_am_matrix_state_for_user($conn, (int)$p['user_id'], (int)($p['company_id'] ?? $companyId), $module);
    }
    unset($p);

    $permSql = 'SELECT permission_id, permission_key, module_key, page_key, action_key, permission_label AS title_fa,
                       description_fa, is_base_self_service AS is_base, is_owner_only, sort_order,
                       group_key, group_title_fa, enforcement_state, is_assignable
                FROM dbo.core_permissions WHERE is_active=1';
    $params = [];
    if ($module !== '') {
        $permSql .= ' AND module_key=?';
        $params[] = $module;
    }
    $permSql .= ' ORDER BY sort_order, permission_key';
    $permissions = m360_am_fetch_all($conn, $permSql, $params);
    foreach ($permissions as &$permRow) {
        $enf = strtoupper((string)($permRow['enforcement_state'] ?? 'NOT_YET_ENFORCED'));
        $permRow['enforcement_state'] = $enf;
        $permRow['is_assignable'] = ((int)($permRow['is_assignable'] ?? 0) === 1 && $enf === 'ENFORCED') ? 1 : 0;
        $permRow['is_base'] = (int)($permRow['is_base'] ?? 0);
    }
    unset($permRow);

    $pending = m360_am_fetch_all(
        $conn,
        "SELECT TOP 50 p.pending_id, p.target_user_id, p.desired_effect, p.status, p.reason, p.created_at,
                cp.permission_key, cp.permission_label
         FROM dbo.core_access_matrix_pending p
         INNER JOIN dbo.core_permissions cp ON cp.permission_id=p.permission_id
         WHERE p.status=N'SUBMITTED'
         ORDER BY p.pending_id DESC"
    );

    echo json_encode([
        'ok' => true,
        'can_apply_direct' => m360_am_can_apply_direct($conn, $actorId),
        'is_owner' => m360_am_is_owner($conn, $actorId),
        'personnel' => $personnel,
        'permissions' => $permissions,
        'pending' => $pending,
        'modules' => m360_access_matrix_module_tabs(),
        'workshop_groups' => m360_access_matrix_workshop_groups(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'متد غیرمجاز.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$body = is_string($raw) && $raw !== '' ? json_decode($raw, true) : $_POST;
if (!is_array($body)) {
    $body = [];
}

$token = (string)($body['erp_csrf_token'] ?? '');
$csrfOk = function_exists('erp_csrf_validate_token')
    ? erp_csrf_validate_token(M360_ACCESS_MATRIX_CSRF, $token)
    : (function_exists('erp_csrf_validate') && erp_csrf_validate(M360_ACCESS_MATRIX_CSRF, $token));
if (!$csrfOk) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'توکن امنیتی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = strtolower(trim((string)($body['action'] ?? 'apply')));

if ($action === 'review') {
    if (!m360_am_can_apply_direct($conn, $actorId) && !m360_am_is_owner($conn, $actorId)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'تأیید پیشنهاد فقط برای مالک/اعمال‌کننده مجاز است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $res = m360_am_review_pending(
        $conn,
        (int)($body['pending_id'] ?? 0),
        (string)($body['decision'] ?? ''),
        trim((string)($body['note'] ?? '')),
        $actorId
    );
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

$targetUserId = (int)($body['user_id'] ?? 0);
$permissionKey = trim((string)($body['permission_key'] ?? ''));
$effect = strtoupper(trim((string)($body['effect'] ?? 'ALLOW')));
$reason = trim((string)($body['reason'] ?? ''));

if ($targetUserId < 1 || $permissionKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'کاربر یا مجوز نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Company scope: target must share active membership
$tgt = m360_am_one($conn, 'SELECT company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1', [$targetUserId]);
if ($tgt === null || (int)$tgt['company_id'] !== $companyId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'محدوده شرکت هدف مجاز نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$wantImmediate = ($action === 'apply');
$canImmediate = m360_am_can_apply_direct($conn, $actorId) || m360_am_is_owner($conn, $actorId);
if ($wantImmediate && !$canImmediate) {
    $wantImmediate = false; // fall back to proposal workflow
}
$res = m360_am_apply_override(
    $conn,
    $companyId,
    $targetUserId,
    $permissionKey,
    $effect,
    $reason,
    $actorId,
    $wantImmediate
);

echo json_encode($res, JSON_UNESCAPED_UNICODE);
