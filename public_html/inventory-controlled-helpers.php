<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/m360-staff-home-helper.php';
require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $message, string $type = 'ok'): void
    {
        erp_auth_context_start();
        if (!isset($_SESSION['inventory_flash']) || !is_array($_SESSION['inventory_flash'])) {
            $_SESSION['inventory_flash'] = [];
        }
        $_SESSION['inventory_flash'][] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('renderFlashes')) {
    function renderFlashes(): void
    {
        erp_auth_context_start();
        $messages = $_SESSION['inventory_flash'] ?? [];
        unset($_SESSION['inventory_flash']);
        if (!is_array($messages)) {
            return;
        }
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $type = (string)($message['type'] ?? 'ok');
            echo '<div class="inventory-flash inventory-flash-' . e($type) . '">' . e((string)($message['message'] ?? '')) . '</div>';
        }
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        return '<input type="hidden" name="erp_csrf_token" value="' . e(erp_csrf_get_or_create_token('inventory')) . '">';
    }
}

if (!function_exists('checkCsrf')) {
    function checkCsrf(): void
    {
        $token = trim((string)($_POST['erp_csrf_token'] ?? $_POST['csrf_token'] ?? ''));
        if (!erp_csrf_validate_token('inventory', $token)) {
            http_response_code(403);
            echo 'ERP security validation failed.';
            exit;
        }
    }
}

if (!function_exists('renderHeader')) {
    function renderHeader(string $title, string $subtitle = ''): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<meta name="robots" content="noindex,nofollow"><title>' . e($title) . '</title>';
        echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
        echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
        echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
        echo '<link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">';
        echo '<link rel="stylesheet" href="assets/css/m360-operational-shell.css">';
        echo '<link rel="stylesheet" href="assets/style.css">';
        echo '</head><body class="m360-rtl inventory-page"><div class="m360-wrap inventory-wrap">';
        echo '<header class="inventory-page-header"><a href="erp-staff-home.php">داشبورد پرسنل</a><h1>' . e($title) . '</h1>';
        if ($subtitle !== '') {
            echo '<p>' . e($subtitle) . '</p>';
        }
        echo '</header>';
    }
}

if (!function_exists('renderFooter')) {
    function renderFooter(): void
    {
        echo '</div></body></html>';
    }
}

if (!function_exists('showErrorPage')) {
    function showErrorPage(string $message, string $detail = ''): void
    {
        renderHeader('پیام سیستم', 'MOGHARE360 Inventory');
        echo '<main class="auth-wrap wide-auth inventory-page"><section class="card form-card">';
        echo '<h2>' . e($message) . '</h2>';
        if ($detail !== '') {
            echo '<p class="muted">' . e($detail) . '</p>';
        }
        echo '<p><a class="btn" href="erp-staff-home.php">بازگشت به داشبورد پرسنل</a></p>';
        echo '</section></main>';
        renderFooter();
        exit;
    }
}

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

function inv_auth_connection()
{
    return inventory_db();
}

function inv_require_staff_session(): int
{
    m360_staff_home_require_session();

    $userId = erp_auth_context_session_user_id();
    if ($userId === null || $userId <= 0) {
        redirect('staff-login.php');
    }

    return $userId;
}

function inv_current_staff(): array
{
    static $staff = null;
    if (is_array($staff)) {
        return $staff;
    }

    $userId = inv_require_staff_session();
    $connection = inv_auth_connection();
    $companyId = (int)($_SESSION['erp_company_id'] ?? 0);
    $username = (string)($_SESSION['erp_username'] ?? '');
    $fullName = $username;
    $roleCode = 'UNKNOWN';
    $roleKeys = [];
    $permissionKeys = [];
    $isSystemOwner = false;

    if ($connection !== false) {
        $userRows = customer_core_fetch_rows(
            $connection,
            'SELECT TOP 1 username, full_name, is_system_owner FROM dbo.core_users WHERE user_id = ?',
            [$userId]
        );
        if (($userRows[0] ?? null) !== null) {
            $username = (string)($userRows[0]['username'] ?? $username);
            $fullName = m360_staff_home_text_from_odbc((string)($userRows[0]['full_name'] ?? $fullName));
            $isSystemOwner = (string)($userRows[0]['is_system_owner'] ?? '0') === '1';
        }

        $roleCode = m360_staff_home_resolve_role_code($connection, $userId, $companyId);
        $roles = erp_auth_current_roles($connection, $userId);
        $permissions = erp_auth_current_permissions($connection, $userId);
        $roleKeys = erp_auth_context_role_keys($roles);
        $permissionKeys = erp_auth_context_permission_keys($permissions);
    }

    $staff = [
        'id' => $userId,
        'user_id' => $userId,
        'username' => $username,
        'full_name' => $fullName,
        'role_name' => $roleCode,
        'role_code' => $roleCode,
        'role_keys' => $roleKeys,
        'permission_keys' => $permissionKeys,
        'is_system_owner' => $isSystemOwner,
        'is_master_admin' => $isSystemOwner || in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN'], true),
    ];

    return $staff;
}

function inv_has_any_permission(array $staff, array $prefixes): bool
{
    $permissions = $staff['permission_keys'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    foreach ($permissions as $permission) {
        $permission = trim((string)$permission);
        foreach ($prefixes as $prefix) {
            $prefix = trim((string)$prefix);
            if ($prefix !== '' && ($permission === $prefix || str_starts_with($permission, $prefix . '.'))) {
                return true;
            }
        }
    }

    return false;
}

function inv_is_inventory_role(array $staff): bool
{
    $roleCode = strtoupper((string)($staff['role_code'] ?? ''));
    $roleKeys = $staff['role_keys'] ?? [];

    return in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'PARTS'], true)
        || in_array('owner', $roleKeys, true)
        || in_array('system_admin', $roleKeys, true)
        || in_array('inventory_staff', $roleKeys, true)
        || inv_has_any_permission($staff, ['inventory', 'stock', 'parts', 'purchase']);
}

function inv_can_access_inventory_action(array $staff, string $action): bool
{
    if (!empty($staff['is_master_admin'])) {
        return true;
    }

    if (!inv_is_inventory_role($staff)) {
        return false;
    }

    $action = trim($action) !== '' ? trim($action) : 'view';

    return in_array($action, ['view', 'search', 'reports', 'valuation', 'receipt', 'new', 'inbound', 'outbound', 'count'], true);
}

function inv_access_denied(): void
{
    http_response_code(403);
    showErrorPage('شما به این بخش از انبار دسترسی ندارید.');
}

function inv_db()
{
    static $connection = null;
    if (is_resource($connection)) {
        return $connection;
    }

    $connection = inv_auth_connection();
    if ($connection === false || !is_resource($connection)) {
        throw new RuntimeException('ارتباط با پایگاه داده انبار برقرار نشد.');
    }

    return $connection;
}

function inv_fetch_all(string $sql, array $params = []): array
{
    $statement = inventory_execute(inv_db(), $sql, $params);
    if ($statement === false) {
        throw new RuntimeException('خواندن اطلاعات انبار انجام نشد.');
    }

    $rows = [];
    while (($row = odbc_fetch_array($statement)) !== false) {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower((string)$key)] = $value;
        }
        $rows[] = $normalized;
    }

    return $rows;
}

function inv_fetch_one(string $sql, array $params = []): ?array
{
    $rows = inv_fetch_all($sql, $params);
    return $rows[0] ?? null;
}

function inv_scalar(string $sql, array $params = []): mixed
{
    $statement = inventory_execute(inv_db(), $sql, $params);
    if ($statement === false || odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = odbc_result($statement, 1);
    return $value === false ? null : $value;
}

function inv_execute(string $sql, array $params = [])
{
    $statement = inventory_execute(inv_db(), $sql, $params);
    if ($statement === false) {
        throw new RuntimeException('ثبت اطلاعات انبار انجام نشد.');
    }

    return $statement;
}

function inv_insert_id(): int
{
    return (int)(inv_scalar('SELECT CAST(SCOPE_IDENTITY() AS BIGINT)') ?? 0);
}

function inv_current_stock(int $itemId, ?int $locationId = null): float
{
    $where = 'inventory_item_id = ? AND movement_status = ?';
    $params = [$itemId, 'RECORDED'];
    if ($locationId !== null) {
        $where .= ' AND stock_location_id = ?';
        $params[] = $locationId;
    }

    $value = inv_scalar(
        "SELECT ISNULL(SUM(CASE
             WHEN movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(movement_qty)
             WHEN movement_type = N'COUNT_ADJUSTMENT' THEN movement_qty
             ELSE ABS(movement_qty)
         END), 0)
         FROM dbo.erp_inventory_stock_movements
         WHERE {$where}",
        $params
    );

    return (float)($value ?? 0);
}

function inv_record_movement(
    int $itemId,
    int $locationId,
    string $type,
    float $quantity,
    string $note = '',
    ?int $jobcardId = null
): int {
    $type = strtoupper(trim($type));
    $quantityValid = $type === 'COUNT_ADJUSTMENT' ? abs($quantity) > 0.00001 : $quantity > 0;
    if ($itemId < 1 || $locationId < 1 || !$quantityValid) {
        throw new RuntimeException('اطلاعات گردش کالا معتبر نیست.');
    }

    if (in_array($type, ['OUTBOUND', 'JOB_CARD_CONSUMPTION'], true)
        && inv_current_stock($itemId, $locationId) < $quantity) {
        throw new RuntimeException('موجودی این محل برای خروج کافی نیست.');
    }

    if ($jobcardId !== null && $jobcardId > 0) {
        $exists = (int)(inv_scalar('SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?', [$jobcardId]) ?? 0);
        if ($exists !== 1) {
            throw new RuntimeException('JobCard مرتبط معتبر نیست.');
        }
        $note = trim($note . ' | JOB_CARD:' . $jobcardId);
    }

    inv_execute(
        'INSERT INTO dbo.erp_inventory_stock_movements
         (inventory_item_id, stock_location_id, movement_type, movement_qty, movement_status, movement_note, created_by, source_ip, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $itemId,
            $locationId,
            $type,
            $quantity,
            'RECORDED',
            $note !== '' ? $note : null,
            inv_staff_username(),
            inventory_client_ip(),
            inventory_user_agent(),
        ]
    );

    $id = inv_insert_id();
    if ($id < 1) {
        throw new RuntimeException('شناسه گردش کالا ایجاد نشد.');
    }

    return $id;
}

function inv_staff_id(): ?int
{
    $staff = inv_current_staff();
    return isset($staff['id']) ? (int)$staff['id'] : null;
}

function inv_staff_username(): string
{
    $staff = inv_current_staff();
    return (string)($staff['username'] ?? '');
}

function inv_staff_role(): string
{
    $staff = inv_current_staff();
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
    $staff = inv_current_staff();
    $username = (string)($staff['username'] ?? '');

    if (inv_is_no_money_data_entry()) {
        return false;
    }

    return !empty($staff['is_master_admin'])
        || $username === 'warehouse_price'
        || inv_has_any_permission($staff, ['inventory.purchase', 'purchase']);
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

function inv_require_inventory_access(string $action = 'view'): array
{
    $staff = inv_current_staff();

    if (!inv_can_access_inventory_action($staff, $action)) {
        inv_access_denied();
    }

    return $staff;
}

function inv_warehouses(): array
{
    return inv_fetch_all("
        SELECT stock_location_id AS id, location_name AS name, location_code
        FROM dbo.erp_stock_locations
        WHERE is_active = 1
        ORDER BY location_name, stock_location_id
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
