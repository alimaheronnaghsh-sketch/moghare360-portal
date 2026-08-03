<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$rows = p360_workflow_pending($conn, $uid);
p360_layout_start('صندوق ورودی', 'inbox.php');
p360_table($rows, ['title'=>'عنوان','task_type'=>'نوع','entity_id'=>'شناسه','task_status'=>'وضعیت']);
p360_layout_end();