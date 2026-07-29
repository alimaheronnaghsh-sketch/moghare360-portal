<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn=p360_db();
$uid=(int)(p360_current_user()['user_id']??0);
$msg=null;$ok=true;
if($_SERVER['REQUEST_METHOD']==='POST'&&p360_csrf_verify()){
 p360_branch_save($conn,$_POST,$uid);
 $msg='ثبت شد.';
}
$rows=p360_branch_list($conn);
p360_layout_start('شعب','branches.php');
p360_flash($msg,$ok);
p360_table($rows,['branch_code'=>'کد','branch_name'=>'نام','company_id'=>'مرجع']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field()."<label>کد</label><input name='branch_code' required><label>نام</label><input name='branch_name' required><label>company_id</label><input name='company_id'><button>ثبت</button></form>";
p360_layout_end();
