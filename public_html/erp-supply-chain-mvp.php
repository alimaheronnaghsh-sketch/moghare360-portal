<?php
declare(strict_types=1);

/**
 * MOGHARE360 Prompt 3 — Supply Chain MVP board (PO / logistics / tools).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-supply-chain-mvp-helper.php';

m360_sc_require_staff();
$conn = customer_core_db();
$userId = m360_sc_actor_user_id();
$message = '';
$okFlash = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        erp_csrf_require_valid('supply_chain_mvp', $_POST['erp_csrf_token'] ?? null);
    } catch (Throwable $e) {
        $message = 'اعتبار امنیتی فرم منقضی شده است.';
    }
    $action = trim((string)($_POST['action'] ?? ''));
    if ($message === '') {
        if ($action === 'create_po') {
            $res = m360_sc_create_purchase_order($conn, (int)($_POST['supplier_id'] ?? 0), trim((string)($_POST['notes'] ?? '')), $userId);
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
            if ($okFlash && !empty($res['purchase_order_id'])) {
                m360_sc_add_po_line(
                    $conn,
                    (int)$res['purchase_order_id'],
                    trim((string)($_POST['item_name'] ?? 'قلم سفارش')),
                    (float)($_POST['ordered_qty'] ?? 1),
                    (int)($_POST['part_id'] ?? 0),
                    null,
                    $userId
                );
            }
        } elseif ($action === 'receive_po_line') {
            $res = m360_sc_receive_po_line($conn, (int)($_POST['po_line_id'] ?? 0), (float)($_POST['recv_qty'] ?? 0), $userId);
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } elseif ($action === 'create_logistics') {
            $res = m360_sc_create_logistics_request(
                $conn,
                trim((string)($_POST['origin_text'] ?? '')),
                trim((string)($_POST['destination_text'] ?? '')),
                (int)($_POST['jobcard_id'] ?? 0),
                $userId
            );
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } elseif ($action === 'create_tool') {
            $res = m360_sc_create_tool(
                $conn,
                trim((string)($_POST['tool_code'] ?? '')),
                trim((string)($_POST['tool_name'] ?? '')),
                trim((string)($_POST['tool_type'] ?? 'TOOL')),
                $userId
            );
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } elseif ($action === 'issue_tool') {
            $res = m360_sc_issue_tool($conn, (int)($_POST['tool_asset_id'] ?? 0), (int)($_POST['issued_to_user_id'] ?? $userId), $userId);
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } elseif ($action === 'return_tool') {
            $res = m360_sc_return_tool($conn, (int)($_POST['tool_asset_id'] ?? 0), trim((string)($_POST['condition_status'] ?? 'GOOD')), $userId);
            $okFlash = !empty($res['ok']);
            $message = (string)($res['message'] ?? '');
        } else {
            $message = 'عملیات نامعتبر است.';
        }
    }
}

$pos = m360_sc_list_purchase_orders($conn);
$logs = m360_sc_list_logistics($conn);
$tools = m360_sc_list_tools($conn);
$poLines = [];
if ($pos !== [] && customer_core_table_exists($conn, M360_SC_PO_LINES_TABLE)) {
    $poLines = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 30 * FROM dbo.' . M360_SC_PO_LINES_TABLE . ' ORDER BY po_line_id DESC',
        []
    );
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>زنجیره تأمین — MVP</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-page">
<main class="m360-shell" style="max-width:1100px;margin:24px auto;padding:0 16px;">
    <header style="margin-bottom:20px;">
        <h1>زنجیره تأمین و لجستیک (MVP)</h1>
        <p>سفارش خرید، لجستیک و ابزار/دارایی — روی بنیاد انبار موجود.</p>
        <p><a href="staff-inventory.php">بازگشت به انبار</a> · <a href="erp-product-home.php">خانه محصول</a></p>
    </header>

    <?php if ($message !== ''): ?>
        <div class="notice <?= $okFlash ? 'ok' : 'error' ?>"><?= m360_sc_h($message) ?></div>
    <?php endif; ?>

    <section style="margin:24px 0;">
        <h2>سفارش خرید</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="create_po">
            <label>شناسه تأمین‌کننده<input name="supplier_id" type="number" min="0" value="1"></label>
            <label>نام قلم<input name="item_name" required maxlength="200"></label>
            <label>تعداد سفارش<input name="ordered_qty" type="number" step="0.001" min="0.001" value="1" required></label>
            <label>شناسه قطعه (اختیاری)<input name="part_id" type="number" min="0" value="0"></label>
            <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
            <button type="submit">ایجاد سفارش خرید</button>
        </form>
        <div class="table-scroll" style="margin-top:12px;">
            <table class="data-table">
                <thead><tr><th>شناسه</th><th>شماره</th><th>وضعیت</th><th>تأمین‌کننده</th></tr></thead>
                <tbody>
                <?php foreach ($pos as $row): ?>
                    <tr>
                        <td><?= m360_sc_h((string)$row['purchase_order_id']) ?></td>
                        <td><?= m360_sc_h((string)$row['po_number']) ?></td>
                        <td><?= m360_sc_h((string)$row['po_status']) ?></td>
                        <td><?= m360_sc_h((string)($row['supplier_id'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($pos === []): ?><tr><td colspan="4">سفارشی ثبت نشده است.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <h3>دریافت جزئی قلم</h3>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="receive_po_line">
            <label>شناسه قلم<input name="po_line_id" type="number" min="1" required></label>
            <label>مقدار دریافت<input name="recv_qty" type="number" step="0.001" min="0.001" required></label>
            <button type="submit">ثبت دریافت</button>
        </form>
        <div class="table-scroll" style="margin-top:12px;">
            <table class="data-table">
                <thead><tr><th>قلم</th><th>سفارش</th><th>نام</th><th>سفارش‌شده</th><th>دریافت‌شده</th><th>مانده</th></tr></thead>
                <tbody>
                <?php foreach ($poLines as $line): ?>
                    <?php $open = (float)$line['ordered_qty'] - (float)$line['received_qty']; ?>
                    <tr>
                        <td><?= m360_sc_h((string)$line['po_line_id']) ?></td>
                        <td><?= m360_sc_h((string)$line['purchase_order_id']) ?></td>
                        <td><?= m360_sc_h((string)$line['item_name']) ?></td>
                        <td><?= m360_sc_h((string)$line['ordered_qty']) ?></td>
                        <td><?= m360_sc_h((string)$line['received_qty']) ?></td>
                        <td><?= m360_sc_h((string)$open) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section style="margin:24px 0;">
        <h2>لجستیک</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="create_logistics">
            <label>مبدأ<input name="origin_text" required maxlength="300"></label>
            <label>مقصد<input name="destination_text" required maxlength="300"></label>
            <label>کارت کار (اختیاری)<input name="jobcard_id" type="number" min="0" value="0"></label>
            <button type="submit">ایجاد درخواست لجستیک</button>
        </form>
        <div class="table-scroll" style="margin-top:12px;">
            <table class="data-table">
                <thead><tr><th>شناسه</th><th>کد</th><th>وضعیت</th><th>مبدأ</th><th>مقصد</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $row): ?>
                    <tr>
                        <td><?= m360_sc_h((string)$row['logistics_request_id']) ?></td>
                        <td><?= m360_sc_h((string)$row['request_code']) ?></td>
                        <td><?= m360_sc_h((string)$row['logistics_status']) ?></td>
                        <td><?= m360_sc_h((string)($row['origin_text'] ?? '')) ?></td>
                        <td><?= m360_sc_h((string)($row['destination_text'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section style="margin:24px 0;">
        <h2>ابزار و دارایی</h2>
        <form method="post" class="m360-form">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="create_tool">
            <label>کد ابزار<input name="tool_code" required maxlength="40"></label>
            <label>نام<input name="tool_name" required maxlength="200"></label>
            <label>نوع
                <select name="tool_type">
                    <option value="TOOL">ابزار</option>
                    <option value="ASSET">دارایی</option>
                    <option value="EQUIPMENT">تجهیزات</option>
                </select>
            </label>
            <button type="submit">ثبت ابزار</button>
        </form>
        <form method="post" class="m360-form" style="margin-top:12px;">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="issue_tool">
            <label>شناسه ابزار<input name="tool_asset_id" type="number" min="1" required></label>
            <label>کاربر گیرنده<input name="issued_to_user_id" type="number" min="1" value="<?= (int)$userId ?>" required></label>
            <button type="submit">صدور ابزار</button>
        </form>
        <form method="post" class="m360-form" style="margin-top:12px;">
            <?= erp_csrf_input('supply_chain_mvp') ?>
            <input type="hidden" name="action" value="return_tool">
            <label>شناسه ابزار<input name="tool_asset_id" type="number" min="1" required></label>
            <label>وضعیت برگشت
                <select name="condition_status">
                    <option value="GOOD">سالم</option>
                    <option value="NEEDS_SERVICE">نیازمند سرویس</option>
                    <option value="DAMAGED">آسیب‌دیده</option>
                    <option value="MISSING"> مفقود</option>
                </select>
            </label>
            <button type="submit">برگشت ابزار</button>
        </form>
        <div class="table-scroll" style="margin-top:12px;">
            <table class="data-table">
                <thead><tr><th>شناسه</th><th>کد</th><th>نام</th><th>وضعیت</th><th>کاربر</th></tr></thead>
                <tbody>
                <?php foreach ($tools as $row): ?>
                    <tr>
                        <td><?= m360_sc_h((string)$row['tool_asset_id']) ?></td>
                        <td><?= m360_sc_h((string)$row['tool_code']) ?></td>
                        <td><?= m360_sc_h((string)$row['tool_name']) ?></td>
                        <td><?= m360_sc_h((string)$row['condition_status']) ?></td>
                        <td><?= m360_sc_h((string)($row['assigned_user_id'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
