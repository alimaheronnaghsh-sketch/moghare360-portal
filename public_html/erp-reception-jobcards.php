<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCards dashboard (read-only GET).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-jobcard-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_reception_jobcard_require_staff();

$p15Missing = !m360_reception_jobcard_p15_gate_available();
$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$contractFilter = isset($_GET['contract']) ? strtoupper(trim((string)$_GET['contract'])) : 'ALL';
$conn = customer_core_db();
$jobcards = [];
$dbOk = $conn !== false;

if ($dbOk) {
    $jobcards = m360_reception_jobcard_list(
        $conn,
        $statusFilter === 'ALL' ? null : $statusFilter,
        $contractFilter === 'ALL' ? null : $contractFilter,
        150
    );
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>کارت‌های کار پذیرش</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <style>
        .p2-jc-wrap { max-width: 1240px; margin: 0 auto; }
        .p2-jc-filters { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.75rem; }
        .p2-jc-filters a { padding: 0.4rem 0.8rem; border-radius: 999px; border: 1px solid #d4d4d8; text-decoration: none; color: #27272a; font-size: 0.85rem; background: #fff; }
        .p2-jc-filters a.active { background: #166534; color: #fff; border-color: #166534; }
        .p2-jc-filters .label { width: 100%; font-size: 0.8rem; color: #71717a; margin-top: 0.5rem; }
        .p2-jc-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .p2-jc-table th, .p2-jc-table td { padding: 0.6rem 0.45rem; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        .p2-jc-table th { background: #fafafa; font-weight: 600; color: #52525b; }
        .p2-jc-badge { display: inline-block; padding: 0.18rem 0.5rem; border-radius: 999px; font-size: 0.76rem; background: #f4f4f5; }
        .p2-jc-badge.signed { background: #dcfce7; color: #166534; }
        .p2-jc-badge.unsigned { background: #fee2e2; color: #991b1b; }
        .p2-jc-badge.overridden { background: #fef3c7; color: #92400e; }
        .p2-jc-btn { display: inline-block; padding: 0.32rem 0.7rem; border-radius: 0.45rem; background: #166534; color: #fff; text-decoration: none; font-size: 0.82rem; }
        .p2-jc-alert { padding: 0.85rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .p2-jc-empty { padding: 2rem; text-align: center; color: #71717a; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#18181b;">
<div class="w1c-wrap p2-jc-wrap">
    <?php m360_operational_shell_render_board('reception_jobcards'); ?>
    <header class="w1c-banner">
        <h1>کارت‌های کار پذیرش</h1>
        <p>پیگیری مراجعه، ثبت ورود و آماده‌سازی فنی</p>
    </header>

    <?php if ($p15Missing): ?>
        <div class="p2-jc-alert">P1.5 Gate missing — ادامه عملیات کنترل‌شده ممکن نیست.</div>
    <?php endif; ?>

    <?php if (!$dbOk): ?>
        <section class="w1c-card w1c-error-box">
            <p>اتصال به پایگاه داده برقرار نشد. لطفاً بعداً تلاش کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <nav class="p2-jc-filters" aria-label="فیلتر وضعیت کارت کار">
                <span class="label">وضعیت پرونده:</span>
                <?php
                $statusFilters = ['ALL' => 'همه'] + array_combine(
                    m360_jobcard_workflow_statuses(),
                    array_map('m360_jobcard_workflow_status_label', m360_jobcard_workflow_statuses())
                );
                foreach ($statusFilters as $code => $label):
                    $active = ($statusFilter === $code);
                    $href = '?status=' . rawurlencode($code) . '&contract=' . rawurlencode($contractFilter);
                ?>
                    <a href="<?= m360_reception_jobcard_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_reception_jobcard_h($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <nav class="p2-jc-filters" aria-label="فیلتر قرارداد">
                <span class="label">وضعیت قرارداد:</span>
                <?php foreach (m360_reception_jobcard_contract_filter_codes() as $code):
                    $active = ($contractFilter === $code);
                    $href = '?status=' . rawurlencode($statusFilter) . '&contract=' . rawurlencode($code);
                ?>
                    <a href="<?= m360_reception_jobcard_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_reception_jobcard_h(m360_reception_jobcard_contract_filter_label($code)) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($jobcards === []): ?>
                <div class="p2-jc-empty">کارت کاری برای نمایش وجود ندارد.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="p2-jc-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>تاریخ</th>
                            <th>مشتری</th>
                            <th>موبایل</th>
                            <th>خودرو</th>
                            <th>پلاک</th>
                            <th>منبع</th>
                            <th>وضعیت</th>
                            <th>قرارداد</th>
                            <th>امضا / ورود</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobcards as $jc):
                            $cs = $jc['contract_summary'] ?? [];
                            $badgeClass = 'unsigned';
                            if (($cs['code'] ?? '') === 'SIGNED') {
                                $badgeClass = 'signed';
                            } elseif (($cs['code'] ?? '') === 'OVERRIDDEN') {
                                $badgeClass = 'overridden';
                            }
                            $arrival = (string)($jc['vehicle_arrival_at'] ?? $jc['checked_in_at'] ?? $jc['reception_at'] ?? '');
                            $signedAt = (string)($cs['signed_at'] ?? '');
                        ?>
                            <tr>
                                <td><?= m360_reception_jobcard_h((string)$jc['jobcard_id']) ?></td>
                                <td><?= m360_reception_jobcard_h(substr((string)($jc['created_at'] ?? ''), 0, 16)) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['customer_name'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['customer_mobile'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['vehicle_label'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['plate_number'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['source_label'] ?? '-')) ?></td>
                                <td><span class="p2-jc-badge"><?= m360_reception_jobcard_h((string)($jc['status_label'] ?? '')) ?></span></td>
                                <td><span class="p2-jc-badge <?= m360_reception_jobcard_h($badgeClass) ?>"><?= m360_reception_jobcard_h((string)($cs['label'] ?? '-')) ?></span></td>
                                <td><?= m360_reception_jobcard_h(substr($signedAt !== '' ? $signedAt : $arrival, 0, 16) ?: '-') ?></td>
                                <td><a class="p2-jc-btn" href="erp-reception-jobcard-detail.php?jobcard_id=<?= (int)$jc['jobcard_id'] ?>">جزئیات</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
