<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Pricing Engine Helper
 */

const ERP_PHASE5_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE5_PLACEHOLDER_ACTIONS = [
    'finance.dashboard.view' => 'placeholder_finance_dashboard_view',
    'finance.pricing.view' => 'placeholder_finance_pricing_view',
    'finance.pricing.write' => 'placeholder_finance_pricing_write',
    'finance.cost.view' => 'placeholder_finance_cost_view',
    'finance.cost.write' => 'placeholder_finance_cost_write',
    'finance.payment.view' => 'placeholder_finance_payment_view',
    'finance.payment.write' => 'placeholder_finance_payment_write',
    'finance.invoice.preview' => 'placeholder_finance_invoice_preview',
];

function pricing_require_helper(string $fileName): void
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

pricing_require_helper('erp-auth-context.php');
pricing_require_helper('erp-permission-guard.php');
pricing_require_helper('erp-csrf.php');

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

function pricing_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function pricing_post_string(string $k): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function pricing_get_string(string $k): string { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : ''; }
function pricing_post_int(string $k): ?int { $r = pricing_post_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function pricing_get_int(string $k): ?int { $r = pricing_get_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function pricing_post_float(string $k): ?float { $r = pricing_post_string($k); return $r !== '' && is_numeric($r) ? (float)$r : null; }

function pricing_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function pricing_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function pricing_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function pricing_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function pricing_db()
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

function pricing_table_exists($c, string $t): bool
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

function pricing_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) {
        return false;
    }
    return $s;
}

function pricing_scalar($c, string $sql, array $p = []): ?string
{
    $s = pricing_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function pricing_fetch_rows($c, string $sql, array $p = []): array
{
    $s = pricing_execute($c, $sql, $p);
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

function pricing_scope_identity($c): ?int
{
    $v = pricing_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
    return ($v !== null && is_numeric($v)) ? (int)$v : null;
}

function pricing_insert_history($c, string $entityType, ?int $entityId, string $actionType, string $summary, ?string $old = null, ?string $new = null): bool
{
    if (!pricing_table_exists($c, 'erp_finance_history')) {
        return false;
    }
    return pricing_execute(
        $c,
        'INSERT INTO dbo.erp_finance_history (entity_type,entity_id,action_type,action_summary,old_value,new_value,created_by,source_ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)',
        [$entityType, $entityId, $actionType, $summary, $old, $new, pricing_safe_current_user(), pricing_client_ip(), pricing_user_agent()]
    ) !== false;
}

function pricing_generate_cost_code(): string
{
    return 'COST-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function pricing_generate_payment_code(): string
{
    return 'PAY-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function pricing_generate_invoice_preview_code(): string
{
    return 'INV-PREV-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function pricing_calculate_line_total(float $qty, float $unitPrice, float $discount): float
{
    $total = ($qty * $unitPrice) - $discount;
    return $total > 0 ? $total : 0.0;
}

function pricing_resolve_payment_status(float $payable, float $paid): string
{
    if ($payable <= 0 && $paid <= 0) {
        return 'UNPAID';
    }
    if ($paid <= 0) {
        return 'UNPAID';
    }
    if ($paid < $payable) {
        return 'PARTIAL_PAID';
    }
    if (abs($paid - $payable) < 0.01) {
        return 'PAID';
    }
    return 'OVERPAID';
}

function pricing_get_cost_header($c, int $headerId): ?array
{
    $rows = pricing_fetch_rows($c, 'SELECT TOP 1 * FROM dbo.erp_jobcard_cost_headers WHERE cost_header_id=?', [$headerId]);
    return $rows[0] ?? null;
}

function pricing_sum_paid_total($c, int $headerId): float
{
    if (!pricing_table_exists($c, 'erp_payment_records')) {
        return 0.0;
    }
    $v = pricing_scalar(
        $c,
        "SELECT ISNULL(SUM(payment_amount),0) FROM dbo.erp_payment_records WHERE cost_header_id=? AND payment_status='RECORDED'",
        [$headerId]
    );
    return $v !== null ? (float)$v : 0.0;
}

function pricing_update_payment_status($c, int $headerId): bool
{
    $header = pricing_get_cost_header($c, $headerId);
    if ($header === null) {
        return false;
    }
    $payable = (float)($header['payable_total'] ?? '0');
    $paid = pricing_sum_paid_total($c, $headerId);
    $remaining = $payable - $paid;
    $status = pricing_resolve_payment_status($payable, $paid);

    return pricing_execute(
        $c,
        'UPDATE dbo.erp_jobcard_cost_headers SET paid_total=?, remaining_total=?, payment_status=?, updated_at=SYSUTCDATETIME(), updated_by=? WHERE cost_header_id=?',
        [$paid, $remaining, $status, pricing_safe_current_user(), $headerId]
    ) !== false;
}

function pricing_recalculate_cost_header($c, int $headerId): bool
{
    if (!pricing_table_exists($c, 'erp_jobcard_cost_lines')) {
        return false;
    }

    $lines = pricing_fetch_rows($c, 'SELECT line_type, line_total FROM dbo.erp_jobcard_cost_lines WHERE cost_header_id=?', [$headerId]);

    $service = 0.0;
    $labour = 0.0;
    $parts = 0.0;
    $discount = 0.0;

    foreach ($lines as $line) {
        $type = strtoupper((string)($line['line_type'] ?? ''));
        $amt = (float)($line['line_total'] ?? '0');
        match ($type) {
            'SERVICE' => $service += $amt,
            'LABOUR' => $labour += $amt,
            'PART' => $parts += $amt,
            'DISCOUNT', 'MANUAL_ADJUSTMENT' => $discount += $amt,
            default => null,
        };
    }

    $payable = $service + $labour + $parts - $discount;
    if ($payable < 0) {
        $payable = 0.0;
    }

    $paid = pricing_sum_paid_total($c, $headerId);
    $remaining = $payable - $paid;
    $payStatus = pricing_resolve_payment_status($payable, $paid);

    $ok = pricing_execute(
        $c,
        'UPDATE dbo.erp_jobcard_cost_headers SET service_total=?, labour_total=?, parts_total=?, discount_total=?, payable_total=?, paid_total=?, remaining_total=?, payment_status=?, calculation_status=?, updated_at=SYSUTCDATETIME(), updated_by=? WHERE cost_header_id=?',
        [$service, $labour, $parts, $discount, $payable, $paid, $remaining, $payStatus, 'CALCULATED', pricing_safe_current_user(), $headerId]
    );

    if ($ok !== false) {
        pricing_insert_history($c, 'COST_HEADER', $headerId, 'RECALCULATE', 'محاسبه مجدد هزینه JobCard', null, (string)$payable);
    }

    return $ok !== false;
}

function pricing_get_or_create_cost_header($c, array $data): ?int
{
    if (!pricing_table_exists($c, 'erp_jobcard_cost_headers')) {
        return null;
    }

    $operationCaseId = $data['operation_case_id'] ?? null;
    $jobcardId = $data['jobcard_id'] ?? null;

    if ($operationCaseId !== null) {
        $existing = pricing_scalar($c, 'SELECT TOP 1 cost_header_id FROM dbo.erp_jobcard_cost_headers WHERE operation_case_id=? ORDER BY cost_header_id DESC', [$operationCaseId]);
        if ($existing !== null) {
            return (int)$existing;
        }
    }
    if ($jobcardId !== null) {
        $existing = pricing_scalar($c, 'SELECT TOP 1 cost_header_id FROM dbo.erp_jobcard_cost_headers WHERE jobcard_id=? ORDER BY cost_header_id DESC', [$jobcardId]);
        if ($existing !== null) {
            return (int)$existing;
        }
    }

    $costCode = pricing_generate_cost_code();
    $ok = pricing_execute(
        $c,
        'INSERT INTO dbo.erp_jobcard_cost_headers (operation_case_id, jobcard_id, customer_id, vehicle_binding_id, cost_code, preview_note, created_by) VALUES (?,?,?,?,?,?,?)',
        [
            $operationCaseId,
            $jobcardId,
            $data['customer_id'] ?? null,
            $data['vehicle_binding_id'] ?? null,
            $costCode,
            $data['preview_note'] ?? null,
            pricing_safe_current_user(),
        ]
    );
    if ($ok === false) {
        return null;
    }

    $id = pricing_scope_identity($c);
    if ($id !== null) {
        pricing_insert_history($c, 'COST_HEADER', $id, 'CREATE', 'ایجاد سربرگ هزینه', null, $costCode);
    }
    return $id;
}

function pricing_get_operation_case($c, int $caseId): ?array
{
    if (!pricing_table_exists($c, 'erp_operation_cases')) {
        return null;
    }
    $rows = pricing_fetch_rows(
        $c,
        'SELECT TOP 1 operation_case_id, operation_code, current_stage, jobcard_id, customer_id, vehicle_binding_id FROM dbo.erp_operation_cases WHERE operation_case_id=?',
        [$caseId]
    );
    return $rows[0] ?? null;
}

function pricing_get_customer_preview($c, ?int $customerId): ?array
{
    if ($customerId === null) {
        return null;
    }
    if (pricing_table_exists($c, 'erp_customer_intakes')) {
        $rows = pricing_fetch_rows(
            $c,
            'SELECT TOP 1 intake_id AS customer_ref, full_name, mobile FROM dbo.erp_customer_intakes WHERE intake_id=?',
            [$customerId]
        );
        if ($rows !== []) {
            return $rows[0];
        }
    }
    if (pricing_table_exists($c, 'Customers_v2')) {
        $rows = pricing_fetch_rows($c, 'SELECT TOP 1 CustomerID AS customer_ref, FullName AS full_name FROM dbo.Customers_v2 WHERE CustomerID=?', [$customerId]);
        return $rows[0] ?? null;
    }
    return null;
}

function pricing_payment_status_label(string $status): string
{
    return match (strtoupper($status)) {
        'UNPAID' => 'پرداخت‌نشده',
        'PARTIAL_PAID' => 'پرداخت جزئی',
        'PAID' => 'پرداخت‌شده',
        'OVERPAID' => 'پرداخت مازاد',
        'CANCELLED' => 'لغو شده',
        default => $status,
    };
}

function pricing_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'PAID' => 'p1cc-badge-active',
        'PARTIAL_PAID' => 'p1cc-badge-duplicate',
        'OVERPAID' => 'p1cc-badge-new',
        'UNPAID' => 'p1cc-error',
        default => 'p1cc-badge-draft',
    };
}

function pricing_format_amount(string|float $amount): string
{
    return number_format((float)$amount, 0, '.', ',');
}

function pricing_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE5_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE5_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function pricing_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(pricing_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function pricing_render_head(string $title, bool $ro = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . pricing_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-financial-system.css">';
    echo '</head><body class="m360-rtl p5fs-page"><div class="p5fs-wrap">';
    if ($ro) {
        echo '<div class="p5fs-readonly-banner">فقط خواندنی</div>';
    }
}

function pricing_render_foot(): void
{
    echo '<p class="p5fs-footer"><a href="erp-finance-control-center.php">مرکز مالی</a> · <a href="erp-service-price-list.php">لیست قیمت</a> · <a href="erp-jobcard-cost-preview.php">هزینه JobCard</a> · <a href="erp-payment-tracking.php">پرداخت‌ها</a> · <a href="erp-invoice-preview.php">پیش‌نمایش فاکتور</a></p></div></body></html>';
}

function pricing_error(string $title, string $msg): void
{
    pricing_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . pricing_h($msg) . '</p></div>';
    pricing_render_foot();
    exit;
}

function pricing_flash(string $key): string
{
    return match ($key) {
        'payment_ok' => 'پرداخت با موفقیت ثبت شد.',
        'cost_ok' => 'سربرگ هزینه ثبت شد.',
        'line_ok' => 'ردیف هزینه اضافه شد.',
        'recalc_ok' => 'محاسبه مجدد انجام شد.',
        'preview_ok' => 'پیش‌نمایش فاکتور داخلی ایجاد شد.',
        'price_ok' => 'قیمت‌گذاری ثبت شد.',
        default => '',
    };
}

function pricing_create_global_snapshot($c): ?int
{
    if (!pricing_table_exists($c, 'erp_financial_summary_snapshots') || !pricing_table_exists($c, 'erp_jobcard_cost_headers')) {
        return null;
    }

    $agg = pricing_fetch_rows(
        $c,
        "SELECT ISNULL(SUM(payable_total),0) AS tp, ISNULL(SUM(paid_total),0) AS tpd, ISNULL(SUM(remaining_total),0) AS tr,
                SUM(CASE WHEN payment_status='UNPAID' THEN 1 ELSE 0 END) AS uc,
                SUM(CASE WHEN payment_status='PARTIAL_PAID' THEN 1 ELSE 0 END) AS pc,
                SUM(CASE WHEN payment_status IN ('PAID','OVERPAID') THEN 1 ELSE 0 END) AS pac
         FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'"
    );
    $a = $agg[0] ?? [];

    $ok = pricing_execute(
        $c,
        'INSERT INTO dbo.erp_financial_summary_snapshots (snapshot_scope, total_payable, total_paid, total_remaining, unpaid_count, partial_paid_count, paid_count, snapshot_note, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
        ['GLOBAL', (float)($a['tp'] ?? 0), (float)($a['tpd'] ?? 0), (float)($a['tr'] ?? 0), (int)($a['uc'] ?? 0), (int)($a['pc'] ?? 0), (int)($a['pac'] ?? 0), 'Global snapshot', pricing_safe_current_user()]
    );
    return $ok === false ? null : pricing_scope_identity($c);
}
