<?php
declare(strict_types=1);

/**
 * MOGHARE360 P3 — Technical operation board (read-only GET).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_technical_require_staff();

$p15Missing = !m360_technical_p15_gate_available();
$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$contractFilter = isset($_GET['contract']) ? strtoupper(trim((string)$_GET['contract'])) : 'ALL';
$conn = customer_core_db();
$jobcards = [];
$dbOk = $conn !== false;

if ($dbOk) {
    $jobcards = m360_technical_list_jobcards(
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
    <title>برد عملیات فنی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <style>
        .p3-tech-wrap { max-width: 1260px; margin: 0 auto; }
        .p3-tech-filters { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.65rem; }
        .p3-tech-filters a { padding: 0.38rem 0.75rem; border-radius: 999px; border: 1px solid #d4d4d8; text-decoration: none; color: #27272a; font-size: 0.84rem; background: #fff; }
        .p3-tech-filters a.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }
        .p3-tech-filters .label { width: 100%; font-size: 0.78rem; color: #71717a; margin-top: 0.4rem; }
        .p3-tech-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .p3-tech-table th, .p3-tech-table td { padding: 0.55rem 0.4rem; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        .p3-tech-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .p3-tech-badge { display: inline-block; padding: 0.16rem 0.48rem; border-radius: 999px; font-size: 0.74rem; background: #f1f5f9; }
        .p3-tech-btn { display: inline-block; padding: 0.3rem 0.65rem; border-radius: 0.4rem; background: #1e3a8a; color: #fff; text-decoration: none; font-size: 0.8rem; }
        .p3-tech-alert { padding: 0.8rem; border-radius: 0.45rem; margin-bottom: 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .p3-tech-empty { padding: 2rem; text-align: center; color: #71717a; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#0f172a;">
<div class="w1c-wrap p3-tech-wrap">
    <?php m360_operational_shell_render_board('technical_board'); ?>
    <header class="w1c-banner">
        <h1>برد عملیات فنی</h1>
        <p>پرونده‌های آماده فنی — عیب‌یابی و اجرای سرویس</p>
    </header>

    <?php if ($p15Missing): ?>
        <div class="p3-tech-alert">P1.5 Gate missing — کنترل قرارداد فعال نیست.</div>
    <?php endif; ?>

    <?php if (!$dbOk): ?>
        <section class="w1c-card w1c-error-box"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <nav class="p3-tech-filters" aria-label="فیلتر وضعیت فنی">
                <span class="label">وضعیت فنی:</span>
                <?php
                $filters = ['ALL' => 'همه'] + array_combine(
                    m360_technician_workflow_board_statuses(),
                    array_map('m360_technician_workflow_status_label', m360_technician_workflow_board_statuses())
                );
                foreach ($filters as $code => $label):
                    $active = ($statusFilter === $code);
                    $href = '?status=' . rawurlencode($code) . '&contract=' . rawurlencode($contractFilter);
                ?>
                    <a href="<?= m360_technical_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_technical_h($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <nav class="p3-tech-filters" aria-label="فیلتر قرارداد">
                <span class="label">قرارداد:</span>
                <?php foreach (m360_technical_contract_filter_codes() as $code):
                    $active = ($contractFilter === $code);
                    $href = '?status=' . rawurlencode($statusFilter) . '&contract=' . rawurlencode($code);
                ?>
                    <a href="<?= m360_technical_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_technical_h(m360_technical_contract_filter_label($code)) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($jobcards === []): ?>
                <div class="p3-tech-empty">پرونده فنی برای نمایش وجود ندارد.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="p3-tech-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>تاریخ</th>
                            <th>مشتری</th>
                            <th>موبایل</th>
                            <th>خودرو</th>
                            <th>پلاک</th>
                            <th>پذیرش</th>
                            <th>فنی</th>
                            <th>قرارداد</th>
                            <th>تکنسین</th>
                            <th>آماده فنی</th>
                            <th>آخرین رویداد</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobcards as $jc):
                            $cs = $jc['contract_summary'] ?? [];
                        ?>
                            <tr>
                                <td><?= m360_technical_h((string)$jc['jobcard_id']) ?></td>
                                <td><?= m360_technical_h(substr((string)($jc['created_at'] ?? ''), 0, 16)) ?></td>
                                <td><?= m360_technical_h((string)($jc['customer_name'] ?? '-')) ?></td>
                                <td><?= m360_technical_h((string)($jc['customer_mobile'] ?? '-')) ?></td>
                                <td><?= m360_technical_h((string)($jc['vehicle_label'] ?? '-')) ?></td>
                                <td><?= m360_technical_h((string)($jc['plate_number'] ?? '-')) ?></td>
                                <td><span class="p3-tech-badge"><?= m360_technical_h((string)($jc['reception_status_label'] ?? '')) ?></span></td>
                                <td><span class="p3-tech-badge"><?= m360_technical_h((string)($jc['technical_status_label'] ?? '')) ?></span></td>
                                <td><?= m360_technical_h((string)($cs['label'] ?? '-')) ?></td>
                                <td><?= m360_technical_h((string)($jc['assigned_technician_user_id'] ?? '-') ?: '-') ?></td>
                                <td><?= m360_technical_h(substr((string)($jc['ready_for_technical_at'] ?? ''), 0, 16) ?: '-') ?></td>
                                <td><?= m360_technical_h((string)($jc['last_technical_action'] ?? '-')) ?></td>
                                <td><a class="p3-tech-btn" href="erp-technical-jobcard-detail.php?jobcard_id=<?= (int)$jc['jobcard_id'] ?>">جزئیات</a></td>
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
