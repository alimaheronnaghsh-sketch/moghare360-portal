<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
$id=(int)($_GET["id"]??0); if(($_SERVER["REQUEST_METHOD"]??"")==="POST"){inv360_csrf_require(); if(($_POST["action"]??"")==="post"){ $res=inv360_post_document($conn,$id,$uid); $ok=!empty($res["ok"]); $msg=(string)$res["message"]; }}
$doc=inv360_stock_doc_get($conn,$id); $lines=inv360_stock_doc_lines($conn,$id);
inv360_layout_start('مشاهده سند','stock-documents.php');
inv360_flash_render($msg,$ok);
?>
<?php if(!$doc): ?><div class="m360-alert m360-alert-err">سند یافت نشد.</div><?php inv360_layout_end(); exit; endif; ?>
<p>شماره: <strong><?= inv360_h($doc['DocNo']) ?></strong> | وضعیت: <?= inv360_h(inv360_status_fa($doc['DocStatus'])) ?></p>
<?php if(strtolower($doc['DocStatus'])!=='posted'): ?>
<form method="post"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="post"><button type="submit">ثبت قطعی</button></form>
<?php else: ?><p class="muted">سند ثبت‌شده تغییرناپذیر است.</p><?php endif; ?>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>کالا</th><th>تعداد</th><th>بها</th></tr></thead><tbody>
<?php foreach($lines as $l): ?><tr><td><?= inv360_h((string)($l['ItemName']??$l['PartID'])) ?></td><td><?= inv360_h((string)$l['Qty']) ?></td><td><?= inv360_h((string)($l['UnitCost']??'')) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();