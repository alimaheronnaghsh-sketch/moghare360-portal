<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Contract Authorization Runtime Helper (Wave 3A)
 *
 * Internal controlled authorization — NOT final legal e-signature.
 * No public portal activation. Uses local-safe DB pattern when schema is READY.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-validation-engine.php';

const MOGHARE360_CONTRACT_AUTH_SCHEMA_READY = 'READY';
const MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED = 'BLOCKED';

const MOGHARE360_CONTRACT_AUTH_TABLE = 'erp_jobcard_authorizations';
const MOGHARE360_CONTRACT_AUTH_HISTORY_TABLE = 'erp_jobcard_authorization_history';
const MOGHARE360_CONTRACT_AUTH_JOBCARD_TABLE = 'erp_jobcards';

const MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE =
    'Contract authorization DB foundation is not confirmed yet.';

const MOGHARE360_CONTRACT_AUTH_INTERNAL_NOTICE =
    'This is internal controlled authorization, not final legal e-signature.';

/**
 * @return list<string>
 */
function moghare360_contract_authorization_candidate_tables(): array
{
    return [
        'erp_jobcard_authorizations',
        'erp_jobcard_contracts',
        'erp_contract_authorizations',
        'erp_jobcard_approval_records',
    ];
}

/**
 * @return list<string>
 */
function moghare360_contract_authorization_allowed_types(): array
{
    return [
        'acceptance_contract',
        'repair_permission',
        'part_purchase_approval',
        'additional_cost_approval',
        'delivery_approval',
        'diagnostic_authorization',
        'other',
    ];
}

/**
 * @return list<string>
 */
function moghare360_contract_authorization_allowed_statuses(): array
{
    return [
        'draft',
        'pending_customer_approval',
        'approved',
        'rejected',
        'cancelled',
    ];
}

/**
 * @return list<string>
 */
function moghare360_contract_authorization_allowed_methods(): array
{
    return [
        'internal_operator',
        'phone_confirmation',
        'in_person_confirmation',
        'written_form',
        'future_customer_portal_pending',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_contract_authorization_type_labels(): array
{
    return [
        'acceptance_contract' => 'قرارداد پذیرش',
        'repair_permission' => 'اجازه تعمیر',
        'part_purchase_approval' => 'تأیید خرید قطعه',
        'additional_cost_approval' => 'تأیید هزینه اضافی',
        'delivery_approval' => 'تأیید تحویل',
        'diagnostic_authorization' => 'مجوز تشخیص',
        'other' => 'سایر',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_contract_authorization_status_labels(): array
{
    return [
        'draft' => 'پیش‌نویس',
        'pending_customer_approval' => 'در انتظار تأیید مشتری',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
        'cancelled' => 'لغو شده',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_contract_authorization_method_labels(): array
{
    return [
        'internal_operator' => 'اپراتور داخلی',
        'phone_confirmation' => 'تأیید تلفنی',
        'in_person_confirmation' => 'تأیید حضوری',
        'written_form' => 'فرم مکتوب',
        'future_customer_portal_pending' => 'در انتظار پورتال مشتری (آینده)',
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_contract_authorization_validate_payload(array $payload): array
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

    $authType = trim((string)($payload['authorization_type'] ?? ''));
    if (!in_array($authType, moghare360_contract_authorization_allowed_types(), true)) {
        $errors[] = [
            'field' => 'authorization_type',
            'rule' => 'allowed_type',
            'message' => 'نوع مجوز/قرارداد نامعتبر است.',
        ];
    } else {
        $clean['authorization_type'] = $authType;
    }

    $authStatus = trim((string)($payload['authorization_status'] ?? ''));
    if (!in_array($authStatus, moghare360_contract_authorization_allowed_statuses(), true)) {
        $errors[] = [
            'field' => 'authorization_status',
            'rule' => 'allowed_status',
            'message' => 'وضعیت مجوز نامعتبر است.',
        ];
    } else {
        $clean['authorization_status'] = $authStatus;
    }

    $authMethod = trim((string)($payload['authorization_method'] ?? ''));
    if (!in_array($authMethod, moghare360_contract_authorization_allowed_methods(), true)) {
        $errors[] = [
            'field' => 'authorization_method',
            'rule' => 'allowed_method',
            'message' => 'روش مجوز نامعتبر است.',
        ];
    } else {
        $clean['authorization_method'] = $authMethod;
    }

    $customerName = trim((string)($payload['customer_name'] ?? ''));
    if ($customerName === '' || mb_strlen($customerName) > 200) {
        $errors[] = [
            'field' => 'customer_name',
            'rule' => 'required_length',
            'message' => 'نام مشتری الزامی است (حداکثر ۲۰۰ کاراکتر).',
        ];
    } else {
        $clean['customer_name'] = $customerName;
    }

    $customerMobile = trim((string)($payload['customer_mobile'] ?? ''));
    if (!moghare360_validation_mobile_ir($customerMobile)) {
        $errors[] = [
            'field' => 'customer_mobile',
            'rule' => 'mobile_ir',
            'message' => 'شماره موبایل مشتری نامعتبر است (مثال: 09123456789).',
        ];
    } else {
        $clean['customer_mobile'] = moghare360_validation_clean_mobile_ir($customerMobile);
    }

    $authNote = trim((string)($payload['authorization_note'] ?? ''));
    if (mb_strlen($authNote) > 2000) {
        $errors[] = [
            'field' => 'authorization_note',
            'rule' => 'max_length',
            'message' => 'یادداشت مجوز حداکثر ۲۰۰۰ کاراکتر مجاز است.',
        ];
    } else {
        $clean['authorization_note'] = $authNote;
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'clean' => $clean,
    ];
}

/**
 * @return array{schema_status: string, message: string, table: string|null, history_table_ok: bool, notes: list<string>}
 */
function moghare360_contract_authorization_schema_status(): array
{
    $notes = [];
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'message' => MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE,
            'table' => null,
            'history_table_ok' => false,
            'notes' => ['اتصال به پایگاه داده برقرار نشد.'],
        ];
    }

    $resolvedTable = moghare360_contract_authorization_resolve_table($connection);

    if ($resolvedTable === null) {
        return [
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'message' => MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE,
            'table' => null,
            'history_table_ok' => false,
            'notes' => [
                'جدول مجوز/قرارداد کارت کار با ستون‌های ایمن یافت نشد.',
                'اجرای دستی wave_3a_contract_authorization_foundation.sql در SSMS پس از تأیید ChatGPT.',
            ],
        ];
    }

    $notes[] = 'Safe authorization table confirmed: dbo.' . $resolvedTable;

    $historyOk = customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_HISTORY_TABLE);
    if ($historyOk) {
        $notes[] = 'Authorization history table confirmed: dbo.' . MOGHARE360_CONTRACT_AUTH_HISTORY_TABLE;
    } else {
        $notes[] = 'Authorization history table not confirmed — audit history write skipped.';
    }

    return [
        'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
        'message' => 'پایه داده مجوز/قرارداد کارت کار تأیید شد.',
        'table' => $resolvedTable,
        'history_table_ok' => $historyOk,
        'notes' => $notes,
    ];
}

function moghare360_contract_authorization_resolve_table($connection): ?string
{
    if ($connection === false) {
        return null;
    }

    foreach (moghare360_contract_authorization_candidate_tables() as $tableName) {
        if (!customer_core_table_exists($connection, $tableName)) {
            continue;
        }

        $required = [
            'jobcard_id',
            'authorization_type',
            'authorization_status',
            'authorization_method',
            'customer_name',
            'customer_mobile',
        ];

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
 * @return array{ok: bool, exists: bool, notes: list<string>}
 */
function moghare360_contract_authorization_validate_jobcard_exists($connection, int $jobcardId): array
{
    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['اتصال DB برای اعتبارسنجی کارت کار در دسترس نیست.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_JOBCARD_TABLE)) {
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
 * @param array<string, mixed> $payload
 * @return array{ok: bool, authorization_id: int|string|null, schema_status: string, message: string, errors: list<array{field: string, rule: string, message: string}>, notes: list<string>}
 */
function moghare360_contract_authorization_create(array $payload): array
{
    $validation = moghare360_contract_authorization_validate_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'authorization_id' => null,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'message' => 'اعتبارسنجی مجوز/قرارداد ناموفق بود.',
            'errors' => $validation['errors'],
            'notes' => [MOGHARE360_CONTRACT_AUTH_INTERNAL_NOTICE],
        ];
    }

    $clean = $validation['clean'];
    $schema = moghare360_contract_authorization_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_CONTRACT_AUTH_SCHEMA_READY || ($schema['table'] ?? null) === null) {
        return [
            'ok' => false,
            'authorization_id' => null,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'message' => MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => array_merge(
                [MOGHARE360_CONTRACT_AUTH_INTERNAL_NOTICE],
                $schema['notes'] ?? []
            ),
        ];
    }

    $connection = customer_core_db();
    $jobcardId = (int)$clean['jobcard_id'];
    $jobcardCheck = moghare360_contract_authorization_validate_jobcard_exists($connection, $jobcardId);
    $notes = $jobcardCheck['notes'];

    if (!$jobcardCheck['ok']) {
        return [
            'ok' => false,
            'authorization_id' => null,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
            'message' => 'ثبت مجوز به‌دلیل عدم تأیید مرجع کارت کار مسدود شد.',
            'errors' => [[
                'field' => 'jobcard_id',
                'rule' => 'jobcard_not_found',
                'message' => 'کارت کار مرجع یافت نشد.',
            ]],
            'notes' => $notes,
        ];
    }

    $tableName = (string)$schema['table'];
    $noteValue = (string)($clean['authorization_note'] ?? '');

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'authorization_id' => null,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
            'message' => 'شروع تراکنش ثبت مجوز ناموفق بود.',
            'errors' => [],
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.' . $tableName . ' (
                jobcard_id, authorization_type, authorization_status, authorization_method,
                customer_name, customer_mobile, authorization_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $jobcardId,
                $clean['authorization_type'],
                $clean['authorization_status'],
                $clean['authorization_method'],
                $clean['customer_name'],
                $clean['customer_mobile'],
                $noteValue !== '' ? $noteValue : null,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج رکورد مجوز/قرارداد ناموفق بود.');
        }

        $authorizationId = customer_core_scope_identity($connection);

        if ($authorizationId === null || (int)$authorizationId < 1) {
            $authorizationId = customer_core_scalar(
                $connection,
                'SELECT TOP 1 authorization_id FROM dbo.' . $tableName . ' WHERE jobcard_id = ? ORDER BY authorization_id DESC',
                [$jobcardId]
            );
        }

        if (($schema['history_table_ok'] ?? false) === true) {
            $historyWritten = moghare360_contract_authorization_write_history(
                $connection,
                (int)$authorizationId,
                $jobcardId,
                $clean['authorization_status'],
                $clean['authorization_type']
            );

            if ($historyWritten) {
                $notes[] = 'erp_jobcard_authorization_history AUTHORIZATION_REGISTERED written';
            }
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش مجوز ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'authorization_id' => $authorizationId,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
            'message' => 'رکورد مجوز/قرارداد داخلی با موفقیت ثبت شد.',
            'errors' => [],
            'notes' => array_merge($notes, [MOGHARE360_CONTRACT_AUTH_INTERNAL_NOTICE]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'authorization_id' => null,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
            'message' => 'ثبت مجوز/قرارداد ناموفق بود.',
            'errors' => [],
            'notes' => array_merge($notes, [$exception->getMessage()]),
        ];
    }
}

function moghare360_contract_authorization_write_history(
    $connection,
    int $authorizationId,
    int $jobcardId,
    string $newStatus,
    string $authType
): bool {
    if ($connection === false) {
        return false;
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_HISTORY_TABLE)) {
        return false;
    }

    $eventBy = customer_core_safe_current_user();

    return customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_jobcard_authorization_history (
            authorization_id, jobcard_id, event_code, event_title, event_notes,
            old_status, new_status, event_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $authorizationId,
            $jobcardId,
            'AUTHORIZATION_REGISTERED',
            'Internal contract authorization registered',
            'Type: ' . $authType . ' — ' . MOGHARE360_CONTRACT_AUTH_INTERNAL_NOTICE,
            null,
            $newStatus,
            $eventBy,
        ]
    ) !== false;
}

/**
 * @return array{ok: bool, schema_status: string, records: list<array<string, string>>, message: string, errors: list<string>, notes: list<string>}
 */
function moghare360_contract_authorization_list_by_jobcard(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'records' => [],
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
            'notes' => [],
        ];
    }

    $schema = moghare360_contract_authorization_schema_status();

    if (($schema['schema_status'] ?? '') !== MOGHARE360_CONTRACT_AUTH_SCHEMA_READY || ($schema['table'] ?? null) === null) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE,
            'errors' => [],
            'notes' => $schema['notes'] ?? [],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
            'records' => [],
            'message' => MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE,
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
        'SELECT authorization_id, jobcard_id, authorization_type, authorization_status, authorization_method,
                customer_name, customer_mobile, authorization_note,
                CONVERT(VARCHAR(30), created_at, 120) AS created_at,
                CONVERT(VARCHAR(30), updated_at, 120) AS updated_at
         FROM dbo.' . $tableName . '
         ' . $whereClause . '
         ORDER BY created_at DESC, authorization_id DESC',
        $params
    );

    return [
        'ok' => true,
        'schema_status' => MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
        'records' => $rows,
        'message' => '',
        'errors' => [],
        'notes' => $schema['notes'] ?? [],
    ];
}
