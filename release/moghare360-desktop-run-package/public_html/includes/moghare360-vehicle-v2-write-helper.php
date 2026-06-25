<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Vehicle Create v2 DB Write Helper (Wave 1E)
 *
 * Reuses existing erp_vehicles and erp_customer_core_history.
 * No config.php · no schema changes · prepared statements only.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_VEHICLE_V2_TABLE = 'erp_vehicles';
const MOGHARE360_VEHICLE_V2_HISTORY_TABLE = 'erp_customer_core_history';

/**
 * @return list<string>
 */
function moghare360_vehicle_v2_required_columns(): array
{
    return [
        'vehicle_id',
        'vehicle_code',
        'plate_number',
        'lifecycle_state',
        'created_by_user_id',
    ];
}

/**
 * @return array{ready: bool, reason: string}
 */
function moghare360_vehicle_v2_schema_ready($connection): array
{
    if ($connection === false) {
        return ['ready' => false, 'reason' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_VEHICLE_V2_TABLE)) {
        return ['ready' => false, 'reason' => 'جدول erp_vehicles یافت نشد.'];
    }

    foreach (moghare360_vehicle_v2_required_columns() as $column) {
        if (!customer_core_column_exists($connection, MOGHARE360_VEHICLE_V2_TABLE, $column)) {
            return ['ready' => false, 'reason' => 'ستون الزامی یافت نشد: ' . $column];
        }
    }

    return ['ready' => true, 'reason' => ''];
}

function moghare360_vehicle_v2_generate_code(): string
{
    return 'V2V-' . date('Ymd-His') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * @param array<string, string> $plate
 */
function moghare360_vehicle_v2_format_plate_number(array $plate): string
{
    $formatted = $plate['province'] . $plate['letter'] . $plate['number'] . '-' . $plate['series'];

    return customer_core_normalize_plate($formatted);
}

/**
 * @param array<string, mixed> $clean
 */
function moghare360_vehicle_v2_compose_notes(array $clean, bool $chassisStoredInColumn = false, bool $engineStoredInColumn = false): ?string
{
    $segments = [];
    $class = trim((string)($clean['vehicle_class'] ?? ''));
    $brandId = trim((string)($clean['brand_id'] ?? ''));
    $modelId = trim((string)($clean['model_id'] ?? ''));
    $userNotes = trim((string)($clean['vehicle_notes'] ?? ''));

    if ($brandId !== '') {
        $segments[] = 'brand_id:' . $brandId;
    }

    if ($modelId !== '') {
        $segments[] = 'model_id:' . $modelId;
    }

    if ($class !== '') {
        $segments[] = 'class:' . $class;
    }

    $chassis = trim((string)($clean['chassis_no'] ?? ''));
    $engine = trim((string)($clean['engine_no'] ?? ''));

    if ($chassis !== '' && !$chassisStoredInColumn) {
        $segments[] = 'chassis:' . $chassis;
    }

    if ($engine !== '' && !$engineStoredInColumn) {
        $segments[] = 'engine:' . $engine;
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
 * @param array<string, mixed> $clean Validated clean payload from vehicle_create_v2
 * @return array{ok: bool, vehicle_id: int|null, vehicle_code: string|null, message: string, error: string, audit_note: string}
 */
function moghare360_vehicle_v2_write(array $clean): array
{
    $connection = customer_core_db();

    $schema = moghare360_vehicle_v2_schema_ready($connection);

    if (!$schema['ready']) {
        return [
            'ok' => false,
            'vehicle_id' => null,
            'vehicle_code' => null,
            'message' => '',
            'error' => 'DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED',
            'audit_note' => $schema['reason'],
        ];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;

    $plateRaw = $clean['plate'] ?? null;

    if (!is_array($plateRaw)) {
        return [
            'ok' => false,
            'vehicle_id' => null,
            'vehicle_code' => null,
            'message' => '',
            'error' => 'داده پلاک معتبر نیست.',
            'audit_note' => '',
        ];
    }

    $plateNumber = moghare360_vehicle_v2_format_plate_number([
        'province' => (string)($plateRaw['province'] ?? ''),
        'letter' => (string)($plateRaw['letter'] ?? ''),
        'number' => (string)($plateRaw['number'] ?? ''),
        'series' => (string)($plateRaw['series'] ?? ''),
    ]);

    $vin = trim((string)($clean['vin'] ?? ''));
    $chassis = trim((string)($clean['chassis_no'] ?? ''));
    $engine = trim((string)($clean['engine_no'] ?? ''));
    $vehicleCode = moghare360_vehicle_v2_generate_code();
    $brand = 'BRAND-' . trim((string)($clean['brand_id'] ?? '0'));
    $model = 'MODEL-' . trim((string)($clean['model_id'] ?? '0'));

    $chassisInColumn = $chassis !== ''
        && customer_core_column_exists($connection, MOGHARE360_VEHICLE_V2_TABLE, 'chassis_number');
    $engineInColumn = $engine !== ''
        && customer_core_column_exists($connection, MOGHARE360_VEHICLE_V2_TABLE, 'engine_number');
    $notes = moghare360_vehicle_v2_compose_notes($clean, $chassisInColumn, $engineInColumn);

    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('شروع تراکنش ناموفق بود.');
        }

        $transactionStarted = true;

        $columns = [
            'vehicle_code',
            'plate_number',
            'vin',
            'brand',
            'model',
            'notes',
            'lifecycle_state',
            'created_by_user_id',
        ];
        $values = [
            $vehicleCode,
            $plateNumber,
            $vin !== '' ? $vin : null,
            $brand,
            $model,
            $notes,
            'ACTIVE',
            $userId,
        ];

        if ($chassisInColumn) {
            $columns[] = 'chassis_number';
            $values[] = $chassis;
        }

        if ($engineInColumn) {
            $columns[] = 'engine_number';
            $values[] = $engine;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', $columns);

        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_vehicles (' . $columnList . ') VALUES (' . $placeholders . ')',
            $values
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج خودرو در erp_vehicles ناموفق بود.');
        }

        $vehicleId = customer_core_scope_identity($connection);

        if ($vehicleId === null || $vehicleId < 1) {
            $vehicleId = (int)(customer_core_scalar(
                $connection,
                'SELECT vehicle_id FROM dbo.erp_vehicles WHERE vehicle_code = ?',
                [$vehicleCode]
            ) ?? 0);
        }

        if ($vehicleId < 1) {
            throw new RuntimeException('شناسه خودرو پس از درج دریافت نشد.');
        }

        $auditNote = 'Audit write pending safe audit target confirmation';

        if (customer_core_table_exists($connection, MOGHARE360_VEHICLE_V2_HISTORY_TABLE)) {
            $historyOk = customer_core_insert_history(
                $connection,
                'erp_vehicles',
                $vehicleId,
                'VEHICLE_CREATE_V2',
                'Vehicle Create v2 — Wave 1E controlled write',
                null,
                json_encode([
                    'vehicle_code' => $vehicleCode,
                    'plate_number' => $plateNumber,
                    'vin' => $vin !== '' ? $vin : null,
                    'brand_id' => $clean['brand_id'] ?? null,
                    'model_id' => $clean['model_id'] ?? null,
                    'vehicle_class' => $clean['vehicle_class'] ?? null,
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

        return [
            'ok' => true,
            'vehicle_id' => $vehicleId,
            'vehicle_code' => $vehicleCode,
            'message' => 'خودرو با موفقیت در erp_vehicles ثبت شد.',
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
            'vehicle_id' => null,
            'vehicle_code' => null,
            'message' => '',
            'error' => $exception->getMessage(),
            'audit_note' => '',
        ];
    }
}
