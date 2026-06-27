<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-demo-scenario-helper.php';

m360_soft_run_require_staff();

$conn = customer_core_db();
$demo = $conn !== false ? m360_soft_run_find_demo_jobcard($conn) : null;
$jobcardId = (int)($demo['jobcard_id'] ?? 0);
$stages = $conn !== false ? m360_demo_scenario_status($conn, $jobcardId) : array_map(static function (array $def): array {
    return array_merge($def, [
        'stage_status' => M360_SOFT_RUN_STATUS_NOT_RUN,
        'evidence_table' => (string)$def['source_table'],
        'evidence_id' => 0,
        'message_fa' => 'اتصال DB برقرار نیست.',
        'gate_result' => M360_SOFT_RUN_STATUS_NOT_RUN,
        'audit_result' => M360_SOFT_RUN_STATUS_NOT_RUN,
        'page_link' => (string)$def['page'],
        'warning' => '',
    ]);
}, m360_demo_scenario_stages());

function m360_sr_badge(string $s): string {
    return match (strtoupper($s)) {
        M360_SOFT_RUN_STATUS_PASS => 'pass',
        M360_SOFT_RUN_STATUS_WARNING => 'warn',
        M360_SOFT_RUN_STATUS_BLOCKED => 'block',
        default => 'notrun',
    };
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سناریوی End-to-End Demo</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-soft-run.css">
</head>
<body class="m360-sr-page">
<div class="w1c-wrap m360-sr-wrap">
    <header class="w1c-banner">
        <h1>سناریوی End-to-End Demo</h1>
        <p>JobCard Demo: <?= $jobcardId > 0 ? m360_soft_run_h((string)$jobcardId) : '—' ?> | Prefix: <?= m360_soft_run_h(M360_SOFT_RUN_DEMO_PREFIX) ?></p>
    </header>
    <nav class="m360-sr-nav">
        <?php foreach (m360_soft_run_nav() as $link): ?>
            <a href="<?= m360_soft_run_h($link['href']) ?>" class="<?= $link['href'] === 'erp-end-to-end-demo-scenario.php' ? 'active' : '' ?>"><?= m360_soft_run_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <table class="m360-sr-table">
            <thead><tr><th>مرحله</th><th>فاز</th><th>وضعیت</th><th>جدول</th><th>ID</th><th>Gate</th><th>Audit</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stages as $st): ?>
                <tr>
                    <td><?= m360_soft_run_h((string)$st['label_fa']) ?></td>
                    <td><?= m360_soft_run_h((string)$st['phase']) ?></td>
                    <td><span class="m360-sr-badge <?= m360_sr_badge((string)$st['stage_status']) ?>"><?= m360_soft_run_h((string)$st['stage_status']) ?></span></td>
                    <td><?= m360_soft_run_h((string)$st['evidence_table']) ?></td>
                    <td><?= (int)($st['evidence_id'] ?? 0) ?></td>
                    <td><?= m360_soft_run_h((string)$st['gate_result']) ?></td>
                    <td><?= m360_soft_run_h((string)$st['audit_result']) ?></td>
                    <td><a class="m360-sr-btn secondary" href="<?= m360_soft_run_h((string)$st['page_link']) ?>">صفحه</a></td>
                </tr>
                <?php if ((string)($st['message_fa'] ?? '') !== ''): ?>
                    <tr><td colspan="8" class="m360-sr-note"><?= m360_soft_run_h((string)$st['message_fa']) ?><?= (string)($st['warning'] ?? '') !== '' ? ' — ' . m360_soft_run_h((string)$st['warning']) : '' ?></td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
