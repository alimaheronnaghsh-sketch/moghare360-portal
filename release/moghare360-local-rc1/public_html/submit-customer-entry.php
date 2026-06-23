<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Submit Customer Entry (controlled write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    customer_core_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('customer_core_entry', $_POST['erp_csrf_token'] ?? null);

$fullName = customer_core_post_string('full_name');
$mobile = customer_core_normalize_mobile(customer_core_post_string('mobile'));
$nationalCode = customer_core_post_string('national_code');
$licensePlate = customer_core_normalize_plate(customer_core_post_string('license_plate'));
$intakeChannel = customer_core_post_string('intake_channel');
$intakeType = customer_core_post_string('intake_type');
$sourceDescription = customer_core_post_string('source_description');
$notes = customer_core_post_string('notes');

$allowedChannels = ['WALK_IN', 'PHONE', 'WHATSAPP', 'WEBSITE', 'INSTAGRAM', 'REFERRAL', 'CORPORATE'];
$allowedTypes = ['CUSTOMER', 'LEAD'];

$errors = [];

if ($fullName === '') {
    $errors[] = 'نام کامل الزامی است.';
}

if ($mobile === '') {
    $errors[] = 'موبایل الزامی است.';
}

if ($intakeChannel === '' || !in_array($intakeChannel, $allowedChannels, true)) {
    $errors[] = 'کانال ورود نامعتبر است.';
}

if ($intakeType === '' || !in_array($intakeType, $allowedTypes, true)) {
    $errors[] = 'نوع ورود نامعتبر است.';
}

if ($errors !== []) {
    customer_core_render_error_page('خطای اعتبارسنجی', implode(' ', $errors));
}

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.entry.create');

    if (!customer_core_table_exists($connection, 'erp_customer_intakes')) {
        throw new RuntimeException('جدول erp_customer_intakes یافت نشد. ابتدا SQL فاز ۱ را اجرا کنید.');
    }

    $duplicate = customer_core_duplicate_check_intake($connection, $mobile, $nationalCode, $licensePlate);
    $duplicateStatus = $duplicate['status'];
    $duplicateReason = $duplicate['reason'] !== '' ? $duplicate['reason'] : null;
    $createdBy = customer_core_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت ورود مشتری انجام نشد.');
    }

    $insertOk = customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_customer_intakes (
            full_name,
            mobile,
            national_code,
            license_plate,
            intake_channel,
            intake_type,
            source_description,
            notes,
            duplicate_status,
            duplicate_reason,
            status,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $fullName,
            $mobile,
            $nationalCode !== '' ? $nationalCode : null,
            $licensePlate !== '' ? $licensePlate : null,
            $intakeChannel,
            $intakeType,
            $sourceDescription !== '' ? $sourceDescription : null,
            $notes !== '' ? $notes : null,
            $duplicateStatus,
            $duplicateReason,
            'OPEN',
            $createdBy,
        ]
    );

    if ($insertOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت ورود مشتری انجام نشد.');
    }

    $intakeId = customer_core_scope_identity($connection);

    if ($intakeId === null) {
        @odbc_rollback($connection);
        throw new RuntimeException('شناسه Intake دریافت نشد.');
    }

    if (!customer_core_insert_history(
        $connection,
        'erp_customer_intakes',
        $intakeId,
        'INTAKE_CREATE',
        'ثبت ورود مشتری جدید — وضعیت تکراری: ' . $duplicateStatus,
        null,
        json_encode([
            'full_name' => $fullName,
            'mobile' => $mobile,
            'duplicate_status' => $duplicateStatus,
        ], JSON_UNESCAPED_UNICODE)
    )) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تاریخچه انجام نشد.');
    }

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت ورود مشتری انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    customer_core_redirect('erp-customer-core-dashboard.php?phase1=customer_entry_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    customer_core_render_error_page('خطا در ثبت', 'ثبت ورود مشتری انجام نشد. لطفاً دوباره تلاش کنید.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
