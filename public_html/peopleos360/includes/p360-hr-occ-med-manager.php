<?php
declare(strict_types=1);

/**
 * Occupational medicine + direct-manager reminder recipients (R1 gaps).
 */

require_once __DIR__ . '/p360-hr-identity-date.php';
require_once __DIR__ . '/p360-jalali.php';

function p360hr_occ_med_status_fa(string $code): string
{
    return match (strtoupper($code)) {
        'DRAFT' => 'پیش‌نویس',
        'VALID' => 'معتبر',
        'EXPIRING_SOON' => 'نزدیک به انقضا',
        'EXPIRED' => 'منقضی‌شده',
        'RENEWAL_REQUIRED' => 'نیازمند تمدید',
        'SUPERSEDED' => 'جایگزین‌شده',
        'VOIDED' => 'باطل‌شده',
        default => 'نامشخص',
    };
}

function p360hr_occ_med_derive_status(array $row): string
{
    $life = strtoupper(trim((string)($row['lifecycle_state'] ?? 'DRAFT')));
    if (in_array($life, ['VOIDED', 'SUPERSEDED', 'DRAFT'], true)) {
        return $life;
    }
    $noExpiry = (int)($row['no_expiry'] ?? 0) === 1;
    $expiry = isset($row['expiry_date']) ? substr((string)$row['expiry_date'], 0, 10) : '';
    if ($noExpiry || $expiry === '') {
        return $life === 'RENEWAL_REQUIRED' ? 'RENEWAL_REQUIRED' : 'VALID';
    }
    $days = p360_days_remaining_until($expiry);
    if ($days === null) {
        return 'VALID';
    }
    if ($days < 0) {
        return 'EXPIRED';
    }
    if ($days <= 30) {
        return 'EXPIRING_SOON';
    }
    return 'VALID';
}

/** @return list<array<string,mixed>> */
function p360hr_occ_med_list(int $employeeId): array
{
    $rows = p360hr_rows(
        'SELECT * FROM dbo.p360_hr_occ_med_reports WHERE employee_id=? ORDER BY report_id DESC',
        [$employeeId]
    );
    foreach ($rows as &$r) {
        $r['derived_status'] = p360hr_occ_med_derive_status($r);
        $r['derived_status_fa'] = p360hr_occ_med_status_fa($r['derived_status']);
    }
    unset($r);
    return $rows;
}

function p360hr_occ_med_get(int $reportId): ?array
{
    $r = p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_occ_med_reports WHERE report_id=?', [$reportId]);
    if ($r === null) {
        return null;
    }
    $r['derived_status'] = p360hr_occ_med_derive_status($r);
    $r['derived_status_fa'] = p360hr_occ_med_status_fa($r['derived_status']);
    return $r;
}

/** @param array<string,mixed> $data */
function p360hr_occ_med_save(int $employeeId, array $data, int $actorUserId): array
{
    if (!p360hr_can_manage_personnel()) {
        return ['ok' => false, 'message' => 'ویرایش طب کار فقط برای منابع انسانی/مالک مجاز است.'];
    }
    $reportId = (int)($data['report_id'] ?? 0);
    $noExpiry = !empty($data['no_expiry']) ? 1 : 0;
    $exam = $data['examination_date'] ?? null;
    $issue = $data['report_issue_date'] ?? null;
    $validFrom = $data['valid_from_date'] ?? null;
    $expiry = $noExpiry ? null : ($data['expiry_date'] ?? null);
    foreach (['examination_date' => $exam, 'report_issue_date' => $issue, 'valid_from_date' => $validFrom, 'expiry_date' => $expiry] as $label => $ymd) {
        if ($ymd === null || $ymd === '') {
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$ymd)) {
            return ['ok' => false, 'message' => 'تاریخ نامعتبر است.'];
        }
        [$y, $m, $d] = array_map('intval', explode('-', (string)$ymd));
        [$jy, $jm, $jd] = p360_gregorian_to_jalali($y, $m, $d);
        if (!p360_jalali_is_valid($jy, $jm, $jd)) {
            return ['ok' => false, 'message' => 'تاریخ شمسی متناظر نامعتبر است.'];
        }
    }
    $life = strtoupper(trim((string)($data['lifecycle_state'] ?? 'DRAFT')));
    $allowedLife = ['DRAFT', 'VALID', 'RENEWAL_REQUIRED', 'SUPERSEDED', 'VOIDED'];
    if (!in_array($life, $allowedLife, true)) {
        $life = 'DRAFT';
    }
    $center = trim((string)($data['medical_center_name'] ?? ''));
    $ref = trim((string)($data['report_ref'] ?? ''));
    $notes = trim((string)($data['notes'] ?? ''));
    $docId = ((int)($data['document_id'] ?? 0)) ?: null;
    $result = trim((string)($data['result_status'] ?? ''));

    if ($reportId > 0) {
        $existing = p360hr_occ_med_get($reportId);
        if ($existing === null || (int)$existing['employee_id'] !== $employeeId) {
            return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
        }
        $ok = p360hr_exec(
            'UPDATE dbo.p360_hr_occ_med_reports SET
                document_id=?, examination_date=?, report_issue_date=?, valid_from_date=?, expiry_date=?, no_expiry=?,
                result_status=?, medical_center_name=?, report_ref=?, notes=?, lifecycle_state=?,
                updated_at=SYSUTCDATETIME(), updated_by_user_id=?
             WHERE report_id=?',
            [
                $docId, $exam ?: null, $issue ?: null, $validFrom ?: null, $expiry, $noExpiry,
                $result !== '' ? $result : null, $center !== '' ? $center : null, $ref !== '' ? $ref : null,
                $notes !== '' ? $notes : null, $life, $actorUserId, $reportId,
            ]
        );
        return $ok ? ['ok' => true, 'message' => 'گزارش طب کار به‌روز شد.', 'report_id' => $reportId]
            : ['ok' => false, 'message' => 'ذخیره ناموفق بود.'];
    }

    $ok = p360hr_exec(
        'INSERT INTO dbo.p360_hr_occ_med_reports
            (employee_id, document_id, examination_date, report_issue_date, valid_from_date, expiry_date, no_expiry,
             result_status, medical_center_name, report_ref, notes, lifecycle_state, created_by_user_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $employeeId, $docId, $exam ?: null, $issue ?: null, $validFrom ?: null, $expiry, $noExpiry,
            $result !== '' ? $result : null, $center !== '' ? $center : null, $ref !== '' ? $ref : null,
            $notes !== '' ? $notes : null, $life, $actorUserId,
        ]
    );
    if (!$ok) {
        return ['ok' => false, 'message' => 'ثبت گزارش ناموفق بود.'];
    }
    $row = p360hr_one('SELECT TOP 1 report_id FROM dbo.p360_hr_occ_med_reports WHERE employee_id=? ORDER BY report_id DESC', [$employeeId]);
    return ['ok' => true, 'message' => 'گزارش طب کار ثبت شد.', 'report_id' => (int)($row['report_id'] ?? 0)];
}

/**
 * Resolve direct manager from DB relation only (not job-title text).
 * @return array{ok:bool,manager_employee_id?:int,manager_user_id?:int,message?:string}
 */
function p360hr_resolve_direct_manager(int $employeeId): array
{
    $profile = p360hr_personnel_profile($employeeId);
    $mgrEmpId = (int)($profile['direct_manager_employee_id'] ?? 0);
    if ($mgrEmpId < 1) {
        return ['ok' => false, 'message' => 'مدیر مستقیم ثبت نشده است'];
    }
    if ($mgrEmpId === $employeeId) {
        return ['ok' => false, 'message' => 'رابطه مدیر مستقیم نامعتبر است'];
    }
    $mgr = p360hr_employee_by_id($mgrEmpId);
    if ($mgr === null) {
        return ['ok' => false, 'message' => 'پرونده مدیر مستقیم یافت نشد'];
    }
    $active = (int)($mgr['is_active'] ?? 1);
    $life = strtoupper((string)($mgr['lifecycle_state'] ?? 'ACTIVE'));
    if ($active !== 1 || in_array($life, ['EXITED', 'INACTIVE', 'TERMINATED'], true)) {
        return ['ok' => false, 'message' => 'مدیر مستقیم غیرفعال است'];
    }
    $uid = (int)($mgr['core_user_id'] ?? 0);
    if ($uid < 1) {
        return ['ok' => false, 'message' => 'حساب کاربری مدیر مستقیم متصل نیست'];
    }
    $user = p360hr_one(
        "SELECT TOP 1 user_id, is_login_enabled, lifecycle_state FROM dbo.core_users WHERE user_id=? AND lifecycle_state=N'ACTIVE'",
        [$uid]
    );
    if ($user === null || (int)($user['is_login_enabled'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'حساب کاربری مدیر مستقیم فعال نیست'];
    }
    $mem = p360hr_one(
        'SELECT TOP 1 1 AS x FROM dbo.erp_company_users WHERE user_id=? AND is_active=1',
        [$uid]
    );
    if ($mem === null) {
        return ['ok' => false, 'message' => 'عضویت شرکتی مدیر مستقیم فعال نیست'];
    }
    return ['ok' => true, 'manager_employee_id' => $mgrEmpId, 'manager_user_id' => $uid];
}

/** @return list<int> */
function p360hr_hr_owner_recipient_user_ids(): array
{
    $ids = [];
    $owners = p360hr_rows("SELECT user_id FROM dbo.core_users WHERE is_system_owner=1 AND lifecycle_state=N'ACTIVE' AND is_login_enabled=1");
    foreach ($owners as $o) {
        $ids[] = (int)$o['user_id'];
    }
    $hr = p360hr_rows(
        "SELECT DISTINCT u.user_id
         FROM dbo.core_users u
         INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.is_active=1
         WHERE u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1
           AND cu.role_code IN (N'SYSTEM_ADMIN', N'OWNER', N'HR_ADMIN')"
    );
    foreach ($hr as $h) {
        $ids[] = (int)$h['user_id'];
    }
    return array_values(array_unique(array_filter($ids)));
}

function p360hr_reminder_ensure_recipient(int $reminderId, int $userId, string $type): bool
{
    if ($reminderId < 1 || $userId < 1) {
        return false;
    }
    $ex = p360hr_one(
        'SELECT id FROM dbo.p360_hr_contract_reminder_recipients WHERE reminder_id=? AND recipient_user_id=? AND recipient_type=?',
        [$reminderId, $userId, $type]
    );
    if ($ex !== null) {
        return false; // already present — not newly created
    }
    return p360hr_exec(
        'INSERT INTO dbo.p360_hr_contract_reminder_recipients (reminder_id, recipient_user_id, recipient_type, recipient_status)
         VALUES (?,?,?,N\'NEW\')',
        [$reminderId, $userId, $type]
    );
}

function p360hr_reminder_sync_recipients(int $reminderId, int $employeeId): array
{
    $added = 0;
    foreach (p360hr_hr_owner_recipient_user_ids() as $uid) {
        $isOwner = p360hr_one('SELECT TOP 1 1 x FROM dbo.core_users WHERE user_id=? AND is_system_owner=1', [$uid]);
        $type = $isOwner !== null ? 'SYSTEM_OWNER' : 'HR_OWNER';
        if (p360hr_reminder_ensure_recipient($reminderId, $uid, $type)) {
            $added++;
        }
    }
    $mgr = p360hr_resolve_direct_manager($employeeId);
    $missingManager = !$mgr['ok'];
    if ($mgr['ok'] && p360hr_reminder_ensure_recipient($reminderId, (int)$mgr['manager_user_id'], 'DIRECT_MANAGER')) {
        $added++;
    }
    // If manager changed: do not delete historical rows; new manager gets a recipient.
    // Former manager keeps historical seen/action but is not re-added.
    return ['ok' => true, 'added' => $added, 'missing_manager' => $missingManager, 'manager_message' => $mgr['message'] ?? ''];
}

/**
 * Patch: after business reminder upsert, sync recipients.
 * Call from refresh.
 */
function p360hr_reminder_refresh_all_r1(): array
{
    $base = p360hr_reminder_refresh_all();
    $rows = p360hr_rows(
        "SELECT reminder_id, employee_id FROM dbo.p360_hr_contract_reminders
         WHERE status IN (N'NEW', N'SEEN', N'ACTION_REQUIRED', N'OVERDUE')"
    );
    $missing = 0;
    foreach ($rows as $r) {
        $sync = p360hr_reminder_sync_recipients((int)$r['reminder_id'], (int)$r['employee_id']);
        if (!empty($sync['missing_manager'])) {
            $missing++;
        }
    }
    $base['missing_manager_count'] = $missing;
    return $base;
}

/** @return list<array<string,mixed>> */
function p360hr_manager_cartable_reminders(int $managerUserId): array
{
    p360hr_reminder_refresh_all_r1();
    return p360hr_rows(
        "SELECT r.reminder_id, r.contract_id, r.employee_id, r.contract_end_date, r.days_remaining, r.status,
                e.first_name, e.last_name, e.display_name_override, e.employee_code, e.job_title,
                rec.id AS recipient_row_id, rec.recipient_status, rec.recommendation_code, rec.recommendation_note,
                c.contract_job_title, c.stage_code
         FROM dbo.p360_hr_contract_reminder_recipients rec
         INNER JOIN dbo.p360_hr_contract_reminders r ON r.reminder_id=rec.reminder_id
         INNER JOIN dbo.p360_employees e ON e.employee_id=r.employee_id
         INNER JOIN dbo.p360_hr_contracts c ON c.contract_id=r.contract_id
         WHERE rec.recipient_user_id=? AND rec.recipient_type=N'DIRECT_MANAGER'
           AND r.status IN (N'NEW', N'SEEN', N'ACTION_REQUIRED', N'OVERDUE')
         ORDER BY r.contract_end_date ASC",
        [$managerUserId]
    );
}

function p360hr_user_is_direct_manager_of_anyone(int $userId): bool
{
    $row = p360hr_one(
        "SELECT TOP 1 1 x
         FROM dbo.p360_hr_personnel_profile p
         INNER JOIN dbo.p360_employees m ON m.employee_id=p.direct_manager_employee_id
         WHERE m.core_user_id=? AND p.direct_manager_employee_id IS NOT NULL",
        [$userId]
    );
    return $row !== null;
}

function p360hr_manager_assert_recipient_access(int $reminderId, int $userId): ?array
{
    return p360hr_one(
        "SELECT TOP 1 rec.*, r.contract_id, r.employee_id
         FROM dbo.p360_hr_contract_reminder_recipients rec
         INNER JOIN dbo.p360_hr_contract_reminders r ON r.reminder_id=rec.reminder_id
         WHERE rec.reminder_id=? AND rec.recipient_user_id=? AND rec.recipient_type=N'DIRECT_MANAGER'",
        [$reminderId, $userId]
    );
}

function p360hr_manager_recommend(int $reminderId, int $userId, string $code, string $note): array
{
    $rec = p360hr_manager_assert_recipient_access($reminderId, $userId);
    if ($rec === null) {
        return ['ok' => false, 'message' => 'دسترسی به این هشدار برای شما مجاز نیست.'];
    }
    $code = strtoupper(trim($code));
    $allowed = ['NO_RECOMMENDATION', 'RENEW_RECOMMENDED', 'NON_RENEW_RECOMMENDED', 'REVIEW_REQUIRED'];
    if (!in_array($code, $allowed, true)) {
        return ['ok' => false, 'message' => 'نوع توصیه نامعتبر است.'];
    }
    $ok = p360hr_exec(
        "UPDATE dbo.p360_hr_contract_reminder_recipients SET
            recommendation_code=?, recommendation_note=?, recipient_status=N'SEEN',
            seen_at=COALESCE(seen_at, SYSUTCDATETIME()), actioned_at=SYSUTCDATETIME()
         WHERE id=?",
        [$code, trim($note) !== '' ? trim($note) : null, (int)$rec['id']]
    );
    return $ok
        ? ['ok' => true, 'message' => 'توصیه مدیر ثبت شد. نهایی‌سازی با منابع انسانی/مالک است.']
        : ['ok' => false, 'message' => 'ثبت توصیه ناموفق بود.'];
}

function p360hr_recommendation_fa(string $code): string
{
    return match (strtoupper($code)) {
        'NO_RECOMMENDATION' => 'بدون توصیه',
        'RENEW_RECOMMENDED' => 'توصیه به تمدید',
        'NON_RENEW_RECOMMENDED' => 'توصیه به عدم تمدید',
        'REVIEW_REQUIRED' => 'نیازمند بررسی',
        default => '—',
    };
}

function p360hr_set_direct_manager(int $employeeId, ?int $managerEmployeeId, int $actorUserId): array
{
    if (!p360hr_can_manage_personnel()) {
        return ['ok' => false, 'message' => 'تنظیم مدیر مستقیم مجاز نیست.'];
    }
    if ($managerEmployeeId !== null && $managerEmployeeId > 0) {
        if ($managerEmployeeId === $employeeId) {
            return ['ok' => false, 'message' => 'فرد نمی‌تواند مدیر مستقیم خودش باشد.'];
        }
        $mgr = p360hr_employee_by_id($managerEmployeeId);
        if ($mgr === null) {
            return ['ok' => false, 'message' => 'مدیر انتخاب‌شده یافت نشد.'];
        }
    } else {
        $managerEmployeeId = null;
    }
    $profile = p360hr_one('SELECT employee_id FROM dbo.p360_hr_personnel_profile WHERE employee_id=?', [$employeeId]);
    if ($profile === null) {
        p360hr_exec('INSERT INTO dbo.p360_hr_personnel_profile (employee_id, direct_manager_employee_id, updated_by_user_id, updated_at) VALUES (?,?,?,SYSUTCDATETIME())', [$employeeId, $managerEmployeeId, $actorUserId]);
    } else {
        p360hr_exec('UPDATE dbo.p360_hr_personnel_profile SET direct_manager_employee_id=?, updated_by_user_id=?, updated_at=SYSUTCDATETIME() WHERE employee_id=?', [$managerEmployeeId, $actorUserId, $employeeId]);
    }
    return ['ok' => true, 'message' => 'مدیر مستقیم ذخیره شد.'];
}
