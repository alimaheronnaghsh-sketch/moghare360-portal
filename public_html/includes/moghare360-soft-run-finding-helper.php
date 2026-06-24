<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Helper (Wave 8A)
 *
 * Controlled internal DB write only for Soft Run findings/corrective action logs.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY = 'READY';
const MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_FINDING_SCHEMA_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_FINDING_TABLE = 'erp_soft_run_findings';
const MOGHARE360_SOFT_RUN_FINDING_HISTORY_TABLE = 'erp_soft_run_finding_history';
const MOGHARE360_SOFT_RUN_FINDING_EXECUTION_TABLE = 'erp_soft_run_pilot_executions';
const MOGHARE360_SOFT_RUN_FINDING_JOBCARD_TABLE = 'erp_jobcards';

const MOGHARE360_SOFT_RUN_FINDING_DEFAULT_USER_ID = 10001;

const MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE =
    'پایه داده ثبت یافته‌های Soft Run هنوز تأیید نشده است.';

const MOGHARE360_SOFT_RUN_FINDING_INTERNAL_NOTICE =
    'Internal Soft Run finding/corrective action log only — not final delivery. Not delivery completion. Not legal e-signature.';

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_allowed_types(): array
{
    return [
        'ISSUE',
        'BUG',
        'OBSERVATION',
        'RISK',
        'PROCESS_GAP',
        'TRAINING_NEED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_allowed_severities(): array
{
    return [
        'LOW',
        'MEDIUM',
        'HIGH',
        'CRITICAL',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_allowed_statuses(): array
{
    return [
        'OPEN',
        'UNDER_REVIEW',
        'ACTION_REQUIRED',
        'RESOLVED',
        'CLOSED',
        'CANCELLED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_allowed_corrective_statuses(): array
{
    return [
        'NOT_STARTED',
        'IN_PROGRESS',
        'DONE',
        'NOT_REQUIRED',
        'BLOCKED',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_soft_run_finding_type_labels(): array
{
    return [
        'ISSUE' => 'مسئله',
        'BUG' => 'باگ',
        'OBSERVATION' => 'مشاهده',
        'RISK' => 'ریسک',
        'PROCESS_GAP' => 'شکاف فرآیند',
        'TRAINING_NEED' => 'نیاز آموزشی',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_soft_run_finding_severity_labels(): array
{
    return [
        'LOW' => 'کم',
        'MEDIUM' => 'متوسط',
        'HIGH' => 'بالا',
        'CRITICAL' => 'بحرانی',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_soft_run_finding_status_labels(): array
{
    return [
        'OPEN' => 'باز',
        'UNDER_REVIEW' => 'در حال بازبینی',
        'ACTION_REQUIRED' => 'نیازمند اقدام',
        'RESOLVED' => 'رفع شده',
        'CLOSED' => 'بسته شده',
        'CANCELLED' => 'لغو شده',
        'NOT_STARTED' => 'شروع نشده',
        'IN_PROGRESS' => 'در حال انجام',
        'DONE' => 'انجام شده',
        'NOT_REQUIRED' => 'نیاز نیست',
        'BLOCKED' => 'مسدود',
    ];
}

function moghare360_soft_run_finding_status_label(string $status): string
{
    $key = strtoupper(trim($status));

    return moghare360_soft_run_finding_status_labels()[$key] ?? 'نامشخص';
}

function moghare360_soft_run_finding_severity_label(string $severity): string
{
    $key = strtoupper(trim($severity));

    return moghare360_soft_run_finding_severity_labels()[$key] ?? 'نامشخص';
}

function moghare360_soft_run_finding_type_label(string $type): string
{
    $key = strtoupper(trim($type));

    return moghare360_soft_run_finding_type_labels()[$key] ?? 'نامشخص';
}

function moghare360_soft_run_finding_normalize(string $value): string
{
    return strtoupper(trim($value));
}

/**
 * @return array{schema_status: string, message: string, notes: list<string>}
 */
function moghare360_soft_run_finding_schema_status(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
            'notes' => ['اتصال به پایگاه داده برقرار نشد.'],
        ];
    }

    $findingsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_HISTORY_TABLE);

    if (!$findingsOk || !$historyOk) {
        return [
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
            'notes' => [
                'اجرای دستی wave_8a_soft_run_findings_register.sql در SSMS روی MOGHARE360_ERP.',
                'findings table: ' . ($findingsOk ? 'OK' : 'MISSING'),
                'history table: ' . ($historyOk ? 'OK' : 'MISSING'),
            ],
        ];
    }

    return [
        'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
        'message' => 'پایه داده ثبت یافته‌های Soft Run تأیید شد.',
        'notes' => [
            'Soft Run findings table confirmed: dbo.' . MOGHARE360_SOFT_RUN_FINDING_TABLE,
            'Soft Run finding history table confirmed: dbo.' . MOGHARE360_SOFT_RUN_FINDING_HISTORY_TABLE,
        ],
    ];
}

function moghare360_soft_run_finding_generate_code(): string
{
    $random = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    return 'SRF-' . date('Ymd-His') . '-' . $random;
}

function moghare360_soft_run_finding_resolve_actor_user_id(): int
{
    return MOGHARE360_SOFT_RUN_FINDING_DEFAULT_USER_ID;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_soft_run_finding_validate_payload(array $payload): array
{
    $errors = [];
    $clean = [];

    $executionRaw = trim((string)($payload['execution_id'] ?? ''));
    if ($executionRaw === '') {
        $clean['execution_id'] = null;
    } elseif (!ctype_digit($executionRaw) || (int)$executionRaw < 1) {
        $errors[] = [
            'field' => 'execution_id',
            'rule' => 'optional_positive_number',
            'message' => 'شناسه اجرا باید عدد مثبت باشد یا خالی بماند.',
        ];
    } else {
        $clean['execution_id'] = (int)$executionRaw;
    }

    $jobcardRaw = trim((string)($payload['jobcard_id'] ?? ''));
    if ($jobcardRaw === '') {
        $clean['jobcard_id'] = null;
    } elseif (!ctype_digit($jobcardRaw) || (int)$jobcardRaw < 1) {
        $errors[] = [
            'field' => 'jobcard_id',
            'rule' => 'optional_positive_number',
            'message' => 'شناسه کارت کار باید عدد مثبت باشد یا خالی بماند.',
        ];
    } else {
        $clean['jobcard_id'] = (int)$jobcardRaw;
    }

    $findingType = moghare360_soft_run_finding_normalize((string)($payload['finding_type'] ?? ''));
    if (!in_array($findingType, moghare360_soft_run_finding_allowed_types(), true)) {
        $errors[] = [
            'field' => 'finding_type',
            'rule' => 'allowed_type',
            'message' => 'نوع یافته نامعتبر است.',
        ];
    } else {
        $clean['finding_type'] = $findingType;
    }

    $severity = moghare360_soft_run_finding_normalize((string)($payload['severity_level'] ?? ''));
    if (!in_array($severity, moghare360_soft_run_finding_allowed_severities(), true)) {
        $errors[] = [
            'field' => 'severity_level',
            'rule' => 'allowed_severity',
            'message' => 'سطح شدت نامعتبر است.',
        ];
    } else {
        $clean['severity_level'] = $severity;
    }

    $findingStatus = moghare360_soft_run_finding_normalize((string)($payload['finding_status'] ?? 'OPEN'));
    if (!in_array($findingStatus, moghare360_soft_run_finding_allowed_statuses(), true)) {
        $errors[] = [
            'field' => 'finding_status',
            'rule' => 'allowed_status',
            'message' => 'وضعیت یافته نامعتبر است.',
        ];
    } else {
        $clean['finding_status'] = $findingStatus;
    }

    $correctiveStatus = moghare360_soft_run_finding_normalize(
        (string)($payload['corrective_action_status'] ?? 'NOT_STARTED')
    );
    if (!in_array($correctiveStatus, moghare360_soft_run_finding_allowed_corrective_statuses(), true)) {
        $errors[] = [
            'field' => 'corrective_action_status',
            'rule' => 'allowed_corrective_status',
            'message' => 'وضعیت اقدام اصلاحی نامعتبر است.',
        ];
    } else {
        $clean['corrective_action_status'] = $correctiveStatus;
    }

    $title = trim((string)($payload['finding_title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 250) {
        $errors[] = [
            'field' => 'finding_title',
            'rule' => 'required_length',
            'message' => 'عنوان یافته الزامی است (حداکثر ۲۵۰ کاراکتر).',
        ];
    } else {
        $clean['finding_title'] = $title;
    }

    $description = trim((string)($payload['finding_description'] ?? ''));
    $clean['finding_description'] = $description === '' ? null : mb_substr($description, 0, 1500);

    $expected = trim((string)($payload['expected_behavior'] ?? ''));
    $clean['expected_behavior'] = $expected === '' ? null : mb_substr($expected, 0, 1000);

    $actual = trim((string)($payload['actual_behavior'] ?? ''));
    $clean['actual_behavior'] = $actual === '' ? null : mb_substr($actual, 0, 1000);

    $corrective = trim((string)($payload['corrective_action'] ?? ''));
    $clean['corrective_action'] = $corrective === '' ? null : mb_substr($corrective, 0, 1500);

    $ownerRaw = trim((string)($payload['owner_user_id'] ?? ''));
    if ($ownerRaw === '') {
        $clean['owner_user_id'] = null;
    } elseif (!ctype_digit($ownerRaw) || (int)$ownerRaw < 1) {
        $errors[] = [
            'field' => 'owner_user_id',
            'rule' => 'optional_positive_number',
            'message' => 'شناسه مسئول باید عدد مثبت باشد یا خالی بماند.',
        ];
    } else {
        $clean['owner_user_id'] = (int)$ownerRaw;
    }

    $dueRaw = trim((string)($payload['due_at'] ?? ''));
    if ($dueRaw === '') {
        $clean['due_at'] = null;
    } else {
        $timestamp = strtotime($dueRaw);
        if ($timestamp === false) {
            $errors[] = [
                'field' => 'due_at',
                'rule' => 'datetime',
                'message' => 'مهلت انجام باید تاریخ/زمان معتبر باشد یا خالی بماند.',
            ];
        } else {
            $clean['due_at'] = gmdate('Y-m-d H:i:s', $timestamp);
        }
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
function moghare360_soft_run_finding_validate_execution_exists($connection, int $executionId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی اجرا در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_EXECUTION_TABLE)) {
        return ['ok' => true, 'exists' => true, 'notes' => ['جدول erp_soft_run_pilot_executions تأیید نشد — اعتبارسنجی مرجع اجرا رد شد.']];
    }

    $count = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions WHERE execution_id = ?',
        [$executionId]
    ) ?? 0);

    if ($count < 1) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اجرای پایلوت با این شناسه یافت نشد.']];
    }

    return ['ok' => true, 'exists' => true, 'notes' => ['Execution reference validated (read-only).']];
}

/**
 * @return array{ok: bool, exists: bool, notes: list<string>}
 */
function moghare360_soft_run_finding_validate_jobcard_exists($connection, int $jobcardId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی کارت کار در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_JOBCARD_TABLE)) {
        return ['ok' => true, 'exists' => true, 'notes' => ['جدول erp_jobcards تأیید نشد — اعتبارسنجی مرجع کارت کار رد شد.']];
    }

    $count = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);

    if ($count < 1) {
        return ['ok' => false, 'exists' => false, 'notes' => ['کارت کار با این شناسه در erp_jobcards یافت نشد.']];
    }

    return ['ok' => true, 'exists' => true, 'notes' => ['JobCard reference validated (read-only).']];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, finding_id: int|null, finding_code: string|null, schema_status: string, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_soft_run_finding_create(array $payload): array
{
    $validation = moghare360_soft_run_finding_validate_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'finding_id' => null,
            'finding_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => 'اعتبارسنجی ثبت یافته ناموفق بود.',
            'errors' => $validation['errors'],
            'notes' => [MOGHARE360_SOFT_RUN_FINDING_INTERNAL_NOTICE],
        ];
    }

    $schema = moghare360_soft_run_finding_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY) {
        return [
            'ok' => false,
            'finding_id' => null,
            'finding_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => array_merge(
                [MOGHARE360_SOFT_RUN_FINDING_INTERNAL_NOTICE],
                $schema['notes'] ?? []
            ),
        ];
    }

    $clean = $validation['clean'];
    $connection = customer_core_db();
    $notes = $schema['notes'] ?? [];

    if ($clean['execution_id'] !== null) {
        $executionCheck = moghare360_soft_run_finding_validate_execution_exists(
            $connection,
            (int)$clean['execution_id']
        );
        $notes = array_merge($notes, $executionCheck['notes']);

        if (!$executionCheck['ok']) {
            return [
                'ok' => false,
                'finding_id' => null,
                'finding_code' => null,
                'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
                'message' => 'ثبت یافته به‌دلیل عدم تأیید مرجع اجرا مسدود شد.',
                'errors' => [[
                    'field' => 'execution_id',
                    'rule' => 'execution_not_found',
                    'message' => 'اجرای پایلوت مرجع یافت نشد.',
                ]],
                'notes' => $notes,
            ];
        }
    }

    if ($clean['jobcard_id'] !== null) {
        $jobcardCheck = moghare360_soft_run_finding_validate_jobcard_exists(
            $connection,
            (int)$clean['jobcard_id']
        );
        $notes = array_merge($notes, $jobcardCheck['notes']);

        if (!$jobcardCheck['ok']) {
            return [
                'ok' => false,
                'finding_id' => null,
                'finding_code' => null,
                'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
                'message' => 'ثبت یافته به‌دلیل عدم تأیید مرجع کارت کار مسدود شد.',
                'errors' => [[
                    'field' => 'jobcard_id',
                    'rule' => 'jobcard_not_found',
                    'message' => 'کارت کار مرجع یافت نشد.',
                ]],
                'notes' => $notes,
            ];
        }
    }

    $findingCode = moghare360_soft_run_finding_generate_code();
    $actorUserId = moghare360_soft_run_finding_resolve_actor_user_id();

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'finding_id' => null,
            'finding_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
            'message' => 'شروع تراکنش ثبت یافته ناموفق بود.',
            'errors' => [],
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_soft_run_findings (
                finding_code,
                execution_id,
                jobcard_id,
                finding_type,
                severity_level,
                finding_status,
                corrective_action_status,
                finding_title,
                finding_description,
                expected_behavior,
                actual_behavior,
                corrective_action,
                owner_user_id,
                due_at,
                resolved_at,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $findingCode,
                $clean['execution_id'],
                $clean['jobcard_id'],
                $clean['finding_type'],
                $clean['severity_level'],
                $clean['finding_status'],
                $clean['corrective_action_status'],
                $clean['finding_title'],
                $clean['finding_description'],
                $clean['expected_behavior'],
                $clean['actual_behavior'],
                $clean['corrective_action'],
                $clean['owner_user_id'],
                $clean['due_at'],
                null,
                $actorUserId,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج رکورد یافته ناموفق بود.');
        }

        $findingId = customer_core_scope_identity($connection);

        if ($findingId === null || (int)$findingId < 1) {
            $findingId = customer_core_scalar(
                $connection,
                'SELECT finding_id FROM dbo.erp_soft_run_findings WHERE finding_code = ?',
                [$findingCode]
            );
        }

        if ($findingId === null || (int)$findingId < 1) {
            throw new RuntimeException('شناسه رکورد یافته پس از درج بازیابی نشد.');
        }

        $findingId = (int)$findingId;

        $historyOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_soft_run_finding_history (
                finding_id,
                old_finding_status,
                new_finding_status,
                old_corrective_action_status,
                new_corrective_action_status,
                change_reason,
                changed_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $findingId,
                null,
                $clean['finding_status'],
                null,
                $clean['corrective_action_status'],
                'Initial Soft Run finding record created',
                $actorUserId,
            ]
        );

        if ($historyOk === false) {
            throw new RuntimeException('درج تاریخچه یافته ناموفق بود.');
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('تأیید تراکنش ثبت یافته ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'finding_id' => $findingId,
            'finding_code' => $findingCode,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
            'message' => 'رکورد یافته Soft Run با موفقیت ثبت شد.',
            'errors' => [],
            'notes' => array_merge($notes, [MOGHARE360_SOFT_RUN_FINDING_INTERNAL_NOTICE]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'finding_id' => null,
            'finding_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
            'message' => 'ثبت یافته ناموفق بود.',
            'errors' => [[
                'field' => 'database',
                'rule' => 'write_failed',
                'message' => 'خطای کنترل‌شده در ثبت پایگاه داده — جزئیات فنی نمایش داده نمی‌شود.',
            ]],
            'notes' => $notes,
        ];
    }
}

/**
 * @return array{ok: bool, records: list<array<string, mixed>>, counts: array<string, array<string, int>>, schema_status: string, message: string}
 */
function moghare360_soft_run_finding_fetch_recent(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $schema = moghare360_soft_run_finding_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => [
                'finding_status' => [],
                'severity_level' => [],
                'corrective_action_status' => [],
            ],
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => [
                'finding_status' => [],
                'severity_level' => [],
                'corrective_action_status' => [],
            ],
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $records = customer_core_fetch_rows(
        $connection,
        'SELECT TOP (' . $limit . ')
            finding_id,
            finding_code,
            execution_id,
            jobcard_id,
            finding_type,
            severity_level,
            finding_status,
            corrective_action_status,
            finding_title,
            created_at
         FROM dbo.erp_soft_run_findings
         ORDER BY finding_id DESC'
    ) ?? [];

    $findingStatusCounts = [];
    foreach (moghare360_soft_run_finding_allowed_statuses() as $status) {
        $findingStatusCounts[$status] = 0;
    }

    $severityCounts = [];
    foreach (moghare360_soft_run_finding_allowed_severities() as $severity) {
        $severityCounts[$severity] = 0;
    }

    $correctiveCounts = [];
    foreach (moghare360_soft_run_finding_allowed_corrective_statuses() as $status) {
        $correctiveCounts[$status] = 0;
    }

    $statusRows = customer_core_fetch_rows(
        $connection,
        'SELECT finding_status, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY finding_status'
    ) ?? [];

    foreach ($statusRows as $row) {
        $key = strtoupper(trim((string)($row['finding_status'] ?? '')));
        if (array_key_exists($key, $findingStatusCounts)) {
            $findingStatusCounts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    $severityRows = customer_core_fetch_rows(
        $connection,
        'SELECT severity_level, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY severity_level'
    ) ?? [];

    foreach ($severityRows as $row) {
        $key = strtoupper(trim((string)($row['severity_level'] ?? '')));
        if (array_key_exists($key, $severityCounts)) {
            $severityCounts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    $correctiveRows = customer_core_fetch_rows(
        $connection,
        'SELECT corrective_action_status, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY corrective_action_status'
    ) ?? [];

    foreach ($correctiveRows as $row) {
        $key = strtoupper(trim((string)($row['corrective_action_status'] ?? '')));
        if (array_key_exists($key, $correctiveCounts)) {
            $correctiveCounts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    return [
        'ok' => true,
        'records' => $records,
        'counts' => [
            'finding_status' => $findingStatusCounts,
            'severity_level' => $severityCounts,
            'corrective_action_status' => $correctiveCounts,
        ],
        'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
        'message' => 'فهرست اخیر یافته‌های Soft Run بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, record: array<string, mixed>|null, schema_status: string, message: string}
 */
function moghare360_soft_run_finding_fetch_detail(int $findingId): array
{
    if ($findingId < 1) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => 'شناسه یافته نامعتبر است.',
        ];
    }

    $schema = moghare360_soft_run_finding_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT
            finding_id,
            finding_code,
            execution_id,
            jobcard_id,
            finding_type,
            severity_level,
            finding_status,
            corrective_action_status,
            finding_title,
            finding_description,
            expected_behavior,
            actual_behavior,
            corrective_action,
            owner_user_id,
            due_at,
            resolved_at,
            created_at,
            updated_at,
            created_by_user_id,
            updated_by_user_id
         FROM dbo.erp_soft_run_findings
         WHERE finding_id = ?',
        [$findingId]
    ) ?? [];

    if ($rows === []) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
            'message' => 'رکورد یافته یافت نشد.',
        ];
    }

    return [
        'ok' => true,
        'record' => $rows[0],
        'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
        'message' => 'جزئیات یافته بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, history: list<array<string, mixed>>, schema_status: string, message: string}
 */
function moghare360_soft_run_finding_fetch_history(int $findingId): array
{
    if ($findingId < 1) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => 'شناسه یافته نامعتبر است.',
        ];
    }

    $schema = moghare360_soft_run_finding_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $history = customer_core_fetch_rows(
        $connection,
        'SELECT
            history_id,
            finding_id,
            old_finding_status,
            new_finding_status,
            old_corrective_action_status,
            new_corrective_action_status,
            change_reason,
            changed_at,
            changed_by_user_id
         FROM dbo.erp_soft_run_finding_history
         WHERE finding_id = ?
         ORDER BY history_id ASC',
        [$findingId]
    ) ?? [];

    return [
        'ok' => true,
        'history' => $history,
        'schema_status' => MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
        'message' => 'تاریخچه یافته بازیابی شد.',
    ];
}
