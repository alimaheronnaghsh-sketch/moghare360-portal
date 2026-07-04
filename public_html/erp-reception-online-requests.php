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
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell m360-rw-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-workbench.php">← میز کار پذیرش</a>
            <span class="m360-rw-badge">پذیرش آنلاین</span>
        </div>
        <h1 class="m360-rw-title">درخواست‌های آنلاین مشتری</h1>
        <p class="m360-rw-subtitle">فهرست درخواست‌ها — تکمیل پرونده پذیرش از ستون اقدامات</p>
    </header>

    <?php if (!$dbOk): ?>
        <section class="m360-rw-alert">اتصال به پایگاه داده برقرار نشد. لطفاً بعداً تلاش کنید.</section>
    <?php else: ?>
        <section class="m360-rw-panel">
            <p class="m360-rw-muted">فیلتر فعال: <strong><?= m360_reception_h($activeFilterLabel) ?></strong>
                — <?= count($requests) ?> مورد<?php if ($statusFilter !== 'ALL' && ($statusCounts['ALL'] ?? 0) > 0): ?>
                    (از <?= (int)($statusCounts['ALL'] ?? 0) ?> درخواست)<?php endif; ?></p>
            <nav class="m360-rw-filters" aria-label="فیلتر وضعیت">
                <?php foreach ($filterLabels as $code => $label):
                    $active = ($statusFilter === $code);
                    $count = (int)($statusCounts[$code] ?? 0);
                ?>
                    <a href="?status=<?= m360_reception_h($code) ?>" class="m360-rw-filter-pill<?= $active ? ' is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                        <?php if ($dbOk): ?><span class="m360-rw-count"><?= $count ?></span><?php endif; ?>
                        <?= m360_reception_h($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($requests === []): ?>
                <p class="m360-rw-muted m360-rw-empty">درخواستی برای فیلتر «<?= m360_reception_h($activeFilterLabel) ?>» وجود ندارد.</p>
            <?php else: ?>
                <div class="m360-rw-table-wrap">
                    <table class="m360-rw-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>تاریخ</th>
                            <th>موبایل</th>
                            <th>مشتری</th>
                            <th>خودرو / پلاک</th>
                            <th>مراجعه</th>
                            <th>نوع</th>
                            <th>وضعیت</th>
                            <th>اقدام</th>
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
                            $rid = (int)($row['online_request_id'] ?? 0);
                        ?>
                            <tr>
                                <td><?= m360_reception_h((string)$rid) ?></td>
                                <td><?= m360_reception_h(substr((string)($row['created_at'] ?? ''), 0, 16)) ?></td>
                                <td><?= m360_reception_h((string)($row['mobile'] ?? '')) ?></td>
                                <td><?= m360_reception_h($customerLabel) ?></td>
                                <td><?= m360_reception_h(trim($vehicleLabel . ($plate !== '' ? ' — ' . $plate : ''))) ?></td>
                                <td><?= m360_reception_h((string)($row['visit_date'] ?? '—')) ?></td>
                                <td><?= m360_reception_h((string)($row['request_type'] ?? '—')) ?></td>
                                <td><span class="m360-rw-status-badge <?= m360_reception_h($badgeClass) ?>"><?= m360_reception_h(m360_online_req_status_label_fa($status)) ?></span></td>
                                <td class="m360-rw-table-actions">
                                    <a class="m360-rw-btn m360-rw-btn-sm" href="erp-reception-intake-file.php?online_request_id=<?= $rid ?>">تکمیل پرونده</a>
                                    <a class="m360-rw-btn m360-rw-btn-secondary m360-rw-btn-sm" href="erp-reception-online-request-detail.php?request_id=<?= $rid ?>">مشاهده</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="m360-rw-footer">
        <a href="erp-reception-workbench.php">میز کار پذیرش</a>
        <a href="erp-staff-home.php">داشبورد پرسنل</a>
    </nav>
</div>
</body>
</html>
