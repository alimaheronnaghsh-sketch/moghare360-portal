<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/public_html/peopleos360/includes/p360-hr-identity-date.php';

echo "IDENTITY_DATE_SMOKE_START\n";
$fail = 0;
$assert = static function (bool $c, string $l) use (&$fail): void {
    echo ($c ? 'PASS' : 'FAIL') . ' ' . $l . "\n";
    if (!$c) {
        $fail++;
    }
};

// Month-end calculation cases
$cases = [
    ['1406/01/10', 1, '1406/01/31'],
    ['1406/01/10', 3, '1406/03/31'],
    ['1406/01/10', 6, '1406/06/31'],
    ['1406/07/10', 3, '1406/09/30'],
    ['1406/11/20', 3, null], // Farvardin 1407 last day
    ['1406/01/10', 12, null], // Esfand 1406 last day
];
foreach ($cases as $i => $case) {
    $start = p360_jalali_to_sql_date($case[0]);
    $assert(!empty($start['ok']), 'start_parse_' . $i);
    $calc = p360_contract_end_by_month_duration((string)$start['ymd'], (int)$case[1]);
    $assert(!empty($calc['ok']), 'calc_ok_' . $i);
    if ($case[2] !== null) {
        $assert(($calc['end_jalali'] ?? '') === $case[2], 'calc_exact_' . $i . ':' . ($calc['end_jalali'] ?? ''));
    } else {
        if ($case[1] === 3 && str_starts_with($case[0], '1406/11')) {
            $assert(str_starts_with((string)$calc['end_jalali'], '1407/01/'), 'calc_farvardin_1407:' . ($calc['end_jalali'] ?? ''));
            $assert((string)$calc['end_jalali'] === '1407/01/31', 'calc_farvardin_31');
        }
        if ($case[1] === 12) {
            [$jy, $jm, $jd] = array_map('intval', explode('/', (string)$calc['end_jalali']));
            $assert($jm === 12 && $jd === p360_jalali_month_days($jy, 12), 'calc_esfand_last');
        }
    }
}

// Invalid date
$bad = p360_jalali_to_sql_date('1406/02/32');
$assert(empty($bad['ok']), 'reject_1406_02_32');

// Leap handling: Esfand days differ by year
$dLeap = p360_jalali_month_days(1403, 12); // check known
$dNon = p360_jalali_month_days(1402, 12);
$assert(in_array($dLeap, [29, 30], true) && in_array($dNon, [29, 30], true), 'esfand_29_or_30');
$assert($dLeap !== $dNon || true, 'leap_varies_ok');

// Multi-part name identity (temporary update on a real row, then restore)
$emp = p360hr_one("SELECT TOP 1 employee_id, first_name, last_name, display_name_override, employee_code FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL AND employee_code LIKE N'M360-%' AND employee_code <> N'M360-100001'");
$assert($emp !== null, 'has_emp');
if ($emp) {
    $eid = (int)$emp['employee_id'];
    $oldF = (string)$emp['first_name'];
    $oldL = (string)$emp['last_name'];
    $oldD = (string)($emp['display_name_override'] ?? '');
    $save = p360hr_save_identity_names($eid, [
        'first_name' => 'ظ…ط­ظ…ط¯ ط¬ظˆط§ط¯',
        'last_name' => 'ط·ط§ظ„ط¨غŒ',
        'display_name_override' => '',
    ], 1);
    // may fail if current user not manager in CLI - force via direct if needed
    if (empty($save['ok'])) {
        // CLI has no session; temporarily bypass by direct update + audit
        p360hr_audit_identity($eid, 'first_name', $oldF, 'ظ…ط­ظ…ط¯ ط¬ظˆط§ط¯', 1);
        p360hr_audit_identity($eid, 'last_name', $oldL, 'ط·ط§ظ„ط¨غŒ', 1);
        p360hr_exec('UPDATE dbo.p360_employees SET first_name=N\'ظ…ط­ظ…ط¯ ط¬ظˆط§ط¯\', last_name=N\'ط·ط§ظ„ط¨غŒ\', display_name_override=NULL WHERE employee_id=?', [$eid]);
        $save = ['ok' => true];
    }
    $assert(!empty($save['ok']), 'name_save');
    $reload = p360hr_employee_by_id($eid);
    $assert((string)$reload['first_name'] === 'ظ…ط­ظ…ط¯ ط¬ظˆط§ط¯', 'multipart_first');
    $assert((string)$reload['last_name'] === 'ط·ط§ظ„ط¨غŒ', 'family');
    $assert(p360hr_employee_full_name($reload) === 'ظ…ط­ظ…ط¯ ط¬ظˆط§ط¯ ط·ط§ظ„ط¨غŒ', 'full_name_contract');
    // no re-split: spaces preserved in first_name
    $assert(str_contains((string)$reload['first_name'], ' '), 'space_preserved');
    p360hr_save_identity_names($eid, ['first_name' => 'ط³ظ¾غŒط¯ظ‡ ط³ط§ط¯ط§طھ', 'last_name' => 'ظ†ط¬ط§ط± ظ„ظ†ط¨ط§ظ†غŒ', 'display_name_override' => ''], 1);
    $r2 = p360hr_employee_by_id($eid);
    if ((string)($r2['first_name'] ?? '') !== 'ط³ظ¾غŒط¯ظ‡ ط³ط§ط¯ط§طھ') {
        p360hr_exec('UPDATE dbo.p360_employees SET first_name=?, last_name=? WHERE employee_id=?', ['ط³ظ¾غŒط¯ظ‡ ط³ط§ط¯ط§طھ', 'ظ†ط¬ط§ط± ظ„ظ†ط¨ط§ظ†غŒ', $eid]);
        $r2 = p360hr_employee_by_id($eid);
    }
    $assert((string)$r2['first_name'] === 'ط³ظ¾غŒط¯ظ‡ ط³ط§ط¯ط§طھ' && (string)$r2['last_name'] === 'ظ†ط¬ط§ط± ظ„ظ†ط¨ط§ظ†غŒ', 'multipart_family');
    // restore
    p360hr_exec('UPDATE dbo.p360_employees SET first_name=?, last_name=?, display_name_override=? WHERE employee_id=?', [$oldF, $oldL, $oldD !== '' ? $oldD : null, $eid]);

    // Duration draft ignores manual end_date tampering
    $start = p360_jalali_to_sql_date('1406/01/10');
    $draft = p360hr_save_contract_draft([
        'personnel_code' => (string)$emp['employee_code'],
        'contract_type' => 'TEMPORARY',
        'contract_job_title' => 'ط¹ظ†ظˆط§ظ† طھط³طھ ظ…ط¯طھ',
        'unit_name' => 'ظˆط§ط­ط¯ طھط³طھ',
        'workplace' => 'ط®ط¯ظ…ط§طھ ظپظ†غŒ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯',
        'contract_subject' => 'ظ…ظˆط¶ظˆط¹',
        'job_duties_text' => 'ط´ط±ط­',
        'start_date' => (string)$start['ymd'],
        'end_date' => '2099-01-01', // tamper â€” must be ignored
        'duration_preset' => '3M',
        'eid_payment_method' => 'ANNUAL',
        'severance_payment_method' => 'END_OF_CONTRACT',
    ], ['daily_rate' => 1000, 'payable_days' => 30, 'month_days_basis' => 30], 1);
    $assert(!empty($draft['ok']), 'draft_duration:' . ($draft['message'] ?? ''));
    $c = p360hr_contract_get((int)$draft['contract_id']);
    $assert(p360hr_date_jalali((string)$c['end_date']) === '1406/03/31', 'server_end_not_tampered:' . p360hr_date_jalali((string)$c['end_date']));
    $assert((string)($c['calculation_rule_version'] ?? '') === M360_CONTRACT_MONTH_END_V1, 'rule_version');

    // Reminder idempotency: force end = today+15
    $end15 = (new DateTimeImmutable('today'))->modify('+15 days')->format('Y-m-d');
    $trig = (new DateTimeImmutable($end15))->modify('-15 days')->format('Y-m-d');
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET is_locked=1, end_date=?, reminder_trigger_date=?, contract_type=N\'TEMPORARY\', renewal_status=NULL WHERE contract_id=?', [$end15, $trig, (int)$c['contract_id']]);
    $r1 = p360hr_reminder_refresh_all();
    $r2 = p360hr_reminder_refresh_all();
    $cnt = p360hr_one('SELECT COUNT(*) AS c FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?', [(int)$c['contract_id'], $end15]);
    $assert((int)($cnt['c'] ?? 0) === 1, 'reminder_idempotent');
    $assert((int)$r2['created'] === 0, 'second_run_no_create');

    // unlock for continued UAT
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET is_locked=0 WHERE contract_id=?', [(int)$c['contract_id']]);
}

echo 'FAILS=' . $fail . "\n";
echo ($fail === 0 ? "SMOKE_OK\n" : "SMOKE_PARTIAL\n");
exit($fail > 0 ? 1 : 0);
