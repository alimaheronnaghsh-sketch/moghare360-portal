<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_audit_list($conn, 100);
p360_layout_start('Audit', 'audit-log.php');
p360_table($rows, ['created_at'=>'زمان','event_name'=>'رویداد','entity_type'=>'موجودیت','entity_id'=>'شناسه','actor_username'=>'کاربر']);
p360_layout_end();