<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_rewards_list($conn);
p360_layout_start('پاداش', 'performance-reviews.php');
p360_table($rows, ['employee_id'=>'پرسنل','reward_title'=>'عنوان','reward_amount'=>'مبلغ']);
p360_layout_end();