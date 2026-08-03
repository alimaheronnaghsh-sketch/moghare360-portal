<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_companies($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_companies WHERE is_active=1 ORDER BY company_id DESC',[]);}
function p360_company_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['company_code']??'')); $name=trim((string)($d['company_name']??''));
  if($code===''||$name==='') return ['ok'=>false,'message'=>'کد و نام شرکت الزامی است.'];
  if(p360_exec($conn,'INSERT INTO dbo.p360_companies (company_code,company_name,legal_name,tax_id,created_by) VALUES (?,?,?,?,?)',[$code,$name,$d['legal_name']??null,$d['tax_id']??null,$uid])===false) return ['ok'=>false,'message'=>'ثبت شرکت ناموفق'];
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 company_id FROM dbo.p360_companies WHERE company_code=?',[$code])??0);
  p360_audit($conn,'COMPANY',(string)$id,'CREATED',$name,$uid); return ['ok'=>true,'message'=>'شرکت ثبت شد.','id'=>$id];
}
function p360_branch_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['branch_code']??'')); $name=trim((string)($d['branch_name']??''));
  if($code===''||$name==='') return ['ok'=>false,'message'=>'کد و نام شعبه الزامی است.'];
  p360_exec($conn,'INSERT INTO dbo.p360_branches (company_id,branch_code,branch_name) VALUES (?,?,?)',[((int)($d['company_id']??0))?:null,$code,$name]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 branch_id FROM dbo.p360_branches WHERE branch_code=?',[$code])??0);
  p360_audit($conn,'BRANCH',(string)$id,'CREATED',$name,$uid); return ['ok'=>true,'message'=>'شعبه ثبت شد.','id'=>$id];
}
function p360_dept_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['department_code']??'')); $name=trim((string)($d['department_name']??''));
  if($code===''||$name==='') return ['ok'=>false,'message'=>'کد و نام دپارتمان الزامی است.'];
  p360_exec($conn,'INSERT INTO dbo.p360_departments (company_id,department_code,department_name) VALUES (?,?,?)',[((int)($d['company_id']??0))?:null,$code,$name]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 department_id FROM dbo.p360_departments WHERE department_code=?',[$code])??0);
  p360_audit($conn,'DEPT',(string)$id,'CREATED',$name,$uid); return ['ok'=>true,'message'=>'دپارتمان ثبت شد.','id'=>$id];
}
function p360_position_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['position_code']??'')); $title=trim((string)($d['position_title']??''));
  if($code===''||$title==='') return ['ok'=>false,'message'=>'کد و عنوان سمت الزامی است.'];
  p360_exec($conn,'INSERT INTO dbo.p360_positions (department_id,position_code,position_title) VALUES (?,?,?)',[((int)($d['department_id']??0))?:null,$code,$title]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 position_id FROM dbo.p360_positions WHERE position_code=?',[$code])??0);
  p360_audit($conn,'POSITION',(string)$id,'CREATED',$title,$uid); return ['ok'=>true,'message'=>'سمت ثبت شد.','id'=>$id];
}
function p360_branches($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_branches WHERE is_active=1 ORDER BY branch_id DESC',[]);}
function p360_departments($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_departments WHERE is_active=1 ORDER BY department_id DESC',[]);}
function p360_positions($conn){return p360_rows($conn,'SELECT * FROM dbo.p360_positions WHERE is_active=1 ORDER BY position_id DESC',[]);}