<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Inventory & Purchase Helper
 */

const ERP_PHASE4_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE4_PLACEHOLDER_ACTIONS = [
    'inventory.catalog.view' => 'placeholder_inventory_catalog_view',
    'inventory.catalog.write' => 'placeholder_inventory_catalog_write',
    'inventory.stock.view' => 'placeholder_inventory_stock_view',
    'inventory.reserve.create' => 'placeholder_inventory_reserve_create',
    'inventory.purchase.create' => 'placeholder_inventory_purchase_create',
    'inventory.purchase.update' => 'placeholder_inventory_purchase_update',
    'inventory.supplier.view' => 'placeholder_inventory_supplier_view',
    'inventory.supplier.write' => 'placeholder_inventory_supplier_write',
    'inventory.movement.view' => 'placeholder_inventory_movement_view',
];

function inventory_require_helper(string $fileName): void
{
    foreach ([__DIR__, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes'] as $base) {
        $path = $base . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
    throw new RuntimeException('Required ERP file not found: ' . $fileName);
}

inventory_require_helper('erp-auth-context.php');
inventory_require_helper('erp-permission-guard.php');
inventory_require_helper('erp-csrf.php');

if (!function_exists('erp_csrf_input')) {
    function erp_csrf_input(string $purpose): string
    {
        return '<input type="hidden" name="erp_csrf_token" value="' .
            htmlspecialchars(erp_csrf_create_token($purpose), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('erp_csrf_require_valid')) {
    function erp_csrf_require_valid(string $purpose, ?string $token): void
    {
        try {
            erp_csrf_require_valid_token($purpose, (string)($token ?? ''));
        } catch (Throwable) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'ERP security validation failed.';
            exit;
        }
    }
}

function inventory_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function inventory_post_string(string $k): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function inventory_get_string(string $k): string { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : ''; }
function inventory_post_int(string $k): ?int { $r = inventory_post_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function inventory_get_int(string $k): ?int { $r = inventory_get_string($k); return $r !== '' && ctype_digit($r) ? (int)$r : null; }
function inventory_post_float(string $k): ?float { $r = inventory_post_string($k); return $r !== '' && is_numeric($r) ? (float)$r : null; }

function inventory_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function inventory_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function inventory_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function inventory_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function inventory_db()
{
    if (!extension_loaded('odbc')) {
        return false;
    }
    try {
        return erp_auth_create_local_odbc_connection();
    } catch (Throwable) {
        return false;
    }
}

function inventory_table_exists($c, string $t): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $t]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function inventory_column_exists($c, string $t, string $col): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $t, $col]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function inventory_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) {
        return false;
    }
    return $s;
}

function inventory_scalar($c, string $sql, array $p = []): ?string
{
    $s = inventory_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function inventory_fetch_rows($c, string $sql, array $p = []): array
{
    $s = inventory_execute($c, $sql, $p);
    if ($s === false) {
        return [];
    }
    $rows = [];
    while (@odbc_fetch_row($s)) {
        $row = [];
        $n = @odbc_num_fields($s);
        if ($n === false) {
            continue;
        }
        for ($i = 1; $i <= $n; $i++) {
            $name = @odbc_field_name($s, $i);
            if ($name === false) {
                continue;
            }
            $val = @odbc_result($s, $i);
            $row[strtolower((string)$name)] = $val === false || $val === null ? '' : (string)$val;
        }
        if ($row !== []) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function inventory_scope_identity($c): ?int
{
    $v = inventory_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
    return ($v !== null && is_numeric($v)) ? (int)$v : null;
}

function inventory_purchase_table($c): ?string
{
    if (inventory_table_exists($c, 'erp_inventory_purchase_requests')) {
        return 'erp_inventory_purchase_requests';
    }
    if (inventory_table_exists($c, 'erp_purchase_requests') && inventory_column_exists($c, 'erp_purchase_requests', 'request_code')) {
        return 'erp_purchase_requests';
    }
    return null;
}

function inventory_movements_table($c): ?string
{
    if (inventory_table_exists($c, 'erp_inventory_stock_movements')) {
        return 'erp_inventory_stock_movements';
    }
    if (inventory_table_exists($c, 'erp_stock_movements') && inventory_column_exists($c, 'erp_stock_movements', 'inventory_item_id')) {
        return 'erp_stock_movements';
    }
    return null;
}

function inventory_insert_history($c, string $entityType, ?int $entityId, string $actionType, string $summary, ?string $old = null, ?string $new = null): bool
{
    if (!inventory_table_exists($c, 'erp_inventory_purchase_history')) {
        return false;
    }
    return inventory_execute(
        $c,
        'INSERT INTO dbo.erp_inventory_purchase_history (entity_type,entity_id,action_type,action_summary,old_value,new_value,created_by,source_ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)',
        [$entityType, $entityId, $actionType, $summary, $old, $new, inventory_safe_current_user(), inventory_client_ip(), inventory_user_agent()]
    ) !== false;
}

function inventory_generate_item_code(): string
{
    return 'INV-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function inventory_generate_purchase_request_code(): string
{
    return 'PR-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function inventory_generate_supplier_code(): string
{
    return 'SUP-' . date('Ymd') . '-' . random_int(1000, 9999);
}

function inventory_get_item($c, int $itemId): ?array
{
    $rows = inventory_fetch_rows($c, 'SELECT TOP 1 * FROM dbo.erp_inventory_items WHERE inventory_item_id=?', [$itemId]);
    return $rows[0] ?? null;
}

function inventory_get_stock_balance($c, int $itemId, ?int $locationId = null): ?array
{
    if ($locationId !== null) {
        $rows = inventory_fetch_rows(
            $c,
            'SELECT TOP 1 * FROM dbo.erp_stock_balances WHERE inventory_item_id=? AND stock_location_id=?',
            [$itemId, $locationId]
        );
    } else {
        $rows = inventory_fetch_rows(
            $c,
            'SELECT TOP 1 stock_balance_id, inventory_item_id, stock_location_id,
                    available_qty, reserved_qty, pending_receive_qty
             FROM dbo.erp_stock_balances WHERE inventory_item_id=? ORDER BY stock_balance_id',
            [$itemId]
        );
    }
    return $rows[0] ?? null;
}

function inventory_ensure_balance_row($c, int $itemId, ?int $locationId): ?int
{
    $existing = inventory_get_stock_balance($c, $itemId, $locationId);
    if ($existing !== null) {
        return (int)$existing['stock_balance_id'];
    }
    $ok = inventory_execute(
        $c,
        'INSERT INTO dbo.erp_stock_balances (inventory_item_id, stock_location_id, available_qty, reserved_qty, pending_receive_qty, updated_by) VALUES (?,?,0,0,0,?)',
        [$itemId, $locationId, inventory_safe_current_user()]
    );
    return $ok === false ? null : inventory_scope_identity($c);
}

function inventory_calculate_available_to_reserve($c, int $itemId): float
{
    $rows = inventory_fetch_rows(
        $c,
        'SELECT ISNULL(SUM(available_qty),0) AS av, ISNULL(SUM(reserved_qty),0) AS rs FROM dbo.erp_stock_balances WHERE inventory_item_id=?',
        [$itemId]
    );
    if ($rows === []) {
        return 0.0;
    }
    $av = (float)($rows[0]['av'] ?? '0');
    $rs = (float)($rows[0]['rs'] ?? '0');
    $result = $av - $rs;
    return $result > 0 ? $result : 0.0;
}

function inventory_create_stock_movement($c, array $data): ?int
{
    $table = inventory_movements_table($c);
    if ($table === null) {
        return null;
    }
    $ok = inventory_execute(
        $c,
        'INSERT INTO dbo.' . $table . ' (inventory_item_id, stock_location_id, reservation_id, purchase_request_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $data['inventory_item_id'] ?? null,
            $data['stock_location_id'] ?? null,
            $data['reservation_id'] ?? null,
            $data['purchase_request_id'] ?? null,
            $data['operation_case_id'] ?? null,
            $data['movement_type'],
            $data['movement_qty'],
            $data['movement_status'] ?? 'RECORDED',
            $data['movement_note'] ?? null,
            inventory_safe_current_user(),
            inventory_client_ip(),
            inventory_user_agent(),
        ]
    );
    return $ok === false ? null : inventory_scope_identity($c);
}

function inventory_stock_badge(string $available, string $reserved, string $pending, string $minStock): string
{
    $av = (float)$available;
    $rs = (float)$reserved;
    $pend = (float)$pending;
    $min = (float)$minStock;
    $free = max(0, $av - $rs);
    if ($pend > 0) {
        return 'PENDING_RECEIVE';
    }
    if ($free <= 0) {
        return 'OUT_OF_STOCK';
    }
    if ($min > 0 && $free <= $min) {
        return 'LOW_STOCK';
    }
    return 'AVAILABLE';
}

function inventory_badge_class(string $badge): string
{
    return match ($badge) {
        'AVAILABLE' => 'p1cc-badge-active',
        'LOW_STOCK' => 'p1cc-badge-duplicate',
        'OUT_OF_STOCK' => 'p1cc-error',
        'PENDING_RECEIVE' => 'p1cc-badge-new',
        default => 'p1cc-badge-draft',
    };
}

function inventory_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE4_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE4_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function inventory_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(inventory_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function inventory_render_head(string $title, bool $ro = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . inventory_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-inventory-purchase.css">';
    echo '</head><body class="m360-rtl p4ip-page"><div class="p4ip-wrap">';
    if ($ro) {
        echo '<div class="p4ip-readonly-banner">فقط خواندنی</div>';
    }
}

function inventory_render_foot(): void
{
    echo '<p class="p4ip-footer"><a href="erp-parts-catalog.php">کاتالوگ</a> · <a href="erp-stock-board.php">انبار</a> · <a href="erp-supplier-board.php">تامین‌کنندگان</a> · <a href="erp-stock-movement-history.php">تاریخچه</a></p></div></body></html>';
}

function inventory_error(string $title, string $msg): void
{
    inventory_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . inventory_h($msg) . '</p></div>';
    inventory_render_foot();
    exit;
}

function inventory_flash(string $key): string
{
    return match ($key) {
        'item_ok' => 'قلم انبار با موفقیت ثبت شد.',
        'reserve_ok' => 'رزرو قطعه با موفقیت ثبت شد.',
        'purchase_ok' => 'درخواست خرید با موفقیت ثبت شد.',
        'supplier_ok' => 'تامین‌کننده با موفقیت ثبت شد.',
        'status_ok' => 'وضعیت درخواست خرید به‌روزرسانی شد.',
        default => '',
    };
}
