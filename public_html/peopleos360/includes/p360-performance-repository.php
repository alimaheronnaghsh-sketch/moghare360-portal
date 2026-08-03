<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_kpi_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_kpi_definitions (kpi_code,kpi_title,weight_pct) VALUES (?,?,?)',[$d['kpi_code']??('KPI'.random_int(10,99)),$d['kpi_title']??'شاخص',(float)($d['weight_pct']??10)]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_kpi_definitions ORDER BY id DESC',[])??0);
  p360_audit($conn,'KPI',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'KPI ثبت شد'];
}
function p360_review_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_performance_reviews (employee_id,review_period,total_score,review_status) VALUES (?,?,?,N\'done\')',[(int)$d['employee_id'],$d['review_period']??date('Y-m'),(float)($d['total_score']??0)]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_performance_reviews ORDER BY id DESC',[])??0);
  if(((int)($d['kpi_id']??0))>0) p360_exec($conn,'INSERT INTO dbo.p360_performance_review_items (review_id,kpi_id,score,note_text) VALUES (?,?,?,?)',[$id,(int)$d['kpi_id'],(float)($d['total_score']??0),$d['note_text']??null]);
  p360_audit($conn,'REVIEW',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'ارزیابی ثبت شد'];
}
function p360_training_record($conn,array $d,int $uid): array {
  if(((int)($d['course_id']??0))<1){ p360_exec($conn,'INSERT INTO dbo.p360_training_courses (course_code,course_title) VALUES (?,?)',['TR'.random_int(100,999),$d['course_title']??'آموزش']); $d['course_id']=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_training_courses ORDER BY id DESC',[])??0); }
  p360_exec($conn,'INSERT INTO dbo.p360_training_records (employee_id,course_id,completed_at,score) VALUES (?,?,?,?)',[(int)$d['employee_id'],(int)$d['course_id'],$d['completed_at']??date('Y-m-d'),(float)($d['score']??0)]);
  return ['ok'=>true,'message'=>'آموزش ثبت شد'];
}
function p360_reward($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_rewards (employee_id,reward_title,reward_amount) VALUES (?,?,?)',[(int)$d['employee_id'],$d['reward_title']??'تشویق',(float)($d['reward_amount']??0)]);
  return ['ok'=>true,'message'=>'تشویق ثبت شد'];
}
function p360_discipline($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_disciplinary_actions (employee_id,action_type,action_text) VALUES (?,?,?)',[(int)$d['employee_id'],$d['action_type']??'warning',$d['action_text']??'']);
  return ['ok'=>true,'message'=>'اقدام انضباطی ثبت شد'];
}