<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/inv360-bootstrap.php';
inv360_require_login();

$conn = inv360_db();

$itemTypes = inv360_i1r2a_item_types_new();
$itemStatuses = [
    'active' => 'فعال',
    'inactive' => 'غیرفعال',
    'stopped_purchase' => 'توقف خرید',
    'blocked_sale' => 'توقف فروش',
    'obsolete' => 'منسوخ',
];
$lifecycleLabels = [
    'DRAFT' => inv360_lifecycle_fa('DRAFT'),
    'NEEDS_COMPLETION' => inv360_lifecycle_fa('NEEDS_COMPLETION'),
    'PENDING_MANAGER_APPROVAL' => inv360_lifecycle_fa('PENDING_MANAGER_APPROVAL'),
    'ACTIVE' => inv360_lifecycle_fa('ACTIVE'),
    'REJECTED' => inv360_lifecycle_fa('REJECTED'),
    'INACTIVE' => inv360_lifecycle_fa('INACTIVE'),
];
$filterLifecycle = trim((string)($_GET['life'] ?? ''));
if ($filterLifecycle !== '' && !isset($lifecycleLabels[$filterLifecycle])) {
    $filterLifecycle = '';
}

$allowedPageSizes = [10, 15, 25];
$pageSize = (int)($_GET['ps'] ?? 15);
if (!in_array($pageSize, $allowedPageSizes, true)) {
    $pageSize = 15;
}
$page = max(1, (int)($_GET['page'] ?? 1));

$q = trim((string)($_GET['q'] ?? ''));
$filterType = trim((string)($_GET['type'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));
$filterBrand = trim((string)($_GET['brand'] ?? ''));
$filterCategory = trim((string)($_GET['category'] ?? ''));

if ($filterType !== '' && !isset($itemTypes[$filterType])) {
    $filterType = '';
}
if ($filterStatus !== '' && !isset($itemStatuses[$filterStatus])) {
    $filterStatus = '';
}

$where = ['i.is_deleted = 0'];
$params = [];

if ($q !== '') {
    $norm = inv360_normalize_search($q);
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $likeNorm = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $norm) . '%';
    $where[] = '(
        i.item_name_fa LIKE ? OR i.item_name_en LIKE ? OR i.common_name LIKE ?
        OR i.workshop_code LIKE ? OR i.technical_code LIKE ? OR i.item_code LIKE ?
        OR i.part_number LIKE ? OR i.oem_code LIKE ? OR i.barcode LIKE ?
        OR i.alternative_codes LIKE ? OR i.brand LIKE ? OR i.manufacturer LIKE ?
        OR i.category_name LIKE ? OR i.subcategory LIKE ? OR i.family_name LIKE ?
        OR i.search_norm LIKE ?
    )';
    $params = array_merge($params, [
        $like, $like, $like,
        $like, $like, $like,
        $like, $like, $like,
        $like, $like, $like,
        $like, $like, $like,
        $likeNorm,
    ]);
}

if ($filterType !== '') {
    $where[] = 'i.item_type = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '') {
    $where[] = 'i.item_status = ?';
    $params[] = $filterStatus;
}
if ($filterLifecycle !== '') {
    $where[] = 'i.lifecycle_status = ?';
    $params[] = $filterLifecycle;
}
if ($filterBrand !== '') {
    $where[] = 'i.brand = ?';
    $params[] = $filterBrand;
}
if ($filterCategory !== '') {
    $where[] = 'i.category_name = ?';
    $params[] = $filterCategory;
}

$whereSql = implode(' AND ', $where);

$total = (int)(inv360_scalar(
    $conn,
    "SELECT COUNT(1) FROM dbo.inv360_items i WHERE {$whereSql}",
    $params
) ?? 0);

$totalPages = max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $pageSize;

$listSql = "SELECT i.item_id, i.item_name_fa, i.item_name_en, i.common_name, i.item_type,
                   i.category_name, i.subcategory, i.brand, i.part_number, i.item_code,
                   i.workshop_code, i.barcode, i.item_status, i.updated_at, i.created_at,
                   i.lifecycle_status, i.completeness_status, i.completeness_score, i.market_grade
            FROM dbo.inv360_items i
            WHERE {$whereSql}
            ORDER BY i.item_id DESC
            OFFSET {$offset} ROWS FETCH NEXT {$pageSize} ROWS ONLY";
$rows = inv360_rows($conn, $listSql, $params);

$brandOptions = inv360_rows(
    $conn,
    "SELECT DISTINCT TOP 200 brand AS v FROM dbo.inv360_items
     WHERE is_deleted = 0 AND brand IS NOT NULL AND LTRIM(RTRIM(brand)) <> N''
     ORDER BY brand",
    []
);
$categoryOptions = inv360_rows(
    $conn,
    "SELECT DISTINCT TOP 200 category_name AS v FROM dbo.inv360_items
     WHERE is_deleted = 0 AND category_name IS NOT NULL AND LTRIM(RTRIM(category_name)) <> N''
     ORDER BY category_name",
    []
);

$queryBase = [
    'q' => $q,
    'type' => $filterType,
    'status' => $filterStatus,
    'life' => $filterLifecycle,
    'brand' => $filterBrand,
    'category' => $filterCategory,
    'ps' => (string)$pageSize,
];

$buildUrl = static function (array $extra) use ($queryBase): string {
    $q = array_merge($queryBase, $extra);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    $qs = http_build_query($q);

    return 'items.php' . ($qs !== '' ? '?' . $qs : '');
};

inv360_layout_start('کالاها', 'items.php');
?>
<style>
.inv360-items {
  --g:#1f6b4a; --g2:#2f8a60; --line:rgba(31,107,74,.28); --muted:#6b8578;
}
.inv360-items .items-sub { margin:-.15rem 0 .85rem; color:var(--muted); font-size:.9375rem; }
.inv360-items .items-toolbar {
  display:flex; flex-wrap:wrap; gap:.65rem; align-items:center; justify-content:space-between; margin-bottom:.85rem;
}
.inv360-items .items-filters {
  display:grid; grid-template-columns:minmax(12rem,1.6fr) repeat(5,minmax(7rem,1fr)) auto;
  gap:.5rem; margin-bottom:.85rem; align-items:end;
}
.inv360-items .life-badge {
  display:inline-block; max-width:100%; padding:.12rem .4rem; border-radius:999px; font-size:.75rem;
  background:#eef6f1; color:#184e37; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:middle;
}
.inv360-items .life-badge.warn { background:#fff3cd; color:#7a5b00; }
.inv360-items .life-badge.pend { background:#e7f1ff; color:#1a4a8a; }
.inv360-items .comp-line {
  font-size:.75rem; color:#6b8578; margin-top:.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.inv360-items .items-filters label { display:flex; flex-direction:column; gap:.25rem; font-size:.8125rem; color:#2c4a3c; font-weight:600; }
.inv360-items .items-filters input,
.inv360-items .items-filters select {
  border:1px solid #c9d8cf; border-radius:8px; padding:.5rem .6rem; font:inherit; background:#fff;
}
.inv360-items .items-meta { color:var(--muted); font-size:.85rem; margin:.35rem 0 .75rem; }
.inv360-items .table-scroll {
  overflow:auto; border:1px solid var(--line); border-radius:12px; background:#fff;
  -webkit-overflow-scrolling:touch;
}
.inv360-items table.m360-table {
  width:100%; min-width:980px; border-collapse:collapse; font-size:.8125rem; table-layout:fixed;
}
.inv360-items table.m360-table th,
.inv360-items table.m360-table td {
  padding:.45rem .55rem; border-bottom:1px solid #e4eee8; text-align:right; vertical-align:middle;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.35;
}
.inv360-items table.m360-table th { background:#eef6f1; color:#184e37; font-size:.8125rem; font-weight:700; }
.inv360-items table.m360-table td.cell-ltr {
  direction:ltr; unicode-bidi:plaintext; font-variant-numeric:tabular-nums; text-align:left;
}
.inv360-items table.m360-table td.cell-date {
  white-space:nowrap; font-variant-numeric:tabular-nums;
}
.inv360-items .row-actions {
  display:inline-flex; align-items:center; gap:.45rem; flex-wrap:nowrap; white-space:nowrap;
}
.inv360-items .row-actions a,
.inv360-items .row-actions button {
  background:transparent; border:0; color:#1f6b4a; font:inherit; font-size:.8125rem; font-weight:600;
  cursor:pointer; padding:0; text-decoration:none; white-space:nowrap;
}
.inv360-items .pager { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; justify-content:space-between; margin-top:.85rem; }
.inv360-items .pager-links { display:flex; gap:.35rem; flex-wrap:wrap; }
.inv360-items .pager-links a,
.inv360-items .pager-links span {
  display:inline-block; min-width:2rem; text-align:center; padding:.35rem .55rem; border-radius:8px;
  border:1px solid var(--line); text-decoration:none; color:#184e37; font-size:.86rem;
}
.inv360-items .pager-links span.is-current { background:#1f6b4a; color:#fff; border-color:#1f6b4a; }
.inv360-items .btn-primary {
  background:#1f6b4a; color:#fff; border:0; border-radius:8px; padding:.6rem 1.1rem; font:inherit; font-weight:700; cursor:pointer;
}
.inv360-items .btn-secondary {
  background:#fff; color:#1f6b4a; border:1px solid #1f6b4a; border-radius:8px; padding:.55rem .9rem; font:inherit; font-weight:600; cursor:pointer; text-decoration:none;
}

/* Right drawer */
.inv360-drawer-overlay {
  position:fixed; inset:0; background:rgba(8,18,14,.52); z-index:1200; display:none;
}
.inv360-drawer-overlay.is-open { display:block; }
.inv360-drawer {
  position:fixed; top:0; bottom:0; right:0; width:min(740px,100vw); max-width:100vw;
  background:#0f1f18; color:#e8f5ef; z-index:1201; transform:translateX(105%);
  transition:transform .22s ease; display:flex; flex-direction:column; box-shadow:-8px 0 28px rgba(0,0,0,.35);
}
.inv360-drawer.is-open { transform:translateX(0); }
.inv360-drawer-header {
  display:flex; align-items:center; justify-content:space-between; gap:.75rem;
  padding:.85rem 1rem; border-bottom:1px solid rgba(31,107,74,.35);
}
.inv360-drawer-header h2 { margin:0; font-size:1.05rem; color:#dff3e8; }
.inv360-drawer-close {
  border:0; background:transparent; color:#b8d0c4; font-size:1.4rem; line-height:1; cursor:pointer; padding:.2rem .45rem;
}
.inv360-drawer-frame-wrap { flex:1; min-height:0; }
.inv360-drawer-frame-wrap iframe {
  width:100%; height:100%; border:0; background:#0f1f18; display:block;
}
body.inv360-drawer-lock { overflow:hidden; }
@media (max-width:900px) {
  .inv360-items .items-filters { grid-template-columns:1fr 1fr; }
  .inv360-drawer { width:100vw; }
}
@media (max-width:560px) {
  .inv360-items .items-filters { grid-template-columns:1fr; }
}
</style>

<section class="inv360-items" id="inv360_items_root">
  <p class="items-sub">جست‌وجو، مشاهده و مدیریت شناسنامه کالاها</p>

  <div class="items-toolbar">
    <button type="button" class="btn-primary" id="inv360_btn_create" data-return-focus="1">ثبت کالا</button>
    <a class="btn-secondary" href="item-search.php">جستجوی سریع</a>
  </div>

  <form method="get" action="items.php" class="items-filters" id="inv360_items_filters">
    <label>جست‌وجو
      <input type="search" name="q" value="<?= inv360_h($q) ?>" placeholder="نام، کد، برند، بارکد…" autocomplete="off">
    </label>
    <label>نوع کالا
      <select name="type">
        <option value="">همه</option>
        <?php foreach ($itemTypes as $code => $label): ?>
          <option value="<?= inv360_h($code) ?>"<?= $filterType === $code ? ' selected' : '' ?>><?= inv360_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>وضعیت
      <select name="status">
        <option value="">همه</option>
        <?php foreach ($itemStatuses as $code => $label): ?>
          <option value="<?= inv360_h($code) ?>"<?= $filterStatus === $code ? ' selected' : '' ?>><?= inv360_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>چرخه حیات
      <select name="life">
        <option value="">همه</option>
        <?php foreach ($lifecycleLabels as $code => $label): ?>
          <option value="<?= inv360_h($code) ?>"<?= $filterLifecycle === $code ? ' selected' : '' ?>><?= inv360_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>برند
      <select name="brand">
        <option value="">همه</option>
        <?php foreach ($brandOptions as $opt):
            $bv = trim((string)($opt['v'] ?? ''));
            if ($bv === '') {
                continue;
            }
            ?>
          <option value="<?= inv360_h($bv) ?>"<?= $filterBrand === $bv ? ' selected' : '' ?>><?= inv360_h($bv) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>دسته‌بندی فعلی
      <select name="category">
        <option value="">همه</option>
        <?php foreach ($categoryOptions as $opt):
            $cv = trim((string)($opt['v'] ?? ''));
            if ($cv === '') {
                continue;
            }
            ?>
          <option value="<?= inv360_h($cv) ?>"<?= $filterCategory === $cv ? ' selected' : '' ?>><?= inv360_h($cv) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تعداد در صفحه
      <select name="ps" onchange="this.form.submit()">
        <?php foreach ($allowedPageSizes as $ps): ?>
          <option value="<?= (int)$ps ?>"<?= $pageSize === $ps ? ' selected' : '' ?>><?= (int)$ps ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="btn-secondary">اعمال</button>
  </form>

  <p class="items-meta">
    <?= (int)$total ?> کالا · صفحه <?= (int)$page ?> از <?= (int)$totalPages ?>
  </p>

  <div class="table-scroll">
    <table class="m360-table">
      <thead>
        <tr>
          <th>نام فارسی</th>
          <th>نام انگلیسی / رایج</th>
          <th>نوع</th>
          <th>دسته‌بندی</th>
          <th>برند</th>
          <th>کد اصلی</th>
          <th>بارکد</th>
          <th>وضعیت</th>
          <th>چرخه / تکمیل</th>
          <th>آخرین تغییر</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows === []): ?>
        <tr><td colspan="11">موردی یافت نشد.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r):
            $id = (int)($r['item_id'] ?? 0);
            $nameFa = (string)($r['item_name_fa'] ?? '');
            $nameAlt = trim((string)($r['item_name_en'] ?? ''));
            if ($nameAlt === '') {
                $nameAlt = trim((string)($r['common_name'] ?? ''));
            }
            $typeCode = (string)($r['item_type'] ?? '');
            $statusCode = (string)($r['item_status'] ?? '');
            $cat = trim((string)($r['category_name'] ?? ''));
            if ($cat === '') {
                $cat = trim((string)($r['subcategory'] ?? ''));
            }
            $primary = trim((string)($r['part_number'] ?? ''));
            if ($primary === '') {
                $primary = trim((string)($r['item_code'] ?? ''));
            }
            if ($primary === '') {
                $primary = trim((string)($r['workshop_code'] ?? ''));
            }
            $updatedRaw = (string)($r['updated_at'] ?? $r['created_at'] ?? '');
            $updatedFa = inv360_jalali_datetime($updatedRaw !== '' ? $updatedRaw : null);
            $life = (string)($r['lifecycle_status'] ?? 'ACTIVE');
            $comp = (string)($r['completeness_status'] ?? '');
            $compScore = $r['completeness_score'] ?? null;
            $compBadge = $comp !== '' ? inv360_completeness_badge($comp, $compScore) : '';
            $lifeCls = 'life-badge';
            if ($life === 'NEEDS_COMPLETION' || $comp === 'NEEDS_COMPLETION' || $comp === 'FATAL_MISSING_IDENTITY') {
                $lifeCls .= ' warn';
            } elseif ($life === 'PENDING_MANAGER_APPROVAL') {
                $lifeCls .= ' pend';
            }
            ?>
          <tr>
            <td title="<?= inv360_h($nameFa) ?>"><?= inv360_h($nameFa) ?></td>
            <td title="<?= inv360_h($nameAlt !== '' ? $nameAlt : '') ?>"><?= inv360_h($nameAlt !== '' ? $nameAlt : '—') ?></td>
            <td title="<?= inv360_h($itemTypes[$typeCode] ?? $typeCode) ?>"><?= inv360_h($itemTypes[$typeCode] ?? $typeCode) ?></td>
            <td title="<?= inv360_h($cat !== '' ? $cat : '') ?>"><?= inv360_h($cat !== '' ? $cat : '—') ?></td>
            <td title="<?= inv360_h((string)($r['brand'] ?? '')) ?>"><?= inv360_h((string)($r['brand'] ?? '') !== '' ? (string)$r['brand'] : '—') ?></td>
            <td class="cell-ltr" title="<?= inv360_h($primary !== '' ? $primary : '') ?>"><?= inv360_h($primary !== '' ? $primary : '—') ?></td>
            <td class="cell-ltr" title="<?= inv360_h((string)($r['barcode'] ?? '')) ?>"><?= inv360_h((string)($r['barcode'] ?? '') !== '' ? (string)$r['barcode'] : '—') ?></td>
            <td><?= inv360_h($itemStatuses[$statusCode] ?? $statusCode) ?></td>
            <td title="<?= inv360_h(inv360_lifecycle_fa($life) . ($compBadge !== '' ? ' · ' . $compBadge : '')) ?>">
              <span class="<?= inv360_h($lifeCls) ?>"><?= inv360_h(inv360_lifecycle_fa($life)) ?></span>
              <?php if ($compBadge !== ''): ?>
                <div class="comp-line"><?= inv360_h($compBadge) ?></div>
              <?php endif; ?>
            </td>
            <td class="cell-date" title="<?= inv360_h($updatedFa) ?>"><?= inv360_h($updatedFa) ?></td>
            <td class="row-actions">
              <a href="item-view.php?id=<?= $id ?>">مشاهده</a>
              <button type="button" class="inv360-edit-btn" data-id="<?= $id ?>">ویرایش</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pager">
    <div class="pager-links">
      <?php if ($page > 1): ?>
        <a href="<?= inv360_h($buildUrl(['page' => (string)($page - 1)])) ?>">قبلی</a>
      <?php endif; ?>
      <?php
      $from = max(1, $page - 2);
      $to = min($totalPages, $page + 2);
      for ($p = $from; $p <= $to; $p++):
          if ($p === $page): ?>
            <span class="is-current" aria-current="page"><?= (int)$p ?></span>
          <?php else: ?>
            <a href="<?= inv360_h($buildUrl(['page' => (string)$p])) ?>"><?= (int)$p ?></a>
          <?php endif;
      endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?= inv360_h($buildUrl(['page' => (string)($page + 1)])) ?>">بعدی</a>
      <?php endif; ?>
    </div>
    <div class="items-meta" style="margin:0">صفحه <?= (int)$page ?> / <?= (int)$totalPages ?></div>
  </div>
</section>

<div class="inv360-drawer-overlay" id="inv360_drawer_overlay" hidden></div>
<aside class="inv360-drawer" id="inv360_drawer" role="dialog" aria-modal="true" aria-labelledby="inv360_drawer_title" hidden>
  <div class="inv360-drawer-header">
    <h2 id="inv360_drawer_title">ثبت کالا</h2>
    <button type="button" class="inv360-drawer-close" id="inv360_drawer_close" aria-label="بستن">×</button>
  </div>
  <div class="inv360-drawer-frame-wrap">
    <iframe id="inv360_drawer_frame" title="فرم کالا" src="about:blank"></iframe>
  </div>
</aside>

<script>
(function () {
  var overlay = document.getElementById('inv360_drawer_overlay');
  var drawer = document.getElementById('inv360_drawer');
  var frame = document.getElementById('inv360_drawer_frame');
  var title = document.getElementById('inv360_drawer_title');
  var btnCreate = document.getElementById('inv360_btn_create');
  var btnClose = document.getElementById('inv360_drawer_close');
  var returnFocusEl = null;
  var formPath = window.location.pathname.replace(/[^\/]+$/, 'item-form.php');

  function allowedFormUrl(url) {
    try {
      var u = new URL(url, window.location.origin);
      if (u.origin !== window.location.origin) return false;
      if (u.pathname !== formPath) return false;
      return true;
    } catch (e) {
      return false;
    }
  }

  function openDrawer(url, heading, focusEl) {
    if (!allowedFormUrl(url)) return;
    returnFocusEl = focusEl || btnCreate;
    if (title) title.textContent = heading || 'ثبت کالا';
    if (frame) frame.src = url;
    if (overlay) { overlay.hidden = false; overlay.classList.add('is-open'); }
    if (drawer) { drawer.hidden = false; drawer.classList.add('is-open'); }
    document.body.classList.add('inv360-drawer-lock');
    if (btnClose) btnClose.focus();
  }

  function closeDrawer() {
    if (overlay) { overlay.classList.remove('is-open'); overlay.hidden = true; }
    if (drawer) { drawer.classList.remove('is-open'); drawer.hidden = true; }
    if (frame) frame.src = 'about:blank';
    document.body.classList.remove('inv360-drawer-lock');
    if (returnFocusEl && typeof returnFocusEl.focus === 'function') {
      try { returnFocusEl.focus(); } catch (e) {}
    }
  }

  function requestClose() {
    try {
      if (frame && frame.contentWindow && frame.src && frame.src.indexOf('item-form.php') !== -1) {
        frame.contentWindow.postMessage({ source: 'moghare360-inv360-items', type: 'inv360-request-close' }, window.location.origin);
        return;
      }
    } catch (e) {}
    closeDrawer();
  }

  if (btnCreate) {
    btnCreate.addEventListener('click', function () {
      openDrawer(window.location.origin + formPath + '?drawer=1', 'ثبت کالا', btnCreate);
    });
  }
  document.querySelectorAll('.inv360-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (!id) return;
      openDrawer(window.location.origin + formPath + '?drawer=1&id=' + id, 'ویرایش کالا', btn);
    });
  });
  if (btnClose) btnClose.addEventListener('click', requestClose);
  if (overlay) overlay.addEventListener('click', requestClose);

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && drawer && drawer.classList.contains('is-open')) {
      requestClose();
    }
  });

  window.addEventListener('message', function (ev) {
    if (ev.origin !== window.location.origin) return;
    var data = ev.data || {};
    if (!data || data.source !== 'moghare360-inv360-item-form') return;
    if (data.type === 'inv360-item-saved' && data.ok) {
      closeDrawer();
      window.location.reload();
      return;
    }
    if (data.type === 'inv360-item-close') {
      closeDrawer();
    }
  });
})();
</script>
<?php
inv360_layout_end();
