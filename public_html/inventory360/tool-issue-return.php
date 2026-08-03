<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $mode=$_POST['mode']??'issue';
  if($mode==='create'){ $res=inv360_asset_create($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; if($ok){header('Location: tools-assets.php');exit;} }
  elseif($mode==='issue'){ $res=inv360_asset_issue($conn,(int)$_POST['asset_id'],$_POST['assigned_person']??'',$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; }
  elseif($mode==='return'){ $res=inv360_asset_return($conn,(int)$_POST['asset_id'],$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; }
}
$assets=inv360_assets_list($conn);
inv360_layout_start('تحویل / برگشت ابزار','tools-assets.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?><input type="hidden" name="mode" value="issue">
<label>ابزار<select name="asset_id"><?php foreach($assets as $a): ?><option value="<?= (int)$a['AssetID'] ?>"><?= inv360_h($a['AssetCode'].' — '.$a['AssetName']) ?></option><?php endforeach; ?></select></label>
<label>تحویل‌گیرنده<input name="assigned_person" required></label>
<button type="submit">تحویل</button></form>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?><input type="hidden" name="mode" value="return">
<label>ابزار<select name="asset_id"><?php foreach($assets as $a): ?><option value="<?= (int)$a['AssetID'] ?>"><?= inv360_h($a['AssetCode'].' — '.$a['AssetName']) ?></option><?php endforeach; ?></select></label>
<button type="submit">برگشت</button></form>
<?php inv360_layout_end();