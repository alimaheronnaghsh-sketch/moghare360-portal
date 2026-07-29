<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_warehouses_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT * FROM dbo.inv360_warehouses WHERE is_active=1 ORDER BY warehouse_id DESC', []);
    foreach ($rows as &$r) {
        $r['WarehouseID'] = $r['warehouse_id'];
        $r['WarehouseName'] = $r['warehouse_name'];
        $r['WarehouseCode'] = $r['warehouse_code'];
    }
    unset($r);
    return $rows;
}

function inv360_warehouse_create($conn, string $code, string $name, string $type, int $userId): array
{
    if (trim($code) === '' || trim($name) === '') {
        return ['ok' => false, 'message' => 'کد و نام انبار الزامی است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_warehouses (warehouse_code, warehouse_name, warehouse_type, company_name, branch_name, created_by)
         VALUES (?,?,?,N\'MOGHAREH\',N\'مرکزی\',?)',
        [trim($code), trim($name), $type !== '' ? $type : 'main', $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت انبار ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 warehouse_id FROM dbo.inv360_warehouses WHERE warehouse_code=?', [trim($code)]) ?? 0);
    inv360_audit($conn, 'WAREHOUSE', (string)$id, 'CREATED', $name, $userId);
    return ['ok' => true, 'message' => 'انبار ثبت شد.', 'warehouse_id' => $id];
}

function inv360_locations_list($conn, ?int $warehouseId = null): array
{
    $rows = $warehouseId
        ? inv360_rows($conn, 'SELECT * FROM dbo.inv360_locations WHERE warehouse_id=? AND is_active=1 ORDER BY location_id DESC', [$warehouseId])
        : inv360_rows($conn, 'SELECT TOP 200 * FROM dbo.inv360_locations WHERE is_active=1 ORDER BY location_id DESC', []);
    foreach ($rows as &$r) {
        $r['LocationID'] = $r['location_id'];
        $r['LocationCode'] = $r['location_code'];
        $r['WarehouseID'] = $r['warehouse_id'];
    }
    unset($r);
    return $rows;
}

function inv360_location_create($conn, int $warehouseId, string $bin, string $name, int $userId): array
{
    if ($warehouseId < 1 || trim($bin) === '') {
        return ['ok' => false, 'message' => 'انبار و Bin الزامی است.'];
    }
    $code = 'BIN-' . $warehouseId . '-' . preg_replace('/\s+/', '', $bin);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_locations (warehouse_id, location_code, location_name, zone_code, aisle_code, rack_code, shelf_code, bin_code, created_by)
         VALUES (?,?,?,N\'Z1\',N\'A1\',N\'R1\',N\'S1\',?,?)',
        [$warehouseId, $code, $name !== '' ? $name : $bin, $bin, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت مکان ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 location_id FROM dbo.inv360_locations WHERE location_code=?', [$code]) ?? 0);
    inv360_audit($conn, 'LOCATION', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'مکان/Bin ثبت شد.', 'location_id' => $id];
}
