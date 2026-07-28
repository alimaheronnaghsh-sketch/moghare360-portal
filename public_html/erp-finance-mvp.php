<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-finance-mvp-helper.php';

m360_fin_require_staff();
$conn = customer_core_db();
$userId = m360_fin_actor_user_id();
$message = '';
$okFlash = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        erp_csrf_require_valid('finance_mvp', $_POST['erp_csrf_token'] ?? null);
    } catch (Throwable $e) {
        $message = 'اعتبار امنیتی فرم منقضی شده است.';
    }
    $action = trim((string)($_POST['action'] ?? ''));
    if ($message === '') {
        if ($action === 'create_invoice') {
            $res = m360_fin_create_invoice_from_jobcard(
                $conn,
                (int)($_POST['jobcard_id'] ?? 0),
                (float)($_POST['service_amount'] ?? 0),
                (float)($_POST['parts_amount'] ?? 0),
                $userId
            );
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } elseif ($action === 'register_payment') {
            $res = m360_fin_register_payment(
                $conn,
                (int)($_POST['invoice_id'] ?? 0),
                (float)($_POST['amount'] ?? 0),
                trim((string)($_POST['payment_method'] ?? 'CASH')),
                $userId
            );
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '') . (isset($res['balance']) ? (' | مانده: ' . $res['balance']) : '');
        } elseif ($action === 'void_invoice') {
            $res = m360_fin_void_invoice($conn, (int)($_POST['invoice_id'] ?? 0), trim((string)($_POST['void_reason'] ?? '')), $userId);
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } else {
            $message = 'عملیات نامعتبر است.';
        }
    }
}

$invoices = m360_fin_list_invoices($conn);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مالی و تسویه — MVP</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-page">
<main class="m360-shell" style="max-width:1100px;margin:24px auto;padding:0 16px;">
    <header style="margin-bottom:20px;">
        <h1>مالی، پرداخت و تسویه (MVP)</h1>
        <p>صدور فاکتور از کارت کار، ثبت پرداخت، دفتر مشتری و گیت تحویل.</p>
        <p><a href="erp-final-invoice-board.php">هیئت فاکتور نهایی</a> · <a href="erp-product-home.php">خانه محصول</a></p>
    </header>

    <?php if ($message !== ''): ?>
        <div class="notice <?= $okFlash ? 'ok' : 'error' ?>"><?= m360_fin_h($message) ?></div>
    <?php endif; ?>

    <section style="margin:24px 0;">
        <h2>صدور فاکتور از کارت کار</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('finance_mvp') ?>
            <input type="hidden" name="action" value="create_invoice">
            <label>شناسه کارت کار<input name="jobcard_id" type="number" min="1" required></label>
            <label>مبلغ خدمات<input name="service_amount" type="number" step="0.01" min="0" value="0"></label>
            <label>مبلغ قطعات<input name="parts_amount" type="number" step="0.01" min="0" value="0"></label>
            <button type="submit">صدور فاکتور</button>
        </form>
    </section>

    <section style="margin:24px 0;">
        <h2>ثبت پرداخت</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('finance_mvp') ?>
            <input type="hidden" name="action" value="register_payment">
            <label>شناسه فاکتور<input name="invoice_id" type="number" min="1" required></label>
            <label>مبلغ<input name="amount" type="number" step="0.01" min="0.01" required></label>
            <label>روش
                <select name="payment_method">
                    <option value="CASH">نقد</option>
                    <option value="CARD">کارت</option>
                    <option value="TRANSFER">حواله</option>
                    <option value="CHEQUE">چک</option>
                    <option value="OTHER">سایر</option>
                </select>
            </label>
            <button type="submit">ثبت پرداخت</button>
        </form>
    </section>

    <section style="margin:24px 0;">
        <h2>ابطال فاکتور (بدون حذف فیزیکی)</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('finance_mvp') ?>
            <input type="hidden" name="action" value="void_invoice">
            <label>شناسه فاکتور<input name="invoice_id" type="number" min="1" required></label>
            <label>دلیل ابطال<textarea name="void_reason" required rows="2"></textarea></label>
            <button type="submit">ابطال</button>
        </form>
    </section>

    <section style="margin:24px 0;">
        <h2>فاکتورها</h2>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>شناسه</th><th>شماره</th><th>کارت کار</th><th>وضعیت</th><th>جمع</th><th>پرداخت‌شده</th><th>مانده</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $row): ?>
                    <tr>
                        <td><?= m360_fin_h((string)$row['final_invoice_id']) ?></td>
                        <td><?= m360_fin_h((string)$row['invoice_no']) ?></td>
                        <td><?= m360_fin_h((string)($row['jobcard_id'] ?? '')) ?></td>
                        <td><?= m360_fin_h((string)$row['invoice_status']) ?></td>
                        <td><?= m360_fin_h((string)$row['total_amount']) ?></td>
                        <td><?= m360_fin_h((string)($row['paid_amount'] ?? '0')) ?></td>
                        <td><?= m360_fin_h((string)($row['balance_amount'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
