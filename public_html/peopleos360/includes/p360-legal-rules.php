<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_legal_categories($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_legal_rule_categories WHERE is_active=1 ORDER BY id',[]);}
function p360_legal_rules($conn){return p360_rows($conn,'SELECT TOP 100 r.*, c.category_name FROM dbo.p360_legal_rules r LEFT JOIN dbo.p360_legal_rule_categories c ON c.id=r.category_id ORDER BY r.id DESC',[]);}
function p360_legal_rule_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['rule_code']??'')); $title=trim((string)($d['rule_title']??'')); $from=$d['effective_from']??'';
  if($code===''||$title===''||$from==='') return ['ok'=>false,'message'=>'کد، عنوان و تاریخ مؤثر الزامی است.'];
  p360_exec($conn,'INSERT INTO dbo.p360_legal_rules (category_id,rule_code,rule_title,rule_text,law_year,effective_from,effective_to,source_title,source_reference,status,version_no) VALUES (?,?,?,?,?,?,?,?,?,N\'active\',1)',
    [((int)($d['category_id']??0))?:null,$code,$title,$d['rule_text']??null,((int)($d['law_year']??0))?:null,$from,($d['effective_to']??'')!==''?$d['effective_to']:null,$d['source_title']??'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی',$d['source_reference']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_legal_rules WHERE rule_code=? ORDER BY id DESC',[$code])??0);
  p360_audit($conn,'LEGAL_RULE',(string)$id,'CREATED',$title,$uid); return ['ok'=>true,'message'=>'قانون ثبت شد.','id'=>$id];
}
function p360_employment_types($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_employment_types WHERE is_active=1 ORDER BY id',[]);}
function p360_contract_types($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_contract_types WHERE is_active=1 ORDER BY id',[]);}
function p360_law_year_create($conn,array $d,int $uid): array {
  $y=(int)($d['law_year']??0); if($y<1300) return ['ok'=>false,'message'=>'سال قانون نامعتبر'];
  p360_exec($conn,'INSERT INTO dbo.p360_labor_law_years (law_year,title,effective_from,effective_to,status) VALUES (?,?,?,?,N\'sample\')',[$y,$d['title']??('سال '.$y),$d['effective_from']??date('Y-m-d'),($d['effective_to']??'')!==''?$d['effective_to']:null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_labor_law_years WHERE law_year=?',[$y])??0);
  p360_audit($conn,'LAW_YEAR',(string)$id,'CREATED',(string)$y,$uid); return ['ok'=>true,'id'=>$id,'message'=>'سال قانونی ثبت شد (نمونه آزمایشی).'];
}
function p360_payroll_component_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_payroll_legal_components (law_year_id,component_code,component_title,component_type,calculation_method,amount,percent_value,taxable_flag,insurance_subject_flag,effective_from,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,1)',
    [((int)($d['law_year_id']??0))?:null,$d['component_code']??('C'.random_int(100,999)),$d['component_title']??'مؤلفه',$d['component_type']??'earning',$d['calculation_method']??'fixed',(float)($d['amount']??0),(float)($d['percent_value']??0),!empty($d['taxable_flag'])?1:0,!empty($d['insurance_subject_flag'])?1:0,$d['effective_from']??date('Y-m-d')]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_payroll_legal_components ORDER BY id DESC',[])??0);
  p360_audit($conn,'PAY_COMP',(string)$id,'CREATED',$d['component_title']??'',$uid); return ['ok'=>true,'id'=>$id,'message'=>'مؤلفه حقوقی ثبت شد.'];
}
function p360_leave_rule_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_leave_rule_definitions (rule_code,leave_type,title,entitlement_method,entitlement_minutes,requires_document,requires_approval,effective_from,is_active) VALUES (?,?,?,?,?,?,?,?,1)',
    [$d['rule_code']??('LR'.random_int(100,999)),$d['leave_type']??'annual',$d['title']??'مرخصی',$d['entitlement_method']??'fixed',((int)($d['entitlement_minutes']??0))?:null,!empty($d['requires_document'])?1:0,1,$d['effective_from']??date('Y-m-d')]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_leave_rule_definitions ORDER BY id DESC',[])??0);
  p360_audit($conn,'LEAVE_RULE',(string)$id,'CREATED',$d['title']??'',$uid); return ['ok'=>true,'id'=>$id,'message'=>'قانون مرخصی ثبت شد.'];
}