<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-route-audit-helper.php';
require_once __DIR__ . '/includes/m360-release-hardening-helper.php';

m360_release_hardening_require_staff();

$summary = m360_route_audit_summary();
$audit = m360_release_hardening_audit();
$missing = $summary['missing_rows'] ?? [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Audit</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>Link Audit</h1>
        <p>Read-only file_exists — no HTTP external calls</p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-link-audit.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-rc-cards">
        <div class="m360-rc-card"><div class="val"><?= (int)($summary['total'] ?? 0) ?></div><div class="lbl">Total Routes</div></div>
        <div class="m360-rc-card"><div class="val"><?= (int)($summary['missing'] ?? 0) ?></div><div class="lbl">Missing Files</div></div>
        <div class="m360-rc-card"><div class="val"><?= (int)($summary['missing_api'] ?? 0) ?></div><div class="lbl">Missing API</div></div>
        <div class="m360-rc-card"><div class="val"><?= (int)($audit['docs_missing'] ? count($audit['docs_missing']) : 0) ?></div><div class="lbl">Missing Docs</div></div>
    </div>
    <section class="w1c-card">
        <h2>Missing Routes (Warning)</h2>
        <?php if ($missing === []): ?>
            <p class="m360-rc-note">همه routeهای registry فایل دارند.</p>
        <?php else: ?>
            <table class="m360-rc-table">
                <thead><tr><th>Key</th><th>Phase</th><th>URL</th><th>Category</th></tr></thead>
                <tbody>
                <?php foreach ($missing as $r): ?>
                    <tr>
                        <td><?= m360_release_h((string)$r['route_key']) ?></td>
                        <td><?= m360_release_h((string)$r['phase_code']) ?></td>
                        <td><?= m360_release_h((string)$r['url']) ?></td>
                        <td><?= m360_release_h((string)$r['category']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <h2>Doc References</h2>
        <ul>
            <?php foreach (m360_release_required_docs() as $doc):
                $ok = is_file(dirname(__DIR__) . '/' . $doc);
            ?>
                <li><span class="m360-rc-badge <?= $ok ? 'pass' : 'warn' ?>"><?= $ok ? 'OK' : 'MISSING' ?></span> <?= m360_release_h($doc) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
</body>
</html>
