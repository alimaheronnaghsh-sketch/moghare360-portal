<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
p360_layout_start('جذب', 'recruitment.php');
echo '<div class="steps"><a href="manpower-requests.php">درخواست نیرو</a><a href="vacancies.php">آگهی</a><a href="candidates.php">کاندید</a></div>';
p360_layout_end();