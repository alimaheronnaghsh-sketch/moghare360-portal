<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_warehouses_list($conn): array
{
    return inv360_rows($conn, 'SELECT * FROM dbo.Warehouses WHERE IsActive = 1 ORDER BY WarehouseID DESC', []);
}

function inv360_warehouse_create($conn, string $code, string $name, string $type, int $userId): array
{
    if (trim($code) === '' || trim($name) === '') {
        return ['ok' => false, 'message' => 'کد و نام انبار الزامی است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Warehouses (WarehouseCode, WarehouseName, IsActive, CreatedAt, CreatedByUserID, WarehouseType, CompanyName, BranchName)
         VALUES (?, ?, 1, SYSUTCDATETIME(), ?, ?, N\'MOGHAREH\', N\'مرکزی\')',
        [trim($code), trim($name), $userId, $type !== '' ? $type : 'main']
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت انبار ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 WarehouseID FROM dbo.Warehouses WHERE WarehouseCode=?', [trim($code)]) ?? 0);
    inv360_audit($conn, 'WAREHOUSE', (string)$id, 'CREATED', $name, $userId);
    return ['ok' => true, 'message' => 'انبار ثبت شد.', 'warehouse_id' => $id];
}

function inv360_locations_list($conn, ?int $warehouseId = null): array
{
    if ($warehouseId) {
        return inv360_rows($conn, 'SELECT * FROM dbo.WarehouseLocations WHERE WarehouseID = ? AND IsActive = 1 ORDER BY LocationID DESC', [$warehouseId]);
    }
    return inv360_rows($conn, 'SELECT TOP 200 * FROM dbo.WarehouseLocations WHERE IsActive = 1 ORDER BY LocationID DESC', []);
}

function inv360_location_create($conn, int $warehouseId, string $bin, string $name, int $userId): array
{
    if ($warehouseId < 1 || trim($bin) === '') {
        return ['ok' => false, 'message' => 'انبار و Bin الزامی است.'];
    }
    $code = 'BIN-' . $warehouseId . '-' . preg_replace('/\s+/', '', $bin);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.WarehouseLocations
            (WarehouseID, ZoneCode, RowCode, RackCode, ShelfCode, BoxCode, LocationCode, IsActive, CreatedAt, CreatedByUserID, BinCode, LocationName, AisleCode)
         VALUES (?, N\'Z1\', N\'R1\', N\'K1\', N\'S1\', ?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, N\'A1\')',
        [$warehouseId, mb_substr($bin, 0, 10), $code, $userId, $bin, $name !== '' ? $name : $bin]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت مکان ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 LocationID FROM dbo.WarehouseLocations WHERE LocationCode=?', [$code]) ?? 0);
    inv360_audit($conn, 'LOCATION', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'مکان/Bin ثبت شد.', 'location_id' => $id];
}
