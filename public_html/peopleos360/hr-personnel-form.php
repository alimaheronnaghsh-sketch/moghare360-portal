<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-identity-date.php';
require_once __DIR__ . '/includes/p360-hr-jalali-ui.php';
require_once __DIR__ . '/includes/p360-hr-occ-med-manager.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

$code = trim((string)($_GET['personnel_code'] ?? $_POST['personnel_code'] ?? ''));
$tabRaw = trim((string)($_GET['tab'] ?? 'A'));
$tabs = [
    'A' => 'شناسه‌های سیستمی',
    'B' => 'مشخصات هویتی',
    'C' => 'تماس و سکونت',
    'D' => 'خانوادگی',
    'E' => 'تحصیلی',
    'F' => 'نظام وظیفه',
    'G' => 'استخدامی',
    'H' => 'بانکی',
    'I' => 'ضمانت',
    'J' => 'مدارک',
    'OCC' => 'طب کار',
    'hist' => 'تاریخچه هویت',
];
$tab = $tabRaw;
if (!isset($tabs[$tab])) {
    $tabLower = strtolower($tabRaw);
    $tab = isset($tabs[$tabLower]) ? $tabLower : 'A';
}

$msg = null;
$ok = false;
$bundle = null;
if ($code !== '') {
    $bundle = p360hr_load_personnel_bundle($code);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_personnel'])) {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_personnel_form', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } elseif ($bundle === null || empty($bundle['ok'])) {
        $msg = 'ابتدا کد پرسنلی معتبر انتخاب کنید.';
    } else {
        $eid = (int)$bundle['employee']['employee_id'];
        $actor = erp_auth_current_user_id() ?? 0;
        if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
            $idRes = p360hr_save_identity_names($eid, $_POST, $actor);
            if (empty($idRes['ok'])) {
                $msg = (string)$idRes['message'];
                $ok = false;
            }
        }
        $data = $_POST;
        if ($msg === null) {
            foreach (['birth_date', 'military_issue_date', 'cooperation_start', 'cooperation_end', 'guarantee_received_at', 'guarantee_returned_at'] as $df) {
                $parsed = p360hr_parse_posted_jalali_sql($df, false);
                if (!$parsed['ok']) {
                    $msg = (string)$parsed['message'];
                    $ok = false;
                    break;
                }
                $data[$df] = $parsed['ymd'];
            }
        }
        if ($msg === null && isset($_POST['direct_manager_employee_id'])) {
            $mgrId = (int)$_POST['direct_manager_employee_id'];
            $mgrRes = p360hr_set_direct_manager($eid, $mgrId > 0 ? $mgrId : null, $actor);
            if (empty($mgrRes['ok'])) {
                $msg = (string)$mgrRes['message'];
                $ok = false;
            }
        }
        if ($msg === null) {
            if (!empty($_POST['hire_date_unknown'])) {
                p360hr_exec('UPDATE dbo.p360_employees SET hire_date_unknown=1, hire_date=NULL WHERE employee_id=?', [$eid]);
                $hy = (int)($_POST['hire_year_jalali'] ?? 0);
                if ($hy >= 1300 && $hy <= 1600) {
                    p360hr_exec('UPDATE dbo.p360_employees SET hire_year_jalali=? WHERE employee_id=?', [$hy, $eid]);
                }
            } else {
                p360hr_exec('UPDATE dbo.p360_employees SET hire_date_unknown=0 WHERE employee_id=?', [$eid]);
                $hireExact = p360hr_parse_posted_jalali_sql('hire_date', false);
                if (!$hireExact['ok']) {
                    $msg = (string)$hireExact['message'];
                    $ok = false;
                } elseif ($hireExact['ymd'] !== null) {
                    p360hr_exec('UPDATE dbo.p360_employees SET hire_date=? WHERE employee_id=?', [$hireExact['ymd'], $eid]);
                }
            }
        }
        if ($msg === null) {
            $data['sync_employee_master'] = 1;
            $res = p360hr_save_personnel_profile($eid, $data, $actor);
            $ok = !empty($res['ok']);
            $msg = (string)($res['message'] ?? '');
        }
        $bundle = p360hr_load_personnel_bundle($code);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_occ_med'])) {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_personnel_form', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } elseif ($bundle === null || empty($bundle['ok'])) {
        $msg = 'ابتدا کد پرسنلی معتبر انتخاب کنید.';
    } else {
        $eid = (int)$bundle['employee']['employee_id'];
        $actor = erp_auth_current_user_id() ?? 0;
        $payload = $_POST;
        foreach (['examination_date', 'report_issue_date', 'valid_from_date', 'expiry_date'] as $df) {
            if ($df === 'expiry_date' && !empty($_POST['no_expiry'])) {
                $payload[$df] = null;
                continue;
            }
            $parsed = p360hr_parse_posted_jalali_sql($df, false);
            if (!$parsed['ok']) {
                $msg = (string)$parsed['message'];
                $ok = false;
                break;
            }
            $payload[$df] = $parsed['ymd'];
        }
        if ($msg === null) {
            $res = p360hr_occ_med_save($eid, $payload, $actor);
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
        }
        $bundle = p360hr_load_personnel_bundle($code);
        $tab = 'OCC';
    }
}

p360hr_layout_start('فرم اطلاعات پرسنلی', 'تکمیل و به‌روزرسانی پرونده پرسنلی');
echo '<form method="get" class="p360hr-section-card" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:end">';
echo '<label>کد پرسنلی<br><input name="personnel_code" value="' . p360hr_h($code) . '" required style="min-width:220px"></label>';
echo '<input type="hidden" name="tab" value="' . p360hr_h($tab) . '">';
echo '<button class="m360-btn m360-btn-primary" type="submit">بارگذاری پرونده</button>';
echo '<a class="m360-btn m360-btn-secondary" href="hr-contract-register.php?personnel_code=' . rawurlencode($code) . '">ثبت قرارداد</a>';
echo '</form>';

if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}

if ($bundle === null) {
    echo '<p>کد پرسنلی را وارد کنید تا فرم اطلاعات پرسنلی بارگذاری شود.</p>';
    p360hr_layout_end();
    exit;
}
if (empty($bundle['ok'])) {
    echo '<div class="m360-alert m360-alert-err">' . p360hr_h((string)$bundle['message']) . '</div>';
    p360hr_layout_end();
    exit;
}

$emp = $bundle['employee'];
$p = $bundle['profile'];
$core = $bundle['core_user'];
$eid = (int)$emp['employee_id'];

if (!empty($bundle['missing'])) {
    echo '<div class="m360-alert m360-alert-err">نقص اطلاعات: ' . p360hr_h(implode('، ', $bundle['missing'])) . '</div>';
}
if (!empty($bundle['warnings'])) {
    echo '<div class="m360-alert">' . p360hr_h(implode(' — ', $bundle['warnings'])) . '</div>';
}

echo '<nav class="p360hr-tabs">';
foreach ($tabs as $k => $label) {
    $cls = $k === $tab ? ' active' : '';
    echo '<a class="' . trim($cls) . '" href="?personnel_code=' . rawurlencode($code) . '&tab=' . rawurlencode($k) . '">' . p360hr_h($label) . '</a>';
}
echo '</nav>';

$csrf = erp_csrf_create_token('p360hr_personnel_form');
$val = static function (array $src, string $key): string {
    return p360hr_h((string)($src[$key] ?? ''));
};

echo '<form method="post" class="p360hr-section-card">';
echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<input type="hidden" name="personnel_code" value="' . p360hr_h($code) . '">';
echo '<input type="hidden" name="save_personnel" value="1">';

switch ($tab) {
    case 'A':
        echo '<p><b>شناسه داخلی:</b> ' . $eid . '</p>';
        echo '<p><b>کد پرسنلی رسمی:</b> ' . p360hr_h((string)$emp['employee_code']) . '</p>';
        echo '<p><b>نام کاربری:</b> ' . p360hr_h((string)($core['username'] ?? '—')) . '</p>';
        echo '<p><b>کد دستگاه انگشت‌زن:</b> ' . p360hr_h(p360hr_fingerprint_code($eid)) . '</p>';
        echo '<p><b>وضعیت:</b> ' . p360hr_h((string)($emp['lifecycle_state'] ?? $emp['employee_status'] ?? '')) . '</p>';
        echo '<p><b>تاریخ ایجاد پرونده:</b> ' . p360hr_h((string)($emp['created_at'] ?? '—')) . '</p>';
        echo '<p><b>نام کامل قرارداد:</b> ' . p360hr_h(p360hr_employee_full_name($emp)) . '</p>';
        break;
    case 'B':
        $preview = p360hr_employee_full_name($emp);
        echo '<div class="p360hr-meta">';
        echo '<label>نام (چندبخشی مجاز)<br><input name="first_name" id="fn" value="' . $val($emp, 'first_name') . '" required></label>';
        echo '<label>نام خانوادگی (چندبخشی مجاز)<br><input name="last_name" id="ln" value="' . $val($emp, 'last_name') . '" required></label>';
        echo '<label>نام کامل نمایشی (اختیاری)<br><input name="display_name_override" id="dn" value="' . $val($emp, 'display_name_override') . '"></label>';
        echo '</div>';
        echo '<p class="p360hr-warn">پیش‌نمایش نام قرارداد: <b id="fullPreview">' . p360hr_h($preview) . '</b></p>';
        echo '<p>نام مهاجرت اولیه: ' . p360hr_h((string)($emp['imported_full_name'] ?? '—')) . '</p>';
        echo '<p><a href="?personnel_code=' . rawurlencode($code) . '&tab=hist">تاریخچه تغییرات هویت</a></p>';
        p360hr_jalali_date_field('birth_date', isset($p['birth_date']) ? (string)$p['birth_date'] : null, 'تاریخ تولد', ['allow_clear' => true]);
        echo '<div class="p360hr-meta">';
        echo '<label>نام پدر<br><input name="father_name" value="' . $val($p, 'father_name') . '"></label>';
        echo '<label>کد ملی<br><input name="national_code" value="' . $val($emp, 'national_code') . '"></label>';
        echo '<label>شماره شناسنامه<br><input name="birth_certificate_no" value="' . $val($p, 'birth_certificate_no') . '"></label>';
        echo '<label>محل تولد<br><input name="birth_place" value="' . $val($p, 'birth_place') . '"></label>';
        echo '<label>جنسیت<br><input name="gender" value="' . $val($p, 'gender') . '"></label>';
        echo '<label>تابعیت<br><input name="nationality" value="' . $val($p, 'nationality') . '"></label>';
        echo '</div>';
        echo '<script>(function(){function u(){var f=document.getElementById("fn").value.trim(),l=document.getElementById("ln").value.trim(),d=document.getElementById("dn").value.trim();document.getElementById("fullPreview").textContent=d!==""?d:(f+" "+l).trim();}["fn","ln","dn"].forEach(function(id){document.getElementById(id).addEventListener("input",u);});})();</script>';
        break;
    case 'C':
        echo '<div class="p360hr-meta">';
        echo '<label>موبایل<br><input name="mobile" value="' . $val($emp, 'mobile') . '"></label>';
        echo '<label>تماس اضطراری<br><input name="emergency_mobile" value="' . $val($p, 'emergency_mobile') . '"></label>';
        echo '<label>نام شخص اضطراری<br><input name="emergency_name" value="' . $val($p, 'emergency_name') . '"></label>';
        echo '<label>نسبت<br><input name="emergency_relation" value="' . $val($p, 'emergency_relation') . '"></label>';
        echo '<label>استان<br><input name="province" value="' . $val($p, 'province') . '"></label>';
        echo '<label>شهر<br><input name="city" value="' . $val($p, 'city') . '"></label>';
        echo '<label>کد پستی<br><input name="postal_code" value="' . $val($p, 'postal_code') . '"></label>';
        echo '</div>';
        echo '<label>نشانی کامل<br><textarea name="address_full" rows="3" style="width:100%">' . $val($p, 'address_full') . '</textarea></label>';
        break;
    case 'D':
        echo '<div class="p360hr-meta">';
        echo '<label>وضعیت تأهل<br><input name="marital_status" value="' . $val($p, 'marital_status') . '"></label>';
        echo '<label>نام همسر<br><input name="spouse_name" value="' . $val($p, 'spouse_name') . '"></label>';
        echo '<label>تعداد فرزند<br><input name="child_count" type="number" value="' . $val($p, 'child_count') . '"></label>';
        echo '<label>فرزند زیر ۱۸<br><input name="child_under_18_count" type="number" value="' . $val($p, 'child_under_18_count') . '"></label>';
        echo '<label><input type="checkbox" name="child_allowance_eligible" value="1"' . (((int)($p['child_allowance_eligible'] ?? 0) === 1) ? ' checked' : '') . '> حق اولاد</label>';
        echo '<label><input type="checkbox" name="marriage_allowance_eligible" value="1"' . (((int)($p['marriage_allowance_eligible'] ?? 0) === 1) ? ' checked' : '') . '> حق تأهل</label>';
        echo '</div>';
        break;
    case 'E':
        echo '<div class="p360hr-meta">';
        echo '<label>آخرین مدرک<br><input name="education_degree" value="' . $val($p, 'education_degree') . '"></label>';
        echo '<label>رشته<br><input name="education_field" value="' . $val($p, 'education_field') . '"></label>';
        echo '<label>مرکز آموزشی<br><input name="education_school" value="' . $val($p, 'education_school') . '"></label>';
        echo '<label>سال اخذ<br><input name="education_year" type="number" value="' . $val($p, 'education_year') . '"></label>';
        echo '</div>';
        break;
    case 'F':
        echo '<div class="p360hr-meta">';
        echo '<label>وضعیت خدمت<br><input name="military_status" value="' . $val($p, 'military_status') . '"></label>';
        echo '<label>نوع کارت<br><input name="military_card_type" value="' . $val($p, 'military_card_type') . '"></label>';
        echo '<label>شماره کارت<br><input name="military_card_no" value="' . $val($p, 'military_card_no') . '"></label>';
        echo '<label><input type="checkbox" name="military_not_applicable" value="1"' . (((int)($p['military_not_applicable'] ?? 0) === 1) ? ' checked' : '') . '> عدم شمول</label>';
        echo '</div>';
        p360hr_jalali_date_field('military_issue_date', isset($p['military_issue_date']) ? (string)$p['military_issue_date'] : null, 'تاریخ صدور', ['allow_clear' => true]);
        break;
    case 'G':
        $hireUnknown = (int)($emp['hire_date_unknown'] ?? 0) === 1;
        echo '<label><input type="checkbox" name="hire_date_unknown" value="1" id="hdu"' . ($hireUnknown ? ' checked' : '') . '> تاریخ دقیق نامشخص است</label>';
        echo '<div id="hireYearBox"' . ($hireUnknown ? '' : ' style="display:none"') . '><label>سال استخدام شمسی<br><select name="hire_year_jalali">';
        $hy = (int)($emp['hire_year_jalali'] ?? 1400);
        for ($y = 1390; $y <= 1410; $y++) {
            echo '<option value="' . $y . '"' . ($hy === $y ? ' selected' : '') . '>' . $y . '</option>';
        }
        echo '</select></label></div>';
        echo '<div id="hireDateBox"' . ($hireUnknown ? ' style="display:none"' : '') . '>';
        p360hr_jalali_date_field('hire_date', isset($emp['hire_date']) ? (string)$emp['hire_date'] : null, 'تاریخ دقیق استخدام', ['allow_clear' => true]);
        echo '</div>';
        echo '<script>document.getElementById("hdu").addEventListener("change",function(){document.getElementById("hireYearBox").style.display=this.checked?"":"none";document.getElementById("hireDateBox").style.display=this.checked?"none":"";});</script>';
        echo '<div class="p360hr-meta">';
        echo '<label>واحد اصلی<br><input name="unit_name" value="' . $val($emp, 'unit_name') . '"></label>';
        echo '<label>عنوان شغل اصلی<br><input name="job_title" value="' . $val($emp, 'job_title') . '"></label>';
        echo '<label>عنوان شغل ثانویه<br><input name="secondary_position" value="' . $val($emp, 'secondary_position') . '"></label>';
        echo '<label>سرپرست مستقیم (متن)<br><input name="direct_supervisor" value="' . $val($p, 'direct_supervisor') . '"></label>';
        echo '<label>مدیر مستقیم (رابطه دیتابیسی)<br><select name="direct_manager_employee_id"><option value="0">— ثبت نشده —</option>';
        $cands = p360hr_rows("SELECT employee_id, first_name, last_name, employee_code FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL AND employee_id<>? ORDER BY first_name, last_name", [$eid]);
        $curMgr = (int)($p['direct_manager_employee_id'] ?? 0);
        foreach ($cands as $c) {
            $sel = ((int)$c['employee_id'] === $curMgr) ? ' selected' : '';
            echo '<option value="' . (int)$c['employee_id'] . '"' . $sel . '>' . p360hr_h(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '') . ' (' . ($c['employee_code'] ?? '') . ')')) . '</option>';
        }
        echo '</select></label>';
        $mgrCheck = p360hr_resolve_direct_manager($eid);
        if (!$mgrCheck['ok']) {
            echo '<p class="p360hr-warn">' . p360hr_h((string)$mgrCheck['message']) . '</p>';
        } else {
            echo '<p>مدیر مستقیم فعال و دارای حساب کاربری معتبر است.</p>';
        }
        echo '<label>محل خدمت<br><input name="workplace" value="' . $val($p, 'workplace') . '"></label>';
        echo '<label>نوع همکاری<br><input name="cooperation_type" value="' . $val($p, 'cooperation_type') . '"></label>';
        echo '<label>علت پایان<br><input name="exit_reason" value="' . $val($p, 'exit_reason') . '"></label>';
        echo '</div>';
        p360hr_jalali_date_field('cooperation_start', isset($p['cooperation_start']) ? (string)$p['cooperation_start'] : null, 'تاریخ شروع همکاری', ['allow_clear' => true]);
        p360hr_jalali_date_field('cooperation_end', isset($p['cooperation_end']) ? (string)$p['cooperation_end'] : null, 'تاریخ پایان همکاری', ['allow_clear' => true]);
        break;
    case 'H':
        echo '<div class="p360hr-meta">';
        echo '<label>نام بانک<br><input name="bank_name" value="' . $val($p, 'bank_name') . '"></label>';
        echo '<label>شماره حساب<br><input name="bank_account" value="' . $val($p, 'bank_account') . '"></label>';
        echo '<label>شبا<br><input name="iban" value="' . $val($p, 'iban') . '"></label>';
        echo '<label>شماره کارت<br><input name="card_no" value="' . $val($p, 'card_no') . '"></label>';
        echo '<label>نام صاحب حساب<br><input name="account_holder_name" value="' . $val($p, 'account_holder_name') . '"></label>';
        echo '<label>کد ملی صاحب حساب<br><input name="account_holder_national_id" value="' . $val($p, 'account_holder_national_id') . '"></label>';
        echo '<label><input type="checkbox" name="account_self_confirmed" value="1"' . (((int)($p['account_self_confirmed'] ?? 0) === 1) ? ' checked' : '') . '> حساب متعلق به خود پرسنل است</label>';
        echo '</div>';
        break;
    case 'I':
        echo '<div class="p360hr-meta">';
        echo '<label>نوع ضمانت<br><input name="guarantee_type" value="' . $val($p, 'guarantee_type') . '"></label>';
        echo '<label>مبلغ<br><input name="guarantee_amount" value="' . $val($p, 'guarantee_amount') . '"></label>';
        echo '<label>شماره چک/سفته<br><input name="guarantee_ref" value="' . $val($p, 'guarantee_ref') . '"></label>';
        echo '<label>بانک/صادرکننده<br><input name="guarantee_issuer" value="' . $val($p, 'guarantee_issuer') . '"></label>';
        echo '<label>محل نگهداری<br><input name="guarantee_storage" value="' . $val($p, 'guarantee_storage') . '"></label>';
        echo '<label>وضعیت<br><input name="guarantee_status" value="' . $val($p, 'guarantee_status') . '"></label>';
        echo '<label>تحویل‌گیرنده<br><input name="guarantee_receiver" value="' . $val($p, 'guarantee_receiver') . '"></label>';
        echo '</div>';
        p360hr_jalali_date_field('guarantee_received_at', isset($p['guarantee_received_at']) ? (string)$p['guarantee_received_at'] : null, 'تاریخ دریافت ضمانت', ['allow_clear' => true]);
        p360hr_jalali_date_field('guarantee_returned_at', isset($p['guarantee_returned_at']) ? (string)$p['guarantee_returned_at'] : null, 'تاریخ استرداد ضمانت', ['allow_clear' => true]);
        echo '<label>توضیحات<br><textarea name="guarantee_notes" rows="2" style="width:100%">' . $val($p, 'guarantee_notes') . '</textarea></label>';
        break;
    case 'J':
        echo '<ul><li>مدارک از کارتابل اسناد مدیریت می‌شود.</li><li>گزارش‌های طب کار ساختاریافته در تب «طب کار» ثبت می‌شوند و می‌توانند به مدرک OCC_MED پیوند بخورند.</li></ul>';
        break;
    case 'OCC':
        $editId = (int)($_GET['report_id'] ?? 0);
        $edit = $editId > 0 ? p360hr_occ_med_get($editId) : null;
        if ($edit && (int)$edit['employee_id'] !== $eid) {
            $edit = null;
        }
        echo '</form>'; // close outer save_personnel form before occ form
        echo '<form method="post" class="p360hr-section-card">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
        echo '<input type="hidden" name="personnel_code" value="' . p360hr_h($code) . '">';
        echo '<input type="hidden" name="save_occ_med" value="1">';
        echo '<input type="hidden" name="report_id" value="' . (int)($edit['report_id'] ?? 0) . '">';
        echo '<h3 class="p360hr-section-title" style="margin-top:0">' . ($edit ? 'ویرایش گزارش طب کار' : 'ثبت گزارش طب کار') . '</h3>';

        echo '<div class="p360hr-form-group"><h4>تاریخ‌ها</h4><div class="p360hr-form-grid">';
        echo '<div>';
        p360hr_jalali_date_field('examination_date', isset($edit['examination_date']) ? (string)$edit['examination_date'] : null, 'تاریخ انجام معاینه', ['allow_clear' => true]);
        echo '</div><div>';
        p360hr_jalali_date_field('report_issue_date', isset($edit['report_issue_date']) ? (string)$edit['report_issue_date'] : null, 'تاریخ صدور گزارش', ['allow_clear' => true]);
        echo '</div><div>';
        p360hr_jalali_date_field('valid_from_date', isset($edit['valid_from_date']) ? (string)$edit['valid_from_date'] : null, 'تاریخ شروع اعتبار', ['allow_clear' => true]);
        echo '</div>';
        $noExp = $edit ? ((int)($edit['no_expiry'] ?? 0) === 1) : false;
        echo '<div><label class="span-2" style="display:flex;align-items:center;gap:.5rem;min-height:44px"><input type="checkbox" name="no_expiry" id="noexp" value="1"' . ($noExp ? ' checked' : '') . '> فاقد تاریخ انقضا</label></div>';
        echo '<div id="expBox" class="span-2"' . ($noExp ? ' style="display:none"' : '') . '>';
        p360hr_jalali_date_field('expiry_date', (!$noExp && isset($edit['expiry_date'])) ? (string)$edit['expiry_date'] : null, 'تاریخ پایان اعتبار', ['allow_clear' => true]);
        echo '</div></div>';
        echo '<script>document.getElementById("noexp").addEventListener("change",function(){document.getElementById("expBox").style.display=this.checked?"none":"";});</script>';
        echo '</div>';

        echo '<div class="p360hr-form-group"><h4>اطلاعات گزارش</h4><div class="p360hr-form-grid">';
        echo '<label>مرکز طب کار<input name="medical_center_name" value="' . p360hr_h((string)($edit['medical_center_name'] ?? '')) . '"></label>';
        echo '<label>شماره/مرجع گزارش<input name="report_ref" value="' . p360hr_h((string)($edit['report_ref'] ?? '')) . '"></label>';
        echo '<label>نتیجه<input name="result_status" value="' . p360hr_h((string)($edit['result_status'] ?? '')) . '"></label>';
        echo '<label>وضعیت گزارش<select name="lifecycle_state">';
        foreach (['DRAFT' => 'پیش‌نویس', 'VALID' => 'معتبر', 'RENEWAL_REQUIRED' => 'نیازمند تمدید', 'SUPERSEDED' => 'جایگزین‌شده', 'VOIDED' => 'باطل‌شده'] as $k => $fa) {
            $sel = ((string)($edit['lifecycle_state'] ?? 'DRAFT') === $k) ? ' selected' : '';
            echo '<option value="' . $k . '"' . $sel . '>' . $fa . '</option>';
        }
        echo '</select></label></div></div>';

        echo '<div class="p360hr-form-group"><h4>مستندات و توضیحات</h4><div class="p360hr-form-grid">';
        echo '<label>شناسه مدرک پیوست (اختیاری)<input name="document_id" type="number" value="' . p360hr_h((string)($edit['document_id'] ?? '')) . '"></label>';
        echo '<label class="span-2">توضیحات<textarea name="notes" rows="3">' . p360hr_h((string)($edit['notes'] ?? '')) . '</textarea></label>';
        echo '</div>';
        echo '<div class="p360hr-form-actions">';
        echo '<button class="m360-btn m360-btn-primary" type="submit">ذخیره گزارش طب کار</button>';
        echo '<a class="m360-btn m360-btn-secondary" href="?personnel_code=' . rawurlencode($code) . '&tab=OCC">انصراف / پاک‌کردن فرم</a>';
        echo '</div></div>';
        echo '</form>';

        echo '<section class="p360hr-section"><h3 class="p360hr-section-title">سوابق</h3>';
        echo '<div class="p360hr-section-card" style="overflow-x:auto"><table class="m360-table"><thead><tr><th>معاینه</th><th>صدور</th><th>اعتبار تا</th><th>وضعیت</th><th>مرکز</th><th></th></tr></thead><tbody>';
        foreach (p360hr_occ_med_list($eid) as $r) {
            echo '<tr>';
            echo '<td>' . p360hr_h(p360hr_date_jalali((string)($r['examination_date'] ?? ''))) . '</td>';
            echo '<td>' . p360hr_h(p360hr_date_jalali((string)($r['report_issue_date'] ?? ''))) . '</td>';
            echo '<td>' . (((int)($r['no_expiry'] ?? 0) === 1) ? 'فاقد انقضا' : p360hr_h(p360hr_date_jalali((string)($r['expiry_date'] ?? '')))) . '</td>';
            echo '<td><span class="p360hr-badge p360hr-badge-info">' . p360hr_h((string)$r['derived_status_fa']) . '</span></td>';
            echo '<td>' . p360hr_h((string)($r['medical_center_name'] ?? '')) . '</td>';
            echo '<td><a href="?personnel_code=' . rawurlencode($code) . '&tab=OCC&report_id=' . (int)$r['report_id'] . '">ویرایش</a>';
            if (!empty($r['document_id'])) {
                echo ' | <a href="hr-document-download.php?id=' . (int)$r['document_id'] . '">دانلود</a>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div></section>';
        echo '<form method="post" style="display:none">'; // keep outer closer happy
        break;
    case 'hist':
        $hist = p360hr_identity_history($eid);
        echo '<table class="m360-table"><thead><tr><th>فیلد</th><th>قبل</th><th>بعد</th><th>زمان</th></tr></thead><tbody>';
        foreach ($hist as $hrow) {
            echo '<tr><td>' . p360hr_h((string)$hrow['field_name']) . '</td><td>' . p360hr_h((string)($hrow['old_value'] ?? '')) . '</td><td>' . p360hr_h((string)($hrow['new_value'] ?? '')) . '</td><td>' . p360hr_h((string)$hrow['created_at']) . '</td></tr>';
        }
        if ($hist === []) {
            echo '<tr><td colspan="4">تاریخچه‌ای ثبت نشده است.</td></tr>';
        }
        echo '</tbody></table>';
        break;
}

if ($tab !== 'A' && $tab !== 'J' && $tab !== 'hist' && $tab !== 'OCC') {
    echo '<p style="margin-top:1rem"><button class="m360-btn" type="submit">ذخیره این بخش</button></p>';
}
echo '</form>';
p360hr_layout_end();
