<?php
declare(strict_types=1);

function work360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function work360_today(): string
{
    return date('Y-m-d');
}

function work360_status_fa(string $code): string
{
    $map = [
        'TODO' => 'برای انجام',
        'IN_PROGRESS' => 'در حال انجام',
        'WAITING' => 'منتظر',
        'BLOCKED' => 'مسدود',
        'DONE' => 'انجام‌شده',
        'CANCELLED' => 'لغو',
    ];
    return $map[strtoupper($code)] ?? $code;
}

function work360_priority_fa(string $code): string
{
    $map = [
        'LOW' => 'کم',
        'NORMAL' => 'عادی',
        'HIGH' => 'بالا',
        'URGENT' => 'فوری',
    ];
    return $map[strtoupper($code)] ?? $code;
}

function work360_role_fa(string $code): string
{
    $map = [
        'OWNER' => 'مالک',
        'MANAGER' => 'مدیر',
        'SUPERVISOR' => 'سرپرست',
        'STAFF' => 'کارشناس',
    ];
    return $map[strtoupper($code)] ?? $code;
}

function work360_suggestions(string $deptCode): array
{
    $map = [
        'PROCUREMENT_LOCAL' => ['استعلام قیمت', 'ثبت درخواست خرید', 'پیگیری تأمین‌کننده', 'کنترل موجودی موردنیاز', 'تحویل قطعه به انبار'],
        'FINANCE_ACCOUNTING_CASHIER' => ['دریافت وجه مشتری', 'ثبت پرداخت', 'کنترل صندوق', 'تسویه فاکتور', 'گزارش روزانه صندوق', 'پیگیری مطالبات'],
        'CRM_RECEPTION' => ['تماس با مشتری', 'تکمیل پذیرش', 'پیگیری پرونده', 'ثبت رضایت', 'یادآوری سرویس', 'ثبت شکایت'],
        'INTERNAL_SERVICE_HALL' => ['تخصیص کار به تکنسین', 'پیگیری تعمیر', 'ثبت توقف کار', 'کنترل مصرف قطعه', 'آماده‌سازی برای QC'],
        'LOGISTICS_EXTERNAL_SERVICE' => ['ارسال خودرو/قطعه', 'پیگیری پیمانکار بیرونی', 'دریافت نتیجه خدمات بیرونی', 'کنترل هزینه خدمات بیرونی', 'برگشت خودرو/قطعه'],
        'FOREIGN_PURCHASE' => ['پیگیری Proforma', 'کنترل پرداخت ارزی', 'پیگیری حمل', 'کنترل اسناد', 'پیگیری ترخیص', 'ثبت هزینه‌های جانبی'],
    ];
    return $map[$deptCode] ?? [];
}

function work360_performance_score(int $today, int $done, int $overdue, int $blocked, int $waiting): float
{
    // Local score: start 100, penalize overdue/blocked/waiting, bonus for done rate. Clamp 0..100.
    $score = 100.0;
    $score -= ($overdue * 12);
    $score -= ($blocked * 10);
    $score -= ($waiting * 4);
    if ($today > 0) {
        $score += min(20.0, ($done / $today) * 20.0);
    } elseif ($done > 0) {
        $score += 10.0;
    }
    if ($score < 0) { $score = 0; }
    if ($score > 100) { $score = 100; }
    return round($score, 1);
}

function work360_light_color(int $overdue, int $blocked, int $todayPending, int $waiting, int $totalOpen): string
{
    if ($overdue > 0 || $blocked > 0) {
        return 'red';
    }
    if ($todayPending > 0 || $waiting > 0) {
        return 'yellow';
    }
    if ($totalOpen === 0) {
        return 'gray';
    }
    return 'green';
}

function work360_scope_sql(array $user, string $alias = 't'): array
{
    $role = strtoupper((string)($user['role_code'] ?? 'STAFF'));
    $uid = (int)($user['user_id'] ?? 0);
    $dept = $user['department_id'] !== null ? (int)$user['department_id'] : null;
    if ($role === 'OWNER' || $role === 'MANAGER') {
        return ['sql' => '1=1', 'params' => []];
    }
    if ($role === 'SUPERVISOR') {
        if ($dept === null) {
            return ['sql' => $alias . '.supervisor_user_id=?', 'params' => [$uid]];
        }
        return [
            'sql' => '(' . $alias . '.department_id=? OR ' . $alias . '.supervisor_user_id=? OR ' . $alias . '.assigned_to_user_id=?)',
            'params' => [$dept, $uid, $uid],
        ];
    }
    return ['sql' => $alias . '.assigned_to_user_id=?', 'params' => [$uid]];
}

function work360_visible_departments($conn, array $user): array
{
    $role = strtoupper((string)($user['role_code'] ?? 'STAFF'));
    if ($role === 'OWNER' || $role === 'MANAGER') {
        return work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', []);
    }
    $deptId = $user['department_id'] !== null ? (int)$user['department_id'] : 0;
    if ($deptId < 1) {
        return [];
    }
    return work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 AND department_id=?', [$deptId]);
}

function work360_dept_metrics($conn, int $departmentId, ?string $today = null): array
{
    $today = $today ?: work360_today();
    $open = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", [$departmentId]) ?? 0);
    $todayDue = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND due_date=? AND status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", [$departmentId, $today]) ?? 0);
    $overdue = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND due_date<? AND status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", [$departmentId, $today]) ?? 0);
    $blocked = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND status_code=N'BLOCKED'", [$departmentId]) ?? 0);
    $waiting = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND status_code=N'WAITING'", [$departmentId]) ?? 0);
    $doneToday = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE department_id=? AND status_code=N'DONE' AND CONVERT(date, completed_at)=?", [$departmentId, $today]) ?? 0);
    $totalDueBucket = max(1, $todayDue + $doneToday + $overdue);
    $completion = round(($doneToday / $totalDueBucket) * 100, 1);
    $delay = $open > 0 ? round(($overdue / $open) * 100, 1) : 0.0;
    $light = work360_light_color($overdue, $blocked, $todayDue, $waiting, $open);
    return compact('open', 'todayDue', 'overdue', 'blocked', 'waiting', 'doneToday', 'completion', 'delay', 'light');
}

function work360_user_metrics($conn, int $userId, ?string $today = null): array
{
    $today = $today ?: work360_today();
    $todayTasks = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE assigned_to_user_id=? AND due_date=? AND status_code<>N'CANCELLED'", [$userId, $today]) ?? 0);
    $done = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE assigned_to_user_id=? AND status_code=N'DONE' AND CONVERT(date, completed_at)=?", [$userId, $today]) ?? 0);
    $overdue = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE assigned_to_user_id=? AND due_date<? AND status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", [$userId, $today]) ?? 0);
    $blocked = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE assigned_to_user_id=? AND status_code=N'BLOCKED'", [$userId]) ?? 0);
    $waiting = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE assigned_to_user_id=? AND status_code=N'WAITING'", [$userId]) ?? 0);
    $score = work360_performance_score($todayTasks, $done, $overdue, $blocked, $waiting);
    return compact('todayTasks', 'done', 'overdue', 'blocked', 'waiting', 'score');
}

function work360_next_task_code($conn): string
{
    $n = (int)(work360_scalar($conn, 'SELECT ISNULL(MAX(task_id),0)+1 FROM dbo.work360_tasks', []) ?? 1);
    return 'W360-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

function work360_set_status($conn, int $taskId, string $newStatus, int $userId, ?string $note = null): bool
{
    $task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$taskId]);
    if (!$task) {
        return false;
    }
    $old = (string)$task['status_code'];
    $newStatus = strtoupper($newStatus);
    $completedAt = ($newStatus === 'DONE') ? date('Y-m-d H:i:s') : null;
    if ($newStatus !== 'DONE') {
        work360_exec($conn, 'UPDATE dbo.work360_tasks SET status_code=?, completed_at=NULL, completion_note=COALESCE(?, completion_note), updated_at=SYSUTCDATETIME() WHERE task_id=?', [$newStatus, $note, $taskId]);
    } else {
        work360_exec($conn, 'UPDATE dbo.work360_tasks SET status_code=?, completed_at=?, completion_note=COALESCE(?, completion_note), updated_at=SYSUTCDATETIME() WHERE task_id=?', [$newStatus, $completedAt, $note, $taskId]);
    }
    work360_exec($conn, 'INSERT INTO dbo.work360_task_status_history (task_id, old_status_code, new_status_code, changed_by_user_id, note) VALUES (?,?,?,?,?)', [$taskId, $old, $newStatus, $userId, $note]);
    return true;
}

function work360_can_access_task(array $user, array $task): bool
{
    $role = strtoupper((string)($user['role_code'] ?? 'STAFF'));
    $uid = (int)$user['user_id'];
    if ($role === 'OWNER' || $role === 'MANAGER') {
        return true;
    }
    if ($role === 'SUPERVISOR') {
        $dept = $user['department_id'] !== null ? (int)$user['department_id'] : -1;
        return ((int)($task['department_id'] ?? 0) === $dept)
            || ((int)($task['supervisor_user_id'] ?? 0) === $uid)
            || ((int)($task['assigned_to_user_id'] ?? 0) === $uid);
    }
    return ((int)($task['assigned_to_user_id'] ?? 0) === $uid);
}

/**
 * Hierarchical completion authority.
 * Returns: ok, reason, mode (SELF|SUPERVISOR|MANAGER|OWNER), label_fa, note_required, already_closed
 */
function work360_can_complete_task($conn, array $user, array $task): array
{
    $role = strtoupper((string)($user['role_code'] ?? 'STAFF'));
    $uid = (int)($user['user_id'] ?? 0);
    $status = strtoupper((string)($task['status_code'] ?? ''));
    $assigneeId = (int)($task['assigned_to_user_id'] ?? 0);
    $taskSupId = (int)($task['supervisor_user_id'] ?? 0);
    $taskDept = (int)($task['department_id'] ?? 0);
    $userDept = $user['department_id'] !== null ? (int)$user['department_id'] : 0;

    if (in_array($status, ['DONE', 'CANCELLED'], true)) {
        return [
            'ok' => false,
            'reason' => 'این کار قبلاً بسته شده است.',
            'mode' => null,
            'label_fa' => '',
            'note_required' => false,
            'already_closed' => true,
        ];
    }

    $assignee = $assigneeId > 0
        ? work360_one($conn, 'SELECT TOP 1 user_id, role_code, department_id, supervisor_user_id FROM dbo.work360_users WHERE user_id=?', [$assigneeId])
        : null;
    $assigneeRole = strtoupper((string)($assignee['role_code'] ?? ''));
    $assigneeSup = (int)($assignee['supervisor_user_id'] ?? 0);
    $assigneeDept = (int)($assignee['department_id'] ?? 0);
    $isSelf = ($assigneeId === $uid);

    if ($role === 'OWNER') {
        return [
            'ok' => true,
            'reason' => '',
            'mode' => $isSelf ? 'SELF' : 'OWNER',
            'label_fa' => $isSelf ? 'اتمام کار من' : 'اتمام توسط مالک',
            'note_required' => !$isSelf,
            'already_closed' => false,
        ];
    }

    if ($role === 'MANAGER') {
        if ($assigneeRole === 'OWNER') {
            return [
                'ok' => false,
                'reason' => 'شما مجوز اتمام این کار را ندارید.',
                'mode' => null,
                'label_fa' => '',
                'note_required' => false,
                'already_closed' => false,
            ];
        }
        return [
            'ok' => true,
            'reason' => '',
            'mode' => $isSelf ? 'SELF' : 'MANAGER',
            'label_fa' => $isSelf ? 'اتمام کار من' : 'اتمام توسط مدیر',
            'note_required' => !$isSelf,
            'already_closed' => false,
        ];
    }

    if ($role === 'SUPERVISOR') {
        if ($isSelf) {
            return [
                'ok' => true,
                'reason' => '',
                'mode' => 'SELF',
                'label_fa' => 'اتمام کار من',
                'note_required' => false,
                'already_closed' => false,
            ];
        }
        // Cannot close manager/owner tasks
        if (in_array($assigneeRole, ['OWNER', 'MANAGER'], true)) {
            return [
                'ok' => false,
                'reason' => 'شما مجوز اتمام این کار را ندارید.',
                'mode' => null,
                'label_fa' => '',
                'note_required' => false,
                'already_closed' => false,
            ];
        }
        // Cannot close another supervisor's assigned task unless self is task supervisor
        if ($assigneeRole === 'SUPERVISOR' && $assigneeId !== $uid && $taskSupId !== $uid) {
            return [
                'ok' => false,
                'reason' => 'شما مجوز اتمام این کار را ندارید.',
                'mode' => null,
                'label_fa' => '',
                'note_required' => false,
                'already_closed' => false,
            ];
        }
        $teamOk = ($assigneeSup === $uid)
            || ($taskSupId === $uid)
            || ($userDept > 0 && $taskDept === $userDept && $assigneeDept === $userDept && $assigneeRole === 'STAFF');
        if ($teamOk) {
            return [
                'ok' => true,
                'reason' => '',
                'mode' => 'SUPERVISOR',
                'label_fa' => 'اتمام توسط سرپرست',
                'note_required' => true,
                'already_closed' => false,
            ];
        }
        return [
            'ok' => false,
            'reason' => 'شما مجوز اتمام این کار را ندارید.',
            'mode' => null,
            'label_fa' => '',
            'note_required' => false,
            'already_closed' => false,
        ];
    }

    // STAFF: own only
    if ($isSelf) {
        return [
            'ok' => true,
            'reason' => '',
            'mode' => 'SELF',
            'label_fa' => 'اتمام کار من',
            'note_required' => false,
            'already_closed' => false,
        ];
    }
    return [
        'ok' => false,
        'reason' => 'شما مجوز اتمام این کار را ندارید.',
        'mode' => null,
        'label_fa' => '',
        'note_required' => false,
        'already_closed' => false,
    ];
}

function work360_completion_history_tag(string $mode): string
{
    $map = [
        'SELF' => 'DONE_BY_SELF',
        'SUPERVISOR' => 'DONE_BY_SUPERVISOR',
        'MANAGER' => 'DONE_BY_MANAGER',
        'OWNER' => 'DONE_BY_OWNER',
    ];
    return $map[$mode] ?? 'DONE_BY_SELF';
}

function work360_completion_followup_note(string $mode): string
{
    $map = [
        'SELF' => 'کار توسط خود کاربر انجام شد.',
        'SUPERVISOR' => 'کار توسط سرپرست بسته شد.',
        'MANAGER' => 'کار توسط مدیر بسته شد.',
        'OWNER' => 'کار توسط مالک بسته شد.',
    ];
    return $map[$mode] ?? 'کار بسته شد.';
}

function work360_completion_badge_fa(string $mode): string
{
    $map = [
        'SELF' => 'اتمام توسط خود کاربر',
        'SUPERVISOR' => 'اتمام توسط سرپرست',
        'MANAGER' => 'اتمام توسط مدیر',
        'OWNER' => 'اتمام توسط مالک',
    ];
    return $map[$mode] ?? '';
}

/**
 * Complete a task with hierarchical authority. Returns ['ok'=>bool,'message'=>string]
 */
function work360_complete_task($conn, array $user, int $taskId, ?string $note = null): array
{
    $task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$taskId]);
    if (!$task) {
        return ['ok' => false, 'message' => 'کار یافت نشد.'];
    }
    $auth = work360_can_complete_task($conn, $user, $task);
    if (!$auth['ok']) {
        return ['ok' => false, 'message' => (string)$auth['reason']];
    }
    $note = trim((string)$note);
    if (!empty($auth['note_required']) && $note === '') {
        $defaults = [
            'SUPERVISOR' => 'اتمام توسط سرپرست',
            'MANAGER' => 'اتمام توسط مدیر',
            'OWNER' => 'اتمام توسط مالک',
        ];
        $mode = (string)$auth['mode'];
        if (isset($defaults[$mode])) {
            $note = $defaults[$mode];
        } else {
            return ['ok' => false, 'message' => 'برای اتمام کار دیگران، یادداشت الزامی است.'];
        }
    }
    if ($note === '') {
        $note = work360_completion_followup_note((string)$auth['mode']);
    }

    $uid = (int)$user['user_id'];
    $old = (string)$task['status_code'];
    $mode = (string)$auth['mode'];
    $tag = work360_completion_history_tag($mode);
    $histNote = $tag . ' | ' . $note;
    $now = date('Y-m-d H:i:s');

    work360_exec(
        $conn,
        'UPDATE dbo.work360_tasks SET status_code=N\'DONE\', completed_at=?, completion_note=?, approved_by_user_id=?, approved_at=?, updated_at=SYSUTCDATETIME() WHERE task_id=?',
        [$now, $note, $uid, $now, $taskId]
    );
    work360_exec(
        $conn,
        'INSERT INTO dbo.work360_task_status_history (task_id, old_status_code, new_status_code, changed_by_user_id, note) VALUES (?,?,N\'DONE\',?,?)',
        [$taskId, $old, $uid, $histNote]
    );
    work360_exec(
        $conn,
        'INSERT INTO dbo.work360_task_followups (task_id, followup_by_user_id, followup_type, note) VALUES (?,?,N\'APPROVAL_NOTE\',?)',
        [$taskId, $uid, work360_completion_followup_note($mode) . ($note !== '' ? (' ' . $note) : '')]
    );
    return ['ok' => true, 'message' => 'کار با موفقیت بسته شد.', 'mode' => $mode];
}

function work360_detect_completion_mode_from_history($conn, int $taskId): ?string
{
    $row = work360_one($conn, "SELECT TOP 1 note FROM dbo.work360_task_status_history WHERE task_id=? AND new_status_code=N'DONE' ORDER BY history_id DESC", [$taskId]);
    if (!$row) {
        return null;
    }
    $note = (string)($row['note'] ?? '');
    if (str_contains($note, 'DONE_BY_OWNER')) {
        return 'OWNER';
    }
    if (str_contains($note, 'DONE_BY_MANAGER')) {
        return 'MANAGER';
    }
    if (str_contains($note, 'DONE_BY_SUPERVISOR')) {
        return 'SUPERVISOR';
    }
    if (str_contains($note, 'DONE_BY_SELF')) {
        return 'SELF';
    }
    return null;
}
