<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
p360_layout_start('راه‌اندازی شرکت', 'company-setup.php');
echo '<p>گام‌های اولیه: شرکت، شعب، واحدها و سمت‌ها.</p><div class="steps"><a href="companies.php">شرکت</a><a href="branches.php">شعب</a><a href="departments.php">واحدها</a><a href="positions.php">سمت‌ها</a></div>';
p360_layout_end();