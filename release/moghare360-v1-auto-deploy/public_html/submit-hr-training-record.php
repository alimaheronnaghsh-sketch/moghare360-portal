<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_training_record', $_POST['erp_csrf_token'] ?? null);

$employeeId = hr_post_int('employee_id');
$title = hr_post_string('training_title');

if ($employeeId === null || $title === '') {
    hr_error('خطای اعتبارسنجی', 'کارمند و عنوان آموزش الزامی است.');
}

$connection = false;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.training.write');

    if (!hr_table_exists($connection, 'erp_hr_training_records')) {
        throw new RuntimeException('جدول erp_hr_training_records یافت نشد.');
    }

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_training_records (employee_id, training_title, training_type, training_date, trainer_name, result_status, score_value, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
        [
            $employeeId, $title,
            hr_post_string('training_type') ?: 'INTERNAL',
            hr_validate_date(hr_post_string('training_date')),
            hr_post_string('trainer_name') ?: null,
            hr_post_string('result_status') ?: 'RECORDED',
            hr_post_float('score_value'),
            hr_post_string('notes') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت آموزش انجام نشد.');

    $trainingId = hr_scope_identity($connection);
    hr_insert_history($connection, 'TRAINING', $trainingId, 'CREATE', 'ثبت رکورد آموزش', null, $title);
} catch (Throwable) {
    hr_error('خطا', 'ثبت آموزش انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-hr-training-discipline.php?employee_id=' . $employeeId . '&ok=training_ok');
