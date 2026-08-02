<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-config-loader.php';

/**
 * MOGHARE360 ERP Auth Context Helper
 *
 * Mission 8 - Auth Context Helper Implementation
 *
 * SELECT-only database reads using ODBC connection resource.
 * Does not replace staff-auth.php or production login.
 * Does not perform INSERT, UPDATE, DELETE, or MERGE.
 */

/** Central ERP idle timeout (seconds). */
const ERP_AUTH_IDLE_TIMEOUT_SECONDS = 3600;

/** Central ERP absolute session lifetime (seconds). */
const ERP_AUTH_ABSOLUTE_LIFETIME_SECONDS = 43200;

/** Central ERP periodic session ID regeneration interval (seconds). */
const ERP_AUTH_REGENERATE_INTERVAL_SECONDS = 1800;

if (!function_exists('erp_auth_context_session_keys')) {
    /**
     * Canonical Central ERP session contract keys.
     *
     * @return list<string>
     */
    function erp_auth_context_session_keys(): array
    {
        return [
            'erp_user_id',
            'erp_username',
            'erp_company_id',
            'erp_login_timestamp',
            'erp_last_activity_timestamp',
            'erp_session_regenerated_at',
            'erp_is_owner',
        ];
    }
}

if (!function_exists('erp_auth_request_is_https')) {
    /**
     * Detect HTTPS without blindly trusting arbitrary X-Forwarded-Proto.
     */
    function erp_auth_request_is_https(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        $port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 0;
        if ($port === 443) {
            return true;
        }

        return false;
    }
}

if (!function_exists('erp_auth_configure_session_cookie')) {
    /**
     * Configure Central ERP PHP session cookie policy before session_start().
     */
    function erp_auth_configure_session_cookie(): void
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return;
        }

        ini_set('session.use_strict_mode', '1');

        $secure = erp_auth_request_is_https();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('erp_auth_expire_session_cookie')) {
    /**
     * Expire the active PHP session cookie using the same parameters as the live session.
     */
    function erp_auth_expire_session_cookie(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $params = session_get_cookie_params();
        $name = session_name();
        $expire = time() - 42000;
        $path = $params['path'] !== '' ? $params['path'] : '/';
        $domain = $params['domain'] ?? '';
        $secure = (bool)($params['secure'] ?? false);
        $httponly = (bool)($params['httponly'] ?? true);
        $samesite = $params['samesite'] ?? 'Lax';
        if (!is_string($samesite) || $samesite === '') {
            $samesite = 'Lax';
        }

        setcookie($name, '', [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }
}

if (!function_exists('erp_auth_destroy_central_session')) {
    /**
     * Clear Central ERP session state, expire cookie, destroy PHP session.
     * Does not clear P360SESSID / INV360SESSID / WORK360SESSID (deferred to G1.4).
     */
    function erp_auth_destroy_central_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            erp_auth_configure_session_cookie();
            @session_start();
        }

        $_SESSION = [];
        erp_auth_expire_session_cookie();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

if (!function_exists('erp_auth_deny_central_session')) {
    /**
     * Destroy Central ERP session and deny access (redirect or JSON for /api/).
     *
     * @param 'expired'|'invalid' $reason
     */
    function erp_auth_deny_central_session(string $reason = 'expired'): void
    {
        erp_auth_destroy_central_session();

        if (PHP_SAPI === 'cli') {
            throw new RuntimeException('ERP central session denied: ' . $reason);
        }

        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_contains($script, '/api/')) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
            }
            echo json_encode([
                'ok' => false,
                'message' => 'نشست منقضی شده است. لطفاً دوباره وارد شوید.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $query = $reason === 'expired' ? '?session=expired' : '';
        if (!headers_sent()) {
            header('Location: staff-login.php' . $query);
        }
        exit;
    }
}

if (!function_exists('erp_auth_session_int')) {
    function erp_auth_session_int(string $key): ?int
    {
        if (!isset($_SESSION[$key])) {
            return null;
        }

        $raw = $_SESSION[$key];
        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }
        if (is_string($raw) && ctype_digit(trim($raw))) {
            $value = (int)trim($raw);

            return $value > 0 ? $value : null;
        }

        return null;
    }
}

if (!function_exists('erp_auth_establish_login_timestamps')) {
    /**
     * Write normalized Central ERP login timestamps after successful authentication.
     * Caller must already hold a started session and call session_regenerate_id(true) first.
     */
    function erp_auth_establish_login_timestamps(): void
    {
        $now = time();
        $_SESSION['erp_login_timestamp'] = $now;
        $_SESSION['erp_last_activity_timestamp'] = $now;
        $_SESSION['erp_session_regenerated_at'] = $now;
    }
}

if (!function_exists('erp_auth_revalidate_active_membership')) {
    /**
     * Revalidate core_users + active erp_company_users for the session identity.
     * Every Central ERP user — including system owners — requires active membership
     * for the session company. SELECT-only — does not update last_login_at or any DB value.
     *
     * @param resource $db
     */
    function erp_auth_revalidate_active_membership($db, int $userId, int $companyId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        $sql = '
            SELECT TOP 1
                u.user_id,
                u.is_login_enabled,
                u.lifecycle_state,
                u.is_system_owner,
                cu.company_id AS membership_company_id,
                cu.is_active AS membership_active
            FROM dbo.core_users u
            INNER JOIN dbo.erp_company_users cu
                ON cu.user_id = u.user_id
               AND cu.company_id = ?
               AND cu.is_active = 1
            WHERE u.user_id = ?
        ';

        $statement = erp_auth_context_odbc_execute($db, $sql, [$companyId, $userId]);
        if ($statement === false) {
            return false;
        }

        $row = erp_auth_context_odbc_fetch_row_assoc($statement);
        if ($row === null) {
            return false;
        }

        if ((int)($row['user_id'] ?? 0) !== $userId) {
            return false;
        }

        if (!erp_auth_context_bool_value($row['is_login_enabled'] ?? false)) {
            return false;
        }

        if (strtoupper(trim((string)($row['lifecycle_state'] ?? ''))) !== 'ACTIVE') {
            return false;
        }

        $membershipCompanyId = (int)($row['membership_company_id'] ?? 0);
        if ($membershipCompanyId !== $companyId) {
            return false;
        }

        if (!erp_auth_context_bool_value($row['membership_active'] ?? false)) {
            return false;
        }

        // erp_is_owner is a derived convenience only — not sole authorization authority.
        $isOwner = erp_auth_context_bool_value($row['is_system_owner'] ?? false);
        $_SESSION['erp_is_owner'] = $isOwner ? 1 : 0;

        return true;
    }
}

if (!function_exists('erp_auth_enforce_central_session')) {
    /**
     * Enforce idle/absolute timeouts, user+company revalidation, activity stamp,
     * and periodic session ID regeneration for an authenticated Central ERP session.
     */
    function erp_auth_enforce_central_session(): void
    {
        static $enforcing = false;
        if ($enforcing) {
            return;
        }

        $userId = erp_auth_context_session_user_id();
        if ($userId === null) {
            return;
        }

        $enforcing = true;
        $db = null;
        try {
            $now = time();
            $loginAt = erp_auth_session_int('erp_login_timestamp');
            $lastActivity = erp_auth_session_int('erp_last_activity_timestamp');
            $regeneratedAt = erp_auth_session_int('erp_session_regenerated_at');

            // Backfill missing stamps from presentation-page sync (no DB write).
            if ($loginAt === null) {
                $loginAt = $now;
                $_SESSION['erp_login_timestamp'] = $loginAt;
            }
            if ($lastActivity === null) {
                $lastActivity = $loginAt;
                $_SESSION['erp_last_activity_timestamp'] = $lastActivity;
            }
            if ($regeneratedAt === null) {
                $regeneratedAt = $loginAt;
                $_SESSION['erp_session_regenerated_at'] = $regeneratedAt;
            }

            if (($now - $loginAt) > ERP_AUTH_ABSOLUTE_LIFETIME_SECONDS) {
                erp_auth_deny_central_session('expired');
            }

            if (($now - $lastActivity) > ERP_AUTH_IDLE_TIMEOUT_SECONDS) {
                erp_auth_deny_central_session('expired');
            }

            $companyId = erp_auth_session_int('erp_company_id');
            if ($companyId === null) {
                erp_auth_deny_central_session('invalid');
            }

            try {
                $db = erp_auth_create_local_odbc_connection();
            } catch (Throwable) {
                erp_auth_deny_central_session('invalid');
            }

            $valid = erp_auth_revalidate_active_membership($db, $userId, $companyId);

            if (!$valid) {
                erp_auth_deny_central_session('invalid');
            }

            $_SESSION['erp_last_activity_timestamp'] = $now;

            if (($now - $regeneratedAt) >= ERP_AUTH_REGENERATE_INTERVAL_SECONDS) {
                session_regenerate_id(true);
                $_SESSION['erp_session_regenerated_at'] = $now;
            }
        } finally {
            if (is_resource($db)) {
                @odbc_close($db);
            }
            $enforcing = false;
        }
    }
}

if (!function_exists('erp_auth_context_start')) {
    /**
     * @param bool $enforce When false, only bootstrap the session (login endpoints).
     */
    function erp_auth_context_start(bool $enforce = true): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            erp_auth_configure_session_cookie();
            session_start();
        }

        if ($enforce && PHP_SAPI !== 'cli' && erp_auth_context_session_user_id() !== null) {
            erp_auth_enforce_central_session();
        }
    }
}

if (!function_exists('erp_auth_context_session_user_id')) {
    function erp_auth_context_session_user_id(): ?int
    {
        return erp_auth_session_int('erp_user_id');
    }
}

if (!function_exists('erp_auth_current_user_id')) {
    function erp_auth_current_user_id(): ?int
    {
        erp_auth_context_start();

        return erp_auth_context_session_user_id();
    }
}

// Apply cookie policy as early as this helper is loaded (before other session_start calls).
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    erp_auth_configure_session_cookie();
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

        $config = erp_load_config();
        $database = $config['database'];
        $server = trim((string)$database['server']);
        $name = trim((string)$database['name']);
        $trusted = (bool)$database['trusted_connection'];
        $username = $trusted ? '' : (string)$database['username'];
        $password = $trusted ? '' : (string)$database['password'];

        if ($server === '' || $name === '') {
            throw new RuntimeException('ERP database configuration is invalid.');
        }

        $dsns = [];
        foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $driver) {
            $dsn = 'Driver={' . $driver . '};Server=' . $server . ';Database=' . $name . ';';
            if ($trusted) {
                $dsn .= 'Trusted_Connection=Yes;';
            }
            if ($driver === 'ODBC Driver 18 for SQL Server') {
                $dsn .= 'TrustServerCertificate=Yes;';
            }
            $dsns[] = $dsn;
        }

        foreach ($dsns as $dsn) {
            $connection = @odbc_connect($dsn, $username, $password);

            if ($connection !== false) {
                return $connection;
            }
        }

        throw new RuntimeException('ERP database connection failed.');
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

        $userId = erp_auth_context_session_user_id();

        if ($userId === null || $userId <= 0) {
            if (PHP_SAPI === 'cli') {
                throw new RuntimeException('ERP auth login is required.');
            }
            erp_auth_deny_central_session('invalid');
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
