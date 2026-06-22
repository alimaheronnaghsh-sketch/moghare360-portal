<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 Customer Score Board (read-only)
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

$connection = false;
$errorMessage = '';
$cards = [];
$summary = ['STANDARD' => 0, 'LOYAL' => 0, 'VIP' => 0, 'AT_RISK' => 0, 'COMPLAINT_PRIORITY' => 0];
$filterVip = crm_get_string('vip_status');
$flash = crm_flash(crm_get_string('ok'));

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    crm_require_auth($connection, 'crm.score.view');

    if (crm_table_exists($connection, 'erp_customer_score_cards')) {
        foreach (array_keys($summary) as $st) {
            $summary[$st] = (int)(crm_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_customer_score_cards sc INNER JOIN (SELECT customer_id, MAX(customer_score_id) AS mid FROM dbo.erp_customer_score_cards GROUP BY customer_id) x ON sc.customer_score_id=x.mid WHERE sc.vip_status=?',
                [$st]
            ) ?? '0');
        }

        $sql = 'SELECT TOP 100 customer_score_id, customer_id, mobile, score_code, total_score, satisfaction_score, revenue_score, loyalty_score, complaint_penalty, vip_status, calculated_at FROM dbo.erp_customer_score_cards';
        $params = [];
        if ($filterVip !== '') {
            $sql .= ' WHERE vip_status = ?';
            $params[] = $filterVip;
        }
        $sql .= ' ORDER BY customer_score_id DESC';
        $cards = crm_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'تابلو امتیاز مشتری قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_render_head('تابلو امتیاز مشتری', true);

echo '<div class="p6crm-hero"><h1>تابلو امتیاز مشتری</h1><p>امتیازدهی و تشخیص VIP — foundation preview</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . crm_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    crm_error('تابلو امتیاز', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">خلاصه VIP</h2><div class="p6crm-kpi-grid">';
foreach (['STANDARD' => 'استاندارد', 'LOYAL' => 'وفادار', 'VIP' => 'VIP', 'AT_RISK' => 'در معرض ریزش', 'COMPLAINT_PRIORITY' => 'اولویت شکایت'] as $k => $l) {
    echo '<div class="p6crm-kpi"><div class="label">' . $l . '</div><div class="value m360-num">' . crm_h((string)$summary[$k]) . '</div></div>';
}
echo '</div></div>';

echo '<div class="p1cc-card"><form method="get" style="margin-bottom:1rem">';
echo '<label class="p1cc-label">فیلتر وضعیت VIP</label> <select class="p1cc-select" name="vip_status" onchange="this.form.submit()">';
echo '<option value="">همه</option>';
foreach (array_keys($summary) as $st) {
    echo '<option value="' . $st . '"' . ($filterVip === $st ? ' selected' : '') . '>' . crm_h(crm_vip_label($st)) . '</option>';
}
echo '</select></form>';

if ($cards === []) {
    echo '<p class="p1cc-hint">امتیازی ثبت نشده است. از <a href="erp-customer-satisfaction.php">فرم رضایت‌سنجی</a> استفاده کنید.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>مشتری</th><th>موبایل</th><th>کل</th><th>رضایت</th><th>درآمد</th><th>وفاداری</th><th>جریمه</th><th>VIP</th><th>تاریخ</th></tr></thead><tbody>';
    foreach ($cards as $c) {
        echo '<tr>';
        echo '<td class="m360-ltr">' . crm_h($c['score_code'] ?? '') . '</td>';
        echo '<td>' . crm_h($c['customer_id'] !== '' ? $c['customer_id'] : '—') . '</td>';
        echo '<td class="m360-ltr">' . crm_h($c['mobile'] ?? '—') . '</td>';
        echo '<td class="m360-ltr">' . crm_h(number_format((float)($c['total_score'] ?? 0), 1)) . '</td>';
        echo '<td class="m360-ltr">' . crm_h($c['satisfaction_score'] ?? '0') . '</td>';
        echo '<td class="m360-ltr">' . crm_h($c['revenue_score'] ?? '0') . '</td>';
        echo '<td class="m360-ltr">' . crm_h($c['loyalty_score'] ?? '0') . '</td>';
        echo '<td class="m360-ltr">' . crm_h($c['complaint_penalty'] ?? '0') . '</td>';
        echo '<td><span class="p1cc-badge ' . crm_badge_class($c['vip_status'] ?? '') . '">' . crm_h(crm_vip_label($c['vip_status'] ?? 'STANDARD')) . '</span></td>';
        echo '<td>' . crm_h($c['calculated_at'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

crm_render_foot();
