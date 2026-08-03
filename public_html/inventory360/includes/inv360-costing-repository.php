<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_landed_cost_calculate($connOrData, $maybeData = null, int $userId = 0)
{
    if (is_array($connOrData) && $maybeData === null) {
        $d = $connOrData;
        $sum = 0.0;
        foreach ([
            'purchase_price', 'foreign_freight', 'insurance', 'bank_fee', 'inspection', 'customs',
            'duties', 'warehousing', 'clearance', 'inland_freight', 'broker_fee', 'other_direct',
        ] as $k) {
            $sum += (float)($d[$k] ?? 0);
        }
        return $sum / max(0.0001, (float)($d['qty'] ?? 1));
    }
    $conn = $connOrData;
    $d = is_array($maybeData) ? $maybeData : [];
    $unit = inv360_landed_cost_calculate($d);
    $sum = $unit * max(0.0001, (float)($d['qty'] ?? 1));
    $save = inv360_landed_cost_save($conn, $d, $userId);
    if (empty($save['ok'])) {
        return $save;
    }
    return ['ok' => true, 'message' => $save['message'], 'result' => ['total_landed' => $sum, 'unit_landed' => $unit], 'id' => $save['id'] ?? null];
}

function inv360_landed_cost_save($conn, array $d, int $userId): array
{
    $ref = 'LC-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $unit = inv360_landed_cost_calculate($d);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_landed_costs
            (ref_no, item_id, valuation_method, purchase_price, foreign_freight, insurance_amount, bank_fee, inspection_fee, customs_fee, duties_amount,
             warehousing_fee, clearance_fee, inland_freight, broker_fee, other_direct_cost, allocation_method, qty, landed_unit_cost, created_by)
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
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 landed_cost_id FROM dbo.inv360_landed_costs WHERE ref_no=?', [$ref]) ?? 0);
    inv360_audit($conn, 'LANDED_COST', (string)$id, 'CREATED', $ref, $userId);
    return ['ok' => true, 'message' => 'بهای تمام‌شده محاسبه و ثبت شد.', 'landed_unit_cost' => $unit, 'id' => $id];
}

function inv360_landed_cost_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT lc.landed_cost_id AS LandedCostID, i.item_name_fa AS ItemName, lc.landed_unit_cost AS UnitLanded,
                (lc.landed_unit_cost * lc.qty) AS TotalLanded
         FROM dbo.inv360_landed_costs lc LEFT JOIN dbo.inv360_items i ON i.item_id=lc.item_id
         ORDER BY lc.landed_cost_id DESC',
        []
    );
}

function inv360_costing_overview($conn, string $method = 'weighted_average'): array
{
    $allowed = ['weighted_average', 'last_purchase_price', 'standard_cost', 'replacement_cost', 'fifo', 'contract_price'];
    if (!in_array($method, $allowed, true)) {
        $method = 'weighted_average';
    }
    if ($method === 'last_purchase_price') {
        $unit = 'ISNULL(i.last_purchase_price,0)';
    } elseif ($method === 'standard_cost') {
        $unit = 'ISNULL(i.standard_cost,0)';
    } else {
        $unit = 'ISNULL(AVG(b.unit_cost), ISNULL(i.standard_cost, ISNULL(i.last_purchase_price,0)))';
    }
    return inv360_rows(
        $conn,
        "SELECT i.item_id AS PartID, i.item_name_fa AS ItemName, ISNULL(SUM(b.physical_qty),0) AS Qty,
                $unit AS UnitCost, ISNULL(SUM(b.physical_qty),0)*($unit) AS Value
         FROM dbo.inv360_items i
         LEFT JOIN dbo.inv360_stock_balances b ON b.item_id=i.item_id
         WHERE i.is_deleted=0
         GROUP BY i.item_id, i.item_name_fa, i.last_purchase_price, i.standard_cost
         ORDER BY Value DESC",
        []
    );
}
