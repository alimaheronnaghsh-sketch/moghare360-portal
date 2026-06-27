<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';

m360_work_require_staff();

$filter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$rows = $conn !== false ? m360_work_board_list($conn, $filter === 'ALL' ? null : $filter, 150) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برد اجرای کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-work-execution.css">
</head>
<body class="m360-wx-page">
<div class="w1c-wrap m360-wx-wrap">
    <header class="w1c-banner">
        <h1>برد اجرای کار</h1>
        <p>پس از تأیید برآورد — مصرف قطعه — تکمیل فنی — آماده QC</p>
    </header>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <nav class="m360-wx-filters">
            <a href="?status=ALL" class="<?= $filter === 'ALL' ? 'active' : '' ?>">همه</a>
            <?php foreach (m360_work_board_filters() as $code): ?>
                <a href="?status=<?= m360_work_h($code) ?>" class="<?= $filter === $code ? 'active' : '' ?>"><?= m360_work_h(m360_work_status_label($code)) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="w1c-card">
            <?php if ($rows === []): ?>
                <p class="m360-wx-empty">پرونده‌ای برای اجرای کار نیست.</p>
            <?php else: ?>
                <table class="m360-wx-table">
                    <thead><tr>
                        <th>کارت کار</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                        <th>فنی</th><th>برآورد</th><th>قطعه</th><th>مالی</th><th>اجرای کار</th><th>QC</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= m360_work_h((string)$r['jobcard_id']) ?></td>
                            <td><?= m360_work_h((string)($r['customer_name'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['customer_mobile'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['vehicle_label'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['plate_number'] ?? '-')) ?></td>
                            <td><?= m360_work_h(m360_technician_workflow_status_label((string)($r['technical_status'] ?? ''))) ?></td>
                            <td><?= m360_work_h(m360_estimate_status_label((string)($r['estimate_status'] ?? ''))) ?></td>
                            <td><?= m360_work_h((string)($r['parts_gate_status'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['finance_gate_status'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['work_status_label'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($r['ready_for_qc_at'] ?? '') !== '' ? 'بله' : 'خیر') ?></td>
                            <td><a class="m360-wx-btn" href="erp-work-execution-detail.php?jobcard_id=<?= (int)$r['jobcard_id'] ?>">جزئیات</a></td>
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
