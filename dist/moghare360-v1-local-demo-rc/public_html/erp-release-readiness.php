<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-release-readiness-helper.php';

m360_release_hardening_require_staff();

$report = m360_release_readiness_report();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Readiness</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>Release Readiness</h1>
        <p>Score: <?= m360_release_h((string)($report['readiness_score'] ?? 0)) ?>% — <?= m360_release_h((string)($report['recommendation_fa'] ?? '')) ?></p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-release-readiness.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <table class="m360-rc-table">
            <thead><tr><th>Category</th><th>Status</th><th>Evidence</th><th>Link</th></tr></thead>
            <tbody>
            <?php foreach (($report['categories'] ?? []) as $cat): ?>
                <tr>
                    <td><?= m360_release_h((string)$cat['title']) ?></td>
                    <td><span class="m360-rc-badge <?= m360_nav_badge_class((string)$cat['status']) ?>"><?= m360_release_h((string)$cat['status']) ?></span></td>
                    <td><?= m360_release_h((string)$cat['evidence']) ?></td>
                    <td><a href="<?= m360_release_h((string)$cat['link']) ?>"><?= m360_release_h((string)$cat['link']) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (($report['blockers'] ?? []) !== []): ?>
            <h3>Blockers</h3><ul><?php foreach ($report['blockers'] as $b): ?><li><?= m360_release_h((string)$b) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (($report['warnings'] ?? []) !== []): ?>
            <h3>Warnings</h3><ul><?php foreach (array_slice($report['warnings'], 0, 15) as $w): ?><li><?= m360_release_h((string)$w) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
