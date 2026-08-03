<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Purchase Request Create Form
 * G0.2R8 — theme / return / labels only. POST/CSRF/fields unchanged.
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$items = [];
$suppliers = [];
$ruleDecisionId = inventory_get_int('rule_decision_id');
$operationCaseId = inventory_get_int('operation_case_id');
$prefill = [
    'inventory_item_id' => inventory_get_int('inventory_item_id'),
    'requested_part_name' => '',
    'requested_part_code' => '',
    'requested_qty' => '1',
    'operation_case_id' => $operationCaseId,
    'service_step_id' => inventory_get_int('service_step_id'),
    'rule_decision_id' => $ruleDecisionId,
];

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.purchase.create');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows($connection, 'SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name');
    }
    if (inventory_table_exists($connection, 'erp_suppliers')) {
        $suppliers = inventory_fetch_rows($connection, 'SELECT supplier_id, supplier_code, supplier_name FROM dbo.erp_suppliers WHERE is_active = 1 ORDER BY supplier_name');
    }

    if ($ruleDecisionId !== null && inventory_table_exists($connection, 'erp_inventory_rule_requests')) {
        $ruleRows = inventory_fetch_rows(
            $connection,
            'SELECT TOP 1 operation_case_id, service_step_id, part_code, part_name, requested_qty
             FROM dbo.erp_inventory_rule_requests WHERE rule_decision_id = ? ORDER BY inventory_rule_request_id DESC',
            [$ruleDecisionId]
        );
        if ($ruleRows !== []) {
            $r = $ruleRows[0];
            $prefill['operation_case_id'] = $prefill['operation_case_id'] ?? (inventory_get_int('operation_case_id') ?? (ctype_digit((string)($r['operation_case_id'] ?? '')) ? (int)$r['operation_case_id'] : null));
            $prefill['service_step_id'] = $prefill['service_step_id'] ?? (ctype_digit((string)($r['service_step_id'] ?? '')) ? (int)$r['service_step_id'] : null);
            $prefill['requested_part_name'] = (string)($r['part_name'] ?? '');
            $prefill['requested_part_code'] = (string)($r['part_code'] ?? '');
            $prefill['requested_qty'] = (string)($r['requested_qty'] ?? '1');
        }
    }

    if ($prefill['inventory_item_id'] !== null) {
        $item = inventory_get_item($connection, $prefill['inventory_item_id']);
        if ($item !== null) {
            if ($prefill['requested_part_name'] === '') {
                $prefill['requested_part_name'] = (string)($item['item_name'] ?? '');
            }
            if ($prefill['requested_part_code'] === '') {
                $prefill['requested_part_code'] = (string)($item['item_code'] ?? '');
            }
        }
    }
} catch (Throwable) {
    $errorMessage = 'صفحه درخواست خرید قابل بارگذاری نیست.';
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
    <title>ثبت درخواست خرید | مقاره ۳۶۰</title>
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
        .m360-inv-hint { color:#9ca3af; font-size:.84rem; margin:0; }
        @media (max-width:760px) { .m360-inv-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-inv-shell">
    <div class="m360-inv-top">
        <div>
            <h1>ثبت درخواست خرید</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">ثبت کنترل‌شده نیاز به تأمین قطعه</p>
        </div>
        <a class="m360-rc-btn secondary" href="inventory360/dashboard.php">بازگشت به انبار و خرید</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="m360-inv-card"><p><?= inventory_h($errorMessage) ?></p></div>
    <?php endif; ?>

    <?php if ($ruleDecisionId !== null): ?>
        <div class="m360-inv-card"><p class="m360-inv-hint">پیش‌فرض از تصمیم قانون شماره <?= inventory_h((string)$ruleDecisionId) ?></p></div>
    <?php endif; ?>

    <form class="m360-inv-card" method="post" action="submit-purchase-request.php">
        <?= erp_csrf_input('inventory_purchase_create') ?>
        <div class="m360-inv-grid">
            <div>
                <label for="inventory_item_id">قلم انبار</label>
                <select id="inventory_item_id" name="inventory_item_id">
                    <option value="">—</option>
                    <?php foreach ($items as $item):
                        $id = (int)($item['inventory_item_id'] ?? 0);
                        $sel = $prefill['inventory_item_id'] === $id ? ' selected' : '';
                    ?>
                        <option value="<?= $id ?>"<?= $sel ?>><?= inventory_h(($item['item_code'] ?? '') . ' — ' . ($item['item_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="supplier_id">تأمین‌کننده</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">—</option>
                    <?php foreach ($suppliers as $sup):
                        $id = (int)($sup['supplier_id'] ?? 0);
                    ?>
                        <option value="<?= $id ?>"><?= inventory_h(($sup['supplier_code'] ?? '') . ' — ' . ($sup['supplier_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="requested_part_name">نام قطعه *</label>
                <input id="requested_part_name" name="requested_part_name" required maxlength="300" value="<?= inventory_h($prefill['requested_part_name']) ?>">
            </div>
            <div>
                <label for="requested_part_code">کد قطعه</label>
                <input class="m360-ltr" id="requested_part_code" name="requested_part_code" maxlength="100" value="<?= inventory_h($prefill['requested_part_code']) ?>">
            </div>
            <div>
                <label for="requested_qty">تعداد *</label>
                <input class="m360-ltr" type="number" step="0.01" min="0.01" id="requested_qty" name="requested_qty" required value="<?= inventory_h($prefill['requested_qty']) ?>">
            </div>
            <div>
                <label for="urgency_level">فوریت</label>
                <select id="urgency_level" name="urgency_level">
                    <?php foreach (['LOW' => 'کم', 'NORMAL' => 'عادی', 'HIGH' => 'بالا', 'URGENT' => 'فوری'] as $v => $l): ?>
                        <option value="<?= $v ?>"<?= $v === 'NORMAL' ? ' selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="purchase_source">منبع تأمین</label>
                <select id="purchase_source" name="purchase_source">
                    <?php foreach (['LOCAL' => 'محلی', 'IMPORT' => 'وارداتی', 'CUSTOMER_PROVIDED' => 'تأمین مشتری', 'UNKNOWN' => 'نامشخص'] as $v => $l): ?>
                        <option value="<?= $v ?>"<?= $v === 'LOCAL' ? ' selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="estimated_cost">هزینه تخمینی</label>
                <input class="m360-ltr" type="number" step="0.01" id="estimated_cost" name="estimated_cost">
            </div>
            <div>
                <label for="operation_case_id">پرونده عملیات</label>
                <input class="m360-ltr" id="operation_case_id" name="operation_case_id" value="<?= inventory_h($prefill['operation_case_id'] !== null ? (string)$prefill['operation_case_id'] : '') ?>">
            </div>
            <div>
                <label for="service_step_id">مرحله سرویس</label>
                <input class="m360-ltr" id="service_step_id" name="service_step_id" value="<?= inventory_h($prefill['service_step_id'] !== null ? (string)$prefill['service_step_id'] : '') ?>">
            </div>
            <div>
                <label for="rule_decision_id">تصمیم قانون</label>
                <input class="m360-ltr" id="rule_decision_id" name="rule_decision_id" value="<?= inventory_h($prefill['rule_decision_id'] !== null ? (string)$prefill['rule_decision_id'] : '') ?>">
            </div>
            <div class="full">
                <label for="internal_note">یادداشت داخلی</label>
                <textarea id="internal_note" name="internal_note" maxlength="1500"></textarea>
            </div>
        </div>
        <div class="m360-inv-actions">
            <button class="m360-rc-btn" type="submit">ثبت درخواست خرید</button>
            <a class="m360-rc-btn secondary" href="inventory360/dashboard.php">انصراف</a>
        </div>
    </form>
</div>
</body>
</html>
