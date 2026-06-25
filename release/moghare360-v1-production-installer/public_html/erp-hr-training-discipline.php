<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$employeeId = hr_get_int('employee_id');
$connection = false;
$errorMessage = '';
$employees = [];
$trainings = [];
$disciplinary = [];
$flash = hr_flash(hr_get_string('ok'));

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.training.write');
    if (hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT employee_id, employee_code, full_name FROM dbo.erp_hr_employees ORDER BY employee_id DESC');
    }
    if (hr_table_exists($connection, 'erp_hr_training_records')) {
        $trainings = hr_fetch_rows($connection, 'SELECT TOP 20 t.training_id, t.training_title, t.training_type, t.result_status, e.full_name FROM dbo.erp_hr_training_records t INNER JOIN dbo.erp_hr_employees e ON e.employee_id=t.employee_id ORDER BY t.training_id DESC');
    }
    if (hr_table_exists($connection, 'erp_hr_disciplinary_records')) {
        $disciplinary = hr_fetch_rows($connection, 'SELECT TOP 20 d.disciplinary_id, d.record_type, d.record_title, d.severity_level, d.record_date, e.full_name FROM dbo.erp_hr_disciplinary_records d INNER JOIN dbo.erp_hr_employees e ON e.employee_id=d.employee_id ORDER BY d.disciplinary_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'صفحه آموزش و انضباط قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_render_head('آموزش و انضباط');
echo '<div class="p7hr-hero"><h1>آموزش و ترفیع/تنبیه</h1><p>ثبت رکوردهای پرسنلی</p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . hr_h($flash) . '</p></div>';
if ($errorMessage !== '') hr_error('آموزش/انضباط', $errorMessage);

echo '<div class="p7hr-split">';
echo '<form class="p1cc-card" method="post" action="submit-hr-training-record.php">';
echo '<h2 class="p7hr-section-title">ثبت آموزش</h2>';
echo erp_csrf_input('hr_training_record');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کارمند *</label><select class="p1cc-select" name="employee_id" required><option value="">انتخاب</option>';
foreach ($employees as $e) {
    $id = (int)($e['employee_id'] ?? 0);
    $sel = $employeeId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . hr_h(($e['full_name'] ?? '') . ' (' . ($e['employee_code'] ?? '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">عنوان آموزش *</label><input class="p1cc-input" name="training_title" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="training_type">';
foreach (['INTERNAL'=>'داخلی','EXTERNAL'=>'خارجی','SAFETY'=>'ایمنی','TECHNICAL'=>'فنی','ADMIN'=>'اداری'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ</label><input class="p1cc-input m360-ltr" type="date" name="training_date"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مدرس</label><input class="p1cc-input" name="trainer_name" maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نتیجه</label><select class="p1cc-select" name="result_status">';
foreach (['RECORDED'=>'ثبت','PASSED'=>'قبول','FAILED'=>'رد','NEEDS_RETRAINING'=>'نیاز به تکرار'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">امتیاز</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="score_value"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="notes" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت آموزش</button></form>';

echo '<form class="p1cc-card" method="post" action="submit-hr-disciplinary-record.php">';
echo '<h2 class="p7hr-section-title">ترفیع / تنبیه</h2>';
echo erp_csrf_input('hr_disciplinary_record');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کارمند *</label><select class="p1cc-select" name="employee_id" required><option value="">انتخاب</option>';
foreach ($employees as $e) {
    $id = (int)($e['employee_id'] ?? 0);
    $sel = $employeeId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . hr_h(($e['full_name'] ?? '') . ' (' . ($e['employee_code'] ?? '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع *</label><select class="p1cc-select" name="record_type" required>';
foreach (['WARNING'=>'اخطار','REWARD'=>'پاداش','PROMOTION_NOTE'=>'یادداشت ترفیع','PERFORMANCE_NOTE'=>'عملکرد','DISCIPLINARY_NOTE'=>'انضباطی'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">عنوان *</label><input class="p1cc-input" name="record_title" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ *</label><input class="p1cc-input m360-ltr" type="date" name="record_date" required value="' . hr_h(date('Y-m-d')) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">شدت</label><select class="p1cc-select" name="severity_level">';
foreach (['LOW'=>'کم','MEDIUM'=>'متوسط','HIGH'=>'زیاد','POSITIVE'=>'مثبت'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">اقدام</label><textarea class="p1cc-textarea" name="action_taken" maxlength="1500"></textarea></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="notes" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت رکورد</button></form>';
echo '</div>';

if ($trainings !== []) {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">آخرین آموزش‌ها</h2><table class="p1cc-table"><thead><tr><th>کارمند</th><th>عنوان</th><th>نوع</th><th>نتیجه</th></tr></thead><tbody>';
    foreach ($trainings as $t) {
        echo '<tr><td>' . hr_h($t['full_name'] ?? '') . '</td><td>' . hr_h($t['training_title'] ?? '') . '</td>';
        echo '<td>' . hr_h($t['training_type'] ?? '') . '</td>';
        echo '<td><span class="p1cc-badge ' . hr_badge_class($t['result_status'] ?? '') . '">' . hr_h($t['result_status'] ?? '') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($disciplinary !== []) {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">آخرین ترفیع/تنبیه</h2><table class="p1cc-table"><thead><tr><th>کارمند</th><th>نوع</th><th>عنوان</th><th>شدت</th><th>تاریخ</th></tr></thead><tbody>';
    foreach ($disciplinary as $d) {
        echo '<tr><td>' . hr_h($d['full_name'] ?? '') . '</td><td>' . hr_h($d['record_type'] ?? '') . '</td><td>' . hr_h($d['record_title'] ?? '') . '</td>';
        echo '<td><span class="p1cc-badge ' . hr_badge_class($d['severity_level'] ?? '') . '">' . hr_h($d['severity_level'] ?? '') . '</span></td>';
        echo '<td class="m360-ltr">' . hr_h($d['record_date'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

hr_render_foot();
