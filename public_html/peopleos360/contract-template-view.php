<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$id = (int)($_GET['id'] ?? 0);
$row = $id ? p360_one($conn, 'SELECT TOP 1 * FROM dbo.p360_contract_templates WHERE id=?', [$id]) : null;
p360_layout_start('نمایش قالب', 'contract-templates.php');
if ($row) echo '<pre class="m360-card m360-form">'.p360_h($row['template_body']).'</pre>'; else echo '<p>قالب یافت نشد.</p>';
p360_layout_end();