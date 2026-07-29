<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_audit_list($conn,100);
inv360_layout_start('Audit','audit-log.php');
?>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>زمان</th><th>رویداد</th><th>موجودیت</th><th>کاربر</th><th>جزئیات</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
<td><?= inv360_h((string)$r['CreatedAt']) ?></td>
<td><?= inv360_h((string)$r['EventCode']) ?></td>
<td><?= inv360_h((string)($r['EntityType']??'').' #'.(string)($r['EntityID']??'')) ?></td>
<td><?= inv360_h((string)($r['UserName']??$r['CreatedByUserID']??'')) ?></td>
<td><?= inv360_h((string)($r['DetailText']??'')) ?></td>
</tr><?php endforeach; if(!$rows) echo '<tr><td colspan="5">رکوردی نیست</td></tr>'; ?>
</tbody></table></div>
<?php inv360_layout_end();