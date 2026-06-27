<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-route-audit-helper.php';

m360_release_hardening_require_staff();

$summary = m360_route_audit_summary();
$rows = $summary['rows'] ?? [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Map — MOGHARE360 V1</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>Route Map</h1>
        <p>P1–P10 — <?= (int)($summary['existing'] ?? 0) ?>/<?= (int)($summary['total'] ?? 0) ?> files present</p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-route-map.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <table class="m360-rc-table">
            <thead><tr><th>Phase</th><th>Title</th><th>URL</th><th>Category</th><th>Method</th><th>File</th><th>API</th><th>Customer</th><th>Staff</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= m360_release_h((string)$r['phase_code']) ?></td>
                    <td><?= m360_release_h((string)$r['title_fa']) ?></td>
                    <td><a href="<?= m360_release_h((string)$r['url']) ?>"><?= m360_release_h((string)$r['url']) ?></a></td>
                    <td><?= m360_release_h((string)$r['category']) ?></td>
                    <td><?= m360_release_h((string)$r['expected_method']) ?></td>
                    <td><span class="m360-rc-badge <?= !empty($r['file_exists']) ? 'pass' : 'warn' ?>"><?= !empty($r['file_exists']) ? 'OK' : 'MISSING' ?></span></td>
                    <td><?= !empty($r['is_api']) ? 'Yes' : 'No' ?></td>
                    <td><?= !empty($r['is_customer_entry']) ? 'Yes' : 'No' ?></td>
                    <td><?= !empty($r['is_staff_entry']) ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
