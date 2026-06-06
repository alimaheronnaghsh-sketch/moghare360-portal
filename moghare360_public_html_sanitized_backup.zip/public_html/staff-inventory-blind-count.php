<?php
declare(strict_types=1);

require_once __DIR__ . '/inventory-controlled-helpers.php';

inv_require_inventory_access('count');

$pdo = inv_pdo();

function bc_post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function bc_get(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}

function bc_staff_username(): string
{
    foreach (['username', 'staff_username', 'StaffUsername', 'user_name', 'name'] as $key) {
        if (!empty($_SESSION[$key])) {
            return (string)$_SESSION[$key];
        }
    }

    return 'staff';
}

function bc_upload_item_photo(): ?string
{
    if (empty($_FILES['item_photo']) || !is_array($_FILES['item_photo'])) {
        return null;
    }

    if ((int)($_FILES['item_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$_FILES['item_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('آپلود عکس کالا ناموفق بود.');
    }

    $tmp = (string)$_FILES['item_photo']['tmp_name'];
    $size = (int)$_FILES['item_photo']['size'];

    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException('حجم عکس باید کمتر از 5MB باشد.');
    }

    $mime = mime_content_type($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('فرمت عکس باید JPG یا PNG یا WEBP باشد.');
    }

    $dir = __DIR__ . '/uploads/blind-count/' . date('Y/m');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $dir . '/' . $fileName;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('ذخیره عکس روی سرور انجام نشد.');
    }

    return 'uploads/blind-count/' . date('Y/m') . '/' . $fileName;
}

$errors = [];
$success = '';

try {
    $sessionStmt = $pdo->query("
        SELECT id, session_title, warehouse_scope
        FROM inventory_blind_count_sessions
        WHERE status = 'Open'
          AND approval_status = 'Open'
        ORDER BY id DESC
        LIMIT 1
    ");

    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new RuntimeException('هیچ جلسه انبارگردانی کور باز وجود ندارد.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (function_exists('checkCsrf')) {
            checkCsrf();
        }

        $itemName = bc_post('item_name');
        $technicalCode = bc_post('technical_code');
        $oemCode = bc_post('oem_code');
        $internalCode = bc_post('internal_code');
        $mainCategory = bc_post('main_category');
        $subCategory = bc_post('sub_category');
        $unitName = bc_post('unit_name', 'عدد');
        $countedQuantityRaw = bc_post('counted_quantity');
        $warehouseName = bc_post('warehouse_name');
        $locationCode = bc_post('location_code');
        $countNote = bc_post('count_note');

        if ($itemName === '') {
            $errors[] = 'نام کالا اجباری است.';
        }

        if ($countedQuantityRaw === '' || !is_numeric($countedQuantityRaw)) {
            $errors[] = 'تعداد باید عددی باشد.';
        }

        $countedQuantity = (float)$countedQuantityRaw;

        if ($countedQuantity <= 0) {
            $errors[] = 'تعداد باید بزرگ‌تر از صفر باشد.';
        }

        if ($warehouseName === '') {
            $errors[] = 'انبار اجباری است.';
        }

        if ($unitName === '') {
            $unitName = 'عدد';
        }

        $photoPath = null;

        if (!$errors) {
            $photoPath = bc_upload_item_photo();

            $stmt = $pdo->prepare("
                INSERT INTO inventory_blind_count_lines
                (
                    session_id,
                    item_name,
                    technical_code,
                    oem_code,
                    internal_code,
                    main_category,
                    sub_category,
                    unit_name,
                    counted_quantity,
                    warehouse_name,
                    location_code,
                    item_photo_path,
                    count_note,
                    counted_by_username,
                    counted_at,
                    review_status,
                    finance_status,
                    final_status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    'در انتظار بررسی',
                    'در انتظار مالی',
                    'Pending'
                )
            ");

            $stmt->execute([
                (int)$session['id'],
                $itemName,
                $technicalCode !== '' ? $technicalCode : null,
                $oemCode !== '' ? $oemCode : null,
                $internalCode !== '' ? $internalCode : null,
                $mainCategory !== '' ? $mainCategory : null,
                $subCategory !== '' ? $subCategory : null,
                $unitName,
                $countedQuantity,
                $warehouseName,
                $locationCode !== '' ? $locationCode : null,
                $photoPath,
                $countNote !== '' ? $countNote : null,
                bc_staff_username()
            ]);

            $success = 'کالا با موفقیت در انبارگردانی ثبت شد.';
        }
    }

    $mainCategories = $pdo->query("
        SELECT DISTINCT main_category
        FROM inventory_items_staging
        WHERE main_category IS NOT NULL AND main_category <> ''
        ORDER BY main_category
    ")->fetchAll(PDO::FETCH_COLUMN);

    $subCategories = $pdo->query("
        SELECT DISTINCT sub_category
        FROM inventory_items_staging
        WHERE sub_category IS NOT NULL AND sub_category <> ''
        ORDER BY sub_category
    ")->fetchAll(PDO::FETCH_COLUMN);

    $warehouses = $pdo->query("
        SELECT DISTINCT warehouse_name
        FROM inventory_warehouses
        WHERE warehouse_name IS NOT NULL AND warehouse_name <> ''
        ORDER BY warehouse_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!$warehouses) {
        $warehouses = $pdo->query("
            SELECT DISTINCT warehouse_name
            FROM inventory_items_staging
            WHERE warehouse_name IS NOT NULL AND warehouse_name <> ''
            ORDER BY warehouse_name
        ")->fetchAll(PDO::FETCH_COLUMN);
    }

    $units = $pdo->query("
        SELECT DISTINCT unit_name
        FROM inventory_units
        WHERE unit_name IS NOT NULL AND unit_name <> ''
        ORDER BY unit_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!$units) {
        $units = ['عدد', 'لیتر', 'دست', 'جفت', 'متر', 'کیلوگرم'];
    }

    $summaryStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_lines,
            SUM(counted_quantity) AS total_quantity
        FROM inventory_blind_count_lines
        WHERE session_id = ?
    ");
    $summaryStmt->execute([(int)$session['id']]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    $recentStmt = $pdo->prepare("
        SELECT
            id,
            item_name,
            technical_code,
            main_category,
            sub_category,
            unit_name,
            counted_quantity,
            warehouse_name,
            location_code,
            counted_by_username,
            counted_at,
            review_status
        FROM inventory_blind_count_lines
        WHERE session_id = ?
        ORDER BY id DESC
        LIMIT 30
    ");
    $recentStmt->execute([(int)$session['id']]);
    $recentRows = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    renderHeader('انبارگردانی کور', 'ثبت آزاد موجودی واقعی انبار');
    renderFlashes();

} catch (Throwable $e) {
    showErrorPage('خطا در صفحه انبارگردانی کور.', $e->getMessage());
    exit;
}
?>

<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>ثبت آزاد کالای انبار</h2>
    <p class="muted">
      <?= e((string)$session['session_title']) ?> |
      محدوده: <?= e((string)$session['warehouse_scope']) ?>
    </p>

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
      <div>
        <strong>تعداد ردیف ثبت‌شده</strong>
        <span class="num"><?= e((string)($summary['total_lines'] ?? 0)) ?></span>
      </div>
      <div>
        <strong>جمع تعداد ثبت‌شده</strong>
        <span class="num"><?= e((string)($summary['total_quantity'] ?? 0)) ?></span>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form-grid">
      <?php if (function_exists('csrfField')) echo csrfField(); ?>

      <label>نام کالا *
        <input name="item_name" required placeholder="مثلاً تسمه سفت کن کامل BMW/INA/M54">
      </label>

      <label>کد فنی
        <input name="technical_code" placeholder="مثلاً 533001510">
      </label>

      <label>کد OEM
        <input name="oem_code" placeholder="اختیاری">
      </label>

      <label>کد داخلی
        <input name="internal_code" placeholder="اختیاری">
      </label>

      <label>گروه اصلی
        <select name="main_category">
          <option value="">انتخاب نشده</option>
          <?php foreach ($mainCategories as $cat): ?>
            <option value="<?= e((string)$cat) ?>"><?= e((string)$cat) ?></option>
          <?php endforeach; ?>
          <option value="نامشخص / نیازمند بررسی">نامشخص / نیازمند بررسی</option>
        </select>
      </label>

      <label>زیرگروه
        <select name="sub_category">
          <option value="">انتخاب نشده</option>
          <?php foreach ($subCategories as $sub): ?>
            <option value="<?= e((string)$sub) ?>"><?= e((string)$sub) ?></option>
          <?php endforeach; ?>
          <option value="سایر / نیازمند بررسی">سایر / نیازمند بررسی</option>
        </select>
      </label>

      <label>واحد *
        <select name="unit_name" required>
          <?php foreach ($units as $unit): ?>
            <option value="<?= e((string)$unit) ?>" <?= (string)$unit === 'عدد' ? 'selected' : '' ?>>
              <?= e((string)$unit) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>تعداد واقعی *
        <input name="counted_quantity" type="number" min="0.01" step="0.01" required placeholder="مثلاً 2">
      </label>

      <label>انبار *
        <select name="warehouse_name" required>
          <option value="">انتخاب انبار</option>
          <?php foreach ($warehouses as $warehouse): ?>
            <option value="<?= e((string)$warehouse) ?>"><?= e((string)$warehouse) ?></option>
          <?php endforeach; ?>
          <option value="نامشخص / نیازمند بررسی">نامشخص / نیازمند بررسی</option>
        </select>
      </label>

      <label>لوکیشن
        <input name="location_code" placeholder="مثلاً انبار مجموعه-G-R5-راست-Q3-B2">
      </label>

      <label>عکس کالا
        <input name="item_photo" type="file" accept="image/jpeg,image/png,image/webp">
      </label>

      <label>توضیح
        <input name="count_note" placeholder="مثلاً بدون کارتن، نیازمند بررسی فنی">
      </label>

      <div class="action-row wide">
        <button class="btn primary" type="submit">ثبت کالا در انبارگردانی</button>
        <a class="btn secondary" href="staff-inventory.php">بازگشت به انبار</a>
      </div>
    </form>
  </section>

  <section class="panel-card wide-card">
    <h2>آخرین کالاهای ثبت‌شده</h2>

    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>نام کالا</th>
            <th>کد فنی</th>
            <th>گروه</th>
            <th>زیرگروه</th>
            <th>واحد</th>
            <th>تعداد</th>
            <th>انبار</th>
            <th>لوکیشن</th>
            <th>ثبت‌کننده</th>
            <th>زمان</th>
            <th>وضعیت</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentRows as $r): ?>
            <tr>
              <td class="num"><?= e((string)$r['id']) ?></td>
              <td><?= e((string)$r['item_name']) ?></td>
              <td class="num"><?= e((string)($r['technical_code'] ?? '')) ?></td>
              <td><?= e((string)($r['main_category'] ?? '')) ?></td>
              <td><?= e((string)($r['sub_category'] ?? '')) ?></td>
              <td><?= e((string)$r['unit_name']) ?></td>
              <td class="num"><?= e((string)$r['counted_quantity']) ?></td>
              <td><?= e((string)$r['warehouse_name']) ?></td>
              <td><?= e((string)($r['location_code'] ?? '')) ?></td>
              <td><?= e((string)($r['counted_by_username'] ?? '')) ?></td>
              <td class="num"><?= e((string)$r['counted_at']) ?></td>
              <td><?= e((string)$r['review_status']) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$recentRows): ?>
            <tr>
              <td colspan="12">هنوز کالایی ثبت نشده است.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<?php renderFooter(); ?>