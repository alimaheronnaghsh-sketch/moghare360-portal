<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Execution Helper (Wave 7A)
 *
 * Controlled internal DB write only for pilot execution logs.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY = 'READY';
const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_TABLE = 'erp_soft_run_pilot_executions';
const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_HISTORY_TABLE = 'erp_soft_run_pilot_execution_history';
const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_JOBCARD_TABLE = 'erp_jobcards';

const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_DEFAULT_USER_ID = 10001;

const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE =
    'پایه داده لاگ اجرای پایلوت Soft Run هنوز تأیید نشده است.';

const MOGHARE360_SOFT_RUN_PILOT_EXECUTION_INTERNAL_NOTICE =
    'Internal Soft Run pilot execution log only — not final delivery. Not delivery completion. Not legal e-signature.';

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_execution_allowed_statuses(): array
{
    return [
        'DRAFT',
        'STARTED',
        'OBSERVED',
        'PASSED',
        'FAILED',
        'BLOCKED',
        'CANCELLED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_execution_allowed_evidence_statuses(): array
{
    return [
        'PENDING_REVIEW',
        'VISIBLE',
        'MISSING',
        'NOT_REQUIRED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_execution_allowed_result_statuses(): array
{
    return [
        'NOT_EVALUATED',
        'PASS',
        'FAIL',
        'BLOCKED',
        'NEEDS_REVIEW',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_soft_run_pilot_execution_status_labels(): array
{
    return [
        'DRAFT' => 'پیش‌نویس',
        'STARTED' => 'شروع شده',
        'OBSERVED' => 'مشاهده شده',
        'PASSED' => 'موفق',
        'FAILED' => 'ناموفق',
        'BLOCKED' => 'مسدود',
        'CANCELLED' => 'لغو شده',
        'PENDING_REVIEW' => 'در انتظار بازبینی',
        'VISIBLE' => 'قابل مشاهده',
        'MISSING' => 'مفقود',
        'NOT_REQUIRED' => 'نیاز نیست',
        'NOT_EVALUATED' => 'ارزیابی نشده',
        'PASS' => 'قبول',
        'FAIL' => 'رد',
        'NEEDS_REVIEW' => 'نیازمند بازبینی',
    ];
}

function moghare360_soft_run_pilot_execution_status_label(string $status): string
{
    $key = strtoupper(trim($status));

    return moghare360_soft_run_pilot_execution_status_labels()[$key] ?? 'نامشخص';
}

function moghare360_soft_run_pilot_execution_normalize_status(string $value): string
{
    return strtoupper(trim($value));
}

/**
 * @return array{schema_status: string, message: string, notes: list<string>}
 */
function moghare360_soft_run_pilot_execution_schema_status(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
            'notes' => ['اتصال به پایگاه داده برقرار نشد.'],
        ];
    }

    $executionsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_EXECUTION_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_EXECUTION_HISTORY_TABLE);

    if (!$executionsOk || !$historyOk) {
        return [
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
            'notes' => [
                'اجرای دستی wave_7a_soft_run_pilot_execution_log.sql در SSMS روی MOGHARE360_ERP.',
                'executions table: ' . ($executionsOk ? 'OK' : 'MISSING'),
                'history table: ' . ($historyOk ? 'OK' : 'MISSING'),
            ],
        ];
    }

    return [
        'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
        'message' => 'پایه داده لاگ اجرای پایلوت Soft Run تأیید شد.',
        'notes' => [
            'Soft Run pilot execution table confirmed: dbo.' . MOGHARE360_SOFT_RUN_PILOT_EXECUTION_TABLE,
            'Soft Run pilot execution history table confirmed: dbo.' . MOGHARE360_SOFT_RUN_PILOT_EXECUTION_HISTORY_TABLE,
        ],
    ];
}

function moghare360_soft_run_pilot_execution_generate_code(): string
{
    $random = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    return 'SRP-' . date('Ymd-His') . '-' . $random;
}

function moghare360_soft_run_pilot_execution_resolve_actor_user_id(): int
{
    return MOGHARE360_SOFT_RUN_PILOT_EXECUTION_DEFAULT_USER_ID;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_soft_run_pilot_execution_validate_payload(array $payload): array
{
    $errors = [];
    $clean = [];

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

    $scenarioKey = trim((string)($payload['scenario_key'] ?? ''));
    if ($scenarioKey === '' || mb_strlen($scenarioKey) > 120) {
        $errors[] = [
            'field' => 'scenario_key',
            'rule' => 'required_length',
            'message' => 'کلید سناریو الزامی است (حداکثر ۱۲۰ کاراکتر).',
        ];
    } elseif (!preg_match('/^[a-z0-9_]+$/', $scenarioKey)) {
        $errors[] = [
            'field' => 'scenario_key',
            'rule' => 'pattern',
            'message' => 'کلید سناریو فقط حروف کوچک انگلیسی، عدد و زیرخط مجاز است.',
        ];
    } else {
        $clean['scenario_key'] = $scenarioKey;
    }

    $scenarioTitle = trim((string)($payload['scenario_title'] ?? ''));
    if ($scenarioTitle === '' || mb_strlen($scenarioTitle) > 250) {
        $errors[] = [
            'field' => 'scenario_title',
            'rule' => 'required_length',
            'message' => 'عنوان سناریو الزامی است (حداکثر ۲۵۰ کاراکتر).',
        ];
    } else {
        $clean['scenario_title'] = $scenarioTitle;
    }

    $executionStatus = moghare360_soft_run_pilot_execution_normalize_status(
        (string)($payload['execution_status'] ?? 'STARTED')
    );
    if (!in_array($executionStatus, moghare360_soft_run_pilot_execution_allowed_statuses(), true)) {
        $errors[] = [
            'field' => 'execution_status',
            'rule' => 'allowed_status',
            'message' => 'وضعیت اجرا نامعتبر است.',
        ];
    } else {
        $clean['execution_status'] = $executionStatus;
    }

    $evidenceStatus = moghare360_soft_run_pilot_execution_normalize_status(
        (string)($payload['evidence_status'] ?? 'PENDING_REVIEW')
    );
    if (!in_array($evidenceStatus, moghare360_soft_run_pilot_execution_allowed_evidence_statuses(), true)) {
        $errors[] = [
            'field' => 'evidence_status',
            'rule' => 'allowed_evidence_status',
            'message' => 'وضعیت شواهد نامعتبر است.',
        ];
    } else {
        $clean['evidence_status'] = $evidenceStatus;
    }

    $resultStatus = moghare360_soft_run_pilot_execution_normalize_status(
        (string)($payload['result_status'] ?? 'NOT_EVALUATED')
    );
    if (!in_array($resultStatus, moghare360_soft_run_pilot_execution_allowed_result_statuses(), true)) {
        $errors[] = [
            'field' => 'result_status',
            'rule' => 'allowed_result_status',
            'message' => 'وضعیت نتیجه نامعتبر است.',
        ];
    } else {
        $clean['result_status'] = $resultStatus;
    }

    foreach ([
        'observed_summary' => 1000,
        'expected_evidence' => 1000,
        'actual_evidence' => 1000,
        'blocker_notes' => 1000,
        'internal_notes' => 1000,
    ] as $field => $maxLen) {
        $value = trim((string)($payload[$field] ?? ''));
        if (mb_strlen($value) > $maxLen) {
            $errors[] = [
                'field' => $field,
                'rule' => 'max_length',
                'message' => 'فیلد ' . $field . ' حداکثر ' . $maxLen . ' کاراکتر مجاز است.',
            ];
        } else {
            $clean[$field] = $value !== '' ? $value : null;
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
function moghare360_soft_run_pilot_execution_validate_jobcard_exists($connection, int $jobcardId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی کارت کار در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_EXECUTION_JOBCARD_TABLE)) {
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

    return ['ok' => true, 'exists' => true, 'notes' => ['JobCard reference validated against erp_jobcards (read-only).']];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, execution_id: int|null, execution_code: string|null, schema_status: string, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_soft_run_pilot_execution_create(array $payload): array
{
    $validation = moghare360_soft_run_pilot_execution_validate_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'execution_id' => null,
            'execution_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => 'اعتبارسنجی ثبت اجرای پایلوت ناموفق بود.',
            'errors' => $validation['errors'],
            'notes' => [MOGHARE360_SOFT_RUN_PILOT_EXECUTION_INTERNAL_NOTICE],
        ];
    }

    $schema = moghare360_soft_run_pilot_execution_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY) {
        return [
            'ok' => false,
            'execution_id' => null,
            'execution_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => array_merge(
                [MOGHARE360_SOFT_RUN_PILOT_EXECUTION_INTERNAL_NOTICE],
                $schema['notes'] ?? []
            ),
        ];
    }

    $clean = $validation['clean'];
    $connection = customer_core_db();
    $notes = $schema['notes'] ?? [];

    if ($clean['jobcard_id'] !== null) {
        $jobcardCheck = moghare360_soft_run_pilot_execution_validate_jobcard_exists(
            $connection,
            (int)$clean['jobcard_id']
        );
        $notes = array_merge($notes, $jobcardCheck['notes']);

        if (!$jobcardCheck['ok']) {
            return [
                'ok' => false,
                'execution_id' => null,
                'execution_code' => null,
                'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
                'message' => 'ثبت اجرای پایلوت به‌دلیل عدم تأیید مرجع کارت کار مسدود شد.',
                'errors' => [[
                    'field' => 'jobcard_id',
                    'rule' => 'jobcard_not_found',
                    'message' => 'کارت کار مرجع یافت نشد.',
                ]],
                'notes' => $notes,
            ];
        }
    }

    $executionCode = moghare360_soft_run_pilot_execution_generate_code();
    $actorUserId = moghare360_soft_run_pilot_execution_resolve_actor_user_id();
    $executionStatus = (string)$clean['execution_status'];
    $resultStatus = (string)$clean['result_status'];

    $startedAt = null;
    if (in_array($executionStatus, ['STARTED', 'OBSERVED', 'PASSED', 'FAILED', 'BLOCKED'], true)) {
        $startedAt = gmdate('Y-m-d H:i:s');
    }

    $completedAt = null;
    if (in_array($executionStatus, ['PASSED', 'FAILED', 'BLOCKED', 'CANCELLED'], true)) {
        $completedAt = gmdate('Y-m-d H:i:s');
    }

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'execution_id' => null,
            'execution_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
            'message' => 'شروع تراکنش ثبت اجرای پایلوت ناموفق بود.',
            'errors' => [],
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_soft_run_pilot_executions (
                execution_code,
                jobcard_id,
                scenario_key,
                scenario_title,
                operator_user_id,
                execution_status,
                evidence_status,
                result_status,
                observed_summary,
                expected_evidence,
                actual_evidence,
                blocker_notes,
                internal_notes,
                started_at,
                completed_at,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $executionCode,
                $clean['jobcard_id'],
                $clean['scenario_key'],
                $clean['scenario_title'],
                $actorUserId,
                $clean['execution_status'],
                $clean['evidence_status'],
                $clean['result_status'],
                $clean['observed_summary'],
                $clean['expected_evidence'],
                $clean['actual_evidence'],
                $clean['blocker_notes'],
                $clean['internal_notes'],
                $startedAt,
                $completedAt,
                $actorUserId,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج رکورد اجرای پایلوت ناموفق بود.');
        }

        $executionId = customer_core_scope_identity($connection);

        if ($executionId === null || (int)$executionId < 1) {
            $executionId = customer_core_scalar(
                $connection,
                'SELECT execution_id FROM dbo.erp_soft_run_pilot_executions WHERE execution_code = ?',
                [$executionCode]
            );
        }

        if ($executionId === null || (int)$executionId < 1) {
            throw new RuntimeException('شناسه رکورد اجرای پایلوت پس از درج بازیابی نشد.');
        }

        $executionId = (int)$executionId;

        $historyOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_soft_run_pilot_execution_history (
                execution_id,
                old_execution_status,
                new_execution_status,
                old_result_status,
                new_result_status,
                change_reason,
                changed_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $executionId,
                null,
                $clean['execution_status'],
                null,
                $clean['result_status'],
                'Initial pilot execution record created',
                $actorUserId,
            ]
        );

        if ($historyOk === false) {
            throw new RuntimeException('درج تاریخچه اجرای پایلوت ناموفق بود.');
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('تأیید تراکنش ثبت اجرای پایلوت ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'execution_id' => $executionId,
            'execution_code' => $executionCode,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
            'message' => 'رکورد اجرای پایلوت Soft Run با موفقیت ثبت شد.',
            'errors' => [],
            'notes' => array_merge($notes, [MOGHARE360_SOFT_RUN_PILOT_EXECUTION_INTERNAL_NOTICE]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'execution_id' => null,
            'execution_code' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
            'message' => 'ثبت اجرای پایلوت ناموفق بود.',
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
function moghare360_soft_run_pilot_execution_fetch_recent(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $schema = moghare360_soft_run_pilot_execution_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => [
                'execution_status' => [],
                'result_status' => [],
            ],
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => [
                'execution_status' => [],
                'result_status' => [],
            ],
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $records = customer_core_fetch_rows(
        $connection,
        'SELECT TOP (' . $limit . ')
            execution_id,
            execution_code,
            jobcard_id,
            scenario_key,
            scenario_title,
            execution_status,
            evidence_status,
            result_status,
            started_at,
            completed_at,
            created_at
         FROM dbo.erp_soft_run_pilot_executions
         ORDER BY execution_id DESC'
    ) ?? [];

    $executionCounts = [];
    foreach (moghare360_soft_run_pilot_execution_allowed_statuses() as $status) {
        $executionCounts[$status] = 0;
    }

    $resultCounts = [];
    foreach (moghare360_soft_run_pilot_execution_allowed_result_statuses() as $status) {
        $resultCounts[$status] = 0;
    }

    $countRows = customer_core_fetch_rows(
        $connection,
        'SELECT execution_status, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_pilot_executions
         GROUP BY execution_status'
    ) ?? [];

    foreach ($countRows as $row) {
        $key = strtoupper(trim((string)($row['execution_status'] ?? '')));
        if (array_key_exists($key, $executionCounts)) {
            $executionCounts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    $resultCountRows = customer_core_fetch_rows(
        $connection,
        'SELECT result_status, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_pilot_executions
         GROUP BY result_status'
    ) ?? [];

    foreach ($resultCountRows as $row) {
        $key = strtoupper(trim((string)($row['result_status'] ?? '')));
        if (array_key_exists($key, $resultCounts)) {
            $resultCounts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    return [
        'ok' => true,
        'records' => $records,
        'counts' => [
            'execution_status' => $executionCounts,
            'result_status' => $resultCounts,
        ],
        'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
        'message' => 'فهرست اخیر اجرای پایلوت بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, record: array<string, mixed>|null, schema_status: string, message: string}
 */
function moghare360_soft_run_pilot_execution_fetch_detail(int $executionId): array
{
    if ($executionId < 1) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR,
            'message' => 'شناسه اجرا نامعتبر است.',
        ];
    }

    $schema = moghare360_soft_run_pilot_execution_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT
            execution_id,
            execution_code,
            jobcard_id,
            scenario_key,
            scenario_title,
            operator_user_id,
            execution_status,
            evidence_status,
            result_status,
            observed_summary,
            expected_evidence,
            actual_evidence,
            blocker_notes,
            internal_notes,
            started_at,
            completed_at,
            created_at,
            updated_at,
            created_by_user_id,
            updated_by_user_id
         FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_id = ?',
        [$executionId]
    );

    $record = $rows[0] ?? null;

    if ($record === null) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
            'message' => 'رکورد اجرای پایلوت یافت نشد.',
        ];
    }

    return [
        'ok' => true,
        'record' => $record,
        'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
        'message' => 'جزئیات اجرای پایلوت بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, history: list<array<string, mixed>>, schema_status: string, message: string}
 */
function moghare360_soft_run_pilot_execution_fetch_history(int $executionId): array
{
    if ($executionId < 1) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR,
            'message' => 'شناسه اجرا نامعتبر است.',
        ];
    }

    $schema = moghare360_soft_run_pilot_execution_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
            'message' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $history = customer_core_fetch_rows(
        $connection,
        'SELECT
            history_id,
            execution_id,
            old_execution_status,
            new_execution_status,
            old_result_status,
            new_result_status,
            change_reason,
            changed_at,
            changed_by_user_id
         FROM dbo.erp_soft_run_pilot_execution_history
         WHERE execution_id = ?
         ORDER BY history_id ASC',
        [$executionId]
    ) ?? [];

    return [
        'ok' => true,
        'history' => $history,
        'schema_status' => MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
        'message' => 'تاریخچه اجرای پایلوت بازیابی شد.',
    ];
}
