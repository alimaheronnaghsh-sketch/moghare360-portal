<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 CRM Helper
 */

const ERP_PHASE6_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE6_PLACEHOLDER_ACTIONS = [
    'crm.followup.view' => 'placeholder_crm_followup_view',
    'crm.followup.write' => 'placeholder_crm_followup_write',
    'crm.satisfaction.write' => 'placeholder_crm_satisfaction_write',
    'crm.score.view' => 'placeholder_crm_score_view',
    'crm.upsell.view' => 'placeholder_crm_upsell_view',
    'crm.upsell.write' => 'placeholder_crm_upsell_write',
];

function crm_require_helper(string $fileName): void
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

crm_require_helper('erp-auth-context.php');
crm_require_helper('erp-permission-guard.php');
crm_require_helper('erp-csrf.php');

if (!function_exists('erp_csrf_input')) {
    function erp_csrf_input(string $purpose): string
    {
        return '<input type="hidden" name="erp_csrf_token" value="' .
            htmlspecialchars(erp_csrf_create_token($purpose), ENT_QUOTES, 'UTF-8') . '">';
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

function crm_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function crm_post_string(string $k): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function crm_get_string(string $k): string { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : ''; }
function crm_post_int(string $k): ?int { $r = crm_post_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function crm_get_int(string $k): ?int { $r = crm_get_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function crm_post_float(string $k): ?float { $r = crm_post_string($k); return $r !== '' && is_numeric($r) ? (float)$r : null; }

function crm_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function crm_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function crm_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function crm_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function crm_db()
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

function crm_table_exists($c, string $t): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $t]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function crm_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) {
        return false;
    }
    return $s;
}

function crm_scalar($c, string $sql, array $p = []): ?string
{
    $s = crm_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function crm_fetch_rows($c, string $sql, array $p = []): array
{
    $s = crm_execute($c, $sql, $p);
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

function crm_scope_identity($c): ?int
{
    $v = crm_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
    return ($v !== null && is_numeric($v)) ? (int)$v : null;
}

function crm_insert_history($c, string $entityType, ?int $entityId, string $actionType, string $summary, ?string $old = null, ?string $new = null): bool
{
    if (!crm_table_exists($c, 'erp_crm_history')) {
        return false;
    }
    return crm_execute(
        $c,
        'INSERT INTO dbo.erp_crm_history (entity_type,entity_id,action_type,action_summary,old_value,new_value,created_by,source_ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)',
        [$entityType, $entityId, $actionType, $summary, $old, $new, crm_safe_current_user(), crm_client_ip(), crm_user_agent()]
    ) !== false;
}

function crm_generate_followup_code(): string
{
    return 'FU-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function crm_generate_score_code(): string
{
    return 'SCR-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function crm_generate_upsell_code(): string
{
    return 'UP-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function crm_validate_score_1_to_10(?int $score, bool $required = false): ?int
{
    if ($score === null) {
        return $required ? null : null;
    }
    if ($score < 1 || $score > 10) {
        return null;
    }
    return $score;
}

function crm_get_schedule($c, int $id): ?array
{
    $rows = crm_fetch_rows($c, 'SELECT TOP 1 * FROM dbo.erp_crm_followup_schedules WHERE followup_schedule_id=?', [$id]);
    return $rows[0] ?? null;
}

function crm_get_customer_preview($c, ?int $customerId, ?int $intakeId = null): ?array
{
    if ($intakeId !== null && crm_table_exists($c, 'erp_customer_intakes')) {
        $rows = crm_fetch_rows($c, 'SELECT TOP 1 intake_id, full_name, mobile, national_code FROM dbo.erp_customer_intakes WHERE intake_id=?', [$intakeId]);
        if ($rows !== []) {
            return $rows[0];
        }
    }
    if ($customerId !== null && crm_table_exists($c, 'erp_customer_intakes')) {
        $rows = crm_fetch_rows($c, 'SELECT TOP 1 intake_id, full_name, mobile, national_code FROM dbo.erp_customer_intakes WHERE intake_id=?', [$customerId]);
        if ($rows !== []) {
            return $rows[0];
        }
    }
    if ($customerId !== null && crm_table_exists($c, 'Customers_v2')) {
        $rows = crm_fetch_rows($c, 'SELECT TOP 1 CustomerID AS intake_id, FullName AS full_name FROM dbo.Customers_v2 WHERE CustomerID=?', [$customerId]);
        return $rows[0] ?? null;
    }
    return null;
}

function crm_get_operation_preview($c, ?int $operationCaseId): ?array
{
    if ($operationCaseId === null || !crm_table_exists($c, 'erp_operation_cases')) {
        return null;
    }
    $rows = crm_fetch_rows(
        $c,
        'SELECT TOP 1 operation_case_id, operation_code, current_stage, current_status, jobcard_id, customer_id FROM dbo.erp_operation_cases WHERE operation_case_id=?',
        [$operationCaseId]
    );
    return $rows[0] ?? null;
}

function crm_resolve_delivery_base_time($c, int $operationCaseId): ?string
{
    if (crm_table_exists($c, 'erp_operation_delivery_checks')) {
        $checked = crm_scalar(
            $c,
            'SELECT TOP 1 CONVERT(NVARCHAR(30), checked_at, 126) FROM dbo.erp_operation_delivery_checks WHERE operation_case_id=? ORDER BY delivery_check_id DESC',
            [$operationCaseId]
        );
        if ($checked !== null && $checked !== '') {
            return $checked;
        }
    }
    if (crm_table_exists($c, 'erp_operation_cases')) {
        $stage = crm_scalar($c, 'SELECT current_stage FROM dbo.erp_operation_cases WHERE operation_case_id=?', [$operationCaseId]);
        if ($stage === 'DELIVERED') {
            $updated = crm_scalar($c, 'SELECT TOP 1 CONVERT(NVARCHAR(30), updated_at, 126) FROM dbo.erp_operation_cases WHERE operation_case_id=?', [$operationCaseId]);
            if ($updated !== null && $updated !== '') {
                return $updated;
            }
        }
    }
    return null;
}

function crm_create_post_delivery_followup($c, array $data): ?int
{
    if (!crm_table_exists($c, 'erp_crm_followup_schedules')) {
        return null;
    }

    $operationCaseId = $data['operation_case_id'] ?? null;
    $baseTime = null;
    if ($operationCaseId !== null) {
        $baseTime = crm_resolve_delivery_base_time($c, (int)$operationCaseId);
    }

    try {
        if (!empty($data['scheduled_at'])) {
            $scheduled = str_replace('T', ' ', (string)$data['scheduled_at']);
        } elseif ($baseTime !== null) {
            $scheduled = (new DateTimeImmutable($baseTime))->modify('+3 days')->format('Y-m-d H:i:s');
        } else {
            $scheduled = (new DateTimeImmutable('now'))->modify('+3 days')->format('Y-m-d H:i:s');
        }
    } catch (Throwable) {
        $scheduled = date('Y-m-d H:i:s', strtotime('+3 days'));
    }

    $code = crm_generate_followup_code();
    $ok = crm_execute(
        $c,
        'INSERT INTO dbo.erp_crm_followup_schedules (customer_id, intake_id, vehicle_binding_id, operation_case_id, jobcard_id, cost_header_id, followup_code, followup_reason, scheduled_at, priority_level, assigned_to_text, source_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $data['customer_id'] ?? null,
            $data['intake_id'] ?? null,
            $data['vehicle_binding_id'] ?? null,
            $operationCaseId,
            $data['jobcard_id'] ?? null,
            $data['cost_header_id'] ?? null,
            $code,
            $data['followup_reason'] ?? 'POST_DELIVERY',
            $scheduled,
            $data['priority_level'] ?? 'NORMAL',
            $data['assigned_to_text'] ?? null,
            $data['source_note'] ?? 'پیگیری ۳ روز پس از تحویل',
            crm_safe_current_user(),
        ]
    );
    if ($ok === false) {
        return null;
    }
    $id = crm_scope_identity($c);
    if ($id !== null) {
        crm_insert_history($c, 'FOLLOWUP_SCHEDULE', $id, 'CREATE', 'ایجاد پیگیری پس از تحویل', null, $code);
    }
    return $id;
}

function crm_calculate_revenue_score($c, ?int $customerId, ?int $intakeId): float
{
    if (!crm_table_exists($c, 'erp_jobcard_cost_headers')) {
        return 0.0;
    }
    $cid = $customerId ?? $intakeId;
    if ($cid === null) {
        return 0.0;
    }
    $paid = crm_scalar($c, 'SELECT ISNULL(SUM(paid_total),0) FROM dbo.erp_jobcard_cost_headers WHERE customer_id=?', [$cid]);
    if ($paid === null) {
        return 0.0;
    }
    $val = (float)$paid;
    if ($val <= 0) {
        return 0.0;
    }
    return min(30.0, $val / 1000000.0 * 10.0);
}

function crm_calculate_customer_score($c, array $survey): array
{
    $overall = (int)($survey['overall_score'] ?? 0);
    $comeback = isset($survey['comeback_probability_score']) && $survey['comeback_probability_score'] !== ''
        ? (int)$survey['comeback_probability_score']
        : $overall;

    $satisfactionScore = (float)($overall * 10);
    $loyaltyScore = (float)($comeback * 5);
    $revenueScore = crm_calculate_revenue_score($c, $survey['customer_id'] ?? null, $survey['intake_id'] ?? null);

    $complaintPenalty = 0.0;
    if (!empty($survey['complaint_text'])) {
        $complaintPenalty = 30.0;
    }
    if ($overall < 5) {
        $complaintPenalty += 20.0;
    } elseif ($overall < 7) {
        $complaintPenalty += 10.0;
    }

    $total = $satisfactionScore + $revenueScore + $loyaltyScore - $complaintPenalty;
    if ($total < 0) {
        $total = 0.0;
    }

    return [
        'satisfaction_score' => $satisfactionScore,
        'revenue_score' => $revenueScore,
        'loyalty_score' => $loyaltyScore,
        'complaint_penalty' => $complaintPenalty,
        'total_score' => $total,
    ];
}

function crm_detect_vip_status(float $totalScore, float $complaintPenalty, float $satisfactionScore, bool $hasComplaint): string
{
    if ($hasComplaint && ($complaintPenalty >= 30 || $satisfactionScore < 30)) {
        return 'COMPLAINT_PRIORITY';
    }
    if ($complaintPenalty >= 30 || $satisfactionScore < 30) {
        return 'AT_RISK';
    }
    if ($totalScore >= 80) {
        return 'VIP';
    }
    if ($totalScore >= 50) {
        return 'LOYAL';
    }
    return 'STANDARD';
}

function crm_insert_score_card($c, array $data): ?int
{
    if (!crm_table_exists($c, 'erp_customer_score_cards')) {
        return null;
    }
    $code = crm_generate_score_code();
    $vip = crm_detect_vip_status(
        (float)$data['total_score'],
        (float)$data['complaint_penalty'],
        (float)$data['satisfaction_score'],
        !empty($data['has_complaint'])
    );
    $ok = crm_execute(
        $c,
        'INSERT INTO dbo.erp_customer_score_cards (customer_id, intake_id, mobile, score_code, total_score, satisfaction_score, revenue_score, loyalty_score, complaint_penalty, vip_status, score_note, calculated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $data['customer_id'] ?? null,
            $data['intake_id'] ?? null,
            $data['mobile'] ?? null,
            $code,
            $data['total_score'],
            $data['satisfaction_score'],
            $data['revenue_score'],
            $data['loyalty_score'],
            $data['complaint_penalty'],
            $vip,
            $data['score_note'] ?? null,
            crm_safe_current_user(),
        ]
    );
    if ($ok === false) {
        return null;
    }
    $id = crm_scope_identity($c);
    if ($id !== null) {
        crm_insert_history($c, 'SCORE_CARD', $id, 'CREATE', 'ثبت امتیاز مشتری', null, $vip);
    }
    return $id;
}

function crm_map_contact_to_status(string $contactResult, ?string $nextFollowupAt): string
{
    return match (strtoupper($contactResult)) {
        'NO_ANSWER' => 'NO_ANSWER',
        'CALLBACK_REQUESTED' => $nextFollowupAt !== null && $nextFollowupAt !== '' ? 'RESCHEDULED' : 'CONTACTED',
        'SATISFIED', 'COMPLETED' => 'COMPLETED',
        'COMPLAINT', 'NEEDS_MANAGER' => 'CONTACTED',
        default => 'CONTACTED',
    };
}

function crm_vip_label(string $status): string
{
    return match (strtoupper($status)) {
        'VIP' => 'VIP',
        'LOYAL' => 'وفادار',
        'AT_RISK' => 'در معرض ریزش',
        'COMPLAINT_PRIORITY' => 'اولویت شکایت',
        default => 'استاندارد',
    };
}

function crm_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'VIP', 'COMPLETED', 'PAID' => 'p1cc-badge-active',
        'LOYAL', 'CONTACTED', 'PARTIAL_PAID' => 'p1cc-badge-duplicate',
        'AT_RISK', 'NO_ANSWER', 'COMPLAINT_PRIORITY' => 'p1cc-error',
        'PENDING', 'OPEN' => 'p1cc-badge-new',
        default => 'p1cc-badge-draft',
    };
}

function crm_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE6_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE6_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function crm_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(crm_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function crm_render_head(string $title, bool $ro = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . crm_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-crm-system.css">';
    echo '</head><body class="m360-rtl p6crm-page"><div class="p6crm-wrap">';
    if ($ro) {
        echo '<div class="p6crm-readonly-banner">فقط خواندنی — بدون ارسال پیام خارجی</div>';
    }
}

function crm_render_foot(): void
{
    echo '<p class="p6crm-footer"><a href="erp-crm-followup-board.php">تابلو پیگیری</a> · <a href="erp-customer-score-board.php">امتیاز مشتری</a> · <a href="erp-upsell-opportunities.php">فرصت فروش</a></p></div></body></html>';
}

function crm_error(string $title, string $msg): void
{
    crm_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . crm_h($msg) . '</p></div>';
    crm_render_foot();
    exit;
}

function crm_flash(string $key): string
{
    return match ($key) {
        'followup_ok' => 'نتیجه تماس با موفقیت ثبت شد.',
        'schedule_ok' => 'پیگیری با موفقیت ثبت شد.',
        'satisfaction_ok' => 'رضایت‌سنجی با موفقیت ثبت شد.',
        'upsell_ok' => 'فرصت فروش با موفقیت ثبت شد.',
        'status_ok' => 'وضعیت فرصت فروش به‌روزرسانی شد.',
        default => '',
    };
}
