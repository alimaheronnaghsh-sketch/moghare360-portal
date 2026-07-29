<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_rows($conn, 'SELECT id, template_code, template_title, version_no, status FROM dbo.p360_contract_templates ORDER BY id DESC', []);
p360_layout_start('قالب‌های قرارداد', 'contract-templates.php');
p360_table($rows, ['template_code'=>'کد','template_title'=>'عنوان','version_no'=>'نسخه','status'=>'وضعیت']);
echo '<a href="contract-template-form.php">قالب جدید</a>';
p360_layout_end();