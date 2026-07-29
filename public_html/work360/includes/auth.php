<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function work360_session_start(): void
{
    $name = (string)(work360_config()['app']['session_name'] ?? 'WORK360SESSID');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($name);
        session_start();
    }
}

function work360_current_user(): ?array
{
    work360_session_start();
    $u = $_SESSION['work360_user'] ?? null;
    return is_array($u) ? $u : null;
}

function work360_require_login(): void
{
    if (work360_current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function work360_logout(): void
{
    work360_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}

function work360_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'نام کاربری و رمز عبور الزامی است.'];
    }
    $conn = work360_db();
    $row = work360_one($conn, "SELECT TOP 1 u.user_id, u.username, u.full_name, u.is_active, u.password_hash, u.role_code, u.department_id, d.department_name_fa, d.department_code
        FROM dbo.work360_users u
        LEFT JOIN dbo.work360_departments d ON d.department_id = u.department_id
        WHERE u.username=?", [$username]);
    if (!$row || (int)$row['is_active'] !== 1) {
        return ['ok' => false, 'message' => 'کاربر یافت نشد یا غیرفعال است.'];
    }
    if (!password_verify($password, (string)$row['password_hash'])) {
        return ['ok' => false, 'message' => 'رمز عبور نادرست است.'];
    }
    work360_session_start();
    $_SESSION['work360_user'] = [
        'user_id' => (int)$row['user_id'],
        'username' => (string)$row['username'],
        'full_name' => (string)$row['full_name'],
        'role_code' => (string)$row['role_code'],
        'department_id' => $row['department_id'] !== null ? (int)$row['department_id'] : null,
        'department_name_fa' => (string)($row['department_name_fa'] ?? ''),
        'department_code' => (string)($row['department_code'] ?? ''),
    ];
    return ['ok' => true, 'message' => 'ورود موفق.'];
}

function work360_role(): string
{
    $u = work360_current_user();
    return strtoupper((string)($u['role_code'] ?? 'STAFF'));
}

function work360_is_owner(): bool { return work360_role() === 'OWNER'; }
function work360_is_manager(): bool { return in_array(work360_role(), ['OWNER', 'MANAGER'], true); }
function work360_is_supervisor(): bool { return in_array(work360_role(), ['OWNER', 'MANAGER', 'SUPERVISOR'], true); }

function work360_require_roles(array $roles): void
{
    work360_require_login();
    $role = work360_role();
    $ok = false;
    foreach ($roles as $r) {
        if (strtoupper((string)$r) === $role) { $ok = true; break; }
    }
    if (!$ok && work360_is_owner()) { return; }
    if (!$ok) {
        http_response_code(403);
        echo 'دسترسی مجاز نیست.';
        exit;
    }
}

function work360_csrf_token(): string
{
    work360_session_start();
    $key = (string)(work360_config()['app']['csrf_key'] ?? 'work360_csrf');
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION[$key];
}

function work360_csrf_field(): string
{
    $t = work360_h(work360_csrf_token());
    return '<input type="hidden" name="work360_csrf" value="' . $t . '">';
}

function work360_csrf_require(): void
{
    work360_session_start();
    $key = (string)(work360_config()['app']['csrf_key'] ?? 'work360_csrf');
    $expected = (string)($_SESSION[$key] ?? '');
    $got = (string)($_POST['work360_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $got)) {
        http_response_code(403);
        echo 'CSRF نامعتبر.';
        exit;
    }
}
