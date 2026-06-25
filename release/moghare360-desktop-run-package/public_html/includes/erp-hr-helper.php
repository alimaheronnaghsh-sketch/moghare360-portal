<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 7 HR Helper
 */

const ERP_PHASE7_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE7_PLACEHOLDER_ACTIONS = [
    'hr.dashboard.view' => 'placeholder_hr_dashboard_view',
    'hr.employee.view' => 'placeholder_hr_employee_view',
    'hr.employee.write' => 'placeholder_hr_employee_write',
    'hr.contract.write' => 'placeholder_hr_contract_write',
    'hr.attendance.write' => 'placeholder_hr_attendance_write',
    'hr.payroll.preview' => 'placeholder_hr_payroll_preview',
    'hr.training.write' => 'placeholder_hr_training_write',
];

function hr_require_helper(string $fileName): void
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

hr_require_helper('erp-auth-context.php');
hr_require_helper('erp-permission-guard.php');
hr_require_helper('erp-csrf.php');

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

function hr_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function hr_post_string(string $k): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function hr_get_string(string $k): string { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : ''; }
function hr_post_int(string $k): ?int { $r = hr_post_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function hr_get_int(string $k): ?int { $r = hr_get_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function hr_post_float(string $k): ?float { $r = hr_post_string($k); return $r !== '' && is_numeric($r) ? (float)$r : null; }
function hr_post_bool(string $k): bool { return hr_post_string($k) === '1' || hr_post_string($k) === 'on'; }

function hr_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function hr_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function hr_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function hr_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function hr_db()
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

function hr_table_exists($c, string $t): bool
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

function hr_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) {
        return false;
    }
    return $s;
}

function hr_scalar($c, string $sql, array $p = []): ?string
{
    $s = hr_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function hr_fetch_rows($c, string $sql, array $p = []): array
{
    $s = hr_execute($c, $sql, $p);
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

function hr_scope_identity($c): ?int
{
    $v = hr_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
    return ($v !== null && is_numeric($v)) ? (int)$v : null;
}

function hr_insert_history($c, string $entityType, ?int $entityId, string $actionType, string $summary, ?string $old = null, ?string $new = null): bool
{
    if (!hr_table_exists($c, 'erp_hr_history')) {
        return false;
    }
    return hr_execute(
        $c,
        'INSERT INTO dbo.erp_hr_history (entity_type,entity_id,action_type,action_summary,old_value,new_value,created_by,source_ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)',
        [$entityType, $entityId, $actionType, $summary, $old, $new, hr_safe_current_user(), hr_client_ip(), hr_user_agent()]
    ) !== false;
}

function hr_generate_employee_code(): string
{
    return 'EMP-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function hr_generate_contract_code(): string
{
    return 'HR-CON-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function hr_validate_date(?string $date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }
    $ts = strtotime($date);
    return $ts === false ? null : date('Y-m-d', $ts);
}

function hr_time_to_hours(?string $time): float
{
    if ($time === null || $time === '') {
        return 0.0;
    }
    $parts = explode(':', $time);
    if (count($parts) < 2) {
        return 0.0;
    }
    return (float)$parts[0] + ((float)$parts[1] / 60.0);
}

function hr_calculate_attendance_hours(?string $checkIn, ?string $checkOut, float $breakHours, float $requiredHours): array
{
    $workHours = 0.0;
    if ($checkIn !== null && $checkIn !== '' && $checkOut !== null && $checkOut !== '') {
        $in = hr_time_to_hours($checkIn);
        $out = hr_time_to_hours($checkOut);
        $workHours = $out >= $in ? $out - $in : (24.0 - $in) + $out;
    }
    $net = max(0.0, $workHours - $breakHours);
    $overtime = max(0.0, $net - $requiredHours);
    $absence = max(0.0, $requiredHours - $net);
    return [
        'work_hours' => round($workHours, 2),
        'net_work_hours' => round($net, 2),
        'overtime_hours' => round($overtime, 2),
        'absence_hours' => round($absence, 2),
    ];
}

function hr_calculate_payroll_preview(array $data): array
{
    $base = (float)($data['base_salary'] ?? 0);
    $allow = (float)($data['allowance_total'] ?? 0);
    $ot = (float)($data['overtime_amount'] ?? 0);
    $fri = (float)($data['friday_work_amount'] ?? 0);
    $bonus = (float)($data['bonus_amount'] ?? 0);
    $ded = (float)($data['deduction_amount'] ?? 0);
    $gross = $base + $allow + $ot + $fri + $bonus;
    $net = $gross - $ded;
    if ($net < 0) {
        $net = 0.0;
    }
    return [
        'gross_preview_amount' => round($gross, 2),
        'net_preview_amount' => round($net, 2),
    ];
}

function hr_get_employee_preview($c, int $employeeId): ?array
{
    $rows = hr_fetch_rows($c, 'SELECT TOP 1 * FROM dbo.erp_hr_employees WHERE employee_id=?', [$employeeId]);
    return $rows[0] ?? null;
}

function hr_check_duplicate_employee($c, string $mobile, string $nationalCode): string
{
    $warnings = [];
    if ($mobile !== '' && hr_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE mobile=?', [$mobile]) !== '0') {
        $warnings[] = 'موبایل تکراری';
    }
    if ($nationalCode !== '' && hr_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE national_code=?', [$nationalCode]) !== '0') {
        $warnings[] = 'کد ملی تکراری';
    }
    return $warnings === [] ? '' : implode('، ', $warnings);
}

function hr_format_amount(string|float $amount): string
{
    return number_format((float)$amount, 0, '.', ',');
}

function hr_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'ACTIVE', 'APPROVED_PREVIEW', 'CALCULATED', 'PASSED', 'POSITIVE' => 'p1cc-badge-active',
        'ON_LEAVE', 'DRAFT', 'RECORDED' => 'p1cc-badge-draft',
        'SUSPENDED', 'REJECTED', 'FAILED', 'HIGH' => 'p1cc-error',
        'EXITED', 'CANCELLED', 'EXPIRED' => 'p1cc-badge-duplicate',
        default => 'p1cc-badge-new',
    };
}

function hr_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE7_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE7_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function hr_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(hr_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function hr_render_head(string $title, bool $ro = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . hr_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-hr-system.css">';
    echo '</head><body class="m360-rtl p7hr-page"><div class="p7hr-wrap">';
    if ($ro) {
        echo '<div class="p7hr-readonly-banner">پورتال داخلی پرسنل — بدون ورود staff login جداگانه</div>';
    }
}

function hr_render_foot(): void
{
    echo '<p class="p7hr-footer"><a href="erp-hr-dashboard.php">داشبورد HR</a> · <a href="erp-employee-create.php">ثبت کارمند</a> · <a href="erp-employee-profile.php">پروفایل</a> · <a href="erp-attendance-entry.php">حضور</a> · <a href="erp-payroll-preview.php">حقوق preview</a></p></div></body></html>';
}

function hr_error(string $title, string $msg): void
{
    hr_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . hr_h($msg) . '</p></div>';
    hr_render_foot();
    exit;
}

function hr_flash(string $key): string
{
    return match ($key) {
        'employee_ok' => 'کارمند با موفقیت ثبت شد.',
        'contract_ok' => 'قرارداد کاری با موفقیت ثبت شد.',
        'attendance_ok' => 'حضور و غیاب با موفقیت ثبت شد.',
        'payroll_ok' => 'پیش‌نمایش حقوق با موفقیت ثبت شد.',
        'training_ok' => 'رکورد آموزش با موفقیت ثبت شد.',
        'disciplinary_ok' => 'رکورد ترفیع/تنبیه با موفقیت ثبت شد.',
        default => '',
    };
}
