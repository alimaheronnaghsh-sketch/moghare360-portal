<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/fin360-finance-helper.php';
require_once __DIR__ . '/includes/fin360-posting-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: erp-final-invoice-board.php');
    exit;
}

fin360_csrf_require();

$tab = preg_replace('/[^a-z_]/', '', (string)($_POST['return_tab'] ?? 'dashboard')) ?: 'dashboard';
$action = (string)($_POST['action'] ?? '');
$actorInfo = fin360_actor();
$actor = $actorInfo['actor'];
$flash = '';
$flashType = 'ok';

try {
    $conn = fin360_db();
} catch (Throwable $e) {
    $_SESSION['fin360_flash'] = ['type' => 'err', 'msg' => 'اتصال پایگاه داده برقرار نشد.'];
    header('Location: erp-final-invoice-board.php?tab=' . urlencode($tab));
    exit;
}

function fin360_f(string $k, $default = ''): string
{
    return trim((string)($_POST[$k] ?? $default));
}

function fin360_fn(string $k): float
{
    return (float)str_replace(',', '', (string)($_POST[$k] ?? '0'));
}

function fin360_redirect(string $tab, string $type, string $msg): void
{
    $_SESSION['fin360_flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: erp-final-invoice-board.php?tab=' . urlencode($tab));
    exit;
}

try {
    switch ($action) {
        case 'create_party': {
            $type = fin360_f('party_type', 'CUSTOMER');
            $name = fin360_f('display_name');
            if ($name === '') {
                fin360_redirect($tab, 'err', 'نام طرف حساب الزامی است.');
            }
            fin360_exec(
                $conn,
                'INSERT INTO dbo.fin360_parties (party_type, display_name, mobile, source_ref_text, is_active) VALUES (?,?,?,?,1)',
                [$type, $name, fin360_f('mobile') ?: null, fin360_f('source_ref_text') ?: null]
            );
            $id = (string)fin360_scalar($conn, 'SELECT MAX(party_id) FROM dbo.fin360_parties');
            fin360_audit($conn, 'CREATE', 'fin360_parties', $id, null, ['name' => $name, 'type' => $type]);
            fin360_redirect($tab, 'ok', 'طرف حساب ثبت شد.');
        }

        case 'create_cash_account': {
            $title = fin360_f('account_title');
            $type = fin360_f('account_type', 'CASHBOX');
            $open = fin360_fn('opening_balance');
            if ($title === '') {
                fin360_redirect($tab, 'err', 'عنوان حساب الزامی است.');
            }
            fin360_exec(
                $conn,
                'INSERT INTO dbo.fin360_cash_accounts (account_type, account_title, bank_name, iban, currency_code, opening_balance, current_book_balance, is_active)
                 VALUES (?,?,?,?,?,?,?,1)',
                [$type, $title, fin360_f('bank_name') ?: null, fin360_f('iban') ?: null, fin360_f('currency_code', 'IRR'), $open, $open]
            );
            $id = (string)fin360_scalar($conn, 'SELECT MAX(cash_account_id) FROM dbo.fin360_cash_accounts');
            fin360_audit($conn, 'CREATE', 'fin360_cash_accounts', $id, null, ['title' => $title, 'type' => $type, 'open' => $open]);
            fin360_redirect($tab, 'ok', 'حساب صندوق/بانک ایجاد شد.');
        }

        case 'customer_receipt': {
            $partyId = (int)fin360_f('party_id');
            $cashId = (int)fin360_f('cash_account_id');
            $amount = fin360_fn('amount');
            $ptype = fin360_f('payment_type', 'CASH');
            $pdate = fin360_f('payment_date') ?: date('Y-m-d');
            if ($amount <= 0 || $cashId <= 0 || $ptype === '') {
                fin360_redirect($tab, 'err', 'مبلغ، نوع پرداخت و حساب نقد الزامی است.');
            }
            $payCode = fin360_next_code($conn, 'PAY', 'fin360_payments', 'payment_id');
            $docCode = fin360_next_code($conn, 'RCP', 'fin360_documents', 'document_id');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_payments (payment_code, payment_type, payment_direction, party_id, cash_account_id, payment_date, amount, reference_no, description, status_code, allocation_status, created_by)
                 VALUES (?,?,'IN',?,?,?,?,?,?,'APPROVED','UNALLOCATED',?)",
                [$payCode, $ptype, $partyId ?: null, $cashId, $pdate, $amount, fin360_f('reference_no') ?: null, fin360_f('description') ?: null, $actor]
            );
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, jobcard_ref_text, subtotal_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,'CUSTOMER_RECEIPT','APPROVED',?,?,?,?,?,?,0,?,?)",
                [$docCode, $pdate, $partyId ?: null, fin360_f('jobcard_ref_text') ?: null, $amount, $amount, $amount, $actor, $actor]
            );
            fin360_exec($conn, 'UPDATE dbo.fin360_cash_accounts SET current_book_balance = current_book_balance + ? WHERE cash_account_id=?', [$amount, $cashId]);
            $docId = (string)fin360_scalar($conn, 'SELECT MAX(document_id) FROM dbo.fin360_documents WHERE document_code=?', [$docCode]);
            fin360_audit($conn, 'CREATE', 'fin360_payments', $payCode, null, ['amount' => $amount, 'type' => $ptype]);
            fin360_audit($conn, 'CREATE', 'fin360_documents', $docId, null, ['type' => 'CUSTOMER_RECEIPT', 'amount' => $amount]);
            fin360_redirect($tab, 'ok', 'دریافت مشتری ثبت شد (' . $docCode . ').');
        }

        case 'customer_prepayment': {
            $partyId = (int)fin360_f('party_id');
            $cashId = (int)fin360_f('cash_account_id');
            $amount = fin360_fn('amount');
            if ($amount <= 0 || $cashId <= 0) {
                fin360_redirect($tab, 'err', 'مبلغ و حساب نقد الزامی است.');
            }
            $payCode = fin360_next_code($conn, 'PAY', 'fin360_payments', 'payment_id');
            $docCode = fin360_next_code($conn, 'PRP', 'fin360_documents', 'document_id');
            $pdate = date('Y-m-d');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_payments (payment_code, payment_type, payment_direction, party_id, cash_account_id, payment_date, amount, description, status_code, allocation_status, created_by)
                 VALUES (?,'CASH','IN',?,?,?,?,?,'APPROVED','UNALLOCATED',?)",
                [$payCode, $partyId ?: null, $cashId, $pdate, $amount, fin360_f('description') ?: null, $actor]
            );
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, jobcard_ref_text, subtotal_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,'CUSTOMER_PREPAYMENT','APPROVED',?,?,?,?,?,?,0,?,?)",
                [$docCode, $pdate, $partyId ?: null, fin360_f('jobcard_ref_text') ?: null, $amount, $amount, $amount, $actor, $actor]
            );
            fin360_exec($conn, 'UPDATE dbo.fin360_cash_accounts SET current_book_balance = current_book_balance + ? WHERE cash_account_id=?', [$amount, $cashId]);
            $docId = (string)fin360_scalar($conn, 'SELECT MAX(document_id) FROM dbo.fin360_documents WHERE document_code=?', [$docCode]);
            fin360_audit($conn, 'CREATE', 'fin360_documents', $docId, null, ['type' => 'CUSTOMER_PREPAYMENT', 'amount' => $amount]);
            fin360_redirect($tab, 'ok', 'پیش‌دریافت ثبت شد.');
        }

        case 'service_invoice':
        case 'parts_invoice': {
            $isService = $action === 'service_invoice';
            $docType = $isService ? 'SERVICE_INVOICE' : 'PARTS_INVOICE';
            $lineType = $isService ? 'SERVICE' : 'PART';
            $partyId = (int)fin360_f('party_id');
            $qty = fin360_fn('quantity') ?: 1;
            $price = fin360_fn('unit_price');
            $disc = fin360_fn('discount_amount');
            $tax = fin360_fn('tax_amount');
            $cost = fin360_fn('cost_amount');
            $title = fin360_f('item_title');
            if ($title === '' || $price < 0) {
                fin360_redirect($tab, 'err', 'عنوان و قیمت الزامی است.');
            }
            $sub = $qty * $price;
            $total = $sub - $disc + $tax;
            $docCode = fin360_next_code($conn, $isService ? 'SIV' : 'PIV', 'fin360_documents', 'document_id');
            $pdate = date('Y-m-d');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, jobcard_ref_text, subtotal_amount, discount_amount, tax_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,?, 'APPROVED', ?,?,?,?,?,?,?,0,?,?,?)",
                [$docCode, $docType, $pdate, $partyId ?: null, fin360_f('jobcard_ref_text') ?: null, $sub, $disc, $tax, $total, $total, $actor, $actor]
            );
            $docId = (int)fin360_scalar($conn, 'SELECT MAX(document_id) FROM dbo.fin360_documents WHERE document_code=?', [$docCode]);
            fin360_exec(
                $conn,
                'INSERT INTO dbo.fin360_document_lines (document_id, line_type, item_title, quantity, unit_price, discount_amount, tax_amount, cost_amount, line_total, jobcard_ref_text)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$docId, $lineType, $title, $qty, $price, $disc, $tax, $cost, $total, fin360_f('jobcard_ref_text') ?: null]
            );
            fin360_audit($conn, 'CREATE', 'fin360_documents', (string)$docId, null, ['type' => $docType, 'total' => $total]);
            fin360_redirect($tab, 'ok', 'فاکتور ثبت شد (' . $docCode . ').');
        }

        case 'purchase_invoice': {
            $partyId = (int)fin360_f('party_id');
            $dtype = fin360_f('document_type', 'PURCHASE_INVOICE');
            if (!in_array($dtype, ['PURCHASE_INVOICE', 'EXTERNAL_SERVICE_INVOICE'], true)) {
                $dtype = 'PURCHASE_INVOICE';
            }
            $amount = fin360_fn('amount');
            $tax = fin360_fn('tax_amount');
            $total = $amount + $tax;
            if ($amount <= 0) {
                fin360_redirect($tab, 'err', 'مبلغ خرید الزامی است.');
            }
            $docCode = fin360_next_code($conn, 'PIN', 'fin360_documents', 'document_id');
            $pdate = date('Y-m-d');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, source_ref_text, subtotal_amount, tax_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,?, 'APPROVED', ?,?,?,?,?,?,0,?,?,?)",
                [$docCode, $dtype, $pdate, $partyId ?: null, fin360_f('reference_text') ?: null, $amount, $tax, $total, $total, $actor, $actor]
            );
            $docId = (string)fin360_scalar($conn, 'SELECT MAX(document_id) FROM dbo.fin360_documents WHERE document_code=?', [$docCode]);
            fin360_audit($conn, 'CREATE', 'fin360_documents', $docId, null, ['type' => $dtype, 'total' => $total, 'due' => fin360_f('due_date')]);
            fin360_redirect($tab, 'ok', 'خرید/بدهی ثبت شد.');
        }

        case 'supplier_payment': {
            $partyId = (int)fin360_f('party_id');
            $cashId = (int)fin360_f('cash_account_id');
            $amount = fin360_fn('amount');
            $ptype = fin360_f('payment_type', 'BANK_TRANSFER');
            if ($amount <= 0 || $cashId <= 0) {
                fin360_redirect($tab, 'err', 'مبلغ و حساب نقد الزامی است.');
            }
            $bal = (float)fin360_scalar($conn, 'SELECT current_book_balance FROM dbo.fin360_cash_accounts WHERE cash_account_id=?', [$cashId]);
            if ($amount > $bal) {
                fin360_redirect($tab, 'err', 'موجودی حساب کافی نیست.');
            }
            $payCode = fin360_next_code($conn, 'PAY', 'fin360_payments', 'payment_id');
            $docCode = fin360_next_code($conn, 'SPY', 'fin360_documents', 'document_id');
            $pdate = date('Y-m-d');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_payments (payment_code, payment_type, payment_direction, party_id, cash_account_id, payment_date, amount, reference_no, status_code, allocation_status, created_by)
                 VALUES (?,?,'OUT',?,?,?,?,?,'APPROVED','UNALLOCATED',?)",
                [$payCode, $ptype, $partyId ?: null, $cashId, $pdate, $amount, fin360_f('reference_no') ?: null, $actor]
            );
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, subtotal_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,'PAYMENT','APPROVED',?,?,?,?,?,0,?,?)",
                [$docCode, $pdate, $partyId ?: null, $amount, $amount, $amount, $actor, $actor]
            );
            fin360_exec($conn, 'UPDATE dbo.fin360_cash_accounts SET current_book_balance = current_book_balance - ? WHERE cash_account_id=?', [$amount, $cashId]);
            $docId = (string)fin360_scalar($conn, 'SELECT MAX(document_id) FROM dbo.fin360_documents WHERE document_code=?', [$docCode]);
            fin360_audit($conn, 'CREATE', 'fin360_payments', $payCode, null, ['direction' => 'OUT', 'amount' => $amount]);
            fin360_audit($conn, 'CREATE', 'fin360_documents', $docId, null, ['type' => 'PAYMENT', 'amount' => $amount]);
            fin360_redirect($tab, 'ok', 'پرداخت ثبت شد.');
        }

        case 'jobcard_settlement': {
            $jc = fin360_f('jobcard_ref_text');
            if ($jc === '') {
                fin360_redirect($tab, 'err', 'مرجع JobCard الزامی است.');
            }
            $svc = fin360_fn('service_sales_amount');
            $parts = fin360_fn('parts_sales_amount');
            $ext = fin360_fn('external_service_sales_amount');
            $add = fin360_fn('additional_charges_amount');
            $disc = fin360_fn('discount_amount');
            $tax = fin360_fn('tax_amount');
            $prep = fin360_fn('prepayment_amount');
            $paid = fin360_fn('paid_amount');
            $final = $svc + $parts + $ext + $add + $tax - $disc;
            $remaining = $final - $prep - $paid;
            $override = fin360_f('override_reason');
            if ($remaining < 0) {
                $remaining = 0;
            }
            if ($remaining == 0.0) {
                $st = $override !== '' ? 'SETTLED_WITH_OVERRIDE' : 'SETTLED';
                $rel = $override !== '' ? 'RELEASED_WITH_OVERRIDE' : 'RELEASED';
            } else {
                $st = 'PARTIALLY_SETTLED';
                $rel = 'BLOCKED';
            }
            $code = fin360_next_code($conn, 'JST', 'fin360_jobcard_settlements', 'settlement_id');
            $partyId = (int)fin360_f('party_id');
            fin360_exec(
                $conn,
                'INSERT INTO dbo.fin360_jobcard_settlements
                 (settlement_code, jobcard_ref_text, customer_party_id, service_sales_amount, parts_sales_amount, external_service_sales_amount,
                  additional_charges_amount, discount_amount, tax_amount, prepayment_amount, paid_amount, credit_amount, final_amount, remaining_amount,
                  settlement_status, financial_release_status, override_reason, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?)',
                [$code, $jc, $partyId ?: null, $svc, $parts, $ext, $add, $disc, $tax, $prep, $paid, $final, $remaining, $st, $rel, $override ?: null, $actor]
            );
            $docCode = fin360_next_code($conn, 'JSD', 'fin360_documents', 'document_id');
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_documents (document_code, document_type, document_status, document_date, party_id, jobcard_ref_text, subtotal_amount, discount_amount, tax_amount, total_amount, paid_amount, remaining_amount, created_by, approved_by)
                 VALUES (?,'JOBCARD_SETTLEMENT','APPROVED',CAST(GETDATE() AS DATE),?,?,?,?,?,?,?,?,?)",
                [$docCode, $partyId ?: null, $jc, $svc + $parts + $ext + $add, $disc, $tax, $final, $prep + $paid, $remaining, $actor, $actor]
            );
            $sid = (string)fin360_scalar($conn, 'SELECT MAX(settlement_id) FROM dbo.fin360_jobcard_settlements WHERE settlement_code=?', [$code]);
            fin360_audit($conn, 'CREATE', 'fin360_jobcard_settlements', $sid, null, ['final' => $final, 'remaining' => $remaining, 'status' => $st]);
            fin360_redirect($tab, 'ok', 'تسویه JobCard ثبت شد. مانده: ' . number_format($remaining, 0));
        }

        case 'tax_rule': {
            $rtype = fin360_f('rule_type');
            $legal = fin360_f('legal_reference');
            $formula = fin360_f('rate_or_formula');
            $from = fin360_f('effective_from') ?: date('Y-m-d');
            if ($rtype === '' || $legal === '' || $formula === '') {
                fin360_redirect($tab, 'err', 'نوع قانون، مرجع قانونی و نرخ/فرمول الزامی است.');
            }
            fin360_exec(
                $conn,
                "INSERT INTO dbo.fin360_tax_rules (rule_type, legal_reference, effective_from, rate_or_formula, published_source, approval_status, last_verified_at, is_active)
                 VALUES (?,?,?,?,?,?,GETDATE(),1)",
                [$rtype, $legal, $from, $formula, fin360_f('published_source') ?: null, fin360_f('approval_status', 'DRAFT')]
            );
            $id = (string)fin360_scalar($conn, 'SELECT MAX(tax_rule_id) FROM dbo.fin360_tax_rules');
            fin360_audit($conn, 'CREATE', 'fin360_tax_rules', $id, null, ['type' => $rtype, 'formula' => $formula]);
            fin360_redirect($tab, 'ok', 'قانون مالیاتی پیکربندی شد (بدون hardcode در کد).');
        }

        case 'seed_posting_rules': {
            $n = fin360_create_demo_posting_rules($conn, $actor);
            fin360_redirect($tab, 'ok', $n > 0 ? "تعداد $n قانون Posting ایجاد شد." : 'قوانین Posting از قبل موجود بودند.');
        }

        case 'post_document': {
            $docId = (int)fin360_f('document_id');
            $res = fin360_post_document($conn, $docId, $actor);
            fin360_redirect($tab, $res['ok'] ? 'ok' : 'err', $res['message']);
        }

        case 'approve_document': {
            $docId = (int)fin360_f('document_id');
            $doc = fin360_one($conn, 'SELECT * FROM dbo.fin360_documents WHERE document_id=?', [$docId]);
            if (!$doc) {
                fin360_redirect($tab, 'err', 'سند یافت نشد.');
            }
            if (strtoupper((string)$doc['document_status']) === 'POSTED') {
                fin360_redirect($tab, 'err', 'سند Posted قابل ویرایش مستقیم نیست.');
            }
            $creator = (string)$doc['created_by'];
            if ($creator === $actor && $actor !== 'local_owner') {
                fin360_redirect($tab, 'err', 'Maker-Checker: ایجادکننده نمی‌تواند همان سند را تأیید کند (مگر حالت توسعه محلی).');
            }
            fin360_exec($conn, "UPDATE dbo.fin360_documents SET document_status='APPROVED', approved_by=?, updated_at=SYSUTCDATETIME() WHERE document_id=?", [$actor, $docId]);
            fin360_audit($conn, 'APPROVE', 'fin360_documents', (string)$docId, $doc, ['status' => 'APPROVED'], null, $actor);
            fin360_redirect($tab, 'ok', 'سند تأیید شد.');
        }

        case 'submit_document': {
            $docId = (int)fin360_f('document_id');
            $doc = fin360_one($conn, 'SELECT * FROM dbo.fin360_documents WHERE document_id=?', [$docId]);
            if (!$doc) {
                fin360_redirect($tab, 'err', 'سند یافت نشد.');
            }
            if (strtoupper((string)$doc['document_status']) === 'POSTED') {
                fin360_redirect($tab, 'err', 'سند Posted قابل ویرایش مستقیم نیست.');
            }
            fin360_exec($conn, "UPDATE dbo.fin360_documents SET document_status='SUBMITTED', updated_at=SYSUTCDATETIME() WHERE document_id=?", [$docId]);
            fin360_audit($conn, 'SUBMIT', 'fin360_documents', (string)$docId, $doc, ['status' => 'SUBMITTED']);
            fin360_redirect($tab, 'ok', 'سند Submit شد.');
        }

        case 'reverse_document': {
            $docId = (int)fin360_f('document_id');
            $doc = fin360_one($conn, 'SELECT * FROM dbo.fin360_documents WHERE document_id=?', [$docId]);
            if (!$doc) {
                fin360_redirect($tab, 'err', 'سند یافت نشد.');
            }
            if (strtoupper((string)$doc['document_status']) !== 'POSTED') {
                fin360_redirect($tab, 'err', 'فقط اسناد Posted نیاز به Reverse دارند.');
            }
            fin360_exec($conn, "UPDATE dbo.fin360_documents SET document_status='REVERSED', updated_at=SYSUTCDATETIME() WHERE document_id=?", [$docId]);
            fin360_exec($conn, "UPDATE dbo.fin360_journal_headers SET journal_status='REVERSED' WHERE document_id=?", [$docId]);
            fin360_audit($conn, 'REVERSE', 'fin360_documents', (string)$docId, $doc, ['status' => 'REVERSED'], fin360_f('reason') ?: 'reversal');
            fin360_redirect($tab, 'ok', 'مسیر Reverse ثبت شد (وضعیت REVERSED).');
        }

        case 'posting_test': {
            $doc = fin360_one($conn, "SELECT TOP 1 document_id FROM dbo.fin360_documents WHERE document_status='APPROVED' ORDER BY document_id DESC");
            if (!$doc) {
                fin360_redirect($tab, 'err', 'سند APPROVED برای آزمایش یافت نشد.');
            }
            $res = fin360_post_document($conn, (int)$doc['document_id'], $actor);
            fin360_redirect($tab, $res['ok'] ? 'ok' : 'err', $res['message']);
        }

        default:
            fin360_redirect($tab, 'err', 'اقدام نامعتبر.');
    }
} catch (Throwable $e) {
    fin360_audit($conn, 'ERROR', 'system', null, null, ['action' => $action], 'internal');
    fin360_redirect($tab, 'err', 'خطای عملیاتی. جزئیات فنی نمایش داده نمی‌شود.');
}
