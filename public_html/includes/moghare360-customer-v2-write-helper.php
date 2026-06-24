<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Customer Create v2 DB Write Helper (Wave 1D)
 *
 * Reuses existing erp_customers / erp_customer_phones / erp_customer_core_history.
 * No config.php · no schema changes · prepared statements only.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_CUSTOMER_V2_TABLE = 'erp_customers';
const MOGHARE360_CUSTOMER_V2_PHONES_TABLE = 'erp_customer_phones';
const MOGHARE360_CUSTOMER_V2_HISTORY_TABLE = 'erp_customer_core_history';

/**
 * @return list<string>
 */
function moghare360_customer_v2_required_columns(): array
{
    return [
        'customer_id',
        'customer_code',
        'customer_type',
        'full_name',
        'primary_mobile',
        'lifecycle_state',
        'created_by_user_id',
    ];
}

/**
 * @return array{ready: bool, reason: string}
 */
function moghare360_customer_v2_schema_ready($connection): array
{
    if ($connection === false) {
        return ['ready' => false, 'reason' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CUSTOMER_V2_TABLE)) {
        return ['ready' => false, 'reason' => 'جدول erp_customers یافت نشد.'];
    }

    foreach (moghare360_customer_v2_required_columns() as $column) {
        if (!customer_core_column_exists($connection, MOGHARE360_CUSTOMER_V2_TABLE, $column)) {
            return ['ready' => false, 'reason' => 'ستون الزامی یافت نشد: ' . $column];
        }
    }

    return ['ready' => true, 'reason' => ''];
}

function moghare360_customer_v2_generate_code(): string
{
    return 'V2C-' . date('Ymd-His') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * @param array<string, mixed> $clean
 */
function moghare360_customer_v2_compose_notes(array $clean): ?string
{
    $segments = [];
    $channel = trim((string)($clean['customer_channel'] ?? ''));
    $class = trim((string)($clean['customer_class'] ?? ''));
    $userNotes = trim((string)($clean['notes'] ?? ''));

    if ($channel !== '') {
        $segments[] = 'channel:' . $channel;
    }

    if ($class !== '') {
        $segments[] = 'class:' . $class;
    }

    if ($userNotes !== '') {
        $segments[] = $userNotes;
    }

    if ($segments === []) {
        return null;
    }

    return implode(' | ', $segments);
}

/**
 * @param array<string, mixed> $clean Validated clean payload from customer_create_v2
 * @return array{ok: bool, customer_id: int|null, customer_code: string|null, message: string, error: string, audit_note: string}
 */
function moghare360_customer_v2_write(array $clean): array
{
    $connection = customer_core_db();

    $schema = moghare360_customer_v2_schema_ready($connection);

    if (!$schema['ready']) {
        return [
            'ok' => false,
            'customer_id' => null,
            'customer_code' => null,
            'message' => '',
            'error' => 'DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED',
            'audit_note' => $schema['reason'],
        ];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;

    $fullName = trim((string)($clean['customer_name'] ?? ''));
    $mobile = trim((string)($clean['mobile'] ?? ''));
    $nationalId = trim((string)($clean['national_id'] ?? ''));
    $notes = moghare360_customer_v2_compose_notes($clean);
    $customerCode = moghare360_customer_v2_generate_code();

    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('شروع تراکنش ناموفق بود.');
        }

        $transactionStarted = true;

        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_customers (
                customer_code,
                customer_type,
                full_name,
                national_id,
                primary_mobile,
                notes,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $customerCode,
                'PERSON',
                $fullName,
                $nationalId !== '' ? $nationalId : null,
                $mobile,
                $notes,
                'ACTIVE',
                $userId,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج مشتری در erp_customers ناموفق بود.');
        }

        $customerId = customer_core_scope_identity($connection);

        if ($customerId === null || $customerId < 1) {
            $customerId = (int)(customer_core_scalar(
                $connection,
                'SELECT customer_id FROM dbo.erp_customers WHERE customer_code = ?',
                [$customerCode]
            ) ?? 0);
        }

        if ($customerId < 1) {
            throw new RuntimeException('شناسه مشتری پس از درج دریافت نشد.');
        }

        $phoneWritten = false;

        if (
            customer_core_table_exists($connection, MOGHARE360_CUSTOMER_V2_PHONES_TABLE)
            && customer_core_column_exists($connection, MOGHARE360_CUSTOMER_V2_PHONES_TABLE, 'customer_id')
            && customer_core_column_exists($connection, MOGHARE360_CUSTOMER_V2_PHONES_TABLE, 'phone_number')
        ) {
            $phoneOk = customer_core_execute(
                $connection,
                'INSERT INTO dbo.erp_customer_phones (
                    customer_id,
                    phone_type,
                    phone_number,
                    is_primary,
                    lifecycle_state,
                    created_by_user_id
                ) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $customerId,
                    'MOBILE',
                    $mobile,
                    1,
                    'ACTIVE',
                    $userId,
                ]
            );

            if ($phoneOk === false) {
                throw new RuntimeException('درج شماره موبایل در erp_customer_phones ناموفق بود.');
            }

            $phoneWritten = true;
        }

        $auditNote = 'Audit write pending safe audit target confirmation';

        if (customer_core_table_exists($connection, MOGHARE360_CUSTOMER_V2_HISTORY_TABLE)) {
            $historyOk = customer_core_insert_history(
                $connection,
                'erp_customers',
                $customerId,
                'CUSTOMER_CREATE_V2',
                'Customer Create v2 — Wave 1D controlled write',
                null,
                json_encode([
                    'customer_code' => $customerCode,
                    'full_name' => $fullName,
                    'primary_mobile' => $mobile,
                    'national_id' => $nationalId !== '' ? $nationalId : null,
                    'customer_channel' => $clean['customer_channel'] ?? null,
                    'customer_class' => $clean['customer_class'] ?? null,
                ], JSON_UNESCAPED_UNICODE)
            );

            if ($historyOk) {
                $auditNote = 'erp_customer_core_history written';
            }
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        $message = 'مشتری با موفقیت در erp_customers ثبت شد.';

        if ($phoneWritten) {
            $message .= ' شماره موبایل در erp_customer_phones ثبت شد.';
        }

        return [
            'ok' => true,
            'customer_id' => $customerId,
            'customer_code' => $customerCode,
            'message' => $message,
            'error' => '',
            'audit_note' => $auditNote,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted && $connection !== false) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);
        }

        return [
            'ok' => false,
            'customer_id' => null,
            'customer_code' => null,
            'message' => '',
            'error' => $exception->getMessage(),
            'audit_note' => '',
        ];
    }
}
