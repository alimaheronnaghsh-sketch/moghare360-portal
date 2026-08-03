<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_disciplinary_list($conn);
p360_layout_start('انضباطی', 'performance-reviews.php');
p360_table($rows, ['employee_id'=>'پرسنل','action_type'=>'نوع']);
p360_layout_end();