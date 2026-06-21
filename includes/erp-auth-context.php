<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Auth Context Helper
 *
 * Mission 8 - Auth Context Helper Implementation
 *
 * SELECT-only database reads using ODBC connection resource.
 * Does not replace staff-auth.php or production login.
 * Does not perform INSERT, UPDATE, DELETE, or MERGE.
 */

if (!function_exists('erp_auth_context_session_keys')) {
    /**
     * @return list<string>
     */
    function erp_auth_context_session_keys(): array
    {
        return [
            'erp_user_id',
            'erp_username',
            'erp_login_timestamp',
            'erp_last_activity_timestamp',
            'erp_session_regenerated_at',
        ];
    }
}

if (!function_exists('erp_auth_context_start')) {
    function erp_auth_context_start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('erp_auth_context_session_user_id')) {
    function erp_auth_context_session_user_id(): ?int
    {
        if (!isset($_SESSION['erp_user_id'])) {
            return null;
        }

        $raw = $_SESSION['erp_user_id'];

        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }

        if (is_string($raw) && ctype_digit(trim($raw))) {
            $userId = (int)trim($raw);

            return $userId > 0 ? $userId : null;
        }

        return null;
    }
}

if (!function_exists('erp_auth_current_user_id')) {
    function erp_auth_current_user_id(): ?int
    {
        erp_auth_context_start();

        $sessionUserId = erp_auth_context_session_user_id();

        if ($sessionUserId !== null) {
            return $sessionUserId;
        }

        // CONTROLLED LOCAL TEST FALLBACK ONLY - NOT PRODUCTION AUTH
        return 10001;
    }
}

if (!function_exists('erp_auth_create_local_odbc_connection')) {
    /**
     * @return resource
     */
    function erp_auth_create_local_odbc_connection()
    {
        if (!extension_loaded('odbc')) {
            throw new RuntimeException('ODBC extension is not available.');
        }

        $dsns = [
            'Driver={ODBC Driver 17 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=Yes;',
            'Driver={ODBC Driver 18 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=Yes;TrustServerCertificate=Yes;',
        ];

        $lastError = null;

        foreach ($dsns as $dsn) {
            $connection = @odbc_connect($dsn, '', '');

            if ($connection !== false) {
                return $connection;
            }

            $lastError = 'ODBC connection failed.';
        }

        throw new RuntimeException($lastError ?? 'ODBC connection failed.');
    }
}

if (!function_exists('erp_auth_context_bool_value')) {
    function erp_auth_context_bool_value(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string)$value));

        return $normalized === '1' || $normalized === 'true';
    }
}

if (!function_exists('erp_auth_context_normalize_row')) {
    /**
     * @param array<string|int, mixed> $row
     * @return array<string, mixed>
     */
    function erp_auth_context_normalize_row(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[strtolower((string)$key)] = $value;
        }

        return $normalized;
    }
}

if (!function_exists('erp_auth_context_odbc_execute')) {
    /**
     * @param resource $connection
     * @param list<mixed> $params
     * @return resource|false
     */
    function erp_auth_context_odbc_execute($connection, string $sql, array $params = [])
    {
        $statement = @odbc_prepare($connection, $sql);

        if ($statement === false) {
            return false;
        }

        if (!@odbc_execute($statement, $params)) {
            return false;
        }

        return $statement;
    }
}

if (!function_exists('erp_auth_context_odbc_fetch_row_assoc')) {
    /**
     * @param resource $statement
     * @return array<string, mixed>|null
     */
    function erp_auth_context_odbc_fetch_row_assoc($statement): ?array
    {
        if (function_exists('odbc_fetch_array')) {
            $row = @odbc_fetch_array($statement);

            if (is_array($row)) {
                return erp_auth_context_normalize_row($row);
            }

            return null;
        }

        if (@odbc_fetch_row($statement) !== true) {
            return null;
        }

        $row = [];
        $columnCount = @odbc_num_fields($statement);

        if ($columnCount === false || $columnCount < 1) {
            return null;
        }

        for ($i = 1; $i <= $columnCount; $i++) {
            $name = @odbc_field_name($statement, $i);

            if ($name === false) {
                continue;
            }

            $value = @odbc_result($statement, $i);
            $row[strtolower((string)$name)] = $value === false ? null : $value;
        }

        return $row !== [] ? $row : null;
    }
}

if (!function_exists('erp_auth_context_odbc_fetch_all_assoc')) {
    /**
     * @param resource $statement
     * @return list<array<string, mixed>>
     */
    function erp_auth_context_odbc_fetch_all_assoc($statement): array
    {
        $rows = [];

        if (function_exists('odbc_fetch_array')) {
            while (true) {
                $row = @odbc_fetch_array($statement);

                if (!is_array($row)) {
                    break;
                }

                $rows[] = erp_auth_context_normalize_row($row);
            }

            return $rows;
        }

        while (true) {
            if (@odbc_fetch_row($statement) !== true) {
                break;
            }

            $row = [];
            $columnCount = @odbc_num_fields($statement);

            if ($columnCount === false || $columnCount < 1) {
                continue;
            }

            for ($i = 1; $i <= $columnCount; $i++) {
                $name = @odbc_field_name($statement, $i);

                if ($name === false) {
                    continue;
                }

                $value = @odbc_result($statement, $i);
                $row[strtolower((string)$name)] = $value === false ? null : $value;
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('erp_auth_context_role_keys')) {
    /**
     * @param array<string, mixed> $rolesResult
     * @return list<string>
     */
    function erp_auth_context_role_keys(array $rolesResult): array
    {
        if (isset($rolesResult['role_keys']) && is_array($rolesResult['role_keys'])) {
            return array_values(array_filter(
                $rolesResult['role_keys'],
                static fn ($roleKey): bool => is_string($roleKey) && $roleKey !== ''
            ));
        }

        return [];
    }
}

if (!function_exists('erp_auth_context_permission_keys')) {
    /**
     * @param array<string, mixed> $permissionsResult
     * @return list<string>
     */
    function erp_auth_context_permission_keys(array $permissionsResult): array
    {
        if (isset($permissionsResult['permission_keys']) && is_array($permissionsResult['permission_keys'])) {
            return array_values(array_filter(
                $permissionsResult['permission_keys'],
                static fn ($permissionKey): bool => is_string($permissionKey) && $permissionKey !== ''
            ));
        }

        return [];
    }
}

if (!function_exists('erp_auth_load_current_user')) {
    /**
     * @param resource $db
     * @return array<string, mixed>|null
     */
    function erp_auth_load_current_user($db): ?array
    {
        $userId = erp_auth_current_user_id();

        if ($userId === null || $userId <= 0) {
            return null;
        }

        $sql = '
            SELECT TOP 1
                user_id,
                username,
                full_name,
                lifecycle_state,
                is_system_owner,
                is_login_enabled
            FROM dbo.core_users
            WHERE user_id = ?
        ';

        $statement = erp_auth_context_odbc_execute($db, $sql, [$userId]);

        if ($statement === false) {
            return null;
        }

        $row = erp_auth_context_odbc_fetch_row_assoc($statement);

        if ($row === null) {
            return null;
        }

        return [
            'user_id' => (int)($row['user_id'] ?? 0),
            'username' => (string)($row['username'] ?? ''),
            'full_name' => (string)($row['full_name'] ?? ''),
            'lifecycle_state' => (string)($row['lifecycle_state'] ?? ''),
            'is_system_owner' => erp_auth_context_bool_value($row['is_system_owner'] ?? false),
            'is_login_enabled' => erp_auth_context_bool_value($row['is_login_enabled'] ?? false),
        ];
    }
}

if (!function_exists('erp_auth_current_roles')) {
    /**
     * @param resource $db
     * @return array{role_keys: list<string>, role_objects: list<array<string, mixed>>}
     */
    function erp_auth_current_roles($db, int $userId): array
    {
        if ($userId <= 0) {
            return [
                'role_keys' => [],
                'role_objects' => [],
            ];
        }

        $sql = '
            SELECT
                r.role_id,
                r.role_key,
                r.role_name,
                r.access_level,
                r.is_active,
                ur.effective_from,
                ur.expires_at,
                ur.revoked_at,
                ur.is_temporary
            FROM dbo.core_user_roles ur
            INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
            WHERE ur.user_id = ?
              AND r.is_active = 1
              AND ur.revoked_at IS NULL
              AND (ur.effective_from IS NULL OR ur.effective_from <= SYSUTCDATETIME())
              AND (ur.expires_at IS NULL OR ur.expires_at >= SYSUTCDATETIME())
            ORDER BY r.sort_order, r.role_id
        ';

        $statement = erp_auth_context_odbc_execute($db, $sql, [$userId]);

        if ($statement === false) {
            return [
                'role_keys' => [],
                'role_objects' => [],
            ];
        }

        $rows = erp_auth_context_odbc_fetch_all_assoc($statement);
        $roleKeys = [];
        $roleObjects = [];

        foreach ($rows as $row) {
            $roleKey = trim((string)($row['role_key'] ?? ''));

            $roleObjects[] = [
                'role_id' => (int)($row['role_id'] ?? 0),
                'role_key' => $roleKey,
                'role_name' => (string)($row['role_name'] ?? ''),
                'access_level' => (string)($row['access_level'] ?? ''),
                'is_active' => erp_auth_context_bool_value($row['is_active'] ?? false),
                'effective_from' => $row['effective_from'] ?? null,
                'expires_at' => $row['expires_at'] ?? null,
                'revoked_at' => $row['revoked_at'] ?? null,
                'is_temporary' => erp_auth_context_bool_value($row['is_temporary'] ?? false),
            ];

            if ($roleKey !== '') {
                $roleKeys[] = $roleKey;
            }
        }

        $roleKeys = array_values(array_unique($roleKeys));

        return [
            'role_keys' => $roleKeys,
            'role_objects' => $roleObjects,
        ];
    }
}

if (!function_exists('erp_auth_current_permissions')) {
    /**
     * @param resource $db
     * @return array{permission_keys: list<string>, permission_objects: list<array<string, mixed>>}
     */
    function erp_auth_current_permissions($db, int $userId): array
    {
        if ($userId <= 0) {
            return [
                'permission_keys' => [],
                'permission_objects' => [],
            ];
        }

        $sql = '
            SELECT DISTINCT
                p.permission_id,
                p.permission_key,
                p.module_key,
                p.action_key,
                p.permission_label,
                p.is_active
            FROM dbo.core_user_roles ur
            INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
            INNER JOIN dbo.core_role_permissions rp ON rp.role_id = r.role_id
            INNER JOIN dbo.core_permissions p ON p.permission_id = rp.permission_id
            WHERE ur.user_id = ?
              AND r.is_active = 1
              AND p.is_active = 1
              AND ur.revoked_at IS NULL
              AND (ur.effective_from IS NULL OR ur.effective_from <= SYSUTCDATETIME())
              AND (ur.expires_at IS NULL OR ur.expires_at >= SYSUTCDATETIME())
            ORDER BY p.permission_key
        ';

        $statement = erp_auth_context_odbc_execute($db, $sql, [$userId]);

        if ($statement === false) {
            return [
                'permission_keys' => [],
                'permission_objects' => [],
            ];
        }

        $rows = erp_auth_context_odbc_fetch_all_assoc($statement);
        $permissionKeys = [];
        $permissionObjects = [];
        $seenPermissionIds = [];

        foreach ($rows as $row) {
            $permissionId = (int)($row['permission_id'] ?? 0);
            $permissionKey = trim((string)($row['permission_key'] ?? ''));

            if ($permissionId > 0 && isset($seenPermissionIds[$permissionId])) {
                continue;
            }

            $permissionObjects[] = [
                'permission_id' => $permissionId,
                'permission_key' => $permissionKey,
                'module_key' => (string)($row['module_key'] ?? ''),
                'action_key' => (string)($row['action_key'] ?? ''),
                'permission_label' => (string)($row['permission_label'] ?? ''),
                'is_active' => erp_auth_context_bool_value($row['is_active'] ?? false),
            ];

            if ($permissionId > 0) {
                $seenPermissionIds[$permissionId] = true;
            }

            if ($permissionKey !== '') {
                $permissionKeys[] = $permissionKey;
            }
        }

        $permissionKeys = array_values(array_unique($permissionKeys));
        sort($permissionKeys);

        return [
            'permission_keys' => $permissionKeys,
            'permission_objects' => $permissionObjects,
        ];
    }
}

if (!function_exists('erp_auth_is_system_owner')) {
    /**
     * @param resource $db
     */
    function erp_auth_is_system_owner($db, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = erp_auth_load_current_user($db);

        if ($user !== null && (int)($user['user_id'] ?? 0) === $userId) {
            if (!empty($user['is_system_owner'])) {
                return true;
            }
        }

        $roles = erp_auth_current_roles($db, $userId);
        $roleKeys = erp_auth_context_role_keys($roles);

        return in_array('owner', $roleKeys, true);
    }
}

if (!function_exists('erp_auth_can')) {
    /**
     * @param resource $db
     */
    function erp_auth_can($db, int $userId, string $permissionKey): bool
    {
        $permissionKey = trim($permissionKey);

        if ($userId <= 0 || $permissionKey === '') {
            return false;
        }

        $permissions = erp_auth_current_permissions($db, $userId);
        $permissionKeys = erp_auth_context_permission_keys($permissions);

        return in_array($permissionKey, $permissionKeys, true);
    }
}

if (!function_exists('erp_auth_require_login')) {
    function erp_auth_require_login(): void
    {
        erp_auth_context_start();

        $userId = erp_auth_current_user_id();

        if ($userId === null || $userId <= 0) {
            throw new RuntimeException('ERP auth login is required.');
        }
    }
}

if (!function_exists('erp_auth_tenant_context')) {
    /**
     * @return array<string, mixed>
     */
    function erp_auth_tenant_context(): array
    {
        return [
            'tenant_operational' => false,
            'current_runtime' => 'moghare360',
            'future_branding' => 'moghareh360',
        ];
    }
}

if (!function_exists('erp_auth_logout_keys')) {
    /**
     * @return list<string>
     */
    function erp_auth_logout_keys(): array
    {
        return erp_auth_context_session_keys();
    }
}
