<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI-only\n";
    exit(1);
}
$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/includes/erp-config-loader.php';
require_once $repoRoot . '/public_html/includes/m360-access-matrix-catalog.php';

$c = erp_load_config()['database'];
$server = trim((string)$c['server']);
$conn = false;
foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $d) {
    $dsn = "Driver={{$d}};Server={$server};Database=moghare360_ERP;";
    if (!empty($c['trusted_connection'])) {
        $dsn .= 'Trusted_Connection=Yes;';
    }
    if ($d === 'ODBC Driver 18 for SQL Server') {
        $dsn .= 'TrustServerCertificate=Yes;';
    }
    $user = !empty($c['trusted_connection']) ? '' : (string)$c['username'];
    $pass = !empty($c['trusted_connection']) ? '' : (string)$c['password'];
    $conn = @odbc_connect($dsn, $user, $pass);
    if ($conn !== false) {
        break;
    }
}
if ($conn === false) {
    fwrite(STDERR, "connect failed\n");
    exit(1);
}
$one = static function ($conn, string $sql): int {
    $st = odbc_prepare($conn, $sql);
    odbc_execute($st);
    $r = odbc_fetch_array($st);
    if (!is_array($r)) {
        return -1;
    }
    foreach ($r as $v) {
        return (int)$v;
    }
    return -1;
};

echo 'db_workshop=' . $one($conn, "SELECT COUNT(*) FROM dbo.core_permissions WHERE module_key=N'workshop'") . "\n";
echo 'db_legacy=' . $one($conn, "SELECT COUNT(*) FROM dbo.core_permissions WHERE module_key=N'workshop_legacy'") . "\n";
echo 'db_ws_assignable=' . $one($conn, "SELECT COUNT(*) FROM dbo.core_permissions WHERE module_key=N'workshop' AND is_assignable=1 AND enforcement_state=N'ENFORCED'") . "\n";
echo 'db_ws_notyet=' . $one($conn, "SELECT COUNT(*) FROM dbo.core_permissions WHERE module_key=N'workshop' AND enforcement_state=N'NOT_YET_ENFORCED'") . "\n";
echo 'db_consumable_locked=' . $one($conn, "SELECT COUNT(*) FROM dbo.core_permissions WHERE permission_key LIKE N'workshop.internal_consumable.%' AND is_assignable=0") . "\n";
echo 'db_perm_total=' . $one($conn, 'SELECT COUNT(*) FROM dbo.core_permissions') . "\n";

require_once $repoRoot . '/public_html/includes/m360-access-matrix-catalog.php';
// Avoid re-including stats file (redeclare). Inline catalog counts:
$d = m360_access_matrix_catalog_definitions();
$w = array_filter($d, static fn($r) => $r['module_key'] === 'workshop');
$l = array_filter($d, static fn($r) => $r['module_key'] === 'workshop_legacy');
echo 'catalog_workshop=' . count($w) . "\n";
echo 'catalog_legacy=' . count($l) . "\n";
