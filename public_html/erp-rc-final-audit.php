<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-rc-final-audit-helper.php';

m360_release_lock_require_staff();

$report = m360_rc_final_audit_report();
$lock = $report['lock'] ?? [];
$current = 'erp-rc-final-audit.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RC Final Audit</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-rc-final.css">
</head>
<body class="m360-rcf-page">
<div class="m360-rcf-wrap">
    <header class="w1c-banner">
        <h1>RC Final Audit — MOGHARE360 V1</h1>
        <p>وضعیت: <?= m360_release_lock_h((string)($lock['rc_status'] ?? '')) ?> — Score: <?= m360_release_lock_h((string)($lock['readiness_score'] ?? 0)) ?>%</p>
        <p class="m360-rcf-note"><?= m360_release_lock_h((string)($lock['recommendation_fa'] ?? '')) ?></p>
    </header>
    <nav class="m360-rcf-nav">
        <?php foreach (m360_release_lock_nav() as $link): ?>
            <a href="<?= m360_release_lock_h((string)$link['href']) ?>" class="<?= $link['href'] === $current ? 'active' : '' ?>"><?= m360_release_lock_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-rcf-cards">
        <div class="m360-rcf-card"><div class="val"><?= m360_release_lock_h((string)($lock['route_audit']['total_routes'] ?? 0)) ?></div><div class="lbl">Route Registry</div></div>
        <div class="m360-rcf-card"><div class="val"><?= m360_release_lock_h((string)($lock['route_audit']['existing_files'] ?? 0)) ?></div><div class="lbl">فایل موجود</div></div>
        <div class="m360-rcf-card"><div class="val"><?= m360_release_lock_h((string)($lock['docs_found'] ?? 0)) ?>/<?= m360_release_lock_h((string)($lock['docs_total'] ?? 0)) ?></div><div class="lbl">Docs</div></div>
        <div class="m360-rcf-card"><div class="val"><?= m360_release_lock_h((string)($lock['tests_found'] ?? 0)) ?>/<?= m360_release_lock_h((string)($lock['tests_total'] ?? 0)) ?></div><div class="lbl">Tests</div></div>
    </div>
    <section class="w1c-card">
        <h2>Phase Status P1–P11</h2>
        <table class="m360-rcf-table">
            <thead><tr><th>Phase</th><th>Routes</th><th>Missing</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (($report['phase_status'] ?? []) as $row): ?>
                <tr>
                    <td><?= m360_release_lock_h((string)$row['phase']) ?></td>
                    <td><?= m360_release_lock_h((string)$row['route_count']) ?></td>
                    <td><?= m360_release_lock_h((string)$row['missing']) ?></td>
                    <td><span class="m360-rcf-badge <?= m360_release_lock_badge((string)$row['status']) ?>"><?= m360_release_lock_h((string)$row['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="w1c-card">
        <h2>Audit Categories</h2>
        <table class="m360-rcf-table">
            <thead><tr><th>Category</th><th>Status</th><th>Evidence</th></tr></thead>
            <tbody>
            <?php foreach (($report['categories'] ?? []) as $cat): ?>
                <tr>
                    <td><?= m360_release_lock_h((string)$cat['title']) ?></td>
                    <td><span class="m360-rcf-badge <?= m360_release_lock_badge((string)$cat['status']) ?>"><?= m360_release_lock_h((string)$cat['status']) ?></span></td>
                    <td><?= m360_release_lock_h((string)$cat['evidence']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php if (($lock['blockers'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Blockers</h3><ul class="m360-rcf-list"><?php foreach ($lock['blockers'] as $b): ?><li><?= m360_release_lock_h((string)$b) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
    <?php if (($lock['warnings'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Warnings</h3><ul class="m360-rcf-list"><?php foreach (array_slice($lock['warnings'], 0, 20) as $w): ?><li><?= m360_release_lock_h((string)$w) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
    <p class="m360-rcf-note">Read-only — بدون POST عملیاتی</p>
</div>
<script src="assets/js/m360-rc-final.js"></script>
</body>
</html>
