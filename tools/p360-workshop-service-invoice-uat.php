<?php
declare(strict_types=1);

/**
 * Phase 2 invoice conversion UAT — rollback. CLI only.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/public_html/includes/erp-customer-core-helper.php';
require_once $root . '/public_html/includes/m360-workshop-service-line-helper.php';
require_once $root . '/public_html/includes/m360-final-invoice-helper.php';

$conn = customer_core_db();
if ($conn === false) {
    fwrite(STDERR, "NO_DB\n");
    exit(1);
}

$pass = 0;
$fail = 0;
$log = [];
function t(string $n, bool $ok, string $d = ''): void
{
    global $pass, $fail, $log;
    if ($ok) {
        $pass++;
        echo "PASS $n $d\n";
        $log[] = "PASS $n $d";
    } else {
        $fail++;
        echo "FAIL $n $d\n";
        $log[] = "FAIL $n $d";
    }
}

t('irr_to_dec', m360_fi_irr_bigint_to_decimal_string(300000000) === '300000000.00');
t('irr_no_float', !str_contains(m360_fi_irr_bigint_to_decimal_string(700000000), 'e'));

@odbc_autocommit($conn, false);
try {
    $jcRows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 jobcard_id, company_id FROM dbo.erp_jobcards WHERE company_id > 0 ORDER BY jobcard_id DESC"
    );
    $jc = (int)$jcRows[0]['jobcard_id'];
    $cid = (int)$jcRows[0]['company_id'];
    $actors = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 2 u.user_id FROM dbo.core_users u
         INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.company_id=? AND cu.is_active=1
         WHERE u.lifecycle_state=N'ACTIVE' ORDER BY u.user_id",
        [$cid]
    );
    $a = (int)$actors[0]['user_id'];
    $b = (int)($actors[1]['user_id'] ?? $a);

    $wi = m360_ws_wi_ensure($conn, $cid, $jc, 'MECHANICAL', 'MECHANICAL', '', $a, 'INV UAT');
    $wid = (int)$wi['work_item_id'];
    customer_core_execute($conn, 'UPDATE dbo.erp_workshop_work_items SET assigned_technician_user_id=? WHERE work_item_id=?', [$a, $wid]);

    $c = m360_ws_sl_create_or_update_draft($conn, $cid, $jc, $wid, [
        'sales_category' => 'ENGINE', 'service_title' => 'تعمیر موتور', 'actual_minutes' => 60,
        'agreement_scope' => 'WITHIN_AGREEMENT',
    ], $a, true);
    $lineId = (int)$c['service_line_id'];
    m360_ws_sl_submit($conn, $lineId, $a, true);
    m360_ws_sl_technical_approve($conn, $lineId, $b);
    m360_ws_sl_set_price($conn, $lineId, '500000000', $b, '');
    m360_ws_sl_mark_ready_for_invoice($conn, $lineId, $b);
    $line = m360_ws_sl_fetch($conn, $lineId);
    t('ready_line', $line !== null && strtoupper((string)$line['status']) === 'READY_FOR_INVOICE');

    // Synthetic invoice header
    $invNo = 'UAT-WS-' . $jc . '-' . gmdate('His');
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_final_invoices
            (jobcard_id, invoice_no, invoice_status, subtotal_amount, discount_amount, tax_amount, total_amount, created_by_user_id)
         VALUES (?, ?, N'DRAFT', 0, 0, 0, 0, ?)",
        [$jc, $invNo, $b]
    );
    $invoiceId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 final_invoice_id FROM dbo.erp_final_invoices WHERE invoice_no=? ORDER BY final_invoice_id DESC',
        [$invNo]
    ) ?? 0);
    t('invoice_draft', $invoiceId > 0);

    $conv1 = m360_fi_convert_workshop_service_line($conn, $invoiceId, $jc, $line, $b);
    t('convert1', !empty($conv1['ok']) && empty($conv1['already']), (string)($conv1['message'] ?? ''));
    $lineAfter = m360_ws_sl_fetch($conn, $lineId);
    t('status_invoiced', $lineAfter !== null && strtoupper((string)$lineAfter['status']) === 'INVOICED');

    $conv2 = m360_fi_convert_workshop_service_line($conn, $invoiceId, $jc, $lineAfter, $b);
    t('convert_idempotent', !empty($conv2['ok']) && !empty($conv2['already']));

    $cnt = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_final_invoice_items WHERE source_type=N'WORKSHOP_SERVICE_LINE' AND source_id=?",
        [$lineId]
    ) ?? 0);
    t('no_duplicate_items', $cnt === 1);

    $item = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 unit_price, line_total, item_title FROM dbo.erp_final_invoice_items
         WHERE source_type=N'WORKSHOP_SERVICE_LINE' AND source_id=?",
        [$lineId]
    );
    t('amount_match', $item !== [] && (int)(float)$item[0]['unit_price'] === 500000000);
    t('title_display', $item !== [] && str_contains((string)$item[0]['item_title'], 'خدمات موتور'));

    $repr = m360_ws_sl_set_price($conn, $lineId, '600000000', $b, 'نباید');
    t('reprice_invoiced_blocked', empty($repr['ok']));

    // Internal consumable exclusion smoke: ensure no IC source type introduced.
    $icSrc = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_final_invoice_items WHERE final_invoice_id=? AND source_type LIKE N'%CONSUMABLE%'",
        [$invoiceId]
    ) ?? 0);
    t('no_consumable_source', $icSrc === 0);

    @odbc_rollback($conn);
    echo "TRANSACTION_ROLLED_BACK\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    echo 'EXCEPTION ' . $e->getMessage() . "\n";
    $fail++;
}

$out = $root . '/tools/_generated/workshop-service-invoice-uat-' . date('Ymd-His') . '.txt';
file_put_contents($out, implode(PHP_EOL, $log) . "\nSUMMARY pass=$pass fail=$fail\n");
echo "SUMMARY pass=$pass fail=$fail\nWROTE=$out\n";
exit($fail > 0 ? 1 : 0);
