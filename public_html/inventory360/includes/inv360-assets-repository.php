<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_tools_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT * FROM dbo.inv360_tools_assets WHERE is_active=1 ORDER BY tool_asset_id DESC', []);
    foreach ($rows as &$r) {
        $r['AssetID'] = $r['tool_asset_id'];
        $r['AssetCode'] = $r['tool_code'];
        $r['AssetName'] = $r['tool_name'];
        $r['SerialNo'] = $r['serial_number'];
        $r['AssignedPerson'] = $r['assigned_user_name'];
        $r['HealthStatus'] = $r['health_status'];
    }
    unset($r);
    return $rows;
}

function inv360_assets_list($conn): array
{
    return inv360_tools_list($conn);
}

function inv360_tool_create($conn, array $d, int $userId): array
{
    $code = trim((string)($d['tool_code'] ?? ($d['asset_code'] ?? '')));
    $name = trim((string)($d['tool_name'] ?? ($d['asset_name'] ?? '')));
    if ($code === '' || $name === '') {
        return ['ok' => false, 'message' => 'کد و نام ابزار الزامی است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_tools_assets (tool_code, tool_name, serial_number, location_text, created_by) VALUES (?,?,?,?,?)',
        [$code, $name, $d['serial'] ?? ($d['serial_no'] ?? null), $d['location'] ?? null, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت ابزار ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 tool_asset_id FROM dbo.inv360_tools_assets WHERE tool_code=?', [$code]) ?? 0);
    inv360_audit($conn, 'TOOL', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'ابزار ثبت شد.', 'tool_id' => $id, 'asset_id' => $id];
}

function inv360_asset_create($conn, array $d, int $userId): array
{
    return inv360_tool_create($conn, $d, $userId);
}

function inv360_tool_issue($conn, int $toolId, string $toUser, int $userId): array
{
    inv360_exec($conn, 'INSERT INTO dbo.inv360_tool_events (tool_asset_id, event_type, event_user_name, note_text, created_by) VALUES (?,N\'issue\',?,?,?)', [$toolId, $toUser, 'صدور ابزار', $userId]);
    inv360_exec($conn, 'UPDATE dbo.inv360_tools_assets SET assigned_user_name=? WHERE tool_asset_id=?', [$toUser, $toolId]);
    inv360_audit($conn, 'TOOL', (string)$toolId, 'ISSUED', $toUser, $userId);
    return ['ok' => true, 'message' => 'ابزار صادر شد.'];
}

function inv360_asset_issue($conn, int $assetId, string $assignedPerson, int $userId): array
{
    return inv360_tool_issue($conn, $assetId, $assignedPerson, $userId);
}

function inv360_tool_return($conn, int $toolId, string $health, int $userId): array
{
    inv360_exec($conn, 'INSERT INTO dbo.inv360_tool_events (tool_asset_id, event_type, event_user_name, note_text, created_by) VALUES (?,N\'return\',?,?,?)', [$toolId, 'return', $health, $userId]);
    inv360_exec($conn, 'UPDATE dbo.inv360_tools_assets SET assigned_user_name=NULL, health_status=? WHERE tool_asset_id=?', [$health !== '' ? $health : 'good', $toolId]);
    inv360_audit($conn, 'TOOL', (string)$toolId, 'RETURNED', $health, $userId);
    return ['ok' => true, 'message' => 'ابزار برگشت داده شد.'];
}

function inv360_asset_return($conn, int $assetId, int $userId, string $health = 'good'): array
{
    return inv360_tool_return($conn, $assetId, $health, $userId);
}
