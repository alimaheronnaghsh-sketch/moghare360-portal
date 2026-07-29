<?php
declare(strict_types=1);

function fin360_db()
{
    static $conn = null;
    if (is_resource($conn)) {
        return $conn;
    }
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC required for Finance360.');
    }
    $server = 'localhost\\SQLEXPRESS';
    $name = 'moghare360_ERP';
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $driver) {
        $dsn = 'Driver={' . $driver . '};Server=' . $server . ';Database=' . $name . ';CharacterSet=UTF-8;Trusted_Connection=Yes;';
        if ($driver === 'ODBC Driver 18 for SQL Server') {
            $dsn .= 'TrustServerCertificate=Yes;';
        }
        $conn = @odbc_connect($dsn, '', '');
        if ($conn !== false) {
            return $conn;
        }
    }
    throw new RuntimeException('Finance360 DB connection failed.');
}

function fin360_exec($conn, string $sql, array $params = []): bool
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    return (bool)@odbc_execute($stmt, $params);
}

function fin360_rows($conn, string $sql, array $params = []): array
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while ($r = odbc_fetch_array($stmt)) {
        $norm = [];
        foreach ($r as $k => $v) {
            $norm[strtolower((string)$k)] = $v;
        }
        $rows[] = $norm;
    }
    return $rows;
}

function fin360_one($conn, string $sql, array $params = []): ?array
{
    $rows = fin360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function fin360_scalar($conn, string $sql, array $params = [])
{
    $row = fin360_one($conn, $sql, $params);
    if (!$row) {
        return null;
    }
    return array_values($row)[0] ?? null;
}

function fin360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fin360_money($n): string
{
    return number_format((float)$n, 0, '.', ',');
}

function fin360_actor(): array
{
    $name = 'local_owner';
    $dev = true;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    foreach (['staff_username', 'username', 'user_name', 'display_name'] as $k) {
        if (!empty($_SESSION[$k])) {
            $name = (string)$_SESSION[$k];
            $dev = false;
            break;
        }
    }
    if (!empty($_SESSION['staff_user']) && is_array($_SESSION['staff_user'])) {
        $su = $_SESSION['staff_user'];
        $name = (string)($su['username'] ?? $su['display_name'] ?? $name);
        $dev = false;
    }
    return ['actor' => $name, 'dev_mode' => $dev];
}

function fin360_csrf_boot(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['fin360_csrf'])) {
        $_SESSION['fin360_csrf'] = bin2hex(random_bytes(16));
    }
}

function fin360_csrf_token(): string
{
    fin360_csrf_boot();
    return (string)$_SESSION['fin360_csrf'];
}

function fin360_csrf_field(): string
{
    return '<input type="hidden" name="fin360_csrf" value="' . fin360_h(fin360_csrf_token()) . '">';
}

function fin360_csrf_require(): void
{
    fin360_csrf_boot();
    $expected = (string)($_SESSION['fin360_csrf'] ?? '');
    $got = (string)($_POST['fin360_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $got)) {
        http_response_code(403);
        echo 'CSRF نامعتبر.';
        exit;
    }
}

function fin360_next_code($conn, string $prefix, string $table, string $col): string
{
    $n = (int)(fin360_scalar($conn, "SELECT ISNULL(MAX($col),0) FROM dbo.$table", []) ?? 0);
    // Prefer identity-ish sequence from count
    $c = (int)(fin360_scalar($conn, "SELECT COUNT(*)+1 FROM dbo.$table", []) ?? 1);
    return $prefix . '-' . str_pad((string)$c, 5, '0', STR_PAD_LEFT);
}

function fin360_audit($conn, string $action, string $entity, ?string $entityId, $before, $after, ?string $reason = null, ?string $approver = null): void
{
    $actor = fin360_actor();
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $page = (string)($_SERVER['PHP_SELF'] ?? 'erp-final-invoice-board.php');
    fin360_exec(
        $conn,
        'INSERT INTO dbo.fin360_audit_log (actor_user, action_code, entity_name, entity_id, before_json, after_json, reason, ip_address, device_info, source_page, approver_user) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [
            $actor['actor'],
            $action,
            $entity,
            $entityId,
            $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            $reason,
            $ip,
            $ua,
            $page,
            $approver,
        ]
    );
}
