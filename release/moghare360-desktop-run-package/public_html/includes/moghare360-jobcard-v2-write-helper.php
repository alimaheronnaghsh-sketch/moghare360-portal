<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Create v2 DB Write Helper (Wave 1F)
 *
 * Reuses existing erp_jobcards and erp_jobcard_change_history.
 * No config.php · no schema changes · prepared statements only.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_JOBCARD_V2_TABLE = 'erp_jobcards';
const MOGHARE360_JOBCARD_V2_HISTORY_TABLE = 'erp_jobcard_change_history';
const MOGHARE360_JOBCARD_V2_RELATIONS_TABLE = 'erp_customer_vehicle_relations';

/**
 * @return list<string>
 */
function moghare360_jobcard_v2_required_columns(): array
{
    return [
        'jobcard_id',
        'jobcard_number',
        'customer_id',
        'vehicle_id',
        'jobcard_status',
        'reception_at',
        'lifecycle_state',
        'created_by_user_id',
    ];
}

/**
 * @return array{ready: bool, reason: string}
 */
function moghare360_jobcard_v2_schema_ready($connection): array
{
    if ($connection === false) {
        return ['ready' => false, 'reason' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_V2_TABLE)) {
        return ['ready' => false, 'reason' => 'جدول erp_jobcards یافت نشد.'];
    }

    foreach (moghare360_jobcard_v2_required_columns() as $column) {
        if (!customer_core_column_exists($connection, MOGHARE360_JOBCARD_V2_TABLE, $column)) {
            return ['ready' => false, 'reason' => 'ستون الزامی یافت نشد: ' . $column];
        }
    }

    return ['ready' => true, 'reason' => ''];
}

function moghare360_jobcard_v2_generate_number(): string
{
    return 'V2J-' . date('Ymd-His') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * @return string|null
 */
function moghare360_jobcard_v2_normalize_reception_at(string $receptionDate): ?string
{
    $receptionDate = trim($receptionDate);

    if ($receptionDate === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $receptionDate) === 1) {
        return $receptionDate . ' 00:00:00.000';
    }

    return $receptionDate;
}

/**
 * @param array<string, mixed> $clean
 */
function moghare360_jobcard_v2_compose_internal_notes(array $clean, ?string $extraNotes = null): ?string
{
    $segments = [];
    $jobcardType = trim((string)($clean['jobcard_type'] ?? ''));
    $serviceCategory = trim((string)($clean['service_category'] ?? ''));

    if ($jobcardType !== '') {
        $segments[] = 'jobcard_type:' . $jobcardType;
    }

    if ($serviceCategory !== '') {
        $segments[] = 'service_category:' . $serviceCategory;
    }

    if ($extraNotes !== null && trim($extraNotes) !== '') {
        $segments[] = trim($extraNotes);
    }

    if ($segments === []) {
        return null;
    }

    return implode(' | ', $segments);
}

/**
 * @return array{ok: bool, customer_ok: bool, vehicle_ok: bool, relation_id: int|null, notes: list<string>}
 */
function moghare360_jobcard_v2_validate_references($connection, int $customerId, int $vehicleId): array
{
    $notes = [];
    $customerOk = false;
    $vehicleOk = false;
    $relationId = null;

    if (customer_core_table_exists($connection, 'erp_customers')) {
        $customerCount = customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_customers WHERE customer_id = ?',
            [$customerId]
        );
        $customerOk = $customerCount !== null && (int)$customerCount > 0;
    } else {
        $notes[] = 'Reference validation pending: erp_customers table not confirmed.';
    }

    if (customer_core_table_exists($connection, 'erp_vehicles')) {
        $vehicleCount = customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_vehicles WHERE vehicle_id = ?',
            [$vehicleId]
        );
        $vehicleOk = $vehicleCount !== null && (int)$vehicleCount > 0;
    } else {
        $notes[] = 'Reference validation pending: erp_vehicles table not confirmed.';
    }

    if (
        $customerOk
        && $vehicleOk
        && customer_core_table_exists($connection, MOGHARE360_JOBCARD_V2_RELATIONS_TABLE)
    ) {
        $relationValue = customer_core_scalar(
            $connection,
            'SELECT TOP 1 relation_id
             FROM dbo.erp_customer_vehicle_relations
             WHERE customer_id = ?
               AND vehicle_id = ?
               AND lifecycle_state = \'ACTIVE\'
             ORDER BY relation_id DESC',
            [$customerId, $vehicleId]
        );

        if ($relationValue !== null && (int)$relationValue > 0) {
            $relationId = (int)$relationValue;
        } else {
            $notes[] = 'Active customer/vehicle relation not found — relation_id pending.';
        }
    }

    if (!$customerOk) {
        $notes[] = 'customer_id not found in erp_customers.';
    }

    if (!$vehicleOk) {
        $notes[] = 'vehicle_id not found in erp_vehicles.';
    }

    return [
        'ok' => $customerOk && $vehicleOk,
        'customer_ok' => $customerOk,
        'vehicle_ok' => $vehicleOk,
        'relation_id' => $relationId,
        'notes' => $notes,
    ];
}

/**
 * @param array<string, mixed> $clean Validated clean payload from jobcard_create_v2
 * @param string $extraNotes Optional form notes not in validation rules
 * @return array{ok: bool, jobcard_id: int|null, jobcard_number: string|null, message: string, error: string, notes: list<string>}
 */
function moghare360_jobcard_v2_write(array $clean, string $extraNotes = ''): array
{
    $connection = customer_core_db();
    $resultNotes = [];

    $schema = moghare360_jobcard_v2_schema_ready($connection);

    if (!$schema['ready']) {
        return [
            'ok' => false,
            'jobcard_id' => null,
            'jobcard_number' => null,
            'message' => '',
            'error' => 'DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED',
            'notes' => [$schema['reason']],
        ];
    }

    $customerId = (int)($clean['customer_id'] ?? 0);
    $vehicleId = (int)($clean['vehicle_id'] ?? 0);

    if ($customerId < 1 || $vehicleId < 1) {
        return [
            'ok' => false,
            'jobcard_id' => null,
            'jobcard_number' => null,
            'message' => '',
            'error' => 'شناسه مشتری یا خودرو معتبر نیست.',
            'notes' => [],
        ];
    }

    $referenceCheck = moghare360_jobcard_v2_validate_references($connection, $customerId, $vehicleId);
    $resultNotes = array_merge($resultNotes, $referenceCheck['notes']);

    if (!$referenceCheck['ok']) {
        return [
            'ok' => false,
            'jobcard_id' => null,
            'jobcard_number' => null,
            'message' => '',
            'error' => 'ارجاع مشتری/خودرو تأیید نشد.',
            'notes' => $resultNotes,
        ];
    }

    $relationId = $referenceCheck['relation_id'];
    $relationColumnExists = customer_core_column_exists($connection, MOGHARE360_JOBCARD_V2_TABLE, 'relation_id');

    if ($relationColumnExists && ($relationId === null || $relationId < 1)) {
        return [
            'ok' => false,
            'jobcard_id' => null,
            'jobcard_number' => null,
            'message' => '',
            'error' => 'رابطه فعال مشتری-خودرو برای درج کارت کار یافت نشد.',
            'notes' => $resultNotes,
        ];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;

    $jobcardNumber = moghare360_jobcard_v2_generate_number();
    $jobcardStatus = 'RECEIVED';
    $receptionAt = moghare360_jobcard_v2_normalize_reception_at((string)($clean['reception_date'] ?? ''));

    if ($receptionAt === null) {
        $receptionAt = customer_core_scalar(
            $connection,
            'SELECT CONVERT(VARCHAR(23), SYSUTCDATETIME(), 121) AS reception_at'
        ) ?? date('Y-m-d H:i:s') . '.000';
    }

    $odometerRaw = trim((string)($clean['odometer'] ?? ''));
    $intakeMileage = $odometerRaw !== '' && ctype_digit($odometerRaw) ? (int)$odometerRaw : null;
    $complaintText = trim((string)($clean['complaint_text'] ?? ''));
    $internalNotes = moghare360_jobcard_v2_compose_internal_notes($clean, $extraNotes);

    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('شروع تراکنش ناموفق بود.');
        }

        $transactionStarted = true;

        $columns = [
            'jobcard_number',
            'customer_id',
            'vehicle_id',
            'reception_user_id',
            'jobcard_status',
            'reception_at',
            'customer_complaint',
            'internal_notes',
            'priority_level',
            'lifecycle_state',
            'created_by_user_id',
        ];
        $values = [
            $jobcardNumber,
            $customerId,
            $vehicleId,
            $userId,
            $jobcardStatus,
            $receptionAt,
            $complaintText !== '' ? $complaintText : null,
            $internalNotes,
            'NORMAL',
            'ACTIVE',
            $userId,
        ];

        if ($relationColumnExists && $relationId !== null) {
            array_splice($columns, 3, 0, ['relation_id']);
            array_splice($values, 3, 0, [$relationId]);
        }

        if ($intakeMileage !== null && customer_core_column_exists($connection, MOGHARE360_JOBCARD_V2_TABLE, 'intake_mileage')) {
            $columns[] = 'intake_mileage';
            $values[] = $intakeMileage;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', $columns);

        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcards (' . $columnList . ') VALUES (' . $placeholders . ')',
            $values
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج کارت کار در erp_jobcards ناموفق بود.');
        }

        $jobcardId = customer_core_scope_identity($connection);

        if ($jobcardId === null || $jobcardId < 1) {
            $jobcardId = (int)(customer_core_scalar(
                $connection,
                'SELECT jobcard_id FROM dbo.erp_jobcards WHERE jobcard_number = ?',
                [$jobcardNumber]
            ) ?? 0);
        }

        if ($jobcardId < 1) {
            throw new RuntimeException('شناسه کارت کار پس از درج دریافت نشد.');
        }

        if (customer_core_table_exists($connection, MOGHARE360_JOBCARD_V2_HISTORY_TABLE)) {
            $historyOk = customer_core_execute(
                $connection,
                'INSERT INTO dbo.erp_jobcard_change_history (
                    jobcard_id,
                    change_type,
                    previous_status,
                    new_status,
                    change_summary,
                    changed_by_user_id
                ) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $jobcardId,
                    'JOBCARD_CREATE_V2',
                    null,
                    $jobcardStatus,
                    'JobCard Create v2 — Wave 1F controlled write',
                    $userId,
                ]
            );

            if ($historyOk !== false) {
                $resultNotes[] = 'erp_jobcard_change_history written';
            } else {
                $resultNotes[] = 'Audit write pending safe audit target confirmation';
            }
        } else {
            $resultNotes[] = 'Audit write pending safe audit target confirmation';
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'jobcard_id' => $jobcardId,
            'jobcard_number' => $jobcardNumber,
            'message' => 'کارت کار با موفقیت در erp_jobcards ثبت شد.',
            'error' => '',
            'notes' => $resultNotes,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted && $connection !== false) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);
        }

        return [
            'ok' => false,
            'jobcard_id' => null,
            'jobcard_number' => null,
            'message' => '',
            'error' => $exception->getMessage(),
            'notes' => $resultNotes,
        ];
    }
}
