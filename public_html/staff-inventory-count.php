<?php
declare(strict_types=1);

require_once __DIR__ . '/inventory-controlled-helpers.php';

inv_require_inventory_access('count');

$pdo = inv_pdo();

function count_get(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}

function count_post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function count_staff_id(): ?int
{
    foreach (['staff_id', 'StaffID', 'user_id', 'UserID'] as $key) {
        if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
            return (int)$_SESSION[$key];
        }
    }
    return null;
}

function count_username(): string
{
    foreach (['username', 'staff_username', 'StaffUsername', 'user_name', 'name'] as $key) {
        if (!empty($_SESSION[$key])) {
            return (string)$_SESSION[$key];
        }
    }
    return 'staff';
}

$errors = [];
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (function_exists('checkCsrf')) {
            checkCsrf();
        }

        $lineId = (int)count_post('line_id');
        $countedRaw = count_post('counted_quantity');
        $note = count_post('count_note');

        if ($lineId <= 0) {
            $errors[] = 'ردیف شمارش معتبر نیست.';
        }

        if ($countedRaw === '' || !is_numeric($countedRaw)) {
            $errors[] = 'تعداد شمارش‌شده باید عددی باشد.';
        }

        $countedQuantity = (float)$countedRaw;

        if ($countedQuantity < 0) {
            $errors[] = 'تعداد شمارش‌شده نمی‌تواند منفی باشد.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("
                UPDATE inventory_counting_lines
                SET
                    counted_quantity = ?,
                    counted_by_staff_id = ?,
                    counted_by_username = ?,
                    counted_at = NOW(),
                    count_note = ?,
                    line_status = 'شمارش شده'
                WHERE id = ?
                  AND line_status IN ('شمارش نشده', 'نیازمند بازبینی', 'شمارش شده')
            ");

            $stmt->execute([
                $countedQuantity,
                count_staff_id(),
                count_username(),
                $note !== '' ? $note : null,
                $lineId
            ]);

            if ($stmt->rowCount() <= 0) {
                $errors[] = 'ردیف پیدا نشد یا قابل ویرایش نیست.';
            } else {
                $success = 'شمارش با موفقیت ثبت شد.';
            }
        }
    }

    $warehouse = count_get('warehouse');
    $location = count_get('location');
    $status = count_get('status');
    $q = count_get('q');

    $sessionStmt = $pdo->query("
        SELECT id, session_title
        FROM inventory_counting_sessions
        WHERE status = 'Open'
        ORDER BY id DESC
        LIMIT 1
    ");
    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new RuntimeException('هیچ جلسه انبارگردانی باز وجود ندارد.');
    }

    $where = ['l.session_id = ?'];
    $params = [(int)$session['id']];

    if ($warehouse !== '') {
        $where[] = 'l.warehouse_name = ?';
        $params[] = $warehouse;
    }

    if ($location !== '') {
        $where[] = 'l.location_code = ?';
        $params[] = $location;
    }

    if ($status !== '') {
        $where[] = 'l.line_status = ?';
        $params[] = $status;
    }

    if ($q !== '') {
        $where[] = '(l.item_name LIKE ? OR l.technical_code LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    $sql = "
        SELECT
            l.*
        FROM inventory_counting_lines l
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
            l.warehouse_name,
            l.location_code,
            l.item_name
        LIMIT 300
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $warehouses = $pdo->query("
        SELECT DISTINCT warehouse_name
        FROM inventory_counting_lines
        WHERE warehouse_name IS NOT NULL AND warehouse_name <> ''
        ORDER BY warehouse_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    $locations = $pdo->query("
        SELECT DISTINCT location_code
        FROM inventory_counting_lines
        WHERE location_code IS NOT NULL AND location_code <> ''
        ORDER BY location_code
    ")->fetchAll(PDO::FETCH_COLUMN);

    $summary = $pdo->prepare("
        SELECT line_status, COUNT(*) AS cnt
        FROM inventory_counting_lines
        WHERE session_id = ?
        GROUP BY line_status
        ORDER BY cnt DESC
    ");
    $summary->execute([(int)$session['id']]);
    $summaryRows = $summary->fetchAll(PDO::FETCH_ASSOC);

    renderHeader('انبارگردانی', 'ثبت شمارش واقعی کالا');
    renderFlashes();

} catch (Throwable $e) {
    showErrorPage('خطا در بارگذاری انبارگردانی.', $e->getMessage());
    exit;
}
?>

<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>انبارگردانی موجودی اولیه</h2>
    <p class="muted"><?= e((string)$session['session_title']) ?></p>

    <?php if ($success): ?>
      <div class="notice good"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="notice bad">
        <?php foreach ($errors as $error): ?>
          <div><?= e($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="summary-grid">
      <?php foreach ($summaryRows as $s): ?>
        <div>
          <strong><?= e((string)$s['line_status']) ?></strong>
          <span class="num"><?= e((string)$s['cnt']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="get" class="form-grid">
      <label>جستجو نام کالا / کد فنی
        <input name="q" value="<?= e($q) ?>" placeholder="مثلاً INA یا 1215">
      </label>

      <label>انبار
        <select name="warehouse">
          <option value="">همه</option>
          <?php foreach ($warehouses as $w): ?>
            <option value="<?= e((string)$w) ?>" <?= $warehouse === (string)$w ? 'selected' : '' ?>>
              <?= e((string)$w) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>لوکیشن
        <select name="location">
          <option value="">همه</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= e((string)$loc) ?>" <?= $location === (string)$loc ? 'selected' : '' ?>>
              <?= e((string)$loc) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>وضعیت
        <select name="status">
          <option value="">همه</option>
          <?php foreach (['شمارش نشده', 'شمارش شده', 'نیازمند بازبینی'] as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
              <?= e($st) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="action-row wide">
        <button class="btn primary" type="submit">فیلتر</button>
        <a class="btn ghost" href="staff-inventory-count.php">پاک کردن فیلتر</a>
        <a class="btn secondary" href="staff-inventory.php">بازگشت به انبار</a>
      </div>
    </form>
  </section>

  <section class="panel-card wide-card">
    <h2>لیست شمارش</h2>

    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>عکس</th>
            <th>کالا</th>
            <th>کد فنی</th>
            <th>گروه</th>
            <th>زیرگروه</th>
            <th>واحد</th>
            <th>سیستمی</th>
            <th>شمارش</th>
            <th>اختلاف</th>
            <th>لوکیشن</th>
            <th>وضعیت</th>
            <th>ثبت</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $img = trim((string)($r['item_photo_path'] ?? ''));
              $diff = ($r['counted_quantity'] === null || $r['counted_quantity'] === '')
                  ? ''
                  : (string)$r['difference_quantity'];
            ?>
            <tr>
              <td>
                <?php if ($img !== ''): ?>
                  <img src="<?= e($img) ?>" alt="" style="width:58px;height:58px;object-fit:cover;border-radius:12px">
                <?php else: ?>
                  <span class="muted">بدون عکس</span>
                <?php endif; ?>
              </td>

              <td><?= e((string)$r['item_name']) ?></td>
              <td class="num"><?= e((string)$r['technical_code']) ?></td>
              <td><?= e((string)$r['main_category']) ?></td>
              <td><?= e((string)$r['sub_category']) ?></td>
              <td><?= e((string)$r['unit_name']) ?></td>
              <td class="num"><?= e((string)$r['system_quantity']) ?></td>

              <td>
                <form method="post" class="inline-count-form">
                  <?php if (function_exists('csrfField')) echo csrfField(); ?>
                  <input type="hidden" name="line_id" value="<?= e((string)$r['id']) ?>">
                  <input
                    name="counted_quantity"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value="<?= e((string)($r['counted_quantity'] ?? '')) ?>"
                    style="width:90px"
                  >
                  <input
                    name="count_note"
                    placeholder="توضیح"
                    value="<?= e((string)($r['count_note'] ?? '')) ?>"
                    style="width:120px"
                  >
              </td>

              <td class="num"><?= e($diff) ?></td>
              <td><?= e((string)$r['location_code']) ?></td>
              <td><?= e((string)$r['line_status']) ?></td>

              <td>
                  <button class="btn primary" type="submit">ذخیره</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$rows): ?>
            <tr>
              <td colspan="12">داده‌ای یافت نشد.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<?php renderFooter(); ?>