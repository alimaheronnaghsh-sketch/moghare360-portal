<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Part Reservation Form
 * G0.2R8 — theme / return / labels only. POST/CSRF/fields unchanged.
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$items = [];
$selectedItemId = inventory_get_int('inventory_item_id');
$availableToReserve = null;
$ruleDecisionId = inventory_get_int('rule_decision_id');
$operationCaseId = inventory_get_int('operation_case_id');
$flash = inventory_get_string('ok') !== '' ? inventory_flash('reserve_ok') : '';

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.reserve.create');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows(
            $connection,
            'SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name'
        );
    }

    if ($selectedItemId !== null) {
        $availableToReserve = inventory_calculate_available_to_reserve($connection, $selectedItemId);
    }
} catch (Throwable) {
    $errorMessage = 'صفحه رزرو قطعه قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رزرو قطعه برای پرونده کار | ماهین 360°</title>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-inv-shell { max-width:920px; margin:0 auto; overflow-x:hidden; box-sizing:border-box; }
        .m360-inv-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin:0 0 .85rem; }
        .m360-inv-top h1 { margin:0; font-size:1.3rem; color:#f3f4f6; }
        .m360-inv-shell .m360-rc-btn { min-height:34px; padding:.28rem .75rem; font-size:.8rem; font-weight:600; border-radius:10px; box-shadow:none; width:auto; }
        .m360-inv-card {
            margin:0 0 .85rem; padding:.9rem; border-radius:12px;
            border:1px solid rgba(34,197,94,.2);
            background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72));
            color:#e5e7eb;
        }
        .m360-inv-grid { display:grid; grid-template-columns:1fr 1fr; gap:.65rem .75rem; }
        .m360-inv-grid .full { grid-column:1 / -1; }
        .m360-inv-grid label { display:block; font-size:.78rem; color:#9ca3af; margin-bottom:.25rem; }
        .m360-inv-grid input, .m360-inv-grid select, .m360-inv-grid textarea {
            width:100%; box-sizing:border-box; min-height:36px; padding:.4rem .55rem; border-radius:10px;
            border:1px solid rgba(34,197,94,.28); background:rgba(15,23,42,.75); color:#f3f4f6; font:inherit;
        }
        .m360-inv-grid textarea { min-height:88px; }
        .m360-inv-actions { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.85rem; }
        .m360-inv-ok { color:#a7f3d0; }
        @media (max-width:760px) { .m360-inv-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-inv-shell">
    <div class="m360-inv-top">
        <div>
            <h1>رزرو قطعه برای پرونده کار</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">رزرو کنترل‌شده از موجودی انبار</p>
        </div>
        <a class="m360-rc-btn secondary" href="inventory360/dashboard.php">بازگشت به انبار و خرید</a>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="m360-inv-card m360-inv-ok"><p><?= inventory_h($flash) ?></p></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="m360-inv-card"><p><?= inventory_h($errorMessage) ?></p></div>
    <?php endif; ?>

    <?php if ($availableToReserve !== null): ?>
        <div class="m360-inv-card">
            <p>مقدار قابل رزرو برای قلم انتخاب‌شده: <strong class="m360-ltr"><?= inventory_h((string)$availableToReserve) ?></strong></p>
        </div>
    <?php endif; ?>

    <form class="m360-inv-card" method="post" action="submit-part-reserve.php">
        <?= erp_csrf_input('inventory_part_reserve') ?>
        <div class="m360-inv-grid">
            <div class="full">
                <label for="inventory_item_id">قلم انبار *</label>
                <select id="inventory_item_id" name="inventory_item_id" required onchange="location.href='erp-part-reserve.php?inventory_item_id='+this.value">
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($items as $item):
                        $id = (int)($item['inventory_item_id'] ?? 0);
                        $sel = $selectedItemId === $id ? ' selected' : '';
                    ?>
                        <option value="<?= $id ?>"<?= $sel ?>><?= inventory_h(($item['item_code'] ?? '') . ' — ' . ($item['item_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="requested_qty">تعداد درخواستی *</label>
                <input class="m360-ltr" type="number" step="0.01" min="0.01" id="requested_qty" name="requested_qty" value="1" required>
            </div>
            <div>
                <label for="operation_case_id">پرونده عملیات</label>
                <input class="m360-ltr" id="operation_case_id" name="operation_case_id" value="<?= inventory_h($operationCaseId !== null ? (string)$operationCaseId : '') ?>">
            </div>
            <div>
                <label for="service_step_id">مرحله سرویس</label>
                <input class="m360-ltr" id="service_step_id" name="service_step_id" value="">
            </div>
            <div>
                <label for="rule_decision_id">تصمیم قانون</label>
                <input class="m360-ltr" id="rule_decision_id" name="rule_decision_id" value="<?= inventory_h($ruleDecisionId !== null ? (string)$ruleDecisionId : '') ?>">
            </div>
            <div class="full">
                <label for="reservation_reason">دلیل رزرو</label>
                <textarea id="reservation_reason" name="reservation_reason" maxlength="1000"></textarea>
            </div>
        </div>
        <div class="m360-inv-actions">
            <button class="m360-rc-btn" type="submit">ثبت رزرو</button>
            <a class="m360-rc-btn secondary" href="inventory360/dashboard.php">انصراف</a>
        </div>
    </form>
</div>
</body>
</html>
