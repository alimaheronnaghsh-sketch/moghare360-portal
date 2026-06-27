<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

m360_mgmt_require_staff();

$conn = customer_core_db();
$rows = $conn !== false ? m360_mgmt_operational_kpi_rows($conn) : [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI عملیاتی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>KPI عملیاتی</h1>
        <p>امروز / ۷ روز / ۳۰ روز — read-only</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>" class="<?= $link['href'] === 'erp-operational-kpi.php' ? 'active' : '' ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <?php if ($conn === false): ?>
            <p>اتصال به پایگاه داده برقرار نشد.</p>
        <?php else: ?>
            <table class="m360-mgmt-table">
                <thead><tr><th>KPI</th><th>امروز</th><th>۷ روز</th><th>۳۰ روز</th><th>توضیح</th><th>وضعیت</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $st = (string)($r['status'] ?? 'OK');
                    $badge = strtolower($st);
                ?>
                    <tr>
                        <td><?= m360_mgmt_h((string)$r['label_fa']) ?></td>
                        <td><?= m360_mgmt_h((string)$r['today']) ?></td>
                        <td><?= m360_mgmt_h((string)$r['days_7']) ?></td>
                        <td><?= m360_mgmt_h((string)$r['days_30']) ?></td>
                        <td><?= m360_mgmt_h((string)$r['hint_fa']) ?></td>
                        <td><span class="m360-mgmt-badge <?= m360_mgmt_h($badge === 'critical' ? 'crit' : ($badge === 'warning' ? 'warn' : 'ok')) ?>"><?= m360_mgmt_h($st) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
