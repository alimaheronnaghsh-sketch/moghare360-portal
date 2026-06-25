<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 3 Rule Engine Helper
 *
 * Contract authorization, service approval triggers, inventory routing decisions.
 * Non-sensitive; does not modify auth architecture.
 */

const ERP_PHASE3_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE3_PLACEHOLDER_ACTIONS = [
    'rule.engine.dashboard.view' => 'placeholder_rule_engine_dashboard_view',
    'rule.engine.approval.view' => 'placeholder_rule_engine_approval_view',
    'rule.engine.approval.decide' => 'placeholder_rule_engine_approval_decide',
    'rule.engine.console.view' => 'placeholder_rule_engine_console_view',
    'rule.engine.console.execute' => 'placeholder_rule_engine_console_execute',
];

function rule_engine_require_helper(string $fileName): void
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

rule_engine_require_helper('erp-auth-context.php');
rule_engine_require_helper('erp-permission-guard.php');
rule_engine_require_helper('erp-csrf.php');

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

function rule_engine_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rule_engine_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function rule_engine_get_string(string $key): string
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
}

function rule_engine_post_int(string $key): ?int
{
    $raw = rule_engine_post_string($key);

    return $raw !== '' && ctype_digit($raw) ? (int)$raw : null;
}

function rule_engine_get_int(string $key): ?int
{
    $raw = rule_engine_get_string($key);

    return $raw !== '' && ctype_digit($raw) ? (int)$raw : null;
}

function rule_engine_post_float(string $key): ?float
{
    $raw = rule_engine_post_string($key);

    return $raw !== '' && is_numeric($raw) ? (float)$raw : null;
}

function rule_engine_post_bool(string $key): bool
{
    return rule_engine_post_string($key) === '1' || rule_engine_post_string($key) === 'true';
}

function rule_engine_safe_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function rule_engine_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function rule_engine_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function rule_engine_safe_current_user(): string
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
function rule_engine_db()
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

function rule_engine_table_exists($connection, string $tableName): bool
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

function rule_engine_column_exists($connection, string $tableName, string $columnName): bool
{
    if ($connection === false) {
        return false;
    }

    $statement = @odbc_prepare(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
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
function rule_engine_execute($connection, string $sql, array $params = [])
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
function rule_engine_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = rule_engine_execute($connection, $sql, $params);

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
function rule_engine_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = rule_engine_execute($connection, $sql, $params);

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

function rule_engine_scope_identity($connection): ?int
{
    $value = rule_engine_scalar($connection, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id');

    return ($value !== null && is_numeric($value)) ? (int)$value : null;
}

function rule_engine_insert_history(
    $connection,
    string $entityType,
    ?int $entityId,
    string $actionType,
    string $actionSummary,
    ?string $oldValue = null,
    ?string $newValue = null
): bool {
    if (!rule_engine_table_exists($connection, 'erp_rule_audit_history')) {
        return false;
    }

    return rule_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_rule_audit_history (
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
            rule_engine_safe_current_user(),
            rule_engine_client_ip(),
            rule_engine_user_agent(),
        ]
    ) !== false;
}

function rule_engine_generate_decision_code(): string
{
    return 'RUL-DEC-' . date('Ymd-His') . '-' . (string)random_int(1000, 9999);
}

/**
 * @return array<string, mixed>
 */
function rule_engine_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_PHASE3_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_PHASE3_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

function rule_engine_require_auth_and_guard($connection, string $actionKey): int
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();

    if ($userId === null || $userId < 1) {
        throw new RuntimeException('دسترسی رد شد. لطفاً وارد شوید.');
    }

    $guard = rule_engine_guard_eval($connection, $userId, $actionKey);

    if (empty($guard['allowed'])) {
        throw new RuntimeException('دسترسی رد شد. مجوز کافی برای این عملیات وجود ندارد.');
    }

    return $userId;
}

/**
 * @return array<string, string>|null
 */
function rule_engine_get_contract($connection, ?int $contractId): ?array
{
    if ($contractId === null || $contractId < 1 || !rule_engine_table_exists($connection, 'erp_customer_contracts')) {
        return null;
    }

    $rows = rule_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 contract_id, contract_code, contract_type, authorization_mode,
                approval_threshold_amount, requires_operation_approval, requires_parts_approval, status
         FROM dbo.erp_customer_contracts WHERE contract_id = ?',
        [$contractId]
    );

    return $rows[0] ?? null;
}

/**
 * @return array<string, string>|null
 */
function rule_engine_get_operation_case($connection, ?int $operationCaseId): ?array
{
    if ($operationCaseId === null || $operationCaseId < 1 || !rule_engine_table_exists($connection, 'erp_operation_cases')) {
        return null;
    }

    $rows = rule_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 operation_case_id, operation_code, contract_id, customer_id,
                vehicle_binding_id, jobcard_id, current_stage, current_status
         FROM dbo.erp_operation_cases WHERE operation_case_id = ?',
        [$operationCaseId]
    );

    return $rows[0] ?? null;
}

/**
 * @return array<string, mixed>
 */
function rule_engine_get_rule_by_code($connection, string $ruleCode): ?array
{
    if (!rule_engine_table_exists($connection, 'erp_rule_definitions')) {
        return null;
    }

    $rows = rule_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 rule_id, rule_code, rule_name FROM dbo.erp_rule_definitions WHERE rule_code = ? AND is_active = 1',
        [$ruleCode]
    );

    return $rows[0] ?? null;
}

/**
 * @return array<string, mixed>
 */
function rule_engine_check_contract_authorization($connection, ?int $contractId, ?float $requestedAmount): array
{
    $requestedAmount = $requestedAmount ?? 0.0;
    $contract = rule_engine_get_contract($connection, $contractId);
    $rule = rule_engine_get_rule_by_code($connection, 'CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD');

    if ($contract === null) {
        return [
            'rule_id' => $rule['rule_id'] ?? null,
            'rule_code' => 'CONTRACT_MISSING',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'NEEDS_REVIEW',
            'decision_reason' => 'قرارداد مشتری یافت نشد یا قابل خواندن نیست.',
            'requested_amount' => $requestedAmount,
            'threshold_amount' => null,
            'next_action' => 'REVIEW_MANUALLY',
            'is_blocking' => 1,
            'inventory_status' => null,
        ];
    }

    $contractType = strtoupper($contract['contract_type'] ?? '');
    $thresholdRaw = $contract['approval_threshold_amount'] ?? '';
    $threshold = ($thresholdRaw !== '' && is_numeric($thresholdRaw)) ? (float)$thresholdRaw : null;
    $requiresOp = ($contract['requires_operation_approval'] ?? '0') === '1' || ($contract['requires_operation_approval'] ?? '') === 'true';

    if ($requiresOp) {
        return [
            'rule_id' => $rule['rule_id'] ?? null,
            'rule_code' => 'CONTRACT_OPERATION_APPROVAL',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'APPROVAL_REQUIRED',
            'decision_reason' => 'قرارداد نیاز به تأیید عملیات دارد.',
            'requested_amount' => $requestedAmount,
            'threshold_amount' => $threshold,
            'next_action' => 'REQUEST_CUSTOMER_APPROVAL',
            'is_blocking' => 1,
            'inventory_status' => null,
            'contract_id' => (int)$contract['contract_id'],
        ];
    }

    if ($contractType === 'OPEN_AUTHORIZATION') {
        $openRule = rule_engine_get_rule_by_code($connection, 'CONTRACT_OPEN_AUTHORIZATION_LIMIT');

        if ($threshold === null || $requestedAmount <= $threshold) {
            return [
                'rule_id' => $openRule['rule_id'] ?? null,
                'rule_code' => 'CONTRACT_OPEN_AUTHORIZATION_LIMIT',
                'decision_code' => rule_engine_generate_decision_code(),
                'decision_status' => 'ALLOWED',
                'decision_reason' => 'مجوز باز — مبلغ در سقف مجاز است.',
                'requested_amount' => $requestedAmount,
                'threshold_amount' => $threshold,
                'next_action' => 'CONTINUE_OPERATION',
                'is_blocking' => 0,
                'inventory_status' => null,
                'contract_id' => (int)$contract['contract_id'],
            ];
        }

        return [
            'rule_id' => $openRule['rule_id'] ?? null,
            'rule_code' => 'CONTRACT_OPEN_AUTHORIZATION_LIMIT',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'APPROVAL_REQUIRED',
            'decision_reason' => 'مبلغ از سقف مجوز باز بیشتر است.',
            'requested_amount' => $requestedAmount,
            'threshold_amount' => $threshold,
            'next_action' => 'REQUEST_CUSTOMER_APPROVAL',
            'is_blocking' => 1,
            'inventory_status' => null,
            'contract_id' => (int)$contract['contract_id'],
        ];
    }

    if ($contractType === 'LIMITED_AUTHORIZATION' && $threshold !== null && $requestedAmount > $threshold) {
        return [
            'rule_id' => $rule['rule_id'] ?? null,
            'rule_code' => 'CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'APPROVAL_REQUIRED',
            'decision_reason' => 'مبلغ از سقف مجوز محدود بیشتر است.',
            'requested_amount' => $requestedAmount,
            'threshold_amount' => $threshold,
            'next_action' => 'REQUEST_CUSTOMER_APPROVAL',
            'is_blocking' => 1,
            'inventory_status' => null,
            'contract_id' => (int)$contract['contract_id'],
        ];
    }

    return [
        'rule_id' => $rule['rule_id'] ?? null,
        'rule_code' => 'CONTRACT_AUTHORIZED',
        'decision_code' => rule_engine_generate_decision_code(),
        'decision_status' => 'ALLOWED',
        'decision_reason' => 'قرارداد اجازه ادامه عملیات را می‌دهد.',
        'requested_amount' => $requestedAmount,
        'threshold_amount' => $threshold,
        'next_action' => 'CONTINUE_OPERATION',
        'is_blocking' => 0,
        'inventory_status' => null,
        'contract_id' => (int)$contract['contract_id'],
    ];
}

/**
 * @return array<string, mixed>
 */
function rule_engine_check_service_requires_approval(
    $connection,
    bool $isOutOfContract,
    ?float $requestedAmount,
    ?string $serviceTitle
): array {
    $rule = rule_engine_get_rule_by_code($connection, 'SERVICE_OUT_OF_CONTRACT_APPROVAL');
    $requestedAmount = $requestedAmount ?? 0.0;

    if (!$isOutOfContract) {
        return [
            'rule_id' => $rule['rule_id'] ?? null,
            'rule_code' => 'SERVICE_IN_CONTRACT',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'ALLOWED',
            'decision_reason' => 'سرویس داخل محدوده قرارداد است.',
            'requested_amount' => $requestedAmount,
            'threshold_amount' => null,
            'next_action' => 'CONTINUE_OPERATION',
            'is_blocking' => 0,
            'inventory_status' => null,
        ];
    }

    $title = $serviceTitle !== null && trim($serviceTitle) !== '' ? trim($serviceTitle) : 'سرویس خارج از قرارداد';

    return [
        'rule_id' => $rule['rule_id'] ?? null,
        'rule_code' => 'SERVICE_OUT_OF_CONTRACT_APPROVAL',
        'decision_code' => rule_engine_generate_decision_code(),
        'decision_status' => 'APPROVAL_REQUIRED',
        'decision_reason' => 'عملیات اضافه خارج از قرارداد: ' . $title,
        'requested_amount' => $requestedAmount,
        'threshold_amount' => null,
        'next_action' => 'REQUEST_INTERNAL_APPROVAL',
        'is_blocking' => 1,
        'inventory_status' => null,
        'approval_type' => 'OUT_OF_CONTRACT_SERVICE',
    ];
}

/**
 * @return array<string, mixed>
 */
function rule_engine_estimate_part_available_qty($connection, ?int $partId, ?string $partCode): ?float
{
    if (!rule_engine_table_exists($connection, 'erp_parts')) {
        return null;
    }

    if ($partId === null && ($partCode === null || trim($partCode) === '')) {
        return null;
    }

    if ($partId === null && $partCode !== null && trim($partCode) !== '') {
        $found = rule_engine_scalar(
            $connection,
            'SELECT TOP 1 part_id FROM dbo.erp_parts WHERE part_code = ? AND is_active = 1',
            [trim($partCode)]
        );
        $partId = ($found !== null && ctype_digit($found)) ? (int)$found : null;
    }

    if ($partId === null) {
        return null;
    }

    if (!rule_engine_table_exists($connection, 'erp_stock_movements')) {
        return null;
    }

    $inQty = rule_engine_scalar(
        $connection,
        "SELECT ISNULL(SUM(quantity), 0) FROM dbo.erp_stock_movements
         WHERE part_id = ? AND movement_type IN ('RECEIPT', 'RETURN', 'ADJUSTMENT', 'SEED')",
        [$partId]
    );
    $outQty = rule_engine_scalar(
        $connection,
        "SELECT ISNULL(SUM(quantity), 0) FROM dbo.erp_stock_movements
         WHERE part_id = ? AND movement_type IN ('ISSUE', 'REVERSAL')",
        [$partId]
    );

    if ($inQty === null && $outQty === null) {
        return null;
    }

    return (float)($inQty ?? '0') - (float)($outQty ?? '0');
}

/**
 * @return array<string, mixed>
 */
function rule_engine_check_inventory_decision(
    $connection,
    ?int $partId,
    ?string $partCode,
    ?string $partName,
    float $requestedQty
): array {
    $availableRule = rule_engine_get_rule_by_code($connection, 'INVENTORY_PART_AVAILABLE_USE_STOCK');
    $purchaseRule = rule_engine_get_rule_by_code($connection, 'INVENTORY_PART_NOT_AVAILABLE_PURCHASE');
    $availableQty = rule_engine_estimate_part_available_qty($connection, $partId, $partCode);

    if ($availableQty === null) {
        return [
            'rule_id' => null,
            'rule_code' => 'INVENTORY_UNKNOWN',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'NEEDS_REVIEW',
            'decision_reason' => 'موجودی قطعه قابل تشخیص نیست — بررسی دستی لازم است.',
            'requested_amount' => null,
            'threshold_amount' => null,
            'inventory_status' => 'UNKNOWN',
            'next_action' => 'MANUAL_CHECK',
            'is_blocking' => 1,
            'available_qty' => null,
            'part_id' => $partId,
            'part_code' => $partCode,
            'part_name' => $partName,
            'requested_qty' => $requestedQty,
            'inventory_decision' => 'UNKNOWN',
        ];
    }

    if ($availableQty >= $requestedQty) {
        return [
            'rule_id' => $availableRule['rule_id'] ?? null,
            'rule_code' => 'INVENTORY_PART_AVAILABLE_USE_STOCK',
            'decision_code' => rule_engine_generate_decision_code(),
            'decision_status' => 'INVENTORY_AVAILABLE',
            'decision_reason' => 'قطعه در انبار موجود است — مسیر رزرو فعال.',
            'requested_amount' => null,
            'threshold_amount' => null,
            'inventory_status' => 'AVAILABLE',
            'next_action' => 'RESERVE_PART',
            'is_blocking' => 0,
            'available_qty' => $availableQty,
            'part_id' => $partId,
            'part_code' => $partCode,
            'part_name' => $partName,
            'requested_qty' => $requestedQty,
            'inventory_decision' => 'AVAILABLE',
        ];
    }

    return [
        'rule_id' => $purchaseRule['rule_id'] ?? null,
        'rule_code' => 'INVENTORY_PART_NOT_AVAILABLE_PURCHASE',
        'decision_code' => rule_engine_generate_decision_code(),
        'decision_status' => 'PURCHASE_REQUIRED',
        'decision_reason' => 'قطعه موجود نیست — مسیر درخواست خرید فعال.',
        'requested_amount' => null,
        'threshold_amount' => null,
        'inventory_status' => 'NOT_AVAILABLE',
        'next_action' => 'CREATE_PURCHASE_REQUEST',
        'is_blocking' => 1,
        'available_qty' => $availableQty,
        'part_id' => $partId,
        'part_code' => $partCode,
        'part_name' => $partName,
        'requested_qty' => $requestedQty,
        'inventory_decision' => 'PURCHASE_REQUIRED',
    ];
}

/**
 * @param array<string, mixed> $context
 * @param array<string, mixed> $decisionPayload
 */
function rule_engine_create_decision($connection, array $decisionPayload, array $context = []): ?int
{
    if (!rule_engine_table_exists($connection, 'erp_rule_decisions')) {
        return null;
    }

    $ruleId = $decisionPayload['rule_id'] ?? null;
    $ruleId = ($ruleId !== null && $ruleId !== '' && is_numeric($ruleId)) ? (int)$ruleId : null;

    $ok = rule_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_rule_decisions (
            rule_id, operation_case_id, service_step_id, contract_id, customer_id,
            vehicle_binding_id, part_id, decision_code, decision_status, decision_reason,
            requested_amount, threshold_amount, inventory_status, next_action, is_blocking,
            created_by, source_ip, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $ruleId,
            $context['operation_case_id'] ?? null,
            $context['service_step_id'] ?? null,
            $decisionPayload['contract_id'] ?? $context['contract_id'] ?? null,
            $context['customer_id'] ?? null,
            $context['vehicle_binding_id'] ?? null,
            $decisionPayload['part_id'] ?? $context['part_id'] ?? null,
            $decisionPayload['decision_code'] ?? rule_engine_generate_decision_code(),
            $decisionPayload['decision_status'] ?? 'NEEDS_REVIEW',
            $decisionPayload['decision_reason'] ?? null,
            $decisionPayload['requested_amount'] ?? null,
            $decisionPayload['threshold_amount'] ?? null,
            $decisionPayload['inventory_status'] ?? null,
            $decisionPayload['next_action'] ?? 'REVIEW_MANUALLY',
            !empty($decisionPayload['is_blocking']) ? 1 : 0,
            rule_engine_safe_current_user(),
            rule_engine_client_ip(),
            rule_engine_user_agent(),
        ]
    );

    if ($ok === false) {
        return null;
    }

    $decisionId = rule_engine_scope_identity($connection);

    if ($decisionId !== null) {
        rule_engine_insert_history(
            $connection,
            'erp_rule_decisions',
            $decisionId,
            'RULE_DECISION',
            (string)($decisionPayload['decision_reason'] ?? 'ثبت تصمیم قانون'),
            null,
            json_encode([
                'status' => $decisionPayload['decision_status'] ?? '',
                'next_action' => $decisionPayload['next_action'] ?? '',
            ], JSON_UNESCAPED_UNICODE)
        );
    }

    return $decisionId;
}

/**
 * @param array<string, mixed> $context
 * @param array<string, mixed> $decisionPayload
 */
function rule_engine_create_approval_request_if_needed(
    $connection,
    ?int $decisionId,
    array $decisionPayload,
    array $context = []
): ?int {
    $status = $decisionPayload['decision_status'] ?? '';

    if ($status !== 'APPROVAL_REQUIRED') {
        return null;
    }

    if (!rule_engine_table_exists($connection, 'erp_service_approval_requests')) {
        return null;
    }

    $approvalType = $decisionPayload['approval_type'] ?? 'MANUAL_MANAGER_APPROVAL';

    if (($decisionPayload['rule_code'] ?? '') === 'SERVICE_OUT_OF_CONTRACT_APPROVAL') {
        $approvalType = 'OUT_OF_CONTRACT_SERVICE';
    } elseif (str_contains((string)($decisionPayload['rule_code'] ?? ''), 'LIMITED') || str_contains((string)($decisionPayload['rule_code'] ?? ''), 'THRESHOLD')) {
        $approvalType = 'LIMIT_EXCEEDED';
    }

    $ok = rule_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_service_approval_requests (
            decision_id, operation_case_id, service_step_id, contract_id, customer_id,
            approval_type, approval_status, requested_amount, approval_reason,
            created_by, source_ip, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $decisionId,
            $context['operation_case_id'] ?? null,
            $context['service_step_id'] ?? null,
            $decisionPayload['contract_id'] ?? $context['contract_id'] ?? null,
            $context['customer_id'] ?? null,
            $approvalType,
            'PENDING',
            $decisionPayload['requested_amount'] ?? null,
            $decisionPayload['decision_reason'] ?? null,
            rule_engine_safe_current_user(),
            rule_engine_client_ip(),
            rule_engine_user_agent(),
        ]
    );

    if ($ok === false) {
        return null;
    }

    $approvalId = rule_engine_scope_identity($connection);

    if ($approvalId !== null) {
        rule_engine_insert_history(
            $connection,
            'erp_service_approval_requests',
            $approvalId,
            'APPROVAL_REQUEST_CREATE',
            'ایجاد درخواست تأیید — نوع: ' . $approvalType,
            null,
            json_encode(['approval_type' => $approvalType], JSON_UNESCAPED_UNICODE)
        );
    }

    return $approvalId;
}

/**
 * @param array<string, mixed> $context
 * @param array<string, mixed> $inventoryPayload
 */
function rule_engine_create_inventory_request_if_needed(
    $connection,
    ?int $decisionId,
    array $inventoryPayload,
    array $context = []
): ?int {
    if (!rule_engine_table_exists($connection, 'erp_inventory_rule_requests')) {
        return null;
    }

    $inventoryDecision = $inventoryPayload['inventory_decision'] ?? 'UNKNOWN';

    if ($inventoryDecision === 'UNKNOWN' && ($inventoryPayload['inventory_status'] ?? '') === '') {
        return null;
    }

    $ok = rule_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_inventory_rule_requests (
            decision_id, operation_case_id, service_step_id, part_id, part_code, part_name,
            requested_qty, available_qty, inventory_decision, next_action, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $decisionId,
            $context['operation_case_id'] ?? null,
            $context['service_step_id'] ?? null,
            $inventoryPayload['part_id'] ?? $context['part_id'] ?? null,
            $inventoryPayload['part_code'] ?? null,
            $inventoryPayload['part_name'] ?? null,
            $inventoryPayload['requested_qty'] ?? 1,
            $inventoryPayload['available_qty'] ?? null,
            $inventoryDecision,
            $inventoryPayload['next_action'] ?? 'MANUAL_CHECK',
            rule_engine_safe_current_user(),
        ]
    );

    if ($ok === false) {
        return null;
    }

    return rule_engine_scope_identity($connection);
}

function rule_engine_status_label_fa(string $status): string
{
    return match (strtoupper(trim($status))) {
        'ALLOWED' => 'مجاز',
        'APPROVAL_REQUIRED' => 'نیازمند تأیید',
        'BLOCKED' => 'متوقف',
        'NEEDS_REVIEW' => 'نیازمند بررسی دستی',
        'INVENTORY_AVAILABLE' => 'مسیر انبار',
        'PURCHASE_REQUIRED' => 'مسیر خرید',
        default => $status,
    };
}

function rule_engine_badge_class(string $status): string
{
    return match (strtoupper(trim($status))) {
        'ALLOWED', 'INVENTORY_AVAILABLE', 'APPROVED' => 'p1cc-badge-active',
        'APPROVAL_REQUIRED', 'PURCHASE_REQUIRED', 'PENDING' => 'p1cc-badge-duplicate',
        'BLOCKED', 'REJECTED' => 'p1cc-error',
        'NEEDS_REVIEW', 'UNKNOWN' => 'p1cc-badge-new',
        default => 'p1cc-badge-draft',
    };
}

function rule_engine_render_head(string $title, bool $readOnly = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . rule_engine_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rule-engine.css">';
    echo '</head><body class="m360-rtl p3re-page">';
    echo '<div class="p3re-wrap">';

    if ($readOnly) {
        echo '<div class="p3re-readonly-banner">فقط خواندنی — مغز تصمیم‌گیری Rule Engine</div>';
    }
}

function rule_engine_render_foot(): void
{
    echo '<p class="p3re-footer-nav"><a class="p3re-link" href="erp-rule-decision-board.php">تابلو تصمیم‌ها</a>';
    echo ' · <a class="p3re-link" href="erp-service-approval-request.php">تأیید سرویس</a>';
    echo ' · <a class="p3re-link" href="erp-rule-test-console.php">کنسول تست قوانین</a></p>';
    echo '</div></body></html>';
}

function rule_engine_render_error_page(string $title, string $message): void
{
    rule_engine_render_head($title, false);
    echo '<div class="p1cc-card p1cc-error"><h1>' . rule_engine_h($title) . '</h1>';
    echo '<p>' . rule_engine_h($message) . '</p></div>';
    rule_engine_render_foot();
    exit;
}
