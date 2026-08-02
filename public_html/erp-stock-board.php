<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Stock Board (read-only)
 * G0.2R8 — theme / return / Persian labels only. Queries unchanged.
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$rows = [];
$filterName = inventory_get_string('item_name');

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.stock.view');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $sql = "SELECT i.inventory_item_id, i.item_code, i.item_name, i.min_stock_qty,
                       ISNULL(SUM(CASE
                           WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                           WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                           ELSE ABS(m.movement_qty)
                       END),0) AS available_qty,
                       CAST(0 AS DECIMAL(18,2)) AS reserved_qty,
                       ISNULL(SUM(CASE WHEN m.movement_type = N'PENDING_RECEIVE' THEN ABS(m.movement_qty) ELSE 0 END),0) AS pending_receive_qty
                FROM dbo.erp_inventory_items i
                LEFT JOIN dbo.erp_inventory_stock_movements m
                  ON m.inventory_item_id = i.inventory_item_id AND m.movement_status = N'RECORDED'
                WHERE i.is_active = 1";
        $params = [];
        if ($filterName !== '') {
            $sql .= ' AND i.item_name LIKE ?';
            $params[] = '%' . $filterName . '%';
        }
        $sql .= ' GROUP BY i.inventory_item_id, i.item_code, i.item_name, i.min_stock_qty ORDER BY i.item_name';
        $rows = inventory_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'تابلو موجودی انبار قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

$badgeFa = static function (string $badge): string {
    return match ($badge) {
        'AVAILABLE' => 'موجود',
        'LOW_STOCK' => 'کم‌موجودی',
        'OUT_OF_STOCK' => 'ناموجود',
        'PENDING_RECEIVE' => 'در انتظار دریافت',
        default => $badge,
    };
};

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وضعیت موجودی انبار | مقاره ۳۶۰</title>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-inv-shell { max-width:1120px; margin:0 auto; overflow-x:hidden; box-sizing:border-box; }
        .m360-inv-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin:0 0 .85rem; }
        .m360-inv-top h1 { margin:0; font-size:1.35rem; color:#f3f4f6; }
        .m360-inv-shell .m360-rc-btn { min-height:32px; padding:.22rem .7rem; font-size:.78rem; font-weight:600; border-radius:10px; box-shadow:none; width:auto; }
        .m360-inv-card {
            margin:0 0 .85rem; padding:.85rem; border-radius:12px;
            border:1px solid rgba(34,197,94,.2);
            background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72));
            color:#e5e7eb;
        }
        .m360-inv-form { display:flex; flex-wrap:wrap; gap:.55rem; align-items:end; }
        .m360-inv-form label { display:block; font-size:.78rem; color:#9ca3af; margin-bottom:.25rem; }
        .m360-inv-form input {
            min-height:34px; padding:.35rem .55rem; border-radius:10px;
            border:1px solid rgba(34,197,94,.28); background:rgba(15,23,42,.75); color:#f3f4f6; min-width:12rem;
        }
        .m360-inv-nav { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.75rem; }
        .m360-inv-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
        .m360-inv-table { width:100%; border-collapse:collapse; font-size:.8rem; }
        .m360-inv-table th, .m360-inv-table td { padding:.4rem .35rem; border-bottom:1px solid rgba(34,197,94,.12); text-align:right; white-space:nowrap; }
        .m360-inv-table th { color:#9ca3af; }
        .m360-inv-badge { display:inline-block; padding:.12rem .45rem; border-radius:999px; font-size:.74rem; background:rgba(148,163,184,.2); }
        .m360-inv-hint { color:#9ca3af; }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-inv-shell">
    <div class="m360-inv-top">
        <div>
            <h1>وضعیت موجودی انبار</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">بررسی موجودی، رزرو و در انتظار دریافت</p>
        </div>
        <a class="m360-rc-btn secondary" href="inventory360/dashboard.php">بازگشت به انبار و خرید</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="m360-inv-card"><p><?= inventory_h($errorMessage) ?></p></div>
    <?php endif; ?>

    <div class="m360-inv-card">
        <form method="get" class="m360-inv-form">
            <div>
                <label for="item_name">فیلتر نام</label>
                <input id="item_name" name="item_name" value="<?= inventory_h($filterName) ?>">
            </div>
            <button class="m360-rc-btn" type="submit">اعمال فیلتر</button>
        </form>
    </div>

    <div class="m360-inv-card">
        <nav class="m360-inv-nav" aria-label="میان‌بر انبار">
            <a class="m360-rc-btn secondary" href="erp-part-reserve.php">رزرو قطعه</a>
            <a class="m360-rc-btn secondary" href="erp-purchase-request-create.php">ثبت درخواست خرید</a>
        </nav>
        <?php if ($rows === []): ?>
            <p class="m360-inv-hint">داده‌ای برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <div class="m360-inv-table-wrap">
            <table class="m360-inv-table">
                <thead>
                <tr>
                    <th>کد</th><th>نام</th><th>موجود</th><th>رزرو</th>
                    <th>در انتظار دریافت</th><th>قابل رزرو</th><th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $av = (float)($row['available_qty'] ?? '0');
                    $rs = (float)($row['reserved_qty'] ?? '0');
                    $pend = (float)($row['pending_receive_qty'] ?? '0');
                    $free = max(0, $av - $rs);
                    $badge = inventory_stock_badge((string)$av, (string)$rs, (string)$pend, $row['min_stock_qty'] ?? '0');
                ?>
                    <tr>
                        <td class="m360-ltr"><?= inventory_h($row['item_code'] ?? '') ?></td>
                        <td><?= inventory_h($row['item_name'] ?? '') ?></td>
                        <td class="m360-ltr"><?= inventory_h((string)$av) ?></td>
                        <td class="m360-ltr"><?= inventory_h((string)$rs) ?></td>
                        <td class="m360-ltr"><?= inventory_h((string)$pend) ?></td>
                        <td class="m360-ltr"><?= inventory_h((string)$free) ?></td>
                        <td><span class="m360-inv-badge"><?= inventory_h($badgeFa($badge)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
