<?php
declare(strict_types=1);
/** Compatibility aliases between UI page names and repository function names. */

function p360_legal_disclaimer_html(): string {
    return '<div class="notice warn">ط§غŒظ† ط³ط§ظ…ط§ظ†ظ‡ ظ…ظˆطھظˆط± طھظ†ط¸غŒظ… ظˆ ط§ط¬ط±ط§غŒ ظ‚ظˆط§ط¹ط¯ ط§ط¯ط§ط±غŒطŒ ط­ظ‚ظˆظ‚ ظˆ ظ…ظ†ط§ط¨ط¹ ط§ظ†ط³ط§ظ†غŒ ط§ط³طھ. ظ…ظ‚ط§ط¯غŒط± ظ‚ط§ظ†ظˆظ†غŒ ط¨ط§غŒط¯ طھظˆط³ط· ظ…ط¯غŒط±/ظ…ط´ط§ظˆط± ط­ظ‚ظˆظ‚غŒ ط¨ط±ط§ط³ط§ط³ ط¢ط®ط±غŒظ† ظ‚ظˆط§ظ†غŒظ† ظˆ ط¨ط®ط´ظ†ط§ظ…ظ‡â€Œظ‡ط§غŒ ط±ط³ظ…غŒ ط«ط¨طھ ظˆ طھط£غŒغŒط¯ ط´ظˆط¯. ظ†ظ…ظˆظ†ظ‡â€Œظ‡ط§غŒ ط¢ط²ظ…ط§غŒط´غŒ ظ†غŒط§ط²ظ…ظ†ط¯ طھط£غŒغŒط¯ ظ…ط¯غŒط±/ظ…ط´ط§ظˆط± ط­ظ‚ظˆظ‚غŒ ظ‡ط³طھظ†ط¯.</div>';
}

function p360_company_list($conn) { return p360_companies($conn); }
function p360_company_save($conn, array $d, int $uid) { return p360_company_create($conn, $d, $uid); }
function p360_branch_list($conn) { return p360_branches($conn); }
function p360_branch_save($conn, array $d, int $uid) { return p360_branch_create($conn, $d, $uid); }
function p360_department_list($conn) { return p360_departments($conn); }
function p360_department_save($conn, array $d, int $uid) { return p360_dept_create($conn, $d, $uid); }
function p360_position_list($conn) { return p360_positions($conn); }
function p360_legal_rules_list($conn) { return p360_legal_rules($conn); }
function p360_legal_rule_save($conn, array $d, int $uid) { return p360_legal_rule_create($conn, $d, $uid); }
function p360_employment_types_list($conn) { return p360_employment_types($conn); }
function p360_contract_types_list($conn) { return p360_contract_types($conn); }
function p360_employee_list($conn) { return p360_employees($conn); }
function p360_employee_save($conn, array $d, int $uid) { return p360_employee_create($conn, $d, $uid); }
function p360_employee_salary_history($conn, int $empId) { return p360_salary_history($conn, $empId); }
function p360_employee_position_history($conn, int $empId) { return p360_position_history($conn, $empId); }
function p360_employee_add_salary($conn, int $empId, float $salary, string $from, string $reason, int $uid) { return p360_salary_change($conn, $empId, $salary, $from, $reason, $uid); }
function p360_employee_add_position_history($conn, int $empId, int $posId, string $from, string $reason, int $uid) { return p360_position_change($conn, $empId, $posId, $from, $reason, $uid); }

function p360_contract_issue($conn, int $empId, int $tplId, string $contractNo, string $start, $end, array $vars, int $uid): array {
    $vars = array_merge($vars, ['contract_start' => $start, 'contract_end' => $end ?: '']);
    return p360_generate_contract($conn, $empId, $tplId, $vars, $uid);
}

function p360_attendance_raw_log($conn, int $deviceId, int $empId, string $punchAt, string $type): array {
    return p360_raw_punch($conn, ['device_id' => $deviceId, 'employee_id' => $empId, 'punch_at' => $punchAt, 'punch_type' => $type], (int)(p360_current_user()['user_id'] ?? 0));
}
function p360_attendance_calculate_day($conn, int $empId, string $date, int $uid = 0): array {
    if ($uid < 1) $uid = (int)(p360_current_user()['user_id'] ?? 0);
    return p360_calc_attendance($conn, $empId, $date, $uid);
}
function p360_jalali_year_save($conn, array $d, int $uid) { return p360_jalali_year_create($conn, $d, $uid); }

function p360_leave_request_create($conn, int $empId, string $leaveType, string $from, string $to, int $minutes, int $uid, int $checkerUserId = 0): array {
    $r = p360_leave_request($conn, ['employee_id' => $empId, 'leave_type' => $leaveType, 'from_at' => $from, 'to_at' => $to, 'reason_text' => null], $uid);
    if (!empty($r['ok']) && $checkerUserId > 0) {
        p360_exec($conn, 'UPDATE dbo.p360_workflow_tasks SET checker_user_id=? WHERE entity_type=N\'leave_request\' AND entity_id=?', [$checkerUserId, (string)$r['id']]);
    }
    return $r;
}
function p360_leave_approve($conn, int $leaveId, int $taskId, int $uid): array {
    $r = p360_workflow_approve($conn, $taskId, $uid, 'leave approved');
    if (!empty($r['ok'])) {
        p360_exec($conn, 'UPDATE dbo.p360_leave_requests SET request_status=N\'approved\' WHERE id=?', [$leaveId]);
    }
    return $r;
}

function p360_loan_create($conn, array $d, int $uid) { return p360_loan_request($conn, $d, $uid); }
function p360_loans_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_loans ORDER BY id DESC', []); }
function p360_manpower_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_manpower_requests ORDER BY id DESC', []); }
function p360_vacancy_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_vacancies ORDER BY id DESC', []); }
function p360_candidate_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_candidates ORDER BY id DESC', []); }
function p360_candidate_hire($conn, int $candidateId, array $d, int $uid) { return p360_candidate_to_employee($conn, $candidateId, $d, $uid); }
function p360_mission_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_mission_requests ORDER BY id DESC', []); }
function p360_overtime_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_overtime_requests ORDER BY id DESC', []); }
function p360_equipment_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_equipment ORDER BY id DESC', []); }
function p360_rewards_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_rewards ORDER BY id DESC', []); }
function p360_disciplinary_list($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_disciplinary_actions ORDER BY id DESC', []); }
function p360_training_courses($conn) { return p360_rows($conn, 'SELECT TOP 100 * FROM dbo.p360_training_courses ORDER BY id DESC', []); }

function p360_payroll_period_create($conn, array $d, int $uid) { return p360_period_create($conn, $d, $uid); }
function p360_payroll_run_employee($conn, int $empId, int $periodId, int $uid) { return p360_payroll_calc($conn, $empId, $periodId, $uid); }

function p360_task_create($conn, string $taskType, string $entityType, string $entityId, string $title, int $makerUserId, ?int $checkerUserId = null): array {
    return p360_workflow_create($conn, $taskType, $entityType, $entityId, $title, $makerUserId, $checkerUserId);
}
function p360_position_save($conn, array $d, int $uid) { return p360_position_create($conn, $d, $uid); }
