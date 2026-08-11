<?php
declare(strict_types=1);

/**
 * PeopleOS HR — Central ERP identity bridge helpers.
 * Prefer core_users + erp session; do not create p360_users for new self-service.
 */

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const P360HR_TEMP_PASSWORD_POLICY = 'M360123456';
const P360HR_RESERVED_PERSONNEL_CODE = 'M360-100001';

function p360hr_odbc()
{
    return customer_core_db();
}

function p360hr_require_central_login(): void
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1) {
        header('Location: ../staff-login.php');
        exit;
    }
}

function p360hr_current_core_user_row()
{
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1) {
        return null;
    }
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, 'SELECT TOP 1 user_id, username, full_name, is_system_owner, is_login_enabled, lifecycle_state, must_change_password, password_changed_at FROM dbo.core_users WHERE user_id=?');
    if ($stmt === false || !@odbc_execute($stmt, [$uid])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return is_array($row) ? $row : null;
}

function p360hr_must_change_password(?array $userRow = null): bool
{
    $row = $userRow ?? p360hr_current_core_user_row();
    if ($row === null) {
        return false;
    }
    $v = $row['must_change_password'] ?? $row['MUST_CHANGE_PASSWORD'] ?? 0;
    return (int)$v === 1 || $v === true || $v === '1';
}

function p360hr_require_password_changed_for_cartable(): void
{
    p360hr_require_central_login();
    if (p360hr_must_change_password()) {
        header('Location: my-password.php?forced=1');
        exit;
    }
}

function p360hr_employee_for_current_user(): ?array
{
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1) {
        return null;
    }
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, 'SELECT TOP 1 * FROM dbo.p360_employees WHERE core_user_id=?');
    if ($stmt === false || !@odbc_execute($stmt, [$uid])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return is_array($row) ? $row : null;
}

function p360hr_employee_by_id(int $employeeId): ?array
{
    if ($employeeId < 1) {
        return null;
    }
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, 'SELECT TOP 1 * FROM dbo.p360_employees WHERE employee_id=?');
    if ($stmt === false || !@odbc_execute($stmt, [$employeeId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return is_array($row) ? $row : null;
}

function p360hr_can_manage_personnel(): bool
{
    $row = p360hr_current_core_user_row();
    if ($row === null) {
        return false;
    }
    $owner = $row['is_system_owner'] ?? $row['IS_SYSTEM_OWNER'] ?? 0;
    if ((int)$owner === 1 || $owner === true || $owner === '1') {
        return true;
    }
    $uid = (int)($row['user_id'] ?? $row['USER_ID'] ?? 0);
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, "SELECT TOP 1 1 AS x FROM dbo.erp_company_users WHERE user_id=? AND is_active=1 AND role_code IN (N'SYSTEM_ADMIN', N'OWNER')");
    if ($stmt === false || !@odbc_execute($stmt, [$uid])) {
        return false;
    }
    return odbc_fetch_array($stmt) !== false;
}

function p360hr_assert_own_employee(int $employeeId): void
{
    $mine = p360hr_employee_for_current_user();
    if ($mine !== null && (int)($mine['employee_id'] ?? $mine['EMPLOYEE_ID'] ?? 0) === $employeeId) {
        return;
    }
    if (p360hr_can_manage_personnel()) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><body><p>دسترسی به پرونده پرسنلی دیگران مجاز نیست.</p></body></html>';
    exit;
}

function p360hr_fingerprint_code(int $employeeId): string
{
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, 'SELECT TOP 1 device_user_code FROM dbo.p360_employee_device_users WHERE employee_id=? AND is_active=1 ORDER BY id DESC');
    if ($stmt === false || !@odbc_execute($stmt, [$employeeId])) {
        return 'ثبت نشده';
    }
    $row = odbc_fetch_array($stmt);
    if (!is_array($row)) {
        return 'ثبت نشده';
    }
    $code = trim((string)($row['device_user_code'] ?? $row['DEVICE_USER_CODE'] ?? ''));
    return $code !== '' ? $code : 'ثبت نشده';
}

function p360hr_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function p360hr_layout_start(string $title, string $subtitle = ''): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $user = p360hr_current_core_user_row();
    $name = p360hr_h((string)($user['full_name'] ?? $user['FULL_NAME'] ?? ''));
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $navActive = static function (string $file) use ($script): string {
        return $script === $file ? ' active' : '';
    };
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . p360hr_h($title) . ' | منابع انسانی</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
    echo '<link rel="stylesheet" href="../assets/css/p360-hr-ui.css">';
    echo '<link rel="stylesheet" href="../assets/css/p360-jalali-picker.css">';
    echo '<script src="../assets/js/p360-jalali-picker.js" defer></script>';
    echo '</head><body><div class="m360-app-shell">';
    echo '<aside class="m360-sidebar"><div class="m360-sidebar-brand"><h2>کارتابل پرسنل</h2><small>ماهین 360°</small></div>';
    echo '<a class="m360-sidebar-link' . $navActive('my-cartable.php') . '" href="my-cartable.php">میز کار من</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-password.php') . '" href="my-password.php">تغییر رمز عبور</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-dossier.php') . '" href="my-dossier.php">پرونده پرسنلی</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-requests.php') . '" href="my-requests.php">درخواست‌های من</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-attendance.php') . '" href="my-attendance.php">حضور و غیاب من</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-payslips.php') . '" href="my-payslips.php">فیش‌های من</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-contracts.php') . '" href="my-contracts.php">قراردادهای من</a>';
    echo '<a class="m360-sidebar-link' . $navActive('my-benefits.php') . '" href="my-benefits.php">عیدی و مزایا</a>';
    if (p360hr_can_manage_personnel()) {
        echo '<div class="m360-sidebar-section">مدیریت منابع انسانی</div>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-personnel-list.php') . '" href="hr-personnel-list.php">مدیریت پرسنل</a>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-personnel-form.php') . '" href="hr-personnel-form.php">فرم اطلاعات پرسنلی</a>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-contract-register.php') . '" href="hr-contract-register.php">ثبت قرارداد پرسنلی</a>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-contract-list.php') . '" href="hr-contract-list.php">فهرست قراردادها</a>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-contract-reminders.php') . '" href="hr-contract-reminders.php">هشدارهای تمدید</a>';
        echo '<a class="m360-sidebar-link' . $navActive('hr-employer-profile.php') . '" href="hr-employer-profile.php">پروفایل کارفرما</a>';
    }
    echo '<div class="m360-sidebar-foot"><span>' . $name . '</span><a href="../staff-logout.php">خروج</a></div></aside>';
    echo '<main class="m360-main">';
    echo '<div class="p360hr-page-head"><h1>' . p360hr_h($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="p360hr-sub">' . p360hr_h($subtitle) . '</p>';
    }
    echo '</div>';
}

function p360hr_layout_end(): void
{
    echo '</main></div></body></html>';
}

/** Styled 403 — does not weaken authorization; caller must set HTTP status first or this sets it. */
function p360hr_forbidden_page(string $messageFa = 'شما مجوز مشاهده این صفحه یا قرارداد را ندارید.'): void
{
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
    } else {
        http_response_code(403);
    }
    p360hr_layout_start('دسترسی غیرمجاز');
    echo '<div class="p360hr-error-wrap"><div class="p360hr-error-card">';
    echo '<h1>دسترسی غیرمجاز</h1>';
    echo '<p>' . p360hr_h($messageFa) . '</p>';
    echo '<div class="p360hr-error-actions">';
    echo '<a class="m360-btn m360-btn-primary" href="my-cartable.php">بازگشت به میز کار من</a>';
    echo '<a class="m360-btn m360-btn-secondary" href="javascript:history.back()">بازگشت به صفحه قبل</a>';
    echo '</div></div></div>';
    p360hr_layout_end();
    exit;
}

function p360hr_change_password(int $userId, string $current, string $new, string $confirm): array
{
    if ($new === '' || mb_strlen($new) < 8) {
        return ['ok' => false, 'message' => 'رمز جدید باید حداقل ۸ کاراکتر باشد.'];
    }
    if ($new !== $confirm) {
        return ['ok' => false, 'message' => 'تکرار رمز جدید مطابقت ندارد.'];
    }
    if ($new === P360HR_TEMP_PASSWORD_POLICY) {
        return ['ok' => false, 'message' => 'استفاده از رمز موقت به‌عنوان رمز دائمی مجاز نیست.'];
    }
    $conn = p360hr_odbc();
    $stmt = @odbc_prepare($conn, 'SELECT TOP 1 password_hash FROM dbo.core_users WHERE user_id=?');
    if ($stmt === false || !@odbc_execute($stmt, [$userId])) {
        return ['ok' => false, 'message' => 'خطا در خواندن حساب کاربری.'];
    }
    $row = odbc_fetch_array($stmt);
    if (!is_array($row)) {
        return ['ok' => false, 'message' => 'حساب کاربری یافت نشد.'];
    }
    $hash = (string)($row['password_hash'] ?? $row['PASSWORD_HASH'] ?? '');
    if ($hash === '' || !password_verify($current, $hash)) {
        return ['ok' => false, 'message' => 'رمز فعلی نادرست است.'];
    }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = @odbc_prepare($conn, 'UPDATE dbo.core_users SET password_hash=?, must_change_password=0, password_changed_at=SYSUTCDATETIME(), password_reset_required_at=NULL, updated_at=SYSUTCDATETIME() WHERE user_id=?');
    if ($upd === false || !@odbc_execute($upd, [$newHash, $userId])) {
        return ['ok' => false, 'message' => 'به‌روزرسانی رمز انجام نشد.'];
    }
    return ['ok' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد.'];
}

function p360hr_admin_reset_password(int $actorUserId, int $targetUserId, ?int $employeeId, string $reasonFa): array
{
    $reasonFa = trim($reasonFa);
    if ($reasonFa === '' || mb_strlen($reasonFa) < 5) {
        return ['ok' => false, 'message' => 'ثبت دلیل فارسی بازنشانی الزامی است.'];
    }
    if (!p360hr_can_manage_personnel()) {
        return ['ok' => false, 'message' => 'اجازه بازنشانی رمز را ندارید.'];
    }
    $conn = p360hr_odbc();
    $hash = password_hash(P360HR_TEMP_PASSWORD_POLICY, PASSWORD_DEFAULT);
    $upd = @odbc_prepare($conn, 'UPDATE dbo.core_users SET password_hash=?, must_change_password=1, password_reset_required_at=SYSUTCDATETIME(), password_reset_by_user_id=?, updated_at=SYSUTCDATETIME() WHERE user_id=?');
    if ($upd === false || !@odbc_execute($upd, [$hash, $actorUserId, $targetUserId])) {
        return ['ok' => false, 'message' => 'بازنشانی انجام نشد.'];
    }
    $ins = @odbc_prepare($conn, 'INSERT INTO dbo.p360_password_reset_audit (target_user_id, target_employee_id, actor_user_id, reason_fa) VALUES (?,?,?,?)');
    if ($ins !== false) {
        @odbc_execute($ins, [$targetUserId, $employeeId, $actorUserId, $reasonFa]);
    }
    return ['ok' => true, 'message' => 'رمز موقت تنظیم شد و تغییر اجباری فعال گردید.'];
}
