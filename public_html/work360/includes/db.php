<?php
declare(strict_types=1);

function work360_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $private = 'C:\\xampp\\moghare360-private\\work360-config.php';
    $example = __DIR__ . DIRECTORY_SEPARATOR . 'work360-config.example.php';
    if (is_file($private)) {
        $cfg = require $private;
    } elseif (is_file($example)) {
        $cfg = require $example;
    } else {
        throw new RuntimeException('Work360 config missing.');
    }
    return $cfg;
}

function work360_db()
{
    static $conn = null;
    if (is_resource($conn)) {
        return $conn;
    }
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC extension required.');
    }
    $db = work360_config()['db'];
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
    throw new RuntimeException('Work360 database connection failed.');
}

function work360_exec($conn, string $sql, array $params = [])
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    return @odbc_execute($stmt, $params);
}

function work360_rows($conn, string $sql, array $params = []): array
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while ($r = odbc_fetch_array($stmt)) {
        $norm = [];
        foreach ($r as $k => $v) {
            $norm[strtolower((string)$k)] = is_string($v) ? $v : $v;
        }
        $rows[] = $norm;
    }
    return $rows;
}

function work360_one($conn, string $sql, array $params = []): ?array
{
    $rows = work360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function work360_scalar($conn, string $sql, array $params = [])
{
    $row = work360_one($conn, $sql, $params);
    if (!$row) {
        return null;
    }
    return array_values($row)[0] ?? null;
}
