<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/crm360-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-reception-board.php');
    exit;
}

crm360_csrf_require();

$tab = preg_replace('/[^a-z_]/', '', (string)($_POST['return_tab'] ?? 'dashboard')) ?: 'dashboard';
$action = (string)($_POST['action'] ?? '');
$actorInfo = crm360_actor();
$actor = $actorInfo['actor'];

function crm360_f(string $k, $default = ''): string
{
    return trim((string)($_POST[$k] ?? $default));
}

function crm360_fi(string $k): int
{
    return (int)($_POST[$k] ?? 0);
}

function crm360_redirect(string $tab, string $type, string $msg): void
{
    $_SESSION['crm360_flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: erp-reception-board.php?tab=' . urlencode($tab));
    exit;
}

try {
    $conn = crm360_db();
} catch (Throwable $e) {
    crm360_redirect($tab, 'err', 'اتصال پایگاه داده برقرار نشد.');
}

try {
    switch ($action) {
        case 'create_customer': {
            $name = crm360_f('full_name');
            $mobile = crm360_f('mobile');
            if ($name === '') {
                crm360_redirect($tab, 'err', 'نام مشتری الزامی است.');
            }
            $dup = null;
            if ($mobile !== '') {
                $dup = crm360_one($conn, 'SELECT customer_profile_id, full_name FROM dbo.crm360_customer_profiles WHERE mobile=?', [$mobile]);
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_customer_profiles (full_name, mobile, national_id, customer_type, consent_sms, consent_marketing, source_channel, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
                [
                    $name,
                    $mobile !== '' ? $mobile : null,
                    crm360_f('national_id') ?: null,
                    crm360_f('customer_type', 'PERSON') ?: 'PERSON',
                    crm360_f('consent_sms') === '1' ? 1 : 0,
                    crm360_f('consent_marketing') === '1' ? 1 : 0,
                    crm360_f('source_channel') ?: null,
                    crm360_f('notes') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ثبت مشتری ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(customer_profile_id) FROM dbo.crm360_customer_profiles WHERE full_name=?', [$name]);
            crm360_ensure_club_row($conn, (int)$id);
            crm360_audit($conn, 'CREATE', 'crm360_customer_profiles', $id, null, ['full_name' => $name, 'mobile' => $mobile]);
            $msg = 'مشتری ثبت شد.';
            if ($dup) {
                $msg = 'هشدار: موبایل تکراری (' . ($dup['full_name'] ?? '') . ') — مشتری جدید با همان موبایل ثبت شد.';
                crm360_redirect($tab, 'warn', $msg);
            }
            crm360_redirect($tab, 'ok', $msg);
        }

        case 'create_vehicle': {
            $custId = crm360_fi('customer_profile_id');
            $brand = crm360_f('brand');
            $model = crm360_f('model');
            if ($custId <= 0 || $brand === '' || $model === '') {
                crm360_redirect($tab, 'err', 'مشتری، برند و مدل الزامی است.');
            }
            if (!in_array($brand, crm360_brands(), true)) {
                crm360_redirect($tab, 'err', 'برند مجاز نیست.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_vehicle_profiles (customer_profile_id, plate_no, vin, brand, model, model_year, color, mileage, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    $custId,
                    crm360_f('plate_no') ?: null,
                    crm360_f('vin') ?: null,
                    $brand,
                    $model,
                    crm360_fi('model_year') ?: null,
                    crm360_f('color') ?: null,
                    crm360_fi('mileage') ?: null,
                    crm360_f('notes') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ثبت خودرو ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(vehicle_profile_id) FROM dbo.crm360_vehicle_profiles WHERE customer_profile_id=?', [$custId]);
            crm360_audit($conn, 'CREATE', 'crm360_vehicle_profiles', $id, null, ['brand' => $brand, 'model' => $model]);
            crm360_redirect($tab, 'ok', 'خودرو ثبت شد.');
        }

        case 'create_case': {
            $custId = crm360_fi('customer_profile_id');
            $vehId = crm360_fi('vehicle_profile_id');
            if ($custId <= 0 || $vehId <= 0) {
                crm360_redirect($tab, 'err', 'مشتری و خودرو الزامی است.');
            }
            $reqId = crm360_f('existing_request_id');
            $existingReqId = $reqId !== '' ? (int)$reqId : null;
            $caseCode = crm360_next_code($conn, 'RC', 'crm360_reception_cases', 'case_id');
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_reception_cases (case_code, existing_request_id, customer_profile_id, vehicle_profile_id, case_type, service_type, reception_source, responsible_staff, assigned_staff, case_status, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,N\'DRAFT\',?,?)',
                [
                    $caseCode,
                    $existingReqId,
                    $custId,
                    $vehId,
                    crm360_f('case_type', 'WALKIN') ?: 'WALKIN',
                    crm360_f('service_type') ?: null,
                    crm360_f('reception_source') ?: null,
                    crm360_f('responsible_staff') ?: null,
                    crm360_f('assigned_staff') ?: null,
                    crm360_f('notes') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد پرونده پذیرش ناموفق بود.');
            }
            $caseId = (int)crm360_scalar($conn, 'SELECT case_id FROM dbo.crm360_reception_cases WHERE case_code=?', [$caseCode]);
            crm360_generate_docs_checklist($conn, $caseId, $actor);
            crm360_ensure_club_row($conn, $custId);
            crm360_sync_case_completion($conn, $caseId);
            crm360_audit($conn, 'CREATE', 'crm360_reception_cases', (string)$caseId, null, ['case_code' => $caseCode, 'existing_request_id' => $existingReqId]);
            crm360_redirect($tab, 'ok', 'پرونده پذیرش ' . $caseCode . ' ایجاد شد.');
        }

        case 'update_case_fields': {
            $caseId = crm360_fi('case_id');
            if ($caseId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه پرونده نامعتبر است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_reception_cases WHERE case_id=?', [$caseId]);
            $ok = crm360_exec(
                $conn,
                'UPDATE dbo.crm360_reception_cases SET case_type=?, service_type=?, responsible_staff=?, assigned_staff=?, case_status=?, notes=?, updated_at=SYSUTCDATETIME() WHERE case_id=?',
                [
                    crm360_f('case_type') ?: ($before['case_type'] ?? 'WALKIN'),
                    crm360_f('service_type') ?: null,
                    crm360_f('responsible_staff') ?: null,
                    crm360_f('assigned_staff') ?: null,
                    crm360_f('case_status') ?: ($before['case_status'] ?? 'DRAFT'),
                    crm360_f('notes') ?: null,
                    $caseId,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'به‌روزرسانی پرونده ناموفق بود.');
            }
            crm360_sync_case_completion($conn, $caseId);
            crm360_audit($conn, 'UPDATE', 'crm360_reception_cases', (string)$caseId, $before, ['updated' => true]);
            crm360_redirect($tab, 'ok', 'پرونده به‌روزرسانی شد.');
        }

        case 'generate_docs_checklist': {
            $caseId = crm360_fi('case_id');
            if ($caseId <= 0 || !crm360_generate_docs_checklist($conn, $caseId, $actor)) {
                crm360_redirect($tab, 'err', 'ایجاد چک‌لیست مدارک ناموفق بود.');
            }
            crm360_audit($conn, 'CREATE', 'crm360_case_documents', (string)$caseId, null, ['checklist' => 'generated']);
            crm360_redirect($tab, 'ok', 'چک‌لیست مدارک ایجاد/تکمیل شد.');
        }

        case 'update_document_status': {
            $docId = crm360_fi('document_id');
            $status = strtoupper(crm360_f('document_status', 'MISSING'));
            $allowed = ['MISSING', 'UPLOADED', 'VERIFIED', 'REJECTED'];
            if ($docId <= 0 || !in_array($status, $allowed, true)) {
                crm360_redirect($tab, 'err', 'وضعیت مدرک نامعتبر است.');
            }
            $fileRef = crm360_f('file_ref_text');
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_case_documents WHERE document_id=?', [$docId]);
            $verifiedBy = $status === 'VERIFIED' ? $actor : null;
            $verifiedAt = $status === 'VERIFIED' ? date('Y-m-d H:i:s') : null;
            $ok = crm360_exec(
                $conn,
                'UPDATE dbo.crm360_case_documents SET document_status=?, file_ref_text=?, verified_by=?, verified_at=? WHERE document_id=?',
                [$status, $fileRef !== '' ? $fileRef : null, $verifiedBy, $verifiedAt, $docId]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'به‌روزرسانی مدرک ناموفق بود.');
            }
            if ($before && !empty($before['case_id'])) {
                crm360_sync_case_completion($conn, (int)$before['case_id']);
            }
            crm360_audit($conn, 'UPDATE', 'crm360_case_documents', (string)$docId, $before, [
                'status' => $status,
                'file_ref_text' => $fileRef,
                'note' => 'DOCUMENT_VAULT_INTEGRATION_PENDING',
            ]);
            crm360_redirect($tab, 'ok', 'وضعیت مدرک به‌روز شد. (DOCUMENT_VAULT_INTEGRATION_PENDING)');
        }

        case 'create_cartable': {
            $custId = crm360_fi('customer_profile_id');
            $title = crm360_f('item_title');
            if ($custId <= 0 || $title === '') {
                crm360_redirect($tab, 'err', 'مشتری و عنوان کارتابل الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_customer_cartable (customer_profile_id, vehicle_profile_id, case_id, item_type, item_title, item_status, due_date, priority_code, assigned_to, source_type, created_by) VALUES (?,?,?,?,?,N\'OPEN\',?,?,?,?,?)',
                [
                    $custId,
                    crm360_fi('vehicle_profile_id') ?: null,
                    crm360_fi('case_id') ?: null,
                    crm360_f('item_type', 'FOLLOWUP') ?: 'FOLLOWUP',
                    $title,
                    crm360_f('due_date') ?: null,
                    crm360_f('priority_code', 'NORMAL') ?: 'NORMAL',
                    crm360_f('assigned_to') ?: null,
                    crm360_f('source_type') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد کارتابل ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(cartable_id) FROM dbo.crm360_customer_cartable WHERE customer_profile_id=?', [$custId]);
            crm360_audit($conn, 'CREATE', 'crm360_customer_cartable', $id, null, ['title' => $title]);
            crm360_redirect($tab, 'ok', 'آیتم کارتابل ایجاد شد.');
        }

        case 'cartable_done': {
            $cartId = crm360_fi('cartable_id');
            if ($cartId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه کارتابل نامعتبر است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_customer_cartable WHERE cartable_id=?', [$cartId]);
            crm360_exec($conn, "UPDATE dbo.crm360_customer_cartable SET item_status=N'DONE', updated_at=SYSUTCDATETIME() WHERE cartable_id=?", [$cartId]);
            crm360_audit($conn, 'UPDATE', 'crm360_customer_cartable', (string)$cartId, $before, ['item_status' => 'DONE']);
            crm360_redirect($tab, 'ok', 'کارتابل بسته شد.');
        }

        case 'create_survey': {
            $custId = crm360_fi('customer_profile_id');
            if ($custId <= 0) {
                crm360_redirect($tab, 'err', 'مشتری الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_satisfaction_surveys (case_id, customer_profile_id, vehicle_profile_id, survey_channel, overall_score, survey_status, created_by) VALUES (?,?,?,?,0,N\'DRAFT\',?)',
                [
                    crm360_fi('case_id') ?: null,
                    $custId,
                    crm360_fi('vehicle_profile_id') ?: null,
                    crm360_f('survey_channel', 'MANUAL') ?: 'MANUAL',
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد نظرسنجی ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(survey_id) FROM dbo.crm360_satisfaction_surveys WHERE customer_profile_id=?', [$custId]);
            crm360_audit($conn, 'CREATE', 'crm360_satisfaction_surveys', $id, null, ['status' => 'DRAFT']);
            crm360_redirect($tab, 'ok', 'پیش‌نویس نظرسنجی ایجاد شد.');
        }

        case 'submit_survey': {
            $surveyId = crm360_fi('survey_id');
            $score = crm360_fi('overall_score');
            if ($surveyId <= 0 || $score < 1 || $score > 10) {
                crm360_redirect($tab, 'err', 'شناسه نظرسنجی و امتیاز (۱–۱۰) الزامی است.');
            }
            $survey = crm360_one($conn, 'SELECT * FROM dbo.crm360_satisfaction_surveys WHERE survey_id=?', [$surveyId]);
            if (!$survey) {
                crm360_redirect($tab, 'err', 'نظرسنجی یافت نشد.');
            }
            $status = $score <= 3 ? 'NEEDS_FOLLOWUP' : 'SUBMITTED';
            crm360_exec(
                $conn,
                'UPDATE dbo.crm360_satisfaction_surveys SET overall_score=?, nps_score=?, reception_score=?, technical_score=?, comment=?, survey_status=?, submitted_at=SYSUTCDATETIME() WHERE survey_id=?',
                [
                    $score,
                    crm360_fi('nps_score') ?: null,
                    crm360_fi('reception_score') ?: null,
                    crm360_fi('technical_score') ?: null,
                    crm360_f('comment') ?: null,
                    $status,
                    $surveyId,
                ]
            );
            if ($score <= 3) {
                crm360_exec(
                    $conn,
                    'INSERT INTO dbo.crm360_customer_cartable (customer_profile_id, vehicle_profile_id, case_id, item_type, item_title, item_status, priority_code, source_type, created_by) VALUES (?,?,?,?,?,N\'OPEN\',N\'HIGH\',N\'SATISFACTION\',?)',
                    [
                        (int)$survey['customer_profile_id'],
                        $survey['vehicle_profile_id'] ?? null,
                        $survey['case_id'] ?? null,
                        'SATISFACTION_FOLLOWUP',
                        'پیگیری رضایت — امتیاز ' . $score,
                        $actor,
                    ]
                );
                if (crm360_f('create_complaint') === '1') {
                    $cmpCode = crm360_next_code($conn, 'CMP', 'crm360_complaints', 'complaint_id');
                    crm360_exec(
                        $conn,
                        'INSERT INTO dbo.crm360_complaints (complaint_code, case_id, customer_profile_id, vehicle_profile_id, complaint_type, severity, title, description, complaint_status, created_by) VALUES (?,?,?,?,?,?,?,?,N\'OPEN\',?)',
                        [
                            $cmpCode,
                            $survey['case_id'] ?? null,
                            (int)$survey['customer_profile_id'],
                            $survey['vehicle_profile_id'] ?? null,
                            'SERVICE',
                            'HIGH',
                            'شکایت ناشی از رضایت پایین',
                            crm360_f('comment') ?: 'امتیاز کلی: ' . $score,
                            $actor,
                        ]
                    );
                }
            }
            crm360_audit($conn, 'UPDATE', 'crm360_satisfaction_surveys', (string)$surveyId, $survey, ['overall_score' => $score, 'status' => $status]);
            crm360_redirect($tab, 'ok', 'نظرسنجی ثبت شد.' . ($score <= 3 ? ' پیگیری کارتابل ایجاد شد.' : ''));
        }

        case 'create_complaint': {
            $custId = crm360_fi('customer_profile_id');
            $title = crm360_f('title');
            $desc = crm360_f('description');
            if ($custId <= 0 || $title === '' || $desc === '') {
                crm360_redirect($tab, 'err', 'مشتری، عنوان و شرح شکایت الزامی است.');
            }
            $cmpCode = crm360_next_code($conn, 'CMP', 'crm360_complaints', 'complaint_id');
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_complaints (complaint_code, case_id, customer_profile_id, vehicle_profile_id, complaint_type, severity, title, description, complaint_status, created_by) VALUES (?,?,?,?,?,?,?,?,N\'OPEN\',?)',
                [
                    $cmpCode,
                    crm360_fi('case_id') ?: null,
                    $custId,
                    crm360_fi('vehicle_profile_id') ?: null,
                    crm360_f('complaint_type', 'SERVICE') ?: 'SERVICE',
                    crm360_f('severity', 'MEDIUM') ?: 'MEDIUM',
                    $title,
                    $desc,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ثبت شکایت ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT complaint_id FROM dbo.crm360_complaints WHERE complaint_code=?', [$cmpCode]);
            crm360_audit($conn, 'CREATE', 'crm360_complaints', $id, null, ['title' => $title]);
            crm360_redirect($tab, 'ok', 'شکایت ' . $cmpCode . ' ثبت شد.');
        }

        case 'update_complaint_status': {
            $cmpId = crm360_fi('complaint_id');
            $status = strtoupper(crm360_f('complaint_status'));
            $correction = crm360_f('correction_action');
            if ($cmpId <= 0 || $status === '') {
                crm360_redirect($tab, 'err', 'شناسه و وضعیت شکایت الزامی است.');
            }
            if ($status === 'CLOSED' && $correction === '') {
                crm360_redirect($tab, 'err', 'بستن شکایت نیازمند اقدام اصلاحی است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_complaints WHERE complaint_id=?', [$cmpId]);
            $closedAt = $status === 'CLOSED' ? date('Y-m-d H:i:s') : null;
            crm360_exec(
                $conn,
                'UPDATE dbo.crm360_complaints SET complaint_status=?, correction_action=?, corrective_owner=?, closed_at=?, updated_at=SYSUTCDATETIME() WHERE complaint_id=?',
                [$status, $correction ?: null, crm360_f('corrective_owner') ?: null, $closedAt, $cmpId]
            );
            crm360_audit($conn, 'UPDATE', 'crm360_complaints', (string)$cmpId, $before, ['status' => $status]);
            crm360_redirect($tab, 'ok', 'وضعیت شکایت به‌روز شد.');
        }

        case 'update_club': {
            $clubId = crm360_fi('club_id');
            if ($clubId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه باشگاه نامعتبر است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_customer_club WHERE club_id=?', [$clubId]);
            crm360_exec(
                $conn,
                'UPDATE dbo.crm360_customer_club SET tier_code=?, points_balance=?, visit_count=?, churn_risk_level=?, updated_at=SYSUTCDATETIME() WHERE club_id=?',
                [
                    crm360_f('tier_code') ?: ($before['tier_code'] ?? 'NEW'),
                    crm360_fi('points_balance'),
                    crm360_fi('visit_count'),
                    crm360_f('churn_risk_level') ?: ($before['churn_risk_level'] ?? 'LOW'),
                    $clubId,
                ]
            );
            crm360_audit($conn, 'UPDATE', 'crm360_customer_club', (string)$clubId, $before, ['updated' => true]);
            crm360_redirect($tab, 'ok', 'باشگاه مشتری به‌روز شد.');
        }

        case 'create_reminder': {
            $custId = crm360_fi('customer_profile_id');
            $vehId = crm360_fi('vehicle_profile_id');
            $title = crm360_f('reminder_title');
            if ($custId <= 0 || $vehId <= 0 || $title === '') {
                crm360_redirect($tab, 'err', 'مشتری، خودرو و عنوان یادآوری الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_service_reminders (customer_profile_id, vehicle_profile_id, case_id, reminder_type, reminder_title, due_date, due_km, reminder_status, preferred_channel, created_by) VALUES (?,?,?,?,?,?,?,N\'SCHEDULED\',?,?)',
                [
                    $custId,
                    $vehId,
                    crm360_fi('case_id') ?: null,
                    crm360_f('reminder_type', 'SERVICE') ?: 'SERVICE',
                    $title,
                    crm360_f('due_date') ?: null,
                    crm360_fi('due_km') ?: null,
                    crm360_f('preferred_channel') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد یادآوری ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(reminder_id) FROM dbo.crm360_service_reminders WHERE customer_profile_id=?', [$custId]);
            crm360_audit($conn, 'CREATE', 'crm360_service_reminders', $id, null, ['title' => $title]);
            crm360_redirect($tab, 'ok', 'یادآوری سرویس ایجاد شد.');
        }

        case 'update_reminder_status': {
            $remId = crm360_fi('reminder_id');
            $status = strtoupper(crm360_f('reminder_status'));
            if ($remId <= 0 || $status === '') {
                crm360_redirect($tab, 'err', 'شناسه و وضعیت یادآوری الزامی است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_service_reminders WHERE reminder_id=?', [$remId]);
            crm360_exec($conn, 'UPDATE dbo.crm360_service_reminders SET reminder_status=?, updated_at=SYSUTCDATETIME() WHERE reminder_id=?', [$status, $remId]);
            if ($status === 'NEEDS_FOLLOWUP' && $before) {
                crm360_exec(
                    $conn,
                    'INSERT INTO dbo.crm360_customer_cartable (customer_profile_id, vehicle_profile_id, case_id, item_type, item_title, item_status, source_type, created_by) VALUES (?,?,?,?,?,N\'OPEN\',N\'REMINDER\',?)',
                    [
                        (int)$before['customer_profile_id'],
                        (int)$before['vehicle_profile_id'],
                        $before['case_id'] ?? null,
                        'REMINDER_FOLLOWUP',
                        'پیگیری یادآوری: ' . ($before['reminder_title'] ?? ''),
                        $actor,
                    ]
                );
            }
            crm360_audit($conn, 'UPDATE', 'crm360_service_reminders', (string)$remId, $before, ['status' => $status]);
            crm360_redirect($tab, 'ok', 'وضعیت یادآوری به‌روز شد.');
        }

        case 'create_return': {
            $custId = crm360_fi('customer_profile_id');
            if ($custId <= 0) {
                crm360_redirect($tab, 'err', 'مشتری الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_return_pipeline (customer_profile_id, vehicle_profile_id, reason_code, return_stage, assigned_to, next_action_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?)',
                [
                    $custId,
                    crm360_fi('vehicle_profile_id') ?: null,
                    crm360_f('reason_code', 'CHURN') ?: 'CHURN',
                    crm360_f('return_stage', 'IDENTIFIED') ?: 'IDENTIFIED',
                    crm360_f('assigned_to') ?: null,
                    crm360_f('next_action_date') ?: null,
                    crm360_f('notes') ?: null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ثبت بازگشت مشتری ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(return_id) FROM dbo.crm360_return_pipeline WHERE customer_profile_id=?', [$custId]);
            crm360_audit($conn, 'CREATE', 'crm360_return_pipeline', $id, null, ['reason' => crm360_f('reason_code')]);
            crm360_redirect($tab, 'ok', 'رکورد بازگشت مشتری ایجاد شد.');
        }

        case 'update_return': {
            $retId = crm360_fi('return_id');
            if ($retId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه بازگشت نامعتبر است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_return_pipeline WHERE return_id=?', [$retId]);
            crm360_exec(
                $conn,
                'UPDATE dbo.crm360_return_pipeline SET return_stage=?, result_status=?, assigned_to=?, next_action_date=?, notes=?, updated_at=SYSUTCDATETIME() WHERE return_id=?',
                [
                    crm360_f('return_stage') ?: ($before['return_stage'] ?? 'IDENTIFIED'),
                    crm360_f('result_status') ?: ($before['result_status'] ?? 'OPEN'),
                    crm360_f('assigned_to') ?: null,
                    crm360_f('next_action_date') ?: null,
                    crm360_f('notes') ?: null,
                    $retId,
                ]
            );
            crm360_audit($conn, 'UPDATE', 'crm360_return_pipeline', (string)$retId, $before, ['updated' => true]);
            crm360_redirect($tab, 'ok', 'بازگشت مشتری به‌روز شد.');
        }

        case 'create_promotion': {
            $code = crm360_next_code($conn, 'PRM', 'crm360_promotions', 'promotion_id');
            $title = crm360_f('title');
            if ($title === '') {
                crm360_redirect($tab, 'err', 'عنوان پروموشن الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_promotions (promotion_code, title, description, promotion_type, start_date, end_date, target_segment, discount_type, discount_value, promotion_status, created_by) VALUES (?,?,?,?,?,?,?,?,?,N\'DRAFT\',?)',
                [
                    $code,
                    $title,
                    crm360_f('description') ?: null,
                    crm360_f('promotion_type', 'DISCOUNT') ?: 'DISCOUNT',
                    crm360_f('start_date') ?: date('Y-m-d'),
                    crm360_f('end_date') ?: date('Y-m-d', strtotime('+30 days')),
                    crm360_f('target_segment') ?: null,
                    crm360_f('discount_type') ?: null,
                    crm360_f('discount_value') !== '' ? (float)crm360_f('discount_value') : null,
                    $actor,
                ]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد پروموشن ناموفق بود.');
            }
            crm360_audit($conn, 'CREATE', 'crm360_promotions', $code, null, ['title' => $title]);
            crm360_redirect($tab, 'ok', 'پروموشن ' . $code . ' ایجاد شد.');
        }

        case 'assign_promotion': {
            $promoId = crm360_fi('promotion_id');
            $custId = crm360_fi('customer_profile_id');
            if ($promoId <= 0 || $custId <= 0) {
                crm360_redirect($tab, 'err', 'پروموشن و مشتری الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_promotion_assignments (promotion_id, customer_profile_id, vehicle_profile_id, case_id, assignment_status) VALUES (?,?,?,?,N\'ASSIGNED\')',
                [$promoId, $custId, crm360_fi('vehicle_profile_id') ?: null, crm360_fi('case_id') ?: null]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'تخصیص پروموشن ناموفق بود.');
            }
            $id = (string)crm360_scalar($conn, 'SELECT MAX(assignment_id) FROM dbo.crm360_promotion_assignments WHERE promotion_id=? AND customer_profile_id=?', [$promoId, $custId]);
            crm360_audit($conn, 'CREATE', 'crm360_promotion_assignments', $id, null, ['promotion_id' => $promoId, 'customer_profile_id' => $custId]);
            crm360_redirect($tab, 'ok', 'پروموشن به مشتری تخصیص یافت.');
        }

        case 'update_assignment': {
            $assignId = crm360_fi('assignment_id');
            $status = strtoupper(crm360_f('assignment_status'));
            if ($assignId <= 0 || $status === '') {
                crm360_redirect($tab, 'err', 'شناسه تخصیص و وضعیت الزامی است.');
            }
            $before = crm360_one($conn, 'SELECT * FROM dbo.crm360_promotion_assignments WHERE assignment_id=?', [$assignId]);
            $usedAt = $status === 'USED' ? date('Y-m-d H:i:s') : null;
            crm360_exec($conn, 'UPDATE dbo.crm360_promotion_assignments SET assignment_status=?, used_at=?, notes=? WHERE assignment_id=?', [
                $status,
                $usedAt,
                crm360_f('notes') ?: null,
                $assignId,
            ]);
            crm360_audit($conn, 'UPDATE', 'crm360_promotion_assignments', (string)$assignId, $before, ['status' => $status]);
            crm360_redirect($tab, 'ok', 'وضعیت تخصیص به‌روز شد.');
        }

        case 'create_sms_campaign': {
            $code = crm360_next_code($conn, 'SMS', 'crm360_sms_campaigns', 'campaign_id');
            $title = crm360_f('title');
            $msg = crm360_f('message_text');
            if ($title === '' || $msg === '') {
                crm360_redirect($tab, 'err', 'عنوان و متن پیام الزامی است.');
            }
            $ok = crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_sms_campaigns (campaign_code, title, target_segment, message_text, send_mode, campaign_status, created_by) VALUES (?,?,?,?,N\'DRAFT_ONLY\',N\'DRAFT\',?)',
                [$code, $title, crm360_f('target_segment') ?: null, $msg, $actor]
            );
            if (!$ok) {
                crm360_redirect($tab, 'err', 'ایجاد کمپین پیامکی ناموفق بود.');
            }
            crm360_audit($conn, 'CREATE', 'crm360_sms_campaigns', $code, null, ['title' => $title, 'note' => 'NO_LIVE_SMS']);
            crm360_redirect($tab, 'ok', 'کمپین ' . $code . ' ایجاد شد (بدون ارسال زنده).');
        }

        case 'build_recipients': {
            $campaignId = crm360_fi('campaign_id');
            $consentMode = crm360_f('consent_mode', 'consent_sms');
            if ($campaignId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه کمپین الزامی است.');
            }
            $field = $consentMode === 'consent_marketing' ? 'consent_marketing' : 'consent_sms';
            $customers = crm360_rows(
                $conn,
                "SELECT customer_profile_id, mobile FROM dbo.crm360_customer_profiles WHERE $field=1 AND mobile IS NOT NULL AND mobile<>''"
            );
            $added = 0;
            $skipped = 0;
            foreach ($customers as $c) {
                $exists = (int)(crm360_scalar(
                    $conn,
                    'SELECT COUNT(*) FROM dbo.crm360_sms_campaign_recipients WHERE campaign_id=? AND customer_profile_id=?',
                    [$campaignId, (int)$c['customer_profile_id']]
                ) ?? 0);
                if ($exists > 0) {
                    continue;
                }
                $consent = 'GRANTED';
                $ok = crm360_exec(
                    $conn,
                    'INSERT INTO dbo.crm360_sms_campaign_recipients (campaign_id, customer_profile_id, mobile, consent_status, recipient_status) VALUES (?,?,?,?,N\'QUEUED\')',
                    [$campaignId, (int)$c['customer_profile_id'], (string)$c['mobile'], $consent]
                );
                if ($ok) {
                    $added++;
                } else {
                    $skipped++;
                }
            }
            $noConsent = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_customer_profiles WHERE mobile IS NOT NULL AND mobile <> '' AND $field = 0", []) ?? 0);
            crm360_audit($conn, 'UPDATE', 'crm360_sms_campaigns', (string)$campaignId, null, ['recipients_added' => $added, 'skipped_no_consent' => $noConsent]);
            crm360_redirect($tab, 'ok', $added . ' گیرنده افزوده شد. ' . $noConsent . ' بدون رضایت رد شد.');
        }

        case 'export_campaign': {
            $campaignId = crm360_fi('campaign_id');
            if ($campaignId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه کمپین الزامی است.');
            }
            $batch = 'BATCH-' . date('Ymd-His');
            crm360_exec(
                $conn,
                "UPDATE dbo.crm360_sms_campaign_recipients SET export_batch_no=?, recipient_status=N'EXPORTED' WHERE campaign_id=? AND recipient_status=N'QUEUED'",
                [$batch, $campaignId]
            );
            crm360_exec($conn, "UPDATE dbo.crm360_sms_campaigns SET campaign_status=N'EXPORTED', updated_at=SYSUTCDATETIME() WHERE campaign_id=?", [$campaignId]);
            crm360_audit($conn, 'EXPORT', 'crm360_sms_campaigns', (string)$campaignId, null, ['batch' => $batch]);
            crm360_redirect($tab, 'ok', 'خروجی گیرندگان با batch ' . $batch . ' آماده شد.');
        }

        case 'mark_sent_manual': {
            $campaignId = crm360_fi('campaign_id');
            if ($campaignId <= 0) {
                crm360_redirect($tab, 'err', 'شناسه کمپین الزامی است.');
            }
            crm360_exec(
                $conn,
                "UPDATE dbo.crm360_sms_campaign_recipients SET recipient_status=N'SENT_MANUAL', sent_manual_at=SYSUTCDATETIME(), delivery_status=N'MANUAL' WHERE campaign_id=? AND recipient_status IN (N'QUEUED',N'EXPORTED')",
                [$campaignId]
            );
            crm360_exec($conn, "UPDATE dbo.crm360_sms_campaigns SET campaign_status=N'SENT_MANUAL', send_mode=N'MANUAL_MARK', updated_at=SYSUTCDATETIME() WHERE campaign_id=?", [$campaignId]);
            crm360_audit($conn, 'UPDATE', 'crm360_sms_campaigns', (string)$campaignId, null, ['sent' => 'manual', 'note' => 'NO_LIVE_SMS']);
            crm360_redirect($tab, 'ok', 'ارسال دستی ثبت شد (بدون SMS زنده).');
        }

        default:
            crm360_redirect($tab, 'err', 'عملیات نامعتبر است.');
    }
} catch (Throwable $e) {
    crm360_redirect($tab, 'err', 'خطا در پردازش درخواست.');
}
