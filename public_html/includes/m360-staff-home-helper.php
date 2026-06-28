<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.4.4-B — Role-aware staff landing routes (post-login bridge only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

const M360_STAFF_HOME_UNKNOWN_WARNING_FA = 'برای این کاربر هنوز نقش عملیاتی معتبر تعریف نشده است.';
const M360_STAFF_HOME_MISSING_ROUTE_FA = 'این صفحه هنوز در مسیر نصب‌شده موجود نیست.';
const M360_STAFF_HOME_REDIRECT_PATH = 'erp-staff-home.php';

const M360_OWNER_LOGIN_REDIRECT_PRIMARY = 'erp-product-home.php';

/** @var list<string> */
const M360_OWNER_LOGIN_REDIRECT_FALLBACKS = [
    'erp-owner-control-center.php',
    'erp-management-dashboard.php',
];

/** @var list<string> */
const M360_STAFF_HOME_ADMIN_ROLE_CODES = ['OWNER', 'SYSTEM_ADMIN'];

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

function m360_staff_home_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
 * @return array{landing_label:string,access_summary:string,allowed_routes:list<array{label_fa:string,file:string,description_fa:string,required_role_group:string}>,role_code:string,is_unknown:bool}
 */
function m360_staff_home_role_routes(string $roleCode): array
{
    $roleCode = strtoupper(trim($roleCode));
    $preview = 'erp-access-permission-preview.php';

    $matrices = [
        'OWNER' => [
            'landing_label' => 'داشبورد مالک / مدیریت',
            'access_summary' => 'نمای عملیاتی کامل مالک و مدیریت سیستم',
            'allowed_routes' => [
                m360_staff_home_route('خانه محصول', 'erp-product-home.php', 'ورود محصولی ERP', 'OWNER'),
                m360_staff_home_route('داشبورد مدیریت', 'erp-management-dashboard.php', 'نمای مدیریتی عملیات', 'OWNER'),
                m360_staff_home_route('مرکز کنترل مالک', 'erp-owner-control-center.php', 'کنترل و نظارت مالک', 'OWNER'),
                m360_staff_home_route('مدیریت دسترسی', 'erp-access-management.php', 'ایجاد و مدیریت پرسنل', 'OWNER'),
                m360_staff_home_route('نقشه مسیرها', 'erp-route-map.php', 'فهرست مسیرهای نصب‌شده', 'OWNER'),
                m360_staff_home_route('آمادگی انتشار', 'erp-release-readiness.php', 'وضعیت Release Readiness', 'OWNER'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'OWNER'),
            ],
        ],
        'SYSTEM_ADMIN' => [
            'landing_label' => 'داشبورد مالک / مدیریت',
            'access_summary' => 'نمای عملیاتی کامل مالک و مدیریت سیستم',
            'allowed_routes' => [
                m360_staff_home_route('خانه محصول', 'erp-product-home.php', 'ورود محصولی ERP', 'SYSTEM_ADMIN'),
                m360_staff_home_route('داشبورد مدیریت', 'erp-management-dashboard.php', 'نمای مدیریتی عملیات', 'SYSTEM_ADMIN'),
                m360_staff_home_route('مرکز کنترل مالک', 'erp-owner-control-center.php', 'کنترل و نظارت مالک', 'SYSTEM_ADMIN'),
                m360_staff_home_route('مدیریت دسترسی', 'erp-access-management.php', 'ایجاد و مدیریت پرسنل', 'SYSTEM_ADMIN'),
                m360_staff_home_route('نقشه مسیرها', 'erp-route-map.php', 'فهرست مسیرهای نصب‌شده', 'SYSTEM_ADMIN'),
                m360_staff_home_route('آمادگی انتشار', 'erp-release-readiness.php', 'وضعیت Release Readiness', 'SYSTEM_ADMIN'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'SYSTEM_ADMIN'),
            ],
        ],
        'RECEPTION' => [
            'landing_label' => 'داشبورد پذیرش',
            'access_summary' => 'ورود عملیاتی پذیرش — درخواست آنلاین و JobCard پذیرش',
            'allowed_routes' => [
                m360_staff_home_route('درخواست‌های آنلاین', 'erp-reception-online-requests.php', 'مشاهده و پذیرش درخواست مشتری', 'RECEPTION'),
                m360_staff_home_route('JobCardهای پذیرش', 'erp-reception-jobcards.php', 'فهرست و مدیریت JobCard پذیرش', 'RECEPTION'),
                m360_staff_home_route('جزئیات JobCard پذیرش', 'erp-reception-jobcard-detail.php', 'جزئیات JobCard (از فهرست)', 'RECEPTION'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'RECEPTION'),
            ],
        ],
        'SERVICE_MANAGER' => [
            'landing_label' => 'داشبورد مدیر سرویس / سالن',
            'access_summary' => 'مدیریت سالن — تخصیص و نظارت فنی و QC',
            'allowed_routes' => [
                m360_staff_home_route('تابلوی فنی', 'erp-technical-board.php', 'نظارت و تخصیص کار فنی', 'SERVICE_MANAGER'),
                m360_staff_home_route('تابلوی اجرای کار', 'erp-work-execution-board.php', 'پیگیری اجرای کارگاه', 'SERVICE_MANAGER'),
                m360_staff_home_route('تابلوی QC', 'erp-qc-board.php', 'ارسال و بازگشت از QC', 'SERVICE_MANAGER'),
                m360_staff_home_route('تایم‌لاین JobCard', 'erp-jobcard-timeline.php', 'مشاهده تاریخچه JobCard', 'SERVICE_MANAGER'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'SERVICE_MANAGER'),
            ],
        ],
        'TECHNICIAN' => [
            'landing_label' => 'داشبورد تکنسین',
            'access_summary' => 'اجرای روزانه کار فنی و تشخیص',
            'allowed_routes' => [
                m360_staff_home_route('تابلوی فنی', 'erp-technical-board.php', 'کارهای فنی محول‌شده', 'TECHNICIAN'),
                m360_staff_home_route('تابلوی اجرای کار', 'erp-work-execution-board.php', 'ثبت و پیگیری اجرای کار', 'TECHNICIAN'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'TECHNICIAN'),
            ],
        ],
        'PARTS' => [
            'landing_label' => 'داشبورد قطعات / انبار',
            'access_summary' => 'عملیات قطعات و موجودی انبار',
            'allowed_routes' => [
                m360_staff_home_route('کاتالوگ قطعات', 'erp-parts-catalog.php', 'فهرست قطعات', 'PARTS'),
                m360_staff_home_route('تابلوی موجودی', 'erp-stock-board.php', 'وضعیت موجودی انبار', 'PARTS'),
                m360_staff_home_route('رزرو قطعه', 'erp-part-reserve.php', 'رزرو قطعه برای JobCard', 'PARTS'),
                m360_staff_home_route('مصرف قطعه JobCard', 'erp-jobcard-part-usage-list.php', 'فهرست مصرف قطعات', 'PARTS'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'PARTS'),
            ],
        ],
        'FINANCE' => [
            'landing_label' => 'داشبورد مالی',
            'access_summary' => 'پیگیری پرداخت، فاکتور نهایی و تسویه',
            'allowed_routes' => [
                m360_staff_home_route('مرکز مالی', 'erp-finance-center.php', 'ورود عملیات مالی', 'FINANCE'),
                m360_staff_home_route('پیگیری پرداخت', 'erp-payment-tracking.php', 'وضعیت پرداخت‌ها', 'FINANCE'),
                m360_staff_home_route('فاکتور نهایی', 'erp-final-invoice-board.php', 'فاکتور نهایی و صورتحساب', 'FINANCE'),
                m360_staff_home_route('جزئیات تسویه', 'erp-settlement-detail.php', 'تسویه (از فاکتور/JobCard)', 'FINANCE'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'FINANCE'),
            ],
        ],
        'QC' => [
            'landing_label' => 'داشبورد کنترل کیفیت',
            'access_summary' => 'کنترل کیفیت و آمادگی تحویل',
            'allowed_routes' => [
                m360_staff_home_route('تابلوی QC', 'erp-qc-board.php', 'بررسی و ثبت QC', 'QC'),
                m360_staff_home_route('کنترل تحویل', 'erp-delivery-control.php', 'آمادگی تحویل به مشتری', 'QC'),
                m360_staff_home_route('پیش‌نمایش دسترسی', $preview, 'مشاهده دسترسی مؤثر کاربر', 'QC'),
            ],
        ],
    ];

    if (!isset($matrices[$roleCode])) {
        return [
            'role_code' => 'UNKNOWN',
            'landing_label' => 'داشبورد پرسنل',
            'access_summary' => M360_STAFF_HOME_UNKNOWN_WARNING_FA,
            'allowed_routes' => [],
            'is_unknown' => true,
        ];
    }

    $matrix = $matrices[$roleCode];

    return [
        'role_code' => $roleCode,
        'landing_label' => $matrix['landing_label'],
        'access_summary' => $matrix['access_summary'],
        'allowed_routes' => $matrix['allowed_routes'],
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
            $fullName = (string)($userRows[0]['full_name'] ?? $fullName);
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
            $deptName = trim((string)($profileRows[0]['dept_name'] ?? '')) !== '' ? (string)$profileRows[0]['dept_name'] : '—';
            $positionName = trim((string)($profileRows[0]['position_name'] ?? '')) !== '' ? (string)$profileRows[0]['position_name'] : '—';
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
        'department_name' => $deptName,
        'position_name' => $positionName,
        'permission_count' => $permissionCount,
        'landing_label' => $matrix['landing_label'],
        'access_summary' => $matrix['access_summary'],
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

/**
 * @param array{label_fa:string,file:string,description_fa:string,required_role_group:string} $route
 */
function m360_staff_home_render_route_card(array $route, int $userId): void
{
    $file = (string)($route['file'] ?? '');
    $exists = $file !== '' && m360_staff_home_route_exists($file);
    $href = m360_staff_home_route_href($file, $userId);

    echo '<div class="m360-staff-route-card' . ($exists ? '' : ' is-missing') . '">';
    echo '<h3>' . m360_staff_home_h((string)($route['label_fa'] ?? '')) . '</h3>';
    echo '<p class="m360-staff-route-desc">' . m360_staff_home_h((string)($route['description_fa'] ?? '')) . '</p>';
    if ($exists) {
        echo '<a class="m360-staff-btn" href="' . m360_staff_home_h($href) . '">ورود به صفحه</a>';
    } else {
        echo '<p class="m360-staff-missing">' . m360_staff_home_h(M360_STAFF_HOME_MISSING_ROUTE_FA) . '</p>';
        echo '<span class="m360-staff-btn disabled" aria-disabled="true">' . m360_staff_home_h($file) . '</span>';
    }
    echo '</div>';
}
