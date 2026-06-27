<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-local-demo-package-helper.php';

m360_release_lock_require_staff();

$pkg = m360_local_package_status();
$current = 'erp-local-demo-package.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Demo Package</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-rc-final.css">
</head>
<body class="m360-rcf-page">
<div class="m360-rcf-wrap">
    <header class="w1c-banner">
        <h1>Local Demo Package — RC</h1>
        <p>Status: <span class="m360-rcf-badge <?= m360_release_lock_badge((string)$pkg['status']) ?>"><?= m360_release_lock_h((string)$pkg['status']) ?></span></p>
        <p class="m360-rcf-note">این صفحه zip نمی‌سازد — فقط manifest و وضعیت. Package فقط با PowerShell script.</p>
    </header>
    <nav class="m360-rcf-nav">
        <?php foreach (m360_release_lock_nav() as $link): ?>
            <a href="<?= m360_release_lock_h((string)$link['href']) ?>" class="<?= $link['href'] === $current ? 'active' : '' ?>"><?= m360_release_lock_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-rcf-cards">
        <div class="m360-rcf-card"><div class="val"><?= $pkg['script_exists'] ? 'OK' : 'MISS' ?></div><div class="lbl">Package Script</div></div>
        <div class="m360-rcf-card"><div class="val"><?= $pkg['dist_built'] ? 'YES' : 'NO' ?></div><div class="lbl">Dist Built</div></div>
        <div class="m360-rcf-card"><div class="val"><?= $pkg['zip_built'] ? 'YES' : 'NO' ?></div><div class="lbl">Zip Built</div></div>
        <div class="m360-rcf-card"><div class="val"><?= m360_release_lock_h((string)($pkg['sha256'] ?? '—')) ?></div><div class="lbl">SHA256</div></div>
    </div>
    <section class="w1c-card">
        <h2>Paths</h2>
        <ul class="m360-rcf-list">
            <li>Script: <code><?= m360_release_lock_h((string)$pkg['package_script']) ?></code></li>
            <li>Dist: <code><?= m360_release_lock_h((string)$pkg['dist_dir']) ?></code></li>
            <li>Zip: <code><?= m360_release_lock_h((string)$pkg['zip_path']) ?></code></li>
            <li>UI builds zip: <?= $pkg['ui_builds_zip'] ? 'true' : 'false (forbidden)' ?></li>
        </ul>
    </section>
    <section class="w1c-card">
        <h2>Include Patterns</h2>
        <ul class="m360-rcf-list"><?php foreach ($pkg['include_patterns'] as $p): ?><li><code><?= m360_release_lock_h($p) ?></code></li><?php endforeach; ?></ul>
    </section>
    <section class="w1c-card">
        <h2>Exclude Rules</h2>
        <ul class="m360-rcf-list"><?php foreach ($pkg['exclude_rules'] as $r): ?><li><code><?= m360_release_lock_h($r) ?></code></li><?php endforeach; ?></ul>
    </section>
    <section class="w1c-card">
        <h2>Credential Scan Patterns</h2>
        <ul class="m360-rcf-list"><?php foreach ($pkg['scan_patterns'] as $s): ?><li><?= m360_release_lock_h($s) ?></li><?php endforeach; ?></ul>
    </section>
    <?php if (($pkg['blockers'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Blockers</h3><ul class="m360-rcf-list"><?php foreach ($pkg['blockers'] as $b): ?><li><?= m360_release_lock_h((string)$b) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
    <?php if (($pkg['warnings'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Warnings</h3><ul class="m360-rcf-list"><?php foreach ($pkg['warnings'] as $w): ?><li><?= m360_release_lock_h((string)$w) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
</div>
<script src="assets/js/m360-rc-final.js"></script>
</body>
</html>
