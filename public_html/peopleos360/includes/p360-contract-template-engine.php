<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_templates($conn){return p360_rows($conn,'SELECT TOP 100 * FROM dbo.p360_contract_templates ORDER BY id DESC',[]);}
function p360_template_get($conn,int $id): ?array {return p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_contract_templates WHERE id=?',[$id]);}
function p360_template_save($conn,array $d,int $uid): array {
  $code=trim((string)($d['template_code']??'')); $title=trim((string)($d['template_title']??'')); $body=(string)($d['template_body']??'');
  if($code===''||$title===''||trim($body)==='') return ['ok'=>false,'message'=>'کد، عنوان و متن قالب الزامی است.'];
  $vars=json_encode(['employee_name'=>'','national_code'=>'','position'=>'','department'=>'','salary'=>'','contract_start'=>'','contract_end'=>'','workplace'=>'','work_hours'=>'','leave_rules'=>'','insurance_rules'=>'','settlement_rules'=>''], JSON_UNESCAPED_UNICODE);
  p360_exec($conn,'INSERT INTO dbo.p360_contract_templates (template_code,template_title,employment_type_id,contract_type_id,company_id,template_body,variables_json,effective_from,version_no,status,confidentiality_level,created_by) VALUES (?,?,?,?,?,?,?,?,1,N\'active\',?,?)',
    [$code,$title,((int)($d['employment_type_id']??0))?:null,((int)($d['contract_type_id']??0))?:null,((int)($d['company_id']??0))?:null,$body,$vars,$d['effective_from']??date('Y-m-d'),$d['confidentiality_level']??'internal',$uid]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_contract_templates WHERE template_code=?',[$code])??0);
  p360_exec($conn,'INSERT INTO dbo.p360_contract_template_versions (template_id,version_no,template_body_snapshot,variables_json_snapshot,change_reason,created_by) VALUES (?,1,?,?,N\'ایجاد اولیه\',?)',[$id,$body,$vars,$uid]);
  p360_audit($conn,'CONTRACT_TPL',(string)$id,'CREATED',$title,$uid); return ['ok'=>true,'message'=>'قالب قرارداد ثبت شد.','id'=>$id];
}
function p360_template_update_body($conn,int $id,string $body,string $reason,int $uid): array {
  $t=p360_template_get($conn,$id); if(!$t) return ['ok'=>false,'message'=>'قالب یافت نشد'];
  $ver=(int)$t['version_no']+1;
  p360_exec($conn,'INSERT INTO dbo.p360_contract_template_versions (template_id,version_no,template_body_snapshot,variables_json_snapshot,change_reason,created_by) VALUES (?,?,?,?,?,?)',[$id,$ver,$body,$t['variables_json'],$reason,$uid]);
  p360_exec($conn,'UPDATE dbo.p360_contract_templates SET template_body=?, version_no=? WHERE id=?',[$body,$ver,$id]);
  p360_audit($conn,'CONTRACT_TPL',(string)$id,'VERSIONED',(string)$ver,$uid); return ['ok'=>true,'message'=>'نسخه جدید قالب ثبت شد. قراردادهای قبلی تغییر نمی‌کنند.'];
}
function p360_generate_contract($conn,int $empId,int $tplId,array $vars,int $uid): array {
  $emp=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_employees WHERE employee_id=?',[$empId]);
  $tpl=p360_template_get($conn,$tplId);
  if(!$emp||!$tpl) return ['ok'=>false,'message'=>'کارمند یا قالب یافت نشد'];
  $map=array_merge([
    'employee_name'=>trim($emp['first_name'].' '.$emp['last_name']),
    'national_code'=>(string)($emp['national_code']??''),
  ],$vars);
  $body=(string)$tpl['template_body'];
  foreach($map as $k=>$v) $body=str_replace('{{'.$k.'}}',(string)$v,$body);
  $no='CNT-'.gmdate('YmdHis').'-'.random_int(10,99);
  p360_exec($conn,'INSERT INTO dbo.p360_employee_contract_snapshots (employee_id,template_id,contract_no,contract_body_snapshot,variables_snapshot_json,start_date,end_date,status) VALUES (?,?,?,?,?,?,?,N\'issued\')',
    [$empId,$tplId,$no,$body,json_encode($map,JSON_UNESCAPED_UNICODE),$vars['contract_start']??date('Y-m-d'),($vars['contract_end']??'')!==''?$vars['contract_end']:null]);
  $sid=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_employee_contract_snapshots WHERE contract_no=?',[$no])??0);
  p360_exec($conn,'INSERT INTO dbo.p360_employee_contracts (employee_id,contract_type_id,template_id,contract_no,start_date,end_date,contract_status) VALUES (?,?,?,?,?,?,N\'active\')',
    [$empId,$tpl['contract_type_id']??null,$tplId,$no,$vars['contract_start']??date('Y-m-d'),($vars['contract_end']??'')!==''?$vars['contract_end']:null]);
  p360_audit($conn,'CONTRACT_SNAP',(string)$sid,'CREATED',$no,$uid); return ['ok'=>true,'message'=>'قرارداد از قالب تولید و snapshot ذخیره شد.','snapshot_id'=>$sid,'contract_no'=>$no,'body'=>$body];
}
function p360_snapshot_get($conn,int $id): ?array {return p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_employee_contract_snapshots WHERE id=?',[$id]);}