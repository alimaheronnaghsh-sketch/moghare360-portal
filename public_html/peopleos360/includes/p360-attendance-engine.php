<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php';
function p360_device_create($conn,array $d,int $uid): array {
  $code=trim((string)($d['device_code']??'')); $name=trim((string)($d['device_name']??''));
  if($code===''||$name==='') return ['ok'=>false,'message'=>'کد و نام دستگاه الزامی'];
  p360_exec($conn,'INSERT INTO dbo.p360_attendance_devices (device_code,device_name,site_id) VALUES (?,?,?)',[$code,$name,((int)($d['site_id']??0))?:null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_attendance_devices WHERE device_code=?',[$code])??0);
  p360_audit($conn,'ATT_DEVICE',(string)$id,'CREATED',$name,$uid); return ['ok'=>true,'id'=>$id,'message'=>'دستگاه ثبت شد'];
}
function p360_raw_punch($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_raw_attendance_logs (device_id,employee_id,punch_at,punch_type,raw_payload) VALUES (?,?,?,?,?)',
    [((int)($d['device_id']??0))?:null,(int)$d['employee_id'],$d['punch_at']??date('Y-m-d H:i:s'),$d['punch_type']??'in',$d['raw_payload']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_raw_attendance_logs ORDER BY id DESC',[])??0);
  p360_audit($conn,'RAW_ATT',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'تردد خام ثبت شد'];
}
function p360_calc_attendance($conn,int $empId,string $date,int $uid): array {
  $logs=p360_rows($conn,'SELECT * FROM dbo.p360_raw_attendance_logs WHERE employee_id=? AND CONVERT(date,punch_at)=? ORDER BY punch_at',[$empId,$date]);
  $in=null; $out=null; foreach($logs as $l){ if(($l['punch_type']??'in')==='in' && !$in) $in=$l['punch_at']; if(($l['punch_type']??'')==='out') $out=$l['punch_at']; }
  $mins=0; if($in&&$out) $mins=max(0,(int)((strtotime($out)-strtotime($in))/60));
  $late=0; if($in){ $start=strtotime($date.' 08:00:00'); $late=max(0,(int)((strtotime($in)-$start)/60)); }
  $ot=max(0,$mins-480);
  p360_exec($conn,'DELETE FROM dbo.p360_attendance_records WHERE employee_id=? AND work_date=?',[$empId,$date]);
  p360_exec($conn,'INSERT INTO dbo.p360_attendance_records (employee_id,work_date,in_at,out_at,worked_minutes,late_minutes,overtime_minutes,record_status) VALUES (?,?,?,?,?,?,?,N\'calculated\')',[$empId,$date,$in,$out,$mins,$late,$ot]);
  p360_audit($conn,'ATT_CALC',(string)$empId,'CALCULATED',$date,$uid); return ['ok'=>true,'worked'=>$mins,'late'=>$late,'ot'=>$ot,'message'=>'کارکرد محاسبه شد'];
}
function p360_shift_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_shifts (shift_code,shift_name,start_time,end_time,required_minutes) VALUES (?,?,?,?,?)',
    [$d['shift_code']??('SH'.random_int(10,99)),$d['shift_name']??'شیفت',$d['start_time']??'08:00',$d['end_time']??'17:00',(int)($d['required_minutes']??480)]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_shifts ORDER BY id DESC',[])??0);
  p360_audit($conn,'SHIFT',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'شیفت ثبت شد'];
}
function p360_jalali_year_create($conn,array $d,int $uid): array {
  $y=(int)($d['jalali_year']??1404);
  p360_exec($conn,'INSERT INTO dbo.p360_jalali_calendar_years (jalali_year,starts_on_gregorian,ends_on_gregorian,status) VALUES (?,?,?,N\'sample\')',[$y,$d['starts_on_gregorian']??'2025-03-21',$d['ends_on_gregorian']??'2026-03-20']);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_jalali_calendar_years WHERE jalali_year=?',[$y])??0);
  // seed a few sample days
  for($i=0;$i<7;$i++){
    $g=date('Y-m-d',strtotime(($d['starts_on_gregorian']??'2025-03-21')." +$i day"));
    $dow=(int)date('N',strtotime($g));
    $type=$dow===5?'friday':'workday';
    p360_exec($conn,'INSERT INTO dbo.p360_jalali_calendar_days (calendar_year_id,jalali_date,gregorian_date,weekday_name,day_type,title,required_minutes) VALUES (?,?,?,?,?,?,?)',
      [$id,sprintf('%d/01/%02d',$y,$i+1),$g,date('D',strtotime($g)),$type,'نمونه آزمایشی؛ نیازمند تأیید مدیر',$type==='workday'?480:0]);
  }
  p360_audit($conn,'JALALI_YEAR',(string)$id,'CREATED',(string)$y,$uid); return ['ok'=>true,'id'=>$id,'message'=>'تقویم شمسی نمونه ثبت شد'];
}
function p360_timesheet_create($conn,int $empId,int $periodId,int $uid): array {
  $sum=(int)(p360_scalar($conn,'SELECT ISNULL(SUM(worked_minutes),0) FROM dbo.p360_attendance_records WHERE employee_id=?',[$empId])??0);
  $ot=(int)(p360_scalar($conn,'SELECT ISNULL(SUM(overtime_minutes),0) FROM dbo.p360_attendance_records WHERE employee_id=?',[$empId])??0);
  p360_exec($conn,'INSERT INTO dbo.p360_timesheets (employee_id,period_id,worked_minutes,overtime_minutes,timesheet_status) VALUES (?,?,?,?,N\'submitted\')',[$empId,$periodId,$sum,$ot]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_timesheets ORDER BY id DESC',[])??0);
  require_once __DIR__.'/p360-workflow.php';
  p360_task_create($conn,'timesheet_approval','timesheet',(string)$id,'تأیید کارکرد',$uid);
  p360_audit($conn,'TIMESHEET',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'تایم‌شیت ثبت و به کارتابل ارسال شد'];
}