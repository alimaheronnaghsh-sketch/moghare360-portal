<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.4 — Owner/admin access management console (shared bootstrap).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

const M360_ACCESS_MGMT_CSRF = 'access_mgmt_p114';
const M360_ACCESS_MGMT_MIN_STAFF_USER_ID = 20001;
const M360_ACCESS_MGMT_MIGRATION_SOURCE = 'ACCESS_MGMT_UI';

/** @var array<string, array{role_key:string, erp_role_code:string, label_fa:string}> */
const M360_ACCESS_MGMT_ROLE_CODE_MAP = [
    'OWNER' => ['role_key' => 'owner', 'erp_role_code' => 'OWNER', 'label_fa' => 'مالک'],
    'SYSTEM_ADMIN' => ['role_key' => 'system_admin', 'erp_role_code' => 'SYSTEM_ADMIN', 'label_fa' => 'ادمین سیستم'],
    'RECEPTION' => ['role_key' => 'reception_staff', 'erp_role_code' => 'RECEPTION', 'label_fa' => 'پذیرش'],
    'SERVICE_MANAGER' => ['role_key' => 'operations_manager', 'erp_role_code' => 'SERVICE_MANAGER', 'label_fa' => 'مدیر سرویس'],
    'TECHNICIAN' => ['role_key' => 'mechanical_staff', 'erp_role_code' => 'TECHNICIAN', 'label_fa' => 'تکنسین'],
    'PARTS' => ['role_key' => 'inventory_staff', 'erp_role_code' => 'PARTS', 'label_fa' => 'قطعات / انبار'],
    'FINANCE' => ['role_key' => 'finance_staff', 'erp_role_code' => 'FINANCE', 'label_fa' => 'مالی'],
    'QC' => ['role_key' => 'technical_manager', 'erp_role_code' => 'QC', 'label_fa' => 'کنترل کیفیت'],
];

/** @var list<string> */
const M360_ACCESS_MGMT_PROTECTED_ROLE_KEYS = ['owner', 'system_admin'];

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/**
 * Normalize ODBC/SQL Server text for UTF-8 HTML output (does not change DB values).
 */
function m360_access_text_from_odbc(mixed $value): string
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

function m360_access_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_access_mgmt_h(?string $value): string
{
    return m360_access_h(m360_access_text_from_odbc($value));
}

/**
 * @param list<mixed> $params
 * @return list<array<string, string>>
 */
function m360_access_fetch_rows($connection, string $sql, array $params = []): array
{
    if ($connection === false) {
        return [];
    }

    $statement = customer_core_execute($connection, $sql, $params);
    if ($statement === false) {
        return [];
    }

    $rows = [];

    if (function_exists('odbc_fetch_array')) {
        while (true) {
            $row = @odbc_fetch_array($statement);
            if (!is_array($row)) {
                break;
            }

            $normalized = [];
            foreach ($row as $name => $cell) {
                $normalized[strtolower((string)$name)] = $cell === false || $cell === null
                    ? ''
                    : m360_access_text_from_odbc((string)$cell);
            }

            if ($normalized !== []) {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

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

            $cell = @odbc_result($statement, $i);
            $row[strtolower((string)$name)] = $cell === false || $cell === null
                ? ''
                : m360_access_text_from_odbc((string)$cell);
        }

        if ($row !== []) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function m360_access_mgmt_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/**
 * @return array<string, array{role_key:string, erp_role_code:string, label_fa:string}>
 */
function m360_access_mgmt_role_code_map(): array
{
    return M360_ACCESS_MGMT_ROLE_CODE_MAP;
}

/**
 * @return list<string>
 */
function m360_access_mgmt_first_wave_role_codes(): array
{
    return array_keys(M360_ACCESS_MGMT_ROLE_CODE_MAP);
}

function m360_access_mgmt_resolve_role_code(string $roleCode): ?array
{
    $roleCode = strtoupper(trim($roleCode));

    return M360_ACCESS_MGMT_ROLE_CODE_MAP[$roleCode] ?? null;
}

function m360_access_mgmt_resolve_role_key(string $roleKey): ?array
{
    $roleKey = strtolower(trim($roleKey));
    foreach (M360_ACCESS_MGMT_ROLE_CODE_MAP as $code => $meta) {
        if ($meta['role_key'] === $roleKey) {
            return array_merge(['ui_role_code' => $code], $meta);
        }
    }

    return null;
}

/**
 * @return resource|false
 */
function m360_access_mgmt_db()
{
    return customer_core_db();
}

function m360_access_mgmt_actor_can_manage_privileged($conn, int $actorUserId): bool
{
    if ($conn === false || $actorUserId <= 0) {
        return false;
    }

    return erp_auth_is_system_owner($conn, $actorUserId);
}

function m360_access_mgmt_actor_is_admin($conn, int $actorUserId): bool
{
    if ($actorUserId <= 0) {
        return false;
    }

    if ($conn !== false && erp_auth_is_system_owner($conn, $actorUserId)) {
        return true;
    }

    if ($conn === false) {
        return $actorUserId === ERP_PHASE1_PLATFORM_OWNER_ID;
    }

    $roles = erp_auth_current_roles($conn, $actorUserId);
    $keys = $roles['role_keys'] ?? [];

    return in_array('owner', $keys, true) || in_array('system_admin', $keys, true);
}

function m360_access_mgmt_require_admin(): int
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();

    if ($userId === null || $userId <= 0) {
        header('Location: owner-login.php');
        exit;
    }

    $conn = m360_access_mgmt_db();
    if (!m360_access_mgmt_actor_is_admin($conn, $userId)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Access denied. Owner or system administrator required.';
        exit;
    }

    return $userId;
}

function m360_access_mgmt_require_post_csrf(): void
{
    erp_csrf_require_valid(M360_ACCESS_MGMT_CSRF, $_POST['erp_csrf_token'] ?? null);
}

function m360_access_mgmt_mask_mobile(?string $mobile): string
{
    $mobile = trim((string)$mobile);
    if ($mobile === '') {
        return '—';
    }

    if (strlen($mobile) <= 4) {
        return '****';
    }

    return str_repeat('*', max(4, strlen($mobile) - 4)) . substr($mobile, -4);
}

function m360_access_mgmt_default_company_id($conn): int
{
    if ($conn === false || !customer_core_table_exists($conn, 'erp_companies')) {
        return 0;
    }

    $id = customer_core_scalar(
        $conn,
        "SELECT TOP 1 company_id FROM dbo.erp_companies WHERE company_code = N'MOGHAREH_MAIN' ORDER BY company_id"
    );

    if ($id !== null && (int)$id > 0) {
        return (int)$id;
    }

    $id = customer_core_scalar($conn, 'SELECT TOP 1 company_id FROM dbo.erp_companies WHERE is_active = 1 ORDER BY company_id');

    return $id !== null ? (int)$id : 0;
}

/**
 * @return list<array<string, string>>
 */
function m360_access_mgmt_nav(): array
{
    return [
        ['href' => 'erp-access-management.php', 'label' => 'مدیریت دسترسی'],
        ['href' => 'erp-access-user-create.php', 'label' => 'ایجاد پرسنل'],
        ['href' => 'erp-access-change-history.php', 'label' => 'تاریخچه'],
        ['href' => 'erp-product-home.php', 'label' => 'خانه محصول'],
        ['href' => 'owner-login.php', 'label' => 'ورود مدیریت'],
    ];
}

function m360_access_mgmt_render_head(string $title): void
{
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . m360_access_mgmt_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/css/m360-access-management.css">';
    echo '</head><body class="m360-access-page"><div class="m360-access-wrap">';
    echo '<header class="m360-access-banner"><h1>' . m360_access_mgmt_h($title) . '</h1>';
    echo '<p>MOGHARE360 V1 — مدیریت دسترسی پرسنل (SQL Server Identity)</p></header>';
    echo '<nav class="m360-access-nav">';
    foreach (m360_access_mgmt_nav() as $link) {
        echo '<a href="' . m360_access_mgmt_h($link['href']) . '">' . m360_access_mgmt_h($link['label']) . '</a>';
    }
    echo '</nav>';
}

function m360_access_mgmt_render_foot(): void
{
    echo '<footer class="m360-access-foot"><p>JSON import فقط bootstrap/fallback — مسیر اصلی این UI است.</p></footer>';
    echo '</div></body></html>';
}

function m360_access_mgmt_flash(string $message, string $type = 'info'): void
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['m360_access_flash'] = ['message' => $message, 'type' => $type];
}

/**
 * @return array{message:string,type:string}|null
 */
function m360_access_mgmt_pull_flash(): ?array
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $flash = $_SESSION['m360_access_flash'] ?? null;
    unset($_SESSION['m360_access_flash']);

    return is_array($flash) ? $flash : null;
}

function m360_access_mgmt_render_flash(): void
{
    $flash = m360_access_mgmt_pull_flash();
    if ($flash === null) {
        return;
    }
    $cls = ($flash['type'] ?? '') === 'error' ? 'm360-access-alert-error' : 'm360-access-alert-info';
    echo '<div class="m360-access-alert ' . m360_access_mgmt_h($cls) . '">' . m360_access_mgmt_h((string)($flash['message'] ?? '')) . '</div>';
}

function m360_access_mgmt_lifecycle_options(): array
{
    return [
        'DRAFT' => 'DRAFT',
        'PENDING_ONBOARDING' => 'PENDING_ONBOARDING',
        'ACTIVE' => 'ACTIVE',
        'SUSPENDED' => 'SUSPENDED',
        'INACTIVE' => 'INACTIVE',
    ];
}

function m360_access_mgmt_effective_access_label($conn, int $userId): string
{
    if ($conn === false || $userId <= 0) {
        return 'UNKNOWN';
    }

    $user = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 is_login_enabled, lifecycle_state, is_system_owner FROM dbo.core_users WHERE user_id = ?',
        [$userId]
    )[0] ?? null;

    if ($user === null) {
        return 'MISSING';
    }

    if ((string)($user['lifecycle_state'] ?? '') !== 'ACTIVE' || (string)($user['is_login_enabled'] ?? '0') !== '1') {
        return 'DISABLED';
    }

    $roleCount = customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.core_user_roles ur WHERE ur.user_id = ? AND ur.revoked_at IS NULL',
        [$userId]
    );

    return ((int)($roleCount ?? '0')) > 0 ? 'EFFECTIVE' : 'NO_ROLE';
}
