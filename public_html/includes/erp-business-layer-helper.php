<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 8 Business Layer UI Helper (non-sensitive, read-only)
 */

const ERP_PHASE8_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE8_PLACEHOLDER_ACTIONS = [
    'business.layer.view' => 'placeholder_business_layer_view',
    'business.layer.operational' => 'placeholder_business_layer_operational',
];

function bl_require_helper(string $fileName): void
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

bl_require_helper('erp-auth-context.php');
bl_require_helper('erp-permission-guard.php');

function bl_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bl_public_root(): string
{
    return dirname(__DIR__);
}

function bl_page_exists(string $page): bool
{
    return is_file(bl_public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page));
}

function bl_db()
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

function bl_table_exists($c, string $t): bool
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

function bl_scalar($c, string $sql, array $p = []): ?string
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p) || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function bl_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE8_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE8_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function bl_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(bl_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function bl_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . bl_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-business-layer.css">';
    echo '</head><body class="m360-rtl p8bl-page"><div class="p8bl-wrap">';
}

function bl_render_foot(): void
{
    echo '<p class="p8bl-footer">';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a> · ';
    echo '<a href="erp-module-navigation.php">راهبری ماژول</a> · ';
    echo '<a href="erp-blueprint-map.php">نقشه Blueprint</a> · ';
    echo '<a href="erp-product-status.php">وضعیت محصول</a> · ';
    echo '<a href="erp-operational-command-center.php">مرکز عملیاتی</a> · ';
    echo '<a href="erp-role-demo-navigation.php">Demo Navigation</a> · ';
    echo '<a href="erp-soft-run-home.php">Soft Run Home</a>';
    echo '</p></div></body></html>';
}

function bl_error(string $title, string $msg): void
{
    bl_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . bl_h($msg) . '</p></div>';
    bl_render_foot();
    exit;
}

/**
 * @return list<array<string, mixed>>
 */
function bl_phase_modules(): array
{
    return [
        [
            'code' => 'CUSTOMER',
            'phase' => 1,
            'title' => 'Customer Core',
            'title_fa' => 'هسته مشتری',
            'desc' => 'ورود مشتری، قرارداد، پروفایل و اتصال خودرو',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-customer-core-dashboard.php',
            'links' => [
                ['erp-customer-entry.php', 'ورود مشتری'],
                ['erp-customer-contract-create.php', 'قرارداد'],
                ['erp-customer-profile.php', 'پروفایل'],
                ['erp-vehicle-binding.php', 'اتصال خودرو'],
            ],
        ],
        [
            'code' => 'OPERATION',
            'phase' => 2,
            'title' => 'Operation Engine',
            'title_fa' => 'موتور عملیات',
            'desc' => 'پرونده عملیاتی، تکنسین، جریان JobCard',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-operation-control-center.php',
            'links' => [
                ['erp-technician-board.php', 'تابلوی تکنسین'],
                ['erp-jobcard-operation-flow.php', 'جریان JobCard'],
            ],
        ],
        [
            'code' => 'RULE',
            'phase' => 3,
            'title' => 'Rule Engine',
            'title_fa' => 'موتور قواعد',
            'desc' => 'تصمیم‌گیری، تأیید سرویس، کنسول تست',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-rule-decision-board.php',
            'links' => [
                ['erp-service-approval-request.php', 'درخواست تأیید'],
                ['erp-rule-test-console.php', 'کنسول تست'],
            ],
        ],
        [
            'code' => 'INVENTORY',
            'phase' => 4,
            'title' => 'Inventory & Purchase',
            'title_fa' => 'انبار و خرید',
            'desc' => 'کاتالوگ، موجودی، رزرو، خرید، تأمین‌کننده',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-stock-board.php',
            'links' => [
                ['erp-parts-catalog.php', 'کاتالوگ'],
                ['erp-part-reserve.php', 'رزرو'],
                ['erp-purchase-request-create.php', 'درخواست خرید'],
                ['erp-supplier-board.php', 'تأمین‌کننده'],
                ['erp-stock-movement-history.php', 'گردش انبار'],
            ],
        ],
        [
            'code' => 'FINANCE',
            'phase' => 5,
            'title' => 'Financial System',
            'title_fa' => 'سیستم مالی',
            'desc' => 'قیمت، هزینه JobCard، پرداخت، پیش‌نمایش فاکتور',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-finance-control-center.php',
            'links' => [
                ['erp-service-price-list.php', 'لیست قیمت'],
                ['erp-jobcard-cost-preview.php', 'هزینه JobCard'],
                ['erp-payment-tracking.php', 'پرداخت‌ها'],
                ['erp-invoice-preview.php', 'پیش‌نمایش فاکتور'],
            ],
        ],
        [
            'code' => 'CRM',
            'phase' => 6,
            'title' => 'CRM System',
            'title_fa' => 'سیستم CRM',
            'desc' => 'پیگیری، رضایت، امتیاز، فرصت فروش',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-crm-followup-board.php',
            'links' => [
                ['erp-customer-satisfaction.php', 'رضایت مشتری'],
                ['erp-customer-score-board.php', 'امتیاز مشتری'],
                ['erp-upsell-opportunities.php', 'فرصت فروش'],
            ],
        ],
        [
            'code' => 'HR',
            'phase' => 7,
            'title' => 'HR & Internal Admin',
            'title_fa' => 'منابع انسانی',
            'desc' => 'پرونده پرسنلی، قرارداد، حضور، حقوق preview',
            'status' => 'COMPLETED FOUNDATION',
            'main' => 'erp-hr-dashboard.php',
            'links' => [
                ['erp-employee-create.php', 'ثبت کارمند'],
                ['erp-employee-profile.php', 'پروفایل'],
                ['erp-employment-contract.php', 'قرارداد کاری'],
                ['erp-attendance-entry.php', 'حضور و غیاب'],
                ['erp-payroll-preview.php', 'حقوق preview'],
                ['erp-hr-training-discipline.php', 'آموزش/انضباط'],
            ],
        ],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function bl_module_navigation_groups(): array
{
    return [
        ['group' => 'Customer Layer', 'group_fa' => 'لایه مشتری', 'layer' => 'CUSTOMER', 'pages' => [
            ['erp-customer-core-dashboard.php', 'داشبورد مشتری'],
            ['erp-customer-entry.php', 'ورود مشتری'],
            ['erp-customer-contract-create.php', 'قرارداد مشتری'],
            ['erp-customer-profile.php', 'پروفایل مشتری'],
            ['erp-vehicle-binding.php', 'اتصال خودرو'],
        ]],
        ['group' => 'Operation Layer', 'group_fa' => 'لایه عملیات', 'layer' => 'OPERATION', 'pages' => [
            ['erp-operation-control-center.php', 'مرکز کنترل عملیات'],
            ['erp-technician-board.php', 'تابلوی تکنسین'],
            ['erp-jobcard-operation-flow.php', 'جریان JobCard'],
        ]],
        ['group' => 'Decision Layer', 'group_fa' => 'لایه تصمیم', 'layer' => 'RULE', 'pages' => [
            ['erp-rule-decision-board.php', 'تابلو تصمیم'],
            ['erp-service-approval-request.php', 'تأیید سرویس'],
            ['erp-rule-test-console.php', 'کنسول تست قواعد'],
        ]],
        ['group' => 'Inventory Layer', 'group_fa' => 'لایه انبار', 'layer' => 'INVENTORY', 'pages' => [
            ['erp-parts-catalog.php', 'کاتالوگ قطعات'],
            ['erp-stock-board.php', 'تابلوی موجودی'],
            ['erp-part-reserve.php', 'رزرو قطعه'],
            ['erp-purchase-request-create.php', 'درخواست خرید'],
            ['erp-supplier-board.php', 'تأمین‌کنندگان'],
            ['erp-stock-movement-history.php', 'تاریخچه گردش'],
        ]],
        ['group' => 'Finance Layer', 'group_fa' => 'لایه مالی', 'layer' => 'FINANCE', 'pages' => [
            ['erp-finance-control-center.php', 'مرکز کنترل مالی'],
            ['erp-service-price-list.php', 'لیست قیمت'],
            ['erp-jobcard-cost-preview.php', 'هزینه JobCard'],
            ['erp-payment-tracking.php', 'پیگیری پرداخت'],
            ['erp-invoice-preview.php', 'پیش‌نمایش فاکتور'],
        ]],
        ['group' => 'CRM Layer', 'group_fa' => 'لایه CRM', 'layer' => 'CRM', 'pages' => [
            ['erp-crm-followup-board.php', 'تابلو پیگیری'],
            ['erp-crm-followup-detail.php', 'جزئیات پیگیری'],
            ['erp-customer-satisfaction.php', 'رضایت مشتری'],
            ['erp-customer-score-board.php', 'امتیاز مشتری'],
            ['erp-upsell-opportunities.php', 'فرصت فروش'],
        ]],
        ['group' => 'HR Layer', 'group_fa' => 'لایه HR', 'layer' => 'HR', 'pages' => [
            ['erp-hr-dashboard.php', 'داشبورد HR'],
            ['erp-employee-create.php', 'ثبت کارمند'],
            ['erp-employee-profile.php', 'پروفایل پرسنلی'],
            ['erp-employment-contract.php', 'قرارداد کاری'],
            ['erp-attendance-entry.php', 'حضور و غیاب'],
            ['erp-payroll-preview.php', 'حقوق preview'],
            ['erp-hr-training-discipline.php', 'آموزش/انضباط'],
        ]],
        ['group' => 'Product Layer', 'group_fa' => 'لایه محصول', 'layer' => 'PRODUCT', 'pages' => [
            ['erp-business-command-center.php', 'مرکز فرماندهی تجاری'],
            ['erp-module-navigation.php', 'راهبری ماژول'],
            ['erp-blueprint-map.php', 'نقشه Blueprint'],
            ['erp-product-status.php', 'وضعیت محصول'],
            ['erp-operational-command-center.php', 'مرکز عملیاتی'],
            ['erp-role-demo-navigation.php', 'Demo Navigation'],
            ['erp-soft-run-home.php', 'Soft Run Home'],
            ['erp-moghare-ready.php', 'Moghare Ready'],
        ]],
    ];
}

/**
 * @return list<array<string, string>>
 */
function bl_blueprint_nodes(): array
{
    return [
        ['id' => 'customer', 'label' => 'Customer', 'label_fa' => 'مشتری', 'url' => 'erp-customer-entry.php', 'status' => 'Built', 'phase' => '1'],
        ['id' => 'vehicle', 'label' => 'Vehicle', 'label_fa' => 'خودرو', 'url' => 'erp-vehicle-binding.php', 'status' => 'Built', 'phase' => '1'],
        ['id' => 'operation', 'label' => 'JobCard / Operation', 'label_fa' => 'عملیات / JobCard', 'url' => 'erp-jobcard-operation-flow.php', 'status' => 'Built', 'phase' => '2'],
        ['id' => 'rule', 'label' => 'Rule Check', 'label_fa' => 'بررسی قواعد', 'url' => 'erp-rule-decision-board.php', 'status' => 'Built', 'phase' => '3'],
        ['id' => 'inventory', 'label' => 'Inventory / Purchase', 'label_fa' => 'انبار / خرید', 'url' => 'erp-stock-board.php', 'status' => 'Built', 'phase' => '4'],
        ['id' => 'finance', 'label' => 'Finance', 'label_fa' => 'مالی', 'url' => 'erp-finance-control-center.php', 'status' => 'Built', 'phase' => '5'],
        ['id' => 'delivery', 'label' => 'Delivery', 'label_fa' => 'تحویل', 'url' => 'erp-delivery-control.php', 'status' => 'Foundation', 'phase' => 'M30'],
        ['id' => 'crm', 'label' => 'CRM', 'label_fa' => 'CRM', 'url' => 'erp-crm-followup-board.php', 'status' => 'Built', 'phase' => '6'],
        ['id' => 'hr', 'label' => 'HR Support', 'label_fa' => 'پشتیبانی HR', 'url' => 'erp-hr-dashboard.php', 'status' => 'Built', 'phase' => '7'],
        ['id' => 'reporting', 'label' => 'Management Reporting', 'label_fa' => 'گزارش مدیریتی', 'url' => '', 'status' => 'Pending next phase', 'phase' => '9'],
        ['id' => 'commercial', 'label' => 'Commercial Demo', 'label_fa' => 'دموی تجاری', 'url' => '', 'status' => 'Pending next phase', 'phase' => '10'],
    ];
}

/**
 * @return list<array<string, string>>
 */
function bl_product_status_rows(): array
{
    return [
        ['label' => 'Soft Run Internal ERP', 'value' => 'READY'],
        ['label' => 'Business Execution Layer Phase 1–7', 'value' => 'BUILT'],
        ['label' => 'UI Productization Layer', 'value' => 'BUILT AFTER TEST'],
        ['label' => 'Business Ready System', 'value' => 'BUILT AFTER TEST'],
        ['label' => 'Commercial Demo Ready', 'value' => 'BUILT AFTER TEST'],
        ['label' => 'SaaS Production', 'value' => 'NOT ACTIVE'],
        ['label' => 'Production SaaS', 'value' => 'NOT ACTIVE'],
    ];
}

/**
 * @return list<string>
 */
function bl_product_boundaries(): array
{
    return [
        'No production login change',
        'No auth rewrite',
        'No permission rewrite',
        'No destructive DB migration',
        'No official tax/final invoice',
        'No customer portal login',
        'No SaaS active',
    ];
}

/**
 * @return array<string, string>
 */
function bl_fetch_operational_stats($c): array
{
    $stats = [
        'operation_cases' => '—',
        'pending_followups' => '—',
        'unpaid_payments' => '—',
        'stock_items' => '—',
        'active_employees' => '—',
    ];
    if ($c === false) {
        return $stats;
    }
    if (bl_table_exists($c, 'erp_operation_cases')) {
        $stats['operation_cases'] = bl_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_operation_cases') ?? '—';
    }
    if (bl_table_exists($c, 'erp_crm_followup_schedules')) {
        $stats['pending_followups'] = bl_scalar($c, "SELECT COUNT(*) FROM dbo.erp_crm_followup_schedules WHERE schedule_status IN ('SCHEDULED','DUE','OVERDUE')") ?? '—';
    }
    if (bl_table_exists($c, 'erp_payment_records')) {
        $stats['unpaid_payments'] = bl_scalar($c, "SELECT COUNT(*) FROM dbo.erp_payment_records WHERE payment_status IN ('PENDING','PARTIAL','UNPAID')") ?? '—';
    }
    if (bl_table_exists($c, 'erp_inventory_items')) {
        $stats['stock_items'] = bl_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_inventory_items') ?? '—';
    } elseif (bl_table_exists($c, 'erp_stock_balances')) {
        $stats['stock_items'] = bl_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_stock_balances') ?? '—';
    }
    if (bl_table_exists($c, 'erp_hr_employees')) {
        $stats['active_employees'] = bl_scalar($c, "SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE employment_status='ACTIVE'") ?? '—';
    }
    return $stats;
}

/**
 * @return list<array<string, mixed>>
 */
function bl_role_demo_groups(): array
{
    return [
        ['role' => 'Owner / Manager', 'role_fa' => 'مالک / مدیر', 'links' => [
            ['erp-business-command-center.php', 'مرکز فرماندهی'],
            ['erp-operational-command-center.php', 'مرکز عملیاتی'],
            ['erp-product-status.php', 'وضعیت محصول'],
            ['erp-blueprint-map.php', 'نقشه Blueprint'],
        ]],
        ['role' => 'Reception', 'role_fa' => 'پذیرش', 'links' => [
            ['erp-customer-entry.php', 'ورود مشتری'],
            ['erp-customer-profile.php', 'پروفایل مشتری'],
            ['erp-vehicle-binding.php', 'اتصال خودرو'],
            ['erp-customer-vehicle-workbench.php', 'Workbench مشتری/خودرو'],
        ]],
        ['role' => 'Technician', 'role_fa' => 'تکنسین', 'links' => [
            ['erp-technician-board.php', 'تابلوی تکنسین'],
            ['erp-jobcard-operation-flow.php', 'جریان JobCard'],
            ['erp-service-operation-workbench-ux.php', 'Workbench سرویس'],
        ]],
        ['role' => 'Inventory', 'role_fa' => 'انبار', 'links' => [
            ['erp-stock-board.php', 'تابلوی موجودی'],
            ['erp-parts-catalog.php', 'کاتالوگ'],
            ['erp-part-reserve.php', 'رزرو'],
            ['erp-stock-movement-history.php', 'گردش انبار'],
        ]],
        ['role' => 'Finance', 'role_fa' => 'مالی', 'links' => [
            ['erp-finance-control-center.php', 'مرکز مالی'],
            ['erp-payment-tracking.php', 'پرداخت‌ها'],
            ['erp-invoice-preview.php', 'پیش‌نمایش فاکتور'],
            ['erp-jobcard-cost-preview.php', 'هزینه JobCard'],
        ]],
        ['role' => 'CRM', 'role_fa' => 'CRM', 'links' => [
            ['erp-crm-followup-board.php', 'تابلو پیگیری'],
            ['erp-customer-satisfaction.php', 'رضایت'],
            ['erp-customer-score-board.php', 'امتیاز'],
            ['erp-upsell-opportunities.php', 'فرصت فروش'],
        ]],
        ['role' => 'HR', 'role_fa' => 'منابع انسانی', 'links' => [
            ['erp-hr-dashboard.php', 'داشبورد HR'],
            ['erp-employee-profile.php', 'پروفایل پرسنلی'],
            ['erp-attendance-entry.php', 'حضور و غیاب'],
            ['erp-payroll-preview.php', 'حقوق preview'],
        ]],
    ];
}

function bl_status_badge_class(string $status): string
{
    return match (true) {
        str_contains($status, 'COMPLETED') || $status === 'Built' || $status === 'READY' || $status === 'BUILT' => 'p8bl-badge-ok',
        $status === 'Foundation' => 'p8bl-badge-foundation',
        str_contains($status, 'PENDING') || str_contains($status, 'Pending') => 'p8bl-badge-pending',
        str_contains($status, 'NOT ACTIVE') => 'p8bl-badge-muted',
        default => 'p8bl-badge-new',
    };
}

function bl_render_page_link(string $file, string $label): void
{
    $exists = $file !== '' && bl_page_exists($file);
    echo '<li class="p8bl-link-item">';
    if ($exists) {
        echo '<a href="' . bl_h($file) . '">' . bl_h($label) . '</a>';
        echo ' <span class="p8bl-badge p8bl-badge-ok">OK</span>';
    } elseif ($file === '') {
        echo '<span>' . bl_h($label) . '</span>';
        echo ' <span class="p8bl-badge p8bl-badge-pending">PENDING</span>';
    } else {
        echo '<span>' . bl_h($label) . '</span>';
        echo ' <span class="p8bl-badge p8bl-badge-missing">NOT FOUND</span>';
    }
    echo '</li>';
}
