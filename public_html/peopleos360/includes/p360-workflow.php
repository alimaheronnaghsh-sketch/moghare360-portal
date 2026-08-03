<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
require_once __DIR__ . '/p360-auth.php';
require_once __DIR__ . '/p360-audit.php';
function p360_workflow_create($conn, string $taskType, string $entityType, string $entityId, string $title, int $makerUserId, ?int $checkerUserId = null): array {
    if ($checkerUserId !== null && $checkerUserId === $makerUserId) return ['ok' => false, 'message' => 'تأییدکننده نمی‌تواند همان سازنده باشد (maker-checker).', 'task_id' => null];
    $ok = p360_exec($conn, 'INSERT INTO dbo.p360_workflow_tasks (task_type, entity_type, entity_id, title, maker_user_id, checker_user_id, task_status) VALUES (?,?,?,?,?,?,N\'pending\')', [$taskType, $entityType, $entityId, $title, $makerUserId, $checkerUserId]);
    if ($ok === false) return ['ok' => false, 'message' => 'ایجاد کار گردش کار ناموفق.', 'task_id' => null];
    $id = (int)(p360_scalar($conn, 'SELECT TOP 1 id FROM dbo.p360_workflow_tasks WHERE entity_type=? AND entity_id=? ORDER BY id DESC', [$entityType, $entityId]) ?? 0);
    p360_exec($conn, 'INSERT INTO dbo.p360_workflow_events (task_id, event_name, actor_user_id, event_note) VALUES (?,?,?,N\'created\')', [$id, 'CREATED', $makerUserId]);
    p360_audit($conn, 'WORKFLOW', (string)$id, 'CREATED', $title, $makerUserId);
    return ['ok' => true, 'message' => 'در صف تأیید قرار گرفت.', 'task_id' => $id];
}
function p360_workflow_approve($conn, int $taskId, int $checkerUserId, ?string $note = null): array {
    $t = p360_one($conn, 'SELECT TOP 1 * FROM dbo.p360_workflow_tasks WHERE id=?', [$taskId]);
    if (!$t) return ['ok' => false, 'message' => 'کار یافت نشد.'];
    if (strtolower((string)$t['task_status']) !== 'pending') return ['ok' => false, 'message' => 'این کار قبلاً تصمیم‌گیری شده.'];
    $maker = (int)($t['maker_user_id'] ?? 0);
    if ($maker === $checkerUserId) return ['ok' => false, 'message' => 'سازنده نمی‌تواند تأییدکننده باشد.'];
    $assigned = (int)($t['checker_user_id'] ?? 0);
    if ($assigned > 0 && $assigned !== $checkerUserId) return ['ok' => false, 'message' => 'این کار برای تأییدکننده دیگری است.'];
    p360_exec($conn, 'UPDATE dbo.p360_workflow_tasks SET task_status=N\'approved\', decided_at=SYSUTCDATETIME(), decision_note=? WHERE id=?', [$note, $taskId]);
    p360_exec($conn, 'INSERT INTO dbo.p360_workflow_events (task_id, event_name, actor_user_id, event_note) VALUES (?,?,?,?)', [$taskId, 'APPROVED', $checkerUserId, $note]);
    p360_audit($conn, 'WORKFLOW', (string)$taskId, 'APPROVED', $note, $checkerUserId);
    return ['ok' => true, 'message' => 'تأیید شد.'];
}
function p360_workflow_reject($conn, int $taskId, int $checkerUserId, ?string $note = null): array {
    $t = p360_one($conn, 'SELECT TOP 1 * FROM dbo.p360_workflow_tasks WHERE id=?', [$taskId]);
    if (!$t) return ['ok' => false, 'message' => 'کار یافت نشد.'];
    if ((int)($t['maker_user_id'] ?? 0) === $checkerUserId) return ['ok' => false, 'message' => 'سازنده نمی‌تواند ردکننده باشد.'];
    p360_exec($conn, 'UPDATE dbo.p360_workflow_tasks SET task_status=N\'rejected\', decided_at=SYSUTCDATETIME(), decision_note=? WHERE id=?', [$note, $taskId]);
    p360_exec($conn, 'INSERT INTO dbo.p360_workflow_events (task_id, event_name, actor_user_id, event_note) VALUES (?,?,?,?)', [$taskId, 'REJECTED', $checkerUserId, $note]);
    return ['ok' => true, 'message' => 'رد شد.'];
}
function p360_workflow_pending($conn, ?int $checkerUserId = null, int $limit = 50): array {
    $limit = max(1, min(200, $limit));
    if ($checkerUserId) {
        return p360_rows($conn, "SELECT TOP $limit * FROM dbo.p360_workflow_tasks WHERE task_status=N'pending' AND (checker_user_id IS NULL OR checker_user_id=?) ORDER BY id DESC", [$checkerUserId]);
    }
    return p360_rows($conn, "SELECT TOP $limit * FROM dbo.p360_workflow_tasks WHERE task_status=N'pending' ORDER BY id DESC", []);
}