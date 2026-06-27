<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-online-intake-bridge-helper.php';
require_once __DIR__ . '/includes/m360-release-lock-helper.php';

m360_release_lock_require_staff();

$report = m360_online_bridge_readiness_report();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Test Readiness</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-rc-final.css">
    <link rel="stylesheet" href="assets/css/m360-online-test.css">
</head>
<body class="m360-ot-page">
<div class="m360-ot-wrap">
    <header class="w1c-banner">
        <h1>Online Test Readiness — P11.3</h1>
        <p>وضعیت: <span class="m360-ot-badge <?= m360_online_bridge_badge_class((string)$report['status']) ?>"><?= m360_online_bridge_h((string)$report['status']) ?></span></p>
        <p class="m360-rcf-note">Read-only — بدون نمایش secret، IP یا payload خام</p>
    </header>
    <nav class="m360-rcf-nav">
        <?php foreach (m360_release_lock_nav() as $link): ?>
            <a href="<?= m360_online_bridge_h((string)$link['href']) ?>"><?= m360_online_bridge_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
        <a href="erp-online-test-readiness.php" class="active">Online Test Readiness</a>
    </nav>
    <section class="w1c-card">
        <table class="m360-ot-table">
            <thead><tr><th>Check</th><th>Status</th></tr></thead>
            <tbody>
            <tr><td>Bridge config example</td><td><?= $report['private_config_present'] || is_file(m360_online_bridge_repo_root() . '/private/m360-online-bridge-config.example.php') ? 'OK' : 'MISS' ?></td></tr>
            <tr><td>Private bridge config present</td><td><?= ($report['private_config_present'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>Bridge enabled</td><td><?= ($report['bridge_enabled'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>Secret configured (masked)</td><td><?= m360_online_bridge_h((string)$report['secret_masked']) ?></td></tr>
            <tr><td>Secure API exists</td><td><?= ($report['secure_api_exists'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>cPanel form template</td><td><?= ($report['cpanel_form_template'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>cPanel forwarder template</td><td><?= ($report['cpanel_forwarder_template'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>P1 intake persistence</td><td><?= ($report['p1_persistence'] ?? false) ? (string)$report['p1_table'] : 'MISSING' ?></td></tr>
            <tr><td>Last log event</td><td><?= m360_online_bridge_h((string)$report['last_log_event']) ?></td></tr>
            <tr><td>Logs folder writable</td><td><?= ($report['log_dir_ok'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>Public debug disabled</td><td><?= ($report['public_debug_disabled'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            <tr><td>Customer JSON hidden</td><td><?= ($report['customer_json_hidden'] ?? false) ? 'YES' : 'NO' ?></td></tr>
            </tbody>
        </table>
    </section>
    <?php if (($report['blockers'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Blockers</h3><ul><?php foreach ($report['blockers'] as $b): ?><li><?= m360_online_bridge_h((string)$b) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
    <?php if (($report['warnings'] ?? []) !== []): ?>
        <section class="w1c-card"><h3>Warnings</h3><ul><?php foreach ($report['warnings'] as $w): ?><li><?= m360_online_bridge_h((string)$w) ?></li><?php endforeach; ?></ul></section>
    <?php endif; ?>
</div>
<script src="assets/js/m360-online-test.js"></script>
</body>
</html>
