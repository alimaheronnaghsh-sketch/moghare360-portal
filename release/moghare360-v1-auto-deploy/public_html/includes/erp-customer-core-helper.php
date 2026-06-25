<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Customer Core System Helper
 *
 * Shared DB, auth bootstrap, CSRF, history, and legacy table compatibility.
 * Does not modify auth architecture or permission model.
 */

const ERP_PHASE1_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE1_PLACEHOLDER_ACTIONS = [
    'customer.core.dashboard.view' => 'placeholder_customer_core_dashboard_view',
    'customer.core.entry.create' => 'placeholder_customer_core_entry_create',
    'customer.core.entry.view' => 'placeholder_customer_core_entry_view',
    'customer.core.contract.create' => 'placeholder_customer_core_contract_create',
    'customer.core.contract.view' => 'placeholder_customer_core_contract_view',
    'customer.core.profile.view' => 'placeholder_customer_core_profile_view',
    'customer.core.vehicle.binding.create' => 'placeholder_customer_core_vehicle_binding_create',
    'customer.core.vehicle.binding.view' => 'placeholder_customer_core_vehicle_binding_view',
];

function customer_core_require_helper(string $fileName): void
{
    $candidates = [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $fileName);
}

customer_core_require_helper('erp-auth-context.php');
customer_core_require_helper('erp-permission-guard.php');
customer_core_require_helper('erp-csrf.php');

if (!function_exists('erp_csrf_input')) {
    function erp_csrf_input(string $purpose): string
    {
        $token = erp_csrf_create_token($purpose);

        return '<input type="hidden" name="erp_csrf_token" value="' .
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8') .
            '">';
    }
}

if (!function_exists('erp_csrf_require_valid')) {
    function erp_csrf_require_valid(string $purpose, ?string $token): void
    {
        try {
            erp_csrf_require_valid_token($purpose, (string)($token ?? ''));
        } catch (Throwable) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'ERP security validation failed.';
            exit;
        }
    }
}

function customer_core_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function customer_core_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function customer_core_get_string(string $key): string
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
}

function customer_core_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function customer_core_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function customer_core_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function customer_core_safe_current_user(): string
{
    erp_auth_context_start();

    if (isset($_SESSION['erp_username']) && is_string($_SESSION['erp_username']) && trim($_SESSION['erp_username']) !== '') {
        return trim($_SESSION['erp_username']);
    }

    if (isset($_SESSION['erp_full_name']) && is_string($_SESSION['erp_full_name']) && trim($_SESSION['erp_full_name']) !== '') {
        return trim($_SESSION['erp_full_name']);
    }

    return 'ERP_STAFF';
}

/**
 * @return resource|false
 */
function customer_core_db()
{
    if (!extension_loaded('odbc')) {
        return false;
    }

    try {
        return erp_auth_create_local_odbc_connection();
    } catch (Throwable) {
        return false;
    }
}

function customer_core_table_exists($connection, string $tableName): bool
{
    if ($connection === false) {
        return false;
    }

    $statement = @odbc_prepare(
        $connection,
        'SELECT COUNT(*) AS table_count
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );

    if ($statement === false || !@odbc_execute($statement, ['dbo', $tableName])) {
        return false;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return false;
    }

    $count = @odbc_result($statement, 1);

    return $count !== false && $count !== null && (int)$count > 0;
}

function customer_core_column_exists($connection, string $tableName, string $columnName): bool
{
    if ($connection === false) {
        return false;
    }

    $statement = @odbc_prepare(
        $connection,
        'SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );

    if ($statement === false || !@odbc_execute($statement, ['dbo', $tableName, $columnName])) {
        return false;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return false;
    }

    $count = @odbc_result($statement, 1);

    return $count !== false && $count !== null && (int)$count > 0;
}

/**
 * @param list<mixed> $params
 */
function customer_core_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false) {
        return false;
    }

    if (!@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

/**
 * @param list<mixed> $params
 */
function customer_core_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = customer_core_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = @odbc_result($statement, 1);

    return $value === false || $value === null ? null : (string)$value;
}

/**
 * @param list<mixed> $params
 * @return list<array<string, string>>
 */
function customer_core_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = customer_core_execute($connection, $sql, $params);

    if ($statement === false) {
        return [];
    }

    $rows = [];

    while (@odbc_fetch_row($statement)) {
        $row = [];
        $columnCount = @odbc_num_fields($statement);

        if ($columnCount === false || $columnCount < 1) {
            continue;
        }

        for ($i = 1; $i <= $columnCount; $i++) {
            $name = @odbc_field_name($statement, $i);

            if ($name === false) {
                continue;
            }

            $value = @odbc_result($statement, $i);
            $row[strtolower((string)$name)] = $value === false || $value === null ? '' : (string)$value;
        }

        if ($row !== []) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function customer_core_scope_identity($connection): ?int
{
    $value = customer_core_scalar($connection, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id');

    if ($value === null || !is_numeric($value)) {
        return null;
    }

    return (int)$value;
}

function customer_core_insert_history(
    $connection,
    string $entityType,
    ?int $entityId,
    string $actionType,
    string $actionSummary,
    ?string $oldValue = null,
    ?string $newValue = null
): bool {
    $createdBy = customer_core_safe_current_user();

    $statement = customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_customer_core_history (
            entity_type,
            entity_id,
            action_type,
            action_summary,
            old_value,
            new_value,
            created_by,
            source_ip,
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $entityType,
            $entityId,
            $actionType,
            $actionSummary,
            $oldValue,
            $newValue,
            $createdBy,
            customer_core_client_ip(),
            customer_core_user_agent(),
        ]
    );

    return $statement !== false;
}

/**
 * @return array<string, mixed>
 */
function customer_core_guard_eval($connection, int $userId, string $actionKey): array
{
    $actionKey = trim($actionKey);
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $result['label'] = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $result['label'] = 'PLACEHOLDER';
        }

        return $result;
    }

    if (!isset(ERP_PHASE1_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
            'action_key' => $actionKey,
        ];
    }

    if ($userId === ERP_PHASE1_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
            'action_key' => $actionKey,
        ];
    }

    return [
        'allowed' => false,
        'label' => 'FAIL',
        'placeholder' => true,
        'action_key' => $actionKey,
    ];
}

function customer_core_require_auth_and_guard($connection, string $actionKey): int
{
    erp_auth_context_start();

    $userId = erp_auth_current_user_id();

    if ($userId === null || $userId < 1) {
        throw new RuntimeException('دسترسی رد شد. لطفاً وارد شوید.');
    }

    $guard = customer_core_guard_eval($connection, $userId, $actionKey);

    if (empty($guard['allowed'])) {
        throw new RuntimeException('دسترسی رد شد. مجوز کافی برای این عملیات وجود ندارد.');
    }

    return $userId;
}

function customer_core_render_head(string $title, bool $readOnly = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . customer_core_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '</head><body class="m360-rtl p1cc-page">';
    echo '<div class="p1cc-wrap">';

    if ($readOnly) {
        echo '<div class="p1cc-readonly-banner">فقط خواندنی — بدون ثبت یا ویرایش</div>';
    }
}

function customer_core_render_foot(): void
{
    echo '</div></body></html>';
}

function customer_core_render_error_page(string $title, string $message): void
{
    customer_core_render_head($title, false);
    echo '<div class="p1cc-card p1cc-error">';
    echo '<h1>' . customer_core_h($title) . '</h1>';
    echo '<p>' . customer_core_h($message) . '</p>';
    echo '<p><a class="p1cc-link" href="erp-customer-core-dashboard.php">بازگشت به داشبورد</a></p>';
    echo '</div>';
    customer_core_render_foot();
    exit;
}

function customer_core_normalize_mobile(string $mobile): string
{
    $mobile = preg_replace('/\s+/', '', $mobile) ?? $mobile;

    return trim($mobile);
}

function customer_core_normalize_plate(string $plate): string
{
    $plate = preg_replace('/\s+/', ' ', trim($plate)) ?? trim($plate);

    return strtoupper($plate);
}

/**
 * @return array{status: string, reason: string}
 */
function customer_core_duplicate_check_intake($connection, string $mobile, string $nationalCode, string $licensePlate): array
{
    $reasons = [];
    $mobile = customer_core_normalize_mobile($mobile);
    $nationalCode = trim($nationalCode);
    $licensePlate = customer_core_normalize_plate($licensePlate);

    if (customer_core_table_exists($connection, 'erp_customer_intakes')) {
        if ($mobile !== '') {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_intakes WHERE mobile = ?',
                [$mobile]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'موبایل تکراری در erp_customer_intakes';
            }
        }

        if ($nationalCode !== '') {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_intakes WHERE national_code = ?',
                [$nationalCode]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'کد ملی تکراری در erp_customer_intakes';
            }
        }

        if ($licensePlate !== '') {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_intakes WHERE license_plate = ?',
                [$licensePlate]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'پلاک تکراری در erp_customer_intakes';
            }
        }
    }

    if ($mobile !== '' && customer_core_table_exists($connection, 'CustomerPhones_v2')) {
        $phoneCol = customer_core_column_exists($connection, 'CustomerPhones_v2', 'PhoneNumber') ? 'PhoneNumber' : null;

        if ($phoneCol !== null) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.CustomerPhones_v2 WHERE PhoneNumber = ?',
                [$mobile]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'موبایل در CustomerPhones_v2';
            }
        }
    }

    if (customer_core_table_exists($connection, 'Customers_v2')) {
        if ($mobile !== '' && customer_core_column_exists($connection, 'Customers_v2', 'Mobile')) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.Customers_v2 WHERE Mobile = ?',
                [$mobile]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'موبایل در Customers_v2';
            }
        }

        if ($nationalCode !== '' && customer_core_column_exists($connection, 'Customers_v2', 'NationalCode')) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.Customers_v2 WHERE NationalCode = ?',
                [$nationalCode]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'کد ملی در Customers_v2';
            }
        }
    }

    if ($licensePlate !== '' && customer_core_table_exists($connection, 'Vehicles')) {
        $plateCol = null;

        foreach (['LicensePlate', 'PlateNumber', 'plate_number'] as $candidate) {
            if (customer_core_column_exists($connection, 'Vehicles', $candidate)) {
                $plateCol = $candidate;
                break;
            }
        }

        if ($plateCol !== null) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.Vehicles WHERE [' . $plateCol . '] = ?',
                [$licensePlate]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'پلاک در Vehicles';
            }
        }
    }

    if ($reasons === []) {
        return ['status' => 'NEW', 'reason' => ''];
    }

    return [
        'status' => 'POSSIBLE_DUPLICATE',
        'reason' => implode('؛ ', $reasons),
    ];
}

/**
 * @return array{status: string, reason: string}
 */
function customer_core_duplicate_check_vehicle($connection, string $licensePlate, string $vin): array
{
    $reasons = [];
    $licensePlate = customer_core_normalize_plate($licensePlate);
    $vin = strtoupper(trim($vin));

    if (customer_core_table_exists($connection, 'erp_customer_vehicle_bindings')) {
        if ($licensePlate !== '') {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_vehicle_bindings WHERE license_plate = ?',
                [$licensePlate]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'پلاک تکراری در erp_customer_vehicle_bindings';
            }
        }

        if ($vin !== '') {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_vehicle_bindings WHERE vin = ?',
                [$vin]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'VIN تکراری در erp_customer_vehicle_bindings';
            }
        }
    }

    if (customer_core_table_exists($connection, 'Vehicles')) {
        foreach (['LicensePlate', 'PlateNumber', 'plate_number'] as $plateCol) {
            if ($licensePlate !== '' && customer_core_column_exists($connection, 'Vehicles', $plateCol)) {
                $count = customer_core_scalar(
                    $connection,
                    'SELECT COUNT(*) FROM dbo.Vehicles WHERE [' . $plateCol . '] = ?',
                    [$licensePlate]
                );

                if ($count !== null && (int)$count > 0) {
                    $reasons[] = 'پلاک در Vehicles';
                    break;
                }
            }
        }

        if ($vin !== '' && customer_core_column_exists($connection, 'Vehicles', 'VIN')) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.Vehicles WHERE VIN = ?',
                [$vin]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'VIN در Vehicles';
            }
        } elseif ($vin !== '' && customer_core_column_exists($connection, 'Vehicles', 'vin')) {
            $count = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.Vehicles WHERE vin = ?',
                [$vin]
            );

            if ($count !== null && (int)$count > 0) {
                $reasons[] = 'VIN در Vehicles';
            }
        }
    }

    if ($reasons === []) {
        return ['status' => 'NEW', 'reason' => ''];
    }

    return [
        'status' => 'POSSIBLE_DUPLICATE',
        'reason' => implode('؛ ', $reasons),
    ];
}

function customer_core_generate_contract_code(): string
{
    return 'CUS-CON-' . date('Ymd-His') . '-' . (string)random_int(1000, 9999);
}

function customer_core_flash_message(string $key): string
{
    return match ($key) {
        'customer_entry_ok' => 'ورود مشتری با موفقیت ثبت شد.',
        'contract_ok' => 'قرارداد مشتری با موفقیت ثبت شد.',
        'vehicle_binding_ok' => 'اتصال خودرو به مشتری با موفقیت ثبت شد.',
        default => '',
    };
}

/** @var list<string> */
const ERP_PHASE1_PHOTO_TYPES = ['FRONT', 'REAR', 'LEFT', 'RIGHT', 'INTERIOR', 'ODOMETER', 'DAMAGE'];
