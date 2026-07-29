<?php
declare(strict_types=1);

require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-auth.php';
require_once __DIR__ . '/inv360-audit.php';
require_once __DIR__ . '/inv360-validation.php';
require_once __DIR__ . '/inv360-money.php';
require_once __DIR__ . '/inv360-search.php';

function inv360_available_qty(array $bal): float
{
    $phys = (float)($bal['PhysicalQty'] ?? 0);
    $res = (float)($bal['ReservedQty'] ?? 0);
    $qua = (float)($bal['QuarantineQty'] ?? 0);
    $blk = (float)($bal['BlockedQty'] ?? 0);
    return $phys - $res - $qua - $blk;
}

function inv360_balance_get($conn, int $partId, ?int $warehouseId, ?int $locationId): array
{
    $row = inv360_one(
        $conn,
        'SELECT TOP 1 * FROM dbo.Inv360StockBalances WHERE PartID = ? AND ((? IS NULL AND WarehouseID IS NULL) OR WarehouseID = ?) AND ((? IS NULL AND LocationID IS NULL) OR LocationID = ?)',
        [$partId, $warehouseId, $warehouseId, $locationId, $locationId]
    );
    if ($row) {
        return $row;
    }
    inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360StockBalances (PartID, WarehouseID, LocationID, PhysicalQty, ReservedQty, QuarantineQty, InTransitQty, BlockedQty, ConsignmentQty)
         VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0)',
        [$partId, $warehouseId, $locationId]
    );
    return inv360_one(
        $conn,
        'SELECT TOP 1 * FROM dbo.Inv360StockBalances WHERE PartID = ? AND ((? IS NULL AND WarehouseID IS NULL) OR WarehouseID = ?) AND ((? IS NULL AND LocationID IS NULL) OR LocationID = ?)',
        [$partId, $warehouseId, $warehouseId, $locationId, $locationId]
    ) ?? [
        'PhysicalQty' => 0, 'ReservedQty' => 0, 'QuarantineQty' => 0, 'BlockedQty' => 0, 'InTransitQty' => 0, 'ConsignmentQty' => 0,
    ];
}

function inv360_balance_adjust($conn, int $partId, ?int $warehouseId, ?int $locationId, float $physDelta, float $resDelta = 0, float $quaDelta = 0, float $blkDelta = 0, float $trnDelta = 0): array
{
    $bal = inv360_balance_get($conn, $partId, $warehouseId, $locationId);
    $id = (int)($bal['BalanceID'] ?? 0);
    $phys = (float)($bal['PhysicalQty'] ?? 0) + $physDelta;
    $res = (float)($bal['ReservedQty'] ?? 0) + $resDelta;
    $qua = (float)($bal['QuarantineQty'] ?? 0) + $quaDelta;
    $blk = (float)($bal['BlockedQty'] ?? 0) + $blkDelta;
    $trn = (float)($bal['InTransitQty'] ?? 0) + $trnDelta;
    if ($phys < -0.0001 || $res < -0.0001 || $qua < -0.0001 || $blk < -0.0001) {
        return ['ok' => false, 'message' => 'موجودی منفی مجاز نیست.'];
    }
    $probe = ['PhysicalQty' => $phys, 'ReservedQty' => $res, 'QuarantineQty' => $qua, 'BlockedQty' => $blk];
    if (inv360_available_qty($probe) < -0.0001) {
        return ['ok' => false, 'message' => 'موجودی آزاد کافی نیست.'];
    }
    inv360_exec(
        $conn,
        'UPDATE dbo.Inv360StockBalances SET PhysicalQty = ?, ReservedQty = ?, QuarantineQty = ?, BlockedQty = ?, InTransitQty = ?, UpdatedAt = SYSUTCDATETIME() WHERE BalanceID = ?',
        [$phys, $res, $qua, $blk, $trn, $id]
    );
    // Keep legacy Parts.Quantity in sync; CHECK requires Quantity > 0
    $tot = (float)(inv360_scalar($conn, 'SELECT ISNULL(SUM(PhysicalQty),0) FROM dbo.Inv360StockBalances WHERE PartID = ?', [$partId]) ?? 0);
    $legacyQty = $tot > 0 ? $tot : 0.001;
    inv360_exec($conn, 'UPDATE dbo.Parts SET Quantity = ?, UpdatedAt = SYSUTCDATETIME() WHERE PartID = ?', [$legacyQty, $partId]);
    return ['ok' => true, 'message' => 'موجودی به‌روز شد.'];
}

function inv360_doc_next_no($conn, string $prefix): string
{
    return strtoupper($prefix) . '-' . gmdate('YmdHis') . '-' . random_int(100, 999);
}

function inv360_create_document($conn, array $data, int $userId): array
{
    $docNo = inv360_doc_next_no($conn, (string)($data['prefix'] ?? 'DOC'));
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360StockDocuments
            (DocNo, DocType, DocStatus, SourceWarehouseID, SourceLocationID, TargetWarehouseID, TargetLocationID, ReasonText, ReferenceType, ReferenceNo, CostCenter, Notes, CreatedByUserID)
         VALUES (?, ?, N\'draft\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $docNo,
            (string)$data['doc_type'],
            $data['source_warehouse_id'] ?? null,
            $data['source_location_id'] ?? null,
            $data['target_warehouse_id'] ?? null,
            $data['target_location_id'] ?? null,
            $data['reason'] ?? null,
            $data['reference_type'] ?? null,
            $data['reference_no'] ?? null,
            $data['cost_center'] ?? null,
            $data['notes'] ?? null,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد سند ناموفق بود.', 'document_id' => null];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 DocumentID FROM dbo.Inv360StockDocuments WHERE DocNo = ?', [$docNo]) ?? 0);
    inv360_audit($conn, 'STOCK_DOC', (string)$id, 'CREATED', $docNo, $userId);
    return ['ok' => true, 'message' => 'سند ایجاد شد.', 'document_id' => $id, 'doc_no' => $docNo];
}

function inv360_add_document_line($conn, int $docId, int $partId, float $qty, $unitCost = null, ?string $note = null): array
{
    if (is_array($unitCost)) {
        $note = isset($unitCost['note']) ? (string)$unitCost['note'] : $note;
        $unitCost = isset($unitCost['unit_cost']) ? (float)$unitCost['unit_cost'] : null;
    } elseif ($unitCost !== null) {
        $unitCost = (float)$unitCost;
    }
    if ($docId < 1 || $partId < 1 || $qty <= 0) {
        return ['ok' => false, 'message' => 'قلم سند نامعتبر است.'];
    }
    $doc = inv360_one($conn, 'SELECT TOP 1 DocStatus FROM dbo.Inv360StockDocuments WHERE DocumentID = ?', [$docId]);
    if (!$doc || strtolower((string)$doc['DocStatus']) !== 'draft') {
        return ['ok' => false, 'message' => 'فقط سند پیش‌نویس قابل ویرایش است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360StockDocumentLines (DocumentID, PartID, Qty, UnitCost, LineNote) VALUES (?, ?, ?, ?, ?)',
        [$docId, $partId, $qty, $unitCost, $note]
    );
    return $ok === false ? ['ok' => false, 'message' => 'ثبت قلم ناموفق بود.'] : ['ok' => true, 'message' => 'قلم ثبت شد.'];
}

function inv360_post_document($conn, int $docId, int $userId): array
{
    $doc = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.Inv360StockDocuments WHERE DocumentID = ?', [$docId]);
    if (!$doc) {
        return ['ok' => false, 'message' => 'سند یافت نشد.'];
    }
    $status = strtolower((string)$doc['DocStatus']);
    if ($status === 'posted') {
        return ['ok' => false, 'message' => 'سند ثبت‌شده قابل تغییر نیست.'];
    }
    if (!in_array($status, ['draft', 'approved', 'submitted'], true)) {
        return ['ok' => false, 'message' => 'وضعیت سند برای ثبت قطعی مجاز نیست.'];
    }
    $lines = inv360_rows($conn, 'SELECT * FROM dbo.Inv360StockDocumentLines WHERE DocumentID = ?', [$docId]);
    if ($lines === []) {
        return ['ok' => false, 'message' => 'سند بدون قلم است.'];
    }
    $type = strtolower((string)$doc['DocType']);
    $srcW = isset($doc['SourceWarehouseID']) ? (int)$doc['SourceWarehouseID'] : null;
    $srcL = isset($doc['SourceLocationID']) ? (int)$doc['SourceLocationID'] : null;
    $tgtW = isset($doc['TargetWarehouseID']) ? (int)$doc['TargetWarehouseID'] : null;
    $tgtL = isset($doc['TargetLocationID']) ? (int)$doc['TargetLocationID'] : null;
    if ($srcW === 0) $srcW = null;
    if ($srcL === 0) $srcL = null;
    if ($tgtW === 0) $tgtW = null;
    if ($tgtL === 0) $tgtL = null;

    $receiptTypes = ['purchase_receipt','transfer_receipt','return_from_consumption','customer_return_receipt','opening_receipt'];
    $issueTypes = ['consumption_issue','sales_issue','scrap_issue','consignment_issue','direct_delivery','supplier_return_issue','supplier_return'];
    $transferTypes = ['transfer','bin_move'];

    foreach ($lines as $line) {
        $partId = (int)$line['PartID'];
        $qty = (float)$line['Qty'];
        if (in_array($type, $receiptTypes, true) || $type === 'adjustment_increase') {
            $r = inv360_balance_adjust($conn, $partId, $tgtW ?? $srcW, $tgtL ?? $srcL, $qty);
            if (empty($r['ok'])) return $r;
        } elseif (in_array($type, $issueTypes, true) || $type === 'adjustment_decrease') {
            if ($type === 'adjustment_decrease' && trim((string)($doc['ReasonText'] ?? '')) === '') {
                return ['ok' => false, 'message' => 'تعدیل کاهش بدون دلیل مجاز نیست.'];
            }
            $r = inv360_balance_adjust($conn, $partId, $srcW ?? $tgtW, $srcL ?? $tgtL, -$qty);
            if (empty($r['ok'])) return $r;
        } elseif (in_array($type, $transferTypes, true)) {
            $r1 = inv360_balance_adjust($conn, $partId, $srcW, $srcL, -$qty);
            if (empty($r1['ok'])) return $r1;
            $r2 = inv360_balance_adjust($conn, $partId, $tgtW, $tgtL, $qty);
            if (empty($r2['ok'])) {
                inv360_balance_adjust($conn, $partId, $srcW, $srcL, $qty); // compensate
                return $r2;
            }
        } elseif ($type === 'reserve') {
            $r = inv360_balance_adjust($conn, $partId, $srcW ?? $tgtW, $srcL ?? $tgtL, 0, $qty);
            if (empty($r['ok'])) return $r;
        } elseif ($type === 'release_reserve') {
            $r = inv360_balance_adjust($conn, $partId, $srcW ?? $tgtW, $srcL ?? $tgtL, 0, -$qty);
            if (empty($r['ok'])) return $r;
        } else {
            return ['ok' => false, 'message' => 'نوع سند پشتیبانی نشده است.'];
        }
        // legacy movement trail
        inv360_exec(
            $conn,
            'INSERT INTO dbo.InventoryMovements (MovementType, PartID, Quantity, SourceLocationID, DestinationLocationID, CreatedByUserID, ApprovedByUserID, MovementDate, Notes, IsDeleted)
             VALUES (?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME(), ?, 0)',
            [$type, $partId, $qty, $srcL, $tgtL, $userId, $userId, 'DOC:' . (string)$doc['DocNo']]
        );
    }

    inv360_exec(
        $conn,
        'UPDATE dbo.Inv360StockDocuments SET DocStatus = N\'posted\', PostedByUserID = ?, PostedAt = SYSUTCDATETIME(), UpdatedAt = SYSUTCDATETIME() WHERE DocumentID = ?',
        [$userId, $docId]
    );
    inv360_audit($conn, 'STOCK_DOC', (string)$docId, 'POSTED', (string)$doc['DocNo'], $userId);
    return ['ok' => true, 'message' => 'سند با موفقیت ثبت قطعی شد.'];
}

function inv360_item_build_search_norm(array $p): string
{
    $parts = [
        $p['WorkshopCode'] ?? '',
        $p['InternalCode'] ?? '',
        $p['TechnicalCode'] ?? '',
        $p['ItemName'] ?? '',
        $p['ItemNameEn'] ?? '',
        $p['CommonName'] ?? '',
        $p['PartNumber'] ?? '',
        $p['OEMCode'] ?? '',
        $p['AlternativeCodes'] ?? '',
        $p['Barcode'] ?? '',
    ];
    return inv360_normalize_search(implode(' ', $parts));
}

function inv360_item_save($conn, array $data, int $userId, ?int $partId = null): array
{
    $name = trim((string)($data['item_name_fa'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'message' => 'نام قطعه الزامی است.', 'part_id' => null];
    }
    $payload = [
        'WorkshopCode' => trim((string)($data['workshop_code'] ?? '')),
        'InternalCode' => trim((string)($data['workshop_code'] ?? ($data['item_code'] ?? ''))),
        'TechnicalCode' => trim((string)($data['technical_code'] ?? '')),
        'ItemName' => $name,
        'ItemNameEn' => trim((string)($data['item_name_en'] ?? '')),
        'CommonName' => trim((string)($data['common_name'] ?? '')),
        'PartNumber' => trim((string)($data['part_number'] ?? '')),
        'OEMCode' => trim((string)($data['oem_code'] ?? '')),
        'AlternativeCodes' => trim((string)($data['alternative_codes'] ?? '')),
        'Barcode' => trim((string)($data['barcode'] ?? '')),
        'ManufacturerBrand' => trim((string)($data['brand'] ?? '')),
        'ManufacturerName' => trim((string)($data['manufacturer'] ?? '')),
        'CountryOfOrigin' => trim((string)($data['country'] ?? '')),
        'ItemType' => trim((string)($data['item_type'] ?? 'spare_part')),
        'SubCategory' => trim((string)($data['subcategory'] ?? '')),
        'FamilyName' => trim((string)($data['family'] ?? '')),
        'TechSpecs' => trim((string)($data['tech_specs'] ?? '')),
        'DimensionsText' => trim((string)($data['dimensions'] ?? '')),
        'WeightKg' => (($data['weight_kg'] ?? '') !== '' && ($data['weight_kg'] ?? null) !== null) ? (float)$data['weight_kg'] : null,
        'ColorName' => trim((string)($data['color'] ?? '')),
        'MaterialName' => trim((string)($data['material'] ?? '')),
        'CapacityText' => trim((string)($data['capacity'] ?? '')),
        'InstallSide' => trim((string)($data['install_side'] ?? 'none')),
        'ItemStatus' => trim((string)($data['item_status'] ?? 'active')),
        'MinStock' => (float)($data['min_stock'] ?? 0),
        'MaxStock' => (($data['max_stock'] ?? '') !== '' && ($data['max_stock'] ?? null) !== null) ? (float)$data['max_stock'] : null,
        'ReorderPoint' => (float)($data['reorder_point'] ?? 0),
        'Description' => trim((string)($data['description'] ?? '')),
    ];
    $payload['SearchNorm'] = inv360_item_build_search_norm($payload);

    if ($partId && $partId > 0) {
        $ok = inv360_exec(
            $conn,
            'UPDATE dbo.Parts SET WorkshopCode=?, InternalCode=?, TechnicalCode=?, ItemName=?, ItemNameEn=?, CommonName=?, PartNumber=?, OEMCode=?, AlternativeCodes=?, Barcode=?,
             ManufacturerBrand=?, ManufacturerName=?, CountryOfOrigin=?, ItemType=?, SubCategory=?, FamilyName=?, TechSpecs=?, DimensionsText=?, WeightKg=?, ColorName=?, MaterialName=?, CapacityText=?, InstallSide=?, ItemStatus=?, MinStock=?, MaxStock=?, ReorderPoint=?, Description=?, SearchNorm=?, UpdatedByUserID=?, UpdatedAt=SYSUTCDATETIME()
             WHERE PartID=?',
            [
                $payload['WorkshopCode'], $payload['InternalCode'], $payload['TechnicalCode'], $payload['ItemName'], $payload['ItemNameEn'], $payload['CommonName'],
                $payload['PartNumber'], $payload['OEMCode'], $payload['AlternativeCodes'], $payload['Barcode'], $payload['ManufacturerBrand'], $payload['ManufacturerName'],
                $payload['CountryOfOrigin'], $payload['ItemType'], $payload['SubCategory'], $payload['FamilyName'], $payload['TechSpecs'], $payload['DimensionsText'],
                $payload['WeightKg'], $payload['ColorName'], $payload['MaterialName'], $payload['CapacityText'], $payload['InstallSide'], $payload['ItemStatus'],
                $payload['MinStock'], $payload['MaxStock'], $payload['ReorderPoint'], $payload['Description'], $payload['SearchNorm'], $userId, $partId,
            ]
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'به‌روزرسانی کالا ناموفق بود.', 'part_id' => $partId];
        }
        inv360_audit($conn, 'ITEM', (string)$partId, 'UPDATED', $payload['ItemName'], $userId);
        return ['ok' => true, 'message' => 'کالا به‌روز شد.', 'part_id' => $partId];
    }

    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Parts
            (ReceiptNumber, MainCategoryID, UnitID, ItemName, Quantity, WorkshopCode, InternalCode, TechnicalCode, ItemNameEn, CommonName, PartNumber, OEMCode, AlternativeCodes, Barcode,
             ManufacturerBrand, ManufacturerName, CountryOfOrigin, ItemType, SubCategory, FamilyName, TechSpecs, DimensionsText, WeightKg, ColorName, MaterialName, CapacityText, InstallSide, ItemStatus, MinStock, MaxStock, ReorderPoint, Description, SearchNorm, WorkflowStatus, TechnicalValidationStatus, CreatedByUserID, CreatedAt, IsActive, IsDeleted)
         VALUES (?, 1, 1, ?, 0.001, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, N\'Draft\', N\'NotProvided\', ?, SYSUTCDATETIME(), 1, 0)',
        [
            'I' . gmdate('ymdHis') . random_int(1000, 9999),
            $payload['ItemName'], $payload['WorkshopCode'], $payload['InternalCode'], $payload['TechnicalCode'], $payload['ItemNameEn'], $payload['CommonName'],
            $payload['PartNumber'], $payload['OEMCode'], $payload['AlternativeCodes'], $payload['Barcode'], $payload['ManufacturerBrand'], $payload['ManufacturerName'],
            $payload['CountryOfOrigin'], $payload['ItemType'], $payload['SubCategory'], $payload['FamilyName'], $payload['TechSpecs'], $payload['DimensionsText'],
            $payload['WeightKg'], $payload['ColorName'], $payload['MaterialName'], $payload['CapacityText'], $payload['InstallSide'], $payload['ItemStatus'],
            $payload['MinStock'], $payload['MaxStock'], $payload['ReorderPoint'], $payload['Description'], $payload['SearchNorm'], $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت کالا ناموفق بود.', 'part_id' => null];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 PartID FROM dbo.Parts WHERE ItemName = ? ORDER BY PartID DESC', [$payload['ItemName']]) ?? 0);
    inv360_audit($conn, 'ITEM', (string)$id, 'CREATED', $payload['ItemName'], $userId);
    return ['ok' => true, 'message' => 'کالا ثبت شد.', 'part_id' => $id];
}
