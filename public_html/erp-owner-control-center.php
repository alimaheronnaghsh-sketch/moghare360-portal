<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-owner-control-helper.php';

m360_mgmt_require_staff();

$section = isset($_GET['section']) ? strtolower(trim((string)$_GET['section'])) : 'high_risk';
if (!in_array($section, m360_owner_control_sections(), true)) {
    $section = 'high_risk';
}

$conn = customer_core_db();
$counts = $conn !== false ? m360_owner_control_counts($conn) : [];
$rows = $conn !== false ? m360_owner_control_list($conn, $section, 50) : [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرکز کنترل مالک</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>مرکز کنترل مالک</h1>
        <p>فقط مشاهده — بدون approve / override / release / payment</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>" class="<?= $link['href'] === 'erp-owner-control-center.php' ? 'active' : '' ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-mgmt-filters">
        <?php foreach (M360_OWNER_SECTION_LABELS_FA as $code => $label): ?>
            <a href="?section=<?= m360_mgmt_h($code) ?>" class="<?= $section === $code ? 'active' : '' ?>"><?= m360_mgmt_h($label) ?> (<?= (int)($counts[$code] ?? 0) ?>)</a>
        <?php endforeach; ?>
    </div>
    <section class="w1c-card">
        <h2 class="m360-mgmt-section-title"><?= m360_mgmt_h(M360_OWNER_SECTION_LABELS_FA[$section] ?? $section) ?></h2>
        <?php if ($rows === []): ?>
            <p class="m360-mgmt-empty">موردی در این بخش نیست.</p>
        <?php else: ?>
            <table class="m360-mgmt-table">
                <thead><tr>
                    <th>کارت کار</th><th>مشتری</th><th>پلاک</th><th>مرحله</th><th>ریسک</th><th>آخرین فعالیت</th><th>مانده</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= m360_mgmt_h((string)$r['jobcard_id']) ?></td>
                        <td><?= m360_mgmt_h((string)($r['customer_name'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['plate_no'] ?? $r['plate_number'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['current_stage_label_fa'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['risk_flags_text'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['last_activity_at'] ?? $r['updated_at'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h(number_format((float)($r['settlement_remaining_amount'] ?? $r['remaining_amount'] ?? 0))) ?></td>
                        <td>
                            <a class="m360-mgmt-btn secondary" href="<?= m360_mgmt_h((string)$r['timeline_href']) ?>">Timeline</a>
                            <a class="m360-mgmt-btn" href="<?= m360_mgmt_h((string)($r['stage_href'] ?? '#')) ?>">مرحله</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
