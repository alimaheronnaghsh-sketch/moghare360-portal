<?php
declare(strict_types=1);

/**
 * Access matrix post-migrate validation + UAT personnel snapshot (CLI).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/includes/erp-config-loader.php';
require_once $repoRoot . '/public_html/includes/m360-access-matrix-helper.php';

$c = erp_load_config()['database'];
if (trim((string)$c['name']) !== 'moghare360_ERP') {
    throw new RuntimeException('Refuse non-canonical database');
}
$server = trim((string)$c['server']);
$trusted = (bool)$c['trusted_connection'];
$user = $trusted ? '' : (string)$c['username'];
$pass = $trusted ? '' : (string)$c['password'];
$conn = false;
foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $d) {
    $dsn = "Driver={{$d}};Server={$server};Database=moghare360_ERP;";
    if ($trusted) {
        $dsn .= 'Trusted_Connection=Yes;';
    }
    if ($d === 'ODBC Driver 18 for SQL Server') {
        $dsn .= 'TrustServerCertificate=Yes;';
    }
    $conn = @odbc_connect($dsn, $user, $pass);
    if ($conn !== false) {
        break;
    }
}
if ($conn === false) {
    throw new RuntimeException('connect failed');
}

$one = static function ($conn, string $sql, array $p = []): ?array {
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $p)) {
        return null;
    }
    $r = odbc_fetch_array($st);
    if (!is_array($r)) {
        return null;
    }
    $n = [];
    foreach ($r as $k => $v) {
        $n[strtolower((string)$k)] = $v;
    }
    return $n;
};

$checks = [];
$checks['perm_unique'] = $one($conn, 'SELECT COUNT(*) c FROM (SELECT permission_key FROM dbo.core_permissions GROUP BY permission_key HAVING COUNT(*)>1) x');
$checks['active_override_dup'] = $one($conn, 'SELECT COUNT(*) c FROM (SELECT company_id,user_id,permission_id FROM dbo.core_user_permission_overrides WHERE revoked_at IS NULL GROUP BY company_id,user_id,permission_id HAVING COUNT(*)>1) x');
$checks['orphan_role_perm'] = $one($conn, 'SELECT COUNT(*) c FROM dbo.core_role_permissions rp LEFT JOIN dbo.core_permissions p ON p.permission_id=rp.permission_id WHERE p.permission_id IS NULL');
$checks['base_count'] = $one($conn, 'SELECT COUNT(*) c FROM dbo.core_permissions WHERE is_base_self_service=1 AND is_active=1');
$checks['perm_total'] = $one($conn, 'SELECT COUNT(*) c FROM dbo.core_permissions WHERE is_active=1');
$checks['route_map'] = $one($conn, 'SELECT COUNT(*) c FROM dbo.core_access_route_map WHERE is_active=1');
$checks['pending_not_effective'] = $one($conn, "SELECT COUNT(*) c FROM dbo.core_access_matrix_pending WHERE status=N'SUBMITTED'");
$checks['personnel_rows'] = $one($conn, "SELECT COUNT(*) c FROM dbo.p360_employees e INNER JOIN dbo.core_users u ON u.user_id=e.core_user_id INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1 WHERE e.employee_code LIKE N'M360-%' AND u.lifecycle_state=N'ACTIVE'");

echo "SQL_VALIDATION\n";
foreach ($checks as $k => $row) {
    echo $k . '=' . (int)($row['c'] ?? -1) . "\n";
}

$people = m360_am_personnel_rows($conn, []);
$outDir = $repoRoot . '/tools';
$lines = ["personnel_code\tname\tunit\ttitle\troles\tbase_ok\tfin_keys\thr_admin_keys\tlogin_ready\n"];
foreach ($people as $p) {
    $uid = (int)$p['user_id'];
    $cid = (int)($p['company_id'] ?? 1);
    $baseOk = m360_am_effective_can($conn, $uid, $cid, 'hr.self.cartable.VIEW') ? 'yes' : 'no';
    $fin = [];
    $hr = [];
    foreach (['finance.reports.VIEW', 'finance.payroll.APPROVE', 'inventory.financial.VIEW', 'purchase.financial.VIEW'] as $k) {
        if (m360_am_effective_can($conn, $uid, $cid, $k)) {
            $fin[] = $k;
        }
    }
    foreach (['hr.admin.personnel.VIEW', 'hr.admin.contracts.VIEW', 'hr.payroll.admin.VIEW'] as $k) {
        if (m360_am_effective_can($conn, $uid, $cid, $k)) {
            $hr[] = $k;
        }
    }
    $login = ((int)($p['is_login_enabled'] ?? 0) === 1) ? 'yes' : 'no';
    $lines[] = implode("\t", [
        (string)$p['employee_code'],
        (string)$p['full_name'],
        (string)($p['unit_name'] ?? ''),
        (string)($p['job_title'] ?? ''),
        (string)($p['role_labels'] ?? ''),
        $baseOk,
        implode(',', $fin),
        implode(',', $hr),
        $login,
    ]) . "\n";
}
$file = $outDir . '/access-matrix-uat-snapshot.tsv';
file_put_contents($file, implode('', $lines));
echo 'UAT_ROWS=' . count($people) . "\n";
echo "UAT_FILE={$file}\n";
echo "VALIDATION_OK\n";
