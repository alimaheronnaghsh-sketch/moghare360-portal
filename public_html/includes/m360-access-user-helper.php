<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-management-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-audit-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-role-helper.php';

function m360_access_user_next_id($conn): int
{
    if ($conn === false) {
        return M360_ACCESS_MGMT_MIN_STAFF_USER_ID;
    }

    $maxStaff = customer_core_scalar(
        $conn,
        'SELECT MAX(user_id) FROM dbo.core_users WHERE user_id >= ?',
        [M360_ACCESS_MGMT_MIN_STAFF_USER_ID]
    );

    if ($maxStaff !== null && (int)$maxStaff >= M360_ACCESS_MGMT_MIN_STAFF_USER_ID) {
        return (int)$maxStaff + 1;
    }

    return M360_ACCESS_MGMT_MIN_STAFF_USER_ID;
}

function m360_access_user_username_exists($conn, string $username, ?int $excludeUserId = null): bool
{
    if ($conn === false || trim($username) === '') {
        return false;
    }

    if ($excludeUserId !== null && $excludeUserId > 0) {
        $count = customer_core_scalar(
            $conn,
            'SELECT COUNT(*) FROM dbo.core_users WHERE username = ? AND user_id <> ?',
            [trim($username), $excludeUserId]
        );
    } else {
        $count = customer_core_scalar(
            $conn,
            'SELECT COUNT(*) FROM dbo.core_users WHERE username = ?',
            [trim($username)]
        );
    }

    return (int)($count ?? '0') > 0;
}

function m360_access_user_validate_department($conn, int $departmentId): bool
{
    if ($conn === false || $departmentId <= 0) {
        return $departmentId <= 0;
    }

    if (!customer_core_table_exists($conn, 'core_departments')) {
        return false;
    }

    $count = customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.core_departments WHERE department_id = ? AND is_active = 1',
        [$departmentId]
    );

    return (int)($count ?? '0') > 0;
}

function m360_access_user_validate_position($conn, int $positionId, int $departmentId = 0): bool
{
    if ($conn === false || $positionId <= 0) {
        return $positionId <= 0;
    }

    if (!customer_core_table_exists($conn, 'core_positions')) {
        return false;
    }

    if ($departmentId > 0) {
        $count = customer_core_scalar(
            $conn,
            'SELECT COUNT(*) FROM dbo.core_positions WHERE position_id = ? AND department_id = ? AND is_active = 1',
            [$positionId, $departmentId]
        );
    } else {
        $count = customer_core_scalar(
            $conn,
            'SELECT COUNT(*) FROM dbo.core_positions WHERE position_id = ? AND is_active = 1',
            [$positionId]
        );
    }

    return (int)($count ?? '0') > 0;
}

function m360_access_user_hash_password(string $plainPassword): string
{
    $plainPassword = trim($plainPassword);
    if ($plainPassword === '') {
        throw new RuntimeException('Temporary password is required.');
    }

    if (strlen($plainPassword) < 8) {
        throw new RuntimeException('Temporary password must be at least 8 characters.');
    }

    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('Password hashing failed.');
    }

    return $hash;
}

function m360_access_user_generate_password(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#';
    $max = strlen($alphabet) - 1;
    $out = '';
    for ($i = 0; $i < max(8, $length); $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }

    return $out;
}

/**
 * @return list<array<string, string>>
 */
function m360_access_user_list_staff($conn): array
{
    if ($conn === false) {
        return [];
    }

    $rows = m360_access_fetch_rows(
        $conn,
        'SELECT u.user_id, u.username, u.full_name, u.email, u.mobile, u.lifecycle_state,
                u.is_login_enabled, u.is_system_owner, u.created_at,
                sp.department_id, sp.position_id,
                d.dept_key, d.dept_name, p.position_key, p.position_name
         FROM dbo.core_users u
         LEFT JOIN dbo.core_staff_profiles sp ON sp.user_id = u.user_id
         LEFT JOIN dbo.core_departments d ON d.department_id = sp.department_id
         LEFT JOIN dbo.core_positions p ON p.position_id = sp.position_id
         ORDER BY u.is_system_owner DESC, u.user_id ASC'
    );

    foreach ($rows as &$row) {
        $uid = (int)($row['user_id'] ?? 0);
        $roleRows = m360_access_fetch_rows(
            $conn,
            'SELECT r.role_key FROM dbo.core_user_roles ur INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id WHERE ur.user_id = ? AND ur.revoked_at IS NULL ORDER BY r.sort_order',
            [$uid]
        );
        $keys = array_map(static fn(array $r): string => (string)($r['role_key'] ?? ''), $roleRows);
        $row['active_role_keys'] = implode(', ', array_filter($keys));
    }
    unset($row);

    return $rows;
}

function m360_access_user_get($conn, int $userId): ?array
{
    if ($conn === false || $userId <= 0) {
        return null;
    }

    $rows = m360_access_fetch_rows(
        $conn,
        'SELECT u.user_id, u.username, u.full_name, u.email, u.mobile, u.lifecycle_state,
                u.is_login_enabled, u.is_system_owner, u.created_at, u.updated_at,
                sp.profile_id, sp.department_id, sp.position_id, sp.notes,
                d.dept_key, d.dept_name, p.position_key, p.position_name
         FROM dbo.core_users u
         LEFT JOIN dbo.core_staff_profiles sp ON sp.user_id = u.user_id
         LEFT JOIN dbo.core_departments d ON d.department_id = sp.department_id
         LEFT JOIN dbo.core_positions p ON p.position_id = sp.position_id
         WHERE u.user_id = ?',
        [$userId]
    );

    return $rows[0] ?? null;
}

/**
 * @return list<array<string, string>>
 */
function m360_access_user_departments($conn): array
{
    if ($conn === false || !customer_core_table_exists($conn, 'core_departments')) {
        return [];
    }

    return m360_access_fetch_rows(
        $conn,
        'SELECT department_id, dept_key, dept_name FROM dbo.core_departments WHERE is_active = 1 ORDER BY sort_order, dept_name'
    );
}

/**
 * @return list<array<string, string>>
 */
function m360_access_user_positions($conn, int $departmentId = 0): array
{
    if ($conn === false || !customer_core_table_exists($conn, 'core_positions')) {
        return [];
    }

    if ($departmentId > 0) {
        return m360_access_fetch_rows(
            $conn,
            'SELECT position_id, department_id, position_key, position_name FROM dbo.core_positions WHERE is_active = 1 AND department_id = ? ORDER BY sort_order, position_name',
            [$departmentId]
        );
    }

    return m360_access_fetch_rows(
        $conn,
        'SELECT position_id, department_id, position_key, position_name FROM dbo.core_positions WHERE is_active = 1 ORDER BY department_id, sort_order, position_name'
    );
}

function m360_access_user_guard_target($conn, int $actorUserId, int $targetUserId, bool $allowPrivilegedTarget = false): void
{
    $target = m360_access_user_get($conn, $targetUserId);
    if ($target === null) {
        throw new RuntimeException('User not found.');
    }

    if ((string)($target['is_system_owner'] ?? '0') === '1' && !$allowPrivilegedTarget) {
        if (!m360_access_mgmt_actor_can_manage_privileged($conn, $actorUserId)) {
            throw new RuntimeException('Only platform system owner may modify this account.');
        }
    }
}

function m360_access_user_upsert_profile($conn, int $userId, int $departmentId, int $positionId): bool
{
    if ($conn === false || !customer_core_table_exists($conn, 'core_staff_profiles')) {
        return false;
    }

    $dept = $departmentId > 0 ? $departmentId : null;
    $pos = $positionId > 0 ? $positionId : null;

    $existing = customer_core_scalar(
        $conn,
        'SELECT TOP 1 profile_id FROM dbo.core_staff_profiles WHERE user_id = ?',
        [$userId]
    );

    if ($existing !== null && (int)$existing > 0) {
        return customer_core_execute(
            $conn,
            'UPDATE dbo.core_staff_profiles SET department_id = ?, position_id = ?, updated_at = SYSUTCDATETIME() WHERE user_id = ?',
            [$dept, $pos, $userId]
        ) !== false;
    }

    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_staff_profiles (user_id, department_id, position_id, notes, created_at)
         VALUES (?, ?, ?, N\'Created via access management UI\', SYSUTCDATETIME())',
        [$userId, $dept, $pos]
    ) !== false;
}

/**
 * @param array<string, mixed> $input
 * @return array{ok:bool, user_id?:int, temporary_password?:string, message:string}
 */
function m360_access_user_create($conn, int $actorUserId, array $input): array
{
    if ($conn === false) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $username = trim((string)($input['username'] ?? ''));
    $displayName = trim((string)($input['display_name'] ?? ''));
    $mobile = trim((string)($input['mobile'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $roleCode = strtoupper(trim((string)($input['role_code'] ?? '')));
    $tempPassword = trim((string)($input['temporary_password'] ?? ''));
    $lifecycle = strtoupper(trim((string)($input['lifecycle_state'] ?? 'ACTIVE')));
    $loginEnabled = !empty($input['is_login_enabled']) ? 1 : 0;
    $departmentId = (int)($input['department_id'] ?? 0);
    $positionId = (int)($input['position_id'] ?? 0);

    if ($username === '' || $displayName === '' || $roleCode === '') {
        throw new RuntimeException('Username, display name, and role are required.');
    }

    if (m360_access_user_username_exists($conn, $username)) {
        throw new RuntimeException('Username already exists.');
    }

    $mapped = m360_access_mgmt_resolve_role_code($roleCode);
    if ($mapped === null) {
        throw new RuntimeException('Invalid role_code.');
    }

    m360_access_role_guard_privileged($mapped['role_key'], $actorUserId, $conn);

    if ($departmentId > 0 && !m360_access_user_validate_department($conn, $departmentId)) {
        throw new RuntimeException('Invalid department.');
    }

    if ($positionId > 0 && !m360_access_user_validate_position($conn, $positionId, $departmentId)) {
        throw new RuntimeException('Invalid position.');
    }

    if (!array_key_exists($lifecycle, m360_access_mgmt_lifecycle_options())) {
        throw new RuntimeException('Invalid lifecycle_state.');
    }

    $passwordHash = m360_access_user_hash_password($tempPassword);
    $userId = m360_access_user_next_id($conn);

    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_users (
            user_id, username, password_hash, full_name, email, mobile,
            lifecycle_state, is_system_owner, is_login_enabled,
            created_at, created_by_user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, SYSUTCDATETIME(), ?)',
        [
            $userId,
            $username,
            $passwordHash,
            $displayName,
            $email !== '' ? $email : null,
            $mobile !== '' ? $mobile : null,
            $lifecycle,
            $loginEnabled,
            $actorUserId,
        ]
    );

    if ($insertOk === false) {
        throw new RuntimeException('Failed to create core_users row.');
    }

    m360_access_user_upsert_profile($conn, $userId, $departmentId, $positionId);

    $companyId = m360_access_mgmt_default_company_id($conn);
    if ($companyId > 0) {
        m360_access_role_upsert_company_user($conn, $companyId, $userId, $mapped['erp_role_code']);
    }

    m360_access_role_assign($conn, $actorUserId, $userId, $mapped['role_key'], 'Initial role on staff create');

    m360_access_audit_log_operation(
        $conn,
        $actorUserId,
        $userId,
        'ONBOARDING',
        'ACCESS_MGMT_USER_CREATED',
        'ACCESS_MGMT_USER_CREATED',
        'core_users',
        $userId,
        null,
        [
            'username' => $username,
            'full_name' => $displayName,
            'role_code' => $roleCode,
            'role_key' => $mapped['role_key'],
            'lifecycle_state' => $lifecycle,
            'is_login_enabled' => $loginEnabled,
            'department_id' => $departmentId,
            'position_id' => $positionId,
        ],
        'Staff user created via access management UI'
    );

    return [
        'ok' => true,
        'user_id' => $userId,
        'message' => 'Staff user created.',
    ];
}

/**
 * @param array<string, mixed> $input
 */
function m360_access_user_update($conn, int $actorUserId, int $userId, array $input): array
{
    m360_access_user_guard_target($conn, $actorUserId, $userId);

    $before = m360_access_user_get($conn, $userId);
    if ($before === null) {
        throw new RuntimeException('User not found.');
    }

    $displayName = trim((string)($input['display_name'] ?? ($before['full_name'] ?? '')));
    $mobile = trim((string)($input['mobile'] ?? ($before['mobile'] ?? '')));
    $email = trim((string)($input['email'] ?? ($before['email'] ?? '')));
    $lifecycle = strtoupper(trim((string)($input['lifecycle_state'] ?? ($before['lifecycle_state'] ?? 'ACTIVE'))));
    $loginEnabled = !empty($input['is_login_enabled']) ? 1 : 0;
    $departmentId = (int)($input['department_id'] ?? (int)($before['department_id'] ?? 0));
    $positionId = (int)($input['position_id'] ?? (int)($before['position_id'] ?? 0));

    if ($displayName === '') {
        throw new RuntimeException('Display name is required.');
    }

    if (!array_key_exists($lifecycle, m360_access_mgmt_lifecycle_options())) {
        throw new RuntimeException('Invalid lifecycle_state.');
    }

    if ($departmentId > 0 && !m360_access_user_validate_department($conn, $departmentId)) {
        throw new RuntimeException('Invalid department.');
    }

    if ($positionId > 0 && !m360_access_user_validate_position($conn, $positionId, $departmentId)) {
        throw new RuntimeException('Invalid position.');
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.core_users SET full_name = ?, email = ?, mobile = ?, lifecycle_state = ?, is_login_enabled = ?, updated_at = SYSUTCDATETIME(), updated_by_user_id = ? WHERE user_id = ?',
        [
            $displayName,
            $email !== '' ? $email : null,
            $mobile !== '' ? $mobile : null,
            $lifecycle,
            $loginEnabled,
            $actorUserId,
            $userId,
        ]
    );

    if ($ok === false) {
        throw new RuntimeException('Profile update failed.');
    }

    m360_access_user_upsert_profile($conn, $userId, $departmentId, $positionId);

    m360_access_audit_log_operation(
        $conn,
        $actorUserId,
        $userId,
        'ACCESS_UPGRADE',
        'ACCESS_MGMT_PROFILE_UPDATED',
        'ACCESS_MGMT_PROFILE_UPDATED',
        'core_users',
        $userId,
        [
            'full_name' => (string)($before['full_name'] ?? ''),
            'lifecycle_state' => (string)($before['lifecycle_state'] ?? ''),
            'is_login_enabled' => (string)($before['is_login_enabled'] ?? ''),
        ],
        [
            'full_name' => $displayName,
            'lifecycle_state' => $lifecycle,
            'is_login_enabled' => $loginEnabled,
            'department_id' => $departmentId,
            'position_id' => $positionId,
        ],
        'Staff profile updated via access management UI'
    );

    return ['ok' => true, 'message' => 'Staff profile updated.'];
}

/**
 * @return array{ok:bool, temporary_password?:string, message:string}
 */
function m360_access_user_reset_password(
    $conn,
    int $actorUserId,
    int $userId,
    ?string $temporaryPassword = null,
    bool $generate = false
): array {
    m360_access_user_guard_target($conn, $actorUserId, $userId);

    if ($generate || $temporaryPassword === null || trim($temporaryPassword) === '') {
        $temporaryPassword = m360_access_user_generate_password();
    }

    $hash = m360_access_user_hash_password($temporaryPassword);

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.core_users SET password_hash = ?, updated_at = SYSUTCDATETIME(), updated_by_user_id = ? WHERE user_id = ?',
        [$hash, $actorUserId, $userId]
    );

    if ($ok === false) {
        throw new RuntimeException('Password reset failed.');
    }

    m360_access_audit_log_operation(
        $conn,
        $actorUserId,
        $userId,
        'EMERGENCY',
        'ACCESS_MGMT_PASSWORD_RESET',
        'ACCESS_MGMT_PASSWORD_RESET',
        'core_users',
        $userId,
        null,
        ['password_reset' => true, 'force_change_supported' => false],
        'Temporary password reset via access management UI'
    );

    return [
        'ok' => true,
        'temporary_password' => $temporaryPassword,
        'message' => 'Temporary password reset. Share once with staff; force-change on first login is not yet supported.',
    ];
}

function m360_access_user_count_non_owner_staff($conn): int
{
    if ($conn === false) {
        return 0;
    }

    $count = customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.core_users WHERE is_system_owner = 0 AND user_id >= ?',
        [M360_ACCESS_MGMT_MIN_STAFF_USER_ID]
    );

    return (int)($count ?? '0');
}

function m360_access_user_count_login_enabled_staff($conn): int
{
    if ($conn === false) {
        return 0;
    }

    $count = customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.core_users WHERE is_system_owner = 0 AND user_id >= ? AND is_login_enabled = 1 AND lifecycle_state = N\'ACTIVE\'',
        [M360_ACCESS_MGMT_MIN_STAFF_USER_ID]
    );

    return (int)($count ?? '0');
}
