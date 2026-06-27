<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-demo-package-helper.php';

m360_release_hardening_require_staff();

$manifest = m360_demo_package_manifest();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Package RC</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>Demo Package RC</h1>
        <p><?= m360_release_h((string)($manifest['rc_version'] ?? '')) ?> — Status: <span class="m360-rc-badge <?= m360_nav_badge_class((string)($manifest['rc_status'] ?? '')) ?>"><?= m360_release_h((string)($manifest['rc_status'] ?? '')) ?></span></p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-demo-package-rc.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <h2>Demo Entry Points</h2>
        <ul>
            <?php foreach (($manifest['demo_entry_points'] ?? []) as $ep): ?>
                <li><a href="<?= m360_release_h((string)$ep['url']) ?>"><?= m360_release_h((string)$ep['title_fa']) ?></a> (<?= m360_release_h((string)$ep['phase']) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <h2>Owner Demo Order</h2>
        <ol>
            <?php foreach (($manifest['owner_demo_order'] ?? []) as $url): ?>
                <li><a href="<?= m360_release_h((string)$url) ?>"><?= m360_release_h((string)$url) ?></a></li>
            <?php endforeach; ?>
        </ol>
        <h2>Required Migrations (P1–P10)</h2>
        <ul><?php foreach (($manifest['migrations'] ?? []) as $m): ?><li><?= m360_release_h((string)$m) ?></li><?php endforeach; ?></ul>
        <h2>Required Tests</h2>
        <ul><?php foreach (($manifest['tests'] ?? []) as $t): ?><li><?= m360_release_h((string)$t) ?></li><?php endforeach; ?></ul>
        <h2>Known Exclusions</h2>
        <ul><?php foreach (($manifest['exclusions'] ?? []) as $ex): ?><li><?= m360_release_h(str_replace('_', ' ', (string)$ex)) ?></li><?php endforeach; ?></ul>
        <p class="m360-rc-note"><?= m360_release_h((string)($manifest['package_build_note'] ?? '')) ?></p>
        <p>Zip build: <?= !empty($manifest['package_zip_available']) ? 'Yes' : 'No (manifest only)' ?></p>
    </section>
</div>
</body>
</html>
