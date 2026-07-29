<?php
declare(strict_types=1);
function p360_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $private = 'C:\\xampp\\moghare360-private\\peopleos360-config.php';
    $example = __DIR__ . DIRECTORY_SEPARATOR . 'p360-config.example.php';
    if (is_file($private)) $cfg = require $private;
    elseif (is_file($example)) $cfg = require $example;
    else throw new RuntimeException('PeopleOS360 config missing.');
    return $cfg;
}
function p360_db() {
    static $conn = null;
    if (is_resource($conn)) return $conn;
    if (!extension_loaded('odbc')) throw new RuntimeException('ODBC extension required.');
    $db = p360_config()['db'];
    $server = (string)$db['server'];
    $name = (string)$db['database'];
    $trusted = !empty($db['trusted']);
    $user = (string)($db['username'] ?? '');
    $pass = (string)($db['password'] ?? '');
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $driver) {
        $dsn = 'Driver={' . $driver . '};Server=' . $server . ';Database=' . $name . ';';
        if ($trusted) $dsn .= 'Trusted_Connection=Yes;';
        if ($driver === 'ODBC Driver 18 for SQL Server') $dsn .= 'TrustServerCertificate=Yes;';
        $conn = @odbc_connect($dsn, $trusted ? '' : $user, $trusted ? '' : $pass);
        if ($conn !== false) return $conn;
    }
    throw new RuntimeException('PeopleOS360 database connection failed.');
}
function p360_exec($conn, string $sql, array $params = []) {
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) return false;
    if (!@odbc_execute($stmt, $params)) return false;
    return $stmt;
}
function p360_rows($conn, string $sql, array $params = []): array {
    $stmt = p360_exec($conn, $sql, $params);
    if ($stmt === false) return [];
    $rows = [];
    while ($row = odbc_fetch_array($stmt)) $rows[] = $row;
    return $rows;
}
function p360_one($conn, string $sql, array $params = []): ?array {
    $rows = p360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}
function p360_scalar($conn, string $sql, array $params = []): ?string {
    $stmt = p360_exec($conn, $sql, $params);
    if ($stmt === false || !odbc_fetch_row($stmt)) return null;
    $v = odbc_result($stmt, 1);
    return $v === false || $v === null ? null : (string)$v;
}