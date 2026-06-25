<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$tenant = mogh_tenant_resolve_from_request();
$username = mogh_api_sanitize_string($body['username'] ?? '', 80);
$password = (string)($body['password'] ?? '');
$endpoint = '/api/auth/owner-login';

if ($username === '' || $password === '') {
    mogh_api_fail('نام کاربری و رمز عبور الزامی است.', 422);
}

$conn = mogh_tenant_db_connect();

try {
    $sql = "SELECT user_id, username, password_hash, full_name, is_login_enabled, lifecycle_state, is_system_owner
            FROM dbo.core_users WHERE username = ? AND is_system_owner = 1";
    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$username])) {
        throw new RuntimeException('owner_query_failed');
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 401, 'owner_not_found');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    if (empty($row['is_login_enabled']) || (string)($row['lifecycle_state'] ?? '') !== 'ACTIVE') {
        mogh_api_fail('حساب مالک فعال نیست.', 403);
    }

    $hash = (string)($row['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 401, 'bad_password');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['erp_user_id'] = (int)$row['user_id'];
    $_SESSION['erp_username'] = (string)$row['username'];
    $_SESSION['erp_company_id'] = $tenant['company_id'];
    $_SESSION['erp_is_owner'] = 1;

    mogh_saas_require_file('erp-csrf.php');
    $csrf = erp_csrf_create_token('owner_login');

    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 200, 'owner_login_ok');
    mogh_api_ok('ورود مالک موفق بود.', [
        'user_id' => (int)$row['user_id'],
        'username' => (string)$row['username'],
        'full_name' => (string)$row['full_name'],
        'company_id' => $tenant['company_id'],
        'session_token' => session_id(),
        'csrf_token' => $csrf,
    ]);
} catch (Throwable) {
    mogh_api_fail('ورود مالک ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
