<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_logistics_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.Inv360LogisticsRequests ORDER BY LogisticsID DESC', []);
    foreach ($rows as &$r) {
        $r['RequestNo'] = $r['RequestCode'] ?? ($r['RequestNo'] ?? '');
        $r['LogStatus'] = $r['LogisticsStatus'] ?? ($r['LogStatus'] ?? '');
    }
    unset($r);
    return $rows;
}

function inv360_logistics_create($conn, array $d, int $userId): array
{
    $code = 'LG-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360LogisticsRequests
            (RequestCode, CarrierName, VehiclePlate, DriverName, WaybillNo, RouteText, OriginText, DestinationText,
             WeightKg, VolumeM3, PackageCount, FreightCost, TrackingCode, ProofNote, LogisticsStatus, PlannedAt, CreatedByUserID)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,N\'planned\',?,?)',
        [
            $code,
            $d['carrier'] ?? null,
            $d['vehicle'] ?? null,
            $d['driver_name'] ?? ($d['driver'] ?? null),
            $d['waybill'] ?? null,
            $d['route_text'] ?? ($d['route'] ?? null),
            $d['origin'] ?? null,
            $d['destination'] ?? null,
            ($d['weight'] ?? '') !== '' ? (float)$d['weight'] : null,
            ($d['volume'] ?? '') !== '' ? (float)$d['volume'] : null,
            ((int)($d['package_count'] ?? ($d['packages'] ?? 0))) ?: null,
            ($d['freight_cost'] ?? '') !== '' ? (float)$d['freight_cost'] : null,
            $d['tracking_code'] ?? ($d['tracking'] ?? null),
            $d['delivery_proof_note'] ?? ($d['proof'] ?? null),
            ($d['planned_date'] ?? '') !== '' ? $d['planned_date'] : null,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت لجستیک ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 LogisticsID FROM dbo.Inv360LogisticsRequests WHERE RequestCode=?', [$code]) ?? 0);
    inv360_audit($conn, 'LOGISTICS', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'درخواست لجستیک ثبت شد.', 'logistics_id' => $id];
}
