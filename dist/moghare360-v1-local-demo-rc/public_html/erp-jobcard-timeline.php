<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-jobcard-timeline-helper.php';

m360_mgmt_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$conn = customer_core_db();
$data = ($conn !== false && $jobcardId > 0) ? m360_timeline_build($conn, $jobcardId) : ['jobcard' => null, 'events' => []];
$jc = $data['jobcard'];
$events = $data['events'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline مدیریتی JobCard</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>Timeline مدیریتی</h1>
        <p>مسیر Intake تا Closed — read-only</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <form method="get" action="erp-jobcard-timeline.php" class="m360-mgmt-filters">
            <label>کارت کار: <input type="number" name="jobcard_id" value="<?= $jobcardId > 0 ? $jobcardId : '' ?>" min="1" required></label>
            <button type="submit" class="m360-mgmt-btn">نمایش</button>
        </form>
    </section>
    <?php if ($jobcardId < 1): ?>
        <section class="w1c-card"><p class="m360-mgmt-empty">شناسه JobCard را وارد کنید.</p></section>
    <?php elseif ($jc === null): ?>
        <section class="w1c-card"><p class="m360-mgmt-empty">JobCard یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <p><strong>کارت کار:</strong> <?= m360_mgmt_h((string)$jc['jobcard_id']) ?> |
               <strong>مشتری:</strong> <?= m360_mgmt_h((string)($jc['customer_name'] ?? '-')) ?> |
               <strong>پلاک:</strong> <?= m360_mgmt_h((string)($jc['plate_number'] ?? '-')) ?> |
               <strong>مرحله:</strong> <?= m360_mgmt_h((string)($jc['current_stage_label_fa'] ?? '-')) ?></p>
        </section>
        <section class="w1c-card">
            <h2 class="m360-mgmt-section-title">رویدادها</h2>
            <?php if ($events === []): ?>
                <p class="m360-mgmt-empty">رویداد history ثبت نشده است.</p>
            <?php else: ?>
                <ul class="m360-mgmt-timeline">
                    <?php foreach ($events as $ev): ?>
                        <li>
                            <strong><?= m360_mgmt_h((string)($ev['event_label_fa'] ?? $ev['event_name'] ?? '')) ?></strong>
                            <span class="m360-mgmt-badge"><?= m360_mgmt_h((string)($ev['source'] ?? '')) ?></span><br>
                            <small><?= m360_mgmt_h((string)($ev['occurred_at'] ?? '')) ?></small>
                            <?php if ((string)($ev['event_note'] ?? '') !== ''): ?>
                                <div><?= m360_mgmt_h((string)$ev['event_note']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section class="w1c-card">
            <h2 class="m360-mgmt-section-title">نقاط عطف</h2>
            <ul>
                <?php foreach (M360_TIMELINE_MILESTONES_FA as $code => $label): ?>
                    <li><?= m360_mgmt_h($label) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
