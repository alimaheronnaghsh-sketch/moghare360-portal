<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-owner-presentation-helper.php';

m360_release_lock_require_staff();

$report = m360_owner_presentation_lock_report();
$current = 'erp-owner-presentation-lock.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Presentation Lock</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-rc-final.css">
</head>
<body class="m360-rcf-page">
<div class="m360-rcf-wrap">
    <header class="w1c-banner">
        <h1>Owner Presentation Lock</h1>
        <p>RC Status: <?= m360_release_lock_h((string)($report['rc_status'] ?? '')) ?></p>
        <p class="m360-rcf-note">ترتیب پیشنهادی ارائه ۲۰ دقیقه‌ای — read-only</p>
    </header>
    <nav class="m360-rcf-nav">
        <?php foreach (m360_release_lock_nav() as $link): ?>
            <a href="<?= m360_release_lock_h((string)$link['href']) ?>" class="<?= $link['href'] === $current ? 'active' : '' ?>"><?= m360_release_lock_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <h2>Demo Flow (10 steps)</h2>
        <table class="m360-rcf-table">
            <thead><tr><th>#</th><th>صفحه</th><th>URL</th><th>یادداشت</th></tr></thead>
            <tbody>
            <?php foreach ($report['flow'] as $step): ?>
                <tr>
                    <td><?= m360_release_lock_h((string)$step['order']) ?></td>
                    <td><?= m360_release_lock_h((string)$step['title_fa']) ?></td>
                    <td><a href="<?= m360_release_lock_h((string)$step['url']) ?>"><?= m360_release_lock_h((string)$step['url']) ?></a></td>
                    <td><?= m360_release_lock_h((string)$step['note_fa']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="w1c-card">
        <h2>نقاط قوت برای ارائه</h2>
        <ul class="m360-rcf-list"><?php foreach ($report['strengths'] as $s): ?><li><?= m360_release_lock_h($s) ?></li><?php endforeach; ?></ul>
    </section>
    <section class="w1c-card">
        <h2>مرز Scope — در V1 نیست (قول ندهید)</h2>
        <ul class="m360-rcf-list"><?php foreach ($report['exclusions'] as $e): ?><li><?= m360_release_lock_h($e) ?></li><?php endforeach; ?></ul>
    </section>
    <section class="w1c-card">
        <h2>Owner Signoff Checklist</h2>
        <table class="m360-rcf-table">
            <thead><tr><th>Item</th></tr></thead>
            <tbody>
            <?php foreach ($report['signoff_checklist'] as $item): ?>
                <tr><td><?= m360_release_lock_h((string)$item['title_fa']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <p><a class="m360-rcf-btn" href="../docs/demo/MOGHARE360_V1_OWNER_PRESENTATION_SCRIPT_FINAL.md">Presentation Script (doc)</a></p>
</div>
<script src="assets/js/m360-rc-final.js"></script>
</body>
</html>
