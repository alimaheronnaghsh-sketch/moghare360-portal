<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 12 Soft Run Pilot Helper (non-sensitive)
 */

const ERP_PHASE12_PLATFORM_OWNER_ID = 10001;
const PILOT_CSRF_SCENARIO_CREATE = 'pilot_scenario_create';
const PILOT_CSRF_FEEDBACK_CREATE = 'pilot_feedback_create';

/** @var array<string, string> */
const ERP_PHASE12_PLACEHOLDER_ACTIONS = [
    'pilot.view' => 'placeholder_pilot_view',
    'pilot.scenario.write' => 'placeholder_pilot_scenario_write',
    'pilot.feedback.write' => 'placeholder_pilot_feedback_write',
];

function pilot_require_helper(string $fileName): void
{
    foreach ([__DIR__, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes'] as $base) {
        $path = $base . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
    throw new RuntimeException('Required ERP file not found: ' . $fileName);
}

pilot_require_helper('erp-auth-context.php');
pilot_require_helper('erp-permission-guard.php');

function pilot_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['moghare360_pilot_csrf']) || !is_array($_SESSION['moghare360_pilot_csrf'])) {
        $_SESSION['moghare360_pilot_csrf'] = [];
    }
}

function pilot_csrf_token(string $action): string
{
    pilot_session_start();
    $action = trim($action);
    if ($action === '') {
        return '';
    }

    if (
        !isset($_SESSION['moghare360_pilot_csrf'][$action])
        || !is_string($_SESSION['moghare360_pilot_csrf'][$action])
        || $_SESSION['moghare360_pilot_csrf'][$action] === ''
    ) {
        $_SESSION['moghare360_pilot_csrf'][$action] = bin2hex(random_bytes(32));
    }

    return $_SESSION['moghare360_pilot_csrf'][$action];
}

function pilot_csrf_rotate(string $action): void
{
    pilot_session_start();
    $action = trim($action);
    if ($action === '') {
        return;
    }
    $_SESSION['moghare360_pilot_csrf'][$action] = bin2hex(random_bytes(32));
}

function pilot_csrf_input(string $action): string
{
    $token = pilot_csrf_token($action);

    return '<input type="hidden" name="pilot_csrf_token" value="' . pilot_h($token) . '">' .
        '<input type="hidden" name="pilot_csrf_action" value="' . pilot_h($action) . '">';
}

/** @return null|string Safe failure reason code */
function pilot_csrf_validate_detail(string $action): ?string
{
    pilot_session_start();
    $action = trim($action);
    $postedToken = trim((string)($_POST['pilot_csrf_token'] ?? ''));
    $postedAction = trim((string)($_POST['pilot_csrf_action'] ?? ''));

    if ($postedToken === '') {
        return 'missing_post_token';
    }
    if ($postedAction === '') {
        return 'missing_post_action';
    }
    if ($action === '') {
        return 'action_mismatch';
    }
    if ($postedAction !== $action) {
        return 'action_mismatch';
    }
    if (
        !isset($_SESSION['moghare360_pilot_csrf'][$action])
        || !is_string($_SESSION['moghare360_pilot_csrf'][$action])
        || $_SESSION['moghare360_pilot_csrf'][$action] === ''
    ) {
        return 'missing_session_token';
    }
    if (!hash_equals($_SESSION['moghare360_pilot_csrf'][$action], $postedToken)) {
        return 'token_mismatch';
    }

    return null;
}

function pilot_csrf_validate(string $action): bool
{
    return pilot_csrf_validate_detail($action) === null;
}

function pilot_csrf_failure_redirect(string $url, string $reason): void
{
    $allowed = [
        'missing_post_token',
        'missing_post_action',
        'missing_session_token',
        'action_mismatch',
        'token_mismatch',
    ];
    if (!in_array($reason, $allowed, true)) {
        $reason = 'token_mismatch';
    }
    $sep = str_contains($url, '?') ? '&' : '?';
    pilot_safe_redirect($url . $sep . 'err=csrf&reason=' . rawurlencode($reason));
}

function pilot_csrf_require_valid(string $action, ?string $token = null, string $returnPage = 'erp-pilot-scenario-builder.php'): void
{
    unset($token);
    $reason = pilot_csrf_validate_detail($action);
    if ($reason !== null) {
        pilot_csrf_failure_redirect($returnPage, $reason);
    }
    pilot_csrf_rotate($action);
}

function pilot_csrf_safe_reason_label(?string $reason): string
{
    $allowed = [
        'missing_post_token',
        'missing_post_action',
        'missing_session_token',
        'action_mismatch',
        'token_mismatch',
    ];
    return in_array((string)$reason, $allowed, true) ? (string)$reason : 'token_mismatch';
}

function pilot_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pilot_safe_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function pilot_db()
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

function pilot_table_exists($c, string $table): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $table]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function pilot_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) {
        return false;
    }
    return $s;
}

function pilot_scalar($c, string $sql, array $p = []): ?string
{
    $s = pilot_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function pilot_fetch_rows($c, string $sql, array $p = []): array
{
    $s = pilot_execute($c, $sql, $p);
    if ($s === false) {
        return [];
    }
    $rows = [];
    while (@odbc_fetch_row($s)) {
        $row = [];
        $n = @odbc_num_fields($s);
        if ($n === false) {
            continue;
        }
        for ($i = 1; $i <= $n; $i++) {
            $name = @odbc_field_name($s, $i);
            if ($name === false) {
                continue;
            }
            $val = @odbc_result($s, $i);
            $row[strtolower((string)$name)] = $val === false || $val === null ? '' : (string)$val;
        }
        if ($row !== []) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function pilot_safe_count($c, string $table, string $where = '1=1', array $p = []): int
{
    if (!pilot_table_exists($c, $table)) {
        return 0;
    }
    $v = pilot_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (int)$v : 0;
}

function pilot_safe_sum($c, string $table, string $col, string $where = '1=1', array $p = []): float
{
    if (!pilot_table_exists($c, $table)) {
        return 0.0;
    }
    $v = pilot_scalar($c, 'SELECT ISNULL(SUM(' . $col . '),0) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (float)$v : 0.0;
}

function pilot_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function pilot_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function pilot_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function pilot_generate_code(string $prefix): string
{
    return $prefix . '-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function pilot_normalize_mobile(?string $mobile): string
{
    $m = preg_replace('/\D+/', '', (string)$mobile) ?? '';
    if (str_starts_with($m, '98') && strlen($m) > 10) {
        $m = '0' . substr($m, 2);
    }
    return substr($m, 0, 50);
}

function pilot_validate_date(?string $date): bool
{
    if ($date === null || trim($date) === '') {
        return true;
    }
    $dt = DateTime::createFromFormat('Y-m-d', trim($date));
    return $dt !== false && $dt->format('Y-m-d') === trim($date);
}

function pilot_insert_history(
    $c,
    string $entityType,
    ?int $entityId,
    string $actionType,
    string $summary,
    ?string $oldValue = null,
    ?string $newValue = null
): bool {
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_history')) {
        return false;
    }
    return pilot_execute(
        $c,
        'INSERT INTO dbo.erp_soft_run_pilot_history (entity_type, entity_id, action_type, action_summary, old_value, new_value, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?,?)',
        [$entityType, $entityId, $actionType, $summary, $oldValue, $newValue, pilot_safe_current_user(), pilot_client_ip(), pilot_user_agent()]
    ) !== false;
}

function pilot_get_active_pilot($c): ?array
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilots')) {
        return null;
    }
    $rows = pilot_fetch_rows(
        $c,
        "SELECT TOP 1 pilot_id, pilot_code, pilot_title, pilot_status, pilot_scope, pilot_note, started_at, completed_at, created_at
         FROM dbo.erp_soft_run_pilots
         WHERE pilot_status IN (N'DRAFT', N'ACTIVE', N'REVIEW')
         ORDER BY CASE pilot_code WHEN N'PILOT-LOCAL-RC1' THEN 0 ELSE 1 END, pilot_id DESC"
    );
    return $rows[0] ?? null;
}

function pilot_get_scenarios($c, int $limit = 20): array
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_scenarios')) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    return pilot_fetch_rows(
        $c,
        'SELECT TOP ' . $limit . ' scenario_id, pilot_id, scenario_code, customer_name, mobile, vehicle_plate, vehicle_brand_model,
                contract_type, authorization_mode, jobcard_service_description, part_required, payment_preview_amount,
                crm_followup_expected, hr_attendance_sample, scenario_status, created_at, created_by
         FROM dbo.erp_soft_run_pilot_scenarios ORDER BY scenario_id DESC'
    );
}

function pilot_get_scenario($c, int $scenarioId): ?array
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_scenarios') || $scenarioId < 1) {
        return null;
    }
    $rows = pilot_fetch_rows(
        $c,
        'SELECT scenario_id, pilot_id, scenario_code, customer_name, mobile, vehicle_plate, vehicle_brand_model,
                contract_type, authorization_mode, jobcard_service_description, part_required, payment_preview_amount,
                crm_followup_expected, hr_attendance_sample, scenario_status, created_at, created_by
         FROM dbo.erp_soft_run_pilot_scenarios WHERE scenario_id=?',
        [$scenarioId]
    );
    return $rows[0] ?? null;
}

function pilot_compute_flow_from_scenario(array $scenario): array
{
    $hasCustomer = trim((string)($scenario['customer_name'] ?? '')) !== '';
    $hasVehicle = trim((string)($scenario['vehicle_plate'] ?? '')) !== '' || trim((string)($scenario['vehicle_brand_model'] ?? '')) !== '';
    $hasContract = trim((string)($scenario['contract_type'] ?? '')) !== '';
    $hasOp = trim((string)($scenario['jobcard_service_description'] ?? '')) !== '';
    $partRequired = (int)($scenario['part_required'] ?? 0) === 1;
    $payAmount = (float)($scenario['payment_preview_amount'] ?? 0);
    $crmExpected = (int)($scenario['crm_followup_expected'] ?? 0) === 1;
    $hrSample = (int)($scenario['hr_attendance_sample'] ?? 0) === 1;

    return [
        'customer_step_status' => $hasCustomer ? 'PASSED' : 'PENDING',
        'vehicle_step_status' => $hasVehicle ? 'PASSED' : ($hasCustomer ? 'READY' : 'PENDING'),
        'contract_step_status' => $hasContract ? 'PASSED' : ($hasVehicle ? 'READY' : 'PENDING'),
        'operation_step_status' => $hasOp ? 'PASSED' : ($hasContract ? 'READY' : 'PENDING'),
        'inventory_step_status' => $partRequired ? ($hasOp ? 'READY' : 'PENDING') : 'SKIPPED',
        'finance_step_status' => $payAmount > 0 ? 'PASSED' : ($hasOp ? 'READY' : 'PENDING'),
        'crm_step_status' => $crmExpected ? ($hasOp ? 'READY' : 'PENDING') : 'SKIPPED',
        'hr_step_status' => $hrSample ? 'READY' : 'SKIPPED',
    ];
}

function pilot_get_latest_flow_snapshot($c, int $scenarioId): ?array
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_flow_snapshots') || $scenarioId < 1) {
        return null;
    }
    $rows = pilot_fetch_rows(
        $c,
        'SELECT TOP 1 flow_snapshot_id, scenario_id, customer_step_status, vehicle_step_status, contract_step_status,
                operation_step_status, inventory_step_status, finance_step_status, crm_step_status, hr_step_status,
                flow_decision, flow_note, created_at, created_by
         FROM dbo.erp_soft_run_pilot_flow_snapshots WHERE scenario_id=? ORDER BY flow_snapshot_id DESC',
        [$scenarioId]
    );
    return $rows[0] ?? null;
}

function pilot_create_initial_flow_snapshot($c, int $scenarioId, array $scenario): bool
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_flow_snapshots')) {
        return false;
    }
    $flow = pilot_compute_flow_from_scenario($scenario);
    $allPassed = $flow['customer_step_status'] === 'PASSED'
        && $flow['vehicle_step_status'] === 'PASSED'
        && $flow['contract_step_status'] === 'PASSED'
        && $flow['operation_step_status'] === 'PASSED';
    $decision = $allPassed ? 'READY_FOR_REVIEW' : 'PENDING';
    $note = 'Initial pilot flow snapshot — internal tables only; no production write.';

    return pilot_execute(
        $c,
        'INSERT INTO dbo.erp_soft_run_pilot_flow_snapshots
         (scenario_id, customer_step_status, vehicle_step_status, contract_step_status, operation_step_status,
          inventory_step_status, finance_step_status, crm_step_status, hr_step_status, flow_decision, flow_note, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $scenarioId,
            $flow['customer_step_status'],
            $flow['vehicle_step_status'],
            $flow['contract_step_status'],
            $flow['operation_step_status'],
            $flow['inventory_step_status'],
            $flow['finance_step_status'],
            $flow['crm_step_status'],
            $flow['hr_step_status'],
            $decision,
            $note,
            pilot_safe_current_user(),
        ]
    ) !== false;
}

function pilot_find_duplicate_scenario($c, string $customerName, string $mobile, string $plate): bool
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_scenarios')) {
        return false;
    }
    $cnt = pilot_scalar(
        $c,
        "SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_scenarios
         WHERE customer_name=? AND ISNULL(mobile,N'')=? AND ISNULL(vehicle_plate,N'')=?
         AND created_at >= DATEADD(hour, -24, SYSUTCDATETIME())",
        [$customerName, $mobile, $plate]
    );
    return $cnt !== null && (int)$cnt > 0;
}

function pilot_get_feedback_summary($c): array
{
    if (!pilot_table_exists($c, 'erp_soft_run_pilot_feedback')) {
        return ['total' => 0, 'blocker' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'open' => 0];
    }
    return [
        'total' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback'),
        'blocker' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback', "LOWER(severity)=N'blocker'"),
        'high' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback', "LOWER(severity)=N'high'"),
        'medium' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback', "LOWER(severity)=N'medium'"),
        'low' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback', "LOWER(severity)=N'low'"),
        'open' => pilot_safe_count($c, 'erp_soft_run_pilot_feedback', "feedback_status=N'OPEN'"),
    ];
}

function pilot_get_pilot_report_summary($c): array
{
    $scenarios = pilot_table_exists($c, 'erp_soft_run_pilot_scenarios')
        ? pilot_safe_count($c, 'erp_soft_run_pilot_scenarios') : 0;
    $fb = pilot_get_feedback_summary($c);
    $blocker = (int)($fb['blocker'] ?? 0);
    $high = (int)($fb['high'] ?? 0);

    if ($blocker > 0) {
        $decision = 'NOT READY';
    } elseif ($high > 0) {
        $decision = 'NEEDS REVIEW';
    } elseif ($scenarios >= 1 && $blocker === 0 && $high === 0) {
        $decision = 'READY FOR CONTROLLED PILOT REVIEW';
    } else {
        $decision = 'PENDING';
    }

    $pilotDecision = match ($decision) {
        'NOT READY' => 'NOT READY',
        'NEEDS REVIEW' => 'PENDING',
        'READY FOR CONTROLLED PILOT REVIEW' => 'READY',
        default => 'PENDING',
    };

    return [
        'scenario_count' => $scenarios,
        'feedback' => $fb,
        'decision' => $decision,
        'pilot_decision' => $pilotDecision,
        'next_actions' => pilot_next_actions($decision, $scenarios, $blocker, $high),
    ];
}

function pilot_next_actions(string $decision, int $scenarios, int $blocker, int $high): array
{
    $actions = ['Confirm no production/SaaS/public portal activation'];
    if ($blocker > 0) {
        $actions[] = 'Fix blocker issues';
    }
    if ($high > 0) {
        $actions[] = 'Review high severity issues';
    }
    if ($scenarios < 1) {
        $actions[] = 'Run at least one full scenario';
    } elseif ($decision === 'READY FOR CONTROLLED PILOT REVIEW') {
        $actions[] = 'Proceed to controlled pilot review signoff';
    }
    return $actions;
}

function pilot_data_checklist(array $scenario, ?array $flow): array
{
    $partRequired = (int)($scenario['part_required'] ?? 0) === 1;
    $crmExpected = (int)($scenario['crm_followup_expected'] ?? 0) === 1;
    $hrSample = (int)($scenario['hr_attendance_sample'] ?? 0) === 1;
    $pay = (float)($scenario['payment_preview_amount'] ?? 0);

    $status = static function (bool $ok, bool $pending = false): string {
        if ($ok) {
            return 'OK';
        }
        return $pending ? 'PENDING' : 'WARNING';
    };

    return [
        ['label' => 'مشتری ثبت شد؟', 'status' => $status(trim((string)($scenario['customer_name'] ?? '')) !== '')],
        ['label' => 'خودرو وصل شد؟', 'status' => $status(trim((string)($scenario['vehicle_plate'] ?? '')) !== '' || trim((string)($scenario['vehicle_brand_model'] ?? '')) !== '')],
        ['label' => 'قرارداد دارد؟', 'status' => $status(trim((string)($scenario['contract_type'] ?? '')) !== '')],
        ['label' => 'عملیات / JobCard دارد؟', 'status' => $status(trim((string)($scenario['jobcard_service_description'] ?? '')) !== '')],
        ['label' => 'قطعه نیاز دارد؟', 'status' => $partRequired ? $status(true) : 'NOT APPLICABLE'],
        ['label' => 'مالی preview دارد؟', 'status' => $pay > 0 ? 'OK' : ($partRequired ? 'PENDING' : 'WARNING')],
        ['label' => 'CRM follow-up دارد؟', 'status' => $crmExpected ? ($flow && ($flow['crm_step_status'] ?? '') !== 'PENDING' ? 'OK' : 'PENDING') : 'NOT APPLICABLE'],
        ['label' => 'HR attendance sample دارد؟', 'status' => $hrSample ? 'OK' : 'NOT APPLICABLE'],
    ];
}

function pilot_flow_steps(): array
{
    return [
        ['key' => 'customer_step_status', 'title' => 'Customer', 'title_fa' => 'مشتری'],
        ['key' => 'vehicle_step_status', 'title' => 'Vehicle', 'title_fa' => 'خودرو'],
        ['key' => 'contract_step_status', 'title' => 'Contract', 'title_fa' => 'قرارداد'],
        ['key' => 'operation_step_status', 'title' => 'JobCard / Operation', 'title_fa' => 'عملیات'],
        ['key' => 'inventory_step_status', 'title' => 'Inventory', 'title_fa' => 'انبار / رزرو قطعه'],
        ['key' => 'finance_step_status', 'title' => 'Finance Preview', 'title_fa' => 'پیش‌نمایش مالی'],
        ['key' => 'crm_step_status', 'title' => 'CRM Follow-up', 'title_fa' => 'پیگیری CRM'],
        ['key' => 'hr_step_status', 'title' => 'HR Attendance', 'title_fa' => 'حضور HR'],
    ];
}

function pilot_boundary_labels(): array
{
    return [
        'INTERNAL PILOT ONLY',
        'NOT PRODUCTION',
        'NOT CUSTOMER PORTAL',
        'NOT OFFICIAL ACCOUNTING',
        'NOT SAAS',
        'NO PRODUCTION WRITE TO PHASE 1–10 TABLES',
    ];
}

function pilot_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE12_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE12_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function pilot_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(pilot_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function pilot_badge_class(string $status): string
{
    return match (strtoupper(str_replace(' ', '_', $status))) {
        'OK', 'PASSED', 'READY', 'ACTIVE', 'READY_FOR_CONTROLLED_PILOT_REVIEW', 'READY_FOR_REVIEW', 'PILOT_PASS' => 'p12pl-badge-ok',
        'PENDING', 'WARNING', 'DRAFT', 'IN_PROGRESS', 'FLOW_REVIEW', 'NEEDS_REVIEW', 'NEEDS_FIX', 'NOT_APPLICABLE' => 'p12pl-badge-warn',
        'FAILED', 'MISSING', 'NOT_READY', 'PILOT_FAIL', 'BLOCKER', 'CANCELLED' => 'p12pl-badge-fail',
        default => 'p12pl-badge-muted',
    };
}

function pilot_severity_badge(string $severity): string
{
    return match (strtolower($severity)) {
        'blocker' => 'p12pl-badge-fail',
        'high' => 'p12pl-badge-fail',
        'medium' => 'p12pl-badge-warn',
        default => 'p12pl-badge-muted',
    };
}

function pilot_format_amount($amount): string
{
    return number_format((float)$amount, 0, '.', ',') . ' تومان (preview)';
}

function pilot_flash(string $key): string
{
    return match ($key) {
        'scenario_ok' => 'سناریوی Pilot با موفقیت ثبت شد.',
        'scenario_dup' => 'هشدار: سناریوی مشابه در ۲۴ ساعت اخیر وجود دارد.',
        'feedback_ok' => 'بازخورد Pilot ثبت شد.',
        default => '',
    };
}

function pilot_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . pilot_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-stabilization.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-pilot.css">';
    echo '</head><body class="m360-rtl p12pl-page"><div class="p12pl-wrap">';
    echo '<div class="p12pl-banner">INTERNAL PILOT ONLY · NOT PRODUCTION · NOT CUSTOMER PORTAL · NOT OFFICIAL ACCOUNTING · NOT SAAS</div>';
}

function pilot_render_foot(): void
{
    echo '<p class="p12pl-footer">';
    echo '<a href="erp-soft-run-pilot-center.php">Pilot Center</a> · ';
    echo '<a href="erp-pilot-scenario-builder.php">Scenario Builder</a> · ';
    echo '<a href="erp-pilot-flow-viewer.php">Flow Viewer</a> · ';
    echo '<a href="erp-pilot-data-checklist.php">Data Checklist</a> · ';
    echo '<a href="erp-pilot-feedback.php">Feedback</a> · ';
    echo '<a href="erp-soft-run-pilot-report.php">Pilot Report</a>';
    echo '</p></div></body></html>';
}

function pilot_error(string $title, string $msg): void
{
    pilot_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . pilot_h($msg) . '</p></div>';
    pilot_render_foot();
    exit;
}
