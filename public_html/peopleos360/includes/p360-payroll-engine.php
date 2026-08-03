<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_period_create($conn,array $d,int $uid): array {
  $code=$d['period_code']??('P'.gmdate('Ym').random_int(10,99));
  p360_exec($conn,'INSERT INTO dbo.p360_payroll_periods (period_code,jalali_year,jalali_month,start_date,end_date,period_status) VALUES (?,?,?,?,?,N\'open\')',
    [$code,(int)($d['jalali_year']??1404),(int)($d['jalali_month']??1),$d['start_date']??date('Y-m-01'),$d['end_date']??date('Y-m-t')]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_payroll_periods WHERE period_code=?',[$code])??0);
  p360_audit($conn,'PAY_PERIOD',(string)$id,'CREATED',$code,$uid); return ['ok'=>true,'id'=>$id,'message'=>'دوره حقوقی ثبت شد'];
}
function p360_payroll_calc($conn,int $empId,int $periodId,int $uid): array {
  $sal=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_employee_salary_history WHERE employee_id=? AND effective_to IS NULL ORDER BY effective_from DESC',[$empId]);
  $base=(float)($sal['base_salary']??0);
  $loanDed=(float)(p360_scalar($conn,'SELECT ISNULL(SUM(amount),0) FROM dbo.p360_loan_installments WHERE paid_flag=0 AND loan_id IN (SELECT id FROM dbo.p360_loans WHERE employee_id=? AND loan_status=N\'approved\')',[$empId])??0);
  // take one installment if any
  $inst=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_loan_installments WHERE paid_flag=0 AND loan_id IN (SELECT id FROM dbo.p360_loans WHERE employee_id=? AND loan_status=N\'approved\') ORDER BY installment_no',[$empId]);
  $ded=$inst?(float)$inst['amount']:0;
  $gross=$base; $net=$gross-$ded;
  p360_exec($conn,'INSERT INTO dbo.p360_payroll_slips (employee_id,period_id,gross_amount,deduction_amount,net_amount,slip_status) VALUES (?,?,?,?,?,N\'calculated\')',[$empId,$periodId,$gross,$ded,$net]);
  $sid=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_payroll_slips ORDER BY id DESC',[])??0);
  p360_exec($conn,'INSERT INTO dbo.p360_payroll_items (slip_id,component_code,component_title,item_type,amount) VALUES (?,?,?,?,?)',[$sid,'BASE','حقوق پایه','earning',$gross]);
  if($ded>0){
    p360_exec($conn,'INSERT INTO dbo.p360_payroll_items (slip_id,component_code,component_title,item_type,amount) VALUES (?,?,?,?,?)',[$sid,'LOAN','قسط وام','deduction',$ded]);
    p360_exec($conn,'UPDATE dbo.p360_loan_installments SET paid_flag=1, payroll_period_id=? WHERE id=?',[$periodId,(int)$inst['id']]);
  }
  p360_audit($conn,'PAYSLIP',(string)$sid,'CALCULATED',(string)$net,$uid); return ['ok'=>true,'slip_id'=>$sid,'gross'=>$gross,'net'=>$net,'message'=>'حقوق محاسبه شد'];
}
function p360_payslip_get($conn,int $id): ?array {return p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_payroll_slips WHERE id=?',[$id]);}
function p360_payslip_items($conn,int $slipId){return p360_rows($conn,'SELECT * FROM dbo.p360_payroll_items WHERE slip_id=?',[$slipId]);}