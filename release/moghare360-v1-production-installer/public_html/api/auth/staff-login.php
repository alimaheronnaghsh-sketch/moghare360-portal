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
$endpoint = '/api/auth/staff-login';

if ($username === '' || $password === '') {
    mogh_api_fail('نام کاربری و رمز عبور الزامی است.', 422);
}

$conn = mogh_tenant_db_connect();

try {
    $sql = "SELECT u.user_id, u.username, u.password_hash, u.full_name, u.is_login_enabled, u.lifecycle_state, u.is_system_owner
            FROM dbo.core_users u
            INNER JOIN dbo.erp_company_users cu ON cu.user_id = u.user_id AND cu.company_id = ? AND cu.is_active = 1
            WHERE u.username = ?";
    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$tenant['company_id'], $username])) {
        throw new RuntimeException('auth_query_failed');
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 401, 'user_not_found');
        mogh_api_fail('نام کاربری یا رمز عبور نادرست است.', 401);
    }

    if (empty($row['is_login_enabled']) || (string)($row['lifecycle_state'] ?? '') !== 'ACTIVE') {
        mogh_api_fail('حساب کاربری فعال نیست.', 403);
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

    mogh_saas_require_file('erp-csrf.php');
    $csrf = erp_csrf_create_token('staff_login');

    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 200, 'staff_login_ok');
    mogh_api_ok('ورود پرسنل موفق بود.', [
        'user_id' => (int)$row['user_id'],
        'username' => (string)$row['username'],
        'full_name' => (string)$row['full_name'],
        'company_id' => $tenant['company_id'],
        'session_token' => session_id(),
        'csrf_token' => $csrf,
    ]);
} catch (Throwable) {
    mogh_api_fail('ورود ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
