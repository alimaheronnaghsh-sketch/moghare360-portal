<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Submit Payment Record
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    pricing_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('finance_payment_record', $_POST['erp_csrf_token'] ?? null);

$costHeaderId = pricing_post_int('cost_header_id');
$amount = pricing_post_float('payment_amount');
$method = pricing_post_string('payment_method') ?: 'CASH';
$operationCaseId = pricing_post_int('operation_case_id');
$jobcardId = pricing_post_int('jobcard_id');
$customerId = pricing_post_int('customer_id');
$reference = pricing_post_string('payment_reference');
$note = pricing_post_string('payment_note');

$allowedMethods = ['CASH', 'CARD', 'BANK_TRANSFER', 'POS_PLACEHOLDER', 'CREDIT', 'OTHER'];

if ($costHeaderId === null || $amount === null || $amount <= 0) {
    pricing_error('خطای اعتبارسنجی', 'سربرگ هزینه و مبلغ پرداخت الزامی است.');
}
if (!in_array($method, $allowedMethods, true)) {
    pricing_error('خطای اعتبارسنجی', 'روش پرداخت نامعتبر است.');
}

$connection = false;

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    pricing_require_auth($connection, 'finance.payment.write');

    if (!pricing_table_exists($connection, 'erp_payment_records')) {
        throw new RuntimeException('جدول erp_payment_records یافت نشد. ابتدا SQL فاز ۵ را اجرا کنید.');
    }

    $header = pricing_get_cost_header($connection, $costHeaderId);
    if ($header === null) {
        throw new RuntimeException('سربرگ هزینه یافت نشد.');
    }

    if ($operationCaseId === null && ($header['operation_case_id'] ?? '') !== '') {
        $operationCaseId = (int)$header['operation_case_id'];
    }
    if ($jobcardId === null && ($header['jobcard_id'] ?? '') !== '') {
        $jobcardId = (int)$header['jobcard_id'];
    }
    if ($customerId === null && ($header['customer_id'] ?? '') !== '') {
        $customerId = (int)$header['customer_id'];
    }

    $paymentCode = pricing_generate_payment_code();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت پرداخت انجام نشد.');
    }

    $ok = pricing_execute(
        $connection,
        'INSERT INTO dbo.erp_payment_records (cost_header_id, operation_case_id, jobcard_id, customer_id, payment_code, payment_method, payment_amount, payment_status, payment_reference, payment_note, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$costHeaderId, $operationCaseId, $jobcardId, $customerId, $paymentCode, $method, $amount, 'RECORDED', $reference ?: null, $note ?: null, pricing_safe_current_user(), pricing_client_ip(), pricing_user_agent()]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت پرداخت انجام نشد.');
    }

    $paymentId = pricing_scope_identity($connection);
    pricing_recalculate_cost_header($connection, $costHeaderId);
    pricing_update_payment_status($connection, $costHeaderId);
    pricing_insert_history($connection, 'PAYMENT', $paymentId, 'CREATE', 'ثبت پرداخت', null, $paymentCode);

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('ثبت پرداخت انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    pricing_error('خطا', 'ثبت پرداخت انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_safe_redirect('erp-payment-tracking.php?ok=payment_ok&cost_header_id=' . $costHeaderId);
