<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
function p360_session_start(): void {
    $name = (string)(p360_config()['app']['session_name'] ?? 'P360SESSID');
    if (session_status() !== PHP_SESSION_ACTIVE) { session_name($name); session_start(); }
}
function p360_current_user(): ?array {
    p360_session_start();
    $u = $_SESSION['p360_user'] ?? null;
    return is_array($u) ? $u : null;
}
function p360_require_login(): void {
    if (p360_current_user() === null) { header('Location: login.php'); exit; }
}
function p360_logout(): void {
    p360_session_start(); $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}
function p360_login(string $username, string $password): array {
    $username = trim($username);
    if ($username === '' || $password === '') return ['ok' => false, 'message' => 'نام کاربری و رمز عبور الزامی است.'];
    $conn = p360_db();
    $row = p360_one($conn, 'SELECT TOP 1 user_id, username, display_name, is_active, password_hash, role_code FROM dbo.p360_users WHERE username=?', [$username]);
    if (!$row || (int)$row['is_active'] !== 1) return ['ok' => false, 'message' => 'کاربر یافت نشد یا غیرفعال است.'];
    if (!password_verify($password, (string)$row['password_hash'])) return ['ok' => false, 'message' => 'رمز عبور نادرست است.'];
    p360_session_start();
    $_SESSION['p360_user'] = [
        'user_id' => (int)$row['user_id'],
        'username' => (string)$row['username'],
        'display_name' => (string)($row['display_name'] ?? $row['username']),
        'role_code' => (string)($row['role_code'] ?? 'OWNER_ADMIN'),
    ];
    return ['ok' => true, 'message' => 'ورود موفق.'];
}
function p360_ensure_amir(string $plainPassword): array {
    $conn = p360_db();
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $ex = p360_one($conn, 'SELECT TOP 1 user_id FROM dbo.p360_users WHERE username=N\'amir\'', []);
    if ($ex) {
        p360_exec($conn, 'UPDATE dbo.p360_users SET password_hash=?, role_code=N\'OWNER_ADMIN\', is_active=1, display_name=N\'Amir Owner\' WHERE username=N\'amir\'', [$hash]);
        return ['ok' => true, 'created' => false, 'user_id' => (int)$ex['user_id']];
    }
    p360_exec($conn, 'INSERT INTO dbo.p360_users (username, display_name, password_hash, role_code, is_active) VALUES (N\'amir\', N\'Amir Owner\', ?, N\'OWNER_ADMIN\', 1)', [$hash]);
    $id = (int)(p360_scalar($conn, 'SELECT TOP 1 user_id FROM dbo.p360_users WHERE username=N\'amir\'', []) ?? 0);
    return ['ok' => true, 'created' => true, 'user_id' => $id];
}