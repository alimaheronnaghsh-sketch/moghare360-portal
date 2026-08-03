<?php
declare(strict_types=1);

/**
 * Estimate board — G0.2R8 theme / return / Persian display only.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_estimate_require_staff();

$filter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$rows = $conn !== false ? m360_estimate_board_list($conn, $filter === 'ALL' ? null : $filter, 150) : [];

$gateLabelFa = static function (string $raw): string {
    $code = strtoupper(trim($raw));
    return match ($code) {
        'PENDING' => 'در انتظار',
        'CLEARED' => 'تأیید شده',
        'NOT_REQUIRED' => 'لازم نیست',
        '', '-' => '—',
        default => $raw !== '' ? $raw : '—',
    };
};
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برآوردها و تأیید هزینه | مقاره ۳۶۰</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <?php m360_operational_shell_render_stylesheets(); ?>
    <style>
        .m360-est-page.m360-rc-page { background: transparent; padding: 0; color: #e5e7eb; }
        .m360-est-wrap { max-width: 1120px; margin: 0 auto; overflow-x: hidden; box-sizing: border-box; }
        .m360-est-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin:0 0 .85rem; }
        .m360-est-top h1 { margin:0; font-size:1.35rem; color:#f3f4f6; }
        .m360-est-wrap .m360-rc-btn { min-height:32px; padding:.22rem .7rem; font-size:.78rem; font-weight:600; border-radius:10px; box-shadow:none; width:auto; }
        .m360-est-filters { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.85rem; }
        .m360-est-filters a { padding:.32rem .7rem; border-radius:999px; border:1px solid rgba(34,197,94,.28); text-decoration:none; color:#e5e7eb; font-size:.8rem; background:rgba(15,23,42,.55); }
        .m360-est-filters a.active { background:#0f766e; color:#fff; border-color:#14b8a6; }
        .m360-est-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
        .m360-est-table { width:100%; border-collapse:collapse; font-size:.8rem; color:#e5e7eb; }
        .m360-est-table th, .m360-est-table td { padding:.4rem .35rem; border-bottom:1px solid rgba(34,197,94,.12); text-align:right; white-space:nowrap; }
        .m360-est-table th { background:rgba(0,0,0,.18); color:#9ca3af; font-weight:600; }
        .m360-est-empty { text-align:center; padding:1.5rem; color:#9ca3af; }
        .m360-est-wrap .w1c-card { background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72)); border:1px solid rgba(34,197,94,.2); color:#e5e7eb; }
    </style>
</head>
<body class="m360-rc-page m360-est-page">
<div class="w1c-wrap m360-rc-wrap m360-est-wrap">
    <?php m360_operational_shell_render_board('estimate_board'); ?>
    <div class="m360-est-top">
        <div>
            <h1>برآوردها و تأیید هزینه</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">برآورد هزینه، تأیید مشتری و گیت قطعه و مالی</p>
        </div>
        <a class="m360-rc-btn secondary" href="erp-operations-home.php">بازگشت به عملیات تعمیرگاه</a>
    </div>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <nav class="m360-est-filters" aria-label="فیلتر وضعیت برآورد">
            <?php foreach (array_merge(['ALL' => 'همه'], array_combine(m360_estimate_board_filters(), array_map('m360_estimate_status_label', m360_estimate_board_filters()))) as $code => $label): ?>
                <a href="?status=<?= m360_estimate_h($code) ?>" class="<?= $filter === $code ? 'active' : '' ?>"><?= m360_estimate_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="w1c-card">
            <?php if ($rows === []): ?>
                <p class="m360-est-empty">پرونده‌ای برای نمایش نیست.</p>
            <?php else: ?>
                <div class="m360-est-table-wrap">
                <table class="m360-est-table">
                    <thead><tr>
                        <th>پرونده کار</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                        <th>فنی</th><th>برآورد</th><th>مبلغ</th><th>علی‌الحساب</th><th>قطعه</th><th>مالی</th><th>اقدام</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= m360_estimate_h((string)$r['jobcard_id']) ?></td>
                            <td><?= m360_estimate_h((string)($r['customer_name'] ?? '-')) ?></td>
                            <td><?= m360_estimate_h((string)($r['customer_mobile'] ?? '-')) ?></td>
                            <td><?= m360_estimate_h((string)($r['vehicle_label'] ?? '-')) ?></td>
                            <td><?= m360_estimate_h((string)($r['plate_number'] ?? '-')) ?></td>
                            <td><?= m360_estimate_h(m360_technician_workflow_status_label((string)($r['technical_status'] ?? ''))) ?></td>
                            <td><?= m360_estimate_h((string)($r['estimate_status_label'] ?? 'منتظر برآورد')) ?></td>
                            <td><?= m360_estimate_h(number_format((float)($r['total_amount'] ?? 0))) ?></td>
                            <td><?= m360_estimate_h(number_format((float)($r['advance_required_amount'] ?? 0))) ?></td>
                            <td><?= m360_estimate_h($gateLabelFa((string)($r['parts_gate_status'] ?? '-'))) ?></td>
                            <td><?= m360_estimate_h($gateLabelFa((string)($r['finance_gate_status'] ?? '-'))) ?></td>
                            <td><a class="m360-rc-btn" href="erp-estimate-detail.php?jobcard_id=<?= (int)$r['jobcard_id'] ?>">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>

