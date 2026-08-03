<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_vacancy_list($conn);
p360_layout_start('آگهی‌ها', 'recruitment.php');
p360_table($rows, ['vacancy_code'=>'کد','title'=>'عنوان','vacancy_status'=>'وضعیت']);
p360_layout_end();