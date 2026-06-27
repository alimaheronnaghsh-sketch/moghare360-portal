<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';

m360_qc_require_staff();

$filter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$rows = $conn !== false ? m360_qc_board_list($conn, $filter === 'ALL' ? null : $filter, 150) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برد QC و بازبینی نهایی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-qc.css">
</head>
<body class="m360-qc-page">
<div class="w1c-wrap m360-qc-wrap">
    <header class="w1c-banner">
        <h1>برد QC و بازبینی نهایی</h1>
        <p>کنترل کیفیت — Pass/Fail — Rework — آمادگی تحویل</p>
    </header>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <nav class="m360-qc-filters">
            <a href="?status=ALL" class="<?= $filter === 'ALL' ? 'active' : '' ?>">همه</a>
            <?php foreach (m360_qc_board_filters() as $code): ?>
                <a href="?status=<?= m360_qc_h($code) ?>" class="<?= $filter === $code ? 'active' : '' ?>"><?= m360_qc_h(m360_qc_status_label($code)) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="w1c-card">
            <?php if ($rows === []): ?>
                <p class="m360-qc-empty">پرونده‌ای برای QC نیست.</p>
            <?php else: ?>
                <table class="m360-qc-table">
                    <thead><tr>
                        <th>کارت کار</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                        <th>اجرای کار</th><th>QC</th><th>برآورد</th><th>آماده QC</th><th>تحویل</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= m360_qc_h((string)$r['jobcard_id']) ?></td>
                            <td><?= m360_qc_h((string)($r['customer_name'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($r['customer_mobile'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($r['vehicle_label'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($r['plate_number'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($r['work_status_label'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($r['qc_status_label'] ?? '-')) ?></td>
                            <td><?= m360_qc_h(m360_estimate_status_label((string)($r['estimate_status'] ?? ''))) ?></td>
                            <td><?= m360_qc_h((string)($r['ready_for_qc_at'] ?? '-') !== '' ? (string)$r['ready_for_qc_at'] : '-') ?></td>
                            <td><?= m360_qc_h((string)($r['delivery_readiness_status'] ?? '-')) ?></td>
                            <td><a class="m360-qc-btn" href="erp-qc-detail.php?jobcard_id=<?= (int)$r['jobcard_id'] ?>">جزئیات</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
