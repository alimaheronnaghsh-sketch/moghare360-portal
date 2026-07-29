<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
p360_layout_start('خودخدمت', 'self-service.php');
echo '<div class="steps"><a href="leave-requests.php">مرخصی</a><a href="mission-requests.php">مأموریت</a><a href="overtime-requests.php">اضافه‌کار</a></div>';
p360_layout_end();