<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('inv_has_text')) {
    function inv_has_text(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        if (function_exists('mb_strpos')) {
            return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
        }

        return strpos($haystack, $needle) !== false;
    }
}

function inv_pdo(): PDO
{
    return getPdo();
}

function inv_fetch_all(string $sql, array $params = []): array
{
    $stmt = inv_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function inv_fetch_one(string $sql, array $params = []): ?array
{
    $stmt = inv_pdo()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function inv_staff_id(): ?int
{
    $staff = currentStaffUser();
    return isset($staff['id']) ? (int)$staff['id'] : null;
}

function inv_staff_username(): string
{
    $staff = currentStaffUser();
    return (string)($staff['username'] ?? '');
}

function inv_staff_role(): string
{
    $staff = currentStaffUser();
    return (string)($staff['role_name'] ?? '');
}

function inv_data_entry_users(): array
{
    return ['yazdani', 'salimi', 'jafar', 'soheil', 'omid'];
}

function inv_is_no_money_data_entry(): bool
{
    $username = inv_staff_username();
    $role = inv_staff_role();

    return in_array($username, inv_data_entry_users(), true)
        || inv_has_text($role, 'بدون مبلغ')
        || inv_has_text($role, 'بدون ریال');
}

function inv_can_purchase_price(): bool
{
    $staff = currentStaffUser();
    $username = (string)($staff['username'] ?? '');
    $role = (string)($staff['role_name'] ?? '');

    if (inv_is_no_money_data_entry()) {
        return false;
    }

    return isMasterAdmin($staff)
        || $username === 'warehouse_price'
        || inv_has_text($role, 'قیمت خرید');
}

function inv_can_inbound_only(): bool
{
    return in_array(inv_staff_username(), ['inbound_receipt1', 'inbound_receipt2', 'inbound_receipt3'], true);
}

function inv_can_new_item_only(): bool
{
    return in_array(inv_staff_username(), inv_data_entry_users(), true)
        || inv_is_no_money_data_entry();
}

function inv_require_inventory_access(string $action = 'view'): void
{
    $staff = requireStaffLogin();
    $username = (string)($staff['username'] ?? '');

    if (isMasterAdmin($staff)) {
        return;
    }

    if ($username === 'warehouse_price') {
        return;
    }

    if (in_array($username, inv_data_entry_users(), true)) {
        if (in_array($action, ['new', 'receipt', 'view'], true)) {
            return;
        }
    }

    if ($action === 'new' && inv_can_new_item_only()) {
        return;
    }

    if ($action === 'receipt' && inv_can_new_item_only()) {
        return;
    }

    if ($action === 'view' && inv_can_new_item_only()) {
        return;
    }

    if ($action === 'inbound' && inv_can_inbound_only()) {
        return;
    }

    if ($action === 'view' && $username === 'manager') {
        return;
    }

    showErrorPage('شما به این بخش از انبار دسترسی ندارید.');
}

function inv_warehouses(): array
{
    return inv_fetch_all("
        SELECT id, warehouse_name AS name
        FROM inventory_warehouses
        WHERE is_active = 1
        ORDER BY sort_order, warehouse_name
    ");
}

function inv_generate_receipt_number(): string
{
    $prefix = 'INV-' . date('Ymd') . '-';

    $row = inv_fetch_one(
        "SELECT COUNT(*) AS c FROM inventory_items_staging WHERE receipt_number LIKE ?",
        [$prefix . '%']
    );

    $next = ((int)($row['c'] ?? 0)) + 1;

    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function inv_upload_file(string $field, string $dir): ?string
{
    if (
        empty($_FILES[$field]) ||
        !is_array($_FILES[$field]) ||
        (int)$_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ((int)$_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('آپلود فایل انجام نشد.');
    }

    if ((int)$_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('حجم فایل باید حداکثر 5 مگابایت باشد.');
    }

    $ext = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('فرمت فایل باید jpg، jpeg، png یا webp باشد.');
    }

    $targetDir = __DIR__ . '/' . trim($dir, '/') . '/' . date('Y') . '/' . date('m');

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $name = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $targetDir . '/' . $name;

    if (!move_uploaded_file((string)$_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('ذخیره فایل انجام نشد.');
    }

    return trim($dir, '/') . '/' . date('Y') . '/' . date('m') . '/' . $name;
}

function inv_location_code(
    string $warehouseName,
    string $floor,
    string $row,
    string $rowSide,
    string $rack,
    string $section
): string {
    return $warehouseName . '-' . $floor . '-R' . $row . '-' . $rowSide . '-Q' . $rack . '-B' . $section;
}

function inv_render_location_selectors(array $selected = []): void
{
    $warehouses = inv_warehouses();
    $floors = ['-2', '-1', 'G', '1', '2', '3'];
    $rows = range(1, 20);
    $rowSides = ['راست', 'چپ'];
    $racks = ['زمین', '1', '2', '3', '4', '5'];
    $sections = range(1, 10);

    echo '<div class="field"><label>انبار *</label><select name="warehouse_id" required>';
    echo '<option value="">انتخاب کنید</option>';

    foreach ($warehouses as $w) {
        $sel = ((string)($selected['warehouse_id'] ?? '') === (string)$w['id']) ? ' selected' : '';
        echo '<option value="' . e((string)$w['id']) . '"' . $sel . '>' . e((string)$w['name']) . '</option>';
    }

    echo '</select></div>';

    $sets = [
        'floor_code' => ['label' => 'طبقه *', 'items' => $floors],
        'row_code' => ['label' => 'ردیف *', 'items' => $rows],
        'row_side' => ['label' => 'سمت ردیف *', 'items' => $rowSides],
        'rack_code' => ['label' => 'قفسه *', 'items' => $racks],
        'section_code' => ['label' => 'بخش *', 'items' => $sections],
    ];

    foreach ($sets as $name => $cfg) {
        echo '<div class="field"><label>' . e($cfg['label']) . '</label>';
        echo '<select name="' . e($name) . '" class="num" required>';
        echo '<option value="">انتخاب کنید</option>';

        foreach ($cfg['items'] as $item) {
            $value = (string)$item;
            $sel = ((string)($selected[$name] ?? '') === $value) ? ' selected' : '';
            echo '<option value="' . e($value) . '"' . $sel . '>' . e($value) . '</option>';
        }

        echo '</select></div>';
    }
}