<?php
declare(strict_types=1);

require_once __DIR__ . '/fin360-finance-helper.php';

/**
 * Post a financial document using fin360_posting_rules.
 * Returns ['ok'=>bool,'message'=>string,'journal_id'=>?int]
 */
function fin360_post_document($conn, int $documentId, string $actor): array
{
    $doc = fin360_one($conn, 'SELECT * FROM dbo.fin360_documents WHERE document_id=?', [$documentId]);
    if (!$doc) {
        return ['ok' => false, 'message' => 'سند یافت نشد.', 'journal_id' => null];
    }
    $status = strtoupper((string)$doc['document_status']);
    if ($status === 'POSTED') {
        return ['ok' => false, 'message' => 'سند قبلاً Posted شده است.', 'journal_id' => null];
    }
    if ($status !== 'APPROVED') {
        return ['ok' => false, 'message' => 'فقط اسناد APPROVED قابل Post هستند.', 'journal_id' => null];
    }

    $docType = (string)$doc['document_type'];
    $eventMap = [
        'CUSTOMER_RECEIPT' => 'CUSTOMER_RECEIPT',
        'GENERAL_RECEIPT' => 'CUSTOMER_RECEIPT',
        'CUSTOMER_PREPAYMENT' => 'CUSTOMER_PREPAYMENT',
        'SERVICE_INVOICE' => 'SERVICE_INVOICE',
        'PARTS_INVOICE' => 'PARTS_INVOICE',
        'PURCHASE_INVOICE' => 'PURCHASE_INVOICE',
        'EXTERNAL_SERVICE_INVOICE' => 'PURCHASE_INVOICE',
        'PAYMENT' => 'SUPPLIER_PAYMENT',
        'SUPPLIER_PAYMENT' => 'SUPPLIER_PAYMENT',
        'GENERAL_PAYMENT' => 'SUPPLIER_PAYMENT',
        'JOBCARD_SETTLEMENT' => 'JOBCARD_SETTLEMENT',
    ];
    $event = $eventMap[$docType] ?? $docType;

    $rule = fin360_one(
        $conn,
        "SELECT TOP 1 * FROM dbo.fin360_posting_rules
         WHERE is_active=1 AND approval_status='APPROVED' AND document_type=? AND event_code=?
           AND effective_from <= CAST(GETDATE() AS DATE)
           AND (effective_to IS NULL OR effective_to >= CAST(GETDATE() AS DATE))
         ORDER BY version_no DESC",
        [$docType, $event]
    );
    if (!$rule) {
        fin360_audit($conn, 'POST_ATTEMPT_NO_RULE', 'fin360_documents', (string)$documentId, $doc, null, 'قانون ثبت حسابداری برای این رویداد تعریف نشده است.');
        return ['ok' => false, 'message' => 'قانون ثبت حسابداری برای این رویداد تعریف نشده است.', 'journal_id' => null];
    }

    $amount = (float)$doc['total_amount'];
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'مبلغ سند برای ثبت حسابداری معتبر نیست.', 'journal_id' => null];
    }

    $debitId = (int)$rule['debit_account_id'];
    $creditId = (int)$rule['credit_account_id'];
    $code = fin360_next_code($conn, 'JRN', 'fin360_journal_headers', 'journal_id');

    $ok = fin360_exec(
        $conn,
        "INSERT INTO dbo.fin360_journal_headers (journal_code, document_id, journal_date, journal_status, description, total_debit, total_credit, created_by, posted_by, posted_at)
         VALUES (?, ?, CAST(GETDATE() AS DATE), 'POSTED', ?, ?, ?, ?, ?, GETDATE())",
        [$code, $documentId, 'Post ' . $docType . ' #' . $documentId, $amount, $amount, $actor, $actor]
    );
    if (!$ok) {
        return ['ok' => false, 'message' => 'ایجاد سند حسابداری ناموفق بود.', 'journal_id' => null];
    }
    $jid = (int)fin360_scalar($conn, 'SELECT MAX(journal_id) FROM dbo.fin360_journal_headers WHERE journal_code=?', [$code]);

    fin360_exec(
        $conn,
        'INSERT INTO dbo.fin360_journal_lines (journal_id, account_id, debit_amount, credit_amount, party_id, jobcard_ref_text, description)
         VALUES (?,?,?,?,?,?,?)',
        [$jid, $debitId, $amount, 0, $doc['party_id'] ?? null, $doc['jobcard_ref_text'] ?? null, 'Debit']
    );
    fin360_exec(
        $conn,
        'INSERT INTO dbo.fin360_journal_lines (journal_id, account_id, debit_amount, credit_amount, party_id, jobcard_ref_text, description)
         VALUES (?,?,?,?,?,?,?)',
        [$jid, $creditId, 0, $amount, $doc['party_id'] ?? null, $doc['jobcard_ref_text'] ?? null, 'Credit']
    );

    $balDebit = (float)fin360_scalar($conn, 'SELECT SUM(debit_amount) FROM dbo.fin360_journal_lines WHERE journal_id=?', [$jid]);
    $balCredit = (float)fin360_scalar($conn, 'SELECT SUM(credit_amount) FROM dbo.fin360_journal_lines WHERE journal_id=?', [$jid]);
    if (abs($balDebit - $balCredit) > 0.01) {
        fin360_exec($conn, "UPDATE dbo.fin360_journal_headers SET journal_status='CANCELLED' WHERE journal_id=?", [$jid]);
        fin360_audit($conn, 'POST_UNBALANCED', 'fin360_journal_headers', (string)$jid, null, ['debit' => $balDebit, 'credit' => $balCredit]);
        return ['ok' => false, 'message' => 'سند حسابداری نامتوازن است؛ ثبت لغو شد.', 'journal_id' => null];
    }

    fin360_exec($conn, "UPDATE dbo.fin360_documents SET document_status='POSTED', posted_by=?, updated_at=SYSUTCDATETIME() WHERE document_id=?", [$actor, $documentId]);
    fin360_audit($conn, 'POST', 'fin360_documents', (string)$documentId, $doc, ['status' => 'POSTED', 'journal_id' => $jid], null, $actor);

    return ['ok' => true, 'message' => 'سند با موفقیت Posted شد.', 'journal_id' => $jid];
}

function fin360_create_demo_posting_rules($conn, string $actor): int
{
    // Seed chart: 1000 cash, 1100 AR, 2000 AP, 2100 customer prepay, 4000 svc rev, 4100 parts rev, 5000 parts COGS
    $map = [
        ['CUSTOMER_RECEIPT', 'CUSTOMER_RECEIPT', '1000', '1100'],
        ['GENERAL_RECEIPT', 'CUSTOMER_RECEIPT', '1000', '1100'],
        ['CUSTOMER_PREPAYMENT', 'CUSTOMER_PREPAYMENT', '1000', '2100'],
        ['SERVICE_INVOICE', 'SERVICE_INVOICE', '1100', '4000'],
        ['PARTS_INVOICE', 'PARTS_INVOICE', '1100', '4100'],
        ['PURCHASE_INVOICE', 'PURCHASE_INVOICE', '5000', '2000'],
        ['PAYMENT', 'SUPPLIER_PAYMENT', '2000', '1000'],
        ['SUPPLIER_PAYMENT', 'SUPPLIER_PAYMENT', '2000', '1000'],
        ['GENERAL_PAYMENT', 'SUPPLIER_PAYMENT', '2000', '1000'],
        ['JOBCARD_SETTLEMENT', 'JOBCARD_SETTLEMENT', '1100', '4000'],
    ];
    $created = 0;
    foreach ($map as [$docType, $event, $debitCode, $creditCode]) {
        $exists = fin360_one($conn, 'SELECT posting_rule_id FROM dbo.fin360_posting_rules WHERE document_type=? AND event_code=? AND is_active=1', [$docType, $event]);
        if ($exists) {
            continue;
        }
        $d = fin360_one($conn, 'SELECT account_id FROM dbo.fin360_accounts WHERE account_code=?', [$debitCode]);
        $c = fin360_one($conn, 'SELECT account_id FROM dbo.fin360_accounts WHERE account_code=?', [$creditCode]);
        if (!$d || !$c) {
            // resolve by name fallbacks
            continue;
        }
        $ok = fin360_exec(
            $conn,
            "INSERT INTO dbo.fin360_posting_rules (event_code, document_type, debit_account_id, credit_account_id, effective_from, version_no, approval_status, is_active)
             VALUES (?,?,?,?,CAST(GETDATE() AS DATE),1,'APPROVED',1)",
            [$event, $docType, (int)$d['account_id'], (int)$c['account_id']]
        );
        if ($ok) {
            $created++;
            fin360_audit($conn, 'CREATE', 'fin360_posting_rules', null, null, ['event' => $event, 'doc' => $docType], 'seed rule');
        }
    }
    return $created;
}
