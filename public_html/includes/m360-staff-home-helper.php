<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.4.4-B — Role-aware staff landing routes (post-login bridge only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

const M360_STAFF_HOME_UNKNOWN_WARNING_FA = 'برای این کاربر هنوز نقش عملیاتی معتبر تعریف نشده است.';
const M360_STAFF_HOME_MISSING_ROUTE_FA = 'این صفحه هنوز در مسیر نصب‌شده موجود نیست.';
const M360_STAFF_HOME_INFO_ROUTE_FA = 'این صفحه از مسیر تابلو یا پرونده مربوط باز می‌شود.';
const M360_STAFF_HOME_NOTE_ROUTE_FA = 'این عملیات داخل پرونده انتخاب‌شده انجام می‌شود و ورود مستقیم ممکن نیست.';
const M360_STAFF_HOME_STATUS_GUIDED_FA = 'راهنمای مسیر';
const M360_STAFF_HOME_STATUS_ACTION_FA = 'عملیات داخلی';
const M360_STAFF_HOME_BACKLOG_FA = 'نیازمند تکمیل در فاز بعدی — لینک غیرفعال است.';
const M360_STAFF_HOME_GUIDED_BTN_FA = 'راهنمای مسیر';
const M360_STAFF_HOME_REDIRECT_PATH = 'erp-staff-home.php';

const M360_STAFF_HOME_GROUP_TODAY = 'today';
const M360_STAFF_HOME_GROUP_FOLLOWUP = 'followup';
const M360_STAFF_HOME_GROUP_OPERATIONS = 'operations';
const M360_STAFF_HOME_GROUP_REPORTS = 'reports';
const M360_STAFF_HOME_GROUP_MANAGER_REF = 'manager_ref';
const M360_STAFF_HOME_GROUP_COORDINATION_REF = 'coordination_ref';
const M360_STAFF_HOME_GROUP_BACKLOG = 'backlog';
const M360_STAFF_HOME_BTN_BOARD_FA = 'مشاهده تابلو';
const M360_STAFF_HOME_BTN_REPORT_FA = 'مشاهده گزارش';
const M360_STAFF_HOME_RUNTIME_HOLD_FA = 'نیازمند بررسی عملیاتی';

/** @var array<string, array{status_fa:string,description_fa:string}> */
const M360_STAFF_HOME_RUNTIME_NOT_READY = [
    'erp-jobcard-part-use.php' => [
        'status_fa' => 'نیازمند بازبینی عملیاتی',
        'description_fa' => 'این مسیر هنوز محصولی‌سازی نشده و تا اصلاح صفحه مقصد از میز کار فعال نمی‌شود.',
    ],
    'erp-payment-tracking.php' => [
        'status_fa' => 'نیازمند بررسی عملیاتی',
        'description_fa' => 'مسیر پرداخت تا رفع خطای بارگذاری از میز کار فعال نیست.',
    ],
];

const M360_OWNER_LOGIN_REDIRECT_PRIMARY = 'erp-product-home.php';

/** @var list<string> */
const M360_OWNER_LOGIN_REDIRECT_FALLBACKS = [
    'erp-owner-control-center.php',
    'erp-management-dashboard.php',
];

/** @var list<string> */
const M360_STAFF_HOME_ADMIN_ROLE_CODES = ['OWNER', 'SYSTEM_ADMIN'];

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/**
 * Normalize ODBC/SQL Server text for UTF-8 HTML output (does not change DB values).
 */
function m360_staff_home_text_from_odbc(mixed $value): string
{
    if ($value === null || $value === false) {
        return '';
    }

    if (!is_string($value)) {
        $value = (string)$value;
    }

    if ($value === '') {
        return '';
    }

    if (mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }

    if (strlen($value) >= 2 && (strlen($value) % 2) === 0 && str_contains($value, "\0")) {
        $wide = $value;
        if (str_starts_with($wide, "\xFF\xFE")) {
            $wide = substr($wide, 2);
        }
        $converted = @iconv('UTF-16LE', 'UTF-8//IGNORE', $wide);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }
    }

    foreach (['Windows-1256', 'CP1256', 'ISO-8859-6'] as $encoding) {
        $converted = @iconv($encoding, 'UTF-8//IGNORE', $value);
        if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
    }

    return $value;
}

function m360_staff_home_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape DB-derived text after ODBC normalization.
 */
function m360_staff_home_h_db(mixed $value): string
{
    return m360_staff_home_h(m360_staff_home_text_from_odbc($value));
}

/**
 * @return array<string, string>
 */
function m360_staff_home_role_labels_fa(): array
{
    return [
        'OWNER' => 'مالک / مدیر ارشد',
        'SYSTEM_ADMIN' => 'مدیر سیستم',
        'SERVICE_MANAGER' => 'مدیر سرویس / سالن',
        'TECHNICIAN' => 'تکنسین',
        'RECEPTION' => 'پذیرش',
        'PARTS' => 'انبار / قطعات',
        'FINANCE' => 'مالی',
        'QC' => 'کنترل کیفیت',
        'UNKNOWN' => 'نامشخص',
    ];
}

function m360_staff_home_role_label_fa(string $roleCode): string
{
    $map = m360_staff_home_role_labels_fa();

    return $map[strtoupper(trim($roleCode))] ?? 'نامشخص';
}

function m360_staff_home_is_admin_role(string $roleCode): bool
{
    return in_array(strtoupper(trim($roleCode)), M360_STAFF_HOME_ADMIN_ROLE_CODES, true);
}

/**
 * @return list<string>
 */
function m360_staff_home_known_role_codes(): array
{
    return [
        'OWNER',
        'SYSTEM_ADMIN',
        'RECEPTION',
        'SERVICE_MANAGER',
        'TECHNICIAN',
        'PARTS',
        'FINANCE',
        'QC',
        'UNKNOWN',
    ];
}

function m360_staff_home_require_session(): void
{
    erp_auth_context_start();
    if (erp_auth_context_session_user_id() === null) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_staff_home_sync_session_from_login_payload(array $payload): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = (int)($payload['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    session_regenerate_id(true);
    $_SESSION['erp_user_id'] = $userId;
    $_SESSION['erp_username'] = (string)($payload['username'] ?? '');
    $_SESSION['erp_company_id'] = (int)($payload['company_id'] ?? 0);
    $_SESSION['erp_login_timestamp'] = time();
}

function m360_owner_login_resolve_redirect_path(): string
{
    foreach (array_merge([M360_OWNER_LOGIN_REDIRECT_PRIMARY], M360_OWNER_LOGIN_REDIRECT_FALLBACKS) as $file) {
        if (m360_nav_file_exists($file)) {
            return $file;
        }
    }

    return M360_OWNER_LOGIN_REDIRECT_PRIMARY;
}

function m360_owner_login_sync_session_from_login_payload(array $payload): void
{
    m360_staff_home_sync_session_from_login_payload($payload);

    if ((int)($payload['user_id'] ?? 0) > 0) {
        $_SESSION['erp_is_owner'] = 1;
    }
}

function m360_owner_login_redirect_after_success(array $payload): bool
{
    m360_owner_login_sync_session_from_login_payload($payload);

    $redirect = trim((string)($payload['redirect_url'] ?? m360_owner_login_resolve_redirect_path()));
    if ($redirect !== '' && !preg_match('#^[a-zA-Z0-9_./?=&%-]+$#', $redirect)) {
        $redirect = m360_owner_login_resolve_redirect_path();
    }

    if (str_contains($redirect, '://') || str_starts_with($redirect, '//')) {
        return true;
    }

    if (!headers_sent()) {
        header('Location: ' . $redirect);
        exit;
    }

    return true;
}

/**
 * P11.7.1-A — documented backlog cards (no pages built).
 *
 * @return list<array<string, string>>
 */
function m360_staff_home_scope_backlog_items(string $roleCode): array
{
    $roleCode = strtoupper(trim($roleCode));
    if ($roleCode === 'UNKNOWN' || !in_array($roleCode, m360_staff_home_known_role_codes(), true)) {
        return [];
    }

    $g = static fn(string $k): string => $k;
    $items = [];

    if (m360_staff_home_is_admin_role($roleCode)) {
        foreach (
            [
                ['انجام کار به جای پرسنل / Impersonation', 'غیرمجاز در V1 — اقدام مدیر باید با هویت خود مدیر و Audit انجام شود.'],
                ['موتور Override مدیریتی', 'نیازمند طراحی امنیتی مستقل — فعلاً فقط مسیر ناوبری مرجع داریم، نه عبور از گیت‌ها.'],
                ['افزایش Permission نقش‌ها', 'نیازمند فاز مستقل دسترسی — P11.8-A هیچ permission جدیدی اضافه نمی‌کند.'],
                ['HR Self-Service', 'نیازمند P15 / backlog'],
            ] as [$label, $desc]
        ) {
            $items[] = m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), $label, '', $desc, $roleCode, 'backlog');
        }
    }

    foreach (
        [
            ['پروفایل شخصی کارمند', 'نیازمند P15 / HR Self-Service'],
            ['تغییر رمز کارمند', 'نیازمند فاز امنیتی مستقل'],
            ['درخواست مرخصی', 'نیازمند P15 / HR Self-Service'],
            ['درخواست اضافه‌کاری', 'نیازمند P15 / HR Self-Service'],
            ['تکمیل مدارک پرسنلی / عکس پروفایل', 'نیازمند P15 / HR Self-Service'],
        ] as [$label, $desc]
    ) {
        $items[] = m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), $label, '', $desc, $roleCode, 'backlog');
    }

    return $items;
}

/**
 * P11.8-A — safe board/list reference card.
 *
 * @return array<string, string>
 */
function m360_staff_home_bridge_ref_item(
    string $groupKey,
    string $labelFa,
    string $file,
    string $descriptionFa,
    string $roleCode,
    string $cardType = 'ref'
): array {
    return m360_staff_home_item($groupKey, $labelFa, $file, $descriptionFa, $roleCode, $cardType);
}

/**
 * @return list<array<string, string>>
 */
function m360_staff_home_admin_manager_bridge_items(string $roleCode): array
{
    $g = M360_STAFF_HOME_GROUP_MANAGER_REF;
    $preview = 'erp-access-permission-preview.php';

    $items = [
        m360_staff_home_bridge_ref_item($g, 'درخواست‌های آنلاین', 'erp-reception-online-requests.php', 'مشاهده تابلو پذیرش آنلاین — مرجع عملیاتی', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'JobCardهای پذیرش', 'erp-reception-jobcards.php', 'مشاهده تابلو JobCard پذیرش', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'قراردادهای پذیرش', 'erp-intake-contracts.php', 'مشاهده تابلو گیت قرارداد', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'تابلوی فنی', 'erp-technical-board.php', 'مرجع تخصیص و عیب‌یابی', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'تابلوی اجرای کار', 'erp-work-execution-board.php', 'مرجع پیگیری اجرای کارگاه', $roleCode),
        m360_staff_home_item($g, 'تایم‌لاین JobCard', 'erp-jobcard-timeline.php', 'مسیر از پرونده باز می‌شود — شناسه JobCard لازم است', $roleCode, 'info'),
        m360_staff_home_bridge_ref_item($g, 'تابلوی موجودی', 'erp-stock-board.php', 'مرجع وضعیت انبار', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'رزرو قطعه', 'erp-part-reserve.php', 'مرجع رزرو قطعه برای JobCard', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'مصرف قطعه JobCard', 'erp-jobcard-part-use.php', 'ورود از تابلو/فهرست — شناسه JobCard لازم است', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'ثبت درخواست خرید', 'erp-purchase-request-create.php', 'ورود ایمن به فرم درخواست خرید', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'پیگیری پرداخت', 'erp-payment-tracking.php', 'مرجع وضعیت پرداخت', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'برد برآورد', 'erp-estimate-board.php', 'مرجع گیت تأیید برآورد', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'فاکتور نهایی', 'erp-final-invoice-board.php', 'مرجع فاکتور و صورتحساب', $roleCode),
        m360_staff_home_item($g, 'جزئیات تسویه', 'erp-settlement-detail.php', 'مسیر از پرونده باز می‌شود — از برد فاکتور/JobCard', $roleCode, 'info'),
        m360_staff_home_bridge_ref_item($g, 'تابلوی QC', 'erp-qc-board.php', 'مرجع کنترل کیفیت', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'کنترل تحویل', 'erp-delivery-control.php', 'مرجع آمادگی تحویل', $roleCode),
        m360_staff_home_bridge_ref_item($g, 'پیش‌نمایش دسترسی', $preview, 'ابزار تشخیص دسترسی کاربر — مدیریتی', $roleCode, 'diag'),
        m360_staff_home_bridge_ref_item($g, 'نقشه مسیرها', 'erp-route-map.php', 'فهرست کامل مسیرهای نصب‌شده', $roleCode, 'diag'),
        m360_staff_home_bridge_ref_item($g, 'مرکز کنترل مالک', 'erp-owner-control-center.php', 'نظارت read-only پرونده‌های پرریسک', $roleCode, 'diag'),
        m360_staff_home_bridge_ref_item($g, 'داشبورد مدیریت', 'erp-management-dashboard.php', 'شاخص‌های read-only عملیاتی', $roleCode, 'diag'),
    ];

    if (!m360_staff_home_route_exists('erp-purchase-request-list.php')) {
        $items[] = m360_staff_home_item(
            $g,
            'فهرست درخواست خرید',
            'erp-purchase-request-list.php',
            'صفحه فهرست درخواست خرید هنوز ساخته نشده است.',
            $roleCode,
            'backlog'
        );
    }

    return $items;
}

/**
 * @return list<array<string, string>>
 */
function m360_staff_home_service_manager_coordination_bridge_items(string $roleCode): array
{
    $g = M360_STAFF_HOME_GROUP_COORDINATION_REF;

    return [
        m360_staff_home_bridge_ref_item($g, 'JobCardهای پذیرش', 'erp-reception-jobcards.php', 'مرجع هماهنگی — نمایش برای پیگیری پذیرش', $roleCode, 'ref_coord'),
        m360_staff_home_bridge_ref_item($g, 'قراردادهای پذیرش', 'erp-intake-contracts.php', 'مرجع هماهنگی — گیت قرارداد', $roleCode, 'ref_coord'),
        m360_staff_home_item($g, 'تایم‌لاین JobCard', 'erp-jobcard-timeline.php', 'مسیر از پرونده باز می‌شود — شناسه JobCard لازم است', $roleCode, 'info'),
        m360_staff_home_bridge_ref_item($g, 'رزرو قطعه', 'erp-part-reserve.php', 'مرجع هماهنگی — انبار', $roleCode, 'ref_coord'),
        m360_staff_home_bridge_ref_item($g, 'مصرف قطعه JobCard', 'erp-jobcard-part-use.php', 'مرجع هماهنگی — ثبت مصرف', $roleCode, 'ref_coord'),
        m360_staff_home_bridge_ref_item($g, 'پیگیری پرداخت', 'erp-payment-tracking.php', 'نمایش برای پیگیری — مرجع مالی', $roleCode, 'ref_coord'),
        m360_staff_home_bridge_ref_item($g, 'برد برآورد', 'erp-estimate-board.php', 'نمایش برای پیگیری — مرجع مالی', $roleCode, 'ref_coord'),
        m360_staff_home_bridge_ref_item($g, 'فاکتور نهایی', 'erp-final-invoice-board.php', 'نمایش برای پیگیری — مرجع مالی', $roleCode, 'ref_coord'),
    ];
}

/**
 * @return list<array<string, string>>
 */
function m360_staff_home_manager_bridge_items(string $roleCode): array
{
    $roleCode = strtoupper(trim($roleCode));
    if (m360_staff_home_is_admin_role($roleCode)) {
        return m360_staff_home_admin_manager_bridge_items($roleCode);
    }
    if ($roleCode === 'SERVICE_MANAGER') {
        return m360_staff_home_service_manager_coordination_bridge_items($roleCode);
    }

    return [];
}

function m360_staff_home_has_manager_bridge(string $roleCode): bool
{
    return m360_staff_home_manager_bridge_items($roleCode) !== [];
}

function m360_staff_home_is_runtime_ready(string $file): bool
{
    return !isset(M360_STAFF_HOME_RUNTIME_NOT_READY[$file]);
}

/**
 * @param list<array<string, string>> $items
 * @return list<array<string, string>>
 */
function m360_staff_home_apply_runtime_hold(array $items): array
{
    foreach ($items as $i => $item) {
        $file = (string)($item['file'] ?? '');
        if ($file === '' || !isset(M360_STAFF_HOME_RUNTIME_NOT_READY[$file])) {
            continue;
        }
        $meta = M360_STAFF_HOME_RUNTIME_NOT_READY[$file];
        $items[$i]['card_type'] = 'runtime_hold';
        $items[$i]['description_fa'] = $meta['description_fa'];
        $items[$i]['runtime_status_fa'] = $meta['status_fa'];
    }

    return $items;
}

function m360_staff_home_usage_path_fa(array $item): string
{
    $cardType = (string)($item['card_type'] ?? 'nav');
    $desc = trim((string)($item['description_fa'] ?? ''));

    return match ($cardType) {
        'info' => $desc !== '' ? 'مسیر استفاده: ' . $desc : 'مسیر استفاده: از تابلو یا فهرست مرتبط',
        'note' => 'مسیر استفاده: از پرونده یا عملیات انتخاب‌شده',
        'backlog' => 'مسیر استفاده: —',
        'ref', 'ref_coord' => $desc !== '' ? 'مسیر استفاده: ' . $desc : 'مسیر استفاده: مشاهده تابلو',
        'diag' => $desc !== '' ? 'مسیر استفاده: ' . $desc : 'مسیر استفاده: ابزار تشخیص مدیریتی',
        default => $desc !== '' ? 'مسیر استفاده: ' . $desc : 'مسیر استفاده: ورود مستقیم از میز کار',
    };
}

/**
 * @return array<string, string>
 */
function m360_staff_home_workbench_group_labels(): array
{
    return [
        M360_STAFF_HOME_GROUP_TODAY => 'کار امروز',
        M360_STAFF_HOME_GROUP_FOLLOWUP => 'پیگیری و جزئیات',
        M360_STAFF_HOME_GROUP_OPERATIONS => 'عملیات مجاز',
        M360_STAFF_HOME_GROUP_REPORTS => 'گزارش‌های مرتبط',
        M360_STAFF_HOME_GROUP_MANAGER_REF => 'مرجع عملیاتی One-Day Run',
        M360_STAFF_HOME_GROUP_COORDINATION_REF => 'مرجع هماهنگی سالن',
        M360_STAFF_HOME_GROUP_BACKLOG => 'موارد غیرفعال / نیازمند تکمیل',
    ];
}

/**
 * @return array<string, string>
 */
function m360_staff_home_role_start_questions(): array
{
    return [
        'OWNER' => 'مدیریت امروز از کجا شروع شود؟',
        'SYSTEM_ADMIN' => 'مدیریت امروز از کجا شروع شود؟',
        'RECEPTION' => 'پذیرش امروز از کجا شروع کند؟',
        'SERVICE_MANAGER' => 'مدیر سالن امروز چه خودروهایی را تخصیص، پیگیری یا ارسال به QC کند؟',
        'TECHNICIAN' => 'تکنسین امروز کار فنی را از کدام تابلوی فنی/اجرا شروع کند؟',
        'PARTS' => 'انبار امروز درخواست قطعه را از کجا ببیند و قطعه را از کجا رزرو/مصرف کند؟',
        'FINANCE' => 'مالی امروز پرداخت، برآورد، فاکتور نهایی و تسویه را از کجا پیگیری کند؟',
        'QC' => 'QC امروز خودروهای آماده کنترل را از کجا ببیند و چک‌لیست را از کجا تکمیل کند؟',
    ];
}

/**
 * @return array{label_fa:string,file:string,description_fa:string,required_role_group:string,group_key:string,card_type:string}
 */
function m360_staff_home_item(
    string $groupKey,
    string $labelFa,
    string $file,
    string $descriptionFa,
    string $roleGroup,
    string $cardType = 'nav'
): array {
    return [
        'group_key' => $groupKey,
        'label_fa' => $labelFa,
        'file' => $file,
        'description_fa' => $descriptionFa,
        'required_role_group' => $roleGroup,
        'card_type' => $cardType,
    ];
}

function m360_staff_home_is_action_endpoint(string $file): bool
{
    if ($file === '') {
        return true;
    }
    if (str_ends_with($file, '-action.php')) {
        return true;
    }

    return in_array($file, [
        'erp-reception-online-request-accept.php',
        'erp-reception-jobcard-action.php',
        'erp-technical-jobcard-action.php',
        'erp-work-execution-action.php',
        'erp-qc-action.php',
        'erp-final-invoice-action.php',
        'erp-settlement-action.php',
        'erp-estimate-action.php',
    ], true);
}

/**
 * @return array{label_fa:string,file:string,description_fa:string,required_role_group:string}
 */
function m360_staff_home_route(string $labelFa, string $file, string $descriptionFa, string $roleGroup): array
{
    return [
        'label_fa' => $labelFa,
        'file' => $file,
        'description_fa' => $descriptionFa,
        'required_role_group' => $roleGroup,
    ];
}

/**
 * @param list<array<string, string>> $items
 * @return list<array{label_fa:string,file:string,description_fa:string,required_role_group:string}>
 */
function m360_staff_home_flatten_allowed_routes(array $items): array
{
    $routes = [];
    foreach ($items as $item) {
        $cardType = (string)($item['card_type'] ?? 'nav');
        $file = (string)($item['file'] ?? '');
        if ($cardType === 'backlog' || $cardType === 'info' || $cardType === 'note') {
            continue;
        }
        if ($file === '' || m360_staff_home_is_action_endpoint($file)) {
            continue;
        }
        if (!m360_staff_home_route_exists($file)) {
            continue;
        }
        $routes[] = m360_staff_home_route(
            (string)($item['label_fa'] ?? ''),
            $file,
            (string)($item['description_fa'] ?? ''),
            (string)($item['required_role_group'] ?? '')
        );
    }

    return $routes;
}

/**
 * @return list<array<string, string>>
 */
function m360_staff_home_workbench_items(string $roleCode): array
{
    $roleCode = strtoupper(trim($roleCode));
    $preview = 'erp-access-permission-preview.php';
    $g = static fn(string $k): string => $k;

    $items = match ($roleCode) {
        'OWNER', 'SYSTEM_ADMIN' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'مدیریت دسترسی پرسنل', 'erp-access-management.php', 'ایجاد کاربر، نقش و رمز موقت', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'خانه محصول', 'erp-product-home.php', 'ورود ماژول‌های عملیاتی P1–P10', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'نقشه مسیرها', 'erp-route-map.php', 'فهرست مسیرهای نصب‌شده', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'داشبورد مدیریت', 'erp-management-dashboard.php', 'شاخص‌ها و پرونده‌های پرریسک — فقط مشاهده', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'مرکز کنترل مالک', 'erp-owner-control-center.php', 'نظارت مالک بر پرونده‌های پرریسک', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'آمادگی انتشار', 'erp-release-readiness.php', 'وضعیت آمادگی انتشار', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'پیش‌نمایش دسترسی', $preview, 'بررسی دسترسی مؤثر کاربران — ابزار مدیریتی', $roleCode),
            ]
        ),
        'RECEPTION' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'درخواست‌های آنلاین', 'erp-reception-online-requests.php', 'شروع پذیرش — مشاهده و پذیرش درخواست‌های جدید', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'JobCardهای پذیرش', 'erp-reception-jobcards.php', 'ثبت ورود و پیشرفت JobCard پذیرش', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'برد قراردادهای پذیرش', 'erp-intake-contracts.php', 'گیت امضای قرارداد P1.5', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات درخواست آنلاین', 'erp-reception-online-request-detail.php', 'از فهرست درخواست‌های آنلاین', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات JobCard پذیرش', 'erp-reception-jobcard-detail.php', 'از فهرست JobCard — فرم‌های عملیاتی', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات قرارداد پذیرش', 'erp-intake-contract-detail.php', 'از برد قراردادها', $roleCode, 'info'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'پذیرش/رد درخواست آنلاین', 'erp-reception-online-request-accept.php', 'از صفحه جزئیات درخواست آنلاین', $roleCode, 'note'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات JobCard پذیرش', 'erp-reception-jobcard-action.php', 'از جزئیات JobCard پذیرش', $roleCode, 'note'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'فرم درخواست مشتری', 'customer-request.php', 'فرم عمومی درخواست مشتری (مرجع)', $roleCode),
            ]
        ),
        'SERVICE_MANAGER' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی فنی', 'erp-technical-board.php', 'تخصیص تکنسین و عیب‌یابی', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی اجرای کار', 'erp-work-execution-board.php', 'پیگیری اجرای کارگاه', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی QC', 'erp-qc-board.php', 'ارسال/بازگشت از QC', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات فنی JobCard', 'erp-technical-jobcard-detail.php', 'از تابلوی فنی — تخصیص و تشخیص', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات اجرای کار', 'erp-work-execution-detail.php', 'از تابلوی اجرا', $roleCode, 'info'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات فنی JobCard', 'erp-technical-jobcard-action.php', 'از جزئیات فنی JobCard', $roleCode, 'note'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات اجرای کار', 'erp-work-execution-action.php', 'از جزئیات اجرای کار', $roleCode, 'note'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'تایم‌لاین JobCard', 'erp-jobcard-timeline.php', 'از مسیر پرونده JobCard باز می‌شود — مشاهده تاریخچه و رویدادها', $roleCode, 'info'),
            ]
        ),
        'TECHNICIAN' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی فنی', 'erp-technical-board.php', 'شروع کار فنی — انتخاب JobCard', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی اجرای کار', 'erp-work-execution-board.php', 'ثبت و تکمیل اجرای کار', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات فنی JobCard', 'erp-technical-jobcard-detail.php', 'تشخیص، یادداشت، سرویس', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات اجرای کار', 'erp-work-execution-detail.php', 'از تابلوی اجرا', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'مصرف قطعه JobCard', 'erp-jobcard-part-use.php', 'ثبت مصرف قطعه برای JobCard', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات عملیات سرویس', 'erp-service-operation-detail.php', 'از جزئیات فنی JobCard', $roleCode, 'info'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات فنی/اجرا', 'erp-technical-jobcard-action.php', 'از جزئیات فنی یا اجرای کار', $roleCode, 'note'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), 'فیلتر کارهای اختصاص‌یافته', '', 'فیلتر کارهای اختصاص‌یافته به تکنسین هنوز تکمیل نشده است.', $roleCode, 'backlog'),
            ]
        ),
        'PARTS' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'رزرو قطعه', 'erp-part-reserve.php', 'رزرو موجودی برای JobCard', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'مصرف قطعه JobCard', 'erp-jobcard-part-use.php', 'ثبت مصرف/صدور قطعه', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'درخواست خرید', 'erp-purchase-request-create.php', 'ثبت درخواست خرید قطعه', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'کاتالوگ قطعات', 'erp-parts-catalog.php', 'جستجوی قطعه', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'تابلوی موجودی', 'erp-stock-board.php', 'وضعیت انبار', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_REPORTS), 'فهرست مصرف', 'erp-jobcard-part-readonly-list.php', 'مشاهده مصرف ثبت‌شده — فقط مشاهده', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), 'فهرست مصرف (نسخه قدیمی)', 'erp-jobcard-part-usage-list.php', 'از «مصرف قطعه JobCard» استفاده کنید.', $roleCode, 'backlog'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), 'فهرست درخواست خرید', 'erp-purchase-request-list.php', 'صفحه فهرست درخواست خرید هنوز ساخته نشده است.', $roleCode, 'backlog'),
            ]
        ),
        'FINANCE' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'پیگیری پرداخت', 'erp-payment-tracking.php', 'وضعیت پرداخت و بدهی', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'برد برآورد', 'erp-estimate-board.php', 'گیت تأیید برآورد P4', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'فاکتور نهایی', 'erp-final-invoice-board.php', 'فاکتور و صورتحساب P7', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات برآورد', 'erp-estimate-detail.php', 'از برد برآورد', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات فاکتور نهایی', 'erp-final-invoice-detail.php', 'از برد فاکتور', $roleCode, 'info'),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات تسویه', 'erp-settlement-detail.php', 'از فاکتور/JobCard', $roleCode, 'info'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات فاکتور/تسویه', 'erp-final-invoice-action.php', 'از جزئیات فاکتور یا تسویه', $roleCode, 'note'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_BACKLOG), 'مرکز مالی', 'erp-finance-center.php', 'صفحه مرکز مالی هنوز ساخته نشده — از پیگیری پرداخت و فاکتور استفاده کنید.', $roleCode, 'backlog'),
            ]
        ),
        'QC' => array_merge(
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'تابلوی QC', 'erp-qc-board.php', 'خودروهای آماده کنترل کیفیت', $roleCode),
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_TODAY), 'کنترل تحویل', 'erp-delivery-control.php', 'آمادگی تحویل به مشتری', $roleCode),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_FOLLOWUP), 'جزئیات QC / چک‌لیست', 'erp-qc-detail.php', 'از تابلوی QC — pass/fail', $roleCode, 'info'),
            ],
            [
                m360_staff_home_item($g(M360_STAFF_HOME_GROUP_OPERATIONS), 'عملیات QC', 'erp-qc-action.php', 'از جزئیات QC', $roleCode, 'note'),
            ]
        ),
        default => [],
    };

    return m360_staff_home_apply_runtime_hold(array_merge(
        $items,
        m360_staff_home_manager_bridge_items($roleCode),
        m360_staff_home_scope_backlog_items($roleCode)
    ));
}

/**
 * @return array<string, list<array<string, string>>>
 */
function m360_staff_home_grouped_workbench(string $roleCode): array
{
    $grouped = [];
    foreach (m360_staff_home_workbench_items($roleCode) as $item) {
        $key = (string)($item['group_key'] ?? M360_STAFF_HOME_GROUP_TODAY);
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $item;
    }

    return $grouped;
}

/**
 * @return array{landing_label:string,access_summary:string,role_start_question:string,workbench_groups:array<string,list<array<string,string>>>,allowed_routes:list<array{label_fa:string,file:string,description_fa:string,required_role_group:string}>,role_code:string,is_unknown:bool}
 */
function m360_staff_home_role_routes(string $roleCode): array
{
    $roleCode = strtoupper(trim($roleCode));
    $questions = m360_staff_home_role_start_questions();

    $meta = [
        'OWNER' => ['داشبورد مالک / مدیریت', 'نظارت، دسترسی و مدیریت سیستم — نه کارگاه روزانه'],
        'SYSTEM_ADMIN' => ['داشبورد مالک / مدیریت', 'نظارت، دسترسی و مدیریت سیستم — نه کارگاه روزانه'],
        'RECEPTION' => ['میز کار پذیرش', 'درخواست آنلاین، JobCard و گیت قرارداد'],
        'SERVICE_MANAGER' => ['میز کار مدیر سالن', 'تخصیص، پیگیری فنی، QC و اجرا'],
        'TECHNICIAN' => ['میز کار تکنسین', 'تشخیص، اجرا و مصرف قطعه'],
        'PARTS' => ['میز کار انبار / قطعات', 'رزرو، مصرف و درخواست خرید'],
        'FINANCE' => ['میز کار مالی', 'پرداخت، برآورد، فاکتور و تسویه'],
        'QC' => ['میز کار کنترل کیفیت', 'QC و آمادگی تحویل'],
    ];

    if (!isset($meta[$roleCode])) {
        return [
            'role_code' => 'UNKNOWN',
            'landing_label' => 'داشبورد پرسنل',
            'access_summary' => M360_STAFF_HOME_UNKNOWN_WARNING_FA,
            'role_start_question' => '',
            'workbench_groups' => [],
            'allowed_routes' => [],
            'is_unknown' => true,
        ];
    }

    $items = m360_staff_home_workbench_items($roleCode);

    return [
        'role_code' => $roleCode,
        'landing_label' => $meta[$roleCode][0],
        'access_summary' => $meta[$roleCode][1],
        'role_start_question' => $questions[$roleCode] ?? '',
        'workbench_groups' => m360_staff_home_grouped_workbench($roleCode),
        'allowed_routes' => m360_staff_home_flatten_allowed_routes($items),
        'is_unknown' => false,
    ];
}

function m360_staff_home_resolve_role_code($conn, int $userId, int $companyId): string
{
    if ($conn === false || $userId <= 0) {
        return 'UNKNOWN';
    }

    $ownerFlag = customer_core_scalar(
        $conn,
        'SELECT TOP 1 is_system_owner FROM dbo.core_users WHERE user_id = ?',
        [$userId]
    );
    if ((string)($ownerFlag ?? '0') === '1') {
        return 'OWNER';
    }

    if ($companyId > 0 && customer_core_table_exists($conn, 'erp_company_users')) {
        $roleCode = customer_core_scalar(
            $conn,
            'SELECT TOP 1 role_code FROM dbo.erp_company_users WHERE user_id = ? AND company_id = ? AND is_active = 1 ORDER BY company_user_id DESC',
            [$userId, $companyId]
        );
        if ($roleCode !== null && trim($roleCode) !== '') {
            $normalized = strtoupper(trim($roleCode));
            if (in_array($normalized, ['OWNER', 'SYSTEM_ADMIN', 'RECEPTION', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS', 'FINANCE', 'QC'], true)) {
                return $normalized;
            }
        }
    }

    $roleRows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 r.role_key FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_id = ? AND ur.revoked_at IS NULL
         ORDER BY r.sort_order, ur.user_role_id',
        [$userId]
    );
    $roleKey = strtolower(trim((string)($roleRows[0]['role_key'] ?? '')));
    $keyMap = [
        'owner' => 'OWNER',
        'system_admin' => 'SYSTEM_ADMIN',
        'reception_staff' => 'RECEPTION',
        'operations_manager' => 'SERVICE_MANAGER',
        'mechanical_staff' => 'TECHNICIAN',
        'inventory_staff' => 'PARTS',
        'finance_staff' => 'FINANCE',
        'technical_manager' => 'QC',
    ];

    return $keyMap[$roleKey] ?? 'UNKNOWN';
}

/**
 * @return array<string, mixed>
 */
function m360_staff_home_load_context($conn): array
{
    m360_staff_home_require_session();

    $userId = (int)erp_auth_context_session_user_id();
    $username = (string)($_SESSION['erp_username'] ?? '');
    $companyId = (int)($_SESSION['erp_company_id'] ?? 0);

    $fullName = $username;
    $deptName = '—';
    $positionName = '—';
    $permissionCount = 0;

    if ($conn !== false) {
        $userRows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 username, full_name FROM dbo.core_users WHERE user_id = ?',
            [$userId]
        );
        if (($userRows[0] ?? null) !== null) {
            $fullName = m360_staff_home_text_from_odbc((string)($userRows[0]['full_name'] ?? $fullName));
            if ($username === '') {
                $username = (string)($userRows[0]['username'] ?? '');
            }
        }

        $profileRows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 d.dept_name, p.position_name
             FROM dbo.core_staff_profiles sp
             LEFT JOIN dbo.core_departments d ON d.department_id = sp.department_id
             LEFT JOIN dbo.core_positions p ON p.position_id = sp.position_id
             WHERE sp.user_id = ?',
            [$userId]
        );
        if (($profileRows[0] ?? null) !== null) {
            $deptRaw = m360_staff_home_text_from_odbc((string)($profileRows[0]['dept_name'] ?? ''));
            $positionRaw = m360_staff_home_text_from_odbc((string)($profileRows[0]['position_name'] ?? ''));
            $deptName = trim($deptRaw) !== '' ? $deptRaw : '—';
            $positionName = trim($positionRaw) !== '' ? $positionRaw : '—';
        }

        if (function_exists('erp_auth_current_permissions')) {
            $permResult = erp_auth_current_permissions($conn, $userId);
            if (!empty($permResult['ok']) && is_array($permResult['permissions'] ?? null)) {
                $permissionCount = count($permResult['permissions']);
            }
        }
    }

    $roleCode = m360_staff_home_resolve_role_code($conn, $userId, $companyId);
    $matrix = m360_staff_home_role_routes($roleCode);

    return [
        'user_id' => $userId,
        'username' => $username,
        'full_name' => $fullName,
        'company_id' => $companyId,
        'role_code' => $matrix['role_code'],
        'role_label_fa' => m360_staff_home_role_label_fa($matrix['role_code']),
        'department_name' => $deptName,
        'position_name' => $positionName,
        'permission_count' => $permissionCount,
        'landing_label' => $matrix['landing_label'],
        'access_summary' => $matrix['access_summary'],
        'role_start_question' => (string)($matrix['role_start_question'] ?? ''),
        'workbench_groups' => is_array($matrix['workbench_groups'] ?? null) ? $matrix['workbench_groups'] : [],
        'allowed_routes' => $matrix['allowed_routes'],
        'is_unknown' => $matrix['is_unknown'],
    ];
}

function m360_staff_home_route_href(string $file, int $userId): string
{
    if ($file === 'erp-access-permission-preview.php') {
        return $file . '?user_id=' . $userId;
    }

    return $file;
}

function m360_staff_home_route_exists(string $file): bool
{
    return m360_nav_file_exists($file);
}

function m360_staff_home_route_status(array $item): string
{
    $cardType = (string)($item['card_type'] ?? 'nav');
    $file = (string)($item['file'] ?? '');

    if ($cardType === 'backlog') {
        return 'نیازمند تکمیل';
    }
    if ($cardType === 'runtime_hold') {
        return (string)($item['runtime_status_fa'] ?? M360_STAFF_HOME_RUNTIME_HOLD_FA);
    }
    if ($cardType === 'ref_coord') {
        return 'مرجع هماهنگی';
    }
    if ($cardType === 'ref') {
        return 'مرجع';
    }
    if ($cardType === 'diag') {
        return 'ابزار تشخیص';
    }
    if ($cardType === 'info') {
        return M360_STAFF_HOME_STATUS_GUIDED_FA;
    }
    if ($cardType === 'note') {
        return M360_STAFF_HOME_STATUS_ACTION_FA;
    }
    if ($file === '') {
        return 'نیازمند تکمیل';
    }
    if (!m360_staff_home_route_exists($file)) {
        return 'غیرفعال';
    }

    return 'موجود';
}

function m360_staff_home_route_status_class(array $item): string
{
    $label = m360_staff_home_route_status($item);

    return match ($label) {
        'موجود' => 'present',
        'مرجع', 'مرجع هماهنگی' => 'reference',
        'ابزار تشخیص' => 'diag',
        M360_STAFF_HOME_STATUS_GUIDED_FA => 'guided',
        M360_STAFF_HOME_STATUS_ACTION_FA => 'action',
        'نیازمند بازبینی عملیاتی', 'نیازمند بررسی عملیاتی' => 'runtime-hold',
        'غیرفعال' => 'disabled',
        default => 'backlog',
    };
}

function m360_staff_home_item_clickable(array $item): bool
{
    $cardType = (string)($item['card_type'] ?? 'nav');
    $file = (string)($item['file'] ?? '');

    if ($cardType === 'backlog' || $cardType === 'runtime_hold' || $cardType === 'note' || $cardType === 'info') {
        return false;
    }
    if (!m360_staff_home_is_runtime_ready($file)) {
        return false;
    }
    if (in_array($cardType, ['ref', 'ref_coord', 'diag', 'nav'], true)) {
        if ($file === '' || m360_staff_home_is_action_endpoint($file)) {
            return false;
        }

        return m360_staff_home_route_exists($file);
    }
    if ($file === '' || m360_staff_home_is_action_endpoint($file)) {
        return false;
    }

    return m360_staff_home_route_exists($file);
}

/**
 * @param array<string, string> $item
 */
function m360_staff_home_render_workbench_item(array $item, int $userId): void
{
    $file = (string)($item['file'] ?? '');
    $cardType = (string)($item['card_type'] ?? 'nav');
    $status = m360_staff_home_route_status($item);
    $statusClass = m360_staff_home_route_status_class($item);
    $clickable = m360_staff_home_item_clickable($item);

    $class = 'm360-staff-route-card';
    if (!$clickable) {
        $class .= ' is-missing';
    }
    if ($cardType === 'backlog') {
        $class .= ' is-backlog';
    }
    if ($cardType === 'runtime_hold') {
        $class .= ' is-runtime-hold';
    }
    if ($cardType === 'note') {
        $class .= ' is-note';
    }
    if ($cardType === 'info') {
        $class .= ' is-info';
    }
    if (in_array($cardType, ['ref', 'ref_coord'], true)) {
        $class .= ' is-ref';
    }
    if ($cardType === 'diag') {
        $class .= ' is-diag';
    }

    $dataRoute = $file !== '' ? ' data-route="' . m360_staff_home_h($file) . '"' : '';
    echo '<div class="' . m360_staff_home_h($class) . '"' . $dataRoute . '>';
    echo '<div class="m360-staff-route-head">';
    echo '<h3>' . m360_staff_home_h((string)($item['label_fa'] ?? '')) . '</h3>';
    echo '<span class="m360-staff-status m360-staff-status-' . m360_staff_home_h($statusClass) . '">' . m360_staff_home_h($status) . '</span>';
    echo '</div>';
    echo '<p class="m360-staff-route-desc">' . m360_staff_home_h((string)($item['description_fa'] ?? '')) . '</p>';
    echo '<p class="m360-staff-route-meta">' . m360_staff_home_h(m360_staff_home_usage_path_fa($item)) . '</p>';

    if ($clickable) {
        $href = m360_staff_home_route_href($file, $userId);
        $btnLabel = match ($cardType) {
            'ref', 'ref_coord' => M360_STAFF_HOME_BTN_BOARD_FA,
            'diag' => M360_STAFF_HOME_BTN_REPORT_FA,
            default => 'ورود به صفحه',
        };
        echo '<a class="m360-staff-btn" href="' . m360_staff_home_h($href) . '">' . m360_staff_home_h($btnLabel) . '</a>';
    } elseif ($cardType === 'info') {
        echo '<p class="m360-staff-missing">' . m360_staff_home_h(M360_STAFF_HOME_INFO_ROUTE_FA) . '</p>';
        echo '<span class="m360-staff-btn disabled" aria-disabled="true">' . m360_staff_home_h(M360_STAFF_HOME_GUIDED_BTN_FA) . '</span>';
    } elseif ($cardType === 'note') {
        echo '<p class="m360-staff-note">' . m360_staff_home_h(M360_STAFF_HOME_NOTE_ROUTE_FA) . '</p>';
    } elseif ($cardType === 'backlog') {
        echo '<p class="m360-staff-missing">' . m360_staff_home_h(M360_STAFF_HOME_BACKLOG_FA) . '</p>';
    } elseif ($cardType === 'runtime_hold') {
        echo '<p class="m360-staff-missing">' . m360_staff_home_h((string)($item['description_fa'] ?? M360_STAFF_HOME_RUNTIME_HOLD_FA)) . '</p>';
        echo '<span class="m360-staff-btn disabled" aria-disabled="true">' . m360_staff_home_h(M360_STAFF_HOME_RUNTIME_HOLD_FA) . '</span>';
    } else {
        echo '<p class="m360-staff-missing">' . m360_staff_home_h(M360_STAFF_HOME_MISSING_ROUTE_FA) . '</p>';
    }
    echo '</div>';
}

/**
 * @param array<string, list<array<string, string>>> $workbenchGroups
 */
function m360_staff_home_render_workbench(array $workbenchGroups, int $userId): void
{
    $labels = m360_staff_home_workbench_group_labels();
    $order = [
        M360_STAFF_HOME_GROUP_TODAY,
        M360_STAFF_HOME_GROUP_FOLLOWUP,
        M360_STAFF_HOME_GROUP_OPERATIONS,
        M360_STAFF_HOME_GROUP_REPORTS,
        M360_STAFF_HOME_GROUP_MANAGER_REF,
        M360_STAFF_HOME_GROUP_COORDINATION_REF,
        M360_STAFF_HOME_GROUP_BACKLOG,
    ];

    foreach ($order as $groupKey) {
        $items = $workbenchGroups[$groupKey] ?? [];
        if ($items === []) {
            continue;
        }
        echo '<section class="m360-staff-workbench-group">';
        $groupClass = 'm360-staff-group-title';
        if ($groupKey === M360_STAFF_HOME_GROUP_MANAGER_REF) {
            $groupClass .= ' m360-staff-group-title-bridge';
        } elseif ($groupKey === M360_STAFF_HOME_GROUP_COORDINATION_REF) {
            $groupClass .= ' m360-staff-group-title-coordination';
        }
        echo '<h2 class="' . m360_staff_home_h($groupClass) . '">' . m360_staff_home_h($labels[$groupKey] ?? $groupKey) . '</h2>';
        echo '<div class="m360-staff-routes">';
        foreach ($items as $item) {
            m360_staff_home_render_workbench_item($item, $userId);
        }
        echo '</div></section>';
    }
}

/**
 * @param array{label_fa:string,file:string,description_fa:string,required_role_group:string} $route
 */
function m360_staff_home_render_route_card(array $route, int $userId): void
{
    m360_staff_home_render_workbench_item([
        'group_key' => M360_STAFF_HOME_GROUP_TODAY,
        'label_fa' => (string)($route['label_fa'] ?? ''),
        'file' => (string)($route['file'] ?? ''),
        'description_fa' => (string)($route['description_fa'] ?? ''),
        'required_role_group' => (string)($route['required_role_group'] ?? ''),
        'card_type' => 'nav',
    ], $userId);
}
