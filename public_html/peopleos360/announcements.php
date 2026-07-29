<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_announcement_add($conn, (string)$_POST['title'], (string)$_POST['body_text'], $uid);
$rows = p360_announcements_list($conn);
p360_layout_start('اطلاعیه', 'announcements.php');
p360_table($rows, ['title'=>'عنوان','created_at'=>'تاریخ']);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>عنوان</label><input name="title"><label>متن</label><textarea name="body_text"></textarea><button>انتشار</button></form>';
p360_layout_end();