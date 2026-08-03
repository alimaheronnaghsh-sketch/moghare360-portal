<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_training_courses($conn);
p360_layout_start('آموزش', 'training.php');
p360_table($rows, ['course_code'=>'کد','course_title'=>'عنوان']);
p360_layout_end();