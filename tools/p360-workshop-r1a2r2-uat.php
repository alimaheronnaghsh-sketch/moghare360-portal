<?php
declare(strict_types=1);

/**
 * R1A-2R2 UAT — ISSUED reversal + pending-cost completion. CLI only. Rollback at end.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-access-matrix-helper.php';
require_once $root . '/public_html/includes/m360-workshop-ic-reversal-helper.php';
require_once $root . '/public_html/includes/m360-inventory-valuation-helper.php';
require_once $root . '/public_html/includes/m360-access-matrix-catalog.php';

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

$jc = (int)(customer_core_scalar($conn, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE company_id>0 ORDER BY jobcard_id DESC') ?? 0);
$cid = (int)(customer_core_scalar($conn, 'SELECT company_id FROM dbo.erp_jobcards WHERE jobcard_id=?', [$jc]) ?? 0);
$invId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 inventory_item_id FROM dbo.erp_inventory_items WHERE is_active=1') ?? 0);
$users = customer_core_fetch_rows(
    $conn,
    "SELECT TOP 3 u.user_id FROM dbo.core_users u
     INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
     WHERE u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1 ORDER BY u.user_id"
);
$A = (int)($users[0]['user_id'] ?? 0);
$B = (int)($users[1]['user_id'] ?? 0);
$C = (int)($users[2]['user_id'] ?? $B);
t('setup', $jc > 0 && $invId > 0 && $A > 0 && $B > 0 && $A !== $B, "jc=$jc inv=$invId A=$A B=$B C=$C");

$defs = array_filter(m360_access_matrix_catalog_definitions(), static fn($r) => ($r['module_key'] ?? '') === 'workshop');
$enf = count(array_filter($defs, static fn($r) => ($r['enforcement_state'] ?? '') === 'ENFORCED'));
$nye = count(array_filter($defs, static fn($r) => ($r['enforcement_state'] ?? '') === 'NOT_YET_ENFORCED'));
t('catalog_workshop_37', $enf === 37 && $nye === 0, "enf=$enf nye=$nye total=" . count($defs));

@odbc_autocommit($conn, false);
try {
    if (customer_core_table_exists($conn, 'erp_stock_balances')) {
        $balId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 stock_balance_id FROM dbo.erp_stock_balances WHERE inventory_item_id=?', [$invId]) ?? 0);
        if ($balId > 0) {
            customer_core_execute($conn, 'UPDATE dbo.erp_stock_balances SET available_qty=500 WHERE stock_balance_id=?', [$balId]);
        }
    }

    // Valuation for confirmed-cost issues
    $d = m360_inv_val_create_draft($conn, $cid, $invId, ['valuation_method' => 'STANDARD_INTERNAL_COST', 'unit_cost' => 100], $A);
    $vid = (int)$d['valuation_id'];
    m360_inv_val_submit($conn, $vid, $A);
    m360_inv_val_approve($conn, $vid, $B);
    t('val_ready', $vid > 0);

    // A — full reversal qty 5
    $ic = m360_ws_ic_create($conn, $cid, $jc, null, ['usage_reason' => 'ابطال کامل', 'consuming_unit' => 'MECHANICAL'], [[
        'inventory_item_id' => $invId, 'quantity' => 5, 'unit_of_measure' => 'عدد', 'manual_description' => '',
    ]], $A);
    $rid = (int)$ic['request_id'];
    m360_ws_ic_submit($conn, $rid, $A);
    $ap = m360_ws_ic_approve($conn, $rid, $B);
    t('issue_full', !empty($ap['ok']), $ap['message'] ?? '');
    $items = m360_ws_ic_fetch_items($conn, $rid);
    $itemId = (int)$items[0]['request_item_id'];
    $origMov = (int)$items[0]['inventory_movement_id'];
    $balBefore = (float)(customer_core_scalar($conn, 'SELECT TOP 1 available_qty FROM dbo.erp_stock_balances WHERE inventory_item_id=?', [$invId]) ?? 0);

    $reqSelf = m360_ws_ic_reversal_request($conn, $itemId, 5, 'WRONG_QTY', 'مقدار اشتباه ثبت شده برای تست', $A, $cid, false);
    // requester A (also creator) — OK for request
    t('rev_request', !empty($reqSelf['ok']), $reqSelf['message'] ?? '');
    $revid = (int)$reqSelf['reversal_id'];
    t('rev_self_approve_blocked', empty(m360_ws_ic_reversal_approve($conn, $revid, $A, $cid, false)['ok']));
    // creator cannot silently approve — use B who is also creator? B approved issue but didn't create. Use B as approver.
    // Actually requesting_user is A, so B can approve. If requester is A, B approves.
    $apR = m360_ws_ic_reversal_approve($conn, $revid, $B, $cid, false);
    t('rev_full_approve', !empty($apR['ok']), $apR['message'] ?? '');
    $itemsAfter = m360_ws_ic_fetch_items($conn, $rid);
    $reqAfter = m360_ws_ic_fetch_request($conn, $rid);
    t('rev_full_qty', (float)($itemsAfter[0]['quantity_reversed'] ?? 0) >= 4.999);
    t('rev_status', strtoupper((string)($reqAfter['status'] ?? '')) === 'REVERSED', 'st=' . (string)($reqAfter['status'] ?? ''));
    $origStill = (int)(customer_core_scalar($conn, 'SELECT stock_movement_id FROM dbo.erp_inventory_stock_movements WHERE stock_movement_id=?', [$origMov]) ?? 0);
    t('orig_mov_preserved', $origStill === $origMov && $origMov > 0);
    $revMov = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_inventory_stock_movements WHERE movement_note LIKE ?",
        ['INTERNAL_CONSUMABLE_REVERSAL:REQ:' . $revid . '%']
    ) ?? 0);
    t('reversing_mov', $revMov === 1, "count=$revMov");
    $dup = m360_ws_ic_reversal_approve($conn, $revid, $B, $cid, false);
    t('dup_rev_blocked', empty($dup['ok']));

    // B — partial reversal
    $ic2 = m360_ws_ic_create($conn, $cid, $jc, null, ['usage_reason' => 'ابطال جزئی', 'consuming_unit' => 'MECHANICAL'], [[
        'inventory_item_id' => $invId, 'quantity' => 5, 'unit_of_measure' => 'عدد', 'manual_description' => '',
    ]], $A);
    $rid2 = (int)$ic2['request_id'];
    m360_ws_ic_submit($conn, $rid2, $A);
    m360_ws_ic_approve($conn, $rid2, $B);
    $it2 = m360_ws_ic_fetch_items($conn, $rid2)[0];
    $item2 = (int)$it2['request_item_id'];
    $r2 = m360_ws_ic_reversal_request($conn, $item2, 2, 'DUPLICATE', 'ثبت تکراری جزئی', $B, $cid, false);
    t('partial_request', !empty($r2['ok']), $r2['message'] ?? '');
    // B requested — A is creator so cannot approve; need C if C!=B else use isOwner path with reason — use C
    $approver = ($C !== $B && $C !== $A) ? $C : $A;
    // If approver is A (creator), maker-checker blocks. Prefer C.
    if ($approver === $A) {
        // Fall back: create request by A, approve by B
        // Current requester is B — A is creator so blocked. Force C = B won't work.
        // Use owner override for test only when stuck — better: request by B, approve by A fails; request by A, approve by B.
        customer_core_execute($conn, "UPDATE dbo.erp_workshop_ic_reversals SET status=N'REVERSAL_REJECTED' WHERE reversal_id=?", [(int)$r2['reversal_id']]);
        customer_core_execute($conn, "UPDATE dbo.erp_workshop_internal_consumable_requests SET status=N'ISSUED' WHERE request_id=?", [$rid2]);
        $r2 = m360_ws_ic_reversal_request($conn, $item2, 2, 'DUPLICATE', 'ثبت تکراری جزئی', $A, $cid, false);
        $approver = $B;
    }
    $ap2 = m360_ws_ic_reversal_approve($conn, (int)$r2['reversal_id'], $approver, $cid, false);
    t('partial_approve', !empty($ap2['ok']), $ap2['message'] ?? '');
    $it2b = m360_ws_ic_fetch_items($conn, $rid2)[0];
    $net = (float)$it2b['quantity'] - (float)($it2b['quantity_reversed'] ?? 0);
    t('partial_net_3', abs($net - 3.0) < 0.001, "net=$net");
    $rExcess = m360_ws_ic_reversal_request($conn, $item2, 4, 'WRONG_QTY', 'بیش از خالص', $A, $cid, false);
    t('excess_blocked', empty($rExcess['ok']));

    // Cost: original snapshot used
    $unit = (float)($it2b['internal_unit_cost'] ?? 0);
    $revCost = (float)($it2b['reversed_total_cost'] ?? 0);
    t('cost_snapshot_used', abs($unit - 100) < 0.001 && abs($revCost - 200) < 0.01, "u=$unit rc=$revCost");

    $sum = m360_ws_jobcard_true_cost_summary($conn, $jc);
    t('true_cost_fields', isset($sum['internal_consumable_gross'], $sum['internal_consumable_reversed'], $sum['internal_consumable_net']));
    t('billable_flags', (int)$it2b['customer_billable'] === 0 && (int)$it2b['invoice_excluded'] === 1);

    // E — pending cost + reversal
    $ic3 = m360_ws_ic_create($conn, $cid, $jc, null, ['usage_reason' => 'معلق', 'consuming_unit' => 'MECHANICAL'], [[
        'inventory_item_id' => $invId, 'quantity' => 2, 'unit_of_measure' => 'عدد', 'manual_description' => '',
    ]], $A);
    // Temporarily block valuation by superseding? Or use different item without valuation.
    // Simpler: create non-stock pending
    $ic3 = m360_ws_ic_create($conn, $cid, $jc, null, ['usage_reason' => 'معلق غیرانبار', 'consuming_unit' => 'HALL'], [[
        'inventory_item_id' => 0, 'quantity' => 2, 'unit_of_measure' => 'عدد', 'manual_description' => 'اسپری تست معلق',
    ]], $A);
    $rid3 = (int)$ic3['request_id'];
    m360_ws_ic_submit($conn, $rid3, $A);
    m360_ws_ic_approve($conn, $rid3, $B);
    $it3 = m360_ws_ic_fetch_items($conn, $rid3)[0];
    t('pending_issue', strtoupper((string)($it3['cost_status'] ?? '')) === 'COST_PENDING');
    $item3 = (int)$it3['request_item_id'];
    $r3 = m360_ws_ic_reversal_request($conn, $item3, 2, 'NOT_CONSUMED', 'مصرف انجام نشده', $A, $cid, false);
    $ap3 = m360_ws_ic_reversal_approve($conn, (int)$r3['reversal_id'], $B, $cid, false);
    t('pending_rev', !empty($ap3['ok']), $ap3['message'] ?? '');
    $it3b = m360_ws_ic_fetch_items($conn, $rid3)[0];
    t('pending_no_fake_zero', trim((string)($it3b['internal_total_cost'] ?? '')) === '');

    // F — pending completion on stock item: issue without using valuation — use manual item then link?
    // Create stock issue while no approved active? Hard because we have approved val.
    // Complete path: create pending by forcing cost_status after issue of stock without val —
    // Alternative: insert valuation after issue of item that had no val at issue time.
    // Use inventory item  that we don't val — if only one item, temporarily don't resolve by using qty on new item id 0 path already tested.
    // For completion: open a pending task manually for a COST_PENDING stock line.
    // Create IC with inv, but resolve won't find if we void all vals — skip by creating pending task + COST_PENDING item via second approve path:
    // Mark: create draft val for same item AFTER pending issue won't help if resolve finds earlier val.

    // Force pending: create request with stock, approve after setting company mismatch on val? Too heavy.
    // Direct SQL in txn to set COST_PENDING on a fresh issued line after wiping snapshot fields.
    $ic4 = m360_ws_ic_create($conn, $cid, $jc, null, ['usage_reason' => 'تکمیل بها', 'consuming_unit' => 'MECHANICAL'], [[
        'inventory_item_id' => $invId, 'quantity' => 1, 'unit_of_measure' => 'عدد', 'manual_description' => '',
    ]], $A);
    $rid4 = (int)$ic4['request_id'];
    m360_ws_ic_submit($conn, $rid4, $A);
    m360_ws_ic_approve($conn, $rid4, $B);
    $it4 = m360_ws_ic_fetch_items($conn, $rid4)[0];
    $item4 = (int)$it4['request_item_id'];
    // Force pending state for completion test
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_request_items
         SET cost_status=N'COST_PENDING', internal_unit_cost=NULL, internal_total_cost=NULL, valuation_id=NULL
         WHERE request_item_id=?",
        [$item4]
    );
    m360_inv_val_open_pending_task($conn, $cid, $invId, $jc, $rid4, $item4, 'تست تکمیل بها', 1.0);
    $taskId = (int)(customer_core_scalar(
        $conn,
        "SELECT TOP 1 task_id FROM dbo.erp_inventory_cost_pending_tasks WHERE request_item_id=? AND task_status=N'OPEN'",
        [$item4]
    ) ?? 0);
    $comp = m360_inv_val_complete_pending_cost($conn, $taskId, $vid, $B, $cid, false, '');
    t('pending_complete', !empty($comp['ok']), $comp['message'] ?? '');
    $it4b = m360_ws_ic_fetch_items($conn, $rid4)[0];
    t('pending_complete_cost', strtoupper((string)$it4b['cost_status']) === 'COST_CONFIRMED' && abs((float)$it4b['internal_total_cost'] - 100) < 0.01);
    $comp2 = m360_inv_val_complete_pending_cost($conn, $taskId, $vid, $B, $cid, false, '');
    t('pending_complete_idempotent', empty($comp2['ok']));

    $filtered = m360_ws_ic_filter_cost_row($it4b, false);
    t('tech_privacy', !isset($filtered['internal_unit_cost']));

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
$file = $outDir . '/workshop-r1a2r2-uat-' . date('Ymd-His') . '.txt';
file_put_contents($file, implode(PHP_EOL, $log) . PHP_EOL);
echo implode(PHP_EOL, $log) . PHP_EOL;
echo "WROTE=$file\n";
exit($fail > 0 ? 2 : 0);
