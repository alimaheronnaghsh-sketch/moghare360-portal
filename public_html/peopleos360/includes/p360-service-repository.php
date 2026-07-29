<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php'; require_once __DIR__.'/p360-workflow.php';
function p360_leave_request($conn,array $d,int $uid): array {
  $emp=(int)$d['employee_id']; $from=$d['from_at']??''; $to=$d['to_at']??'';
  if($emp<1||$from===''||$to==='') return ['ok'=>false,'message'=>'فیلدها ناقص است'];
  $mins=max(1,(int)((strtotime($to)-strtotime($from))/60));
  p360_exec($conn,'INSERT INTO dbo.p360_leave_requests (employee_id,leave_type,from_at,to_at,minutes_count,reason_text,request_status) VALUES (?,?,?,?,?,?,N\'submitted\')',
    [$emp,$d['leave_type']??'annual',$from,$to,$mins,$d['reason_text']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_leave_requests ORDER BY id DESC',[])??0);
  p360_task_create($conn,'leave_approval','leave_request',(string)$id,'تأیید مرخصی',$uid);
  p360_audit($conn,'LEAVE',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'درخواست مرخصی ثبت شد'];
}
function p360_mission_request($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_mission_requests (employee_id,destination,from_at,to_at,reason_text,request_status) VALUES (?,?,?,?,?,N\'submitted\')',
    [(int)$d['employee_id'],$d['destination']??null,$d['from_at'],$d['to_at'],$d['reason_text']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_mission_requests ORDER BY id DESC',[])??0);
  p360_task_create($conn,'mission_approval','mission_request',(string)$id,'تأیید مأموریت',$uid);
  p360_audit($conn,'MISSION',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'مأموریت ثبت شد'];
}
function p360_overtime_request($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_overtime_requests (employee_id,work_date,overtime_minutes,reason_text,request_status) VALUES (?,?,?,?,N\'submitted\')',
    [(int)$d['employee_id'],$d['work_date'],(int)$d['overtime_minutes'],$d['reason_text']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_overtime_requests ORDER BY id DESC',[])??0);
  p360_task_create($conn,'overtime_approval','overtime_request',(string)$id,'تأیید اضافه‌کاری',$uid);
  p360_audit($conn,'OT',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'اضافه‌کاری ثبت شد'];
}
function p360_att_correction($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_employee_requests (employee_id,request_type,request_body,request_status) VALUES (?,N\'attendance_correction\',?,N\'submitted\')',[(int)$d['employee_id'],$d['request_body']??'']);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_employee_requests ORDER BY id DESC',[])??0);
  p360_task_create($conn,'att_correction','employee_request',(string)$id,'اصلاح تردد',$uid);
  p360_audit($conn,'ATT_CORR',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'درخواست اصلاح تردد ثبت شد'];
}
function p360_announce($conn,string $title,string $body,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_announcements (title,body_text,created_by) VALUES (?,?,?)',[$title,$body,$uid]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_announcements ORDER BY id DESC',[])??0);
  p360_audit($conn,'ANNOUNCE',(string)$id,'CREATED',$title,$uid); return ['ok'=>true,'id'=>$id,'message'=>'ابلاغ ثبت شد'];
}
function p360_ack($conn,int $annId,int $empId): array {
  p360_exec($conn,'INSERT INTO dbo.p360_acknowledgments (announcement_id,employee_id,acknowledged_at) VALUES (?,?,SYSUTCDATETIME())',[$annId,$empId]);
  return ['ok'=>true,'message'=>'تأیید دریافت ثبت شد'];
}