<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$id = (int)($_GET['id'] ?? 0);
$task = work360_one($conn, 'SELECT TOP 1 t.*, d.department_name_fa, a.full_name AS assignee_name, s.full_name AS supervisor_name
  FROM dbo.work360_tasks t
  JOIN dbo.work360_departments d ON d.department_id=t.department_id
  LEFT JOIN dbo.work360_users a ON a.user_id=t.assigned_to_user_id
  LEFT JOIN dbo.work360_users s ON s.user_id=t.supervisor_user_id
  WHERE t.task_id=?', [$id]);
if (!$task || !work360_can_access_task($user, $task)) {
    http_response_code(404);
    echo 'کار یافت نشد.';
    exit;
}
$hist = work360_rows($conn, 'SELECT h.*, u.full_name FROM dbo.work360_task_status_history h LEFT JOIN dbo.work360_users u ON u.user_id=h.changed_by_user_id WHERE h.task_id=? ORDER BY h.history_id DESC', [$id]);
$fus = work360_rows($conn, 'SELECT f.*, u.full_name FROM dbo.work360_task_followups f LEFT JOIN dbo.work360_users u ON u.user_id=f.followup_by_user_id WHERE f.task_id=? ORDER BY f.followup_id DESC', [$id]);
work360_layout_start('جزئیات کار', 'task-view.php');
?>
<div class="m360-card">
  <h3><?= work360_h((string)$task['title']) ?> <span class="m360-badge"><?= work360_h((string)$task['task_code']) ?></span></h3>
  <p>واحد: <?= work360_h((string)$task['department_name_fa']) ?></p>
  <p>مسئول: <?= work360_h((string)($task['assignee_name'] ?? '—')) ?> · سرپرست: <?= work360_h((string)($task['supervisor_name'] ?? '—')) ?></p>
  <p>وضعیت: <?= work360_h(work360_status_fa((string)$task['status_code'])) ?> · اولویت: <?= work360_h(work360_priority_fa((string)$task['priority_code'])) ?></p>
  <p>سررسید: <?= work360_h((string)$task['due_date']) ?> <?= work360_h((string)($task['due_time'] ?? '')) ?></p>
  <p>منبع: <?= work360_h((string)($task['source_type'] ?? 'MANUAL')) ?> · ارجاع: <?= work360_h((string)($task['reference_text'] ?? '—')) ?></p>
  <p>نتیجه مورد انتظار: <?= work360_h((string)($task['expected_result'] ?? '—')) ?></p>
  <p><?= nl2br(work360_h((string)($task['description'] ?? ''))) ?></p>
  <div class="w360-actions">
    <a class="m360-btn m360-btn-secondary" href="task-status.php?id=<?= $id ?>">تغییر وضعیت</a>
    <a class="m360-btn m360-btn-secondary" href="task-followup.php?id=<?= $id ?>">ثبت پیگیری</a>
    <a class="m360-btn m360-btn-secondary" href="task-edit.php?id=<?= $id ?>">ویرایش</a>
    <a class="m360-btn m360-btn-secondary" href="my-tasks.php">بازگشت</a>
  </div>
</div>
<div class="m360-card">
  <h3>تاریخچه وضعیت</h3>
  <table class="m360-table"><thead><tr><th>از</th><th>به</th><th>توسط</th><th>یادداشت</th><th>زمان</th></tr></thead><tbody>
  <?php foreach ($hist as $h): ?>
  <tr><td><?= work360_h(work360_status_fa((string)($h['old_status_code'] ?? '—'))) ?></td><td><?= work360_h(work360_status_fa((string)$h['new_status_code'])) ?></td><td><?= work360_h((string)($h['full_name'] ?? '')) ?></td><td><?= work360_h((string)($h['note'] ?? '')) ?></td><td><?= work360_h((string)$h['created_at']) ?></td></tr>
  <?php endforeach; ?>
  <?php if ($hist === []): ?><tr><td colspan="5">موردی نیست.</td></tr><?php endif; ?>
  </tbody></table>
</div>
<div class="m360-card">
  <h3>پیگیری‌ها</h3>
  <table class="m360-table"><thead><tr><th>نوع</th><th>یادداشت</th><th>توسط</th><th>پیگیری بعدی</th><th>زمان</th></tr></thead><tbody>
  <?php foreach ($fus as $f): ?>
  <tr><td><?= work360_h((string)$f['followup_type']) ?></td><td><?= work360_h((string)$f['note']) ?></td><td><?= work360_h((string)($f['full_name'] ?? '')) ?></td><td><?= work360_h((string)($f['next_followup_date'] ?? '—')) ?></td><td><?= work360_h((string)$f['created_at']) ?></td></tr>
  <?php endforeach; ?>
  <?php if ($fus === []): ?><tr><td colspan="5">موردی نیست.</td></tr><?php endif; ?>
  </tbody></table>
</div>
<?php work360_layout_end(); ?>
