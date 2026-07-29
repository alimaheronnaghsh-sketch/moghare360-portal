<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_employment_types_list($conn);
p360_layout_start('انواع همکاری', 'employment-types.php');
echo p360_legal_disclaimer_html();
p360_table($rows, ['type_code'=>'کد','type_name'=>'نام','relation_type'=>'رابطه','legal_warning'=>'هشدار']);
p360_layout_end();