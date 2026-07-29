<?php
declare(strict_types=1);

require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-auth.php';

function inv360_audit($conn, string $entityType, string $entityId, string $eventName, ?string $note, int $userId = 0): void
{
    $user = inv360_current_user();
    $username = (string)($user['username'] ?? '');
    if ($userId < 1) {
        $userId = (int)($user['user_id'] ?? 0);
    }
    if (inv360_table_exists($conn, 'Inv360AppAudit')) {
        inv360_exec(
            $conn,
            'INSERT INTO dbo.Inv360AppAudit (EntityType, EntityID, EventName, EventNote, ActorUserID, ActorUsername)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$entityType, $entityId, $eventName, $note, $userId > 0 ? $userId : null, $username !== '' ? $username : null]
        );
    }
    // Legacy AuditLog has a narrow ActionType CHECK; map to allowed values only.
    if (inv360_table_exists($conn, 'AuditLog')) {
        $mapped = 'UPDATE';
        $upper = strtoupper($eventName);
        if (str_contains($upper, 'CREATE') || $upper === 'CREATED') {
            $mapped = 'CREATE';
        } elseif (str_contains($upper, 'APPROVE') || $upper === 'POSTED' || $upper === 'ACCEPT') {
            $mapped = 'APPROVE';
        } elseif (str_contains($upper, 'REJECT') || $upper === 'CANCEL') {
            $mapped = 'REJECT';
        }
        inv360_exec(
            $conn,
            'INSERT INTO dbo.AuditLog (EntityName, EntityID, ActionType, OldValue, NewValue, ActionByUserID, ActionAt, IPAddress, UserAgent)
             VALUES (?, ?, ?, NULL, ?, ?, SYSUTCDATETIME(), ?, ?)',
            [
                substr($entityType, 0, 50),
                substr($entityId, 0, 50),
                $mapped,
                substr((string)$note, 0, 500),
                $userId > 0 ? $userId : null,
                substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300),
            ]
        );
    }
}

function inv360_audit_list($conn, int $limit = 100): array
{
    $limit = max(1, min(500, $limit));
    if (inv360_table_exists($conn, 'Inv360AppAudit')) {
        return inv360_rows(
            $conn,
            "SELECT TOP $limit a.CreatedAt, a.EventName AS EventCode, a.EntityType, a.EntityID,
                    a.EventNote AS DetailText, a.ActorUsername AS UserName, a.ActorUserID AS CreatedByUserID
             FROM dbo.Inv360AppAudit a
             ORDER BY a.AppAuditID DESC",
            []
        );
    }
    return [];
}

function inv360_setting_get($conn, string $key): ?string
{
    if (!inv360_table_exists($conn, 'Inv360Settings')) {
        return null;
    }
    return inv360_scalar($conn, 'SELECT SettingValue FROM dbo.Inv360Settings WHERE SettingKey=?', [$key]);
}

function inv360_setting_set($conn, string $key, string $value, int $userId = 0): bool
{
    if (!inv360_table_exists($conn, 'Inv360Settings')) {
        return false;
    }
    $exists = inv360_scalar($conn, 'SELECT COUNT(*) FROM dbo.Inv360Settings WHERE SettingKey=?', [$key]);
    if (((int)$exists) > 0) {
        $ok = inv360_exec($conn, 'UPDATE dbo.Inv360Settings SET SettingValue=?, UpdatedAt=SYSUTCDATETIME() WHERE SettingKey=?', [$value, $key]);
    } else {
        $ok = inv360_exec($conn, 'INSERT INTO dbo.Inv360Settings (SettingKey, SettingValue) VALUES (?,?)', [$key, $value]);
    }
    if ($ok !== false) {
        inv360_audit($conn, 'SETTING', $key, 'UPDATED', $value, $userId);
        return true;
    }
    return false;
}
