<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-management-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-audit-helper.php';

function m360_access_role_fetch_by_key($conn, string $roleKey): ?array
{
    if ($conn === false) {
        return null;
    }

    $rows = m360_access_fetch_rows(
        $conn,
        'SELECT TOP 1 role_id, role_key, role_name, access_level, is_active FROM dbo.core_roles WHERE role_key = ?',
        [strtolower(trim($roleKey))]
    );

    return $rows[0] ?? null;
}

function m360_access_role_fetch_by_id($conn, int $roleId): ?array
{
    if ($conn === false || $roleId <= 0) {
        return null;
    }

    $rows = m360_access_fetch_rows(
        $conn,
        'SELECT TOP 1 role_id, role_key, role_name, access_level, is_active FROM dbo.core_roles WHERE role_id = ?',
        [$roleId]
    );

    return $rows[0] ?? null;
}

/**
 * @return list<array<string, string>>
 */
function m360_access_role_list_assignable($conn, bool $includePrivileged = false): array
{
    if ($conn === false) {
        return [];
    }

    $rows = m360_access_fetch_rows(
        $conn,
        'SELECT role_id, role_key, role_name, access_level, is_active FROM dbo.core_roles WHERE is_active = 1 ORDER BY sort_order, role_id'
    );

    if ($includePrivileged) {
        return $rows;
    }

    return array_values(array_filter(
        $rows,
        static fn(array $row): bool => !in_array((string)($row['role_key'] ?? ''), M360_ACCESS_MGMT_PROTECTED_ROLE_KEYS, true)
    ));
}

/**
 * @return list<array<string, string>>
 */
function m360_access_role_active_for_user($conn, int $userId): array
{
    if ($conn === false || $userId <= 0) {
        return [];
    }

    return m360_access_fetch_rows(
        $conn,
        'SELECT ur.user_role_id, ur.user_id, ur.role_id, ur.granted_by_request_id, ur.effective_from, ur.expires_at,
                ur.revoked_at, ur.is_temporary, r.role_key, r.role_name
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_id = ?
         ORDER BY ur.revoked_at, r.sort_order, ur.user_role_id',
        [$userId]
    );
}

function m360_access_role_guard_privileged(string $roleKey, int $actorUserId, $conn): void
{
    if (!in_array(strtolower(trim($roleKey)), M360_ACCESS_MGMT_PROTECTED_ROLE_KEYS, true)) {
        return;
    }

    if (!m360_access_mgmt_actor_can_manage_privileged($conn, $actorUserId)) {
        throw new RuntimeException('Only platform system owner may assign owner/system_admin roles.');
    }
}

function m360_access_role_upsert_company_user($conn, int $companyId, int $userId, string $erpRoleCode): bool
{
    if ($conn === false || $companyId <= 0 || !customer_core_table_exists($conn, 'erp_company_users')) {
        return false;
    }

    $existing = customer_core_scalar(
        $conn,
        'SELECT TOP 1 company_user_id FROM dbo.erp_company_users WHERE company_id = ? AND user_id = ? ORDER BY company_user_id',
        [$companyId, $userId]
    );

    if ($existing !== null && (int)$existing > 0) {
        return customer_core_execute(
            $conn,
            'UPDATE dbo.erp_company_users SET role_code = ?, is_active = 1 WHERE company_user_id = ?',
            [strtoupper(trim($erpRoleCode)), (int)$existing]
        ) !== false;
    }

    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_company_users (company_id, user_id, role_code, is_active, created_at)
         VALUES (?, ?, ?, 1, SYSUTCDATETIME())',
        [$companyId, $userId, strtoupper(trim($erpRoleCode))]
    ) !== false;
}

function m360_access_role_fetch_user_role_id($conn, int $userId, int $roleId, int $requestId): int
{
    if ($conn === false) {
        return 0;
    }

    $value = customer_core_scalar(
        $conn,
        'SELECT TOP 1 user_role_id FROM dbo.core_user_roles
         WHERE user_id = ? AND role_id = ? AND granted_by_request_id = ?
         ORDER BY user_role_id DESC',
        [$userId, $roleId, $requestId]
    );

    return $value !== null && is_numeric($value) ? (int)$value : 0;
}

function m360_access_role_assign(
    $conn,
    int $actorUserId,
    int $userId,
    string $roleKey,
    ?string $reason = null
): array {
    if ($conn === false) {
        throw new RuntimeException('Database connection unavailable.');
    }

    m360_access_role_guard_privileged($roleKey, $actorUserId, $conn);

    $role = m360_access_role_fetch_by_key($conn, $roleKey);
    if ($role === null || (string)($role['is_active'] ?? '0') !== '1') {
        throw new RuntimeException('Role not found or inactive: ' . $roleKey);
    }

    $roleId = (int)($role['role_id'] ?? 0);
    $active = m360_access_fetch_rows(
        $conn,
        'SELECT TOP 1 user_role_id FROM dbo.core_user_roles WHERE user_id = ? AND role_id = ? AND revoked_at IS NULL',
        [$userId, $roleId]
    );

    if (($active[0] ?? null) !== null) {
        return [
            'ok' => true,
            'message' => 'Role already active.',
            'user_role_id' => (int)($active[0]['user_role_id'] ?? 0),
        ];
    }

    $justification = $reason ?? ('Access Management role grant for ' . (string)$role['role_key']);
    $requestId = m360_access_audit_ensure_request(
        $conn,
        $actorUserId,
        $userId,
        'ROLE_GRANT',
        $justification,
        'ROLE'
    );

    if ($requestId === null) {
        throw new RuntimeException(M360_ACCESS_ROLE_GRANT_REQUEST_FAILED_FA);
    }

    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_user_roles (user_id, role_id, granted_by_request_id, effective_from, is_temporary, created_at)
         VALUES (?, ?, ?, SYSUTCDATETIME(), 0, SYSUTCDATETIME())',
        [$userId, $roleId, $requestId]
    );

    if ($ok === false) {
        throw new RuntimeException('تخصیص نقش در پایگاه داده انجام نشد.');
    }

    $userRoleId = m360_access_role_fetch_user_role_id($conn, $userId, $roleId, $requestId);
    if ($userRoleId <= 0) {
        $userRoleId = customer_core_scope_identity($conn) ?? 0;
    }

    $mapped = m360_access_mgmt_resolve_role_key((string)$role['role_key']);
    if ($mapped !== null) {
        $companyId = m360_access_mgmt_default_company_id($conn);
        if ($companyId > 0) {
            m360_access_role_upsert_company_user($conn, $companyId, $userId, (string)$mapped['erp_role_code']);
        }
    }

    $afterPayload = [
        'user_id' => $userId,
        'role_id' => $roleId,
        'role_key' => (string)$role['role_key'],
        'request_id' => $requestId,
    ];

    m360_access_audit_record_change(
        $conn,
        $userId,
        $requestId,
        'ACCESS_MGMT_ROLE_GRANTED',
        'core_user_roles',
        $userRoleId > 0 ? $userRoleId : $roleId,
        $actorUserId,
        null,
        $afterPayload,
        $reason
    );

    m360_access_audit_record_event(
        $conn,
        $actorUserId,
        'ACCESS_MGMT_ROLE_GRANTED',
        $userId,
        $requestId,
        'core_user_roles',
        $userRoleId > 0 ? $userRoleId : $roleId,
        array_merge($afterPayload, ['reason' => $reason]),
        false
    );

    return ['ok' => true, 'message' => 'Role assigned.', 'user_role_id' => $userRoleId, 'request_id' => $requestId];
}

function m360_access_role_revoke(
    $conn,
    int $actorUserId,
    int $userId,
    int $userRoleId,
    ?string $reason = null
): array {
    if ($conn === false) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $row = m360_access_fetch_rows(
        $conn,
        'SELECT TOP 1 ur.user_role_id, ur.role_id, r.role_key
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_role_id = ? AND ur.user_id = ? AND ur.revoked_at IS NULL',
        [$userRoleId, $userId]
    )[0] ?? null;

    if ($row === null) {
        throw new RuntimeException('Active role assignment not found.');
    }

    m360_access_role_guard_privileged((string)($row['role_key'] ?? ''), $actorUserId, $conn);

    $justification = $reason ?? ('Access Management role revoke for ' . (string)($row['role_key'] ?? ''));
    $requestId = m360_access_audit_ensure_request(
        $conn,
        $actorUserId,
        $userId,
        'ACCESS_DOWNGRADE',
        $justification,
        'REVOKE'
    );

    if ($requestId === null) {
        throw new RuntimeException(M360_ACCESS_ROLE_REVOKE_REQUEST_FAILED_FA);
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.core_user_roles SET revoked_at = SYSUTCDATETIME(), revoked_by_request_id = ? WHERE user_role_id = ? AND user_id = ? AND revoked_at IS NULL',
        [$requestId, $userRoleId, $userId]
    );

    if ($ok === false) {
        throw new RuntimeException('Role revoke failed.');
    }

    m360_access_audit_record_change(
        $conn,
        $userId,
        $requestId,
        'ACCESS_MGMT_ROLE_REVOKED',
        'core_user_roles',
        $userRoleId,
        $actorUserId,
        ['role_key' => (string)($row['role_key'] ?? ''), 'user_role_id' => $userRoleId],
        ['revoked' => true, 'request_id' => $requestId],
        $reason
    );

    m360_access_audit_record_event(
        $conn,
        $actorUserId,
        'ACCESS_MGMT_ROLE_REVOKED',
        $userId,
        $requestId,
        'core_user_roles',
        $userRoleId,
        ['role_key' => (string)($row['role_key'] ?? ''), 'reason' => $reason, 'request_id' => $requestId],
        false
    );

    return ['ok' => true, 'message' => 'Role revoked.', 'request_id' => $requestId];
}
