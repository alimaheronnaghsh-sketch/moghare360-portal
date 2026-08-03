<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
require_once __DIR__ . '/p360-auth.php';
function p360_audit($conn, string $entityType, string $entityId, string $eventName, ?string $note, int $userId = 0): void {
    $user = p360_current_user();
    $username = (string)($user['username'] ?? '');
    if ($userId < 1) $userId = (int)($user['user_id'] ?? 0);
    p360_exec($conn, 'INSERT INTO dbo.p360_audit_log (entity_type, entity_id, event_name, event_note, actor_user_id, actor_username) VALUES (?,?,?,?,?,?)', [$entityType, $entityId, $eventName, $note, $userId > 0 ? $userId : null, $username !== '' ? $username : null]);
}
function p360_audit_list($conn, int $limit = 100): array {
    $limit = max(1, min(500, $limit));
    return p360_rows($conn, "SELECT TOP $limit * FROM dbo.p360_audit_log ORDER BY id DESC", []);
}