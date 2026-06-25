<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.employee.write');
    @odbc_close($connection);
} catch (Throwable) {
    hr_error('ثبت کارمند', 'صفحه ثبت کارمند قابل بارگذاری نیست.');
}

hr_render_head('ثبت کارمند');
echo '<div class="p7hr-hero"><h1>ثبت کارمند جدید</h1><p>پرونده پرسنلی — controlled write</p></div>';
echo '<form class="p1cc-card" method="post" action="submit-employee-create.php">';
echo erp_csrf_input('hr_employee_create');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نام کامل *</label><input class="p1cc-input" name="full_name" required maxlength="250"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">موبایل</label><input class="p1cc-input m360-ltr" name="mobile" maxlength="50"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد ملی</label><input class="p1cc-input m360-ltr" name="national_code" maxlength="50"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ تولد</label><input class="p1cc-input m360-ltr" type="date" name="birth_date"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت تأهل</label><select class="p1cc-select" name="marital_status"><option value="">—</option><option value="SINGLE">مجرد</option><option value="MARRIED">متأهل</option><option value="UNKNOWN">نامشخص</option></select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تعداد فرزند</label><input class="p1cc-input m360-ltr" type="number" min="0" name="children_count"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تماس اضطراری</label><input class="p1cc-input" name="emergency_contact_name" maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">موبایل اضطراری</label><input class="p1cc-input m360-ltr" name="emergency_contact_mobile" maxlength="50"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">دپارتمان</label><input class="p1cc-input" name="department_name" maxlength="150"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">سمت</label><input class="p1cc-input" name="position_title" maxlength="150"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ استخدام</label><input class="p1cc-input m360-ltr" type="date" name="hire_date"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="notes" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت کارمند</button></form>';
hr_render_foot();
