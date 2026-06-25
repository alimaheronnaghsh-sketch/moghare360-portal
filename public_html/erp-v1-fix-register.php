<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-v1-post-run-control-helper.php';

$connection = false;
$items = [];
$summary = [];
$errorMessage = '';

try {
    $connection = v1ctrl_db();
    if ($connection !== false) {
        $items = v1ctrl_fetch_fix_items($connection);
        $summary = v1ctrl_fix_summary($items);
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

v1ctrl_render_head('MOGHARE360 V1 — Post-Run Fix Register');
echo '<div class="v1sig-banner">Post-Run Fix / Development Control — جلوگیری از چرخه ساخت بی‌پایان</div>';
echo '<div class="v1sig-hero"><h1>Fix / Development Register</h1>';
echo '<p>ثبت اصلاحات بعد از Production Run واقعی — تفکیک Bug / Fix / Improvement / V2 Backlog</p></div>';

if ($errorMessage !== '') {
    echo '<div class="v1sig-card" style="border-color:#fca5a5">' . v1ctrl_h($errorMessage) . '</div>';
}

echo '<div class="v1sig-kpi">';
foreach (['OPEN' => 'باز', 'IN_REVIEW' => 'در بررسی', 'FIXED' => 'رفع‌شده', 'DEFERRED_TO_V2' => 'معوق V2', 'CLOSED' => 'بسته'] as $k => $label) {
    echo '<div><div class="n m360-num">' . (int)($summary[$k] ?? 0) . '</div><div>' . v1ctrl_h($label) . '</div></div>';
}
echo '</div>';

echo '<div class="v1sig-card"><h2>خلاصه دسته‌بندی</h2><p>';
$cats = ['BUG', 'FIX', 'UI', 'TRAINING', 'DATA', 'SECURITY', 'V2_BACKLOG'];
$parts = [];
foreach ($cats as $c) {
    $parts[] = $c . ': ' . (int)($summary[$c] ?? 0);
}
echo v1ctrl_h(implode(' · ', $parts));
echo '</p></div>';

echo '<div class="v1sig-card"><h2>ثبت اصلاحات (Fix Register)</h2>';
if ($items === []) {
    echo '<p>جدول خالی است یا migration اجرا نشده — <code>v1_post_run_fix_register.sql</code></p>';
} else {
    echo '<table class="v1sig-table"><thead><tr>';
    echo '<th>item_id</th><th>category</th><th>severity</th><th>source</th>';
    echo '<th>description</th><th>affected_module</th><th>owner_decision</th>';
    echo '<th>status</th><th>created_at</th><th>closed_at</th>';
    echo '</tr></thead><tbody>';
    foreach ($items as $row) {
        $st = (string)($row['status'] ?? '');
        echo '<tr>';
        echo '<td class="m360-num">' . v1ctrl_h((string)($row['item_id'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['category'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['severity'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['source'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['description'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['affected_module'] ?? '')) . '</td>';
        echo '<td>' . v1ctrl_h((string)($row['owner_decision'] ?? '')) . '</td>';
        echo '<td><span class="v1sig-badge ' . v1ctrl_status_badge($st) . '">' . v1ctrl_h($st) . '</span></td>';
        echo '<td class="m360-ltr">' . v1ctrl_h((string)($row['created_at'] ?? '')) . '</td>';
        echo '<td class="m360-ltr">' . v1ctrl_h((string)($row['closed_at'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '<div class="v1sig-card"><h2>قوانین کنترل</h2><ul>';
echo '<li>هر مورد جدید فقط از Production Run / بازخورد کاربر / بررسی مالک یا پرسنل ثبت شود</li>';
echo '<li>V2_BACKLOG = خارج از scope فعلی V1 — بدون شروع ساخت دوباره</li>';
echo '<li>CRITICAL/HIGH قبل از ران روزانه واقعی باید FIXED یا DEFERRED با تصمیم مالک باشد</li>';
echo '</ul><p><a href="erp-v1-production-signoff.php">بازگشت به Production Signoff</a></p></div>';

v1ctrl_render_foot();
