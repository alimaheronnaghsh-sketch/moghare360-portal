<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Contract Authorization Workflow Helper (Wave 3B)
 *
 * Internal controlled status transitions — NOT final legal e-signature.
 * No public portal activation.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_CONTRACT_AUTH_WF_TABLE = 'erp_jobcard_authorizations';
const MOGHARE360_CONTRACT_AUTH_WF_HISTORY_TABLE = 'erp_jobcard_authorization_history';

const MOGHARE360_CONTRACT_AUTH_WF_EVENT_CODE = 'AUTHORIZATION_STATUS_CHANGED';
const MOGHARE360_CONTRACT_AUTH_WF_EVENT_TITLE = 'Authorization status changed';

const MOGHARE360_CONTRACT_AUTH_WF_INTERNAL_NOTICE =
    'This is internal controlled workflow, not final legal e-signature.';

const MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE =
    'Contract authorization DB foundation is not confirmed yet.';

/**
 * @return array<string, list<string>>
 */
function moghare360_contract_authorization_workflow_allowed_transitions(): array
{
    return [
        'draft' => ['pending_customer_approval'],
        'pending_customer_approval' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['cancelled'],
        'rejected' => ['cancelled'],
        'cancelled' => [],
    ];
}

/**
 * @return list<string>
 */
function moghare360_contract_authorization_workflow_forbidden_transition_keys(): array
{
    return [
        'approved->draft',
        'approved->rejected',
        'rejected->approved',
        'cancelled->draft',
        'cancelled->pending_customer_approval',
        'cancelled->approved',
        'cancelled->rejected',
        'pending_customer_approval->draft',
    ];
}

function moghare360_contract_authorization_workflow_status_label(string $status): string
{
    return match (strtolower(trim($status))) {
        'draft' => 'پیش‌نویس',
        'pending_customer_approval' => 'در انتظار تأیید مشتری',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
        'cancelled' => 'لغو شده',
        default => 'نامشخص',
    };
}

/**
 * @return array{ok: bool, schema_ready: bool, message: string}
 */
function moghare360_contract_authorization_workflow_schema_ready(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'schema_ready' => false,
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_WF_TABLE)) {
        return [
            'ok' => false,
            'schema_ready' => false,
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_WF_HISTORY_TABLE)) {
        return [
            'ok' => false,
            'schema_ready' => false,
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
        ];
    }

    return ['ok' => true, 'schema_ready' => true, 'message' => ''];
}

/**
 * @return array{ok: bool, record: array<string, string>|null, message: string, errors: list<string>, notes: list<string>}
 */
function moghare360_contract_authorization_workflow_get_record(int $authorizationId): array
{
    if ($authorizationId < 1) {
        return [
            'ok' => false,
            'record' => null,
            'message' => 'شناسه مجوز نامعتبر است.',
            'errors' => ['invalid_authorization_id'],
            'notes' => [],
        ];
    }

    $schema = moghare360_contract_authorization_workflow_schema_ready();

    if (!$schema['schema_ready']) {
        return [
            'ok' => false,
            'record' => null,
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
            'errors' => ['schema_blocked'],
            'notes' => [],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'record' => null,
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
            'errors' => ['db_connection_failed'],
            'notes' => [],
        ];
    }

    $where = 'WHERE authorization_id = ?';
    $params = [$authorizationId];

    if (customer_core_column_exists($connection, MOGHARE360_CONTRACT_AUTH_WF_TABLE, 'is_deleted')) {
        $where .= ' AND is_deleted = 0';
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT authorization_id, jobcard_id, authorization_type, authorization_status, authorization_method,
                customer_name, customer_mobile, authorization_note,
                CONVERT(VARCHAR(30), created_at, 120) AS created_at,
                CONVERT(VARCHAR(30), updated_at, 120) AS updated_at
         FROM dbo.erp_jobcard_authorizations
         ' . $where,
        $params
    );

    if ($rows === []) {
        return [
            'ok' => false,
            'record' => null,
            'message' => 'رکورد مجوز یافت نشد.',
            'errors' => ['authorization_not_found'],
            'notes' => [],
        ];
    }

    return [
        'ok' => true,
        'record' => $rows[0],
        'message' => '',
        'errors' => [],
        'notes' => [MOGHARE360_CONTRACT_AUTH_WF_INTERNAL_NOTICE],
    ];
}

/**
 * @param array<string, string> $record
 * @return array{ok: bool, target_status: string|null, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_contract_authorization_workflow_validate_transition(
    array $record,
    string $targetStatus,
    string $note
): array {
    $errors = [];
    $notes = [MOGHARE360_CONTRACT_AUTH_WF_INTERNAL_NOTICE];

    $currentStatus = strtolower(trim((string)($record['authorization_status'] ?? '')));
    $targetStatus = strtolower(trim($targetStatus));
    $note = trim($note);

    $allowedMap = moghare360_contract_authorization_workflow_allowed_transitions();
    $allowedTargets = $allowedMap[$currentStatus] ?? null;

    if ($allowedTargets === null) {
        $errors[] = [
            'field' => 'authorization_status',
            'rule' => 'unknown_current_status',
            'message' => 'وضعیت فعلی مجوز نامعتبر یا ناشناخته است.',
        ];

        return [
            'ok' => false,
            'target_status' => null,
            'message' => 'انتقال وضعیت مجاز نیست.',
            'errors' => $errors,
            'notes' => $notes,
        ];
    }

    $transitionKey = $currentStatus . '->' . $targetStatus;

    if (in_array($transitionKey, moghare360_contract_authorization_workflow_forbidden_transition_keys(), true)) {
        $errors[] = [
            'field' => 'target_status',
            'rule' => 'forbidden_transition',
            'message' => 'این انتقال وضعیت ممنوع است: ' . $transitionKey,
        ];
    }

    if (!in_array($targetStatus, $allowedTargets, true)) {
        $errors[] = [
            'field' => 'target_status',
            'rule' => 'transition_not_allowed',
            'message' => 'انتقال از «' . moghare360_contract_authorization_workflow_status_label($currentStatus)
                . '» به «' . moghare360_contract_authorization_workflow_status_label($targetStatus) . '» مجاز نیست.',
        ];
    }

    if ($currentStatus === $targetStatus) {
        $errors[] = [
            'field' => 'target_status',
            'rule' => 'same_status',
            'message' => 'وضعیت هدف با وضعیت فعلی یکسان است.',
        ];
    }

    if (
        in_array($currentStatus, ['approved', 'rejected'], true)
        && $targetStatus === 'cancelled'
        && $note === ''
    ) {
        $errors[] = [
            'field' => 'workflow_note',
            'rule' => 'cancellation_reason_required',
            'message' => 'برای لغو پس از تأیید یا رد، ذکر دلیل لغو الزامی است.',
        ];
    }

    if (mb_strlen($note) > 2000) {
        $errors[] = [
            'field' => 'workflow_note',
            'rule' => 'max_length',
            'message' => 'یادداشت گردش کار حداکثر ۲۰۰۰ کاراکتر مجاز است.',
        ];
    }

    if ($errors !== []) {
        return [
            'ok' => false,
            'target_status' => null,
            'message' => 'اعتبارسنجی انتقال وضعیت ناموفق بود.',
            'errors' => $errors,
            'notes' => $notes,
        ];
    }

    return [
        'ok' => true,
        'target_status' => $targetStatus,
        'message' => '',
        'errors' => [],
        'notes' => $notes,
    ];
}

/**
 * @return array{ok: bool, authorization_id: int|null, old_status: string|null, new_status: string|null, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_contract_authorization_workflow_apply(
    int $authorizationId,
    string $targetStatus,
    string $note
): array {
    $recordResult = moghare360_contract_authorization_workflow_get_record($authorizationId);

    if (!($recordResult['ok'] ?? false) || ($recordResult['record'] ?? null) === null) {
        return [
            'ok' => false,
            'authorization_id' => null,
            'old_status' => null,
            'new_status' => null,
            'message' => (string)($recordResult['message'] ?? 'خواندن رکورد مجوز ناموفق بود.'),
            'errors' => [],
            'notes' => $recordResult['notes'] ?? [],
        ];
    }

    $record = $recordResult['record'];
    $validation = moghare360_contract_authorization_workflow_validate_transition($record, $targetStatus, $note);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'authorization_id' => $authorizationId,
            'old_status' => (string)($record['authorization_status'] ?? ''),
            'new_status' => null,
            'message' => (string)$validation['message'],
            'errors' => $validation['errors'],
            'notes' => $validation['notes'],
        ];
    }

    $connection = customer_core_db();
    $oldStatus = (string)($record['authorization_status'] ?? '');
    $newStatus = (string)$validation['target_status'];
    $jobcardId = (int)($record['jobcard_id'] ?? 0);
    $note = trim($note);

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'authorization_id' => $authorizationId,
            'old_status' => $oldStatus,
            'new_status' => null,
            'message' => 'شروع تراکنش گردش کار ناموفق بود.',
            'errors' => [],
            'notes' => [],
        ];
    }

    try {
        $updateSql = 'UPDATE dbo.erp_jobcard_authorizations SET authorization_status = ?';

        if (customer_core_column_exists($connection, MOGHARE360_CONTRACT_AUTH_WF_TABLE, 'updated_at')) {
            $updateSql .= ', updated_at = SYSUTCDATETIME()';
        }

        $updateSql .= ' WHERE authorization_id = ?';

        $updateOk = customer_core_execute($connection, $updateSql, [$newStatus, $authorizationId]);

        if ($updateOk === false) {
            throw new RuntimeException('به‌روزرسانی وضعیت مجوز ناموفق بود.');
        }

        $eventNotes = moghare360_contract_authorization_workflow_build_event_notes($oldStatus, $newStatus, $note);
        $eventBy = customer_core_safe_current_user();

        $historyOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcard_authorization_history (
                authorization_id, jobcard_id, event_code, event_title, event_notes,
                old_status, new_status, event_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $authorizationId,
                $jobcardId,
                MOGHARE360_CONTRACT_AUTH_WF_EVENT_CODE,
                MOGHARE360_CONTRACT_AUTH_WF_EVENT_TITLE,
                $eventNotes,
                $oldStatus,
                $newStatus,
                $eventBy,
            ]
        );

        if ($historyOk === false) {
            throw new RuntimeException('ثبت تاریخچه گردش کار ناموفق بود.');
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش گردش کار ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'authorization_id' => $authorizationId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'message' => 'انتقال وضعیت مجوز با موفقیت انجام شد.',
            'errors' => [],
            'notes' => array_merge(
                $validation['notes'],
                ['erp_jobcard_authorization_history ' . MOGHARE360_CONTRACT_AUTH_WF_EVENT_CODE . ' written']
            ),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'authorization_id' => $authorizationId,
            'old_status' => $oldStatus,
            'new_status' => null,
            'message' => 'اعمال انتقال وضعیت ناموفق بود.',
            'errors' => [],
            'notes' => [$exception->getMessage()],
        ];
    }
}

function moghare360_contract_authorization_workflow_build_event_notes(
    string $oldStatus,
    string $newStatus,
    string $note
): string {
    $parts = [
        'old_status=' . $oldStatus,
        'new_status=' . $newStatus,
    ];

    if ($note !== '') {
        $parts[] = 'operator_note=' . substr($note, 0, 1500);
    }

    $parts[] = MOGHARE360_CONTRACT_AUTH_WF_INTERNAL_NOTICE;

    return implode(' | ', $parts);
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, message: string, errors: list<string>, notes: list<string>}
 */
function moghare360_contract_authorization_workflow_history(int $authorizationId): array
{
    if ($authorizationId < 1) {
        return [
            'ok' => false,
            'rows' => [],
            'message' => 'شناسه مجوز نامعتبر است.',
            'errors' => ['invalid_authorization_id'],
            'notes' => [],
        ];
    }

    $schema = moghare360_contract_authorization_workflow_schema_ready();

    if (!$schema['schema_ready']) {
        return [
            'ok' => false,
            'rows' => [],
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
            'errors' => ['schema_blocked'],
            'notes' => [],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'rows' => [],
            'message' => MOGHARE360_CONTRACT_AUTH_WF_BLOCK_MESSAGE,
            'errors' => ['db_connection_failed'],
            'notes' => [],
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT history_id, authorization_id, jobcard_id, event_code, event_title, event_notes,
                old_status, new_status,
                CONVERT(VARCHAR(30), event_at, 120) AS event_at,
                event_by
         FROM dbo.erp_jobcard_authorization_history
         WHERE authorization_id = ?
         ORDER BY event_at DESC, history_id DESC',
        [$authorizationId]
    );

    return [
        'ok' => true,
        'rows' => $rows,
        'message' => '',
        'errors' => [],
        'notes' => [MOGHARE360_CONTRACT_AUTH_WF_INTERNAL_NOTICE],
    ];
}

/**
 * @return list<string>
 */
function moghare360_contract_authorization_workflow_next_actions(string $currentStatus): array
{
    $map = moghare360_contract_authorization_workflow_allowed_transitions();
    $currentStatus = strtolower(trim($currentStatus));

    return $map[$currentStatus] ?? [];
}
