<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
$rows=inv360_stock_docs_list($conn);
inv360_layout_start('اسناد انبار','stock-documents.php');
inv360_flash_render($msg,$ok);
?>
<p><a class="btn" href="stock-document-form.php">سند جدید</a></p>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>شماره</th><th>نوع</th><th>وضعیت</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= inv360_h($r['DocNo']) ?></td><td><?= inv360_h($r['DocType']) ?></td><td><?= inv360_h(inv360_status_fa($r['DocStatus'])) ?></td><td><a href="stock-document-view.php?id=<?= (int)$r['DocumentID'] ?>">مشاهده</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();