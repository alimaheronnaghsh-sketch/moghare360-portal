<?php
require_once __DIR__.'/inv360-db.php';
require_once __DIR__.'/inv360-auth.php';
function inv360_audit($conn, string $entityType, string $entityId, string $eventName, ?string $note, int $userId=0): void {
    $user = inv360_current_user();
    $username = (string)($user['username'] ?? '');
    if ($userId < 1) $userId = (int)($user['user_id'] ?? 0);
    inv360_exec($conn, 'INSERT INTO dbo.inv360_app_audit (entity_type, entity_id, event_name, event_note, actor_user_id, actor_username) VALUES (?,?,?,?,?,?)',
        [$entityType,$entityId,$eventName,$note,$userId>0?$userId:null,$username!==''?$username:null]);
}
function inv360_audit_list($conn, int $limit=100): array {
    $limit=max(1,min(500,$limit));
    return inv360_rows($conn, "SELECT TOP $limit created_at AS CreatedAt, event_name AS EventCode, entity_type AS EntityType, entity_id AS EntityID, event_note AS DetailText, actor_username AS UserName, actor_user_id AS CreatedByUserID FROM dbo.inv360_app_audit ORDER BY app_audit_id DESC", []);
}
function inv360_setting_get($conn, string $key): ?string {
    return inv360_scalar($conn, 'SELECT setting_value FROM dbo.inv360_settings WHERE setting_key=?', [$key]);
}
function inv360_setting_set($conn, string $key, string $value, int $userId=0): bool {
    $n=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_settings WHERE setting_key=?',[$key])??0);
    if($n>0) $ok=inv360_exec($conn,'UPDATE dbo.inv360_settings SET setting_value=?, updated_at=SYSUTCDATETIME() WHERE setting_key=?',[$value,$key]);
    else $ok=inv360_exec($conn,'INSERT INTO dbo.inv360_settings (setting_key, setting_value) VALUES (?,?)',[$key,$value]);
    if($ok!==false){ inv360_audit($conn,'SETTING',$key,'UPDATED',$value,$userId); return true; }
    return false;
}