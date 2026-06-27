<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-release-lock-helper.php';

m360_release_lock_require_staff();

$items = m360_rc_final_checklist_items();
$passCount = count(array_filter($items, static fn(array $i): bool => ($i['status'] ?? '') === M360_RC_STATUS_PASS));
$current = 'erp-rc-final-checklist.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RC Final Checklist</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-rc-final.css">
</head>
<body class="m360-rcf-page">
<div class="m360-rcf-wrap">
    <header class="w1c-banner">
        <h1>RC Final Checklist</h1>
        <p><?= m360_release_lock_h((string)$passCount) ?> / <?= m360_release_lock_h((string)count($items)) ?> PASS — محاسبه از فایل/manifest</p>
        <p class="m360-rcf-note">Read-only — بدون POST عملیاتی</p>
    </header>
    <nav class="m360-rcf-nav">
        <?php foreach (m360_release_lock_nav() as $link): ?>
            <a href="<?= m360_release_lock_h((string)$link['href']) ?>" class="<?= $link['href'] === $current ? 'active' : '' ?>"><?= m360_release_lock_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <table class="m360-rcf-table">
            <thead><tr><th>Checklist</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= m360_release_lock_h((string)$item['title_fa']) ?></td>
                    <td><span class="m360-rcf-badge <?= m360_release_lock_badge((string)$item['status']) ?>"><?= m360_release_lock_h((string)$item['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<script src="assets/js/m360-rc-final.js"></script>
</body>
</html>
