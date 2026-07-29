<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    if (isset($_POST['leave_id'])) {
        $r = p360_leave_approve($conn, (int)$_POST['leave_id'], (int)$_POST['task_id'], $uid);
    } else {
        $r = p360_workflow_approve($conn, (int)$_POST['task_id'], $uid, $_POST['note'] ?? null);
    }
    $ok = !empty($r['ok']); $msg = $r['message'] ?? '';
}
$rows = p360_workflow_pending($conn, null);
p360_layout_start('تأییدها', 'approvals.php');
p360_flash($msg, $ok);
foreach ($rows as $t) {
    echo '<form class="card" method="post" style="margin-bottom:.5rem">'.p360_csrf_field();
    echo '<div>#' . (int)$t['id'] . ' — ' . p360_h($t['title']) . '</div>';
    echo '<input type="hidden" name="task_id" value="'.(int)$t['id'].'">';
    if ($t['entity_type']==='leave_request') echo '<input type="hidden" name="leave_id" value="'.p360_h($t['entity_id']).'">';
    echo '<button name="action" value="approve">تأیید</button></form>';
}
p360_layout_end();