<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * Controlled smoke tests for P360 dynamic contract engine.
 * No commit. Uses moghare360_ERP only.
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/public_html/peopleos360/includes/p360-hr-contract-engine.php';

echo "SMOKE_START\n";
p360hr_seed_contract_template();

$fail = 0;
$assert = static function (bool $cond, string $label) use (&$fail): void {
    echo ($cond ? 'PASS' : 'FAIL') . ' ' . $label . "\n";
    if (!$cond) {
        $fail++;
    }
};

// B: no sample hardcoding in new engine files
$scanFiles = [
    $repoRoot . '/public_html/peopleos360/includes/p360-hr-contract-engine.php',
    $repoRoot . '/public_html/peopleos360/includes/p360-hr-contract-template.php',
    $repoRoot . '/public_html/peopleos360/includes/p360-hr-wage-engine.php',
    $repoRoot . '/public_html/peopleos360/hr-contract-register.php',
    $repoRoot . '/public_html/peopleos360/hr-personnel-form.php',
];
$banned = ['ط±غŒط­ط§ظ†ظ‡ ط³ط§ط¯ط§طھ طھظˆظپغŒظ‚', 'طھط£ظ…غŒظ†â€Œع©ظ†ظ†ط¯ظ‡ ظ‚ط·ط¹ط§طھ ظˆ ظ‡ظ…ط§ظ‡ظ†ع¯غŒ ط¨ط§ ظ…ط´طھط±غŒ', 'ط®ط¯ظ…ط§طھ ظپط±ظˆط´ع¯ط§ظ‡غŒ ظˆ ظ†ط¸ط§ظپطھ ظ‚ظپط³ظ‡â€Œظ‡ط§ ظˆ ع†غŒط¯ظ…ط§ظ†'];
foreach ($scanFiles as $f) {
    $txt = is_file($f) ? (string)file_get_contents($f) : '';
    foreach ($banned as $b) {
        $assert(strpos($txt, $b) === false, 'no_hardcode:' . basename($f) . ':' . mb_substr($b, 0, 12));
    }
}

// Template 18 clauses
$body = p360hr_active_template_body();
    $assert(strpos($body, 'ظ†ط³ط® ظ‚ط±ط§ط±ط¯ط§ط¯') !== false, 'clause18_present');
    $assert(strpos($body, 'ط­ظ‚â€Œط§ظ„ط³ط¹غŒ') !== false || strpos($body, 'ط­ظ‚ط§ظ„ط³ط¹غŒ') !== false || preg_match('/ط­ظ‚.ط§ظ„ط³ط¹غŒ/u', $body), 'clause9_title');
    $assert(strpos($body, '{{WAGE_TABLE_HTML}}') !== false, 'wage_table_placeholder');
$assert(strpos($body, '{{EMPLOYEE_FULL_NAME}}') !== false, 'dynamic_employee_placeholder');

// Wage formulas differ by type
$base = ['month_days_basis' => 30, 'housing_monthly' => 1000000, 'eid_payment_method' => 'ANNUAL', 'severance_payment_method' => 'END_OF_CONTRACT'];
$h = p360hr_calculate_wage_table('HOURLY', $base + ['hourly_rate' => 100000, 'approved_hours' => 176]);
$t = p360hr_calculate_wage_table('TEMPORARY', $base + ['daily_rate' => 500000, 'payable_days' => 30]);
$p = p360hr_calculate_wage_table('PERMANENT', $base + ['monthly_fixed' => 15000000]);
$s = p360hr_calculate_wage_table('SPECIFIC_WORK', $base + ['specific_total' => 20000000]);
$c = p360hr_calculate_wage_table('CONTRACTUAL', $base + ['contractual_total' => 40000000, 'contractual_stages' => 4, 'contractual_stage_amount' => 10000000]);
$assert(count($h['rows']) === 11, 'wage_rows_11_hourly');
$assert(count($t['rows']) === 11, 'wage_rows_11_temporary');
$assert($h['total_monthly'] !== $t['total_monthly'], 'hourly_ne_temporary');
$assert($p['total_monthly'] !== $s['total_monthly'], 'permanent_ne_specific');
$assert($c['model'] === 'CONTRACTUAL', 'contractual_model');
$html = p360hr_wage_table_html($t['rows'], $t['total_daily'], $t['total_monthly']);
$assert(strpos($html, 'ط±ط¯غŒظپ') !== false && strpos($html, 'ظ…ظˆط¶ظˆط¹') !== false && strpos($html, 'ط§ط·ظ„ط§ط¹ط§طھ ط§ط¶ط§ظپظ‡') !== false, 'wage_table_columns');
$assert(strpos($html, 'ط¬ظ…ط¹ ع©ظ„ ط­ظ‚ظˆظ‚ ظˆ ظ…ط²ط§غŒط§') !== false, 'wage_table_total_row');

// Auto-fill from real personnel
$emp = p360hr_one('SELECT TOP 1 employee_code, first_name, last_name FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL AND employee_code LIKE N\'M360-%\' AND employee_code <> N\'M360-100001\' ORDER BY employee_id');
$assert($emp !== null, 'has_personnel_row');
if ($emp) {
    $bundle = p360hr_load_personnel_bundle((string)$emp['employee_code']);
    $assert(!empty($bundle['ok']), 'autofill_ok');
    $assert(trim((string)($bundle['employee']['first_name'] ?? '')) !== '', 'autofill_name');
    $assert((string)($bundle['employee']['employee_code'] ?? '') === (string)$emp['employee_code'], 'autofill_code_match');
    $name = trim(($bundle['employee']['first_name'] ?? '') . ' ' . ($bundle['employee']['last_name'] ?? ''));
    $assert($name !== 'ط±غŒط­ط§ظ†ظ‡ ط³ط§ط¯ط§طھ طھظˆظپغŒظ‚', 'autofill_not_sample_name');

    // Ensure mobile for OTP stage (personnel profile / master) â€” not a sample person hardcode.
    $eid = (int)$bundle['employee']['employee_id'];
    $mobileNow = trim((string)($bundle['employee']['mobile'] ?? ''));
    if ($mobileNow === '') {
        p360hr_exec('UPDATE dbo.p360_employees SET mobile=? WHERE employee_id=? AND (mobile IS NULL OR mobile=N\'\')', ['09120000000', $eid]);
        $bundle = p360hr_load_personnel_bundle((string)$emp['employee_code']);
    }

    $actor = 1;
    $save = p360hr_save_contract_draft([
        'personnel_code' => (string)$emp['employee_code'],
        'contract_type' => 'TEMPORARY',
        'contract_job_title' => (string)($bundle['employee']['job_title'] ?? 'ط¹ظ†ظˆط§ظ† طھط³طھ'),
        'unit_name' => (string)($bundle['employee']['unit_name'] ?? 'ظˆط§ط­ط¯ طھط³طھ'),
        'direct_supervisor' => 'ط³ط±ظ¾ط±ط³طھ طھط³طھ',
        'workplace' => 'ط®ط¯ظ…ط§طھ ظپظ†غŒ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯',
        'contract_subject' => 'ظ…ظˆط¶ظˆط¹ طھط³طھ ظ‚ط±ط§ط±ط¯ط§ط¯',
        'job_duties_text' => 'ط´ط±ط­ ظˆط¸ط§غŒظپ طھط³طھ ع©ظ†طھط±ظ„â€Œط´ط¯ظ‡',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+6 months')),
        'duration_text' => 'ط´ط´ ظ…ط§ظ‡',
        'duration_preset' => '6M',
        'eid_payment_method' => 'MONTHLY',
        'severance_payment_method' => 'MONTHLY',
    ], [
        'daily_rate' => 800000,
        'payable_days' => 30,
        'month_days_basis' => 30,
        'housing_monthly' => 900000,
        'food_monthly' => 500000,
        'eid_monthly' => 200000,
        'severance_monthly' => 100000,
        'eid_payment_method' => 'MONTHLY',
        'severance_payment_method' => 'MONTHLY',
    ], $actor);
    $assert(!empty($save['ok']), 'draft_save:' . ($save['message'] ?? ''));
    $cid = (int)($save['contract_id'] ?? 0);
    $assert($cid > 0, 'contract_id');
    $rows = p360hr_contract_wage_rows($cid);
    $assert(count($rows) === 11, 'persisted_wage_rows');
    $eidRow = null;
    foreach ($rows as $r) {
        if ($r['component_code'] === 'EID_BONUS') {
            $eidRow = $r;
        }
    }
    $assert($eidRow !== null && (float)$eidRow['monthly_amount'] > 0, 'monthly_eid_in_table');

    // Accept path soft (may fail without disciplinary file initially â€” engine creates txt)
    $acc = p360hr_accept_contract($cid, $actor, '127.0.0.1', 'smoke-agent');
    $assert(!empty($acc['ok']), 'accept:' . ($acc['message'] ?? ''));
    $otp = p360hr_otp_issue($cid, 120);
    $assert(!empty($otp['ok']), 'otp_issue:' . ($otp['message'] ?? ''));
    $code = (string)($otp['otp_dev'] ?? '');
    if ($code === '') {
        // Force local style: cannot read plaintext; mark partial
        echo "INFO otp_dev_unavailable_outside_http_local\n";
    } else {
        $ver = p360hr_otp_verify($cid, $code);
        $assert(!empty($ver['ok']), 'otp_verify');
    }

    // Change invalidation
    $beforeHash = (string)(p360hr_contract_get($cid)['contract_hash'] ?? '');
    $save2 = p360hr_save_contract_draft([
        'contract_id' => $cid,
        'personnel_code' => (string)$emp['employee_code'],
        'contract_type' => 'TEMPORARY',
        'contract_job_title' => 'ط¹ظ†ظˆط§ظ† طھط؛غŒغŒط±â€ŒغŒط§ظپطھظ‡ طھط³طھ',
        'unit_name' => (string)($bundle['employee']['unit_name'] ?? 'ظˆط§ط­ط¯ طھط³طھ'),
        'workplace' => 'ط®ط¯ظ…ط§طھ ظپظ†غŒ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯',
        'contract_subject' => 'ظ…ظˆط¶ظˆط¹ طھط³طھ ظ‚ط±ط§ط±ط¯ط§ط¯',
        'job_duties_text' => 'ط´ط±ط­ ظˆط¸ط§غŒظپ طھط³طھ ع©ظ†طھط±ظ„â€Œط´ط¯ظ‡',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+6 months')),
        'duration_preset' => '6M',
        'eid_payment_method' => 'MONTHLY',
        'severance_payment_method' => 'MONTHLY',
    ], [
        'daily_rate' => 850000,
        'payable_days' => 30,
        'month_days_basis' => 30,
        'housing_monthly' => 900000,
        'eid_monthly' => 200000,
        'severance_monthly' => 100000,
        'eid_payment_method' => 'MONTHLY',
        'severance_payment_method' => 'MONTHLY',
    ], $actor);
    $after = p360hr_contract_get($cid);
    $assert((int)($after['acceptance_checked'] ?? 1) === 0, 'change_invalidates_acceptance');
    $assert((string)($after['contract_hash'] ?? '') !== $beforeHash || $beforeHash === '', 'hash_changed_or_set');

    // PDF draft
    $pdf = p360hr_generate_pdf($cid, false);
    $assert(!empty($pdf['ok']), 'pdf_draft:' . ($pdf['message'] ?? ''));
    $assert(!empty($pdf['hash']), 'pdf_sha256');
    $assert(($pdf['font'] ?? '') === 'vazirmatn' || ($pdf['font'] ?? '') === 'dejavusans', 'pdf_font');

    // Benefit payment duplicate prevention (requires locked â€” simulate by force lock for test payment only on unlocked skip)
    // Create locked-like insert via register after temporary lock flag for test
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET is_locked=1 WHERE contract_id=?', [$cid]);
    $pay1 = p360hr_register_benefit_payment($cid, 'EID_BONUS', '1405-05', 200000.0, 'smoke');
    $pay2 = p360hr_register_benefit_payment($cid, 'EID_BONUS', '1405-05', 200000.0, 'smoke-dup');
    $assert(!empty($pay1['ok']), 'benefit_pay_1');
    $assert(empty($pay2['ok']), 'benefit_dup_blocked');
    $sett = p360hr_benefit_settlement_remaining($cid, 'EID_BONUS', 2400000.0);
    $assert($sett['previous_paid'] === 200000.0, 'settlement_paid');
    $assert($sett['remaining_payable'] === 2200000.0, 'settlement_remaining');
    // unlock again so UAT can continue editing if needed
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET is_locked=0 WHERE contract_id=?', [$cid]);
    echo "SMOKE_CONTRACT_ID={$cid}\n";
}

$employer = p360hr_employer_active();
$assert($employer !== null, 'employer_profile_exists');
$assert((string)($employer['trade_name'] ?? '') === 'ظ…ط¬ظ…ظˆط¹ظ‡ ظ…ظ‚ط§ط±ظ‡ ظ…ظˆطھظˆط±ط²' || (string)($employer['trade_name'] ?? '') !== '', 'employer_trade');

echo 'SMOKE_FAILS=' . $fail . "\n";
echo ($fail === 0 ? "SMOKE_OK\n" : "SMOKE_PARTIAL\n");
exit($fail > 0 ? 1 : 0);
