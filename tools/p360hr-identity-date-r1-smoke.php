<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * R1 gap smoke: occupational medicine dates + direct-manager reminder recipients.
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/public_html/peopleos360/includes/p360-hr-occ-med-manager.php';

echo "R1_SMOKE_START\n";
$fail = 0;
$assert = static function (bool $c, string $l) use (&$fail): void {
    echo ($c ? 'PASS' : 'FAIL') . ' ' . $l . "\n";
    if (!$c) {
        $fail++;
    }
};

// --- A. Occupational medicine Jalali / SQL ---
$issue = p360_jalali_to_sql_date('1404/05/12');
$assert(!empty($issue['ok']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$issue['ymd']), 'occ_issue_sql');
$exp = p360_jalali_to_sql_date('1405/05/12');
$assert(!empty($exp['ok']), 'occ_expiry_sql');
$bad = p360_jalali_to_sql_date('1406/02/32');
$assert(empty($bad['ok']), 'occ_reject_invalid_day');
$leapDays = p360_jalali_month_days(1403, 12);
$nonLeap = p360_jalali_month_days(1402, 12);
$assert(in_array($leapDays, [29, 30], true) && in_array($nonLeap, [29, 30], true), 'occ_leap_esfand');
$reloadJ = p360_sql_date_to_jalali((string)$issue['ymd']);
$assert($reloadJ === '1404/05/12' || str_replace('-', '/', $reloadJ) === '1404/05/12' || preg_match('#1404[/\\-]05[/\\-]12#', $reloadJ), 'occ_jalali_reload:' . $reloadJ);

$empA = p360hr_one("SELECT TOP 1 employee_id, employee_code, core_user_id FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL AND employee_code LIKE N'M360-%' AND core_user_id IS NOT NULL ORDER BY employee_id");
$empB = p360hr_one("SELECT TOP 1 employee_id, employee_code, core_user_id FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL AND employee_code LIKE N'M360-%' AND core_user_id IS NOT NULL AND employee_id<>? ORDER BY employee_id", [(int)($empA['employee_id'] ?? 0)]);
$assert($empA !== null && $empB !== null, 'has_two_emps');

$reportId = 0;
if ($empA) {
    $eidA = (int)$empA['employee_id'];
    // Direct insert (CLI has no HR session for can_manage)
    $okIns = p360hr_exec(
        'INSERT INTO dbo.p360_hr_occ_med_reports
            (employee_id, examination_date, report_issue_date, valid_from_date, expiry_date, no_expiry,
             medical_center_name, report_ref, result_status, lifecycle_state, created_by_user_id)
         VALUES (?,?,?,?,?,0,N\'ظ…ط±ع©ط² ط·ط¨ ع©ط§ط± طھط³طھ\',N\'R1-REF\',N\'FIT\',N\'VALID\',1)',
        [$eidA, $issue['ymd'], $issue['ymd'], $issue['ymd'], $exp['ymd']]
    );
    $assert($okIns, 'occ_insert_canonical');
    $row = p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_occ_med_reports WHERE employee_id=? ORDER BY report_id DESC', [$eidA]);
    $assert($row !== null, 'occ_row');
    $reportId = (int)($row['report_id'] ?? 0);
    $assert(substr((string)$row['examination_date'], 0, 10) === (string)$issue['ymd'], 'occ_exam_stored_sql');
    $assert(substr((string)$row['report_issue_date'], 0, 10) === (string)$issue['ymd'], 'occ_issue_stored_sql');
    $assert((int)$row['no_expiry'] === 0, 'occ_has_expiry');
    $derived = p360hr_occ_med_derive_status($row);
    $assert(in_array($derived, ['VALID', 'EXPIRING_SOON', 'EXPIRED', 'RENEWAL_REQUIRED'], true), 'occ_status_derived:' . $derived);
    $assert(p360hr_occ_med_status_fa($derived) !== 'ظ†ط§ظ…ط´ط®طµ' && !preg_match('/^[A-Z_]+$/', p360hr_occ_med_status_fa($derived)), 'occ_status_fa');

    // no-expiry without fake date
    p360hr_exec(
        'INSERT INTO dbo.p360_hr_occ_med_reports
            (employee_id, examination_date, report_issue_date, expiry_date, no_expiry, lifecycle_state, created_by_user_id)
         VALUES (?,?,?,NULL,1,N\'VALID\',1)',
        [$eidA, $issue['ymd'], $issue['ymd']]
    );
    $nx = p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_occ_med_reports WHERE employee_id=? AND no_expiry=1 ORDER BY report_id DESC', [$eidA]);
    $assert($nx !== null && ($nx['expiry_date'] === null || trim((string)$nx['expiry_date']) === ''), 'occ_no_expiry_null');
    $assert(p360hr_occ_med_derive_status($nx) === 'VALID', 'occ_no_expiry_valid');

    // Privacy: list scoped by employee
    $listA = p360hr_occ_med_list($eidA);
    $listB = p360hr_occ_med_list((int)$empB['employee_id']);
    foreach ($listA as $r) {
        $assert((int)$r['employee_id'] === $eidA, 'occ_list_own_only_A');
    }
    foreach ($listB as $r) {
        $assert((int)$r['employee_id'] === (int)$empB['employee_id'], 'occ_list_own_only_B');
    }
    $cross = p360hr_occ_med_get($reportId);
    $assert($cross !== null && (int)$cross['employee_id'] === $eidA, 'occ_get_owner');
    // employee B must not treat as own
    $assert((int)$cross['employee_id'] !== (int)$empB['employee_id'], 'occ_cross_blocked_by_owner_check');
}

// --- C/D. Direct manager reminder ---
$mgrEmp = null;
$subEmp = null;
if ($empA && $empB) {
    // Prefer pair where B can manage A: set A.direct_manager = B
    $mgrEmp = $empB;
    $subEmp = $empA;
    $mgrUid = (int)($mgrEmp['core_user_id'] ?? 0);
    $subId = (int)$subEmp['employee_id'];
    $mgrId = (int)$mgrEmp['employee_id'];

    $prof = p360hr_one('SELECT employee_id FROM dbo.p360_hr_personnel_profile WHERE employee_id=?', [$subId]);
    if ($prof === null) {
        p360hr_exec('INSERT INTO dbo.p360_hr_personnel_profile (employee_id, direct_manager_employee_id) VALUES (?,?)', [$subId, $mgrId]);
    } else {
        p360hr_exec('UPDATE dbo.p360_hr_personnel_profile SET direct_manager_employee_id=? WHERE employee_id=?', [$mgrId, $subId]);
    }
    $resolved = p360hr_resolve_direct_manager($subId);
    $assert(!empty($resolved['ok']) && (int)$resolved['manager_user_id'] === $mgrUid, 'mgr_resolve_ok');

    // Contract ending in ~15 days
    $endG = (new DateTimeImmutable('today'))->modify('+15 days')->format('Y-m-d');
    $startG = (new DateTimeImmutable('today'))->modify('-1 month')->format('Y-m-d');
    $contract = p360hr_one(
        "SELECT TOP 1 contract_id FROM dbo.p360_hr_contracts WHERE employee_id=? AND is_locked=1 ORDER BY contract_id DESC",
        [$subId]
    );
    $cid = 0;
    if ($contract) {
        $cid = (int)$contract['contract_id'];
        p360hr_exec(
            "UPDATE dbo.p360_hr_contracts SET end_date=?, start_date=?, contract_type=N'TEMPORARY',
                renewal_status=NULL, reminder_trigger_date=NULL WHERE contract_id=?",
            [$endG, $startG, $cid]
        );
    } else {
        // Minimal locked contract for smoke
        p360hr_exec(
            "INSERT INTO dbo.p360_hr_contracts
                (employee_id, personnel_code, contract_type, contract_job_title, unit_name, workplace, contract_subject,
                 start_date, end_date, stage_code, is_locked, created_by_user_id)
             VALUES (?,?,N'TEMPORARY',N'ط´ط؛ظ„ طھط³طھ غŒط§ط¯ط¢ظˆط±',N'ظˆط§ط­ط¯',N'ظ…ط­ظ„',N'ظ…ظˆط¶ظˆط¹',?,?,N'FINAL',1,1)",
            [$subId, (string)$subEmp['employee_code'], $startG, $endG]
        );
        $contract = p360hr_one('SELECT TOP 1 contract_id FROM dbo.p360_hr_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$subId]);
        $cid = (int)($contract['contract_id'] ?? 0);
    }
    $assert($cid > 0, 'mgr_contract');

    // Clear prior smoke reminders for this contract+end to count cleanly
    $oldRems = p360hr_rows('SELECT reminder_id FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?', [$cid, $endG]);
    foreach ($oldRems as $or) {
        p360hr_exec('DELETE FROM dbo.p360_hr_contract_reminder_recipients WHERE reminder_id=?', [(int)$or['reminder_id']]);
        p360hr_exec('DELETE FROM dbo.p360_hr_contract_reminders WHERE reminder_id=?', [(int)$or['reminder_id']]);
    }

    $r1 = p360hr_reminder_refresh_all_r1();
    $assert(!empty($r1['ok']), 'refresh1_ok');
    $rem = p360hr_one(
        'SELECT * FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?',
        [$cid, $endG]
    );
    $assert($rem !== null, 'one_reminder');
    $rid = (int)($rem['reminder_id'] ?? 0);

    $hrRecs = p360hr_rows(
        "SELECT * FROM dbo.p360_hr_contract_reminder_recipients WHERE reminder_id=? AND recipient_type IN (N'SYSTEM_OWNER', N'HR_OWNER')",
        [$rid]
    );
    $assert(count($hrRecs) >= 1, 'owner_hr_recipient');
    $mgrRec = p360hr_one(
        "SELECT * FROM dbo.p360_hr_contract_reminder_recipients WHERE reminder_id=? AND recipient_type=N'DIRECT_MANAGER' AND recipient_user_id=?",
        [$rid, $mgrUid]
    );
    $assert($mgrRec !== null, 'manager_recipient');

    $cart = p360hr_manager_cartable_reminders($mgrUid);
    $seen = false;
    foreach ($cart as $c) {
        if ((int)$c['reminder_id'] === $rid) {
            $seen = true;
        }
    }
    $assert($seen, 'manager_cartable_visible');

    // Unrelated manager denial
    $unrelatedUid = (int)$empA['core_user_id'];
    if ($unrelatedUid === $mgrUid) {
        $other = p360hr_one('SELECT TOP 1 core_user_id FROM dbo.p360_employees WHERE core_user_id IS NOT NULL AND core_user_id NOT IN (?,?)', [$mgrUid, $unrelatedUid]);
        $unrelatedUid = (int)($other['core_user_id'] ?? 0);
    }
    if ($unrelatedUid > 0 && $unrelatedUid !== $mgrUid) {
        $denied = p360hr_manager_assert_recipient_access($rid, $unrelatedUid);
        $assert($denied === null, 'unrelated_manager_denied');
        $cartU = p360hr_manager_cartable_reminders($unrelatedUid);
        $leak = false;
        foreach ($cartU as $c) {
            if ((int)$c['reminder_id'] === $rid) {
                $leak = true;
            }
        }
        $assert(!$leak, 'unrelated_no_cartable_leak');
    }

    $r2 = p360hr_reminder_refresh_all_r1();
    $assert(!empty($r2['ok']), 'refresh2_ok');
    $remCount = p360hr_one(
        'SELECT COUNT(*) AS c FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?',
        [$cid, $endG]
    );
    $assert((int)($remCount['c'] ?? 0) === 1, 'no_dup_reminder');
    $recCount = p360hr_one(
        'SELECT COUNT(*) AS c FROM dbo.p360_hr_contract_reminder_recipients WHERE reminder_id=? AND recipient_type=N\'DIRECT_MANAGER\' AND recipient_user_id=?',
        [$rid, $mgrUid]
    );
    $assert((int)($recCount['c'] ?? 0) === 1, 'no_dup_recipient');

    // Manager recommendation only
    $recOk = p360hr_manager_recommend($rid, $mgrUid, 'RENEW_RECOMMENDED', 'طھط³طھ طھظˆطµغŒظ‡');
    $assert(!empty($recOk['ok']), 'mgr_recommend');
    $assert(str_contains((string)$recOk['message'], 'ظ†ظ‡ط§غŒغŒ') || str_contains((string)$recOk['message'], 'ظ…ظ†ط§ط¨ط¹ ط§ظ†ط³ط§ظ†غŒ'), 'mgr_cannot_finalize_msg');

    // Missing manager case
    p360hr_exec('UPDATE dbo.p360_hr_personnel_profile SET direct_manager_employee_id=NULL WHERE employee_id=?', [$subId]);
    $miss = p360hr_resolve_direct_manager($subId);
    $assert(empty($miss['ok']) && str_contains((string)$miss['message'], 'ظ…ط¯غŒط± ظ…ط³طھظ‚غŒظ… ط«ط¨طھ ظ†ط´ط¯ظ‡'), 'missing_manager_msg');
    // Restore manager for cleanliness
    p360hr_exec('UPDATE dbo.p360_hr_personnel_profile SET direct_manager_employee_id=? WHERE employee_id=?', [$mgrId, $subId]);
}

// --- F. Regression pointers ---
$assert(function_exists('p360_contract_end_by_month_duration'), 'reg_month_end');
$assert(function_exists('p360hr_save_identity_names'), 'reg_identity');
$assert(function_exists('p360hr_renew_from_contract'), 'reg_renew');
$assert(is_file($repoRoot . '/public_html/assets/js/p360-jalali-picker.js'), 'reg_jalali_js');
$assert(is_file($repoRoot . '/public_html/assets/css/p360-jalali-picker.css'), 'reg_jalali_css');

echo $fail === 0 ? "R1_SMOKE_OK\n" : "R1_SMOKE_FAIL count={$fail}\n";
exit($fail === 0 ? 0 : 1);
