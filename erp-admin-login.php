<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1A Admin Login Prototype
 *
 * Independent ERP Admin Login for Platform Owner only.
 * SELECT only. No audit write. No portal login dependency.
 */

const ERP_LOGIN_ALLOWED_USERNAME = 'mahin.paradigm.owner';

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/erp-config-loader.php';

function erp_login_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_login_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string)$value));

    return $normalized === '1' || $normalized === 'true';
}

/**
 * @return resource|false
 */
function erp_login_connect(array $config)
{
    $server = (string)$config['database']['server'];
    $database = (string)$config['database']['name'];

    $dsns = [
        'Driver={ODBC Driver 17 for SQL Server};Server=' . $server . ';Database=' . $database . ';Trusted_Connection=yes;',
        'Driver={ODBC Driver 18 for SQL Server};Server=' . $server . ';Database=' . $database . ';Trusted_Connection=yes;TrustServerCertificate=yes;',
    ];

    foreach ($dsns as $dsn) {
        $connection = @odbc_connect($dsn, '', '');
        if ($connection !== false) {
            return $connection;
        }
    }

    return false;
}

/**
 * @param resource $connection
 * @return array<string, string>|null
 */
function erp_login_fetch_user($connection, string $username): ?array
{
    $sql = 'SELECT user_id, username, full_name, password_hash, is_login_enabled, is_system_owner
            FROM dbo.core_users
            WHERE username = ?';

    $statement = @odbc_prepare($connection, $sql);
    if ($statement === false) {
        return null;
    }

    if (@odbc_execute($statement, [$username]) === false) {
        @odbc_free_result($statement);
        return null;
    }

    if (!@odbc_fetch_row($statement)) {
        @odbc_free_result($statement);
        return null;
    }

    $row = [
        'user_id' => (string)@odbc_result($statement, 1),
        'username' => (string)@odbc_result($statement, 2),
        'full_name' => (string)@odbc_result($statement, 3),
        'password_hash' => (string)@odbc_result($statement, 4),
        'is_login_enabled' => (string)@odbc_result($statement, 5),
        'is_system_owner' => (string)@odbc_result($statement, 6),
    ];

    @odbc_free_result($statement);

    return $row;
}

/**
 * @param resource $connection
 * @return list<string>
 */
function erp_login_fetch_role_keys($connection, int $userId): array
{
    $sql = 'SELECT r.role_key
            FROM dbo.core_user_roles ur
            INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
            WHERE ur.user_id = ? AND ur.revoked_at IS NULL
            ORDER BY r.sort_order, r.role_key';

    $statement = @odbc_prepare($connection, $sql);
    if ($statement === false) {
        return [];
    }

    if (@odbc_execute($statement, [$userId]) === false) {
        @odbc_free_result($statement);
        return [];
    }

    $roles = [];

    while (@odbc_fetch_row($statement)) {
        $roleKey = trim((string)@odbc_result($statement, 1));
        if ($roleKey !== '') {
            $roles[] = $roleKey;
        }
    }

    @odbc_free_result($statement);

    return $roles;
}

function erp_login_has_required_role(array $roleKeys): bool
{
    return in_array('owner', $roleKeys, true) || in_array('system_admin', $roleKeys, true);
}

$configError = '';
$loginError = '';
$loginSuccess = false;

try {
    $config = erp_load_config();
    ini_set('display_errors', $config['security']['display_errors_to_browser'] ? '1' : '0');
} catch (Throwable $e) {
    $configError = 'Configuration error. Please contact system administrator.';
}

if ($configError === '' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($configError === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim((string)($_POST['username'] ?? ''));
    $submittedPassword = (string)($_POST['password'] ?? '');

    if ($submittedUsername !== ERP_LOGIN_ALLOWED_USERNAME || $submittedPassword === '') {
        $loginError = 'Invalid login attempt.';
    } else {
        $connection = erp_login_connect($config);

        if ($connection === false) {
            $loginError = 'Invalid login attempt.';
        } else {
            $user = erp_login_fetch_user($connection, ERP_LOGIN_ALLOWED_USERNAME);

            if ($user === null) {
                $loginError = 'Invalid login attempt.';
            } else {
                $userId = (int)$user['user_id'];
                $passwordHash = $user['password_hash'];
                $roleKeys = erp_login_fetch_role_keys($connection, $userId);

                $isValid = erp_login_bool($user['is_login_enabled'])
                    && erp_login_bool($user['is_system_owner'])
                    && password_verify($submittedPassword, $passwordHash)
                    && erp_login_has_required_role($roleKeys);

                if (!$isValid) {
                    $loginError = 'Invalid login attempt.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['erp_user_id'] = $userId;
                    $_SESSION['erp_username'] = (string)$user['username'];
                    $_SESSION['erp_full_name'] = (string)$user['full_name'];
                    $_SESSION['erp_is_system_owner'] = true;
                    $_SESSION['erp_roles'] = $roleKeys;
                    $_SESSION['erp_login_time'] = time();
                    $_SESSION['erp_last_activity'] = time();
                    $_SESSION['erp_session_token'] = bin2hex(random_bytes(32));

                    $loginSuccess = true;
                }
            }

            @odbc_close($connection);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>ERP Admin Login Prototype</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; color: #1f2937; }
    .wrap { max-width: 420px; margin: 40px auto; }
    .card { background: #fff; border: 1px solid #d8dee4; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    h1 { margin: 0 0 8px; font-size: 1.25rem; }
    p { margin: 0 0 16px; color: #64748b; font-size: 0.92rem; }
    label { display: block; margin-bottom: 6px; font-size: 0.9rem; }
    input { width: 100%; box-sizing: border-box; padding: 10px 12px; margin-bottom: 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
    button { width: 100%; padding: 11px 12px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; font-size: 0.95rem; cursor: pointer; }
    .warn { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; }
    .error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }
    .success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-weight: bold; text-align: center; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="warn">LOCAL ERP ADMIN LOGIN PROTOTYPE — PLATFORM OWNER ONLY</div>

      <?php if ($configError !== ''): ?>
        <div class="error"><?= erp_login_h($configError) ?></div>
      <?php elseif ($loginSuccess): ?>
        <div class="success">ERP Admin Login OK</div>
      <?php else: ?>
        <h1>ورود ادمین ERP</h1>
        <p>نسخه آزمایشی — فقط مالک پلتفرم</p>

        <?php if ($loginError !== ''): ?>
          <div class="error"><?= erp_login_h($loginError) ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <label for="username">نام کاربری</label>
          <input id="username" name="username" type="text" autocomplete="username" required>

          <label for="password">رمز عبور</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>

          <button type="submit">ورود</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
