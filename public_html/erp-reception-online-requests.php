<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Online requests list (presentation unified under Customer Relations hub).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'reception-ui-helper.php';

m360_reception_require_staff();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$filterLabels = m360_reception_list_filter_labels();
if ($statusFilter !== 'ALL' && !isset($filterLabels[$statusFilter])) {
    $statusFilter = 'ALL';
}
$typeFilter = trim((string)($_GET['type'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$sort = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($_GET['sort'] ?? 'id'))) ?: 'id';
$dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));

$conn = customer_core_db();
$requests = [];
$statusCounts = [];
$dbOk = $conn !== false;

if ($dbOk) {
    $requests = m360_reception_list_requests($conn, $statusFilter === 'ALL' ? null : $statusFilter, 500);
    $statusCounts = m360_reception_status_counts($conn);
}

if ($typeFilter !== '') {
    $requests = array_values(array_filter($requests, static function (array $row) use ($typeFilter): bool {
        return strcasecmp((string)($row['request_type'] ?? ''), $typeFilter) === 0;
    }));
}
if ($q !== '') {
    $requests = array_values(array_filter($requests, static function (array $row) use ($q): bool {
        $hay = strtolower(implode(' ', [
            (string)($row['online_request_id'] ?? ''),
            (string)($row['mobile'] ?? ''),
            (string)($row['erp_customer_name'] ?? ''),
            (string)($row['customer_name'] ?? ''),
            (string)($row['vehicle_plate'] ?? ''),
            (string)($row['vehicle_brand'] ?? ''),
            (string)($row['vehicle_model'] ?? ''),
        ]));
        return str_contains($hay, strtolower($q));
    }));
}

$sortMap = [
    'id' => 'online_request_id',
    'date' => 'created_at',
    'created_at' => 'created_at',
    'online_request_id' => 'online_request_id',
];
$sortKey = $sortMap[$sort] ?? 'online_request_id';
$requests = m360_rui_sort_rows($requests, $sortKey, $dir);
$pageInfo = m360_rui_paginate($requests, $page, 10);
$rows = $pageInfo['rows'];

$typeOptions = [];
foreach ($requests as $r) {
    $t = trim((string)($r['request_type'] ?? ''));
    if ($t !== '') {
        $typeOptions[$t] = m360_rui_label($t);
    }
}
ksort($typeOptions);

$keep = m360_rui_query_keep(['page' => null], ['page']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>درخواست‌های آنلاین — MOGHARE360</title>
<?php m360_rui_css_links(); ?>
</head>
<body class="c360-body">
<div class="c360-wrap">
<?php m360_rui_render_head('درخواست‌های آنلاین', 'درخواست‌های آنلاین', 'پیگیری و تکمیل درخواست‌های آنلاین پذیرش'); ?>

<?php if (!$dbOk): ?>
  <div class="c360-flash err">اتصال به پایگاه داده برقرار نشد.</div>
<?php else: ?>
  <section class="c360-panel">
    <form class="c360-filter-bar" method="get">
      <input type="hidden" name="sort" value="<?= m360_rui_h($sort) ?>">
      <input type="hidden" name="dir" value="<?= m360_rui_h($dir) ?>">
      <label>وضعیت
        <select name="status">
          <?php foreach ($filterLabels as $code => $label): ?>
            <option value="<?= m360_rui_h($code) ?>" <?= $statusFilter === $code ? 'selected' : '' ?>><?= m360_rui_h($label) ?> (<?= (int)($statusCounts[$code] ?? 0) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>نوع
        <select name="type">
          <option value="">همه</option>
          <?php foreach ($typeOptions as $code => $label): ?>
            <option value="<?= m360_rui_h($code) ?>" <?= $typeFilter === $code ? 'selected' : '' ?>><?= m360_rui_h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>جستجو
        <input type="text" name="q" value="<?= m360_rui_h($q) ?>" placeholder="موبایل، نام، پلاک، شناسه">
      </label>
      <label>&nbsp;<button class="c360-btn primary" type="submit">اعمال</button></label>
    </form>

    <div class="c360-sort-links">
      <a class="c360-btn" href="?<?= m360_rui_h(m360_rui_query_keep(['sort' => 'id', 'dir' => $sort === 'id' && $dir === 'desc' ? 'asc' : 'desc', 'page' => 1])) ?>">مرتب‌سازی شناسه</a>
      <a class="c360-btn" href="?<?= m360_rui_h(m360_rui_query_keep(['sort' => 'date', 'dir' => $sort === 'date' && $dir === 'desc' ? 'asc' : 'desc', 'page' => 1])) ?>">مرتب‌سازی تاریخ</a>
    </div>

    <?php if ($rows === []): ?>
      <p class="c360-muted">موردی یافت نشد.</p>
    <?php else: ?>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead>
            <tr>
              <th style="width:70px">شناسه</th>
              <th style="width:130px">تاریخ</th>
              <th style="width:110px">موبایل</th>
              <th>مشتری</th>
              <th>خودرو / پلاک</th>
              <th style="width:110px">مراجعه</th>
              <th style="width:140px">نوع</th>
              <th style="width:120px">وضعیت</th>
              <th class="c360-action-cell">اقدام</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row):
              $rid = (int)($row['online_request_id'] ?? 0);
              $customerLabel = trim((string)($row['erp_customer_name'] ?? ''));
              if ($customerLabel === '') {
                  $customerLabel = (string)($row['customer_name'] ?? '');
              }
              $vehicleLabel = trim((string)($row['vehicle_brand'] ?? '') . ' ' . (string)($row['vehicle_model'] ?? ''));
              $plate = (string)($row['vehicle_plate'] ?? '');
              $vehicleCell = trim($vehicleLabel . ($plate !== '' ? ' — ' . $plate : ''));
              $status = strtoupper((string)($row['request_status'] ?? ''));
              $typeRaw = (string)($row['request_type'] ?? '');
          ?>
            <tr>
              <td title="<?= m360_rui_h((string)$rid) ?>"><?= $rid ?></td>
              <td title="<?= m360_rui_h(m360_rui_jalali_date((string)($row['created_at'] ?? ''))) ?>"><?= m360_rui_h(m360_rui_jalali_date((string)($row['created_at'] ?? ''))) ?></td>
              <td title="<?= m360_rui_h((string)($row['mobile'] ?? '')) ?>"><?= m360_rui_h((string)($row['mobile'] ?? '')) ?></td>
              <td title="<?= m360_rui_h($customerLabel) ?>"><?= m360_rui_h($customerLabel !== '' ? $customerLabel : '—') ?></td>
              <td title="<?= m360_rui_h($vehicleCell) ?>"><?= m360_rui_h($vehicleCell !== '' ? $vehicleCell : '—') ?></td>
              <td title="<?= m360_rui_h(m360_rui_jalali_date((string)($row['visit_date'] ?? ''), false)) ?>"><?= m360_rui_h(m360_rui_jalali_date((string)($row['visit_date'] ?? ''), false)) ?></td>
              <td title="<?= m360_rui_h(m360_rui_label($typeRaw)) ?>"><?= m360_rui_h(m360_rui_label($typeRaw)) ?></td>
              <td><span class="c360-status"><?= m360_rui_h(m360_rui_label($status)) ?></span></td>
              <td class="c360-action-cell">
                <a class="c360-btn" href="erp-reception-intake-file.php?online_request_id=<?= $rid ?>">ورود</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php m360_rui_render_pagination($pageInfo, $keep); ?>
    <?php endif; ?>
  </section>
<?php endif; ?>
</div>
</body>
</html>
