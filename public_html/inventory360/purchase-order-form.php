<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$id=(int)($_GET['id']??0); $sup=inv360_suppliers_list($conn); $items=inv360_items_list($conn,100);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  if(($_POST['action']??'')==='create'){
    $res=inv360_po_create($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message'];
    if($ok){header('Location: purchase-order-form.php?id='.(int)$res['po_id']);exit;}
  } elseif(($_POST['action']??'')==='add_line'){
    $res=inv360_po_add_line($conn,(int)$_POST['po_id'],$_POST); $ok=!empty($res['ok']); $msg=(string)$res['message']; $id=(int)$_POST['po_id'];
  }
}
$po=$id?inv360_po_get($conn,$id):null; $lines=$id?inv360_po_lines($conn,$id):[];
inv360_layout_start('فرم سفارش خرید','purchase-orders.php'); inv360_flash_render($msg,$ok);
if(!$po):
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="create">
<label>تأمین‌کننده<select name="supplier_id"><?php foreach($sup as $s): ?><option value="<?= (int)$s['SupplierID'] ?>"><?= inv360_h($s['SupplierName']) ?></option><?php endforeach; ?></select></label>
<label>مرجع PR<input name="pr_ref"></label>
<label>مرجع RFQ<input name="rfq_ref"></label>
<label>محل تحویل<input name="delivery_place"></label>
<label>زمان تحویل<input name="delivery_time"></label>
<label>شرایط پرداخت<input name="payment_terms"></label>
<label>گارانتی<input name="warranty"></label>
<label>جریمه تأخیر<input name="delay_penalty"></label>
<label>مسئول<input name="responsible_person"></label>
<label>ارز<input name="currency" value="IRR"></label>
<label>نرخ ارز<input type="number" step="0.0001" name="exchange_rate" value="1"></label>
<button type="submit">ایجاد PO</button></form>
<?php else: ?>
<div class="panel"><p>شماره: <strong><?= inv360_h($po['PONo']) ?></strong> — وضعیت: <?= inv360_h(inv360_status_fa($po['POStatus'])) ?></p></div>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="add_line"><input type="hidden" name="po_id" value="<?= $id ?>">
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>قیمت واحد<input type="number" step="0.01" name="unit_price" required></label>
<label>تخفیف<input type="number" step="0.01" name="discount" value="0"></label>
<label>مالیات<input type="number" step="0.01" name="tax" value="0"></label>
<button type="submit">افزودن خط</button></form>
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>سفارش</th><th>دریافت‌شده</th><th>باقیمانده</th></tr></thead><tbody>
<?php foreach($lines as $ln): $rem=(float)$ln['QtyOrdered']-(float)$ln['QtyReceived']; ?>
<tr><td><?= inv360_h((string)($ln['ItemName']??$ln['ItemText']??'')) ?></td><td><?= inv360_h((string)$ln['QtyOrdered']) ?></td><td><?= inv360_h((string)$ln['QtyReceived']) ?></td><td><?= inv360_h((string)$rem) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; inv360_layout_end();