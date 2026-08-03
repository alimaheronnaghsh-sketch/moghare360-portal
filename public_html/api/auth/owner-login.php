<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_saas_require_file('erp-auth-context.php');

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$tenant = mogh_tenant_resolve_from_request();
$username = mogh_api_sanitize_string($body['username'] ?? '', 80);
$password = (string)($body['password'] ?? '');
$endpoint = '/api/auth/owner-login';
$requestedCompanyId = (int)($tenant['company_id'] ?? 0);

if ($username === '' || $password === '') {
    mogh_api_fail('نام کاربری و رمز عبور الزامی است.', 422);
}

if ($requestedCompanyId <= 0) {
    mogh_api_fail('شناسه شرکت معتبر نیست.', 422);
}

$conn = mogh_tenant_db_connect();

try {
    // 1–2. Resolve core user by username (password + active checks next).
    $sql = "SELECT user_id, username, password_hash, full_name, is_login_enabled, lifecycle_state, is_system_owner
            FROM dbo.core_users WHERE username = ?";
    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$username])) {
        throw new RuntimeException('owner_query_failed');
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        mogh_api_log_request($conn, $requestedCompanyId, $endpoint, 'POST', 401, 'owner_not_found');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    if (empty($row['is_login_enabled']) || (string)($row['lifecycle_state'] ?? '') !== 'ACTIVE') {
        mogh_api_fail('حساب مالک فعال نیست.', 403);
    }

    $hash = (string)($row['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        mogh_api_log_request($conn, $requestedCompanyId, $endpoint, 'POST', 401, 'bad_password');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    $userId = (int)($row['user_id'] ?? 0);
    if ($userId <= 0) {
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    // 3. Resolve active company membership for the request company (no first-row fallback).
    $memSql = "SELECT company_id, is_active
               FROM dbo.erp_company_users
               WHERE user_id = ? AND company_id = ? AND is_active = 1";
    $memStmt = odbc_prepare($conn, $memSql);
    if ($memStmt === false || !@odbc_execute($memStmt, [$userId, $requestedCompanyId])) {
        throw new RuntimeException('owner_membership_query_failed');
    }
    $memRow = odbc_fetch_array($memStmt);
    if ($memRow === false) {
        mogh_api_log_request($conn, $requestedCompanyId, $endpoint, 'POST', 401, 'owner_no_membership');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    $verifiedCompanyId = (int)($memRow['company_id'] ?? 0);
    if ($verifiedCompanyId <= 0 || $verifiedCompanyId !== $requestedCompanyId) {
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }
    if (empty($memRow['is_active'])) {
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    // 4. Owner authority only after membership is established.
    if (!erp_auth_context_bool_value($row['is_system_owner'] ?? false)) {
        mogh_api_log_request($conn, $verifiedCompanyId, $endpoint, 'POST', 401, 'not_system_owner');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    // 5–6. Normalized Central ERP session; company from verified membership.
    erp_auth_context_start(false);
    session_regenerate_id(true);
    $_SESSION['erp_user_id'] = $userId;
    $_SESSION['erp_username'] = (string)$row['username'];
    $_SESSION['erp_company_id'] = $verifiedCompanyId;
    $_SESSION['erp_is_owner'] = 1;
    erp_auth_establish_login_timestamps();

    mogh_saas_require_file('erp-csrf.php');
    $csrf = erp_csrf_create_token('owner_login');

    mogh_api_log_request($conn, $verifiedCompanyId, $endpoint, 'POST', 200, 'owner_login_ok');
    mogh_api_ok('ورود مالک موفق بود.', [
        'user_id' => $userId,
        'username' => (string)$row['username'],
        'full_name' => (string)$row['full_name'],
        'company_id' => $verifiedCompanyId,
        'session_token' => session_id(),
        'csrf_token' => $csrf,
        'redirect_url' => 'erp-product-home.php',
    ]);
} catch (Throwable) {
    mogh_api_fail('ورود مالک ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
