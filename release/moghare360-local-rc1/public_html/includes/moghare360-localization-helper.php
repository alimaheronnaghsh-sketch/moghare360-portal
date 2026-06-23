<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 12.5 Localization Helper (read-only utility)
 */

const ERP_PHASE125_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE125_PLACEHOLDER_ACTIONS = [
    'localization.audit.view' => 'placeholder_localization_audit_view',
];

function mogh_loc_require_helper(string $fileName): void
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

mogh_loc_require_helper('erp-auth-context.php');
mogh_loc_require_helper('erp-permission-guard.php');

function mogh_loc_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mogh_loc_public_path(): string
{
    return dirname(__DIR__);
}

function mogh_loc_project_root(): string
{
    return dirname(__DIR__, 2);
}

function mogh_loc_brand_logo_path(): string
{
    return 'assets/moghare360-brand/moghareh-motors-logo.jpg';
}

function mogh_loc_brand_logo_exists(): bool
{
    return is_file(mogh_loc_public_path() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'moghare360-brand' . DIRECTORY_SEPARATOR . 'moghareh-motors-logo.jpg');
}

function mogh_loc_brand_logo_status(): string
{
    return mogh_loc_brand_logo_exists() ? 'Owner Provided Asset' : 'PENDING OWNER FILE';
}

/** @return array<string, string> */
function mogh_loc_dictionary(): array
{
    return [
        'Dashboard' => 'داشبورد',
        'Report' => 'گزارش',
        'Pilot' => 'اجرای آزمایشی',
        'Demo' => 'نسخه نمایشی',
        'Soft Run' => 'اجرای نرم داخلی',
        'KPI' => 'شاخص کلیدی عملکرد',
        'CRM' => 'پیگیری ارتباط با مشتری',
        'SaaS' => 'نرم‌افزار ابری اشتراکی',
        'Customer' => 'مشتری',
        'Vehicle' => 'خودرو',
        'JobCard' => 'کارت کار',
        'Service Operation' => 'عملیات سرویس',
        'Inventory' => 'انبار',
        'Purchase' => 'خرید',
        'Finance Preview' => 'پیش‌نمایش مالی',
        'HR' => 'منابع انسانی',
        'Release Candidate' => 'نامزد انتشار',
        'Production' => 'نسخه عملیاتی',
        'Not Active' => 'فعال نیست',
        'Ready' => 'آماده',
        'Completed' => 'تکمیل‌شده',
        'Commercial Demo' => 'نسخه نمایشی تجاری',
        'Business Ready' => 'آماده بهره‌برداری مدیریتی',
        'Controlled Workshop Pilot' => 'اجرای آزمایشی کنترل‌شده تعمیرگاه',
        'Asset Registry' => 'دفتر ثبت دارایی',
        'Copyright Policy' => 'سیاست کپی‌رایت',
        'Brand System' => 'سیستم برند',
        'Localization Audit' => 'ممیزی فارسی‌سازی',
    ];
}

function mogh_loc_term(string $english): string
{
    $dict = mogh_loc_dictionary();
    return $dict[$english] ?? $english;
}

/** @return list<string> */
function mogh_loc_allowed_english_terms(): array
{
    return array_keys(mogh_loc_dictionary());
}

/** @return list<string> */
function mogh_loc_terms_needing_review(): array
{
    return [
        'ERP',
        'MOGHARE360',
        'MOGHAREH MOTORS',
        'SQL Server',
        'PHP',
        'CSRF',
        'RC1',
        'URL',
        'API',
    ];
}

/** @return list<array{code:string,file:string,title_fa:string,title_en:string,status:string}> */
function mogh_loc_product_pages(): array
{
    return [
        ['code' => 'bcc', 'file' => 'erp-business-command-center.php', 'title_fa' => 'مرکز فرماندهی تجاری', 'title_en' => 'Business Command Center', 'status' => 'OK'],
        ['code' => 'product_status', 'file' => 'erp-product-status.php', 'title_fa' => 'وضعیت محصول', 'title_en' => 'Product Status', 'status' => 'OK'],
        ['code' => 'mgmt_dash', 'file' => 'erp-management-dashboard.php', 'title_fa' => 'داشبورد مدیریت', 'title_en' => 'Management Dashboard', 'status' => 'NEEDS REVIEW'],
        ['code' => 'pilot_center', 'file' => 'erp-soft-run-pilot-center.php', 'title_fa' => 'مرکز اجرای آزمایشی', 'title_en' => 'Soft Run Pilot Center', 'status' => 'NEEDS REVIEW'],
        ['code' => 'local_rc1', 'file' => 'erp-local-release-candidate.php', 'title_fa' => 'نامزد انتشار محلی', 'title_en' => 'Local Release Candidate', 'status' => 'NEEDS REVIEW'],
        ['code' => 'commercial_demo', 'file' => 'moghare360-commercial-demo.php', 'title_fa' => 'نسخه نمایشی تجاری', 'title_en' => 'Commercial Demo', 'status' => 'NEEDS REVIEW'],
        ['code' => 'sales_showcase', 'file' => 'moghare360-sales-showcase.php', 'title_fa' => 'ویترین فروش', 'title_en' => 'Sales Showcase', 'status' => 'HIGH ENGLISH CONTENT'],
        ['code' => 'final_report', 'file' => 'moghare360-final-release-report.php', 'title_fa' => 'گزارش نهایی انتشار', 'title_en' => 'Final Release Report', 'status' => 'HIGH ENGLISH CONTENT'],
    ];
}

/** @return array<string, mixed> */
function mogh_loc_page_registry(): array
{
    return [
        'audit_pages' => mogh_loc_product_pages(),
        'phase_12_5_pages' => [
            ['file' => 'erp-localization-audit.php', 'title_fa' => 'ممیزی فارسی‌سازی محصول'],
            ['file' => 'erp-brand-system.php', 'title_fa' => 'سیستم برند'],
            ['file' => 'erp-asset-registry.php', 'title_fa' => 'دفتر ثبت دارایی‌های محصول'],
            ['file' => 'moghare360-demo-package.php', 'title_fa' => 'بسته نمایشی MOGHARE360'],
        ],
        'allowed_english' => mogh_loc_allowed_english_terms(),
        'needs_review' => mogh_loc_terms_needing_review(),
    ];
}

function mogh_loc_estimate_language_status(string $pageCode): string
{
    foreach (mogh_loc_product_pages() as $page) {
        if (($page['code'] ?? '') === $pageCode) {
            return (string)($page['status'] ?? 'NEEDS REVIEW');
        }
    }
    return 'NEEDS REVIEW';
}

function mogh_loc_status_badge_class(string $status): string
{
    return match ($status) {
        'OK' => 'm125bl-badge-ok',
        'NEEDS REVIEW' => 'm125bl-badge-warn',
        'HIGH ENGLISH CONTENT' => 'm125bl-badge-fail',
        default => 'm125bl-badge-muted',
    };
}

/** @return list<array<string, string>> */
function mogh_loc_asset_registry(): array
{
    $logoPath = mogh_loc_brand_logo_path();
    $logoExists = mogh_loc_brand_logo_exists();
    return [
        [
            'category' => 'برند',
            'name' => 'لوگوی Moghareh Motors',
            'path' => $logoPath,
            'ownership' => $logoExists ? 'Owner Provided Asset' : 'PENDING OWNER FILE',
            'notes' => $logoExists ? 'فایل مالک در مسیر برند ثبت شده است.' : 'فایل لوگو یافت نشد؛ از fallback متنی استفاده می‌شود.',
        ],
        [
            'category' => 'فونت',
            'name' => 'System font stack',
            'path' => 'Vazirmatn, Tahoma, Segoe UI, Arial, sans-serif',
            'ownership' => 'System / CSS stack only',
            'notes' => 'بدون فایل فونت تجاری در repo.',
        ],
        [
            'category' => 'آیکون',
            'name' => 'CSS/SVG internal',
            'path' => 'assets/moghare360-ui/',
            'ownership' => 'Product generated',
            'notes' => 'فقط آیکون‌های داخلی CSS/SVG.',
        ],
        [
            'category' => 'تصویر',
            'name' => 'Third-party images',
            'path' => '—',
            'ownership' => 'Prohibited unless licensed',
            'notes' => 'تصویر third-party بدون مجوز ممنوع.',
        ],
        [
            'category' => 'لوگوی خودرو',
            'name' => 'Car brand logos',
            'path' => '—',
            'ownership' => 'Prohibited unless licensed',
            'notes' => 'Benz, BMW, Toyota, Lexus, Kia, Hyundai و سایر برندها ممنوع مگر با مجوز.',
        ],
    ];
}

/** @return list<array{label:string,value:string}> */
function mogh_loc_product_status_labels(): array
{
    return [
        ['label' => 'Internal Soft Run', 'value' => 'آماده'],
        ['label' => 'Business Ready System', 'value' => 'آماده'],
        ['label' => 'Commercial Demo Readiness', 'value' => 'آماده'],
        ['label' => 'Local Release Candidate 1', 'value' => 'آماده'],
        ['label' => 'Controlled Workshop Pilot', 'value' => 'آماده'],
        ['label' => 'Production SaaS', 'value' => 'فعال نیست'],
        ['label' => 'Public Customer Portal', 'value' => 'فعال نیست'],
        ['label' => 'Official Accounting', 'value' => 'فعال نیست'],
    ];
}

/** @return list<string> */
function mogh_loc_boundary_labels(): array
{
    return [
        'این فاز Production Deploy نیست.',
        'این فاز SaaS را فعال نمی‌کند.',
        'این فاز Portal عمومی مشتری را فعال نمی‌کند.',
        'این فاز Installer واقعی نمی‌سازد.',
        'این فاز ZIP دانلودی واقعی نمی‌سازد (PHASE 15).',
        'فایل‌های Login/Auth/Permission تغییر نکرده‌اند.',
        'دارایی بدون مالکیت یا مجوز در نسخه نمایشی استفاده نمی‌شود.',
    ];
}

/** @return list<array{file:string,label:string,group:string}> */
function mogh_loc_demo_package_links(): array
{
    return [
        ['file' => 'moghare360-commercial-demo.php', 'label' => 'نسخه نمایشی تجاری', 'group' => 'Demo / Commercial'],
        ['file' => 'moghare360-sales-showcase.php', 'label' => 'ویترین فروش', 'group' => 'Demo / Commercial'],
        ['file' => 'moghare360-product-packages.php', 'label' => 'بسته‌های محصول', 'group' => 'Demo / Commercial'],
        ['file' => 'moghare360-license-preview.php', 'label' => 'پیش‌نمایش مجوز', 'group' => 'Demo / Commercial'],
        ['file' => 'moghare360-commercial-checklist.php', 'label' => 'چک‌لیست تجاری', 'group' => 'Demo / Commercial'],
        ['file' => 'moghare360-final-release-report.php', 'label' => 'گزارش نهایی انتشار', 'group' => 'Demo / Commercial'],
    ];
}

/** @return list<array{title:string,path:string,type:string}> */
function mogh_loc_demo_package_sections(): array
{
    return [
        ['title' => 'Product Brief', 'path' => 'moghare360-commercial-demo.php', 'type' => 'page'],
        ['title' => 'Pricing Draft', 'path' => 'moghare360-product-packages.php', 'type' => 'page'],
        ['title' => 'SaaS Packaging Plan', 'path' => 'moghare360-license-preview.php', 'type' => 'page'],
        ['title' => 'Tenant Ready Architecture', 'path' => 'erp-product-status.php', 'type' => 'page'],
        ['title' => 'Commercial Release Checklist', 'path' => 'moghare360-commercial-checklist.php', 'type' => 'page'],
        ['title' => 'Final Release Report', 'path' => 'moghare360-final-release-report.php', 'type' => 'page'],
        ['title' => 'Copyright and Asset Policy', 'path' => 'docs/product/MOGHARE360_COPYRIGHT_AND_ASSET_POLICY.md', 'type' => 'doc'],
        ['title' => 'Persian Language Guide', 'path' => 'docs/product/MOGHARE360_PERSIAN_LANGUAGE_GUIDE.md', 'type' => 'doc'],
    ];
}

function mogh_loc_page_exists(string $page): bool
{
    return is_file(mogh_loc_public_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($page, '/')));
}

function mogh_loc_doc_exists(string $rel): bool
{
    return is_file(mogh_loc_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($rel, '/')));
}

function mogh_loc_db()
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

function mogh_loc_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE125_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE125_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function mogh_loc_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(mogh_loc_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function mogh_loc_error(string $title, string $message): never
{
    mogh_loc_render_head($title);
    echo '<div class="m125bl-warning-box"><strong>' . mogh_loc_h($title) . '</strong><p>' . mogh_loc_h($message) . '</p></div>';
    mogh_loc_render_foot();
    exit;
}

function mogh_loc_render_brand_logo(): void
{
    if (mogh_loc_brand_logo_exists()) {
        echo '<img class="m125bl-logo" src="' . mogh_loc_h(mogh_loc_brand_logo_path()) . '" alt="Moghareh Motors Logo">';
        return;
    }
    echo '<div class="m125bl-logo-fallback">MOGHAREH MOTORS<br>MOGHARE360</div>';
}

function mogh_loc_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . mogh_loc_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-brand-localization.css">';
    echo '</head><body class="m125bl-page"><div class="m125bl-wrap">';
    echo '<div class="m125bl-banner">Phase 12.5 — فارسی‌سازی · برند · دفتر دارایی · بسته نمایشی — Read-only · Not Production</div>';
}

function mogh_loc_render_foot(): void
{
    echo '<p class="m125bl-footer">';
    echo '<a href="erp-localization-audit.php">ممیزی فارسی‌سازی</a> · ';
    echo '<a href="erp-brand-system.php">سیستم برند</a> · ';
    echo '<a href="erp-asset-registry.php">دفتر ثبت دارایی</a> · ';
    echo '<a href="moghare360-demo-package.php">بسته نمایشی</a> · ';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a>';
    echo '</p></div></body></html>';
}

function mogh_loc_render_phase125_nav(): void
{
    echo '<div class="m125bl-nav-grid" style="margin-bottom:1.25rem">';
    $links = [
        ['erp-brand-system.php', 'سیستم برند', 'Brand System'],
        ['erp-localization-audit.php', 'ممیزی فارسی‌سازی', 'Localization Audit'],
        ['erp-asset-registry.php', 'دفتر ثبت دارایی', 'Asset Registry'],
        ['moghare360-demo-package.php', 'بسته نمایشی', 'Demo Package Plan'],
    ];
    foreach ($links as [$file, $fa, $en]) {
        echo '<a class="m125bl-nav-card" href="' . mogh_loc_h($file) . '">';
        echo '<span class="m125bl-nav-title">' . mogh_loc_h($fa) . '</span>';
        echo '<span class="m125bl-nav-sub">' . mogh_loc_h($en) . '</span></a>';
    }
    echo '</div>';
}
