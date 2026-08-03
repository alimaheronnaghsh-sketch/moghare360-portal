<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-identity-date.php';
require_once __DIR__ . '/includes/p360-hr-jalali-ui.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

p360hr_seed_contract_template();

$tabs = [
    'select' => 'انتخاب پرسنل',
    'profile' => 'مشخصات پرسنلی',
    'type' => 'نوع و مدت',
    'job' => 'شغل و وظایف',
    'place' => 'محل و ساعات',
    'wage' => 'فرم محاسبه حقوق',
    'clause9' => 'جدول حق‌السعی',
    'bank' => 'بانک و ضمانت',
    'text' => 'متن قرارداد',
    'attach' => 'پیوست‌ها',
    'review' => 'بررسی نهایی',
    'accept' => 'تأیید و OTP',
    'sign' => 'امضا',
    'pdf' => 'PDF و تاریخچه',
];
$tab = strtolower(trim((string)($_GET['tab'] ?? 'select')));
if (!isset($tabs[$tab])) {
    $tab = 'select';
}

$code = trim((string)($_REQUEST['personnel_code'] ?? ''));
$contractId = (int)($_REQUEST['contract_id'] ?? 0);
$msg = null;
$ok = false;
$devOtp = null;

$bundle = $code !== '' ? p360hr_load_personnel_bundle($code) : null;
$contract = $contractId > 0 ? p360hr_contract_get($contractId) : null;
if ($contract && $code === '') {
    $code = (string)$contract['personnel_code'];
    $bundle = p360hr_load_personnel_bundle($code);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    $action = (string)($_POST['action'] ?? '');
    if (!erp_csrf_validate_token('p360hr_contract', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $actor = erp_auth_current_user_id() ?? 0;
        if ($action === 'save_draft') {
            $fields = $_POST;
            $wageInput = [
                'hourly_rate' => (float)($_POST['hourly_rate'] ?? 0),
                'daily_rate' => (float)($_POST['daily_rate'] ?? 0),
                'monthly_fixed' => (float)($_POST['monthly_fixed'] ?? 0),
                'month_days_basis' => (int)($_POST['month_days_basis'] ?? 30),
                'payable_days' => (float)($_POST['payable_days'] ?? 30),
                'required_hours' => (float)($_POST['required_hours'] ?? 0),
                'approved_hours' => (float)($_POST['approved_hours'] ?? 0),
                'housing_monthly' => (float)($_POST['housing_monthly'] ?? 0),
                'food_monthly' => (float)($_POST['food_monthly'] ?? 0),
                'marriage_monthly' => (float)($_POST['marriage_monthly'] ?? 0),
                'child_monthly' => (float)($_POST['child_monthly'] ?? 0),
                'seniority_monthly' => (float)($_POST['seniority_monthly'] ?? 0),
                'eid_monthly' => (float)($_POST['eid_monthly'] ?? 0),
                'severance_monthly' => (float)($_POST['severance_monthly'] ?? 0),
                'transport_monthly' => (float)($_POST['transport_monthly'] ?? 0),
                'technical_monthly' => (float)($_POST['technical_monthly'] ?? 0),
                'other_monthly' => (float)($_POST['other_monthly'] ?? 0),
                'specific_total' => (float)($_POST['specific_total'] ?? 0),
                'contractual_total' => (float)($_POST['contractual_total'] ?? 0),
                'contractual_stages' => (int)($_POST['contractual_stages'] ?? 1),
                'contractual_stage_amount' => (float)($_POST['contractual_stage_amount'] ?? 0),
            ];
            $res = p360hr_save_contract_draft($fields, $wageInput, $actor);
            $ok = !empty($res['ok']);
            $msg = (string)($res['message'] ?? '');
            if ($ok) {
                $contractId = (int)$res['contract_id'];
                $contract = p360hr_contract_get($contractId);
                if (!empty($res['unresolved'])) {
                    $msg .= ' — کمبودها: ' . implode('، ', p360hr_unresolved_placeholder_labels($res['unresolved']));
                }
            }
        } elseif ($action === 'set_stage') {
            $res = p360hr_set_stage($contractId, (string)($_POST['stage_code'] ?? 'DRAFT'));
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'issue_otp') {
            $res = p360hr_otp_issue($contractId);
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            if (!empty($res['otp_dev'])) {
                $devOtp = (string)$res['otp_dev'];
            }
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'verify_otp') {
            $res = p360hr_otp_verify($contractId, (string)($_POST['otp_code'] ?? ''));
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'accept') {
            $res = p360hr_accept_contract($contractId, $actor, (string)($_SERVER['REMOTE_ADDR'] ?? ''), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'sign_employee' || $action === 'sign_employer') {
            $role = $action === 'sign_employee' ? 'employee' : 'employer';
            $res = p360hr_save_signature_png($contractId, $role, (string)($_POST['signature_data'] ?? ''));
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'finalize') {
            $res = p360hr_finalize_contract($contractId, $actor);
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            $contract = p360hr_contract_get($contractId);
        } elseif ($action === 'draft_pdf') {
            $res = p360hr_generate_pdf($contractId, false);
            $ok = !empty($res['ok']);
            $msg = $ok ? ('پیش‌نویس PDF آماده شد. SHA256=' . ($res['hash'] ?? '')) : (string)$res['message'];
        }
    }
}

$emp = ($bundle && !empty($bundle['ok'])) ? $bundle['employee'] : null;
$profile = ($bundle && !empty($bundle['ok'])) ? $bundle['profile'] : [];
$wageRows = $contractId > 0 ? p360hr_contract_wage_rows($contractId) : [];
$wageSnap = [];
if ($contract && !empty($contract['wage_snapshot_json'])) {
    $decoded = json_decode((string)$contract['wage_snapshot_json'], true);
    if (is_array($decoded)) {
        $wageSnap = $decoded['inputs'] ?? [];
    }
}

p360hr_layout_start('ثبت قرارداد پرسنلی');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
if ($devOtp !== null) {
    echo '<div class="m360-alert">کد UAT محلی (فقط 127.0.0.1): ' . p360hr_h($devOtp) . '</div>';
}

echo '<nav class="p360hr-tabs">';
foreach ($tabs as $k => $label) {
    $q = http_build_query(['tab' => $k, 'personnel_code' => $code, 'contract_id' => $contractId ?: null]);
    $cls = $k === $tab ? ' active' : '';
    echo '<a class="' . trim($cls) . '" href="?' . $q . '">' . p360hr_h($label) . '</a>';
}
echo '</nav>';

$csrf = erp_csrf_create_token('p360hr_contract');
$h = static function ($v): string {
    return p360hr_h((string)$v);
};

if ($tab === 'select') {
    echo '<form method="get" class="m360-card"><label>کد پرسنلی (اولین فیلد)<br>';
    echo '<input name="personnel_code" value="' . $h($code) . '" required autofocus style="min-width:240px"></label>';
    echo '<input type="hidden" name="tab" value="profile">';
    echo '<button class="m360-btn" type="submit">یافتن پرسنل</button></form>';
    if ($bundle && empty($bundle['ok'])) {
        echo '<div class="m360-alert m360-alert-err">' . $h($bundle['message']) . '</div>';
    }
    p360hr_layout_end();
    exit;
}

if (!$emp) {
    echo '<div class="m360-alert m360-alert-err">ابتدا از تب «انتخاب پرسنل» کد پرسنلی را بارگذاری کنید.</div>';
    p360hr_layout_end();
    exit;
}

echo '<div class="p360hr-meta">';
echo '<div><b>کد پرسنلی</b>' . $h($emp['employee_code']) . '</div>';
echo '<div><b>نام</b>' . $h(trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''))) . '</div>';
echo '<div><b>واحد</b>' . $h($emp['unit_name'] ?? '') . '</div>';
echo '<div><b>شغل سازمانی</b>' . $h($emp['job_title'] ?? '') . '</div>';
if ($contract) {
    echo '<div><b>قرارداد</b>#' . (int)$contract['contract_id'] . ' / v' . (int)$contract['contract_version'] . '</div>';
    echo '<div><b>مرحله</b>' . $h(p360hr_stage_fa((string)$contract['stage_code'])) . '</div>';
}
echo '</div>';

if (!empty($bundle['missing'])) {
    echo '<div class="m360-alert m360-alert-err">اخطار نقص داده: ' . $h(implode('، ', $bundle['missing'])) . '</div>';
}

if ($tab === 'profile') {
    echo '<section class="m360-card">';
    echo '<p>نام و نام خانوادگی: <b>' . $h(p360hr_employee_full_name($emp)) . '</b> (از پرونده پرسنلی — بدون تایپ مجدد)</p>';
    echo '<p>کد ملی: ' . $h($emp['national_code'] ?? '—') . '</p>';
    echo '<p>تاریخ تولد: ' . $h(p360hr_date_jalali((string)($profile['birth_date'] ?? ''))) . '</p>';
    echo '<p>شماره شناسنامه: ' . $h($profile['birth_certificate_no'] ?? '—') . '</p>';
    echo '<p>موبایل: ' . $h($emp['mobile'] ?? '—') . '</p>';
    echo '<p>نشانی: ' . $h($profile['address_full'] ?? '—') . '</p>';
    echo '<p>تحصیلات: ' . $h(trim(($profile['education_degree'] ?? '') . ' ' . ($profile['education_field'] ?? ''))) . '</p>';
    echo '<p>تأهل / فرزند: ' . $h($profile['marital_status'] ?? '—') . ' / ' . $h($profile['child_count'] ?? '0') . '</p>';
    echo '<p>عکس: ' . (trim((string)($emp['personnel_photo_path'] ?? '')) !== '' ? 'نمایش‌پذیر' : 'ثبت نشده') . '</p>';
    echo '<p><a href="hr-personnel-form.php?personnel_code=' . rawurlencode($code) . '">ویرایش فرم اطلاعات پرسنلی</a></p>';
    echo '</section>';
    p360hr_layout_end();
    exit;
}

// Shared draft form for editable tabs
$showForm = in_array($tab, ['type', 'job', 'place', 'wage', 'bank', 'review'], true);
if ($showForm) {
    echo '<form method="post" class="m360-card">';
    echo '<input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
    echo '<input type="hidden" name="action" value="save_draft">';
    echo '<input type="hidden" name="personnel_code" value="' . $h($code) . '">';
    echo '<input type="hidden" name="contract_id" value="' . (int)$contractId . '">';
}

if ($tab === 'type' && $showForm) {
    $ctype = (string)($contract['contract_type'] ?? 'TEMPORARY');
    $preset = (string)($contract['duration_preset'] ?? '3M');
    $daysLeft = !empty($contract['end_date']) ? p360_days_remaining_until((string)$contract['end_date']) : null;
    echo '<h3>نوع و مدت قرارداد</h3>';
    echo '<label>نوع قرارداد<br><select name="contract_type" id="ctype">';
    foreach (['PERMANENT' => 'دائم', 'TEMPORARY' => 'موقت', 'HOURLY' => 'ساعتی', 'SPECIFIC_WORK' => 'کار معین', 'CONTRACTUAL' => 'پیمانی'] as $k => $fa) {
        echo '<option value="' . $k . '"' . ($ctype === $k ? ' selected' : '') . '>' . $fa . '</option>';
    }
    echo '</select></label>';
    echo '<p>تاریخ انعقاد (سیستمی، فقط‌خواندنی): <b>' . $h(p360hr_date_jalali((string)($contract['created_date'] ?? date('Y-m-d')))) . '</b></p>';
    p360hr_jalali_date_field('start_date', isset($contract['start_date']) ? (string)$contract['start_date'] : null, 'تاریخ شروع قرارداد', ['required' => true, 'allow_clear' => false]);
    echo '<div id="durationBox">';
    echo '<label>مدت قرارداد<br><select name="duration_preset" id="durPreset">';
    foreach (['1M' => 'یک ماه', '2M' => 'دو ماه', '3M' => 'سه ماه', '6M' => 'شش ماه', '9M' => 'نه ماه', '12M' => 'یک سال', 'CUSTOM' => 'مدت سفارشی'] as $k => $fa) {
        echo '<option value="' . $k . '"' . ($preset === $k ? ' selected' : '') . '>' . $fa . '</option>';
    }
    echo '</select></label>';
    echo '<div id="customDur" style="display:' . ($preset === 'CUSTOM' ? 'block' : 'none') . '">';
    echo '<label>واحد<br><select name="duration_unit"><option value="MONTHS"' . ((($contract['duration_unit'] ?? '') === 'YEARS') ? '' : ' selected') . '>ماه</option><option value="YEARS"' . ((($contract['duration_unit'] ?? '') === 'YEARS') ? ' selected' : '') . '>سال</option></select></label>';
    echo '<label>مقدار<br><input type="number" min="1" max="120" name="duration_custom_value" value="' . $h(($contract['duration_unit'] ?? '') === 'YEARS' ? (int)(($contract['duration_months'] ?? 12) / 12) : (int)($contract['duration_months'] ?? 3)) . '"></label>';
    echo '</div></div>';
    echo '<p class="p360hr-warn">تاریخ پایان بر اساس مدت قرارداد و آخرین روز ماه نهایی به‌صورت خودکار محاسبه می‌شود.</p>';
    echo '<div class="p360hr-meta">';
    echo '<div><b>تاریخ پایان محاسبه‌شده</b>' . $h(!empty($contract['end_date']) ? p360hr_date_jalali((string)$contract['end_date']) : '—') . '</div>';
    echo '<div><b>مدت</b>' . $h((string)($contract['duration_text'] ?? '—')) . '</div>';
    echo '<div><b>روزهای باقی‌مانده</b>' . ($daysLeft === null ? '—' : (string)$daysLeft) . '</div>';
    echo '<div><b>وضعیت قرارداد</b>' . $h(p360hr_stage_fa((string)($contract['stage_code'] ?? 'DRAFT'))) . '</div>';
    echo '<div><b>تاریخ ایجاد هشدار تمدید</b>' . $h(!empty($contract['reminder_trigger_date']) ? p360hr_date_jalali((string)$contract['reminder_trigger_date']) : '—') . '</div>';
    echo '<div><b>نسخه قاعده</b>' . $h((string)($contract['calculation_rule_version'] ?? M360_CONTRACT_MONTH_END_V1)) . '</div>';
    echo '</div>';
    echo '<div class="p360hr-meta">';
    echo '<label>عیدی<br><select name="eid_payment_method">';
    foreach (['MONTHLY' => 'ماهانه', 'ANNUAL' => 'سالانه', 'FINAL_SETTLEMENT' => 'در تسویه نهایی', 'LEGAL_SETTING' => 'طبق تنظیمات قانونی/قراردادی'] as $k => $fa) {
        $sel = (($contract['eid_payment_method'] ?? 'ANNUAL') === $k) ? ' selected' : '';
        echo '<option value="' . $k . '"' . $sel . '>' . $fa . '</option>';
    }
    echo '</select></label>';
    echo '<label>سنوات<br><select name="severance_payment_method">';
    foreach (['MONTHLY' => 'ماهانه', 'ANNUAL' => 'سالانه', 'END_OF_CONTRACT' => 'در پایان قرارداد', 'FINAL_SETTLEMENT' => 'در تسویه نهایی'] as $k => $fa) {
        $sel = (($contract['severance_payment_method'] ?? 'END_OF_CONTRACT') === $k) ? ' selected' : '';
        echo '<option value="' . $k . '"' . $sel . '>' . $fa . '</option>';
    }
    echo '</select></label></div>';
    echo '<script>
    (function(){
      var ctype=document.getElementById("ctype"), box=document.getElementById("durationBox"), preset=document.getElementById("durPreset"), custom=document.getElementById("customDur");
      function sync(){ box.style.display = (ctype.value==="PERMANENT") ? "none" : "block"; custom.style.display = (preset.value==="CUSTOM") ? "block" : "none"; }
      ctype.addEventListener("change", sync); preset.addEventListener("change", sync); sync();
    })();
    </script>';
}

if ($tab === 'job' && $showForm) {
    echo '<h3>عنوان شغل و شرح وظایف</h3>';
    echo '<p>عنوان شغل سازمانی (فقط‌خواندنی از پرونده): <b>' . $h($emp['job_title'] ?? '') . '</b></p>';
    echo '<input type="hidden" name="org_job_title" value="' . $h($emp['job_title'] ?? '') . '">';
    echo '<label>عنوان شغل قراردادی<br><input name="contract_job_title" value="' . $h($contract['contract_job_title'] ?? ($emp['job_title'] ?? '')) . '" required style="width:100%"></label>';
    echo '<label><input type="checkbox" name="update_master_job_title" value="1"> به‌روزرسانی عنوان شغل در پرونده پرسنلی</label>';
    echo '<label>واحد<br><input name="unit_name" value="' . $h($contract['unit_name'] ?? ($emp['unit_name'] ?? '')) . '"></label>';
    echo '<label>سرپرست مستقیم<br><input name="direct_supervisor" value="' . $h($contract['direct_supervisor'] ?? ($profile['direct_supervisor'] ?? '')) . '"></label>';
    echo '<label>موضوع قرارداد<br><input name="contract_subject" value="' . $h($contract['contract_subject'] ?? '') . '" required style="width:100%"></label>';
    echo '<label>شرح تکمیلی موضوع<br><textarea name="contract_subject_details" rows="2" style="width:100%">' . $h($contract['contract_subject_details'] ?? '') . '</textarea></label>';
    echo '<label>شرح وظایف<br><textarea name="job_duties_text" rows="4" style="width:100%" required>' . $h($contract['job_duties_text'] ?? '') . '</textarea></label>';
}

if ($tab === 'place' && $showForm) {
    $er = p360hr_employer_active();
    echo '<h3>محل و ساعات کار</h3>';
    echo '<label>محل انجام کار<br><input name="workplace" value="' . $h($contract['workplace'] ?? ($er['default_workplace'] ?? 'خدمات فنی مقاره عابد')) . '" style="width:100%"></label>';
    echo '<pre style="white-space:pre-wrap;background:#f7fbf9;padding:1rem;border-radius:8px">' . $h(p360hr_default_shift_snapshot_text()) . '</pre>';
    echo '<p>نسخه شیفت در ذخیره قرارداد به‌صورت Snapshot ثبت می‌شود.</p>';
    // Keep other required hidden fields from contract if present
    echo '<input type="hidden" name="contract_type" value="' . $h($contract['contract_type'] ?? 'TEMPORARY') . '">';
    echo '<input type="hidden" name="contract_job_title" value="' . $h($contract['contract_job_title'] ?? ($emp['job_title'] ?? '')) . '">';
    echo '<input type="hidden" name="unit_name" value="' . $h($contract['unit_name'] ?? ($emp['unit_name'] ?? '')) . '">';
    echo '<input type="hidden" name="contract_subject" value="' . $h($contract['contract_subject'] ?? '') . '">';
    echo '<input type="hidden" name="job_duties_text" value="' . $h($contract['job_duties_text'] ?? '') . '">';
    echo '<input type="hidden" name="start_date" value="' . $h($contract['start_date'] ?? '') . '">';
    echo '<input type="hidden" name="duration_preset" value="' . $h($contract['duration_preset'] ?? '3M') . '">';
    echo '<input type="hidden" name="duration_unit" value="' . $h($contract['duration_unit'] ?? 'MONTHS') . '">';
    echo '<input type="hidden" name="duration_custom_value" value="' . $h((string)($contract['duration_months'] ?? '3')) . '">';
}

if ($tab === 'wage' && $showForm) {
    $ctype = (string)($contract['contract_type'] ?? 'TEMPORARY');
    echo '<h3>فرم محاسبه حقوق و مزایای قرارداد</h3>';
    echo '<p>نوع محاسبه: <b>' . $h(p360hr_contract_type_fa($ctype)) . '</b></p>';
    echo '<input type="hidden" name="contract_type" value="' . $h($ctype) . '">';
    echo '<div class="p360hr-meta">';
    if ($ctype === 'HOURLY') {
        echo '<label>مبلغ هر ساعت<br><input name="hourly_rate" type="number" step="1" value="' . $h($wageSnap['hourly_rate'] ?? '') . '"></label>';
        echo '<label>ساعات موظفی<br><input name="required_hours" type="number" step="0.5" value="' . $h($wageSnap['required_hours'] ?? '') . '"></label>';
        echo '<label>ساعات تأییدشده<br><input name="approved_hours" type="number" step="0.5" value="' . $h($wageSnap['approved_hours'] ?? '') . '"></label>';
    } elseif ($ctype === 'TEMPORARY') {
        echo '<label>مزد روزانه<br><input name="daily_rate" type="number" step="1" value="' . $h($wageSnap['daily_rate'] ?? '') . '"></label>';
        echo '<label>روزهای مبنای ماه<br><input name="month_days_basis" type="number" value="' . $h($wageSnap['month_days_basis'] ?? '30') . '"></label>';
        echo '<label>روزهای قابل پرداخت<br><input name="payable_days" type="number" step="0.5" value="' . $h($wageSnap['payable_days'] ?? '30') . '"></label>';
    } elseif ($ctype === 'PERMANENT') {
        echo '<label>مزد روزانه<br><input name="daily_rate" type="number" step="1" value="' . $h($wageSnap['daily_rate'] ?? '') . '"></label>';
        echo '<label>مزد ماهانه ثابت<br><input name="monthly_fixed" type="number" step="1" value="' . $h($wageSnap['monthly_fixed'] ?? '') . '"></label>';
        echo '<label>روزهای مبنای ماه<br><input name="month_days_basis" type="number" value="' . $h($wageSnap['month_days_basis'] ?? '30') . '"></label>';
    } elseif ($ctype === 'SPECIFIC_WORK') {
        echo '<label>مبلغ کل کار معین<br><input name="specific_total" type="number" step="1" value="' . $h($wageSnap['specific_total'] ?? '') . '"></label>';
    } else {
        echo '<label>مبلغ کل پیمان<br><input name="contractual_total" type="number" step="1" value="' . $h($wageSnap['contractual_total'] ?? '') . '"></label>';
        echo '<label>تعداد مراحل<br><input name="contractual_stages" type="number" value="' . $h($wageSnap['contractual_stages'] ?? '1') . '"></label>';
        echo '<label>مبلغ هر مرحله<br><input name="contractual_stage_amount" type="number" step="1" value="' . $h($wageSnap['contractual_stage_amount'] ?? '') . '"></label>';
    }
    echo '<label>حق مسکن ماهانه<br><input name="housing_monthly" type="number" value="' . $h($wageSnap['housing_monthly'] ?? '') . '"></label>';
    echo '<label>بن و خواروبار<br><input name="food_monthly" type="number" value="' . $h($wageSnap['food_monthly'] ?? '') . '"></label>';
    echo '<label>حق تأهل<br><input name="marriage_monthly" type="number" value="' . $h($wageSnap['marriage_monthly'] ?? '') . '"></label>';
    echo '<label>حق اولاد<br><input name="child_monthly" type="number" value="' . $h($wageSnap['child_monthly'] ?? '') . '"></label>';
    echo '<label>پایه سنوات<br><input name="seniority_monthly" type="number" value="' . $h($wageSnap['seniority_monthly'] ?? '') . '"></label>';
    echo '<label>عیدی ماهانه (در صورت انتخاب ماهانه)<br><input name="eid_monthly" type="number" value="' . $h($wageSnap['eid_monthly'] ?? '') . '"></label>';
    echo '<label>سنوات ماهانه<br><input name="severance_monthly" type="number" value="' . $h($wageSnap['severance_monthly'] ?? '') . '"></label>';
    echo '<label>ایاب و ذهاب<br><input name="transport_monthly" type="number" value="' . $h($wageSnap['transport_monthly'] ?? '') . '"></label>';
    echo '<label>حق فنی<br><input name="technical_monthly" type="number" value="' . $h($wageSnap['technical_monthly'] ?? '') . '"></label>';
    echo '<label>سایر مزایا<br><input name="other_monthly" type="number" value="' . $h($wageSnap['other_monthly'] ?? '') . '"></label>';
    echo '</div>';
    // preserve key fields
    foreach (['contract_job_title', 'unit_name', 'direct_supervisor', 'workplace', 'contract_subject', 'contract_subject_details', 'job_duties_text', 'start_date', 'duration_preset', 'duration_unit', 'duration_custom_value', 'eid_payment_method', 'severance_payment_method'] as $fk) {
        $v = $contract[$fk] ?? '';
        if ($fk === 'duration_custom_value') {
            $v = (string)($contract['duration_months'] ?? '3');
        }
        echo '<input type="hidden" name="' . $fk . '" value="' . $h($v) . '">';
    }
    if (($contract['contract_job_title'] ?? '') === '') {
        echo '<input type="hidden" name="contract_job_title" value="' . $h($emp['job_title'] ?? '') . '">';
    }
    if (($contract['unit_name'] ?? '') === '') {
        echo '<input type="hidden" name="unit_name" value="' . $h($emp['unit_name'] ?? '') . '">';
    }
}

if ($tab === 'bank' && $showForm) {
    echo '<h3>حساب بانکی و ضمانت</h3>';
    echo '<p>بانک: ' . $h($profile['bank_name'] ?? '—') . '</p>';
    echo '<p>حساب: ' . $h($profile['bank_account'] ?? '—') . '</p>';
    echo '<p>شبا: ' . $h(p360hr_mask_iban((string)($profile['iban'] ?? ''))) . '</p>';
    echo '<p>صاحب حساب: ' . $h($profile['account_holder_name'] ?? '—') . '</p>';
    echo '<p>تأیید خود پرسنل: ' . (((int)($profile['account_self_confirmed'] ?? 0) === 1) ? 'بله' : 'خیر') . '</p>';
    echo '<p>ضمانت: ' . $h($profile['guarantee_type'] ?? '—') . ' / ' . $h(p360hr_money_fa(isset($profile['guarantee_amount']) ? (float)$profile['guarantee_amount'] : null)) . '</p>';
    echo '<p><a href="hr-personnel-form.php?personnel_code=' . rawurlencode($code) . '&tab=H">ویرایش بانکی</a></p>';
    foreach (['contract_type', 'contract_job_title', 'unit_name', 'direct_supervisor', 'workplace', 'contract_subject', 'job_duties_text', 'start_date', 'duration_preset', 'duration_unit', 'duration_custom_value', 'eid_payment_method', 'severance_payment_method'] as $fk) {
        $fallback = '';
        if ($fk === 'contract_type') {
            $fallback = 'TEMPORARY';
        } elseif ($fk === 'contract_job_title') {
            $fallback = (string)($emp['job_title'] ?? '');
        } elseif ($fk === 'unit_name') {
            $fallback = (string)($emp['unit_name'] ?? '');
        } elseif ($fk === 'duration_preset') {
            $fallback = '3M';
        } elseif ($fk === 'duration_custom_value') {
            $fallback = (string)($contract['duration_months'] ?? '3');
        }
        echo '<input type="hidden" name="' . $fk . '" value="' . $h($contract[$fk] ?? $fallback) . '">';
    }
}

if ($showForm) {
    echo '<p style="margin-top:1rem"><button class="m360-btn" type="submit">محاسبه و ذخیره پیش‌نویس</button></p>';
    echo '</form>';
}

if ($tab === 'clause9') {
    echo '<section class="m360-card"><h3>۹) حق‌السعی — جدول</h3>';
    if ($wageRows === []) {
        echo '<p>ابتدا فرم محاسبه را ذخیره کنید.</p>';
    } else {
        $td = 0.0;
        $tm = 0.0;
        foreach ($wageRows as $r) {
            $td += (float)$r['daily_amount'];
            $tm += (float)$r['monthly_amount'];
        }
        echo p360hr_wage_table_html($wageRows, $td, $tm);
        echo '<p>جمع کل توسط سیستم محاسبه شده و قابل بازنویسی دستی نیست.</p>';
    }
    echo '</section>';
}

if ($tab === 'text') {
    echo '<section class="m360-card"><h3>متن قرارداد</h3>';
    if (!$contract || trim(p360hr_load_rendered_body($contract)) === '') {
        echo '<p>پس از ذخیره پیش‌نویس، متن ۱۸ ماده‌ای اینجا نمایش داده می‌شود.</p>';
    } else {
        echo '<pre style="white-space:pre-wrap;font-family:Vazirmatn,Tahoma,sans-serif;line-height:1.8">' . $h(p360hr_load_rendered_body($contract)) . '</pre>';
    }
    echo '</section>';
}

if ($tab === 'attach') {
    $disc = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'disciplinary-v1.txt';
    echo '<section class="m360-card"><h3>پیوست‌ها</h3>';
    echo '<p>آیین‌نامه انضباطی: ' . (is_file($disc) || is_file(str_replace('.txt', '.pdf', $disc)) ? 'موجود' : 'در اولین پذیرش نسخه پایه ایجاد می‌شود') . '</p>';
    echo '<p>شرح وظایف: از متن قرارداد / پیوست جداگانه</p>';
    echo '</section>';
}

if ($tab === 'review') {
    $employer = p360hr_employer_active();
    $gaps = p360hr_employer_mandatory_gaps($employer);
    echo '<section class="m360-card"><h3>بررسی نهایی</h3>';
    if ($gaps !== []) {
        echo '<p class="m360-alert m360-alert-err">نقص کارفرما (نهایی‌سازی مسدود): ' . $h(implode('، ', $gaps)) . ' — <a href="hr-employer-profile.php">تکمیل پروفایل</a></p>';
    } else {
        echo '<p>پروفایل کارفرما برای نهایی‌سازی کامل است. پیش‌نویس قبل از تکمیل نیز مجاز است.</p>';
    }
    if ($contract) {
        $employee = $emp;
        $td = 0.0;
        $tm = 0.0;
        foreach ($wageRows as $r) {
            $td += (float)$r['daily_amount'];
            $tm += (float)$r['monthly_amount'];
        }
        $built = p360hr_build_placeholder_map($contract, $employee, $profile, $employer ?? [], $wageRows, $td, $tm);
        if ($built['unresolved'] !== []) {
            echo '<p class="m360-alert m360-alert-err">جایگاه‌های ناقص: ' . $h(implode('، ', p360hr_unresolved_placeholder_labels($built['unresolved']))) . '</p>';
        } else {
            echo '<p>تمام جایگاه‌های الزامی پر شده‌اند.</p>';
        }
        echo '<form method="post" style="display:flex;gap:.5rem;flex-wrap:wrap">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
        echo '<input type="hidden" name="contract_id" value="' . $contractId . '">';
        echo '<input type="hidden" name="personnel_code" value="' . $h($code) . '">';
        echo '<input type="hidden" name="action" value="set_stage">';
        echo '<select name="stage_code"><option value="ADMIN_REVIEW">بررسی اداری</option><option value="PRESENTED">ارائه به پرسنل</option></select>';
        echo '<button class="m360-btn" type="submit">ثبت مرحله</button></form>';
    }
    echo '</section>';
}

if ($tab === 'accept') {
    echo '<section class="m360-card"><h3>تأیید و OTP</h3>';
    if (!$contractId) {
        echo '<p>قراردادی انتخاب نشده است.</p>';
    } else {
        echo '<p>' . $h(P360HR_ACCEPT_TEXT) . '</p>';
        echo '<form method="post" style="margin-bottom:1rem">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '"><input type="hidden" name="action" value="accept">';
        echo '<input type="hidden" name="contract_id" value="' . $contractId . '"><input type="hidden" name="personnel_code" value="' . $h($code) . '">';
        echo '<label><input type="checkbox" required> می‌پذیرم</label> ';
        echo '<button class="m360-btn" type="submit">ثبت پذیرش</button></form>';
        echo '<form method="post" style="display:inline-block;margin-left:.5rem">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '"><input type="hidden" name="action" value="issue_otp">';
        echo '<input type="hidden" name="contract_id" value="' . $contractId . '"><input type="hidden" name="personnel_code" value="' . $h($code) . '">';
        echo '<button class="m360-btn" type="submit">ارسال OTP پرسنلی</button></form>';
        echo '<form method="post" style="margin-top:1rem;display:flex;gap:.5rem;align-items:end">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '"><input type="hidden" name="action" value="verify_otp">';
        echo '<input type="hidden" name="contract_id" value="' . $contractId . '"><input type="hidden" name="personnel_code" value="' . $h($code) . '">';
        echo '<label>کد ۶ رقمی<br><input name="otp_code" pattern="\\d{6}" maxlength="6" required></label>';
        echo '<button class="m360-btn" type="submit">تأیید OTP</button></form>';
    }
    echo '</section>';
}

if ($tab === 'sign') {
    echo '<section class="m360-card"><h3>امضا</h3>';
    echo '<canvas id="sig" width="480" height="180" style="border:1px solid #2f5d50;touch-action:none;background:#fff;max-width:100%"></canvas>';
    echo '<p><button type="button" id="sigClear" class="m360-btn">پاک کردن</button></p>';
    echo '<form method="post" id="sigFormEmp"><input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
    echo '<input type="hidden" name="action" value="sign_employee"><input type="hidden" name="signature_data" id="sigDataEmp">';
    echo '<input type="hidden" name="contract_id" value="' . $contractId . '"><input type="hidden" name="personnel_code" value="' . $h($code) . '">';
    echo '<button class="m360-btn" type="submit">ثبت امضای پرسنل</button></form>';
    echo '<form method="post" id="sigFormEr" style="margin-top:.5rem"><input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
    echo '<input type="hidden" name="action" value="sign_employer"><input type="hidden" name="signature_data" id="sigDataEr">';
    echo '<input type="hidden" name="contract_id" value="' . $contractId . '"><input type="hidden" name="personnel_code" value="' . $h($code) . '">';
    echo '<button class="m360-btn" type="submit">ثبت امضای کارفرما</button></form>';
    echo '<form method="post" style="margin-top:1rem"><input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
    echo '<input type="hidden" name="action" value="finalize"><input type="hidden" name="contract_id" value="' . $contractId . '">';
    echo '<input type="hidden" name="personnel_code" value="' . $h($code) . '">';
    echo '<button class="m360-btn" type="submit">نهایی‌سازی، PDF و قفل</button></form>';
    echo '<script>
    (function(){
      var c=document.getElementById("sig"),x=c.getContext("2d"),d=false;
      function pos(e){var r=c.getBoundingClientRect();var t=e.touches?e.touches[0]:e;return {x:(t.clientX-r.left)*c.width/r.width,y:(t.clientY-r.top)*c.height/r.height};}
      function start(e){d=true;var p=pos(e);x.beginPath();x.moveTo(p.x,p.y);e.preventDefault();}
      function move(e){if(!d)return;var p=pos(e);x.lineWidth=2;x.lineCap="round";x.strokeStyle="#111";x.lineTo(p.x,p.y);x.stroke();e.preventDefault();}
      function end(){d=false;}
      c.addEventListener("mousedown",start);c.addEventListener("mousemove",move);window.addEventListener("mouseup",end);
      c.addEventListener("touchstart",start,{passive:false});c.addEventListener("touchmove",move,{passive:false});c.addEventListener("touchend",end);
      document.getElementById("sigClear").onclick=function(){x.clearRect(0,0,c.width,c.height);};
      function bind(formId,hid){document.getElementById(formId).addEventListener("submit",function(ev){document.getElementById(hid).value=c.toDataURL("image/png");});}
      bind("sigFormEmp","sigDataEmp");bind("sigFormEr","sigDataEr");
    })();
    </script>';
    echo '</section>';
}

if ($tab === 'pdf') {
    echo '<section class="m360-card"><h3>PDF و تاریخچه</h3>';
    if ($contract) {
        echo '<p>Hash قرارداد: ' . $h($contract['contract_hash'] ?? '—') . '</p>';
        echo '<p>Hash جدول مزد: ' . $h($contract['wage_table_hash'] ?? '—') . '</p>';
        echo '<p>PDF نهایی: ' . $h($contract['final_pdf_path'] ?? '—') . '</p>';
        echo '<p>SHA256 PDF: ' . $h($contract['final_pdf_hash'] ?? '—') . '</p>';
        echo '<form method="post"><input type="hidden" name="erp_csrf_token" value="' . $h($csrf) . '">';
        echo '<input type="hidden" name="action" value="draft_pdf"><input type="hidden" name="contract_id" value="' . $contractId . '">';
        echo '<input type="hidden" name="personnel_code" value="' . $h($code) . '">';
        echo '<button class="m360-btn" type="submit">تولید پیش‌نویس PDF</button></form>';
        if (!empty($contract['final_pdf_path'])) {
            echo '<p><a href="hr-contract-pdf.php?contract_id=' . $contractId . '">دانلود PDF نهایی</a></p>';
        }
    } else {
        echo '<p>قراردادی نیست.</p>';
    }
    echo '</section>';
}

p360hr_layout_end();
