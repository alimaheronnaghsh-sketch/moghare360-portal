<?php
/**
 * MOGHARE360 ERP Audit Helper
 *
 * Phase 1A safe audit write helper.
 * Safe output only. No raw errors displayed.
 */

declare(strict_types=1);

require_once __DIR__ . '/erp-config-loader.php';
require_once __DIR__ . '/erp-auth-helper.php';

function erp_audit_safe_actor(): array
{
    erp_auth_start_session();

    if (!erp_auth_is_logged_in()) {
        return [
            'actor_user_id' => null,
            'actor_username' => null,
        ];
    }

    $currentUser = erp_auth_current_user();

    return [
        'actor_user_id' => isset($currentUser['user_id']) ? (int) $currentUser['user_id'] : null,
        'actor_username' => isset($currentUser['username'])
            ? erp_audit_sanitize_string((string) $currentUser['username'], 160)
            : null,
    ];
}

function erp_audit_client_context(): array
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    return [
        'ip_address' => erp_audit_sanitize_string(is_string($ipAddress) ? $ipAddress : null, 90),
        'user_agent' => erp_audit_sanitize_string(is_string($userAgent) ? $userAgent : null, 1000),
    ];
}

function erp_audit_sanitize_string(?string $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    $clean = trim($value);

    if ($clean === '') {
        return null;
    }

    if ($maxLength > 0 && mb_strlen($clean, 'UTF-8') > $maxLength) {
        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }

    return $clean;
}

function erp_audit_safe_json(array $details): ?string
{
    $blockedKeys = [
        'password',
        'password_hash',
        'hash',
        'erp_session_token',
        'session_token',
        'token',
        'database_password',
        'db_password',
        'config_secret',
        'connection_string',
        'sql_error',
        'sqlstate',
        'stack_trace',
        'trace',
        'private_config_path',
    ];

    $safe = [];

    foreach ($details as $key => $value) {
        $safeKey = strtolower(trim((string) $key));

        if ($safeKey === '' || in_array($safeKey, $blockedKeys, true)) {
            continue;
        }

        if (is_scalar($value) || $value === null) {
            $safe[$safeKey] = $value;
            continue;
        }

        if (is_array($value)) {
            $safe[$safeKey] = '[array]';
            continue;
        }

        $safe[$safeKey] = '[object]';
    }

    if ($safe === []) {
        return null;
    }

    $json = json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($json) || $json === '') {
        return null;
    }

    return $json;
}

function erp_audit_connection()
{
    $config = erp_load_config();
    $db = $config['database'] ?? [];

    $driver = $db['driver'] ?? '';
    $server = $db['server'] ?? '';
    $database = $db['name'] ?? '';
    $trustedConnection = (bool) ($db['trusted_connection'] ?? false);

    if ($driver !== 'odbc') {
        return false;
    }

    $connectionString = 'Driver={ODBC Driver 17 for SQL Server};Server=' . $server . ';Database=' . $database . ';';

    if ($trustedConnection) {
        $connectionString .= 'Trusted_Connection=yes;';
        return @odbc_connect($connectionString, '', '');
    }

    $username = (string) ($db['username'] ?? '');
    $password = (string) ($db['password'] ?? '');

    return @odbc_connect($connectionString, $username, $password);
}

function erp_audit_write(array $event): bool
{
    $action = erp_audit_sanitize_string((string) ($event['action'] ?? ''), 160);

    if ($action === null) {
        return false;
    }

    $actor = erp_audit_safe_actor();
    $client = erp_audit_client_context();

    $actorUserId = isset($event['actor_user_id'])
        ? (int) $event['actor_user_id']
        : $actor['actor_user_id'];

    $entityType = erp_audit_sanitize_string(isset($event['entity_type']) ? (string) $event['entity_type'] : null, 100);
    $entityId = isset($event['entity_id']) && $event['entity_id'] !== null ? (int) $event['entity_id'] : null;
    $requestId = isset($event['request_id']) && $event['request_id'] !== null ? (int) $event['request_id'] : null;
    $subjectUserId = isset($event['subject_user_id']) && $event['subject_user_id'] !== null ? (int) $event['subject_user_id'] : null;

    $details = is_array($event['details'] ?? null) ? $event['details'] : [];
    $details['actor_username'] = $actor['actor_username'];
    $detailsJson = erp_audit_safe_json($details);

    $ipAddress = erp_audit_sanitize_string(
        isset($event['ip_address']) ? (string) $event['ip_address'] : $client['ip_address'],
        90
    );

    $userAgent = erp_audit_sanitize_string(
        isset($event['user_agent']) ? (string) $event['user_agent'] : $client['user_agent'],
        1000
    );

    $isEmergency = !empty($event['is_emergency']) ? 1 : 0;

    $connection = erp_audit_connection();

    if (!$connection) {
        return false;
    }

    $sql = "
        INSERT INTO dbo.core_audit_logs
        (
            actor_user_id,
            action,
            entity_type,
            entity_id,
            request_id,
            subject_user_id,
            details_json,
            ip_address,
            user_agent,
            is_emergency,
            created_at
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSDATETIME())
    ";

    $params = [
        $actorUserId,
        $action,
        $entityType,
        $entityId,
        $requestId,
        $subjectUserId,
        $detailsJson,
        $ipAddress,
        $userAgent,
        $isEmergency,
    ];

    $statement = @odbc_prepare($connection, $sql);

    if (!$statement) {
        @odbc_close($connection);
        return false;
    }

    $result = @odbc_execute($statement, $params);

    @odbc_close($connection);

    return $result === true;
}

function erp_audit_login_success(int $userId, string $username): bool
{
    return erp_audit_write([
        'actor_user_id' => $userId,
        'action' => 'ERP_LOGIN_SUCCESS',
        'entity_type' => 'ERP_USER',
        'entity_id' => $userId,
        'details' => [
            'username' => erp_audit_sanitize_string($username, 160),
        ],
    ]);
}

function erp_audit_login_failure(string $username): bool
{
    return erp_audit_write([
        'action' => 'ERP_LOGIN_FAILURE',
        'entity_type' => 'ERP_LOGIN',
        'details' => [
            'username' => erp_audit_sanitize_string($username, 160),
        ],
    ]);
}

function erp_audit_logout(): bool
{
    $actor = erp_audit_safe_actor();

    return erp_audit_write([
        'actor_user_id' => $actor['actor_user_id'],
        'action' => 'ERP_LOGOUT',
        'entity_type' => 'ERP_SESSION',
        'details' => [
            'username' => $actor['actor_username'],
        ],
    ]);
}

function erp_audit_access_denied(string $eventType): bool
{
    $action = erp_audit_sanitize_string($eventType, 160);

    if ($action === null) {
        $action = 'ERP_ACCESS_DENIED';
    }

    return erp_audit_write([
        'action' => $action,
        'entity_type' => 'ERP_ACCESS',
        'details' => [
            'result' => 'denied',
        ],
    ]);
}
