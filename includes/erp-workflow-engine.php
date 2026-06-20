<?php
/**
 * MOGHARE360 ERP Workflow Engine
 *
 * Phase 1A access request state machine service layer.
 * Controlled SELECT and UPDATE only. Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/erp-config-loader.php';
require_once __DIR__ . '/erp-auth-helper.php';
require_once __DIR__ . '/erp-permission-helper.php';
require_once __DIR__ . '/erp-audit-helper.php';

class ERP_Workflow_Engine
{
    private const VALID_STATES = [
        'DRAFT',
        'SUBMITTED',
        'UNDER_REVIEW',
        'PARTIALLY_APPROVED',
        'APPROVED',
        'REJECTED',
        'CANCELLED',
        'APPLIED',
    ];

    private const PRIVILEGED_ROLES = [
        'owner',
        'system_admin',
    ];

    private const TRANSITION_MAP = [
        'DRAFT' => [
            'SUBMITTED',
            'CANCELLED',
        ],
        'SUBMITTED' => [
            'UNDER_REVIEW',
            'CANCELLED',
        ],
        'UNDER_REVIEW' => [
            'PARTIALLY_APPROVED',
            'APPROVED',
            'REJECTED',
            'CANCELLED',
        ],
        'PARTIALLY_APPROVED' => [
            'APPROVED',
            'REJECTED',
            'CANCELLED',
        ],
        'APPROVED' => [
            'APPLIED',
            'CANCELLED',
        ],
        'REJECTED' => [
            'CANCELLED',
        ],
        'CANCELLED' => [],
        'APPLIED' => [],
    ];

    public function validateTransition(string $fromState, string $toState): bool
    {
        $fromState = $this->normalizeState($fromState);
        $toState = $this->normalizeState($toState);

        if ($fromState === '' || $toState === '') {
            return false;
        }

        if (!in_array($fromState, self::VALID_STATES, true) || !in_array($toState, self::VALID_STATES, true)) {
            return false;
        }

        if ($fromState === $toState) {
            return false;
        }

        if ($toState === 'CANCELLED') {
            return $fromState !== 'APPLIED';
        }

        $allowedTargets = self::TRANSITION_MAP[$fromState] ?? [];

        return in_array($toState, $allowedTargets, true);
    }

    public function canUserTransition(int $userId, string $role, string $fromState, string $toState): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (!$this->validateTransition($fromState, $toState)) {
            return false;
        }

        $role = strtolower(trim($role));

        if ($role === '') {
            return false;
        }

        if (in_array($role, self::PRIVILEGED_ROLES, true)) {
            return true;
        }

        $fromState = $this->normalizeState($fromState);
        $toState = $this->normalizeState($toState);

        if ($fromState === 'DRAFT' && $toState === 'SUBMITTED') {
            return false;
        }

        return false;
    }

    public function getNextState(int $requestId)
    {
        if ($requestId <= 0) {
            return false;
        }

        $connection = $this->dbConnection();

        if (!$connection) {
            return false;
        }

        $sql = '
            SELECT request_state
            FROM dbo.core_access_requests
            WHERE request_id = ?
        ';

        $statement = $this->execute($connection, $sql, [$requestId]);

        if (!$statement || @odbc_fetch_row($statement) !== true) {
            @odbc_close($connection);

            return false;
        }

        $state = $this->normalizeState((string)@odbc_result($statement, 'request_state'));

        @odbc_close($connection);

        if ($state === '' || !in_array($state, self::VALID_STATES, true)) {
            return false;
        }

        return $state;
    }

    public function applyTransition(int $requestId, string $toState, int $userId, ?string $comment = null): bool
    {
        if ($requestId <= 0 || $userId <= 0) {
            return false;
        }

        $toState = $this->normalizeState($toState);

        if ($toState === '') {
            return false;
        }

        $connection = $this->dbConnection();

        if (!$connection) {
            return false;
        }

        $requestContext = $this->fetchRequestContext($connection, $requestId);

        if ($requestContext === null) {
            @odbc_close($connection);

            return false;
        }

        $fromState = $requestContext['request_state'];

        if (!$this->validateTransition($fromState, $toState)) {
            @odbc_close($connection);

            return false;
        }

        $userRoles = $this->fetchUserRoles($connection, $userId);
        $canTransition = false;

        foreach ($userRoles as $role) {
            if ($this->canUserTransition($userId, $role, $fromState, $toState)) {
                $canTransition = true;
                break;
            }
        }

        if (!$canTransition) {
            @odbc_close($connection);

            return false;
        }

        $safeComment = $this->sanitizeComment($comment);
        $beforeJson = $this->safeJson([
            'request_state' => $fromState,
            'comment' => null,
        ]);
        $afterJson = $this->safeJson([
            'request_state' => $toState,
            'comment' => $safeComment,
        ]);

        if ($beforeJson === null || $afterJson === null) {
            @odbc_close($connection);

            return false;
        }

        try {
            @odbc_autocommit($connection, false);

            $updateSql = '
                UPDATE dbo.core_access_requests
                SET
                    request_state = ?,
                    updated_at = SYSDATETIME()
                WHERE request_id = ?
                  AND request_state = ?
            ';

            $updateStatement = $this->execute($connection, $updateSql, [
                $toState,
                $requestId,
                $fromState,
            ]);

            if (!$updateStatement) {
                throw new RuntimeException('state update failed');
            }

            $historySql = '
                INSERT INTO dbo.core_access_change_history
                (
                    user_id,
                    request_id,
                    change_type,
                    entity_type,
                    entity_id,
                    before_json,
                    after_json,
                    changed_by_user_id,
                    changed_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, SYSDATETIME())
            ';

            $historyStatement = $this->execute($connection, $historySql, [
                $requestContext['subject_user_id'],
                $requestId,
                'STATE_CHANGE',
                'core_access_requests',
                $requestId,
                $beforeJson,
                $afterJson,
                $userId,
            ]);

            if (!$historyStatement) {
                throw new RuntimeException('history insert failed');
            }

            if (!@odbc_commit($connection)) {
                throw new RuntimeException('commit failed');
            }

            @odbc_autocommit($connection, true);
            @odbc_close($connection);

            erp_audit_write([
                'actor_user_id' => $userId,
                'action' => 'ERP_ACCESS_REQUEST_STATE_CHANGED',
                'entity_type' => 'core_access_requests',
                'entity_id' => $requestId,
                'request_id' => $requestId,
                'subject_user_id' => $requestContext['subject_user_id'],
                'details' => [
                    'from_state' => $fromState,
                    'to_state' => $toState,
                    'comment' => $safeComment,
                ],
            ]);

            return true;
        } catch (Throwable $exception) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);
            @odbc_close($connection);

            return false;
        }
    }

    private function normalizeState(string $state): string
    {
        return strtoupper(trim($state));
    }

    private function sanitizeComment(?string $comment): ?string
    {
        if ($comment === null) {
            return null;
        }

        $comment = trim($comment);

        if ($comment === '') {
            return null;
        }

        if (mb_strlen($comment) > 2000) {
            return mb_substr($comment, 0, 2000);
        }

        return $comment;
    }

    private function safeJson(array $payload): ?string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($json) || $json === '') {
            return null;
        }

        return $json;
    }

    private function dbConnection()
    {
        $config = erp_load_config();
        $database = $config['database'] ?? [];

        $server = (string)($database['server'] ?? '');
        $name = (string)($database['name'] ?? '');
        $trusted = (bool)($database['trusted_connection'] ?? true);
        $username = (string)($database['username'] ?? '');
        $password = (string)($database['password'] ?? '');

        if ($server === '' || $name === '') {
            return false;
        }

        $connectionString = 'Driver={ODBC Driver 17 for SQL Server};Server=' .
            $server .
            ';Database=' .
            $name .
            ';TrustServerCertificate=Yes;';

        if ($trusted) {
            $connectionString .= 'Trusted_Connection=Yes;';

            return @odbc_connect($connectionString, '', '');
        }

        return @odbc_connect($connectionString, $username, $password);
    }

    private function execute($connection, string $sql, array $params = [])
    {
        $statement = @odbc_prepare($connection, $sql);

        if (!$statement) {
            return false;
        }

        $ok = @odbc_execute($statement, $params);

        if (!$ok) {
            return false;
        }

        return $statement;
    }

    private function fetchRequestContext($connection, int $requestId): ?array
    {
        $sql = '
            SELECT
                request_state,
                subject_user_id
            FROM dbo.core_access_requests
            WHERE request_id = ?
        ';

        $statement = $this->execute($connection, $sql, [$requestId]);

        if (!$statement || @odbc_fetch_row($statement) !== true) {
            return null;
        }

        $state = $this->normalizeState((string)@odbc_result($statement, 'request_state'));
        $subjectUserId = (int)@odbc_result($statement, 'subject_user_id');

        if ($state === '' || !in_array($state, self::VALID_STATES, true) || $subjectUserId <= 0) {
            return null;
        }

        return [
            'request_state' => $state,
            'subject_user_id' => $subjectUserId,
        ];
    }

    private function fetchUserRoles($connection, int $userId): array
    {
        $sql = '
            SELECT r.role_key
            FROM dbo.core_user_roles ur
            INNER JOIN dbo.core_roles r
                ON ur.role_id = r.role_id
            WHERE ur.user_id = ?
              AND ur.revoked_at IS NULL
              AND (ur.expires_at IS NULL OR ur.expires_at > SYSDATETIME())
        ';

        $statement = $this->execute($connection, $sql, [$userId]);

        if (!$statement) {
            return [];
        }

        $roles = [];

        while (@odbc_fetch_row($statement)) {
            $roleKey = strtolower(trim((string)@odbc_result($statement, 'role_key')));

            if ($roleKey !== '') {
                $roles[] = $roleKey;
            }
        }

        return array_values(array_unique($roles));
    }
}
