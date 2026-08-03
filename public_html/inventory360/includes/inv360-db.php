<?php
declare(strict_types=1);

function inv360_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $private = 'C:\\xampp\\moghare360-private\\inventory360-config.php';
    $example = __DIR__ . DIRECTORY_SEPARATOR . 'inv360-config.example.php';
    if (is_file($private)) {
        $cfg = require $private;
    } elseif (is_file($example)) {
        $cfg = require $example;
    } else {
        throw new RuntimeException('Inventory360 config missing.');
    }
    return $cfg;
}

function inv360_db()
{
    static $conn = null;
    if (is_resource($conn)) {
        return $conn;
    }
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC extension required.');
    }
    $db = inv360_config()['db'];
    $server = (string)$db['server'];
    $name = (string)$db['database'];
    $trusted = !empty($db['trusted']);
    $user = (string)($db['username'] ?? '');
    $pass = (string)($db['password'] ?? '');
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $driver) {
        $dsn = 'Driver={' . $driver . '};Server=' . $server . ';Database=' . $name . ';';
        if ($trusted) {
            $dsn .= 'Trusted_Connection=Yes;';
        }
        if ($driver === 'ODBC Driver 18 for SQL Server') {
            $dsn .= 'TrustServerCertificate=Yes;';
        }
        $conn = @odbc_connect($dsn, $trusted ? '' : $user, $trusted ? '' : $pass);
        if ($conn !== false) {
            return $conn;
        }
    }
    throw new RuntimeException('Inventory360 database connection failed.');
}

function inv360_exec($conn, string $sql, array $params = [])
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    if (!@odbc_execute($stmt, $params)) {
        return false;
    }
    return $stmt;
}

function inv360_rows($conn, string $sql, array $params = []): array
{
    $stmt = inv360_exec($conn, $sql, $params);
    if ($stmt === false) {
        return [];
    }
    $rows = [];
    while ($row = odbc_fetch_array($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

function inv360_one($conn, string $sql, array $params = []): ?array
{
    $rows = inv360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function inv360_scalar($conn, string $sql, array $params = []): ?string
{
    $stmt = inv360_exec($conn, $sql, $params);
    if ($stmt === false || !odbc_fetch_row($stmt)) {
        return null;
    }
    $v = odbc_result($stmt, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function inv360_table_exists($conn, string $table): bool
{
    $n = inv360_scalar(
        $conn,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $table]
    );
    return ((int)$n) > 0;
}

function inv360_column_exists($conn, string $table, string $column): bool
{
    $n = inv360_scalar(
        $conn,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        ['dbo', $table, $column]
    );
    return ((int)$n) > 0;
}
