<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Reception online requests list (read-only GET).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';

m360_reception_require_staff();

$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$filterLabels = m360_reception_list_filter_labels();
if ($statusFilter !== 'ALL' && !isset($filterLabels[$statusFilter])) {
    $statusFilter = 'ALL';
}
$activeFilterLabel = $filterLabels[$statusFilter] ?? 'همه';

$conn = customer_core_db();
$requests = [];
$statusCounts = [];
$dbOk = $conn !== false;

if ($dbOk) {
    $requests = m360_reception_list_requests($conn, $statusFilter === 'ALL' ? null : $statusFilter, 150);
    $statusCounts = m360_reception_status_counts($conn);
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>درخواست‌های آنلاین — پذیرش</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        .p1-req-wrap { max-width: 1200px; margin: 0 auto; }
        .p1-req-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
        .p1-req-filters a { padding: 0.45rem 0.85rem; border-radius: 999px; border: 1px solid #d4d4d8; text-decoration: none; color: #27272a; font-size: 0.9rem; background: #fff; }
        .p1-req-filters a.active { background: #166534; color: #fff; border-color: #166534; font-weight: 700; box-shadow: 0 0 0 2px rgba(22, 101, 52, 0.25); }
        .p1-req-filter-meta { margin: 0 0 0.85rem; font-size: 0.92rem; color: #52525b; }
        .p1-req-filter-meta strong { color: #166534; }
        .p1-req-count { display: inline-block; min-width: 1.25rem; margin-right: 0.35rem; padding: 0.05rem 0.4rem; border-radius: 999px; font-size: 0.75rem; background: rgba(0,0,0,.06); }
        .p1-req-filters a.active .p1-req-count { background: rgba(255,255,255,.22); }
        .p1-req-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .p1-req-table th, .p1-req-table td { padding: 0.65rem 0.5rem; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        .p1-req-table th { background: #fafafa; font-weight: 600; color: #52525b; }
        .p1-req-badge { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.78rem; background: #f4f4f5; }
        .p1-req-badge.is-new { background: #dbeafe; color: #1e40af; }
        .p1-req-badge.is-review { background: #fef3c7; color: #92400e; }
        .p1-req-badge.is-accepted { background: #dcfce7; color: #166534; }
        .p1-req-badge.is-converted { background: #e0e7ff; color: #3730a3; }
        .p1-req-badge.is-rejected { background: #fee2e2; color: #991b1b; }
        .p1-req-btn { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 0.5rem; background: #166534; color: #fff; text-decoration: none; font-size: 0.85rem; }
        .p1-req-empty { padding: 2rem; text-align: center; color: #71717a; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#18181b;">
<div class="w1c-wrap p1-req-wrap">
    <header class="w1c-banner">
        <h1>درخواست‌های آنلاین مشتری</h1>
        <p>پذیرش — بررسی و تبدیل به کارت کار</p>
    </header>

    <?php if (!$dbOk): ?>
        <section class="w1c-card w1c-error-box">
            <p>اتصال به پایگاه داده برقرار نشد. لطفاً بعداً تلاش کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <p class="p1-req-filter-meta">فیلتر فعال: <strong><?= m360_reception_h($activeFilterLabel) ?></strong>
                — <?= count($requests) ?> مورد در این نما<?php if ($statusFilter !== 'ALL' && ($statusCounts['ALL'] ?? 0) > 0): ?>
                    (از <?= (int)($statusCounts['ALL'] ?? 0) ?> درخواست ثبت‌شده)<?php endif; ?></p>
            <nav class="p1-req-filters" aria-label="فیلتر وضعیت">
                <?php foreach ($filterLabels as $code => $label):
                    $active = ($statusFilter === $code);
                    $count = (int)($statusCounts[$code] ?? 0);
                ?>
                    <a href="?status=<?= m360_reception_h($code) ?>" class="<?= $active ? 'active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                        <?php if ($dbOk): ?><span class="p1-req-count"><?= $count ?></span><?php endif; ?>
                        <?= m360_reception_h($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($requests === []): ?>
                <div class="p1-req-empty">درخواستی برای فیلتر «<?= m360_reception_h($activeFilterLabel) ?>» وجود ندارد.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="p1-req-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>تاریخ ثبت</th>
                            <th>موبایل</th>
                            <th>مشتری</th>
                            <th>خودرو / پلاک</th>
                            <th>مراجعه</th>
                            <th>نوع</th>
                            <th>وضعیت</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row):
                            $status = strtoupper((string)($row['request_status'] ?? ''));
                            $badgeClass = 'is-new';
                            if ($status === M360_ONLINE_REQ_STATUS_UNDER_REVIEW) {
                                $badgeClass = 'is-review';
                            } elseif ($status === M360_ONLINE_REQ_STATUS_ACCEPTED) {
                                $badgeClass = 'is-accepted';
                            } elseif ($status === M360_ONLINE_REQ_STATUS_CONVERTED) {
                                $badgeClass = 'is-converted';
                            } elseif ($status === M360_ONLINE_REQ_STATUS_REJECTED) {
                                $badgeClass = 'is-rejected';
                            }
                            $customerLabel = trim((string)($row['erp_customer_name'] ?? ''));
                            if ($customerLabel === '') {
                                $customerLabel = (string)($row['customer_name'] ?? '');
                            }
                            $vehicleLabel = trim((string)($row['vehicle_brand'] ?? '') . ' ' . (string)($row['vehicle_model'] ?? ''));
                            $plate = (string)($row['vehicle_plate'] ?? '');
                        ?>
                            <tr>
                                <td><?= m360_reception_h((string)($row['online_request_id'] ?? '')) ?></td>
                                <td><?= m360_reception_h(substr((string)($row['created_at'] ?? ''), 0, 16)) ?></td>
                                <td><?= m360_reception_h((string)($row['mobile'] ?? '')) ?></td>
                                <td><?= m360_reception_h($customerLabel) ?></td>
                                <td><?= m360_reception_h(trim($vehicleLabel . ($plate !== '' ? ' — ' . $plate : ''))) ?></td>
                                <td><?= m360_reception_h((string)($row['visit_date'] ?? '—')) ?></td>
                                <td><?= m360_reception_h((string)($row['request_type'] ?? '—')) ?></td>
                                <td><span class="p1-req-badge <?= $badgeClass ?>"><?= m360_reception_h(m360_online_req_status_label_fa($status)) ?></span></td>
                                <td>
                                    <a class="p1-req-btn" href="erp-reception-online-request-detail.php?request_id=<?= (int)($row['online_request_id'] ?? 0) ?>">مشاهده</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-command-center.php">مرکز کارت کار</a>
        <a href="erp-v1-master-console.php">کنسول اصلی</a>
    </nav>
</div>
</body>
</html>
