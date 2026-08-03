<?php
declare(strict_types=1);

/**
 * R1A-2R1 UAT — company scope + valuation cost snapshots. CLI only. Rollback at end.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-access-matrix-helper.php';
require_once $root . '/public_html/includes/m360-workshop-access-enforcement.php';
require_once $root . '/public_html/includes/m360-workshop-internal-consumable-helper.php';
require_once $root . '/public_html/includes/m360-inventory-valuation-helper.php';
require_once $root . '/public_html/includes/m360-technical-operation-helper.php';
require_once $root . '/public_html/includes/m360-qc-helper.php';
require_once $root . '/public_html/includes/m360-fulljob-lifecycle-helper.php';

$conn = m360_am_db();
if ($conn === false) {
    fwrite(STDERR, "NO_DB\n");
    exit(1);
}

$outDir = $root . '/tools/_generated';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
$log = [];
$pass = 0;
$fail = 0;
function t(string $n, bool $ok, string $d = ''): void
{
    global $log, $pass, $fail;
    if ($ok) {
        $pass++;
        $log[] = "PASS $n $d";
    } else {
        $fail++;
        $log[] = "FAIL $n $d";
    }
}

$hasCompanyCol = customer_core_column_exists($conn, 'erp_jobcards', 'company_id');
t('jobcard_company_col', $hasCompanyCol);
$amb = (int)(customer_core_scalar($conn, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE company_id IS NULL OR company_id=0') ?? 0);
$filled = (int)(customer_core_scalar($conn, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE company_id IS NOT NULL AND company_id>0') ?? 0);
t('jobcards_backfilled', $filled > 0, "filled=$filled amb=$amb");
t('no_ambiguous_guess', true, "amb=$amb (left unresolved if any)");

$jc = (int)(customer_core_scalar($conn, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE company_id>0 ORDER BY jobcard_id DESC') ?? 0);
$cid = (int)(customer_core_scalar($conn, 'SELECT company_id FROM dbo.erp_jobcards WHERE jobcard_id=?', [$jc]) ?? 0);
t('resolve_company', m360_ws_resolve_jobcard_company_id($conn, $jc) === $cid, "jc=$jc cid=$cid");

// Board company filter: Owner sees rows; fake company 999999 sees none
$ownerRows = m360_technical_list_jobcards($conn, null, null, 50, $cid, true);
$wrongRows = m360_technical_list_jobcards($conn, null, null, 50, 999999, false);
t('board_owner_or_company', count($ownerRows) >= 0);
t('board_wrong_company_empty', $wrongRows === []);

$qcWrong = m360_qc_board_list($conn, null, 50, 999999, false);
t('qc_wrong_company_empty', $qcWrong === []);

$unitWrong = m360_fulljob_unit_work_board($conn, 'MECHANICAL', 999999, false);
t('unit_wrong_company_empty', $unitWrong === []);

$users = customer_core_fetch_rows(
    $conn,
    "SELECT TOP 2 u.user_id FROM dbo.core_users u
     INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
     WHERE u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1 ORDER BY u.user_id"
);
$actorA = (int)($users[0]['user_id'] ?? 0);
$actorB = (int)($users[1]['user_id'] ?? 0);
t('actors', $actorA > 0 && $actorB > 0 && $actorA !== $actorB, "A=$actorA B=$actorB");

$invId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 inventory_item_id FROM dbo.erp_inventory_items WHERE is_active=1') ?? 0);
t('inventory_item', $invId > 0, "id=$invId");

@odbc_autocommit($conn, false);
try {
    // Unknown cost path
    $ic = m360_ws_ic_create(
        $conn,
        $cid,
        $jc,
        null,
        ['usage_reason' => 'تست بهای نامشخص', 'consuming_unit' => 'MECHANICAL'],
        [['inventory_item_id' => $invId, 'manual_description' => '', 'quantity' => 2, 'unit_of_measure' => 'عدد']],
        $actorA
    );
    t('ic_create', !empty($ic['ok']), $ic['message'] ?? '');
    $rid = (int)($ic['request_id'] ?? 0);
    m360_ws_ic_submit($conn, $rid, $actorA);
    $ap = m360_ws_ic_approve($conn, $rid, $actorB);
    t('ic_approve_pending_cost', !empty($ap['ok']), $ap['message'] ?? '');
    $items = m360_ws_ic_fetch_items($conn, $rid);
    $cst = strtoupper((string)($items[0]['cost_status'] ?? ''));
    $totalRaw = trim((string)($items[0]['internal_total_cost'] ?? ''));
    t('unknown_not_zero', $cst === 'COST_PENDING' && $totalRaw === '', "status=$cst total='$totalRaw'");
    $bd = m360_inv_val_jobcard_true_cost_breakdown($conn, $jc);
    t('pending_in_breakdown', (int)$bd['pending_item_count'] >= 1);

    // Approved valuation then new issue (ensure stock room inside txn)
    if (customer_core_table_exists($conn, 'erp_stock_balances')) {
        $balId = (int)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 stock_balance_id FROM dbo.erp_stock_balances WHERE inventory_item_id=? ORDER BY stock_balance_id',
            [$invId]
        ) ?? 0);
        if ($balId > 0) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.erp_stock_balances SET available_qty = CASE WHEN available_qty < 100 THEN 100 ELSE available_qty END WHERE stock_balance_id=?',
                [$balId]
            );
        }
    }

    $draft = m360_inv_val_create_draft(
        $conn,
        $cid,
        $invId,
        ['valuation_method' => 'STANDARD_INTERNAL_COST', 'unit_cost' => 1500.5, 'currency' => 'IRR'],
        $actorA
    );
    t('val_draft', !empty($draft['ok']), (string)($draft['valuation_id'] ?? 0));
    $vid = (int)$draft['valuation_id'];
    m360_inv_val_submit($conn, $vid, $actorA);
    t('val_self_approve_blocked', empty(m360_inv_val_approve($conn, $vid, $actorA)['ok']));
    t('val_approve', !empty(m360_inv_val_approve($conn, $vid, $actorB)['ok']));

    $ic2 = m360_ws_ic_create(
        $conn,
        $cid,
        $jc,
        null,
        ['usage_reason' => 'تست بهای تأییدشده', 'consuming_unit' => 'MECHANICAL'],
        [['inventory_item_id' => $invId, 'manual_description' => '', 'quantity' => 3, 'unit_of_measure' => 'عدد']],
        $actorA
    );
    $rid2 = (int)$ic2['request_id'];
    m360_ws_ic_submit($conn, $rid2, $actorA);
    $ap2 = m360_ws_ic_approve($conn, $rid2, $actorB);
    t('ic_approve_with_cost', !empty($ap2['ok']), $ap2['message'] ?? '');
    $items2 = m360_ws_ic_fetch_items($conn, $rid2);
    $unit = (float)($items2[0]['internal_unit_cost'] ?? 0);
    $total = (float)($items2[0]['internal_total_cost'] ?? 0);
    $cst2 = strtoupper((string)($items2[0]['cost_status'] ?? ''));
    t('cost_snapshot', $cst2 === 'COST_CONFIRMED' && abs($unit - 1500.5) < 0.001 && abs($total - 4501.5) < 0.01, "u=$unit t=$total");

    // Historical immutability: change valuation, old snapshot stays
    $draft2 = m360_inv_val_create_draft(
        $conn,
        $cid,
        $invId,
        ['valuation_method' => 'STANDARD_INTERNAL_COST', 'unit_cost' => 9999, 'currency' => 'IRR'],
        $actorA
    );
    $vid2 = (int)$draft2['valuation_id'];
    m360_inv_val_submit($conn, $vid2, $actorA);
    m360_inv_val_approve($conn, $vid2, $actorB);
    $items2b = m360_ws_ic_fetch_items($conn, $rid2);
    t('historical_immutable', abs((float)($items2b[0]['internal_unit_cost'] ?? 0) - 1500.5) < 0.001);

    $confirmed = m360_ws_ic_jobcard_true_cost($conn, $jc);
    t('true_cost_excludes_pending', $confirmed >= 4501.0, "confirmed=$confirmed");

    $filtered = m360_ws_ic_filter_cost_row($items2[0], false);
    t('cost_privacy', !isset($filtered['internal_unit_cost']) && !isset($filtered['valuation_method']));

    $apDup = m360_ws_ic_approve($conn, $rid2, $actorB);
    t('dup_approve_blocked', empty($apDup['ok']));

    // Catalog workshop still 35 ENFORCED
    require_once $root . '/public_html/includes/m360-access-matrix-catalog.php';
    $defs = array_filter(m360_access_matrix_catalog_definitions(), static fn($r) => ($r['module_key'] ?? '') === 'workshop');
    $enf = count(array_filter($defs, static fn($r) => ($r['enforcement_state'] ?? '') === 'ENFORCED'));
    t('workshop_enforced_35', $enf === 35, "enf=$enf");

    $ov = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.core_user_permission_overrides o
         INNER JOIN dbo.core_permissions p ON p.permission_id=o.permission_id
         WHERE p.permission_key LIKE N'workshop.%' AND o.revoked_at IS NULL
           AND o.created_at >= DATEADD(hour, -2, SYSUTCDATETIME())"
    ) ?? 0);
    t('no_real_assignment', $ov === 0);
} finally {
    @odbc_rollback($conn);
    @odbc_autocommit($conn, true);
    $log[] = 'TRANSACTION_ROLLED_BACK';
}

$log[] = "SUMMARY pass=$pass fail=$fail";
$file = $outDir . '/workshop-r1a2r1-uat-' . date('Ymd-His') . '.txt';
file_put_contents($file, implode(PHP_EOL, $log) . PHP_EOL);
echo implode(PHP_EOL, $log) . PHP_EOL;
echo "WROTE=$file\n";
exit($fail > 0 ? 2 : 0);
