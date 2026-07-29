<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_employees($conn){return p360_rows($conn,'SELECT TOP 200 * FROM dbo.p360_employees WHERE is_active=1 ORDER BY employee_id DESC',[]);}
function p360_employee_get($conn,int $id): ?array {return p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_employees WHERE employee_id=?',[$id]);}
function p360_employee_search($conn,string $q): array {
  $q=trim($q); if($q==='') return []; $like='%'.$q.'%';
  return p360_rows($conn,'SELECT TOP 50 * FROM dbo.p360_employees WHERE is_active=1 AND (employee_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR national_code LIKE ? OR mobile LIKE ?) ORDER BY employee_id DESC',[$like,$like,$like,$like,$like]);
}
function p360_employee_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['employee_code']??'')); $fn=trim((string)($d['first_name']??'')); $ln=trim((string)($d['last_name']??''));
  if($code===''||$fn===''||$ln==='') return ['ok'=>false,'message'=>'کد و نام و نام خانوادگی الزامی است.'];
  p360_exec($conn,'INSERT INTO dbo.p360_employees (employee_code,first_name,last_name,national_code,mobile,email,company_id,branch_id,department_id,position_id,employment_type_id,hire_date,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
    [$code,$fn,$ln,$d['national_code']??null,$d['mobile']??null,$d['email']??null,((int)($d['company_id']??0))?:null,((int)($d['branch_id']??0))?:null,((int)($d['department_id']??0))?:null,((int)($d['position_id']??0))?:null,((int)($d['employment_type_id']??0))?:null,($d['hire_date']??'')!==''?$d['hire_date']:null,$uid]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 employee_id FROM dbo.p360_employees WHERE employee_code=?',[$code])??0);
  if(((float)($d['base_salary']??0))>0) p360_salary_change($conn,$id,(float)$d['base_salary'],$d['hire_date']??date('Y-m-d'),'حقوق اولیه',$uid);
  if(((int)($d['position_id']??0))>0) p360_position_change($conn,$id,(int)$d['position_id'],$d['hire_date']??date('Y-m-d'),'سمت اولیه',$uid);
  p360_audit($conn,'EMPLOYEE',(string)$id,'CREATED',$fn.' '.$ln,$uid); return ['ok'=>true,'message'=>'کارمند ثبت شد.','employee_id'=>$id];
}
function p360_salary_change($conn,int $empId,float $salary,string $from,string $reason,int $uid): array {
  p360_exec($conn,'UPDATE dbo.p360_employee_salary_history SET effective_to=? WHERE employee_id=? AND effective_to IS NULL',[date('Y-m-d',strtotime($from.' -1 day')),$empId]);
  p360_exec($conn,'INSERT INTO dbo.p360_employee_salary_history (employee_id,base_salary,effective_from,change_reason,created_by) VALUES (?,?,?,?,?)',[$empId,$salary,$from,$reason,$uid]);
  p360_audit($conn,'SALARY',(string)$empId,'CHANGED',(string)$salary,$uid); return ['ok'=>true,'message'=>'تاریخچه حقوق ثبت شد (بدون بازنویسی).'];
}
function p360_position_change($conn,int $empId,int $posId,string $from,string $reason,int $uid): array {
  $pos=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_positions WHERE position_id=?',[$posId]);
  p360_exec($conn,'UPDATE dbo.p360_employee_position_history SET effective_to=? WHERE employee_id=? AND effective_to IS NULL',[date('Y-m-d',strtotime($from.' -1 day')),$empId]);
  p360_exec($conn,'INSERT INTO dbo.p360_employee_position_history (employee_id,position_id,position_title,department_id,effective_from,change_reason,created_by) VALUES (?,?,?,?,?,?,?)',
    [$empId,$posId,$pos['position_title']??null,$pos['department_id']??null,$from,$reason,$uid]);
  p360_exec($conn,'UPDATE dbo.p360_employees SET position_id=?, department_id=? WHERE employee_id=?',[$posId,$pos['department_id']??null,$empId]);
  p360_audit($conn,'POSITION_HIST',(string)$empId,'CHANGED',(string)$posId,$uid); return ['ok'=>true,'message'=>'تاریخچه سمت ثبت شد (بدون بازنویسی).'];
}
function p360_salary_history($conn,int $empId){return p360_rows($conn,'SELECT * FROM dbo.p360_employee_salary_history WHERE employee_id=? ORDER BY effective_from DESC',[$empId]);}
function p360_position_history($conn,int $empId){return p360_rows($conn,'SELECT * FROM dbo.p360_employee_position_history WHERE employee_id=? ORDER BY effective_from DESC',[$empId]);}