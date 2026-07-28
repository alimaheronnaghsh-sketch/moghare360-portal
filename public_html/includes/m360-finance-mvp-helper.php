<?php
declare(strict_types=1);

/**
 * MOGHARE360 Prompt 4 — finance MVP helper (invoice snapshot / payment / ledger / settlement gate).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-auth-context.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-delivery-readiness-helper.php';

const M360_FIN_LEDGER_TABLE = 'erp_customer_ledger_entries';
const M360_FIN_AUDIT_TABLE = 'erp_finance_audit_events';

function m360_fin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_fin_require_staff(): void
{
    erp_auth_context_start();
    $userId = (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
    if ($userId < 1) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_fin_actor_user_id(): int
{
    return (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
}

function m360_fin_audit($conn, string $entityType, int $entityId, string $eventName, ?string $note, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_FIN_AUDIT_TABLE) || $entityId < 1) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FIN_AUDIT_TABLE . ' (entity_type, entity_id, event_name, event_note, created_by_user_id)
         VALUES (?, ?, ?, ?, ?)',
        [$entityType, $entityId, $eventName, $note, $userId > 0 ? $userId : null]
    );
}

function m360_fin_customer_balance($conn, int $customerId): float
{
    if (!customer_core_table_exists($conn, M360_FIN_LEDGER_TABLE) || $customerId < 1) {
        return 0.0;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT ISNULL(SUM(debit_amount - credit_amount), 0) AS bal
         FROM dbo.' . M360_FIN_LEDGER_TABLE . '
         WHERE customer_id = ? AND is_reversed = 0',
        [$customerId]
    );
    return (float)($rows[0]['bal'] ?? 0);
}

/** @return array{ok:bool,message:string,ledger_entry_id:?int} */
function m360_fin_ledger_post(
    $conn,
    int $customerId,
    string $entryType,
    float $debit,
    float $credit,
    ?int $jobcardId,
    ?int $invoiceId,
    ?int $paymentId,
    string $reference,
    string $note,
    int $userId
): array {
    if (!customer_core_table_exists($conn, M360_FIN_LEDGER_TABLE) || $customerId < 1) {
        return ['ok' => false, 'message' => 'دفتر مشتری آماده نیست.', 'ledger_entry_id' => null];
    }
    $entryType = strtoupper(trim($entryType));
    if (!in_array($entryType, ['DEBIT', 'CREDIT', 'REVERSAL'], true)) {
        return ['ok' => false, 'message' => 'نوع سند دفتر نامعتبر است.', 'ledger_entry_id' => null];
    }
    if ($debit < 0 || $credit < 0 || ($debit <= 0 && $credit <= 0)) {
        return ['ok' => false, 'message' => 'مبالغ دفتر نامعتبر است.', 'ledger_entry_id' => null];
    }
    $balanceAfter = m360_fin_customer_balance($conn, $customerId) + $debit - $credit;
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FIN_LEDGER_TABLE . '
            (customer_id, jobcard_id, invoice_id, payment_id, entry_type, debit_amount, credit_amount, balance_after, reference_code, entry_note, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $customerId,
            $jobcardId > 0 ? $jobcardId : null,
            $invoiceId > 0 ? $invoiceId : null,
            $paymentId > 0 ? $paymentId : null,
            $entryType,
            $debit,
            $credit,
            $balanceAfter,
            $reference !== '' ? $reference : null,
            $note !== '' ? $note : null,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت دفتر مشتری ناموفق بود.', 'ledger_entry_id' => null];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 ledger_entry_id FROM dbo.' . M360_FIN_LEDGER_TABLE . ' WHERE customer_id = ? ORDER BY ledger_entry_id DESC',
        [$customerId]
    ) ?? 0);
    return ['ok' => true, 'message' => 'سند دفتر ثبت شد.', 'ledger_entry_id' => $id > 0 ? $id : null];
}

/** @return array{ok:bool,message:string,invoice_id:?int} */
function m360_fin_create_invoice_from_jobcard($conn, int $jobcardId, float $serviceAmount, float $partsAmount, int $userId): array
{
    if (!customer_core_table_exists($conn, 'erp_final_invoices') || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'جدول فاکتور آماده نیست.', 'invoice_id' => null];
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE jobcard_id = ?', [$jobcardId]);
    if ($rows === []) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.', 'invoice_id' => null];
    }
    $jc = $rows[0];
    $customerId = (int)($jc['customer_id'] ?? 0);
    $vehicleId = (int)($jc['vehicle_id'] ?? 0);
    $requestId = (int)($jc['online_request_id'] ?? 0);
    $subtotal = max(0.0, $serviceAmount) + max(0.0, $partsAmount);
    if ($subtotal <= 0) {
        return ['ok' => false, 'message' => 'مبلغ فاکتور باید بزرگ‌تر از صفر باشد.', 'invoice_id' => null];
    }
    $invoiceNo = 'INV-' . $jobcardId . '-' . gmdate('YmdHis');
    // Align with existing local statuses (CALCULATED/FINALIZED) while exposing paid/balance snapshots.
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_final_invoices
            (jobcard_id, customer_id, vehicle_id, invoice_no, invoice_status, subtotal_amount, discount_amount, tax_amount, total_amount,
             paid_amount, balance_amount, invoice_type, currency_code, online_request_id, created_by_user_id)
         VALUES (?, ?, ?, ?, N\'FINALIZED\', ?, 0, 0, ?, 0, ?, N\'mixed\', N\'IRR\', ?, ?)',
        [
            $jobcardId,
            $customerId > 0 ? $customerId : null,
            $vehicleId > 0 ? $vehicleId : null,
            $invoiceNo,
            $subtotal,
            $subtotal,
            $subtotal,
            $requestId > 0 ? $requestId : null,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد فاکتور ناموفق بود.', 'invoice_id' => null];
    }
    $invoiceId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 final_invoice_id FROM dbo.erp_final_invoices WHERE invoice_no = ? ORDER BY final_invoice_id DESC',
        [$invoiceNo]
    ) ?? 0);
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'شناسه فاکتور پس از ایجاد دریافت نشد.', 'invoice_id' => null];
    }
    if ($invoiceId > 0 && customer_core_table_exists($conn, 'erp_final_invoice_items')) {
        if ($serviceAmount > 0) {
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.erp_final_invoice_items
                    (final_invoice_id, jobcard_id, source_type, item_type, item_title, quantity, unit_price, line_total)
                 VALUES (?, ?, N\'SERVICE\', N\'SERVICE\', N\'خدمات تعمیرات\', 1, ?, ?)',
                [$invoiceId, $jobcardId, $serviceAmount, $serviceAmount]
            );
        }
        if ($partsAmount > 0) {
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.erp_final_invoice_items
                    (final_invoice_id, jobcard_id, source_type, item_type, item_title, quantity, unit_price, line_total)
                 VALUES (?, ?, N\'PART\', N\'PART\', N\'قطعات مصرفی\', 1, ?, ?)',
                [$invoiceId, $jobcardId, $partsAmount, $partsAmount]
            );
        }
    }
    if ($customerId > 0) {
        m360_fin_ledger_post($conn, $customerId, 'DEBIT', $subtotal, 0, $jobcardId, $invoiceId, null, $invoiceNo, 'صدور فاکتور', $userId);
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET final_invoice_status = N\'FINALIZED\', current_final_invoice_id = ?, final_invoice_amount = ?, settlement_status = N\'UNPAID\', settlement_remaining_amount = ?, settlement_amount_paid = 0, finance_gate_status = N\'UNPAID\', updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
        [$invoiceId, $subtotal, $subtotal, $jobcardId]
    );
    m360_fin_audit($conn, 'INVOICE', $invoiceId, 'INVOICE_ISSUED', $invoiceNo, $userId);
    return ['ok' => true, 'message' => 'فاکتور صادر شد.', 'invoice_id' => $invoiceId > 0 ? $invoiceId : null];
}

/** @return array{ok:bool,message:string,payment_id:?int,balance:?float} */
function m360_fin_register_payment($conn, int $invoiceId, float $amount, string $method, int $userId): array
{
    if (!customer_core_table_exists($conn, 'erp_payments') || $invoiceId < 1 || $amount <= 0) {
        return ['ok' => false, 'message' => 'پرداخت نامعتبر است.', 'payment_id' => null, 'balance' => null];
    }
    $invRows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_final_invoices WHERE final_invoice_id = ?', [$invoiceId]);
    if ($invRows === []) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'payment_id' => null, 'balance' => null];
    }
    $inv = $invRows[0];
    $status = strtoupper(trim((string)($inv['invoice_status'] ?? '')));
    if (in_array($status, ['CANCELLED', 'VOIDED', 'VOID'], true) || trim((string)($inv['voided_at'] ?? '')) !== '') {
        return ['ok' => false, 'message' => 'فاکتور باطل‌شده قابل پرداخت نیست.', 'payment_id' => null, 'balance' => null];
    }
    $total = (float)($inv['total_amount'] ?? 0);
    $paid = (float)($inv['paid_amount'] ?? 0);
    $balance = max(0.0, $total - $paid);
    if ($amount > $balance + 0.0001) {
        return ['ok' => false, 'message' => 'مبلغ پرداخت از مانده فاکتور بیشتر است.', 'payment_id' => null, 'balance' => $balance];
    }
    $jobcardId = (int)($inv['jobcard_id'] ?? 0);
    $customerId = (int)($inv['customer_id'] ?? 0);
    $method = strtoupper(trim($method));
    $methodMap = [
        'CASH' => 'CASH',
        'CARD' => 'CARD',
        'POS' => 'POS',
        'TRANSFER' => 'BANK_TRANSFER',
        'BANK_TRANSFER' => 'BANK_TRANSFER',
        'CHEQUE' => 'OTHER',
        'CREDIT' => 'OTHER',
        'OTHER' => 'OTHER',
    ];
    $method = $methodMap[$method] ?? 'CASH';
    $paymentType = ($amount + 0.0001 >= $balance) ? 'FULL' : 'PARTIAL';
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_payments
            (jobcard_id, customer_id, payment_type, payment_method, payment_amount, currency_code, payment_status, payment_reference, payment_note, received_by_user_id, received_at, is_active)
         VALUES (?, ?, ?, ?, ?, N\'IRR\', N\'RECEIVED\', ?, ?, ?, SYSUTCDATETIME(), 1)',
        [
            $jobcardId > 0 ? $jobcardId : null,
            $customerId > 0 ? $customerId : null,
            $paymentType,
            $method,
            $amount,
            'INV-' . $invoiceId,
            'پرداخت فاکتور ' . $invoiceId,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت پرداخت ناموفق بود.', 'payment_id' => null, 'balance' => $balance];
    }
    $paymentId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 payment_id FROM dbo.erp_payments WHERE jobcard_id = ? ORDER BY payment_id DESC',
        [$jobcardId]
    ) ?? 0);
    $newPaid = $paid + $amount;
    $newBalance = max(0.0, $total - $newPaid);
    $newStatus = $newBalance <= 0.0001 ? 'PAID' : 'PARTIALLY_PAID';
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_final_invoices SET paid_amount = ?, balance_amount = ?, invoice_status = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ?',
        [$newPaid, $newBalance, $newStatus, $invoiceId]
    );
    if ($customerId > 0) {
        m360_fin_ledger_post($conn, $customerId, 'CREDIT', 0, $amount, $jobcardId, $invoiceId, $paymentId, 'PAY-' . $paymentId, 'پرداخت فاکتور', $userId);
    }
    if ($jobcardId > 0) {
        $settleStatus = $newBalance <= 0.0001 ? 'PAID' : 'PARTIAL';
        $financeGate = $newBalance <= 0.0001 ? 'PAID' : 'PARTIAL';
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET settlement_status = ?, finance_gate_status = ?, settlement_amount_paid = ?, settlement_remaining_amount = ?, updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
            [$settleStatus, $financeGate, $newPaid, $newBalance, $jobcardId]
        );
    }
    m360_fin_audit($conn, 'PAYMENT', $paymentId, 'PAYMENT_RECEIVED', 'invoice=' . $invoiceId . ';amount=' . $amount, $userId);
    return ['ok' => true, 'message' => 'پرداخت ثبت شد.', 'payment_id' => $paymentId > 0 ? $paymentId : null, 'balance' => $newBalance];
}

/** @return array{ok:bool,message:string} */
function m360_fin_void_invoice($conn, int $invoiceId, string $reason, int $userId): array
{
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_final_invoices WHERE final_invoice_id = ?', [$invoiceId]);
    if ($rows === []) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.'];
    }
    $inv = $rows[0];
    if (trim((string)($inv['voided_at'] ?? '')) !== '') {
        return ['ok' => false, 'message' => 'فاکتور قبلاً باطل شده است.'];
    }
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل ابطال الزامی است.'];
    }
    // Physical delete blocked: only void.
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.erp_final_invoices
         SET invoice_status = N\'VOIDED\', voided_at = SYSUTCDATETIME(), void_reason = ?, updated_at = SYSUTCDATETIME()
         WHERE final_invoice_id = ?',
        [mb_substr($reason, 0, 500), $invoiceId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ابطال فاکتور ناموفق بود.'];
    }
    m360_fin_audit($conn, 'INVOICE', $invoiceId, 'INVOICE_VOIDED', $reason, $userId);
    return ['ok' => true, 'message' => 'فاکتور باطل شد (حذف فیزیکی مجاز نیست).'];
}

/** @return array{ok:bool,message:string,allowed:bool} */
function m360_fin_delivery_gate_check($conn, int $jobcardId): array
{
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE jobcard_id = ?', [$jobcardId]);
    if ($rows === []) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.', 'allowed' => false];
    }
    $jc = $rows[0];
    $remaining = (float)($jc['settlement_remaining_amount'] ?? 0);
    $settlement = strtoupper(trim((string)($jc['settlement_status'] ?? '')));
    $finance = strtoupper(trim((string)($jc['finance_gate_status'] ?? '')));
    $paidOk = ($remaining <= 0.0001) && (in_array($settlement, ['PAID', 'SETTLED', 'CLEARED', 'CLOSED'], true) || in_array($finance, ['PAID', 'CLEARED', 'PASSED', 'OK', 'SETTLED'], true));
    if (!$paidOk) {
        return ['ok' => true, 'message' => 'تحویل به‌دلیل مانده تسویه‌نشده مسدود است.', 'allowed' => false];
    }
    $qcOk = m360_delivery_readiness_validate($conn, $jobcardId, $jc, null);
    if (empty($qcOk['ok'])) {
        return ['ok' => true, 'message' => (string)$qcOk['message'], 'allowed' => false];
    }
    return ['ok' => true, 'message' => 'گیت تحویل از نظر مالی و QC باز است.', 'allowed' => true];
}

/** @return list<array<string,mixed>> */
function m360_fin_list_invoices($conn, int $limit = 50): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(200, $limit)) . ' final_invoice_id, jobcard_id, customer_id, invoice_no, invoice_status, total_amount, paid_amount, balance_amount, created_at
         FROM dbo.erp_final_invoices ORDER BY final_invoice_id DESC',
        []
    );
}
