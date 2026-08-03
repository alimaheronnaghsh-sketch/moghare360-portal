<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_equipment_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_equipment (equipment_code,equipment_name,serial_no) VALUES (?,?,?)',[$d['equipment_code']??('EQ'.random_int(100,999)),$d['equipment_name']??'تجهیز',$d['serial_no']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_equipment ORDER BY id DESC',[])??0);
  p360_audit($conn,'EQUIP',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'تجهیز ثبت شد'];
}
function p360_equipment_issue($conn,int $eqId,int $empId,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_equipment_assignments (equipment_id,employee_id,issued_at,assignment_status) VALUES (?,?,SYSUTCDATETIME(),N\'issued\')',[$eqId,$empId]);
  p360_audit($conn,'EQUIP',(string)$eqId,'ISSUED',(string)$empId,$uid); return ['ok'=>true,'message'=>'تحویل تجهیزات ثبت شد'];
}
function p360_equipment_return($conn,int $assignmentId,int $uid): array {
  p360_exec($conn,'UPDATE dbo.p360_equipment_assignments SET returned_at=SYSUTCDATETIME(), assignment_status=N\'returned\' WHERE id=?',[$assignmentId]);
  p360_audit($conn,'EQUIP',(string)$assignmentId,'RETURNED','',$uid); return ['ok'=>true,'message'=>'بازگشت تجهیزات ثبت شد'];
}
function p360_exit_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_exit_cases (employee_id,exit_type,exit_date,exit_status) VALUES (?,?,?,N\'open\')',[(int)$d['employee_id'],$d['exit_type']??'resignation',$d['exit_date']??date('Y-m-d')]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_exit_cases ORDER BY id DESC',[])??0);
  foreach(['تسویه سنوات','تسویه مرخصی','تسویه وام','برگشت تجهیزات','مفاصاحساب داخلی'] as $t){
    p360_exec($conn,'INSERT INTO dbo.p360_exit_clearance_items (exit_case_id,item_title,cleared_flag) VALUES (?,?,0)',[$id,$t]);
  }
  p360_audit($conn,'EXIT',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'پرونده خروج و چک‌لیست تسویه ایجاد شد'];
}
function p360_exit_clear_item($conn,int $itemId,int $uid): array {
  p360_exec($conn,'UPDATE dbo.p360_exit_clearance_items SET cleared_flag=1, cleared_at=SYSUTCDATETIME() WHERE id=?',[$itemId]);
  return ['ok'=>true,'message'=>'آیتم تسویه تأیید شد'];
}