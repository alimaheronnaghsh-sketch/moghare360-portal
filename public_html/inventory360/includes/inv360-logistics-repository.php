<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_logistics_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.inv360_logistics_requests ORDER BY logistics_id DESC', []);
    foreach ($rows as &$r) {
        $r['RequestNo'] = $r['request_code'];
        $r['OriginText'] = $r['origin_text'];
        $r['DestinationText'] = $r['destination_text'];
        $r['LogStatus'] = $r['logistics_status'];
    }
    unset($r);
    return $rows;
}

function inv360_logistics_create($conn, array $d, int $userId): array
{
    $code = 'LG-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_logistics_requests
            (request_code, carrier_name, vehicle_plate, driver_name, waybill_no, route_text, origin_text, destination_text,
             weight_kg, volume_m3, package_count, freight_cost, tracking_code, proof_note, logistics_status, planned_at, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,N\'planned\',?,?)',
        [
            $code, $d['carrier'] ?? null, $d['vehicle'] ?? null, $d['driver_name'] ?? ($d['driver'] ?? null),
            $d['waybill'] ?? null, $d['route_text'] ?? ($d['route'] ?? null), $d['origin'] ?? null, $d['destination'] ?? null,
            ($d['weight'] ?? '') !== '' ? (float)$d['weight'] : null, ($d['volume'] ?? '') !== '' ? (float)$d['volume'] : null,
            ((int)($d['package_count'] ?? ($d['packages'] ?? 0))) ?: null,
            ($d['freight_cost'] ?? '') !== '' ? (float)$d['freight_cost'] : null,
            $d['tracking_code'] ?? ($d['tracking'] ?? null), $d['delivery_proof_note'] ?? ($d['proof'] ?? null),
            ($d['planned_date'] ?? '') !== '' ? $d['planned_date'] : null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت لجستیک ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 logistics_id FROM dbo.inv360_logistics_requests WHERE request_code=?', [$code]) ?? 0);
    inv360_audit($conn, 'LOGISTICS', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'درخواست لجستیک ثبت شد.', 'logistics_id' => $id];
}
