<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_tools_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT * FROM dbo.Inv360ToolsAssets WHERE IsActive=1 ORDER BY ToolAssetID DESC', []);
    foreach ($rows as &$r) {
        $r['AssetID'] = $r['ToolAssetID'] ?? null;
        $r['AssetCode'] = $r['ToolCode'] ?? '';
        $r['AssetName'] = $r['ToolName'] ?? '';
        $r['SerialNo'] = $r['SerialNumber'] ?? '';
        $r['AssignedPerson'] = $r['AssignedUserName'] ?? '';
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
        'INSERT INTO dbo.Inv360ToolsAssets (ToolCode, ToolName, SerialNumber, LocationText, ChecklistNote, CreatedByUserID)
         VALUES (?,?,?,?,?,?)',
        [$code, $name, $d['serial'] ?? ($d['serial_no'] ?? null), $d['location'] ?? null, $d['checklist'] ?? null, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت ابزار ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 ToolAssetID FROM dbo.Inv360ToolsAssets WHERE ToolCode=?', [$code]) ?? 0);
    inv360_audit($conn, 'TOOL', (string)$id, 'CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'ابزار ثبت شد.', 'tool_id' => $id, 'asset_id' => $id];
}

function inv360_asset_create($conn, array $d, int $userId): array
{
    return inv360_tool_create($conn, $d, $userId);
}

function inv360_tool_issue($conn, int $toolId, string $toUser, int $userId): array
{
    $open = (int)(inv360_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.Inv360ToolEvents WHERE ToolAssetID=? AND EventType=N'issue'
         AND ToolEventID > ISNULL((SELECT MAX(ToolEventID) FROM dbo.Inv360ToolEvents WHERE ToolAssetID=? AND EventType=N'return'),0)",
        [$toolId, $toolId]
    ) ?? 0);
    if ($open > 0) {
        return ['ok' => false, 'message' => 'ابزار هنوز برگشت نشده است.'];
    }
    inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360ToolEvents (ToolAssetID, EventType, EventUserName, NoteText, CreatedByUserID) VALUES (?,N\'issue\',?,?,?)',
        [$toolId, $toUser, 'صدور ابزار', $userId]
    );
    inv360_exec($conn, 'UPDATE dbo.Inv360ToolsAssets SET AssignedUserName=? WHERE ToolAssetID=?', [$toUser, $toolId]);
    inv360_audit($conn, 'TOOL', (string)$toolId, 'ISSUED', $toUser, $userId);
    return ['ok' => true, 'message' => 'ابزار صادر شد.'];
}

function inv360_asset_issue($conn, int $assetId, string $assignedPerson, int $userId): array
{
    return inv360_tool_issue($conn, $assetId, $assignedPerson, $userId);
}

function inv360_tool_return($conn, int $toolId, string $health, int $userId): array
{
    inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360ToolEvents (ToolAssetID, EventType, EventUserName, NoteText, CreatedByUserID) VALUES (?,N\'return\',?,?,?)',
        [$toolId, 'return', $health, $userId]
    );
    inv360_exec(
        $conn,
        'UPDATE dbo.Inv360ToolsAssets SET AssignedUserName=NULL, HealthStatus=? WHERE ToolAssetID=?',
        [$health !== '' ? $health : 'good', $toolId]
    );
    inv360_audit($conn, 'TOOL', (string)$toolId, 'RETURNED', $health, $userId);
    return ['ok' => true, 'message' => 'ابزار برگشت داده شد.'];
}

function inv360_asset_return($conn, int $assetId, int $userId, string $health = 'good'): array
{
    return inv360_tool_return($conn, $assetId, $health, $userId);
}
