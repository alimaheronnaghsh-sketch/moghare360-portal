<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Operation Engine Helper
 *
 * Orchestration layer connecting Customer Core → JobCard → Service → QC → Delivery.
 * Does not modify auth architecture or permission model.
 */

const ERP_PHASE2_PLATFORM_OWNER_ID = 10001;

/** @var list<string> */
const ERP_PHASE2_OPERATION_STAGES = [
    'RECEPTION',
    'DIAGNOSIS',
    'SERVICE',
    'WAITING_APPROVAL',
    'WAITING_PARTS',
    'QC',
    'READY_FOR_DELIVERY',
    'DELIVERED',
    'CANCELLED',
];

/** @var list<string> */
const ERP_PHASE2_STEP_TYPES = [
    'INSPECTION',
    'DIAGNOSIS',
    'REPAIR',
    'REPLACEMENT',
    'ROAD_TEST',
    'QC',
    'DELIVERY_CHECK',
];

/** @var list<string> */
const ERP_PHASE2_STEP_STATUSES = [
    'OPEN',
    'ASSIGNED',
    'IN_PROGRESS',
    'DONE',
    'RETURNED',
    'CANCELLED',
];

/** @var list<string> */
const ERP_PHASE2_QC_DECISION_STATUSES = [
    'PASSED',
    'FAILED_RETURN_TO_SERVICE',
    'FAILED_RETURN_TO_DIAGNOSIS',
    'HOLD',
];

/** @var array<string, string> */
const ERP_PHASE2_PLACEHOLDER_ACTIONS = [
    'operation.engine.dashboard.view' => 'placeholder_operation_engine_dashboard_view',
    'operation.engine.flow.view' => 'placeholder_operation_engine_flow_view',
    'operation.engine.flow.write' => 'placeholder_operation_engine_flow_write',
    'operation.engine.technician.board.view' => 'placeholder_operation_engine_technician_board_view',
    'operation.engine.case.create' => 'placeholder_operation_engine_case_create',
    'operation.engine.service.update' => 'placeholder_operation_engine_service_update',
    'operation.engine.qc.decide' => 'placeholder_operation_engine_qc_decide',
    'operation.engine.delivery.check' => 'placeholder_operation_engine_delivery_check',
];

function operation_engine_require_helper(string $fileName): void
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

operation_engine_require_helper('erp-auth-context.php');
operation_engine_require_helper('erp-permission-guard.php');
operation_engine_require_helper('erp-csrf.php');

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

function operation_engine_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function operation_engine_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function operation_engine_get_string(string $key): string
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
}

function operation_engine_get_int(string $key): ?int
{
    $raw = operation_engine_get_string($key);

    return $raw !== '' && ctype_digit($raw) ? (int)$raw : null;
}

function operation_engine_post_int(string $key): ?int
{
    $raw = operation_engine_post_string($key);

    return $raw !== '' && ctype_digit($raw) ? (int)$raw : null;
}

function operation_engine_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function operation_engine_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function operation_engine_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function operation_engine_safe_current_user(): string
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
function operation_engine_db()
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

function operation_engine_table_exists($connection, string $tableName): bool
{
    if ($connection === false) {
        return false;
    }

    $statement = @odbc_prepare(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
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

/**
 * @param list<mixed> $params
 */
function operation_engine_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

/**
 * @param list<mixed> $params
 */
function operation_engine_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = operation_engine_execute($connection, $sql, $params);

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
function operation_engine_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = operation_engine_execute($connection, $sql, $params);

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

function operation_engine_scope_identity($connection): ?int
{
    $value = operation_engine_scalar($connection, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id');

    return ($value !== null && is_numeric($value)) ? (int)$value : null;
}

function operation_engine_insert_history(
    $connection,
    string $entityType,
    ?int $entityId,
    string $actionType,
    string $actionSummary,
    ?string $oldValue = null,
    ?string $newValue = null
): bool {
    if (!operation_engine_table_exists($connection, 'erp_operation_history')) {
        return false;
    }

    return operation_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_operation_history (
            entity_type, entity_id, action_type, action_summary,
            old_value, new_value, created_by, source_ip, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $entityType,
            $entityId,
            $actionType,
            $actionSummary,
            $oldValue,
            $newValue,
            operation_engine_safe_current_user(),
            operation_engine_client_ip(),
            operation_engine_user_agent(),
        ]
    ) !== false;
}

/**
 * @return array<string, mixed>
 */
function operation_engine_guard_eval($connection, int $userId, string $actionKey): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $result['label'] = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $result['label'] = 'PLACEHOLDER';
        }

        return $result;
    }

    if (!isset(ERP_PHASE2_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false, 'action_key' => $actionKey];
    }

    if ($userId === ERP_PHASE2_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true, 'action_key' => $actionKey];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true, 'action_key' => $actionKey];
}

function operation_engine_require_auth_and_guard($connection, string $actionKey): int
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();

    if ($userId === null || $userId < 1) {
        throw new RuntimeException('دسترسی رد شد. لطفاً وارد شوید.');
    }

    $guard = operation_engine_guard_eval($connection, $userId, $actionKey);

    if (empty($guard['allowed'])) {
        throw new RuntimeException('دسترسی رد شد. مجوز کافی برای این عملیات وجود ندارد.');
    }

    return $userId;
}

function operation_engine_generate_operation_code(): string
{
    return 'OP-' . date('Ymd-His') . '-' . (string)random_int(1000, 9999);
}

function operation_engine_validate_stage(string $stage): bool
{
    return in_array(strtoupper(trim($stage)), ERP_PHASE2_OPERATION_STAGES, true);
}

function operation_engine_validate_step_status(string $status): bool
{
    return in_array(strtoupper(trim($status)), ERP_PHASE2_STEP_STATUSES, true);
}

function operation_engine_validate_step_type(string $type): bool
{
    return in_array(strtoupper(trim($type)), ERP_PHASE2_STEP_TYPES, true);
}

function operation_engine_validate_qc_decision(string $status): bool
{
    return in_array(strtoupper(trim($status)), ERP_PHASE2_QC_DECISION_STATUSES, true);
}

function operation_engine_render_head(string $title, bool $readOnly = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . operation_engine_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-operation-engine.css">';
    echo '</head><body class="m360-rtl p2oe-page">';
    echo '<div class="p2oe-wrap">';

    if ($readOnly) {
        echo '<div class="p2oe-readonly-banner">فقط خواندنی — بدون ثبت یا ویرایش مستقیم</div>';
    }
}

function operation_engine_render_foot(): void
{
    echo '<p class="p2oe-footer-nav"><a class="p2oe-link" href="erp-operation-control-center.php">مرکز کنترل عملیات</a>';
    echo ' · <a class="p2oe-link" href="erp-jobcard-operation-flow.php">جریان عملیاتی</a>';
    echo ' · <a class="p2oe-link" href="erp-technician-board.php">تابلوی تکنسین</a></p>';
    echo '</div></body></html>';
}

function operation_engine_render_error_page(string $title, string $message): void
{
    operation_engine_render_head($title, false);
    echo '<div class="p1cc-card p1cc-error"><h1>' . operation_engine_h($title) . '</h1>';
    echo '<p>' . operation_engine_h($message) . '</p>';
    echo '<p><a class="p2oe-link" href="erp-operation-control-center.php">بازگشت به مرکز کنترل</a></p></div>';
    operation_engine_render_foot();
    exit;
}

function operation_engine_badge_class(string $value): string
{
    $value = strtoupper(trim($value));

    return match ($value) {
        'OPEN', 'RECEPTION', 'ASSIGNED' => 'p1cc-badge-open',
        'IN_PROGRESS', 'SERVICE', 'DIAGNOSIS' => 'p1cc-badge-draft',
        'DONE', 'PASSED', 'DELIVERED', 'QC_PASSED', 'READY_FOR_DELIVERY' => 'p1cc-badge-active',
        'RETURNED', 'RETURNED_FROM_QC', 'FAILED_RETURN_TO_SERVICE', 'FAILED_RETURN_TO_DIAGNOSIS' => 'p1cc-badge-duplicate',
        'QC_HOLD', 'HOLD', 'WAITING_APPROVAL', 'WAITING_PARTS' => 'p1cc-badge-new',
        'CANCELLED' => 'p1cc-badge-draft',
        default => 'p1cc-badge-draft',
    };
}

/**
 * @return array<string, string>|null
 */
function operation_engine_load_case($connection, int $operationCaseId): ?array
{
    $rows = operation_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 * FROM dbo.erp_operation_cases WHERE operation_case_id = ?',
        [$operationCaseId]
    );

    return $rows[0] ?? null;
}

/**
 * @return array<string, string>|null
 */
function operation_engine_latest_qc_decision($connection, int $operationCaseId): ?array
{
    $rows = operation_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 decision_status, decision_note, decided_at, decided_by
         FROM dbo.erp_operation_qc_decisions
         WHERE operation_case_id = ?
         ORDER BY decided_at DESC, qc_decision_id DESC',
        [$operationCaseId]
    );

    return $rows[0] ?? null;
}

function operation_engine_all_steps_done($connection, int $operationCaseId): bool
{
    $total = operation_engine_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_operation_service_steps
         WHERE operation_case_id = ? AND step_status <> 'CANCELLED'",
        [$operationCaseId]
    );
    $pending = operation_engine_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_operation_service_steps
         WHERE operation_case_id = ? AND step_status NOT IN ('DONE', 'CANCELLED')",
        [$operationCaseId]
    );

    return $total !== null && (int)$total > 0 && $pending !== null && (int)$pending === 0;
}

function operation_engine_flash_message(string $key): string
{
    return match ($key) {
        'case_ok' => 'پرونده عملیاتی با موفقیت ایجاد شد.',
        'service_ok' => 'وضعیت سرویس به‌روزرسانی شد.',
        'qc_ok' => 'تصمیم QC ثبت شد.',
        'delivery_ok' => 'بررسی تحویل ثبت شد.',
        default => '',
    };
}
