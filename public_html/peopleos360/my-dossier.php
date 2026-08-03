<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';
require_once __DIR__ . '/includes/p360-hr-occ-med-manager.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
if ($emp === null) {
    p360hr_layout_start('پرونده پرسنلی');
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}

$eid = (int)($emp['employee_id'] ?? 0);
$tab = strtolower(trim((string)($_GET['tab'] ?? 'identity')));
$tabs = [
    'identity' => 'اطلاعات هویتی',
    'employment' => 'اطلاعات استخدامی',
    'org' => 'اطلاعات سازمانی',
    'docs' => 'مدارک پرسنلی',
    'occ_med' => 'طب کار',
    'contracts' => 'قراردادها و امضاها',
    'attendance' => 'حضور و غیاب',
    'requests' => 'درخواست‌های پرسنلی',
    'payroll' => 'حقوق و فیش‌ها',
    'login_device' => 'اطلاعات ورود و دستگاه انگشت‌زن',
    'history' => 'تاریخچه تغییرات',
];
if (!isset($tabs[$tab])) {
    $tab = 'identity';
}

p360hr_layout_start('پرونده پرسنلی');
echo '<div class="p360hr-meta">';
echo '<div><b>شناسه داخلی</b>' . p360hr_h((string)$eid) . '</div>';
echo '<div><b>کد پرسنلی</b>' . p360hr_h((string)($emp['employee_code'] ?? '')) . '</div>';
echo '<div><b>کد انگشت‌زن</b>' . p360hr_h(p360hr_fingerprint_code($eid)) . '</div>';
echo '<div><b>نام</b>' . p360hr_h(p360hr_employee_full_name($emp)) . '</div>';
echo '<div><b>سال استخدام</b>' . p360hr_h((string)($emp['hire_year_jalali'] ?? '—')) . '</div>';
echo '<div><b>واحد</b>' . p360hr_h((string)($emp['unit_name'] ?? '')) . '</div>';
echo '<div><b>شغل</b>' . p360hr_h((string)($emp['job_title'] ?? '')) . '</div>';
echo '<div><b>وضعیت</b>' . p360hr_h((string)($emp['lifecycle_state'] ?? '')) . '</div>';
echo '</div>';

echo '<nav class="p360hr-tabs">';
foreach ($tabs as $k => $label) {
    $cls = $k === $tab ? ' active' : '';
    echo '<a class="' . trim($cls) . '" href="my-dossier.php?tab=' . rawurlencode($k) . '">' . p360hr_h($label) . '</a>';
}
echo '</nav>';

echo '<section class="m360-card">';
switch ($tab) {
    case 'identity':
        echo '<p>نام: ' . p360hr_h((string)($emp['first_name'] ?? '')) . '</p>';
        echo '<p>نام خانوادگی: ' . p360hr_h((string)($emp['last_name'] ?? '')) . '</p>';
        echo '<p>نام کامل نمایشی: ' . p360hr_h(trim((string)($emp['display_name_override'] ?? '')) !== '' ? (string)$emp['display_name_override'] : trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''))) . '</p>';
        echo '<p>کد پرسنلی: ' . p360hr_h((string)($emp['employee_code'] ?? '')) . '</p>';
        echo '<p>عکس پرسنلی: ' . (((string)($emp['personnel_photo_path'] ?? '')) !== '' ? 'ثبت شده' : 'ثبت نشده') . '</p>';
        echo '<p class="p360hr-warn">ویرایش هویت رسمی فقط توسط منابع انسانی/مالک انجام می‌شود.</p>';
        break;
    case 'employment':
        echo '<p>سال استخدام (جلالی): ' . p360hr_h((string)($emp['hire_year_jalali'] ?? '—')) . '</p>';
        echo '<p>وضعیت همکاری: ' . p360hr_h((string)($emp['lifecycle_state'] ?? '')) . '</p>';
        break;
    case 'org':
        echo '<p>واحد: ' . p360hr_h((string)($emp['unit_name'] ?? '')) . ' (' . p360hr_h((string)($emp['unit_code'] ?? '')) . ')</p>';
        echo '<p>عنوان شغل: ' . p360hr_h((string)($emp['job_title'] ?? '')) . '</p>';
        echo '<p>سمت دوم: ' . p360hr_h((string)($emp['secondary_position'] ?? '—')) . '</p>';
        break;
    case 'docs':
        echo '<p><a href="my-documents.php">مدیریت و مشاهده مدارک پرسنلی</a></p>';
        break;
    case 'occ_med':
        echo '<p class="p360hr-warn">گزارش‌های طب کار فقط‌خواندنی هستند و ویرایش رسمی توسط منابع انسانی انجام می‌شود.</p>';
        echo '<table class="m360-table"><thead><tr><th>تاریخ معاینه</th><th>تاریخ صدور</th><th>پایان اعتبار</th><th>وضعیت</th><th>مرکز طب کار</th><th></th></tr></thead><tbody>';
        $reports = p360hr_occ_med_list($eid);
        foreach ($reports as $r) {
            echo '<tr>';
            echo '<td>' . p360hr_h(p360hr_date_jalali((string)($r['examination_date'] ?? ''))) . '</td>';
            echo '<td>' . p360hr_h(p360hr_date_jalali((string)($r['report_issue_date'] ?? ''))) . '</td>';
            echo '<td>' . (((int)($r['no_expiry'] ?? 0) === 1) ? 'فاقد انقضا' : p360hr_h(p360hr_date_jalali((string)($r['expiry_date'] ?? '')))) . '</td>';
            echo '<td>' . p360hr_h((string)$r['derived_status_fa']) . '</td>';
            echo '<td>' . p360hr_h((string)($r['medical_center_name'] ?? '')) . '</td>';
            echo '<td>';
            if (!empty($r['document_id'])) {
                echo '<a href="hr-document-download.php?id=' . (int)$r['document_id'] . '">مشاهده / دانلود محافظت‌شده</a>';
            } else {
                echo '—';
            }
            echo '</td></tr>';
        }
        if ($reports === []) {
            echo '<tr><td colspan="6">گزارش طب کاری ثبت نشده است.</td></tr>';
        }
        echo '</tbody></table>';
        break;
    case 'contracts':
        echo '<p><a href="my-contracts.php">مشاهده و پذیرش قراردادهای پرسنلی</a></p>';
        break;
    case 'attendance':
        echo '<p><a href="my-attendance.php">مشاهده حضور و غیاب</a></p>';
        break;
    case 'requests':
        echo '<p><a href="my-requests.php">ثبت و پیگیری درخواست‌ها</a></p>';
        break;
    case 'payroll':
        echo '<p><a href="my-payslips.php">مشاهده فیش‌های قابل‌نمایش</a></p>';
        break;
    case 'login_device':
        $u = p360hr_current_core_user_row();
        echo '<p>نام کاربری: ' . p360hr_h((string)($u['username'] ?? '')) . '</p>';
        echo '<p>کد دستگاه انگشت‌زن: ' . p360hr_h(p360hr_fingerprint_code($eid)) . '</p>';
        echo '<p>وضعیت تغییر رمز: ' . (p360hr_must_change_password($u) ? 'نیازمند تغییر' : 'به‌روز') . '</p>';
        break;
    case 'history':
        echo '<p>تاریخچه تغییرات از مسیرهای حسابرسی مرکزی/پرسنلی در فازهای بعدی تکمیل می‌شود.</p>';
        break;
}
echo '</section>';
p360hr_layout_end();
