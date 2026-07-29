<?php
require_once __DIR__.'/p360-db.php'; require_once __DIR__.'/p360-audit.php'; require_once __DIR__.'/p360-employee-repository.php';
function p360_manpower_create($conn,array $d,int $uid): array {
  $no='MR-'.gmdate('YmdHis');
  p360_exec($conn,'INSERT INTO dbo.p360_manpower_requests (request_no,department_id,position_id,headcount,reason_text,request_status,created_by) VALUES (?,?,?,?,?,N\'submitted\',?)',
    [$no,((int)($d['department_id']??0))?:null,((int)($d['position_id']??0))?:null,(int)($d['headcount']??1),$d['reason_text']??null,$uid]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_manpower_requests WHERE request_no=?',[$no])??0);
  p360_audit($conn,'MANPOWER',(string)$id,'CREATED',$no,$uid); return ['ok'=>true,'id'=>$id,'message'=>'درخواست نیرو ثبت شد'];
}
function p360_vacancy_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_vacancies (vacancy_code,title,department_id,position_id,vacancy_status) VALUES (?,?,?,?,N\'open\')',
    [$d['vacancy_code']??('V'.random_int(100,999)),$d['title']??'موقعیت',((int)($d['department_id']??0))?:null,((int)($d['position_id']??0))?:null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_vacancies ORDER BY id DESC',[])??0);
  p360_audit($conn,'VACANCY',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'موقعیت شغلی ثبت شد'];
}
function p360_candidate_create($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_candidates (vacancy_id,full_name,mobile,email,candidate_status) VALUES (?,?,?,?,N\'new\')',
    [((int)($d['vacancy_id']??0))?:null,$d['full_name']??'',$d['mobile']??null,$d['email']??null]);
  $id=(int)(p360_scalar($conn,'SELECT TOP 1 id FROM dbo.p360_candidates ORDER BY id DESC',[])??0);
  p360_audit($conn,'CANDIDATE',(string)$id,'CREATED','',$uid); return ['ok'=>true,'id'=>$id,'message'=>'کاندید ثبت شد'];
}
function p360_interview($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_candidate_interviews (candidate_id,interview_at,interviewer,score,notes) VALUES (?,?,?,?,?)',
    [(int)$d['candidate_id'],$d['interview_at']??date('Y-m-d H:i:s'),$d['interviewer']??null,(float)($d['score']??0),$d['notes']??null]);
  p360_exec($conn,'UPDATE dbo.p360_candidates SET candidate_status=N\'interviewed\' WHERE id=?',[(int)$d['candidate_id']]);
  return ['ok'=>true,'message'=>'مصاحبه ثبت شد'];
}
function p360_offer($conn,array $d,int $uid): array {
  p360_exec($conn,'INSERT INTO dbo.p360_job_offers (candidate_id,offer_salary,offer_status) VALUES (?,?,N\'sent\')',[(int)$d['candidate_id'],(float)($d['offer_salary']??0)]);
  p360_exec($conn,'UPDATE dbo.p360_candidates SET candidate_status=N\'offered\' WHERE id=?',[(int)$d['candidate_id']]);
  return ['ok'=>true,'message'=>'پیشنهاد همکاری ثبت شد'];
}
function p360_candidate_to_employee($conn,int $candidateId,array $d,int $uid): array {
  $c=p360_one($conn,'SELECT TOP 1 * FROM dbo.p360_candidates WHERE id=?',[$candidateId]);
  if(!$c) return ['ok'=>false,'message'=>'کاندید یافت نشد'];
  $parts=preg_split('/\s+/',trim((string)$c['full_name']),2);
  $res=p360_employee_create($conn,[
    'employee_code'=>$d['employee_code']??('E'.gmdate('His').random_int(10,99)),
    'first_name'=>$parts[0]??'نام','last_name'=>$parts[1]??'نام‌خانوادگی',
    'mobile'=>$c['mobile']??null,'email'=>$c['email']??null,
    'company_id'=>$d['company_id']??null,'department_id'=>$d['department_id']??null,'position_id'=>$d['position_id']??null,
    'hire_date'=>$d['hire_date']??date('Y-m-d'),'base_salary'=>$d['base_salary']??0,
  ],$uid);
  if(!empty($res['ok'])) p360_exec($conn,'UPDATE dbo.p360_candidates SET candidate_status=N\'hired\' WHERE id=?',[$candidateId]);
  return $res;
}