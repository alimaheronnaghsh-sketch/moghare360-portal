<?php
declare(strict_types=1);

/**
 * Contract expiry reminders + renewal/exit helpers.
 */

require_once __DIR__ . '/p360-hr-contract-engine.php';
require_once __DIR__ . '/p360-jalali.php';

function p360hr_audit_identity(int $employeeId, string $field, ?string $old, ?string $new, int $actorUserId): void
{
    $old = $old ?? '';
    $new = $new ?? '';
    if ($old === $new) {
        return;
    }
    p360hr_exec(
        'INSERT INTO dbo.p360_hr_identity_audit (employee_id, field_name, old_value, new_value, actor_user_id) VALUES (?,?,?,?,?)',
        [$employeeId, $field, $old !== '' ? $old : null, $new !== '' ? $new : null, $actorUserId]
    );
}

/**
 * @return list<array<string,mixed>>
 */
function p360hr_identity_history(int $employeeId): array
{
    return p360hr_rows(
        'SELECT TOP 100 id, field_name, old_value, new_value, actor_user_id, created_at FROM dbo.p360_hr_identity_audit WHERE employee_id=? ORDER BY id DESC',
        [$employeeId]
    );
}

/** @param array<string,mixed> $data */
function p360hr_save_identity_names(int $employeeId, array $data, int $actorUserId): array
{
    if (!p360hr_can_manage_personnel()) {
        return ['ok' => false, 'message' => 'ویرایش هویت فقط برای منابع انسانی/مالک مجاز است.'];
    }
    $emp = p360hr_employee_by_id($employeeId);
    if ($emp === null) {
        return ['ok' => false, 'message' => 'پرسنل یافت نشد.'];
    }
    $first = trim((string)($data['first_name'] ?? ''));
    $last = trim((string)($data['last_name'] ?? ''));
    $display = trim((string)($data['display_name_override'] ?? ''));
    if ($first === '' || $last === '') {
        return ['ok' => false, 'message' => 'نام و نام خانوادگی الزامی است.'];
    }
    // Multi-part names allowed; never re-split tokens.
    if (preg_match('/[0-9]/', $first . $last)) {
        return ['ok' => false, 'message' => 'نام نباید شامل رقم باشد.'];
    }
    $imported = trim((string)($emp['imported_full_name'] ?? ''));
    if ($imported === '') {
        $imported = trim((string)($emp['first_name'] ?? '') . ' ' . (string)($emp['last_name'] ?? ''));
        p360hr_exec('UPDATE dbo.p360_employees SET imported_full_name=? WHERE employee_id=? AND (imported_full_name IS NULL OR imported_full_name=N\'\')', [$imported, $employeeId]);
    }
    p360hr_audit_identity($employeeId, 'first_name', (string)($emp['first_name'] ?? ''), $first, $actorUserId);
    p360hr_audit_identity($employeeId, 'last_name', (string)($emp['last_name'] ?? ''), $last, $actorUserId);
    p360hr_audit_identity($employeeId, 'display_name_override', (string)($emp['display_name_override'] ?? ''), $display, $actorUserId);
    $ok = p360hr_exec(
        'UPDATE dbo.p360_employees SET first_name=?, last_name=?, display_name_override=? WHERE employee_id=?',
        [$first, $last, $display !== '' ? $display : null, $employeeId]
    );
    return $ok
        ? ['ok' => true, 'message' => 'هویت پرسنلی ذخیره شد.', 'full_name' => $display !== '' ? $display : trim($first . ' ' . $last)]
        : ['ok' => false, 'message' => 'ذخیره هویت ناموفق بود.'];
}

function p360hr_resolve_duration_months(array $fields): array
{
    $preset = strtoupper(trim((string)($fields['duration_preset'] ?? '')));
    $map = [
        '1M' => 1, '2M' => 2, '3M' => 3, '6M' => 6, '9M' => 9, '12M' => 12, '1Y' => 12,
    ];
    if ($preset === 'CUSTOM') {
        $unit = strtoupper(trim((string)($fields['duration_unit'] ?? 'MONTHS')));
        $num = (int)($fields['duration_custom_value'] ?? 0);
        if ($num < 1) {
            return ['ok' => false, 'months' => 0, 'message' => 'مقدار مدت سفارشی نامعتبر است.', 'preset' => $preset, 'unit' => $unit];
        }
        $months = ($unit === 'YEARS') ? ($num * 12) : $num;
        return ['ok' => true, 'months' => $months, 'message' => '', 'preset' => 'CUSTOM', 'unit' => $unit === 'YEARS' ? 'YEARS' : 'MONTHS'];
    }
    if ($preset === 'NONE' || $preset === 'PERMANENT') {
        return ['ok' => true, 'months' => 0, 'message' => '', 'preset' => 'PERMANENT', 'unit' => 'NONE'];
    }
    if (!isset($map[$preset])) {
        // Backward compat: numeric duration_months
        $m = (int)($fields['duration_months'] ?? 0);
        if ($m > 0) {
            return ['ok' => true, 'months' => $m, 'message' => '', 'preset' => 'CUSTOM', 'unit' => 'MONTHS'];
        }
        return ['ok' => false, 'months' => 0, 'message' => 'انتخاب مدت قرارداد الزامی است.', 'preset' => $preset, 'unit' => 'MONTHS'];
    }
    return ['ok' => true, 'months' => $map[$preset], 'message' => '', 'preset' => $preset, 'unit' => ($map[$preset] % 12 === 0 && $map[$preset] >= 12 ? 'YEARS' : 'MONTHS')];
}

function p360hr_reminder_refresh_all(): array
{
    $created = 0;
    $updated = 0;
    $rows = p360hr_rows(
        "SELECT contract_id, employee_id, end_date, is_locked, contract_type, stage_code
         FROM dbo.p360_hr_contracts
         WHERE is_locked=1 AND end_date IS NOT NULL AND contract_type <> N'PERMANENT'
           AND (renewal_status IS NULL OR renewal_status NOT IN (N'RENEWED', N'EXITED'))"
    );
    foreach ($rows as $r) {
        $end = substr((string)$r['end_date'], 0, 10);
        $days = p360_days_remaining_until($end);
        if ($days === null) {
            continue;
        }
        $trigger = (new DateTimeImmutable($end))->modify('-15 days')->format('Y-m-d');
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        if ($today < $trigger && $days > 15) {
            continue;
        }
        $status = 'NEW';
        if ($days < 0) {
            $status = 'OVERDUE';
        } elseif ($days <= 15) {
            $status = 'ACTION_REQUIRED';
        }
        $existing = p360hr_one(
            'SELECT reminder_id, status FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?',
            [(int)$r['contract_id'], $end]
        );
        if ($existing === null) {
            $ok = p360hr_exec(
                'INSERT INTO dbo.p360_hr_contract_reminders
                    (contract_id, employee_id, reminder_type, trigger_date, contract_end_date, days_remaining, status)
                 VALUES (?, ?, N\'CONTRACT_EXPIRY_15D\', ?, ?, ?, ?)',
                [(int)$r['contract_id'], (int)$r['employee_id'], $trigger, $end, $days, $status]
            );
            if ($ok) {
                $created++;
            }
        } else {
            $st = (string)$existing['status'];
            if (!in_array($st, ['ACTIONED', 'CLOSED'], true)) {
                p360hr_exec(
                    'UPDATE dbo.p360_hr_contract_reminders SET days_remaining=?, status=? WHERE reminder_id=?',
                    [$days, $status === 'OVERDUE' ? 'OVERDUE' : ($st === 'SEEN' ? 'SEEN' : $status), (int)$existing['reminder_id']]
                );
                $updated++;
            }
        }
        p360hr_exec('UPDATE dbo.p360_hr_contracts SET reminder_trigger_date=? WHERE contract_id=?', [$trigger, (int)$r['contract_id']]);
        $rid = $existing !== null
            ? (int)$existing['reminder_id']
            : (int)(p360hr_one(
                'SELECT reminder_id FROM dbo.p360_hr_contract_reminders WHERE contract_id=? AND reminder_type=N\'CONTRACT_EXPIRY_15D\' AND contract_end_date=?',
                [(int)$r['contract_id'], $end]
            )['reminder_id'] ?? 0);
        if ($rid > 0 && function_exists('p360hr_reminder_sync_recipients')) {
            p360hr_reminder_sync_recipients($rid, (int)$r['employee_id']);
        }
    }
    return ['ok' => true, 'created' => $created, 'updated' => $updated];
}

/** @return list<array<string,mixed>> */
function p360hr_reminders_for_dashboard(): array
{
    if (function_exists('p360hr_reminder_refresh_all_r1')) {
        p360hr_reminder_refresh_all_r1();
    } else {
        p360hr_reminder_refresh_all();
    }
    return p360hr_rows(
        "SELECT r.*, e.first_name, e.last_name, e.display_name_override, e.employee_code, e.job_title,
                c.contract_type, c.start_date, c.direct_supervisor, c.stage_code, c.renewal_status,
                p.direct_manager_employee_id
         FROM dbo.p360_hr_contract_reminders r
         INNER JOIN dbo.p360_employees e ON e.employee_id=r.employee_id
         INNER JOIN dbo.p360_hr_contracts c ON c.contract_id=r.contract_id
         LEFT JOIN dbo.p360_hr_personnel_profile p ON p.employee_id=r.employee_id
         WHERE r.status IN (N'NEW', N'SEEN', N'ACTION_REQUIRED', N'OVERDUE')
         ORDER BY r.contract_end_date ASC"
    );
}

function p360hr_reminder_status_fa(string $s): string
{
    return match (strtoupper($s)) {
        'NEW' => 'جدید',
        'SEEN' => 'دیده‌شده',
        'ACTION_REQUIRED' => 'نیازمند اقدام',
        'ACTIONED' => 'اقدام‌شده',
        'OVERDUE' => 'سررسید گذشته',
        'CLOSED' => 'بسته',
        default => $s,
    };
}

function p360hr_renew_from_contract(int $oldContractId, int $actorUserId): array
{
    $old = p360hr_contract_get($oldContractId);
    if ($old === null || (int)($old['is_locked'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'فقط قرارداد نهایی‌شده قابل تمدید است.'];
    }
    $emp = p360hr_employee_by_id((int)$old['employee_id']);
    if ($emp === null) {
        return ['ok' => false, 'message' => 'پرسنل یافت نشد.'];
    }
    $proposeStart = null;
    if (!empty($old['end_date'])) {
        $proposeStart = (new DateTimeImmutable(substr((string)$old['end_date'], 0, 10)))->modify('+1 day')->format('Y-m-d');
    }
    $wageSnap = [];
    if (!empty($old['wage_snapshot_json'])) {
        $decoded = json_decode((string)$old['wage_snapshot_json'], true);
        if (is_array($decoded)) {
            $wageSnap = $decoded['inputs'] ?? [];
        }
    }
    $fields = [
        'personnel_code' => (string)$old['personnel_code'],
        'contract_type' => (string)$old['contract_type'],
        'contract_job_title' => (string)$old['contract_job_title'],
        'unit_name' => (string)$old['unit_name'],
        'direct_supervisor' => (string)$old['direct_supervisor'],
        'workplace' => (string)$old['workplace'],
        'contract_subject' => (string)$old['contract_subject'],
        'contract_subject_details' => (string)($old['contract_subject_details'] ?? ''),
        'job_duties_text' => (string)$old['job_duties_text'],
        'start_date' => $proposeStart ?? '',
        'duration_preset' => (string)($old['duration_preset'] ?? '3M'),
        'duration_months' => (int)($old['duration_months'] ?? 3),
        'duration_unit' => (string)($old['duration_unit'] ?? 'MONTHS'),
        'eid_payment_method' => (string)$old['eid_payment_method'],
        'severance_payment_method' => (string)$old['severance_payment_method'],
        'previous_contract_id' => $oldContractId,
    ];
    if (strtoupper((string)$old['contract_type']) === 'PERMANENT') {
        $fields['duration_preset'] = 'PERMANENT';
    }
    $res = p360hr_save_contract_draft($fields, $wageSnap, $actorUserId);
    if (empty($res['ok'])) {
        return $res;
    }
    $newId = (int)$res['contract_id'];
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET previous_contract_id=?, renewal_status=N\'DRAFT_RENEWAL\' WHERE contract_id=?', [$oldContractId, $newId]);
    p360hr_exec("UPDATE dbo.p360_hr_contracts SET renewal_status=N'RENEWED' WHERE contract_id=?", [$oldContractId]);
    p360hr_exec(
        "UPDATE dbo.p360_hr_contract_reminders SET status=N'ACTIONED', action_type=N'RENEW', actioned_at=SYSUTCDATETIME(), actioned_by_user_id=?, related_new_contract_id=?
         WHERE contract_id=? AND status NOT IN (N'ACTIONED', N'CLOSED')",
        [$actorUserId, $newId, $oldContractId]
    );
    return ['ok' => true, 'message' => 'پیش‌نویس تمدید ایجاد شد. امضا/OTP قبلی قابل استفاده نیست.', 'contract_id' => $newId];
}

function p360hr_open_exit_case(int $contractId, int $actorUserId, array $data): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    $ok = p360hr_exec(
        'INSERT INTO dbo.p360_hr_exit_cases
            (employee_id, contract_id, reminder_id, exit_type, last_work_date, reason_fa, notes_fa,
             assets_returned, settlement_status, guarantee_status, contract_status, account_status, confirmed, created_by_user_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?)',
        [
            (int)$c['employee_id'], $contractId,
            ((int)($data['reminder_id'] ?? 0)) ?: null,
            trim((string)($data['exit_type'] ?? '')) ?: null,
            trim((string)($data['last_work_date'] ?? '')) ?: null,
            trim((string)($data['reason_fa'] ?? '')) ?: null,
            trim((string)($data['notes_fa'] ?? '')) ?: null,
            !empty($data['assets_returned']) ? 1 : 0,
            trim((string)($data['settlement_status'] ?? '')) ?: null,
            trim((string)($data['guarantee_status'] ?? '')) ?: null,
            trim((string)($data['contract_status'] ?? 'ENDING')) ?: null,
            trim((string)($data['account_status'] ?? 'PENDING')) ?: null,
            $actorUserId,
        ]
    );
    if (!$ok) {
        return ['ok' => false, 'message' => 'ثبت پیش‌نویس خروج ناموفق بود.'];
    }
    // Opening form does NOT change lifecycle.
    p360hr_exec(
        "UPDATE dbo.p360_hr_contract_reminders SET status=N'ACTIONED', action_type=N'EXIT_DRAFT', actioned_at=SYSUTCDATETIME(), actioned_by_user_id=?
         WHERE contract_id=? AND status NOT IN (N'CLOSED')",
        [$actorUserId, $contractId]
    );
    p360hr_exec("UPDATE dbo.p360_hr_contracts SET renewal_status=N'EXIT_PENDING' WHERE contract_id=? AND is_locked=1", [$contractId]);
    $row = p360hr_one('SELECT TOP 1 exit_id FROM dbo.p360_hr_exit_cases WHERE contract_id=? ORDER BY exit_id DESC', [$contractId]);
    return ['ok' => true, 'message' => 'فرم خروج ثبت شد. تأیید نهایی جداگانه لازم است.', 'exit_id' => (int)($row['exit_id'] ?? 0)];
}

function p360hr_confirm_exit_case(int $exitId, int $actorUserId): array
{
    if (!p360hr_can_manage_personnel()) {
        return ['ok' => false, 'message' => 'تأیید خروج مجاز نیست.'];
    }
    $ex = p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_exit_cases WHERE exit_id=?', [$exitId]);
    if ($ex === null) {
        return ['ok' => false, 'message' => 'پرونده خروج یافت نشد.'];
    }
    if ((int)($ex['confirmed'] ?? 0) === 1) {
        return ['ok' => false, 'message' => 'قبلاً تأیید شده است.'];
    }
    p360hr_exec(
        'UPDATE dbo.p360_hr_exit_cases SET confirmed=1, confirmed_by_user_id=?, confirmed_at=SYSUTCDATETIME() WHERE exit_id=?',
        [$actorUserId, $exitId]
    );
    // Controlled lifecycle update only after confirmation
    p360hr_exec(
        "UPDATE dbo.p360_employees SET lifecycle_state=N'EXITED', employee_status=N'inactive', is_active=0 WHERE employee_id=?",
        [(int)$ex['employee_id']]
    );
    if (!empty($ex['contract_id'])) {
        p360hr_exec("UPDATE dbo.p360_hr_contracts SET renewal_status=N'EXITED' WHERE contract_id=?", [(int)$ex['contract_id']]);
    }
    return ['ok' => true, 'message' => 'خروج با تأیید و حسابرسی ثبت شد.'];
}
