<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

pilot_session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    pilot_error('خطا', 'فقط درخواست POST مجاز است.');
}

$csrfAction = 'pilot_scenario_create';
$csrfReason = pilot_csrf_validate_detail($csrfAction);
if ($csrfReason !== null) {
    pilot_csrf_failure_redirect('erp-pilot-scenario-builder.php', $csrfReason);
}

$customerName = trim((string)($_POST['customer_name'] ?? ''));
$mobile = pilot_normalize_mobile($_POST['mobile'] ?? '');
$plate = trim((string)($_POST['vehicle_plate'] ?? ''));
$brandModel = trim((string)($_POST['vehicle_brand_model'] ?? ''));
$contractType = trim((string)($_POST['contract_type'] ?? ''));
$authMode = trim((string)($_POST['authorization_mode'] ?? ''));
$jobDesc = trim((string)($_POST['jobcard_service_description'] ?? ''));
$partRequired = ((string)($_POST['part_required'] ?? '0')) === '1' ? 1 : 0;
$payRaw = trim((string)($_POST['payment_preview_amount'] ?? '0'));
$crmExpected = ((string)($_POST['crm_followup_expected'] ?? '0')) === '1' ? 1 : 0;
$hrSample = ((string)($_POST['hr_attendance_sample'] ?? '0')) === '1' ? 1 : 0;

if ($customerName === '') {
    pilot_error('خطای اعتبارسنجی', 'نام مشتری الزامی است.');
}
if ($payRaw !== '' && !is_numeric($payRaw)) {
    pilot_error('خطای اعتبارسنجی', 'مبلغ پیش‌نمایش مالی باید عددی باشد.');
}
$payAmount = is_numeric($payRaw) ? (float)$payRaw : 0.0;
if ($payAmount < 0) {
    pilot_error('خطای اعتبارسنجی', 'مبلغ نمی‌تواند منفی باشد.');
}

$c = false;
$scenarioId = 0;
$dup = false;

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('اتصال برقرار نشد.');
    }
    pilot_require_auth($c, 'pilot.scenario.write');

    if (!pilot_table_exists($c, 'erp_soft_run_pilot_scenarios')) {
        throw new RuntimeException('جداول Pilot یافت نشد. SQL را اجرا کنید.');
    }

    $dup = pilot_find_duplicate_scenario($c, $customerName, $mobile, $plate);
    $pilot = pilot_get_active_pilot($c);
    $pilotId = $pilot !== null ? (int)($pilot['pilot_id'] ?? 0) : null;
    $scenarioCode = pilot_generate_code('SCN');

    $ok = pilot_execute(
        $c,
        'INSERT INTO dbo.erp_soft_run_pilot_scenarios
         (pilot_id, scenario_code, customer_name, mobile, vehicle_plate, vehicle_brand_model,
          contract_type, authorization_mode, jobcard_service_description, part_required,
          payment_preview_amount, crm_followup_expected, hr_attendance_sample, scenario_status, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,N\'READY\',?)',
        [
            $pilotId, $scenarioCode, $customerName, $mobile, $plate, $brandModel,
            $contractType, $authMode, $jobDesc, $partRequired,
            $payAmount, $crmExpected, $hrSample, pilot_safe_current_user(),
        ]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت سناریو انجام نشد.');
    }

    $newId = pilot_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT)');
    $scenarioId = ($newId !== null && is_numeric($newId)) ? (int)$newId : 0;
    if ($scenarioId < 1) {
        throw new RuntimeException('شناسه سناریو دریافت نشد.');
    }

    $scenario = pilot_get_scenario($c, $scenarioId);
    if ($scenario === null || !pilot_create_initial_flow_snapshot($c, $scenarioId, $scenario)) {
        throw new RuntimeException('ایجاد flow snapshot انجام نشد.');
    }

    pilot_insert_history($c, 'scenario', $scenarioId, 'CREATE', 'Pilot scenario created: ' . $scenarioCode, null, $customerName);
    pilot_csrf_rotate($csrfAction);
} catch (Throwable) {
    pilot_error('خطا', 'ثبت سناریوی Pilot انجام نشد.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$url = 'erp-pilot-flow-viewer.php?scenario_id=' . $scenarioId . '&ok=scenario_ok';
if ($dup) {
    $url .= '&dup=1';
}
pilot_safe_redirect($url);
