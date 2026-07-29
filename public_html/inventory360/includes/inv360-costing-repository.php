<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_landed_cost_calculate($connOrData, $maybeData = null, int $userId = 0)
{
    // Support both calculate(array) and calculate($conn, array, $userId)
    if (is_array($connOrData) && $maybeData === null) {
        $d = $connOrData;
        $sum = 0.0;
        foreach ([
            'purchase_price', 'foreign_freight', 'insurance', 'bank_fee', 'inspection', 'customs',
            'duties', 'warehousing', 'clearance', 'inland_freight', 'broker_fee', 'other_direct',
        ] as $k) {
            $sum += (float)($d[$k] ?? 0);
        }
        $qty = max(0.0001, (float)($d['qty'] ?? 1));
        return $sum / $qty;
    }

    $conn = $connOrData;
    $d = is_array($maybeData) ? $maybeData : [];
    $unit = inv360_landed_cost_calculate($d);
    $sum = $unit * max(0.0001, (float)($d['qty'] ?? 1));
    $save = inv360_landed_cost_save($conn, $d, $userId);
    if (empty($save['ok'])) {
        return $save;
    }
    return [
        'ok' => true,
        'message' => $save['message'],
        'result' => [
            'total_landed' => $sum,
            'unit_landed' => $unit,
        ],
        'id' => $save['id'] ?? null,
    ];
}

function inv360_landed_cost_save($conn, array $d, int $userId): array
{
    $ref = 'LC-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $unit = inv360_landed_cost_calculate($d);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360LandedCosts
            (RefNo, PartID, ValuationMethod, PurchasePrice, ForeignFreight, InsuranceAmount, BankFee, InspectionFee, CustomsFee, DutiesAmount,
             WarehousingFee, ClearanceFee, InlandFreight, BrokerFee, OtherDirectCost, AllocationMethod, Qty, LandedUnitCost, CreatedByUserID)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $ref, ((int)($d['part_id'] ?? 0)) ?: null, $d['valuation_method'] ?? 'weighted_average',
            (float)($d['purchase_price'] ?? 0), (float)($d['foreign_freight'] ?? 0), (float)($d['insurance'] ?? 0),
            (float)($d['bank_fee'] ?? 0), (float)($d['inspection'] ?? 0), (float)($d['customs'] ?? 0), (float)($d['duties'] ?? 0),
            (float)($d['warehousing'] ?? 0), (float)($d['clearance'] ?? 0), (float)($d['inland_freight'] ?? 0),
            (float)($d['broker_fee'] ?? 0), (float)($d['other_direct'] ?? 0), $d['allocation_method'] ?? 'by_value',
            max(0.0001, (float)($d['qty'] ?? 1)), $unit, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت بهای تمام‌شده ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 LandedCostID FROM dbo.Inv360LandedCosts WHERE RefNo=?', [$ref]) ?? 0);
    inv360_audit($conn, 'LANDED_COST', (string)$id, 'CREATED', $ref . ';unit=' . $unit, $userId);
    return ['ok' => true, 'message' => 'بهای تمام‌شده محاسبه و ثبت شد.', 'landed_unit_cost' => $unit, 'id' => $id];
}

function inv360_landed_cost_list($conn): array
{
    $rows = inv360_rows(
        $conn,
        'SELECT TOP 50 lc.*, p.ItemName, lc.LandedUnitCost AS UnitLanded,
                (lc.LandedUnitCost * lc.Qty) AS TotalLanded
         FROM dbo.Inv360LandedCosts lc
         LEFT JOIN dbo.Parts p ON p.PartID=lc.PartID
         ORDER BY lc.LandedCostID DESC',
        []
    );
    return $rows;
}

function inv360_costing_overview($conn, string $method = 'weighted_average'): array
{
    $allowed = ['weighted_average', 'last_purchase_price', 'standard_cost', 'replacement_cost', 'fifo', 'contract_price'];
    if (!in_array($method, $allowed, true)) {
        $method = 'weighted_average';
    }
    if ($method === 'last_purchase_price') {
        $unitExpr = 'ISNULL(p.LastPurchasePrice,0)';
    } elseif ($method === 'standard_cost') {
        $unitExpr = 'ISNULL(p.StandardCost,0)';
    } elseif ($method === 'replacement_cost') {
        $unitExpr = 'ISNULL(NULLIF(p.StandardCost,0), ISNULL(p.LastPurchasePrice,0))';
    } else {
        $unitExpr = 'ISNULL(AVG(b.UnitCost), ISNULL(p.StandardCost, ISNULL(p.LastPurchasePrice,0)))';
    }
    return inv360_rows(
        $conn,
        "SELECT p.PartID, p.ItemName,
                ISNULL(SUM(b.PhysicalQty),0) AS Qty,
                $unitExpr AS UnitCost,
                ISNULL(SUM(b.PhysicalQty),0) * ($unitExpr) AS Value
         FROM dbo.Parts p
         LEFT JOIN dbo.Inv360StockBalances b ON b.PartID=p.PartID
         WHERE ISNULL(p.IsDeleted,0)=0
         GROUP BY p.PartID, p.ItemName, p.LastPurchasePrice, p.StandardCost
         ORDER BY Value DESC",
        []
    );
}
