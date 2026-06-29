<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_estimate_require_staff();

$filter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$rows = $conn !== false ? m360_estimate_board_list($conn, $filter === 'ALL' ? null : $filter, 150) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برد برآورد هزینه</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
</head>
<body class="m360-est-page">
<div class="w1c-wrap m360-est-wrap">
    <?php m360_operational_shell_render_board('estimate_board'); ?>
    <header class="w1c-banner">
        <h1>برد برآورد و تأیید</h1>
        <p>برآورد هزینه — تأیید مشتری — گیت قطعه و مالی</p>
    </header>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <nav class="m360-est-filters">
            <?php foreach (array_merge(['ALL' => 'همه'], array_combine(m360_estimate_board_filters(), array_map('m360_estimate_status_label', m360_estimate_board_filters()))) as $code => $label): ?>
                <a href="?status=<?= m360_estimate_h($code) ?>" class="<?= $filter === $code ? 'active' : '' ?>"><?= m360_estimate_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="w1c-card">
            <?php if ($rows === []): ?>
                <p class="m360-est-empty">پرونده‌ای برای نمایش نیست.</p>
            <?php else: ?>
                <table class="m360-est-table">
                    <thead><tr>
                        <th>کارت کار</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                        <th>فنی</th><th>برآورد</th><th>مبلغ</th><th>علی‌الحساب</th><th>قطعه</th><th>مالی</th><th></th>
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
                            <td><?= m360_estimate_h((string)($r['parts_gate_status'] ?? '-')) ?></td>
                            <td><?= m360_estimate_h((string)($r['finance_gate_status'] ?? '-')) ?></td>
                            <td><a class="m360-est-btn" href="erp-estimate-detail.php?jobcard_id=<?= (int)$r['jobcard_id'] ?>">جزئیات</a></td>
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
