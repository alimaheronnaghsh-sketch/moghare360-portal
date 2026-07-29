<?php
declare(strict_types=1);

require_once __DIR__ . '/inv360-db.php';

function inv360_session_start(): void
{
    $name = (string)(inv360_config()['app']['session_name'] ?? 'INV360SESSID');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($name);
        session_start();
    }
}

function inv360_current_user(): ?array
{
    inv360_session_start();
    $u = $_SESSION['inv360_user'] ?? null;
    return is_array($u) ? $u : null;
}

function inv360_require_login(): void
{
    if (inv360_current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function inv360_logout(): void
{
    inv360_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}

function inv360_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'نام کاربری و رمز عبور الزامی است.'];
    }
    $conn = inv360_db();
    $row = inv360_one(
        $conn,
        'SELECT TOP 1 UserID, Username, DisplayName, IsActive, IsDeleted, AppPasswordHash, RoleCode
         FROM dbo.Users WHERE Username = ?',
        [$username]
    );
    if ($row === null || (int)($row['IsDeleted'] ?? 0) === 1 || (int)($row['IsActive'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'کاربر یافت نشد یا غیرفعال است.'];
    }
    $hash = (string)($row['AppPasswordHash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return ['ok' => false, 'message' => 'رمز عبور نادرست است.'];
    }
    inv360_session_start();
    $_SESSION['inv360_user'] = [
        'user_id' => (int)$row['UserID'],
        'username' => (string)$row['Username'],
        'display_name' => (string)($row['DisplayName'] ?? $row['Username']),
        'role_code' => (string)($row['RoleCode'] ?? 'OWNER_ADMIN'),
    ];
    return ['ok' => true, 'message' => 'ورود موفق.'];
}

function inv360_ensure_amir(string $plainPassword): array
{
    $conn = inv360_db();
    $existing = inv360_one($conn, 'SELECT TOP 1 UserID, Username FROM dbo.Users WHERE Username = ?', ['amir']);
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($existing) {
        inv360_exec(
            $conn,
            'UPDATE dbo.Users SET AppPasswordHash = ?, RoleCode = ?, IsActive = 1, IsDeleted = 0, DisplayName = COALESCE(NULLIF(DisplayName, \'\'), N\'Amir Owner\'), UpdatedAt = SYSUTCDATETIME() WHERE Username = ?',
            [$hash, 'OWNER_ADMIN', 'amir']
        );
        return ['ok' => true, 'created' => false, 'user_id' => (int)$existing['UserID']];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Users (Username, DisplayName, PasswordHash, PasswordSalt, IsActive, IsDeleted, MustChangePassword, CreatedAt, AppPasswordHash, RoleCode)
         VALUES (N\'amir\', N\'Amir Owner\', CONVERT(VARBINARY(64), REPLICATE(0x00, 64)), CONVERT(VARBINARY(32), REPLICATE(0x00, 32)), 1, 0, 0, SYSUTCDATETIME(), ?, N\'OWNER_ADMIN\')',
        [$hash]
    );
    if ($ok === false) {
        return ['ok' => false, 'created' => false, 'user_id' => 0, 'message' => 'ایجاد کاربر amir ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 UserID FROM dbo.Users WHERE Username = ?', ['amir']) ?? 0);
    return ['ok' => true, 'created' => true, 'user_id' => $id];
}
