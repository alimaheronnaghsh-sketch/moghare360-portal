<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Go/No-Go Decision Helper (Wave 9B)
 *
 * Controlled internal DB write only for executive Soft Run decision logs.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

$readinessHelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-executive-soft-run-readiness-helper.php';
if (is_file($readinessHelperPath)) {
    require_once $readinessHelperPath;
}

const MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY = 'READY';
const MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED = 'BLOCKED';
const MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_ERROR = 'ERROR';

const MOGHARE360_EXECUTIVE_GO_NO_GO_DECISIONS_TABLE = 'erp_executive_soft_run_decisions';
const MOGHARE360_EXECUTIVE_GO_NO_GO_HISTORY_TABLE = 'erp_executive_soft_run_decision_history';
const MOGHARE360_EXECUTIVE_GO_NO_GO_FINDING_TABLE = 'erp_soft_run_findings';
const MOGHARE360_EXECUTIVE_GO_NO_GO_PILOT_TABLE = 'erp_soft_run_pilot_executions';

const MOGHARE360_EXECUTIVE_GO_NO_GO_DEFAULT_USER_ID = 10001;

const MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE =
    'پایه داده ثبت تصمیم مدیریتی Go/No-Go هنوز تأیید نشده است.';

const MOGHARE360_EXECUTIVE_GO_NO_GO_INTERNAL_NOTICE =
    'Internal executive Go/No-Go review decision log only — not final delivery. Not delivery completion. Not legal e-signature.';

/**
 * @return list<string>
 */
function moghare360_executive_go_no_go_decision_allowed_types(): array
{
    return [
        'GO_REVIEW',
        'CONDITIONAL_GO',
        'HOLD',
        'NO_GO',
        'REVIEW_REQUIRED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_executive_go_no_go_decision_allowed_statuses(): array
{
    return [
        'RECORDED',
        'UNDER_REVIEW',
        'ACTION_REQUIRED',
        'ACCEPTED',
        'CLOSED',
        'CANCELLED',
    ];
}

/**
 * @return list<string>
 */
function moghare360_executive_go_no_go_decision_allowed_readiness_statuses(): array
{
    return [
        'EXECUTIVE_REVIEW_READY',
        'GO_REVIEW_REQUIRED',
        'BLOCKED',
        'EMPTY',
        'ERROR',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_executive_go_no_go_decision_type_labels(): array
{
    return [
        'GO_REVIEW' => 'بازبینی Go',
        'CONDITIONAL_GO' => 'Go مشروط',
        'HOLD' => 'توقف',
        'NO_GO' => 'No-Go',
        'REVIEW_REQUIRED' => 'نیازمند بازبینی',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_executive_go_no_go_decision_status_labels(): array
{
    return [
        'RECORDED' => 'ثبت شده',
        'UNDER_REVIEW' => 'در حال بازبینی',
        'ACTION_REQUIRED' => 'نیازمند اقدام',
        'ACCEPTED' => 'پذیرفته شده',
        'CLOSED' => 'بسته شده',
        'CANCELLED' => 'لغو شده',
        'EXECUTIVE_REVIEW_READY' => 'آماده بازبینی مدیریتی',
        'GO_REVIEW_REQUIRED' => 'نیازمند بازبینی Go/No-Go',
        'BLOCKED' => 'مسدود',
        'EMPTY' => 'خالی',
        'ERROR' => 'خطا',
    ];
}

function moghare360_executive_go_no_go_decision_normalize(string $value): string
{
    return strtoupper(trim($value));
}

function moghare360_executive_go_no_go_decision_type_label(string $type): string
{
    $key = moghare360_executive_go_no_go_decision_normalize($type);

    return moghare360_executive_go_no_go_decision_type_labels()[$key] ?? 'نامشخص';
}

function moghare360_executive_go_no_go_decision_status_label(string $status): string
{
    $key = moghare360_executive_go_no_go_decision_normalize($status);

    return moghare360_executive_go_no_go_decision_status_labels()[$key] ?? 'نامشخص';
}

function moghare360_executive_go_no_go_decision_generate_code(): string
{
    $random = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    return 'EGD-' . date('Ymd-His') . '-' . $random;
}

function moghare360_executive_go_no_go_decision_resolve_actor_user_id(): int
{
    return MOGHARE360_EXECUTIVE_GO_NO_GO_DEFAULT_USER_ID;
}

/**
 * @return array{schema_status: string, message: string, notes: list<string>}
 */
function moghare360_executive_go_no_go_decision_schema_status(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
            'notes' => ['اتصال به پایگاه داده برقرار نشد.'],
        ];
    }

    $decisionsOk = customer_core_table_exists($connection, MOGHARE360_EXECUTIVE_GO_NO_GO_DECISIONS_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_EXECUTIVE_GO_NO_GO_HISTORY_TABLE);

    if (!$decisionsOk || !$historyOk) {
        return [
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
            'notes' => [
                'اجرای دستی wave_9b_executive_go_no_go_decision_log.sql در SSMS روی MOGHARE360_ERP.',
                'decisions table: ' . ($decisionsOk ? 'OK' : 'MISSING'),
                'history table: ' . ($historyOk ? 'OK' : 'MISSING'),
            ],
        ];
    }

    return [
        'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
        'message' => 'پایه داده ثبت تصمیم مدیریتی Go/No-Go تأیید شد.',
        'notes' => [
            'Executive decisions table confirmed: dbo.' . MOGHARE360_EXECUTIVE_GO_NO_GO_DECISIONS_TABLE,
            'Executive decision history table confirmed: dbo.' . MOGHARE360_EXECUTIVE_GO_NO_GO_HISTORY_TABLE,
        ],
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   executive_readiness_status: string,
 *   wave6_status: string,
 *   wave7_status: string,
 *   wave8_status: string,
 *   go_interpretation: string,
 *   message: string,
 *   snapshot_available: bool
 * }
 */
function moghare360_executive_go_no_go_decision_fetch_current_snapshot(): array
{
    if (!function_exists('moghare360_executive_soft_run_readiness_evaluate')) {
        return [
            'ok' => false,
            'executive_readiness_status' => 'GO_REVIEW_REQUIRED',
            'wave6_status' => '',
            'wave7_status' => '',
            'wave8_status' => '',
            'go_interpretation' => '',
            'message' => 'داشبورد آمادگی مدیریتی WAVE 9A در دسترس نیست.',
            'snapshot_available' => false,
        ];
    }

    $evaluation = moghare360_executive_soft_run_readiness_evaluate();
    $summary = (array)($evaluation['summary'] ?? []);

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'executive_readiness_status' => (string)($evaluation['status'] ?? ''),
        'wave6_status' => (string)($summary['wave6_status'] ?? ''),
        'wave7_status' => (string)($summary['wave7_status'] ?? ''),
        'wave8_status' => (string)($summary['wave8_status'] ?? ''),
        'go_interpretation' => (string)($evaluation['go_interpretation'] ?? ''),
        'message' => (string)($evaluation['message'] ?? ''),
        'snapshot_available' => true,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_executive_go_no_go_decision_validate_payload(array $payload): array
{
    $errors = [];
    $clean = [];

    $readinessStatus = moghare360_executive_go_no_go_decision_normalize(
        (string)($payload['executive_readiness_status'] ?? '')
    );
    if (!in_array($readinessStatus, moghare360_executive_go_no_go_decision_allowed_readiness_statuses(), true)) {
        $errors[] = [
            'field' => 'executive_readiness_status',
            'rule' => 'allowed_readiness_status',
            'message' => 'وضعیت آمادگی مدیریتی نامعتبر است.',
        ];
    } else {
        $clean['executive_readiness_status'] = $readinessStatus;
    }

    foreach (['wave6_status' => 'wave6_status', 'wave7_status' => 'wave7_status', 'wave8_status' => 'wave8_status'] as $field => $key) {
        $value = trim((string)($payload[$field] ?? ''));
        $clean[$key] = $value === '' ? null : mb_substr($value, 0, 80);
    }

    $decisionType = moghare360_executive_go_no_go_decision_normalize((string)($payload['decision_type'] ?? ''));
    if (!in_array($decisionType, moghare360_executive_go_no_go_decision_allowed_types(), true)) {
        $errors[] = [
            'field' => 'decision_type',
            'rule' => 'allowed_type',
            'message' => 'نوع تصمیم نامعتبر است.',
        ];
    } else {
        $clean['decision_type'] = $decisionType;
    }

    $decisionStatus = moghare360_executive_go_no_go_decision_normalize(
        (string)($payload['decision_status'] ?? 'RECORDED')
    );
    if (!in_array($decisionStatus, moghare360_executive_go_no_go_decision_allowed_statuses(), true)) {
        $errors[] = [
            'field' => 'decision_status',
            'rule' => 'allowed_status',
            'message' => 'وضعیت تصمیم نامعتبر است.',
        ];
    } else {
        $clean['decision_status'] = $decisionStatus;
    }

    $title = trim((string)($payload['decision_title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 250) {
        $errors[] = [
            'field' => 'decision_title',
            'rule' => 'required_length',
            'message' => 'عنوان تصمیم الزامی است (حداکثر ۲۵۰ کاراکتر).',
        ];
    } else {
        $clean['decision_title'] = $title;
    }

    $summary = trim((string)($payload['decision_summary'] ?? ''));
    $clean['decision_summary'] = $summary === '' ? null : mb_substr($summary, 0, 1500);

    $reason = trim((string)($payload['management_reason'] ?? ''));
    if ($reason === '' || mb_strlen($reason) > 1500) {
        $errors[] = [
            'field' => 'management_reason',
            'rule' => 'required_length',
            'message' => 'دلیل مدیریتی الزامی است (حداکثر ۱۵۰۰ کاراکتر).',
        ];
    } else {
        $clean['management_reason'] = $reason;
    }

    $actionSummary = trim((string)($payload['required_action_summary'] ?? ''));
    $clean['required_action_summary'] = $actionSummary === '' ? null : mb_substr($actionSummary, 0, 1500);

    $riskNote = trim((string)($payload['risk_note'] ?? ''));
    $clean['risk_note'] = $riskNote === '' ? null : mb_substr($riskNote, 0, 1500);

    foreach (['finding_id' => 'finding_id', 'pilot_execution_id' => 'pilot_execution_id', 'decided_by_user_id' => 'decided_by_user_id'] as $field => $key) {
        $raw = trim((string)($payload[$field] ?? ''));
        if ($raw === '') {
            $clean[$key] = null;
        } elseif (!ctype_digit($raw) || (int)$raw < 1) {
            $errors[] = [
                'field' => $field,
                'rule' => 'optional_positive_number',
                'message' => 'شناسه ' . $field . ' باید عدد مثبت باشد یا خالی بماند.',
            ];
        } else {
            $clean[$key] = (int)$raw;
        }
    }

    $dueRaw = trim((string)($payload['decision_due_at'] ?? ''));
    if ($dueRaw === '') {
        $clean['decision_due_at'] = null;
    } else {
        $timestamp = strtotime($dueRaw);
        if ($timestamp === false) {
            $errors[] = [
                'field' => 'decision_due_at',
                'rule' => 'datetime',
                'message' => 'مهلت تصمیم باید تاریخ/زمان معتبر باشد یا خالی بماند.',
            ];
        } else {
            $clean['decision_due_at'] = gmdate('Y-m-d H:i:s', $timestamp);
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
function moghare360_executive_go_no_go_decision_validate_finding_exists($connection, int $findingId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی یافته در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_EXECUTIVE_GO_NO_GO_FINDING_TABLE)) {
        return ['ok' => true, 'exists' => true, 'notes' => ['جدول erp_soft_run_findings تأیید نشد — اعتبارسنجی مرجع یافته رد شد.']];
    }

    $count = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_findings WHERE finding_id = ?',
        [$findingId]
    ) ?? 0);

    if ($count < 1) {
        return ['ok' => false, 'exists' => false, 'notes' => ['یافته با این شناسه یافت نشد.']];
    }

    return ['ok' => true, 'exists' => true, 'notes' => ['Finding reference validated (read-only).']];
}

/**
 * @return array{ok: bool, exists: bool, notes: list<string>}
 */
function moghare360_executive_go_no_go_decision_validate_pilot_execution_exists($connection, int $executionId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی اجرا در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_EXECUTIVE_GO_NO_GO_PILOT_TABLE)) {
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

    return ['ok' => true, 'exists' => true, 'notes' => ['Pilot execution reference validated (read-only).']];
}

/**
 * @param array<string, mixed> $payload
 * @return array{
 *   ok: bool,
 *   decision_id: int|null,
 *   decision_code: string|null,
 *   executive_readiness_status: string|null,
 *   decision_type: string|null,
 *   decision_status: string|null,
 *   schema_status: string,
 *   message: string,
 *   errors: list<array{field: string, rule: string, message: string}>,
 *   notes: list<string>
 * }
 */
function moghare360_executive_go_no_go_decision_create(array $payload): array
{
    $validation = moghare360_executive_go_no_go_decision_validate_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'decision_id' => null,
            'decision_code' => null,
            'executive_readiness_status' => null,
            'decision_type' => null,
            'decision_status' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => 'اعتبارسنجی ثبت تصمیم ناموفق بود.',
            'errors' => $validation['errors'],
            'notes' => [MOGHARE360_EXECUTIVE_GO_NO_GO_INTERNAL_NOTICE],
        ];
    }

    $schema = moghare360_executive_go_no_go_decision_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY) {
        return [
            'ok' => false,
            'decision_id' => null,
            'decision_code' => null,
            'executive_readiness_status' => null,
            'decision_type' => null,
            'decision_status' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => array_merge(
                [MOGHARE360_EXECUTIVE_GO_NO_GO_INTERNAL_NOTICE],
                $schema['notes'] ?? []
            ),
        ];
    }

    $clean = $validation['clean'];
    $connection = customer_core_db();
    $notes = $schema['notes'] ?? [];

    if ($clean['finding_id'] !== null) {
        $findingCheck = moghare360_executive_go_no_go_decision_validate_finding_exists(
            $connection,
            (int)$clean['finding_id']
        );
        $notes = array_merge($notes, $findingCheck['notes']);

        if (!$findingCheck['ok']) {
            return [
                'ok' => false,
                'decision_id' => null,
                'decision_code' => null,
                'executive_readiness_status' => null,
                'decision_type' => null,
                'decision_status' => null,
                'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
                'message' => 'ثبت تصمیم به‌دلیل عدم تأیید مرجع یافته مسدود شد.',
                'errors' => [[
                    'field' => 'finding_id',
                    'rule' => 'finding_not_found',
                    'message' => 'یافته مرجع یافت نشد.',
                ]],
                'notes' => $notes,
            ];
        }
    }

    if ($clean['pilot_execution_id'] !== null) {
        $pilotCheck = moghare360_executive_go_no_go_decision_validate_pilot_execution_exists(
            $connection,
            (int)$clean['pilot_execution_id']
        );
        $notes = array_merge($notes, $pilotCheck['notes']);

        if (!$pilotCheck['ok']) {
            return [
                'ok' => false,
                'decision_id' => null,
                'decision_code' => null,
                'executive_readiness_status' => null,
                'decision_type' => null,
                'decision_status' => null,
                'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
                'message' => 'ثبت تصمیم به‌دلیل عدم تأیید مرجع اجرای پایلوت مسدود شد.',
                'errors' => [[
                    'field' => 'pilot_execution_id',
                    'rule' => 'pilot_execution_not_found',
                    'message' => 'اجرای پایلوت مرجع یافت نشد.',
                ]],
                'notes' => $notes,
            ];
        }
    }

    $decisionCode = moghare360_executive_go_no_go_decision_generate_code();
    $actorUserId = moghare360_executive_go_no_go_decision_resolve_actor_user_id();

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'decision_id' => null,
            'decision_code' => null,
            'executive_readiness_status' => null,
            'decision_type' => null,
            'decision_status' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
            'message' => 'شروع تراکنش ثبت تصمیم ناموفق بود.',
            'errors' => [],
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_executive_soft_run_decisions (
                decision_code,
                executive_readiness_status,
                wave6_status,
                wave7_status,
                wave8_status,
                decision_type,
                decision_status,
                decision_title,
                decision_summary,
                management_reason,
                required_action_summary,
                risk_note,
                finding_id,
                pilot_execution_id,
                decided_by_user_id,
                decision_due_at,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $decisionCode,
                $clean['executive_readiness_status'],
                $clean['wave6_status'],
                $clean['wave7_status'],
                $clean['wave8_status'],
                $clean['decision_type'],
                $clean['decision_status'],
                $clean['decision_title'],
                $clean['decision_summary'],
                $clean['management_reason'],
                $clean['required_action_summary'],
                $clean['risk_note'],
                $clean['finding_id'],
                $clean['pilot_execution_id'],
                $clean['decided_by_user_id'],
                $clean['decision_due_at'],
                $actorUserId,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج رکورد تصمیم ناموفق بود.');
        }

        $decisionId = customer_core_scope_identity($connection);

        if ($decisionId === null || (int)$decisionId < 1) {
            $decisionId = customer_core_scalar(
                $connection,
                'SELECT decision_id FROM dbo.erp_executive_soft_run_decisions WHERE decision_code = ?',
                [$decisionCode]
            );
        }

        if ($decisionId === null || (int)$decisionId < 1) {
            throw new RuntimeException('شناسه رکورد تصمیم پس از درج بازیابی نشد.');
        }

        $decisionId = (int)$decisionId;

        $historyOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_executive_soft_run_decision_history (
                decision_id,
                old_decision_status,
                new_decision_status,
                old_decision_type,
                new_decision_type,
                change_reason,
                changed_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $decisionId,
                null,
                $clean['decision_status'],
                null,
                $clean['decision_type'],
                'Initial executive Soft Run Go/No-Go decision record created',
                $actorUserId,
            ]
        );

        if ($historyOk === false) {
            throw new RuntimeException('درج تاریخچه تصمیم ناموفق بود.');
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('تأیید تراکنش ثبت تصمیم ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'decision_id' => $decisionId,
            'decision_code' => $decisionCode,
            'executive_readiness_status' => $clean['executive_readiness_status'],
            'decision_type' => $clean['decision_type'],
            'decision_status' => $clean['decision_status'],
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
            'message' => 'رکورد تصمیم مدیریتی Go/No-Go با موفقیت ثبت شد.',
            'errors' => [],
            'notes' => array_merge($notes, [MOGHARE360_EXECUTIVE_GO_NO_GO_INTERNAL_NOTICE]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'decision_id' => null,
            'decision_code' => null,
            'executive_readiness_status' => null,
            'decision_type' => null,
            'decision_status' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
            'message' => 'ثبت تصمیم ناموفق بود.',
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
 * @return array{
 *   ok: bool,
 *   records: list<array<string, mixed>>,
 *   counts: array<string, array<string, int>>,
 *   schema_status: string,
 *   message: string
 * }
 */
function moghare360_executive_go_no_go_decision_fetch_recent(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $schema = moghare360_executive_go_no_go_decision_schema_status();
    $emptyCounts = [
        'decision_type' => [],
        'decision_status' => [],
        'executive_readiness_status' => [],
    ];

    if (($schema['schema_status'] ?? '') !== MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => $emptyCounts,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'records' => [],
            'counts' => $emptyCounts,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $records = customer_core_fetch_rows(
        $connection,
        'SELECT TOP (' . $limit . ')
            decision_id,
            decision_code,
            executive_readiness_status,
            wave6_status,
            wave7_status,
            wave8_status,
            decision_type,
            decision_status,
            decision_title,
            finding_id,
            pilot_execution_id,
            created_at
         FROM dbo.erp_executive_soft_run_decisions
         ORDER BY decision_id DESC'
    ) ?? [];

    $typeCounts = [];
    foreach (moghare360_executive_go_no_go_decision_allowed_types() as $type) {
        $typeCounts[$type] = 0;
    }

    $statusCounts = [];
    foreach (moghare360_executive_go_no_go_decision_allowed_statuses() as $status) {
        $statusCounts[$status] = 0;
    }

    $readinessCounts = [];
    foreach (moghare360_executive_go_no_go_decision_allowed_readiness_statuses() as $status) {
        $readinessCounts[$status] = 0;
    }

    foreach ([
        ['decision_type', $typeCounts],
        ['decision_status', $statusCounts],
        ['executive_readiness_status', $readinessCounts],
    ] as [$column, &$bucket]) {
        $rows = customer_core_fetch_rows(
            $connection,
            'SELECT ' . $column . ', COUNT(*) AS cnt
             FROM dbo.erp_executive_soft_run_decisions
             GROUP BY ' . $column
        ) ?? [];

        foreach ($rows as $row) {
            $key = moghare360_executive_go_no_go_decision_normalize((string)($row[$column] ?? ''));
            if (array_key_exists($key, $bucket)) {
                $bucket[$key] = (int)($row['cnt'] ?? 0);
            }
        }
    }

    return [
        'ok' => true,
        'records' => $records,
        'counts' => [
            'decision_type' => $typeCounts,
            'decision_status' => $statusCounts,
            'executive_readiness_status' => $readinessCounts,
        ],
        'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
        'message' => 'فهرست اخیر تصمیم‌های مدیریتی Go/No-Go بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, record: array<string, mixed>|null, schema_status: string, message: string}
 */
function moghare360_executive_go_no_go_decision_fetch_detail(int $decisionId): array
{
    if ($decisionId < 1) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => 'شناسه تصمیم نامعتبر است.',
        ];
    }

    $schema = moghare360_executive_go_no_go_decision_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT
            decision_id,
            decision_code,
            executive_readiness_status,
            wave6_status,
            wave7_status,
            wave8_status,
            decision_type,
            decision_status,
            decision_title,
            decision_summary,
            management_reason,
            required_action_summary,
            risk_note,
            finding_id,
            pilot_execution_id,
            decided_by_user_id,
            decision_due_at,
            created_at,
            updated_at,
            created_by_user_id,
            updated_by_user_id
         FROM dbo.erp_executive_soft_run_decisions
         WHERE decision_id = ?',
        [$decisionId]
    ) ?? [];

    if ($rows === []) {
        return [
            'ok' => false,
            'record' => null,
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
            'message' => 'رکورد تصمیم یافت نشد.',
        ];
    }

    return [
        'ok' => true,
        'record' => $rows[0],
        'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
        'message' => 'جزئیات تصمیم مدیریتی بازیابی شد.',
    ];
}

/**
 * @return array{ok: bool, history: list<array<string, mixed>>, schema_status: string, message: string}
 */
function moghare360_executive_go_no_go_decision_fetch_history(int $decisionId): array
{
    if ($decisionId < 1) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => 'شناسه تصمیم نامعتبر است.',
        ];
    }

    $schema = moghare360_executive_go_no_go_decision_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED,
            'message' => MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE,
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'history' => [],
            'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_ERROR,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
        ];
    }

    $history = customer_core_fetch_rows(
        $connection,
        'SELECT
            history_id,
            decision_id,
            old_decision_status,
            new_decision_status,
            old_decision_type,
            new_decision_type,
            change_reason,
            changed_at,
            changed_by_user_id
         FROM dbo.erp_executive_soft_run_decision_history
         WHERE decision_id = ?
         ORDER BY history_id ASC',
        [$decisionId]
    ) ?? [];

    return [
        'ok' => true,
        'history' => $history,
        'schema_status' => MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_READY,
        'message' => 'تاریخچه تصمیم مدیریتی بازیابی شد.',
    ];
}
