<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rules = p360_legal_rules_list($conn);
p360_layout_start('مرکز قوانین', 'legal-rule-center.php');
echo p360_legal_disclaimer_html();
p360_table($rules, ['rule_code'=>'کد','rule_title'=>'عنوان','status'=>'وضعیت','effective_from'=>'از']);
echo '<p><a href="legal-rule-form.php">ثبت قانون جدید</a> | <a href="legal-text-upload.php">بارگذاری متن</a></p>';
p360_layout_end();