<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Delivery Clearance Helper (Wave 4C)
 *
 * Internal delivery clearance records — NOT final vehicle delivery.
 * Uses WAVE 4B delivery eligibility. DB writes only when schema is READY.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';

const MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY = 'READY';
const MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED = 'BLOCKED';
const MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_ERROR = 'ERROR';

const MOGHARE360_DELIVERY_CLEARANCE_TABLE = 'erp_jobcard_delivery_clearances';
const MOGHARE360_DELIVERY_CLEARANCE_HISTORY_TABLE = 'erp_jobcard_delivery_clearance_history';
const MOGHARE360_DELIVERY_CLEARANCE_JOBCARD_TABLE = 'erp_jobcards';

const MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE =
    'Delivery clearance DB foundation is not confirmed yet.';

const MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE =
    'This is internal delivery clearance only — not final vehicle delivery. Not legal final e-signature.';

/**
 * @return list<string>
 */
function moghare360_delivery_clearance_candidate_tables(): array
{
    return [
        'erp_jobcard_delivery_clearances',
        'erp_delivery_clearances',
        'erp_jobcard_delivery_approvals',
        'erp_jobcard_delivery_control',
    ];
}

/**
 * @return list<string>
 */
function moghare360_delivery_clearance_allowed_statuses(): array
{
    return [
        'draft',
        'clearance_requested',
        'cleared',
        'not_cleared',
        'cancelled',
    ];
}

/**
 * @return list<string>
 */
function moghare360_delivery_clearance_allowed_decisions(): array
{
    return [
        'eligible_for_delivery_review',
        'cleared_for_delivery_process',
        'not_cleared_missing_requirements',
        'cancelled_by_internal_review',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_delivery_clearance_status_labels(): array
{
    return [
        'draft' => 'پیش‌نویس',
        'clearance_requested' => 'درخواست Clearance',
        'cleared' => 'Clearance داده شد',
        'not_cleared' => 'Clearance داده نشد',
        'cancelled' => 'لغو شده',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_delivery_clearance_decision_labels(): array
{
    return [
        'eligible_for_delivery_review' => 'صلاحیت برای بازبینی تحویل',
        'cleared_for_delivery_process' => 'Clearance برای فرآیند تحویل داخلی',
        'not_cleared_missing_requirements' => 'عدم Clearance — الزامات ناقص',
        'cancelled_by_internal_review' => 'لغو توسط بازبینی داخلی',
    ];
}

function moghare360_delivery_clearance_status_label(string $status): string
{
    $key = strtolower(trim($status));

    return moghare360_delivery_clearance_status_labels()[$key] ?? 'نامشخص';
}

/**
 * @return array<string, mixed>
 */
function moghare360_delivery_clearance_fetch_eligibility(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    return moghare360_delivery_eligibility_evaluate($jobcardId);
}

/**
 * @return array{schema_status: string, message: string, table: string|null, history_table_ok: bool, notes: list<string>}
 */
function moghare360_delivery_clearance_schema_status(): array
{
    $notes = [];
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'table' => null,
            'history_table_ok' => false,
            'notes' => ['اتصال به پایگاه داده برقرار نشد.'],
        ];
    }

    $resolvedTable = moghare360_delivery_clearance_resolve_table($connection);

    if ($resolvedTable === null) {
        return [
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'table' => null,
            'history_table_ok' => false,
            'notes' => [
                'جدول Clearance تحویل کارت کار با ستون‌های ایمن یافت نشد.',
                'اجرای دستی wave_4c_delivery_clearance_foundation.sql در SSMS پس از تأیید ChatGPT.',
            ],
        ];
    }

    $notes[] = 'Safe delivery clearance table confirmed: dbo.' . $resolvedTable;

    $historyOk = customer_core_table_exists($connection, MOGHARE360_DELIVERY_CLEARANCE_HISTORY_TABLE);
    if ($historyOk) {
        $notes[] = 'Delivery clearance history table confirmed: dbo.' . MOGHARE360_DELIVERY_CLEARANCE_HISTORY_TABLE;
    } else {
        $notes[] = 'Delivery clearance history table not confirmed — audit history write skipped.';
    }

    return [
        'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
        'message' => 'پایه داده Clearance تحویل کارت کار تأیید شد.',
        'table' => $resolvedTable,
        'history_table_ok' => $historyOk,
        'notes' => $notes,
    ];
}

function moghare360_delivery_clearance_resolve_table($connection): ?string
{
    if ($connection === false) {
        return null;
    }

    foreach (moghare360_delivery_clearance_candidate_tables() as $tableName) {
        if (!customer_core_table_exists($connection, $tableName)) {
            continue;
        }

        $required = ['jobcard_id', 'clearance_status', 'clearance_decision', 'reviewer_name'];
        $allPresent = true;

        foreach ($required as $column) {
            if (!customer_core_column_exists($connection, $tableName, $column)) {
                $allPresent = false;
                break;
            }
        }

        if ($allPresent) {
            return $tableName;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_delivery_clearance_validate_payload(array $payload): array
{
    $errors = [];
    $clean = [];

    $jobcardIdRaw = trim((string)($payload['jobcard_id'] ?? ''));
    if ($jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1) {
        $errors[] = [
            'field' => 'jobcard_id',
            'rule' => 'positive_number',
            'message' => 'شناسه کارت کار باید عدد مثبت باشد.',
        ];
    } else {
        $clean['jobcard_id'] = (int)$jobcardIdRaw;
    }

    $clearanceStatus = trim((string)($payload['clearance_status'] ?? ''));
    if (!in_array($clearanceStatus, moghare360_delivery_clearance_allowed_statuses(), true)) {
        $errors[] = [
            'field' => 'clearance_status',
            'rule' => 'allowed_status',
            'message' => 'وضعیت Clearance نامعتبر است.',
        ];
    } else {
        $clean['clearance_status'] = $clearanceStatus;
    }

    $clearanceDecision = trim((string)($payload['clearance_decision'] ?? ''));
    if (!in_array($clearanceDecision, moghare360_delivery_clearance_allowed_decisions(), true)) {
        $errors[] = [
            'field' => 'clearance_decision',
            'rule' => 'allowed_decision',
            'message' => 'تصمیم Clearance نامعتبر است.',
        ];
    } else {
        $clean['clearance_decision'] = $clearanceDecision;
    }

    $reviewerName = trim((string)($payload['reviewer_name'] ?? ''));
    if ($reviewerName === '' || mb_strlen($reviewerName) > 200) {
        $errors[] = [
            'field' => 'reviewer_name',
            'rule' => 'required_length',
            'message' => 'نام بازبین الزامی است (حداکثر ۲۰۰ کاراکتر).',
        ];
    } else {
        $clean['reviewer_name'] = $reviewerName;
    }

    $clearanceNote = trim((string)($payload['clearance_note'] ?? ''));
    if (mb_strlen($clearanceNote) > 2000) {
        $errors[] = [
            'field' => 'clearance_note',
            'rule' => 'max_length',
            'message' => 'یادداشت Clearance حداکثر ۲۰۰۰ کاراکتر مجاز است.',
        ];
    } else {
        $clean['clearance_note'] = $clearanceNote;
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'clean' => $clean,
    ];
}

/**
 * @return array{ok: bool, exists: bool, notes: list<string>}
 */
function moghare360_delivery_clearance_validate_jobcard_exists($connection, int $jobcardId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی کارت کار در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_DELIVERY_CLEARANCE_JOBCARD_TABLE)) {
        return ['ok' => false, 'exists' => false, 'notes' => ['جدول erp_jobcards تأیید نشد.']];
    }

    $count = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);

    if ($count < 1) {
        return ['ok' => false, 'exists' => false, 'notes' => ['کارت کار با این شناسه در erp_jobcards یافت نشد.']];
    }

    return ['ok' => true, 'exists' => true, 'notes' => ['JobCard reference validated against erp_jobcards.']];
}

/**
 * @return list<array{field: string, rule: string, message: string}>
 */
function moghare360_delivery_clearance_eligibility_clearance_errors(
    array $eligibility,
    string $clearanceStatus,
    string $clearanceDecision
): array {
    $eligStatus = strtoupper(trim((string)($eligibility['status'] ?? '')));

    if (!in_array($eligStatus, [
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE,
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
    ], true)) {
        return [];
    }

    $errors = [];

    if ($clearanceStatus === 'cleared') {
        $errors[] = [
            'field' => 'clearance_status',
            'rule' => 'eligibility_blocks_cleared',
            'message' => 'وضعیت cleared مجاز نیست — صلاحیت تحویل NOT_ELIGIBLE یا ERROR است.',
        ];
    }

    if ($clearanceDecision === 'cleared_for_delivery_process') {
        $errors[] = [
            'field' => 'clearance_decision',
            'rule' => 'eligibility_blocks_cleared_decision',
            'message' => 'تصمیم cleared_for_delivery_process مجاز نیست — صلاحیت تحویل NOT_ELIGIBLE یا ERROR است.',
        ];
    }

    return $errors;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, clearance_id: int|string|null, schema_status: string, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_delivery_clearance_create(array $payload): array
{
    $validation = moghare360_delivery_clearance_validate_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'message' => 'اعتبارسنجی Clearance تحویل ناموفق بود.',
            'errors' => $validation['errors'],
            'notes' => [MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE],
        ];
    }

    $clean = $validation['clean'];
    $jobcardId = (int)$clean['jobcard_id'];
    $eligibility = moghare360_delivery_clearance_fetch_eligibility($jobcardId);
    $eligibilityErrors = moghare360_delivery_clearance_eligibility_clearance_errors(
        $eligibility,
        (string)$clean['clearance_status'],
        (string)$clean['clearance_decision']
    );

    if ($eligibilityErrors !== []) {
        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
            'message' => 'ثبت Clearance به‌دلیل صلاحیت تحویل مسدود شد.',
            'errors' => $eligibilityErrors,
            'notes' => [
                MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE,
                'Eligibility status: ' . (string)($eligibility['status'] ?? ''),
            ],
        ];
    }

    $schema = moghare360_delivery_clearance_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY || ($schema['table'] ?? null) === null) {
        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => array_merge(
                [MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE],
                $schema['notes'] ?? []
            ),
        ];
    }

    $connection = customer_core_db();
    $jobcardCheck = moghare360_delivery_clearance_validate_jobcard_exists($connection, $jobcardId);
    $notes = $jobcardCheck['notes'];

    if (!$jobcardCheck['ok']) {
        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
            'message' => 'ثبت Clearance به‌دلیل عدم تأیید مرجع کارت کار مسدود شد.',
            'errors' => [[
                'field' => 'jobcard_id',
                'rule' => 'jobcard_not_found',
                'message' => 'کارت کار مرجع یافت نشد.',
            ]],
            'notes' => $notes,
        ];
    }

    $tableName = (string)$schema['table'];
    $noteValue = (string)($clean['clearance_note'] ?? '');

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
            'message' => 'شروع تراکنش ثبت Clearance ناموفق بود.',
            'errors' => [],
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.' . $tableName . ' (
                jobcard_id, clearance_status, clearance_decision, reviewer_name, clearance_note
            ) VALUES (?, ?, ?, ?, ?)',
            [
                $jobcardId,
                $clean['clearance_status'],
                $clean['clearance_decision'],
                $clean['reviewer_name'],
                $noteValue !== '' ? $noteValue : null,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج رکورد Clearance تحویل ناموفق بود.');
        }

        $clearanceId = customer_core_scope_identity($connection);

        if ($clearanceId === null || (int)$clearanceId < 1) {
            $clearanceId = customer_core_scalar(
                $connection,
                'SELECT TOP 1 clearance_id FROM dbo.' . $tableName . ' WHERE jobcard_id = ? ORDER BY clearance_id DESC',
                [$jobcardId]
            );
        }

        if (($schema['history_table_ok'] ?? false) === true) {
            $historyWritten = moghare360_delivery_clearance_write_history(
                $connection,
                (int)$clearanceId,
                $jobcardId,
                (string)$clean['clearance_status'],
                (string)$clean['clearance_decision'],
                (string)$clean['reviewer_name']
            );

            if ($historyWritten) {
                $notes[] = 'erp_jobcard_delivery_clearance_history CLEARANCE_REGISTERED written';
            }
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش Clearance ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'clearance_id' => $clearanceId,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
            'message' => 'رکورد Clearance تحویل داخلی با موفقیت ثبت شد.',
            'errors' => [],
            'notes' => array_merge($notes, [MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'clearance_id' => null,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
            'message' => 'ثبت Clearance تحویل ناموفق بود.',
            'errors' => [],
            'notes' => array_merge($notes, [$exception->getMessage()]),
        ];
    }
}

function moghare360_delivery_clearance_write_history(
    $connection,
    int $clearanceId,
    int $jobcardId,
    string $newStatus,
    string $decision,
    string $reviewerName
): bool {
    if ($connection === false) {
        return false;
    }

    if (!customer_core_table_exists($connection, MOGHARE360_DELIVERY_CLEARANCE_HISTORY_TABLE)) {
        return false;
    }

    $eventBy = customer_core_safe_current_user();

    return customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_jobcard_delivery_clearance_history (
            clearance_id, jobcard_id, event_code, event_title, event_notes,
            old_status, new_status, clearance_decision, event_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $clearanceId,
            $jobcardId,
            'CLEARANCE_REGISTERED',
            'Internal delivery clearance registered',
            'Reviewer: ' . $reviewerName . ' — ' . MOGHARE360_DELIVERY_CLEARANCE_INTERNAL_NOTICE,
            null,
            $newStatus,
            $decision,
            $eventBy,
        ]
    ) !== false;
}

/**
 * @return array{ok: bool, schema_status: string, records: list<array<string, string>>, message: string, errors: list<string>, notes: list<string>}
 */
function moghare360_delivery_clearance_list_by_jobcard(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
            'notes' => [],
        ];
    }

    $schema = moghare360_delivery_clearance_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY || ($schema['table'] ?? null) === null) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => $schema['notes'] ?? [],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'errors' => ['db_connection_failed'],
            'notes' => [],
        ];
    }

    $tableName = (string)$schema['table'];
    $whereClause = 'WHERE jobcard_id = ?';
    $params = [$jobcardId];

    if (customer_core_column_exists($connection, $tableName, 'is_deleted')) {
        $whereClause .= ' AND is_deleted = 0';
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT clearance_id, jobcard_id, clearance_status, clearance_decision, reviewer_name, clearance_note,
                CONVERT(VARCHAR(30), created_at, 120) AS created_at,
                CONVERT(VARCHAR(30), updated_at, 120) AS updated_at
         FROM dbo.' . $tableName . '
         ' . $whereClause . '
         ORDER BY created_at DESC, clearance_id DESC',
        $params
    );

    return [
        'ok' => true,
        'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
        'records' => $rows,
        'message' => '',
        'errors' => [],
        'notes' => $schema['notes'] ?? [],
    ];
}

/**
 * @return array{ok: bool, schema_status: string, records: list<array<string, string>>, message: string, errors: list<string>, notes: list<string>}
 */
function moghare360_delivery_clearance_history_by_jobcard(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
            'notes' => [],
        ];
    }

    $schema = moghare360_delivery_clearance_schema_status();

    if (($schema['history_table_ok'] ?? false) !== true) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => $schema['notes'] ?? [],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE,
            'errors' => ['db_connection_failed'],
            'notes' => [],
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT history_id, clearance_id, jobcard_id, event_code, event_title, event_notes,
                old_status, new_status, clearance_decision, event_by,
                CONVERT(VARCHAR(30), event_at, 120) AS event_at
         FROM dbo.erp_jobcard_delivery_clearance_history
         WHERE jobcard_id = ?
         ORDER BY event_at DESC, history_id DESC',
        [$jobcardId]
    );

    return [
        'ok' => true,
        'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
        'records' => $rows,
        'message' => '',
        'errors' => [],
        'notes' => $schema['notes'] ?? [],
    ];
}
