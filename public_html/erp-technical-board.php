<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';

m360_am_guard_any(['workshop.electrical_options.view', 'workshop.inspection.view']);
m360_technical_require_staff();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';
$wsCtx = m360_ws_require_actor_context();

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
        150,
        (int)$wsCtx['company_id'],
        (bool)$wsCtx['is_owner']
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
    <?php m360_operational_shell_render_stylesheets(); ?>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .p3-tech-wrap { max-width: 1260px; margin: 0 auto; }
        .p3-tech-filters { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.65rem; }
        .p3-tech-filters .label { width: 100%; font-size: 0.78rem; margin-top: 0.4rem; }
        .p3-tech-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .p3-tech-badge { display: inline-block; padding: 0.16rem 0.48rem; border-radius: 999px; background: rgba(34,197,94,.12); }
        .p3-tech-alert { padding: 0.8rem; border-radius: 0.45rem; margin-bottom: 1rem; }
        .p3-tech-empty { padding: 2rem; text-align: center; }
    </style>
</head>
<body class="m360-lux-page">
<div class="w1c-wrap p3-tech-wrap">
    <?php m360_operational_shell_render_board('technical_board'); ?>
    <header class="w1c-banner">
        <h1>برد عملیات فنی</h1>
        <p>پرونده‌های آماده فنی، عیب‌یابی، تخصیص تکنسین و کنترل گیت قرارداد.</p>
    </header>

    <?php if ($p15Missing): ?>
        <div class="p3-tech-alert m360-lux-warn">گیت قرارداد فعال نیست؛ کنترل قرارداد باید قبل از شروع کار روشن باشد.</div>
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
                            <th>شناسه</th><th>تاریخ</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                            <th>پذیرش</th><th>فنی</th><th>قرارداد</th><th>تکنسین</th><th>آماده فنی</th><th>آخرین رویداد</th><th>اقدام</th>
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
                                <td><a class="p3-tech-btn m360-lux-link" href="erp-technical-jobcard-detail.php?jobcard_id=<?= (int)$jc['jobcard_id'] ?>">جزئیات</a></td>
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

