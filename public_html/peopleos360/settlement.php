<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_exit_clearance_items ORDER BY id DESC', []);
p360_layout_start('تسویه', 'settlement.php');
echo p360_legal_disclaimer_html();
p360_table($rows, ['item_title'=>'آیتم','cleared_flag'=>'تسویه']);
p360_layout_end();