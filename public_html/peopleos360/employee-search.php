<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$q = (string)($_GET['q'] ?? '');
$rows = $q !== '' ? p360_search_employees($conn, $q) : [];
p360_layout_start('جستجوی پرسنل', 'employee-search.php');
echo '<form method="get"><input name="q" value="'.p360_h($q).'"><button>جستجو</button></form>';
p360_table($rows, ['employee_code'=>'کد','first_name'=>'نام','last_name'=>'نام خانوادگی','mobile'=>'موبایل']);
p360_layout_end();