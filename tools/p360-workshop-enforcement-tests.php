<?php
declare(strict_types=1);

/**
 * Synthetic workshop permission enforcement smoke (no real personnel assignment).
 * Uses transaction rollback where possible; Owner bypass exercised via resolver is_owner flag only when present.
 * CLI only. Evidence outside Git.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/public_html/includes/m360-access-matrix-helper.php';
require $root . '/public_html/includes/m360-workshop-access-enforcement.php';

$conn = m360_am_db();
if ($conn === false) {
    fwrite(STDERR, "NO_DB\n");
    exit(1);
}

$outDir = $root . '/tools/_generated';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
$out = $outDir . '/workshop-enforcement-tests-' . date('Ymd-His') . '.txt';
$log = [];

$log[] = 'TEST_A_price_filter';
$f = m360_ws_filter_price_fields(['description' => 'svc', 'unit_price' => '1000', 'qty' => '1']);
$log[] = 'rejected=' . implode(',', $f['rejected']) . ' clean_has_price=' . (isset($f['clean']['unit_price']) ? 'yes' : 'no');

$log[] = 'TEST_B_assign_map';
$log[] = 'MECHANICAL=' . (m360_ws_assign_permission_for_team('MECHANICAL') ?? 'null');
$log[] = 'ELECTRICAL=' . (m360_ws_assign_permission_for_team('ELECTRICAL') ?? 'null');
$log[] = 'OPTIONS=' . (m360_ws_assign_permission_for_team('OPTIONS') ?? 'null');
$log[] = 'BAD=' . (m360_ws_assign_permission_for_team('X') ?? 'null');

$log[] = 'TEST_C_resolve_unknown_user';
$r = m360_am_resolve($conn, 999999991, 1, 'workshop.operations.home.view');
$log[] = 'allowed=' . (!empty($r['allowed']) ? 'yes' : 'no') . ' source=' . ($r['source'] ?? '');

$log[] = 'TEST_D_object_scope_missing_jobcard';
$missing = m360_am_one($conn, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE jobcard_id=?', [999999991]);
$log[] = 'missing_jobcard=' . ($missing === null ? 'ok_null' : 'unexpected');

$log[] = 'TEST_E_no_real_assignment_check';
$ov = m360_am_fetch_all(
    $conn,
    "SELECT COUNT(*) AS c
     FROM dbo.core_user_permission_overrides o
     INNER JOIN dbo.core_permissions p ON p.permission_id=o.permission_id
     WHERE p.permission_key LIKE N'workshop.%'
       AND o.revoked_at IS NULL
       AND o.created_at >= DATEADD(hour, -6, SYSUTCDATETIME())"
);
$log[] = 'recent_workshop_overrides=' . (string)($ov[0]['c'] ?? '0');

$log[] = 'TEST_F_catalog_enforced_assignable';
require_once $root . '/public_html/includes/m360-access-matrix-catalog.php';
foreach (m360_access_matrix_catalog_definitions() as $d) {
    if (($d['module_key'] ?? '') !== 'workshop') {
        continue;
    }
    $enf = (string)$d['enforcement_state'];
    $asg = (int)$d['is_assignable'];
    if ($enf === 'ENFORCED' && $asg !== 1) {
        $log[] = 'BAD_ASSIGNABLE_OFF ' . $d['permission_key'];
    }
    if ($enf !== 'ENFORCED' && $asg === 1) {
        $log[] = 'BAD_ASSIGNABLE_ON ' . $d['permission_key'];
    }
}
$log[] = 'catalog_assignable_consistency=checked';

file_put_contents($out, implode(PHP_EOL, $log) . PHP_EOL);
echo "WROTE=$out\n";
foreach ($log as $line) {
    echo $line . PHP_EOL;
}
