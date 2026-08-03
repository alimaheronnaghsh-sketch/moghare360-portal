<?php
declare(strict_types=1);

/**
 * R1A-2 synthetic UAT — no real personnel overrides. CLI only. Evidence under tools/_generated.
 * Outer ODBC transaction is rolled back at end (no durable synthetic rows).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-access-matrix-helper.php';
require_once $root . '/public_html/includes/m360-workshop-work-item-helper.php';
require_once $root . '/public_html/includes/m360-workshop-diagnosis-report-helper.php';
require_once $root . '/public_html/includes/m360-workshop-work-report-helper.php';
require_once $root . '/public_html/includes/m360-workshop-internal-consumable-helper.php';

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

function t(string $name, bool $ok, string $detail = ''): void
{
    global $log, $pass, $fail;
    if ($ok) {
        $pass++;
        $log[] = "PASS $name $detail";
    } else {
        $fail++;
        $log[] = "FAIL $name $detail";
    }
}

$jc = (int)(customer_core_scalar($conn, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards ORDER BY jobcard_id DESC') ?? 0);
t('jobcard_exists', $jc > 0, 'id=' . $jc);

$companyId = 1;
$actorA = 900001;
$actorB = 900002;

$realUsers = customer_core_fetch_rows(
    $conn,
    "SELECT TOP 2 u.user_id FROM dbo.core_users u
     INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
     WHERE u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1 ORDER BY u.user_id"
);
if (count($realUsers) >= 2) {
    $actorA = (int)$realUsers[0]['user_id'];
    $actorB = (int)$realUsers[1]['user_id'];
}
$log[] = "actors A=$actorA B=$actorB";

@odbc_autocommit($conn, false);

try {
    // A/B — work items + parallel inspection
    $p = m360_ws_wi_ensure($conn, $companyId, $jc, 'PERIODIC_SERVICE', 'PERIODIC_SERVICE', '', $actorA);
    t('periodic_wi', !empty($p['ok']), $p['message'] ?? '');
    $im = m360_ws_wi_ensure($conn, $companyId, $jc, 'INSPECTION', 'INSPECTION', 'MECHANICAL', $actorA);
    $ie = m360_ws_wi_ensure($conn, $companyId, $jc, 'INSPECTION', 'INSPECTION', 'ELECTRICAL', $actorA);
    t('inspection_mech_wi', !empty($im['ok']));
    t('inspection_elec_wi', !empty($ie['ok']));
    t('inspection_parallel_distinct', (int)$im['work_item_id'] !== (int)$ie['work_item_id'] && (int)$im['work_item_id'] > 0);

    t('perm_periodic', m360_ws_wi_assign_permission('PERIODIC_SERVICE', '') === 'workshop.assign.periodic_service');
    t('perm_insp_m', m360_ws_wi_assign_permission('INSPECTION', 'MECHANICAL') === 'workshop.assign.inspection_mechanical');
    t('perm_insp_e', m360_ws_wi_assign_permission('INSPECTION', 'ELECTRICAL') === 'workshop.assign.inspection_electrical');
    t('perm_mech_not_periodic', m360_ws_assign_permission_for_team('MECHANICAL') !== 'workshop.assign.periodic_service');

    // C — diagnosis maker-checker
    $d = m360_ws_diag_create_or_update_draft($conn, $companyId, $jc, null, ['diagnosis_summary' => 'تست تشخیص R1A2'], $actorA);
    t('diag_create', !empty($d['ok']), (string)($d['diagnosis_report_id'] ?? 0));
    $did = (int)$d['diagnosis_report_id'];
    $ds = m360_ws_diag_submit($conn, $did, $actorA);
    t('diag_submit', !empty($ds['ok']));
    $selfApprove = m360_ws_diag_approve($conn, $did, $actorA, false);
    t('diag_self_approve_blocked', empty($selfApprove['ok']));
    $dap = m360_ws_diag_approve($conn, $did, $actorB, false);
    t('diag_approve_other', !empty($dap['ok']), $dap['message'] ?? '');

    $d2 = m360_ws_diag_create_or_update_draft($conn, $companyId, $jc, null, ['diagnosis_summary' => 'تست برگشت'], $actorA);
    $did2 = (int)$d2['diagnosis_report_id'];
    m360_ws_diag_submit($conn, $did2, $actorA);
    $retEmpty = m360_ws_diag_return($conn, $did2, $actorB, '');
    t('diag_return_reason_required', empty($retEmpty['ok']));
    $ret = m360_ws_diag_return($conn, $did2, $actorB, 'نیاز به اصلاح توضیحات');
    $retRow = m360_ws_diag_fetch($conn, $did2);
    t('diag_return_ok', !empty($ret['ok']) && strtoupper((string)($retRow['status'] ?? '')) === 'RETURNED', 'new=' . (int)($ret['new_report_id'] ?? 0));

    // D — work report maker-checker
    $w = m360_ws_wr_create_or_update_draft($conn, $companyId, $jc, null, ['work_description' => 'کار انجام‌شده تست', 'duration_minutes' => 30], $actorA);
    $wid = (int)$w['work_report_id'];
    t('wr_create', !empty($w['ok']));
    m360_ws_wr_submit($conn, $wid, $actorA);
    t('wr_self_approve_blocked', empty(m360_ws_wr_approve($conn, $wid, $actorA)['ok']));
    t('wr_approve_other', !empty(m360_ws_wr_approve($conn, $wid, $actorB)['ok']));

    $w2 = m360_ws_wr_create_or_update_draft($conn, $companyId, $jc, null, ['work_description' => 'کار برای برگشت'], $actorA);
    $wid2 = (int)$w2['work_report_id'];
    m360_ws_wr_submit($conn, $wid2, $actorA);
    t('wr_return_reason_required', empty(m360_ws_wr_return($conn, $wid2, $actorB, '')['ok']));
    t('wr_return_ok', !empty(m360_ws_wr_return($conn, $wid2, $actorB, 'اصلاح مدت زمان')['ok']));

    // E — IC create + price reject
    $f = m360_ws_filter_price_fields(['unit_price' => '100', 'quantity' => 1]);
    t('ic_price_filter', $f['rejected'] !== [] && !isset($f['clean']['unit_price']));

    $invId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 inventory_item_id FROM dbo.erp_inventory_items WHERE is_active=1') ?? 0);
    $ic = m360_ws_ic_create(
        $conn,
        $companyId,
        $jc,
        null,
        ['usage_reason' => 'شست‌وشوی قطعه تست', 'consuming_unit' => 'MECHANICAL'],
        [[
            'inventory_item_id' => $invId,
            'manual_description' => $invId > 0 ? '' : 'دستمال تست',
            'quantity' => 1,
            'unit_of_measure' => 'عدد',
        ]],
        $actorA
    );
    t('ic_create', !empty($ic['ok']), $ic['message'] ?? '');
    $rid = (int)($ic['request_id'] ?? 0);
    if ($rid > 0) {
        m360_ws_ic_submit($conn, $rid, $actorA);
        $items = m360_ws_ic_fetch_items($conn, $rid);
        t('ic_billable_false', isset($items[0]) && (int)$items[0]['customer_billable'] === 0);
        t('ic_invoice_excluded', isset($items[0]) && (int)$items[0]['invoice_excluded'] === 1);
        $selfIc = m360_ws_ic_approve($conn, $rid, $actorA);
        t('ic_self_approve_blocked', empty($selfIc['ok']));
        $ap = m360_ws_ic_approve($conn, $rid, $actorB);
        t('ic_approve_attempt', !empty($ap['ok']), $ap['message'] ?? '');
        if (!empty($ap['ok'])) {
            $reqAfter = m360_ws_ic_fetch_request($conn, $rid);
            $items2 = m360_ws_ic_fetch_items($conn, $rid);
            $stockManaged = (int)($items2[0]['stock_managed'] ?? 0);
            $mov = (int)($items2[0]['inventory_movement_id'] ?? 0);
            $noteHits = (int)(customer_core_scalar(
                $conn,
                "SELECT COUNT(*) FROM dbo.erp_inventory_stock_movements WHERE movement_note LIKE ?",
                ['INTERNAL_CONSUMABLE:REQ:' . $rid . '%']
            ) ?? 0);
            $movementOk = $stockManaged === 0
                ? (strtoupper((string)($reqAfter['status'] ?? '')) === 'ISSUED' && count($items2) === 1)
                : (strtoupper((string)($reqAfter['status'] ?? '')) === 'ISSUED' && $mov > 0 && $noteHits === 1);
            t(
                'ic_movement_once',
                $movementOk,
                'mov=' . $mov . ' notes=' . $noteHits . ' stock=' . $stockManaged . ' items=' . count($items2)
            );
            t('ic_true_cost_fn', is_float(m360_ws_ic_jobcard_true_cost($conn, $jc)) || is_numeric(m360_ws_ic_jobcard_true_cost($conn, $jc)));
            $ap2 = m360_ws_ic_approve($conn, $rid, $actorB);
            t('ic_dup_approve_blocked', empty($ap2['ok']));
        } else {
            $ic2 = m360_ws_ic_create(
                $conn,
                $companyId,
                $jc,
                null,
                ['usage_reason' => 'مصرف غیرانباردار', 'consuming_unit' => 'HALL'],
                [['inventory_item_id' => 0, 'manual_description' => 'اسپری تست', 'quantity' => 1, 'unit_of_measure' => 'عدد']],
                $actorA
            );
            $rid2 = (int)$ic2['request_id'];
            m360_ws_ic_submit($conn, $rid2, $actorA);
            $apn = m360_ws_ic_approve($conn, $rid2, $actorB);
            t('ic_nonstock_approve', !empty($apn['ok']), $apn['message'] ?? '');
        }

        // G — return path on fresh submitted
        $ic3 = m360_ws_ic_create(
            $conn,
            $companyId,
            $jc,
            null,
            ['usage_reason' => 'برگشت تست', 'consuming_unit' => 'MECHANICAL'],
            [['inventory_item_id' => 0, 'manual_description' => 'بست تست', 'quantity' => 2, 'unit_of_measure' => 'عدد']],
            $actorA
        );
        $rid3 = (int)$ic3['request_id'];
        m360_ws_ic_submit($conn, $rid3, $actorA);
        t('ic_return_reason', empty(m360_ws_ic_return($conn, $rid3, $actorB, '')['ok']));
        t('ic_return_ok', !empty(m360_ws_ic_return($conn, $rid3, $actorB, 'مقدار اشتباه است')['ok']));
    }

    // H — cost filter
    $sample = ['quantity' => 1, 'internal_unit_cost' => 10, 'internal_total_cost' => 10, 'manual_description' => 'x'];
    $filtered = m360_ws_ic_filter_cost_row($sample, false);
    t('cost_hidden', !isset($filtered['internal_unit_cost']) && !isset($filtered['internal_total_cost']));
    $shown = m360_ws_ic_filter_cost_row($sample, true);
    t('cost_visible_with_perm', isset($shown['internal_total_cost']));

    // I — object scope
    $missing = customer_core_fetch_rows($conn, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE jobcard_id=?', [999999991]);
    t('object_missing_jobcard', $missing === []);

    require_once $root . '/public_html/includes/m360-access-matrix-catalog.php';
    $defs = array_filter(m360_access_matrix_catalog_definitions(), static fn($r) => ($r['module_key'] ?? '') === 'workshop');
    $enf = count(array_filter($defs, static fn($r) => ($r['enforcement_state'] ?? '') === 'ENFORCED'));
    $nye = count(array_filter($defs, static fn($r) => ($r['enforcement_state'] ?? '') === 'NOT_YET_ENFORCED'));
    t('catalog_enforced_35', $enf === 35, "enf=$enf nye=$nye");

    $ov = customer_core_fetch_rows(
        $conn,
        "SELECT COUNT(*) AS c FROM dbo.core_user_permission_overrides o
         INNER JOIN dbo.core_permissions p ON p.permission_id=o.permission_id
         WHERE p.permission_key LIKE N'workshop.%' AND o.revoked_at IS NULL
           AND o.created_at >= DATEADD(hour, -2, SYSUTCDATETIME())"
    );
    t('no_real_assignment', (int)($ov[0]['c'] ?? 0) === 0);

} finally {
    @odbc_rollback($conn);
    @odbc_autocommit($conn, true);
    $log[] = 'TRANSACTION_ROLLED_BACK';
}

$log[] = "SUMMARY pass=$pass fail=$fail";
$file = $outDir . '/workshop-r1a2-uat-' . date('Ymd-His') . '.txt';
file_put_contents($file, implode(PHP_EOL, $log) . PHP_EOL);
echo implode(PHP_EOL, $log) . PHP_EOL;
echo "WROTE=$file\n";
exit($fail > 0 ? 2 : 0);
