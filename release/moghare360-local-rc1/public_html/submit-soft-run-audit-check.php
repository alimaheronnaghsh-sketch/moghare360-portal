<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    br_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('br_soft_run_audit', $_POST['erp_csrf_token'] ?? null);

$checkCode = trim((string)($_POST['check_code'] ?? ''));
$checkStatus = trim((string)($_POST['check_status'] ?? 'PASSED'));
$checkScore = is_numeric($_POST['check_score'] ?? '') ? (float)$_POST['check_score'] : 10.0;
$checkNote = trim((string)($_POST['check_note'] ?? ''));

if ($checkCode === '') {
    br_error('خطای اعتبارسنجی', 'کد چک الزامی است.');
}

$connection = false;
try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.audit');

    if (!business_ready_table_exists($connection, 'erp_soft_run_audit_checks')) {
        throw new RuntimeException('جدول erp_soft_run_audit_checks یافت نشد.');
    }

    $exists = business_ready_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_soft_run_audit_checks WHERE check_code=?', [$checkCode]);
    if ($exists === '0') {
        throw new RuntimeException('کد چک یافت نشد.');
    }

    $ok = business_ready_execute(
        $connection,
        'UPDATE dbo.erp_soft_run_audit_checks SET check_status=?, check_score=?, check_note=?, checked_at=SYSUTCDATETIME(), checked_by=? WHERE check_code=?',
        [$checkStatus, $checkScore, $checkNote ?: null, br_safe_current_user(), $checkCode]
    );
    if ($ok === false) throw new RuntimeException('به‌روزرسانی audit انجام نشد.');

    business_ready_insert_report_history($connection, 'SOFT_RUN_AUDIT', 'Audit ' . $checkCode, $checkStatus . ' score=' . $checkScore);
} catch (Throwable) {
    br_error('خطا', 'ثبت audit انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_safe_redirect('erp-soft-run-audit.php?ok=audit_ok');
