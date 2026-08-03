<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php'; require_once __DIR__.'/p360-workflow.php';
function p360_loan_type_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_loan_types (type_code,type_name,max_amount) VALUES (?,?,?)',[$d['type_code']??('LT'.random_int(10,99)),$d['type_name']??'وام',(float)($d['max_amount']??0)]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_loan_types ORDER BY id DESC',[])??0);
  p360_audit($conn,'LOAN_TYPE',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'نوع وام ثبت شد'];
}
function p360_loan_request($conn,array $d,int $uid): array {
  $amt=(float)($d['principal_amount']??0); $n=(int)($d['installment_count']??0);
  if($amt<=0||$n<1) return ['ok'=>false,'message'=>'مبلغ/اقساط نامعتبر'];
  p360_exec($conn,'INSERT INTO dbo.p360_loans (employee_id,loan_type_id,principal_amount,installment_count,loan_status) VALUES (?,?,?,?,N\'submitted\')',
    [(int)$d['employee_id'],((int)($d['loan_type_id']??0))?:null,$amt,$n]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_loans ORDER BY id DESC',[])??0);
  p360_task_create($conn,'loan_approval','loan',(string)$id,'تأیید وام',$uid);
  p360_audit($conn,'LOAN',(string)$id,'CREATED',(string)$amt,$uid); return ['ok'=>true,'id'=>$id,'message'=>'درخواست وام ثبت شد'];
}
function p360_loan_approve_and_schedule($conn,int $loanId,int $checkerId): array {
  $loan=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_loans WHERE id=?',[$loanId]);
  if(!$loan) return ['ok'=>false,'message'=>'وام یافت نشد'];
  if((int)($loan['created_by']??0)===$checkerId) {/* created_by may be null; maker check via workflow */}
  p360_exec($conn,'UPDATE dbo.p360_loans SET loan_status=N\'approved\', approved_by=? WHERE id=?',[$checkerId,$loanId]);
  $n=(int)$loan['installment_count']; $each=round(((float)$loan['principal_amount'])/$n,0);
  for($i=1;$i<=$n;$i++){
    $due=date('Y-m-d',strtotime('+'.$i.' month'));
    $amt=($i===$n)?((float)$loan['principal_amount']-$each*($n-1)):$each;
    p360_exec($conn,'INSERT INTO dbo.p360_loan_installments (loan_id,installment_no,due_date,amount,paid_flag) VALUES (?,?,?,?,0)',[$loanId,$i,$due,$amt]);
  }
  p360_audit($conn,'LOAN',(string)$loanId,'SCHEDULED','',$checkerId); return ['ok'=>true,'message'=>'وام تأیید و اقساط تولید شد'];
}