<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_disciplinary_record', $_POST['erp_csrf_token'] ?? null);

$employeeId = hr_post_int('employee_id');
$recordType = hr_post_string('record_type');
$recordTitle = hr_post_string('record_title');
$recordDate = hr_validate_date(hr_post_string('record_date'));

if ($employeeId === null || $recordType === '' || $recordTitle === '' || $recordDate === null) {
    hr_error('خطای اعتبارسنجی', 'کارمند، نوع، عنوان و تاریخ الزامی است.');
}

$connection = false;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.training.write');

    if (!hr_table_exists($connection, 'erp_hr_disciplinary_records')) {
        throw new RuntimeException('جدول erp_hr_disciplinary_records یافت نشد.');
    }

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_disciplinary_records (employee_id, record_type, record_title, record_date, severity_level, action_taken, notes, created_by) VALUES (?,?,?,?,?,?,?,?)',
        [
            $employeeId, $recordType, $recordTitle, $recordDate,
            hr_post_string('severity_level') ?: 'LOW',
            hr_post_string('action_taken') ?: null,
            hr_post_string('notes') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت رکورد انضباطی انجام نشد.');

    $disciplinaryId = hr_scope_identity($connection);
    hr_insert_history($connection, 'DISCIPLINARY', $disciplinaryId, 'CREATE', 'ثبت ترفیع/تنبیه', null, $recordTitle);
} catch (Throwable) {
    hr_error('خطا', 'ثبت رکورد انضباطی انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-hr-training-discipline.php?employee_id=' . $employeeId . '&ok=disciplinary_ok');
