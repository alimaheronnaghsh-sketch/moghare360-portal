<?php
declare(strict_types=1);

/**
 * Personnel access matrix — effective permission resolver, personnel rows, apply/propose.
 */

require_once __DIR__ . '/erp-customer-core-helper.php';
require_once __DIR__ . '/m360-access-management-helper.php';
require_once __DIR__ . '/m360-access-matrix-catalog.php';

const M360_ACCESS_MATRIX_CSRF = 'access_matrix_v1';

function m360_am_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return resource|false */
function m360_am_db()
{
    return m360_access_mgmt_db();
}

function m360_am_fetch_all($conn, string $sql, array $params = []): array
{
    return m360_access_fetch_rows($conn, $sql, $params);
}

function m360_am_one($conn, string $sql, array $params = []): ?array
{
    $rows = m360_am_fetch_all($conn, $sql, $params);
    return $rows[0] ?? null;
}

function m360_am_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function m360_am_actor_user_id(): int
{
    if (!function_exists('erp_auth_current_user_id')) {
        require_once dirname(__DIR__, 2) . '/includes/erp-auth-context.php';
    }
    return (int)(erp_auth_current_user_id() ?? 0);
}

/**
 * Owner-facing Persian label for erp_company_users.role_code (presentation only).
 * Does not rename stored codes.
 */
function m360_am_company_role_label_fa(string $roleCode): string
{
    $code = strtoupper(trim($roleCode));
    if ($code === '' || $code === 'EMPLOYEE') {
        return 'سرمایه انسانی';
    }
    if ($code === 'OWNER' || $code === 'SYSTEM_OWNER') {
        return 'مالک سیستم';
    }
    if ($code === 'SYSTEM_ADMIN') {
        return 'مدیر سیستم';
    }
    if (function_exists('m360_access_mgmt_resolve_role_code')) {
        $mapped = m360_access_mgmt_resolve_role_code($code);
        if (is_array($mapped) && trim((string)($mapped['label_fa'] ?? '')) !== '') {
            return (string)$mapped['label_fa'];
        }
    }
    return $code;
}

/**
 * Normalize core_roles display names for Owner-facing matrix (presentation only).
 *
 * @param array{role_key?:string,role_name?:string} $role
 */
function m360_am_core_role_label_fa(array $role): string
{
    $key = strtolower(trim((string)($role['role_key'] ?? '')));
    $fixed = [
        'owner' => 'مالک سیستم',
        'system_admin' => 'مدیر سیستم',
    ];
    if (isset($fixed[$key])) {
        return $fixed[$key];
    }
    $name = trim((string)($role['role_name'] ?? ''));
    return $name !== '' ? $name : ($key !== '' ? $key : '—');
}

function m360_am_is_owner($conn, int $userId): bool
{
    if ($userId < 1) {
        return false;
    }
    $row = m360_am_one($conn, "SELECT is_system_owner FROM dbo.core_users WHERE user_id=? AND lifecycle_state=N'ACTIVE' AND is_login_enabled=1", [$userId]);
    if ($row !== null && ((int)($row['is_system_owner'] ?? 0) === 1)) {
        return true;
    }
    // Secondary: role key owner (DB only — avoid session side effects in CLI/batch).
    $role = m360_am_one(
        $conn,
        "SELECT TOP 1 1 x FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id=ur.role_id AND r.is_active=1
         WHERE ur.user_id=? AND ur.revoked_at IS NULL
           AND LOWER(r.role_key) IN (N'owner', N'system_owner')",
        [$userId]
    );
    return $role !== null;
}

function m360_am_can_manage_matrix($conn, int $userId): bool
{
    if (m360_am_is_owner($conn, $userId)) {
        return true;
    }
    return m360_am_effective_can($conn, $userId, 1, 'access.matrix.manage');
}

function m360_am_can_apply_direct($conn, int $userId): bool
{
    if (m360_am_is_owner($conn, $userId)) {
        return true;
    }
    return m360_am_effective_can($conn, $userId, 1, 'access.matrix.apply_direct');
}

/**
 * Effective permission:
 * 1) mandatory base self-service ALLOW
 * 2) active DENY override
 * 3) active ALLOW override
 * 4) role-derived
 * 5) Owner bypass for non-owner-only keys still goes through is_owner for admin ops — Owner gets all except we still never remove base.
 *
 * @return array{allowed:bool,source:string}
 */
function m360_am_resolve($conn, int $userId, int $companyId, string $permissionKey): array
{
    $permissionKey = trim($permissionKey);
    if ($userId < 1 || $permissionKey === '') {
        return ['allowed' => false, 'source' => 'none'];
    }

    $base = m360_access_matrix_base_permission_keys();
    if (in_array($permissionKey, $base, true)) {
        $emp = m360_am_one(
            $conn,
            "SELECT TOP 1 e.employee_id
             FROM dbo.p360_employees e
             INNER JOIN dbo.core_users u ON u.user_id=e.core_user_id
             INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
             WHERE e.core_user_id=? AND u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1
               AND ISNULL(e.is_active,1)=1
               AND UPPER(ISNULL(e.lifecycle_state,N'ACTIVE')) NOT IN (N'EXITED', N'TERMINATED', N'INACTIVE')",
            [$userId]
        );
        if ($emp !== null) {
            return ['allowed' => true, 'source' => 'base'];
        }
    }

    if (m360_am_is_owner($conn, $userId)) {
        return ['allowed' => true, 'source' => 'owner'];
    }

    $perm = m360_am_one($conn, 'SELECT permission_id, is_owner_only FROM dbo.core_permissions WHERE permission_key=? AND is_active=1', [$permissionKey]);
    if ($perm === null) {
        // Legacy role check without calling erp_auth_can (avoids recursion with erp_auth_can → m360_am_effective_can).
        $legacy = m360_am_one(
            $conn,
            "SELECT TOP 1 1 x
             FROM dbo.core_user_roles ur
             INNER JOIN dbo.core_role_permissions rp ON rp.role_id=ur.role_id
             INNER JOIN dbo.core_permissions p ON p.permission_id=rp.permission_id AND p.is_active=1
             INNER JOIN dbo.core_roles r ON r.role_id=ur.role_id AND r.is_active=1
             WHERE ur.user_id=? AND p.permission_key=? AND ur.revoked_at IS NULL
               AND (ur.effective_from IS NULL OR ur.effective_from<=SYSUTCDATETIME())
               AND (ur.expires_at IS NULL OR ur.expires_at>=SYSUTCDATETIME())",
            [$userId, $permissionKey]
        );
        return $legacy !== null
            ? ['allowed' => true, 'source' => 'role']
            : ['allowed' => false, 'source' => 'none'];
    }
    if ((int)($perm['is_owner_only'] ?? 0) === 1) {
        return ['allowed' => false, 'source' => 'none'];
    }

    $pid = (int)$perm['permission_id'];
    $ov = m360_am_one(
        $conn,
        "SELECT TOP 1 effect FROM dbo.core_user_permission_overrides
         WHERE company_id=? AND user_id=? AND permission_id=? AND revoked_at IS NULL
           AND (effective_from IS NULL OR effective_from<=SYSUTCDATETIME())
           AND (effective_to IS NULL OR effective_to>=SYSUTCDATETIME())
         ORDER BY override_id DESC",
        [$companyId, $userId, $pid]
    );
    if ($ov !== null) {
        $effect = strtoupper((string)$ov['effect']);
        if ($effect === 'DENY') {
            return ['allowed' => false, 'source' => 'direct_deny'];
        }
        if ($effect === 'ALLOW') {
            return ['allowed' => true, 'source' => 'direct_allow'];
        }
    }

    $roleHit = m360_am_one(
        $conn,
        "SELECT TOP 1 1 x
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_role_permissions rp ON rp.role_id=ur.role_id
         INNER JOIN dbo.core_roles r ON r.role_id=ur.role_id AND r.is_active=1
         WHERE ur.user_id=? AND rp.permission_id=? AND ur.revoked_at IS NULL
           AND (ur.effective_from IS NULL OR ur.effective_from<=SYSUTCDATETIME())
           AND (ur.expires_at IS NULL OR ur.expires_at>=SYSUTCDATETIME())",
        [$userId, $pid]
    );
    if ($roleHit !== null) {
        return ['allowed' => true, 'source' => 'role'];
    }

    return ['allowed' => false, 'source' => 'none'];
}

function m360_am_effective_can($conn, int $userId, int $companyId, string $permissionKey): bool
{
    return !empty(m360_am_resolve($conn, $userId, $companyId, $permissionKey)['allowed']);
}

/** @return list<array<string,mixed>> */
function m360_am_personnel_rows($conn, array $filters = []): array
{
    $q = trim((string)($filters['q'] ?? ''));
    $unit = trim((string)($filters['unit'] ?? ''));
    $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.display_name_override,
                   e.unit_name, e.job_title, e.lifecycle_state, e.personnel_photo_path,
                   u.user_id, u.username, u.is_login_enabled, u.is_system_owner, u.lifecycle_state AS user_lifecycle,
                   cu.role_code, cu.company_id, cu.is_active AS company_active
            FROM dbo.p360_employees e
            INNER JOIN dbo.core_users u ON u.user_id=e.core_user_id
            INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
            WHERE e.employee_code LIKE N'M360-%'
              AND u.lifecycle_state=N'ACTIVE'";
    $params = [];
    if ($q !== '') {
        $sql .= ' AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR u.username LIKE ?)';
        $like = '%' . $q . '%';
        $params = [$like, $like, $like, $like];
    }
    if ($unit !== '') {
        $sql .= ' AND e.unit_name LIKE ?';
        $params[] = '%' . $unit . '%';
    }
    $sql .= ' ORDER BY e.first_name, e.last_name';
    $rows = m360_am_fetch_all($conn, $sql, $params);
    foreach ($rows as &$r) {
        $fn = trim((string)($r['display_name_override'] ?? ''));
        if ($fn === '') {
            $fn = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
        }
        $r['full_name'] = $fn;
        $roles = m360_am_fetch_all(
            $conn,
            "SELECT r.role_key, r.role_name FROM dbo.core_user_roles ur
             INNER JOIN dbo.core_roles r ON r.role_id=ur.role_id
             WHERE ur.user_id=? AND ur.revoked_at IS NULL AND r.is_active=1",
            [(int)$r['user_id']]
        );
        $r['roles'] = $roles;
        $roleKeys = [];
        $roleLabelParts = [];
        foreach ($roles as $roleRow) {
            $roleKeys[] = strtolower(trim((string)($roleRow['role_key'] ?? '')));
            $roleLabelParts[] = m360_am_core_role_label_fa($roleRow);
        }
        $roleLabels = implode('، ', array_filter($roleLabelParts, static fn($x) => trim((string)$x) !== '' && (string)$x !== '—'));
        $r['role_labels'] = $roleLabels;

        $isOwnerFlag = (int)($r['is_system_owner'] ?? 0) === 1 || in_array('owner', $roleKeys, true);
        $companyCode = strtoupper(trim((string)($r['role_code'] ?? '')));
        $companyLabel = m360_am_company_role_label_fa($companyCode);

        if ($isOwnerFlag) {
            // Owner authority — never substitute سرمایه انسانی for Owner/Admin.
            $r['employment_label_fa'] = '';
            $r['roles_line_fa'] = $roleLabels;
            $r['personnel_type_fa'] = $roleLabels !== '' ? $roleLabels : 'مالک سیستم';
        } else {
            $employment = ($companyCode === 'EMPLOYEE' || $companyCode === '')
                ? 'سرمایه انسانی'
                : $companyLabel;
            // Keep human-capital status distinct from security/ops roles.
            if ($employment === 'سرمایه انسانی') {
                $r['employment_label_fa'] = 'سرمایه انسانی';
                $r['roles_line_fa'] = $roleLabels;
                $r['personnel_type_fa'] = 'سرمایه انسانی';
            } else {
                $r['employment_label_fa'] = '';
                $r['roles_line_fa'] = $roleLabels !== '' ? $roleLabels : $employment;
                $r['personnel_type_fa'] = $r['roles_line_fa'];
            }
        }
    }
    unset($r);
    return $rows;
}

/** @return array<string,mixed> */
function m360_am_matrix_state_for_user($conn, int $userId, int $companyId, string $moduleKey = ''): array
{
    $sql = 'SELECT permission_id, permission_key, module_key, page_key, action_key, permission_label,
                   description_fa, is_base_self_service, contains_financial_data, contains_private_hr_data,
                   requires_maker_checker, is_owner_only, risk_level, sort_order,
                   group_key, group_title_fa, enforcement_state, is_assignable
            FROM dbo.core_permissions WHERE is_active=1';
    $params = [];
    if ($moduleKey !== '') {
        $sql .= ' AND module_key=?';
        $params[] = $moduleKey;
    }
    $sql .= ' ORDER BY sort_order, permission_key';
    $perms = m360_am_fetch_all($conn, $sql, $params);
    $out = [];
    foreach ($perms as $p) {
        $key = (string)$p['permission_key'];
        $res = m360_am_resolve($conn, $userId, $companyId, $key);
        $enforcement = strtoupper((string)($p['enforcement_state'] ?? 'NOT_YET_ENFORCED'));
        $assignable = (int)($p['is_assignable'] ?? 0) === 1 && $enforcement === 'ENFORCED';
        $out[] = [
            'permission_id' => (int)$p['permission_id'],
            'permission_key' => $key,
            'module_key' => (string)$p['module_key'],
            'page_key' => (string)($p['page_key'] ?? ''),
            'action_key' => (string)$p['action_key'],
            'title_fa' => (string)$p['permission_label'],
            'description_fa' => (string)($p['description_fa'] ?? ''),
            'group_key' => (string)($p['group_key'] ?? ''),
            'group_title_fa' => (string)($p['group_title_fa'] ?? ''),
            'enforcement_state' => $enforcement,
            'is_assignable' => $assignable,
            'is_base' => (int)($p['is_base_self_service'] ?? 0) === 1,
            'is_owner_only' => (int)($p['is_owner_only'] ?? 0) === 1,
            'financial' => (int)($p['contains_financial_data'] ?? 0) === 1,
            'hr_private' => (int)($p['contains_private_hr_data'] ?? 0) === 1,
            'allowed' => !empty($res['allowed']),
            'source' => (string)$res['source'],
        ];
    }
    return $out;
}

function m360_am_permission_id($conn, string $key): int
{
    $r = m360_am_one($conn, 'SELECT permission_id FROM dbo.core_permissions WHERE permission_key=?', [$key]);
    return (int)($r['permission_id'] ?? 0);
}

function m360_am_audit($conn, int $targetUserId, string $changeType, array $before, array $after, int $actorId, ?int $requestId, string $reason): void
{
    m360_am_exec(
        $conn,
        'INSERT INTO dbo.core_access_change_history
            (user_id, request_id, change_type, before_json, after_json, changed_by_user_id, changed_at)
         VALUES (?,?,?,?,?,?,SYSUTCDATETIME())',
        [
            $targetUserId,
            $requestId,
            $changeType,
            json_encode($before, JSON_UNESCAPED_UNICODE),
            json_encode(array_merge($after, ['reason' => $reason]), JSON_UNESCAPED_UNICODE),
            $actorId,
        ]
    );
}

/**
 * @param 'ALLOW'|'DENY'|'CLEAR' $effect
 */
function m360_am_apply_override($conn, int $companyId, int $targetUserId, string $permissionKey, string $effect, string $reason, int $actorId, bool $immediate): array
{
    $reason = trim($reason);
    if ($reason === '' || mb_strlen($reason) < 3) {
        return ['ok' => false, 'message' => 'علت تغییر دسترسی الزامی است.'];
    }
    if (in_array($permissionKey, m360_access_matrix_base_permission_keys(), true) && $effect !== 'ALLOW') {
        return ['ok' => false, 'message' => 'دسترسی پایه پرسنلی قابل حذف نیست.'];
    }
    $pid = m360_am_permission_id($conn, $permissionKey);
    if ($pid < 1) {
        return ['ok' => false, 'message' => 'مجوز یافت نشد.'];
    }
    $meta = m360_am_one($conn, 'SELECT is_owner_only, is_assignable, enforcement_state, permission_label FROM dbo.core_permissions WHERE permission_id=?', [$pid]);
    if ($meta && (int)($meta['is_owner_only'] ?? 0) === 1 && !m360_am_is_owner($conn, $actorId)) {
        return ['ok' => false, 'message' => 'این مجوز فقط توسط مالک قابل واگذاری است.'];
    }
    $enf = strtoupper((string)($meta['enforcement_state'] ?? ''));
    if ($enf !== 'ENFORCED' || (int)($meta['is_assignable'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'این مجوز هنوز قابل تخصیص نیست (فقط مجوزهای ENFORCED قابل تخصیص هستند).'];
    }
    if (in_array($permissionKey, ['access.matrix.manage', 'access.matrix.apply_direct', 'admin.roles.PERMISSION_MANAGE'], true)
        && $effect === 'ALLOW' && $targetUserId === $actorId && !m360_am_is_owner($conn, $actorId)) {
        return ['ok' => false, 'message' => 'واگذاری خودمجوز مدیریت دسترسی مجاز نیست.'];
    }

    $before = m360_am_resolve($conn, $targetUserId, $companyId, $permissionKey);

    if (!$immediate) {
        m360_am_exec(
            $conn,
            "INSERT INTO dbo.core_access_matrix_pending
                (company_id, target_user_id, permission_id, desired_effect, status, reason, requested_by_user_id)
             VALUES (?,?,?,?,N'SUBMITTED',?,?)",
            [$companyId, $targetUserId, $pid, $effect, $reason, $actorId]
        );
        return ['ok' => true, 'message' => 'پیشنهاد دسترسی ثبت شد و در انتظار تأیید است.', 'pending' => true];
    }

    // Revoke existing active override first (preserve history by setting revoked_at)
    m360_am_exec(
        $conn,
        'UPDATE dbo.core_user_permission_overrides SET revoked_at=SYSUTCDATETIME(), revoked_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE company_id=? AND user_id=? AND permission_id=? AND revoked_at IS NULL',
        [$actorId, $companyId, $targetUserId, $pid]
    );

    if ($effect === 'ALLOW' || $effect === 'DENY') {
        m360_am_exec(
            $conn,
            'INSERT INTO dbo.core_user_permission_overrides
                (company_id, user_id, permission_id, effect, reason, requested_by_user_id, approved_by_user_id)
             VALUES (?,?,?,?,?,?,?)',
            [$companyId, $targetUserId, $pid, $effect, $reason, $actorId, $actorId]
        );
    }

    $after = m360_am_resolve($conn, $targetUserId, $companyId, $permissionKey);
    m360_am_audit($conn, $targetUserId, 'PERMISSION_OVERRIDE', $before, [
        'permission_key' => $permissionKey,
        'effect' => $effect,
        'source' => $after['source'],
        'allowed' => $after['allowed'],
    ], $actorId, null, $reason);

    return ['ok' => true, 'message' => 'دسترسی اعمال شد.', 'pending' => false, 'effective' => $after];
}

function m360_am_review_pending($conn, int $pendingId, string $decision, string $note, int $actorId): array
{
    $row = m360_am_one($conn, "SELECT * FROM dbo.core_access_matrix_pending WHERE pending_id=? AND status=N'SUBMITTED'", [$pendingId]);
    if ($row === null) {
        return ['ok' => false, 'message' => 'پیشنهاد فعال یافت نشد.'];
    }
    $decision = strtoupper($decision);
    if ($decision === 'REJECT') {
        m360_am_exec($conn, "UPDATE dbo.core_access_matrix_pending SET status=N'REJECTED', reviewed_by_user_id=?, review_note=?, reviewed_at=SYSUTCDATETIME() WHERE pending_id=?", [$actorId, $note !== '' ? $note : null, $pendingId]);
        return ['ok' => true, 'message' => 'پیشنهاد رد شد.'];
    }
    if ($decision === 'RETURN') {
        m360_am_exec($conn, "UPDATE dbo.core_access_matrix_pending SET status=N'RETURNED', reviewed_by_user_id=?, review_note=?, reviewed_at=SYSUTCDATETIME() WHERE pending_id=?", [$actorId, $note !== '' ? $note : null, $pendingId]);
        return ['ok' => true, 'message' => 'پیشنهاد برای اصلاح برگشت داده شد.'];
    }
    if ($decision !== 'APPROVE') {
        return ['ok' => false, 'message' => 'تصمیم نامعتبر است.'];
    }
    $perm = m360_am_one($conn, 'SELECT permission_key FROM dbo.core_permissions WHERE permission_id=?', [(int)$row['permission_id']]);
    $key = (string)($perm['permission_key'] ?? '');
    $res = m360_am_apply_override($conn, (int)$row['company_id'], (int)$row['target_user_id'], $key, (string)$row['desired_effect'], (string)$row['reason'], $actorId, true);
    if (empty($res['ok'])) {
        return $res;
    }
    m360_am_exec($conn, "UPDATE dbo.core_access_matrix_pending SET status=N'APPLIED', reviewed_by_user_id=?, review_note=?, reviewed_at=SYSUTCDATETIME(), applied_at=SYSUTCDATETIME() WHERE pending_id=?", [$actorId, $note !== '' ? $note : null, $pendingId]);
    return ['ok' => true, 'message' => 'پیشنهاد تأیید و اعمال شد.'];
}

function m360_am_route_permission($conn, string $scriptBasename): ?string
{
    $scriptBasename = str_replace('\\', '/', $scriptBasename);
    $scriptBasename = ltrim($scriptBasename, '/');
    // Try exact and peopleos360/ prefix variants
    $candidates = [$scriptBasename, basename($scriptBasename)];
    if (str_contains($scriptBasename, 'peopleos360/')) {
        $candidates[] = 'peopleos360/' . basename($scriptBasename);
    }
    foreach ($candidates as $c) {
        $row = m360_am_one($conn, 'SELECT TOP 1 permission_key FROM dbo.core_access_route_map WHERE is_active=1 AND route_pattern=? ORDER BY map_id', [$c]);
        if ($row) {
            return (string)$row['permission_key'];
        }
    }
    return null;
}

function m360_am_forbidden(string $message = 'شما مجوز مشاهده این صفحه یا انجام این عملیات را ندارید.'): void
{
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>دسترسی غیرمجاز</title>';
    echo '<link rel="stylesheet" href="assets/css/m360-suite-theme.css">';
    echo '<link rel="stylesheet" href="assets/css/m360-access-matrix.css"></head><body>';
    echo '<div class="m360-am-error"><div class="m360-am-error-card">';
    echo '<h1>دسترسی غیرمجاز</h1><p>' . m360_am_h($message) . '</p>';
    echo '<a class="m360-btn m360-btn-primary" href="erp-staff-home.php">بازگشت</a>';
    echo '</div></div></body></html>';
    exit;
}

/**
 * Enforce mapped route permission for Central ERP pages.
 * Requires authenticated user + active positive company membership (non-Owner).
 */
function m360_am_require_route_permission(?string $permissionKey = null): void
{
    if (!function_exists('erp_auth_require_login')) {
        customer_core_require_helper('erp-auth-context.php');
    }
    erp_auth_require_login();
    $uid = (int)(erp_auth_current_user_id() ?? 0);
    $conn = m360_am_db();
    if ($conn === false || $uid < 1) {
        m360_am_forbidden();
    }
    if (m360_am_is_owner($conn, $uid)) {
        return;
    }
    $key = $permissionKey;
    if ($key === null || $key === '') {
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $rel = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if (str_contains($rel, 'peopleos360/')) {
            $script = 'peopleos360/' . basename($rel);
        }
        $key = m360_am_route_permission($conn, $script);
    }
    if ($key === null || $key === '') {
        // Unmapped operational page: deny non-owner until catalogued (safe default for guarded pages only)
        return;
    }
    $mem = m360_am_one(
        $conn,
        'SELECT TOP 1 company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1 AND company_id > 0 ORDER BY company_id',
        [$uid]
    );
    $companyId = (int)($mem['company_id'] ?? 0);
    if ($companyId < 1) {
        m360_am_forbidden('عضویت فعال شرکت برای این کاربر یافت نشد.');
    }
    if (!m360_am_effective_can($conn, $uid, $companyId, $key)) {
        m360_am_forbidden();
    }
}
