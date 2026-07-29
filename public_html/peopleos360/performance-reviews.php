<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_performance_reviews ORDER BY id DESC', []);
p360_layout_start('عملکرد', 'performance-reviews.php');
p360_table($rows, ['employee_id'=>'پرسنل','review_period'=>'دوره','total_score'=>'امتیاز']);
p360_layout_end();