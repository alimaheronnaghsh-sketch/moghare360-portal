<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
p360_layout_start('گزارش', 'reports.php');
echo '<div class="kpi-grid"><div class="kpi"><span>پرسنل</span><strong>'.p360_report_headcount($conn).'</strong></div></div>';
p360_layout_end();