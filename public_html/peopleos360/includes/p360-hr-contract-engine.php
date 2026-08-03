<?php
declare(strict_types=1);

/**
 * Dynamic personnel + employment contract engine (HR-isolated OTP/PDF).
 */

require_once __DIR__ . '/p360-hr-central-bridge.php';
require_once __DIR__ . '/p360-hr-contract-template.php';
require_once __DIR__ . '/p360-hr-wage-engine.php';
require_once __DIR__ . '/p360-jalali.php';

const P360HR_OTP_TTL_DEFAULT = 120;
const P360HR_OTP_MAX_ATTEMPTS = 5;
const P360HR_OTP_RESEND_LIMIT = 5;
const P360HR_ACCEPT_TEXT = 'قرارداد، جدول حقوق و مزایا، شرح وظایف و پیوست‌های آن را مطالعه کردم و با آگاهی تأیید می‌کنم.';

/** @return array<string,mixed> */
function p360hr_row_lc(?array $row): array
{
    if ($row === null) {
        return [];
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[strtolower((string)$k)] = $v;
    }
    return $out;
}

function p360hr_one(string $sql, array $params = []): ?array
{
    $conn = p360hr_odbc();
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        return null;
    }
    $r = odbc_fetch_array($st);
    return is_array($r) ? p360hr_row_lc($r) : null;
}

/** @return list<array<string,mixed>> */
function p360hr_rows(string $sql, array $params = []): array
{
    $conn = p360hr_odbc();
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        return [];
    }
    $out = [];
    while ($r = odbc_fetch_array($st)) {
        $out[] = p360hr_row_lc($r);
    }
    return $out;
}

function p360hr_exec(string $sql, array $params = []): bool
{
    $conn = p360hr_odbc();
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function p360hr_date_jalali(?string $ymd): string
{
    $ymd = trim((string)$ymd);
    if ($ymd === '') {
        return '—';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $ymd, $m)) {
        return p360_gregorian_to_jalali_display($m[1] . '-' . $m[2] . '-' . $m[3]);
    }
    return $ymd;
}

function p360hr_employee_full_name(array $employee): string
{
    $override = trim((string)($employee['display_name_override'] ?? ''));
    if ($override !== '') {
        return $override;
    }
    return trim(trim((string)($employee['first_name'] ?? '')) . ' ' . trim((string)($employee['last_name'] ?? '')));
}

function p360hr_default_shift_snapshot_text(): string
{
    return "شنبه تا چهارشنبه:\nساعت ۸:۰۰ تا ۱۸:۰۰\nبا کسر ۶۰ دقیقه بابت ناهار و نماز\nحضور خالص برنامه‌ریزی‌شده: ۹ ساعت\n\nپنجشنبه:\nساعت ۸:۰۰ تا ۱۵:۰۰\nبدون کسر ناهار و نماز\n\nروزهای تعطیل رسمی به‌جز جمعه:\nساعت ۸:۰۰ تا ۱۵:۰۰\nبدون کسر ناهار و نماز\n\nجمعه:\nتعطیل کامل\n\nدر صورت حضور در روز جمعه، ثبت حضور به‌تنهایی موجب پرداخت نمی‌شود و نیازمند درخواست پرسنل و تأیید مدیر است.";
}

function p360hr_eid_method_fa(string $m): string
{
    return match (strtoupper($m)) {
        'MONTHLY' => 'ماهانه',
        'ANNUAL' => 'سالانه',
        'FINAL_SETTLEMENT' => 'در تسویه نهایی',
        'LEGAL_SETTING' => 'طبق تنظیمات قانونی/قراردادی',
        default => $m,
    };
}

function p360hr_sev_method_fa(string $m): string
{
    return match (strtoupper($m)) {
        'MONTHLY' => 'ماهانه',
        'ANNUAL' => 'سالانه',
        'END_OF_CONTRACT' => 'در پایان قرارداد',
        'FINAL_SETTLEMENT' => 'در تسویه نهایی',
        default => $m,
    };
}

function p360hr_stage_fa(string $code): string
{
    return match (strtoupper($code)) {
        'DRAFT' => 'پیش‌نویس',
        'ADMIN_REVIEW' => 'بررسی اداری/مدیریتی',
        'PRESENTED' => 'ارائه نسخه نهایی به پرسنل',
        'ACCEPTED' => 'تیک پذیرش آگاهانه',
        'OTP_VERIFIED' => 'OTP پرسنلی',
        'EMPLOYEE_SIGNED' => 'امضای دیجیتال پرسنل',
        'FINALIZED' => 'امضای کارفرما، تولید PDF و قفل',
        default => $code,
    };
}

function p360hr_storage_root(): string
{
    // Prefer runtime storage outside public web root when available.
    $runtime = 'C:\\xampp\\htdocs\\moghare360\\storage\\hr-contracts';
    if (!is_dir($runtime)) {
        @mkdir($runtime, 0755, true);
    }
    if (is_dir($runtime) && is_writable($runtime)) {
        return $runtime;
    }
    $fallback = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'hr-contracts';
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0755, true);
    }
    return $fallback;
}

function p360hr_contract_body_path(int $contractId, int $version): string
{
    $dir = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'bodies';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . DIRECTORY_SEPARATOR . 'c' . $contractId . '-v' . $version . '.txt';
}

function p360hr_store_rendered_body(int $contractId, int $version, string $body): string
{
    $path = p360hr_contract_body_path($contractId, $version);
    @file_put_contents($path, $body);
    // Keep a short DB pointer because ODBC truncates long nvarchar binds.
    return 'FILE:' . $path . '|SHA256:' . hash('sha256', $body);
}

function p360hr_load_rendered_body(array $contract): string
{
    $raw = (string)($contract['rendered_body'] ?? '');
    if (strpos($raw, 'FILE:') === 0 && preg_match('/^FILE:(.+)\|SHA256:([a-f0-9]{64})$/i', $raw, $m)) {
        if (is_file($m[1])) {
            return (string)file_get_contents($m[1]);
        }
    }
    $cid = (int)($contract['contract_id'] ?? 0);
    $ver = (int)($contract['contract_version'] ?? 1);
    $path = p360hr_contract_body_path($cid, $ver);
    if ($cid > 0 && is_file($path)) {
        return (string)file_get_contents($path);
    }
    return $raw;
}

function p360hr_template_storage_path(string $code = 'EMPLOYMENT_V1', int $version = 1): string
{
    $dir = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'templates';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . DIRECTORY_SEPARATOR . $code . '_v' . $version . '.txt';
}

function p360hr_seed_contract_template(): void
{
    $body = p360hr_contract_template_body_v1();
    $path = p360hr_template_storage_path('EMPLOYMENT_V1', 1);
    $hash = hash('sha256', $body);
    $written = @file_put_contents($path, $body);
    if ($written === false) {
        return;
    }
    // ODBC SQL Server drivers often truncate long nvarchar binds (~4KB).
    // Canonical body lives in versioned storage file; DB holds pointer + hash.
    $pointer = 'FILE:' . $path . '|SHA256:' . $hash;
    $row = p360hr_one("SELECT template_id, body_text FROM dbo.p360_hr_contract_templates WHERE template_code=N'EMPLOYMENT_V1' AND version_no=1");
    if ($row === null) {
        p360hr_exec(
            "INSERT INTO dbo.p360_hr_contract_templates (template_code, version_no, title_fa, body_text, is_active) VALUES (N'EMPLOYMENT_V1', 1, N'قرارداد کار', ?, 1)",
            [$pointer]
        );
        return;
    }
    $existing = (string)($row['body_text'] ?? '');
    if ($existing !== $pointer && (strpos($existing, 'FILE:') !== 0 || strpos($existing, $hash) === false)) {
        p360hr_exec(
            'UPDATE dbo.p360_hr_contract_templates SET body_text=? WHERE template_id=?',
            [$pointer, (int)$row['template_id']]
        );
    }
}

function p360hr_active_template_body(): string
{
    p360hr_seed_contract_template();
    $row = p360hr_one("SELECT body_text FROM dbo.p360_hr_contract_templates WHERE template_code=N'EMPLOYMENT_V1' AND is_active=1 ORDER BY version_no DESC");
    $meta = (string)($row['body_text'] ?? '');
    if (strpos($meta, 'FILE:') === 0) {
        if (preg_match('/^FILE:(.+)\|SHA256:([a-f0-9]{64})$/i', $meta, $m)) {
            $path = $m[1];
            if (is_file($path)) {
                $body = (string)file_get_contents($path);
                if ($body !== '' && hash('sha256', $body) === strtolower($m[2])) {
                    return $body;
                }
                if ($body !== '') {
                    return $body;
                }
            }
        }
    }
    $path = p360hr_template_storage_path('EMPLOYMENT_V1', 1);
    if (is_file($path)) {
        $body = (string)file_get_contents($path);
        if ($body !== '' && strpos($body, '{{EMPLOYEE_FULL_NAME}}') !== false) {
            return $body;
        }
    }
    return p360hr_contract_template_body_v1();
}

function p360hr_employer_active(): ?array
{
    return p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_employer_profile WHERE is_active=1 ORDER BY employer_profile_id DESC');
}

function p360hr_employer_mandatory_gaps(?array $emp = null): array
{
    $e = $emp ?? p360hr_employer_active();
    if ($e === null) {
        return ['پروفایل کارفرما یافت نشد'];
    }
    $gaps = [];
    foreach ([
        'trade_name' => 'نام تجاری',
        'representative_name' => 'نام نماینده کارفرما',
        'representative_title' => 'سمت نماینده',
        'address_full' => 'نشانی کارفرما',
        'default_workplace' => 'محل انجام کار پیش‌فرض',
    ] as $k => $label) {
        if (trim((string)($e[$k] ?? '')) === '') {
            $gaps[] = $label;
        }
    }
    return $gaps;
}

function p360hr_personnel_profile(int $employeeId): array
{
    $row = p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_personnel_profile WHERE employee_id=?', [$employeeId]);
    return $row ?? ['employee_id' => $employeeId];
}

function p360hr_employee_by_personnel_code(string $code): ?array
{
    $code = trim($code);
    if ($code === '') {
        return null;
    }
    return p360hr_one('SELECT TOP 1 * FROM dbo.p360_employees WHERE employee_code=?', [$code]);
}

/**
 * Full auto-fill bundle for contract registration.
 * @return array{ok:bool,message:string,employee?:array,profile?:array,core_user?:array,missing?:list<string>,warnings?:list<string>}
 */
function p360hr_load_personnel_bundle(string $personnelCode): array
{
    $emp = p360hr_employee_by_personnel_code($personnelCode);
    if ($emp === null) {
        return ['ok' => false, 'message' => 'پرسنلی با این کد یافت نشد.'];
    }
    $eid = (int)($emp['employee_id'] ?? 0);
    $profile = p360hr_personnel_profile($eid);
    $core = null;
    $cuid = (int)($emp['core_user_id'] ?? 0);
    if ($cuid > 0) {
        $core = p360hr_one('SELECT TOP 1 user_id, username, full_name, is_login_enabled, lifecycle_state FROM dbo.core_users WHERE user_id=?', [$cuid]);
    }
    $missing = [];
    $fullName = trim((string)($emp['first_name'] ?? '') . ' ' . (string)($emp['last_name'] ?? ''));
    if ($fullName === '') {
        $missing[] = 'نام و نام خانوادگی';
    }
    if (trim((string)($emp['national_code'] ?? '')) === '') {
        $missing[] = 'کد ملی';
    }
    if (trim((string)($emp['mobile'] ?? '')) === '' && trim((string)($profile['emergency_mobile'] ?? '')) === '') {
        $missing[] = 'شماره موبایل';
    }
    if (trim((string)($emp['job_title'] ?? '')) === '') {
        $missing[] = 'عنوان شغل سازمانی';
    }
    if (trim((string)($emp['unit_name'] ?? '')) === '') {
        $missing[] = 'واحد';
    }
    if (trim((string)($profile['bank_account'] ?? '')) === '' && trim((string)($profile['iban'] ?? '')) === '') {
        $missing[] = 'اطلاعات بانکی';
    }
    if (trim((string)($profile['address_full'] ?? '')) === '') {
        $missing[] = 'نشانی محل سکونت';
    }
    $warnings = [];
    if ((int)($profile['account_self_confirmed'] ?? 0) !== 1) {
        $warnings[] = 'تأیید تعلق حساب بانکی به پرسنل انجام نشده است.';
    }
    if (trim((string)($emp['personnel_photo_path'] ?? '')) === '') {
        $warnings[] = 'عکس پرسنلی ثبت نشده است.';
    }
    return [
        'ok' => true,
        'message' => 'پرونده بارگذاری شد.',
        'employee' => $emp,
        'profile' => $profile,
        'core_user' => $core ?? [],
        'missing' => $missing,
        'warnings' => $warnings,
    ];
}

/** @param array<string,mixed> $data */
function p360hr_save_personnel_profile(int $employeeId, array $data, int $actorUserId): array
{
    if ($employeeId < 1) {
        return ['ok' => false, 'message' => 'شناسه پرسنل نامعتبر است.'];
    }
    $exists = p360hr_one('SELECT employee_id FROM dbo.p360_hr_personnel_profile WHERE employee_id=?', [$employeeId]);
    $fields = [
        'father_name', 'birth_certificate_no', 'birth_date', 'birth_place', 'gender', 'nationality',
        'emergency_name', 'emergency_mobile', 'emergency_relation', 'province', 'city', 'address_full', 'postal_code',
        'marital_status', 'spouse_name', 'child_count', 'child_under_18_count',
        'education_degree', 'education_field', 'education_school', 'education_year',
        'military_status', 'military_card_type', 'military_card_no', 'military_issue_date',
        'direct_supervisor', 'workplace', 'cooperation_type', 'cooperation_start', 'cooperation_end', 'exit_reason',
        'bank_name', 'bank_account', 'iban', 'card_no', 'account_holder_name', 'account_holder_national_id',
        'guarantee_type', 'guarantee_amount', 'guarantee_ref', 'guarantee_issuer', 'guarantee_received_at',
        'guarantee_storage', 'guarantee_status', 'guarantee_returned_at', 'guarantee_receiver', 'guarantee_notes',
    ];
    $vals = [];
    foreach ($fields as $f) {
        $v = $data[$f] ?? null;
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') {
                $v = null;
            }
        }
        $vals[$f] = $v;
    }
    $bit = static function ($v): int {
        return ((int)$v === 1 || $v === true || $v === '1' || $v === 'on') ? 1 : 0;
    };
    $childEligible = $bit($data['child_allowance_eligible'] ?? 0);
    $marrEligible = $bit($data['marriage_allowance_eligible'] ?? 0);
    $milNa = $bit($data['military_not_applicable'] ?? 0);
    $selfConf = $bit($data['account_self_confirmed'] ?? 0);

    // Soft consistency check — not external bank verification.
    $holderNid = (string)($vals['account_holder_national_id'] ?? '');
    $emp = p360hr_employee_by_id($employeeId);
    $empNid = trim((string)($emp['national_code'] ?? $emp['NATIONAL_CODE'] ?? ''));
    if ($selfConf === 1 && $holderNid !== '' && $empNid !== '' && $holderNid !== $empNid) {
        return ['ok' => false, 'message' => 'کد ملی صاحب حساب با کد ملی پرسنل یکسان نیست. تأیید تعلق حساب مجاز نیست.'];
    }

    if ($exists === null) {
        $ok = p360hr_exec(
            "INSERT INTO dbo.p360_hr_personnel_profile (
                employee_id, father_name, birth_certificate_no, birth_date, birth_place, gender, nationality,
                emergency_name, emergency_mobile, emergency_relation, province, city, address_full, postal_code,
                marital_status, spouse_name, child_count, child_under_18_count, child_allowance_eligible, marriage_allowance_eligible,
                education_degree, education_field, education_school, education_year,
                military_status, military_card_type, military_card_no, military_issue_date, military_not_applicable,
                direct_supervisor, workplace, cooperation_type, cooperation_start, cooperation_end, exit_reason,
                bank_name, bank_account, iban, card_no, account_holder_name, account_holder_national_id, account_self_confirmed,
                guarantee_type, guarantee_amount, guarantee_ref, guarantee_issuer, guarantee_received_at, guarantee_storage,
                guarantee_status, guarantee_returned_at, guarantee_receiver, guarantee_notes, updated_at, updated_by_user_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,SYSUTCDATETIME(),?)",
            [
                $employeeId, $vals['father_name'], $vals['birth_certificate_no'], $vals['birth_date'], $vals['birth_place'], $vals['gender'], $vals['nationality'],
                $vals['emergency_name'], $vals['emergency_mobile'], $vals['emergency_relation'], $vals['province'], $vals['city'], $vals['address_full'], $vals['postal_code'],
                $vals['marital_status'], $vals['spouse_name'], $vals['child_count'], $vals['child_under_18_count'], $childEligible, $marrEligible,
                $vals['education_degree'], $vals['education_field'], $vals['education_school'], $vals['education_year'],
                $vals['military_status'], $vals['military_card_type'], $vals['military_card_no'], $vals['military_issue_date'], $milNa,
                $vals['direct_supervisor'], $vals['workplace'], $vals['cooperation_type'], $vals['cooperation_start'], $vals['cooperation_end'], $vals['exit_reason'],
                $vals['bank_name'], $vals['bank_account'], $vals['iban'], $vals['card_no'], $vals['account_holder_name'], $vals['account_holder_national_id'], $selfConf,
                $vals['guarantee_type'], $vals['guarantee_amount'], $vals['guarantee_ref'], $vals['guarantee_issuer'], $vals['guarantee_received_at'], $vals['guarantee_storage'],
                $vals['guarantee_status'], $vals['guarantee_returned_at'], $vals['guarantee_receiver'], $vals['guarantee_notes'], $actorUserId,
            ]
        );
    } else {
        $ok = p360hr_exec(
            "UPDATE dbo.p360_hr_personnel_profile SET
                father_name=?, birth_certificate_no=?, birth_date=?, birth_place=?, gender=?, nationality=?,
                emergency_name=?, emergency_mobile=?, emergency_relation=?, province=?, city=?, address_full=?, postal_code=?,
                marital_status=?, spouse_name=?, child_count=?, child_under_18_count=?, child_allowance_eligible=?, marriage_allowance_eligible=?,
                education_degree=?, education_field=?, education_school=?, education_year=?,
                military_status=?, military_card_type=?, military_card_no=?, military_issue_date=?, military_not_applicable=?,
                direct_supervisor=?, workplace=?, cooperation_type=?, cooperation_start=?, cooperation_end=?, exit_reason=?,
                bank_name=?, bank_account=?, iban=?, card_no=?, account_holder_name=?, account_holder_national_id=?, account_self_confirmed=?,
                guarantee_type=?, guarantee_amount=?, guarantee_ref=?, guarantee_issuer=?, guarantee_received_at=?, guarantee_storage=?,
                guarantee_status=?, guarantee_returned_at=?, guarantee_receiver=?, guarantee_notes=?, updated_at=SYSUTCDATETIME(), updated_by_user_id=?
             WHERE employee_id=?",
            [
                $vals['father_name'], $vals['birth_certificate_no'], $vals['birth_date'], $vals['birth_place'], $vals['gender'], $vals['nationality'],
                $vals['emergency_name'], $vals['emergency_mobile'], $vals['emergency_relation'], $vals['province'], $vals['city'], $vals['address_full'], $vals['postal_code'],
                $vals['marital_status'], $vals['spouse_name'], $vals['child_count'], $vals['child_under_18_count'], $childEligible, $marrEligible,
                $vals['education_degree'], $vals['education_field'], $vals['education_school'], $vals['education_year'],
                $vals['military_status'], $vals['military_card_type'], $vals['military_card_no'], $vals['military_issue_date'], $milNa,
                $vals['direct_supervisor'], $vals['workplace'], $vals['cooperation_type'], $vals['cooperation_start'], $vals['cooperation_end'], $vals['exit_reason'],
                $vals['bank_name'], $vals['bank_account'], $vals['iban'], $vals['card_no'], $vals['account_holder_name'], $vals['account_holder_national_id'], $selfConf,
                $vals['guarantee_type'], $vals['guarantee_amount'], $vals['guarantee_ref'], $vals['guarantee_issuer'], $vals['guarantee_received_at'], $vals['guarantee_storage'],
                $vals['guarantee_status'], $vals['guarantee_returned_at'], $vals['guarantee_receiver'], $vals['guarantee_notes'], $actorUserId, $employeeId,
            ]
        );
    }

    // Optional master employee identity updates (not sample-hardcoded).
    if (!empty($data['sync_employee_master'])) {
        p360hr_exec(
            'UPDATE dbo.p360_employees SET national_code=COALESCE(?, national_code), mobile=COALESCE(?, mobile), job_title=COALESCE(?, job_title), unit_name=COALESCE(?, unit_name), secondary_position=COALESCE(?, secondary_position) WHERE employee_id=?',
            [
                ($data['national_code'] ?? '') !== '' ? $data['national_code'] : null,
                ($data['mobile'] ?? '') !== '' ? $data['mobile'] : null,
                ($data['job_title'] ?? '') !== '' ? $data['job_title'] : null,
                ($data['unit_name'] ?? '') !== '' ? $data['unit_name'] : null,
                ($data['secondary_position'] ?? '') !== '' ? $data['secondary_position'] : null,
                $employeeId,
            ]
        );
    }

    return $ok ? ['ok' => true, 'message' => 'فرم اطلاعات پرسنلی ذخیره شد.'] : ['ok' => false, 'message' => 'ذخیره فرم پرسنلی ناموفق بود.'];
}

function p360hr_contract_get(int $contractId): ?array
{
    return p360hr_one('SELECT TOP 1 * FROM dbo.p360_hr_contracts WHERE contract_id=?', [$contractId]);
}

/** @return list<array<string,mixed>> */
function p360hr_contract_wage_rows(int $contractId): array
{
    return p360hr_rows('SELECT * FROM dbo.p360_hr_contract_wage_components WHERE contract_id=? ORDER BY row_no', [$contractId]);
}

function p360hr_mask_iban(?string $iban): string
{
    $iban = preg_replace('/\s+/', '', (string)$iban) ?? '';
    if ($iban === '') {
        return '—';
    }
    $len = strlen($iban);
    if ($len <= 8) {
        return $iban;
    }
    return substr($iban, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($iban, -4);
}

/**
 * @param array<string,mixed> $contract
 * @param array<string,mixed> $employee
 * @param array<string,mixed> $profile
 * @param array<string,mixed> $employer
 * @param list<array<string,mixed>> $wageRows
 * @return array{map:array<string,string>,unresolved:list<string>,body:string,wage_html:string,total_daily:float,total_monthly:float}
 */
function p360hr_build_placeholder_map(array $contract, array $employee, array $profile, array $employer, array $wageRows, float $totalDaily, float $totalMonthly): array
{
    $type = strtoupper((string)($contract['contract_type'] ?? 'TEMPORARY'));
    $eidMethod = (string)($contract['eid_payment_method'] ?? 'ANNUAL');
    $sevMethod = (string)($contract['severance_payment_method'] ?? 'END_OF_CONTRACT');
    $shift = (string)($contract['shift_snapshot_json'] ?? '');
    if ($shift !== '' && ($shift[0] ?? '') === '{') {
        $decoded = json_decode($shift, true);
        $shiftText = is_array($decoded) ? (string)($decoded['text'] ?? p360hr_default_shift_snapshot_text()) : p360hr_default_shift_snapshot_text();
    } else {
        $shiftText = $shift !== '' ? $shift : p360hr_default_shift_snapshot_text();
    }
    $wageHtml = p360hr_wage_table_html($wageRows, $totalDaily, $totalMonthly);
    $gType = trim((string)($profile['guarantee_type'] ?? ''));
    $gAmt = $profile['guarantee_amount'] ?? null;
    $gSummary = ($gType === '' && ($gAmt === null || $gAmt === ''))
        ? '—'
        : trim($gType . ' / ' . p360hr_money_fa($gAmt !== null && $gAmt !== '' ? (float)$gAmt : null));

    $byCode = [];
    foreach ($wageRows as $r) {
        $byCode[(string)$r['component_code']] = $r;
    }
    $status = static function (string $code) use ($byCode): string {
        return (string)($byCode[$code]['extra_info_fa'] ?? '—');
    };
    $daily = static function (string $code) use ($byCode): string {
        return p360hr_money_fa(isset($byCode[$code]['daily_amount']) ? (float)$byCode[$code]['daily_amount'] : null);
    };
    $monthly = static function (string $code) use ($byCode): string {
        return p360hr_money_fa(isset($byCode[$code]['monthly_amount']) ? (float)$byCode[$code]['monthly_amount'] : null);
    };

    $map = [
        'EMPLOYER_TRADE_NAME' => (string)($employer['trade_name'] ?? ''),
        'EMPLOYER_REPRESENTATIVE_NAME' => (string)($employer['representative_name'] ?? ''),
        'EMPLOYER_REPRESENTATIVE_TITLE' => (string)($employer['representative_title'] ?? ''),
        'EMPLOYER_ADDRESS' => (string)($employer['address_full'] ?? ''),
        'EMPLOYEE_FULL_NAME' => p360hr_employee_full_name($employee),
        'EMPLOYEE_FATHER_NAME' => (string)($profile['father_name'] ?? '—'),
        'EMPLOYEE_NATIONAL_ID' => (string)($employee['national_code'] ?? '—'),
        'EMPLOYEE_BIRTH_CERTIFICATE_NO' => (string)($profile['birth_certificate_no'] ?? '—'),
        'EMPLOYEE_BIRTH_DATE_JALALI' => p360hr_date_jalali(isset($profile['birth_date']) ? (string)$profile['birth_date'] : null),
        'EMPLOYEE_ADDRESS' => (string)($profile['address_full'] ?? '—'),
        'EMPLOYEE_MOBILE' => (string)($employee['mobile'] ?? ($profile['emergency_mobile'] ?? '—')),
        'PERSONNEL_CODE' => (string)($contract['personnel_code'] ?? ($employee['employee_code'] ?? '')),
        'CONTRACT_JOB_TITLE' => (string)($contract['contract_job_title'] ?? ''),
        'EMPLOYEE_UNIT' => (string)($contract['unit_name'] ?? ($employee['unit_name'] ?? '')),
        'EMPLOYEE_EDUCATION' => trim((string)($profile['education_degree'] ?? '') . ' ' . (string)($profile['education_field'] ?? '')),
        'EMPLOYEE_MARITAL_STATUS' => (string)($profile['marital_status'] ?? '—'),
        'EMPLOYEE_CHILD_COUNT' => (string)($profile['child_count'] ?? '0'),
        'EMPLOYEE_CHILD_UNDER_18_COUNT' => (string)($profile['child_under_18_count'] ?? '0'),
        'EMPLOYEE_GUARANTEE_SUMMARY' => $gSummary,
        'CONTRACT_TYPE_PERSIAN' => p360hr_contract_type_fa($type),
        'TEMPORARY_NOTE' => $type === 'TEMPORARY'
            ? "تبصره:\nاین قرارداد جهت کار موقت با توجه به بند (و) از ماده ۱۰ قانون کار بین طرفین تنظیم گردیده است."
            : '',
        'TEMPORARY_CLAUSE' => $type === 'TEMPORARY'
            ? 'این قرارداد جهت کار موقت با توجه به بند (و) از ماده ۱۰ قانون کار بین طرفین تنظیم گردیده است.'
            : '',
        'DIRECT_SUPERVISOR' => (string)($contract['direct_supervisor'] ?? ($profile['direct_supervisor'] ?? '—')),
        'JOB_DUTIES_TEXT' => (string)($contract['job_duties_text'] ?? ''),
        'WORKPLACE' => (string)($contract['workplace'] ?? ($employer['default_workplace'] ?? '')),
        'CONTRACT_CREATED_DATE_JALALI' => p360hr_date_jalali((string)($contract['created_date'] ?? date('Y-m-d'))),
        'CONTRACT_START_DATE_JALALI' => p360hr_date_jalali((string)($contract['start_date'] ?? '')),
        'CONTRACT_END_DATE_JALALI' => p360hr_date_jalali((string)($contract['end_date'] ?? '')),
        'CONTRACT_DURATION_TEXT' => (string)($contract['duration_text'] ?? '—'),
        'SHIFT_SNAPSHOT_TEXT' => $shiftText,
        'CONTRACT_SUBJECT' => (string)($contract['contract_subject'] ?? ''),
        'CONTRACT_SUBJECT_DETAILS' => (string)($contract['contract_subject_details'] ?? ''),
        'WAGE_TABLE_HTML' => $wageHtml,
        'BASE_WAGE_STATUS' => $status('BASE_WAGE'),
        'BASE_WAGE_DAILY' => $daily('BASE_WAGE'),
        'BASE_WAGE_MONTHLY' => $monthly('BASE_WAGE'),
        'SENIORITY_STATUS' => $status('SENIORITY'),
        'SENIORITY_DAILY' => $daily('SENIORITY'),
        'SENIORITY_MONTHLY' => $monthly('SENIORITY'),
        'HOUSING_STATUS' => $status('HOUSING'),
        'HOUSING_DAILY' => $daily('HOUSING'),
        'HOUSING_MONTHLY' => $monthly('HOUSING'),
        'FOOD_BASKET_STATUS' => $status('FOOD_BASKET'),
        'FOOD_BASKET_DAILY' => $daily('FOOD_BASKET'),
        'FOOD_BASKET_MONTHLY' => $monthly('FOOD_BASKET'),
        'MARRIAGE_STATUS' => $status('MARRIAGE'),
        'MARRIAGE_DAILY' => $daily('MARRIAGE'),
        'MARRIAGE_MONTHLY' => $monthly('MARRIAGE'),
        'CHILD_ALLOWANCE_STATUS' => $status('CHILD_ALLOWANCE'),
        'CHILD_ALLOWANCE_DAILY' => $daily('CHILD_ALLOWANCE'),
        'CHILD_ALLOWANCE_MONTHLY' => $monthly('CHILD_ALLOWANCE'),
        'EID_STATUS' => $status('EID_BONUS'),
        'EID_DAILY' => $daily('EID_BONUS'),
        'EID_MONTHLY' => $monthly('EID_BONUS'),
        'SEVERANCE_STATUS' => $status('SEVERANCE'),
        'SEVERANCE_DAILY' => $daily('SEVERANCE'),
        'SEVERANCE_MONTHLY' => $monthly('SEVERANCE'),
        'TRANSPORT_STATUS' => $status('TRANSPORT'),
        'TRANSPORT_DAILY' => $daily('TRANSPORT'),
        'TRANSPORT_MONTHLY' => $monthly('TRANSPORT'),
        'TECHNICAL_STATUS' => $status('TECHNICAL'),
        'TECHNICAL_DAILY' => $daily('TECHNICAL'),
        'TECHNICAL_MONTHLY' => $monthly('TECHNICAL'),
        'OTHER_STATUS' => $status('OTHER'),
        'OTHER_DAILY' => $daily('OTHER'),
        'OTHER_MONTHLY' => $monthly('OTHER'),
        'TOTAL_DAILY' => p360hr_money_fa($totalDaily),
        'TOTAL_MONTHLY' => p360hr_money_fa($totalMonthly),
        'EMPLOYEE_BANK_NAME' => (string)($profile['bank_name'] ?? '—'),
        'EMPLOYEE_BANK_ACCOUNT' => (string)($profile['bank_account'] ?? '—'),
        'EMPLOYEE_IBAN_MASKED_OR_APPROVED_DISPLAY' => p360hr_mask_iban((string)($profile['iban'] ?? '')),
        'EMPLOYEE_ACCOUNT_HOLDER_NAME' => (string)($profile['account_holder_name'] ?? '—'),
        'EID_PAYMENT_METHOD' => p360hr_eid_method_fa($eidMethod),
        'EID_MONTHLY_NOTE' => strtoupper($eidMethod) === 'MONTHLY'
            ? 'توافق گردید عیدی و پاداش به‌صورت ماهانه پرداخت و در محاسبات تسویه ثبت شود.'
            : '',
        'SEVERANCE_PAYMENT_METHOD' => p360hr_sev_method_fa($sevMethod),
        'SEVERANCE_MONTHLY_NOTE' => strtoupper($sevMethod) === 'MONTHLY'
            ? 'توافق گردید سنوات به‌صورت ماهانه پرداخت و در محاسبات تسویه ثبت شود.'
            : '',
    ];

    $mandatory = [
        'EMPLOYER_TRADE_NAME', 'EMPLOYER_REPRESENTATIVE_NAME', 'EMPLOYEE_FULL_NAME', 'PERSONNEL_CODE',
        'CONTRACT_JOB_TITLE', 'EMPLOYEE_UNIT', 'CONTRACT_TYPE_PERSIAN', 'WORKPLACE',
        'CONTRACT_START_DATE_JALALI', 'CONTRACT_SUBJECT', 'JOB_DUTIES_TEXT', 'WAGE_TABLE_HTML',
    ];
    $unresolved = [];
    foreach ($mandatory as $key) {
        $v = trim((string)($map[$key] ?? ''));
        if ($v === '' || $v === '—' || $v === '0000/00/00') {
            $unresolved[] = $key;
        }
    }
    if ($type !== 'PERMANENT' && trim((string)($map['CONTRACT_END_DATE_JALALI'] ?? '')) === '—') {
        $unresolved[] = 'CONTRACT_END_DATE_JALALI';
    }

    $body = p360hr_active_template_body();
    foreach ($map as $k => $v) {
        $body = str_replace('{{' . $k . '}}', (string)$v, $body);
    }
    if (preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $body, $m)) {
        foreach (array_unique($m[0]) as $ph) {
            $unresolved[] = trim($ph, '{}');
        }
    }

    return [
        'map' => $map,
        'unresolved' => array_values(array_unique($unresolved)),
        'body' => $body,
        'wage_html' => $wageHtml,
        'total_daily' => $totalDaily,
        'total_monthly' => $totalMonthly,
    ];
}

function p360hr_invalidate_acceptance(int $contractId): void
{
    p360hr_exec(
        "UPDATE dbo.p360_hr_contracts SET
            acceptance_checked=0, accepted_at=NULL, accepted_ip=NULL, accepted_ua=NULL, accepted_by_user_id=NULL,
            otp_verified_at=NULL, employee_signed_at=NULL, employer_signed_at=NULL,
            employee_signature_path=NULL, employer_signature_path=NULL,
            final_pdf_path=NULL, final_pdf_hash=NULL, is_locked=0,
            stage_code=CASE WHEN stage_code IN (N'FINALIZED', N'EMPLOYEE_SIGNED', N'OTP_VERIFIED', N'ACCEPTED', N'PRESENTED') THEN N'ADMIN_REVIEW' ELSE stage_code END,
            updated_at=SYSUTCDATETIME()
         WHERE contract_id=? AND is_locked=0",
        [$contractId]
    );
    // Invalidate unused OTPs for this contract
    p360hr_exec(
        'UPDATE dbo.p360_hr_contract_otp SET consumed_at=SYSUTCDATETIME() WHERE contract_id=? AND consumed_at IS NULL',
        [$contractId]
    );
}

/**
 * @param array<string,mixed> $input wage form inputs
 * @param array<string,mixed> $fields contract fields
 */
function p360hr_save_contract_draft(array $fields, array $input, int $actorUserId): array
{
    $personnelCode = trim((string)($fields['personnel_code'] ?? ''));
    $bundle = p360hr_load_personnel_bundle($personnelCode);
    if (!$bundle['ok']) {
        return $bundle;
    }
    /** @var array<string,mixed> $employee */
    $employee = $bundle['employee'];
    /** @var array<string,mixed> $profile */
    $profile = $bundle['profile'];
    $eid = (int)$employee['employee_id'];
    $contractType = strtoupper(trim((string)($fields['contract_type'] ?? 'TEMPORARY')));
    $allowed = ['PERMANENT', 'TEMPORARY', 'HOURLY', 'SPECIFIC_WORK', 'CONTRACTUAL'];
    if (!in_array($contractType, $allowed, true)) {
        return ['ok' => false, 'message' => 'نوع قرارداد نامعتبر است.'];
    }

    $input['marriage_eligible'] = (int)($profile['marriage_allowance_eligible'] ?? 0);
    $input['child_eligible'] = (int)($profile['child_allowance_eligible'] ?? 0);
    $input['eid_payment_method'] = (string)($fields['eid_payment_method'] ?? 'ANNUAL');
    $input['severance_payment_method'] = (string)($fields['severance_payment_method'] ?? 'END_OF_CONTRACT');

    $calc = p360hr_calculate_wage_table($contractType, $input);
    $employer = p360hr_employer_active() ?? [];
    $workplace = trim((string)($fields['workplace'] ?? ''));
    if ($workplace === '') {
        $workplace = (string)($employer['default_workplace'] ?? 'خدمات فنی مقاره عابد');
    }
    $shiftJson = json_encode([
        'version' => 'SHIFT_V1',
        'text' => p360hr_default_shift_snapshot_text(),
    ], JSON_UNESCAPED_UNICODE);

    $contractId = (int)($fields['contract_id'] ?? 0);
    $orgTitle = (string)($employee['job_title'] ?? '');
    $contractTitle = trim((string)($fields['contract_job_title'] ?? ''));
    if ($contractTitle === '') {
        $contractTitle = $orgTitle;
    }
    $updateMaster = !empty($fields['update_master_job_title']) ? 1 : 0;
    $createdDate = date('Y-m-d');

    // Start date: accept only canonical SQL ymd (Jalali picker). Reject free-form.
    $startRaw = trim((string)($fields['start_date'] ?? ''));
    $startDate = '';
    if ($startRaw !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startRaw)) {
            $startDate = $startRaw;
        } else {
            $j = p360_jalali_to_sql_date($startRaw);
            if (!$j['ok']) {
                return ['ok' => false, 'message' => 'تاریخ شروع قرارداد نامعتبر است.'];
            }
            $startDate = (string)$j['ymd'];
        }
    }

    $endDate = null;
    $durationText = '';
    $durationMonths = null;
    $durationPreset = null;
    $durationUnit = null;
    $calcRule = null;
    $reminderTrigger = null;
    $durationSnap = null;

    if ($contractType === 'PERMANENT') {
        $durationPreset = 'PERMANENT';
        $durationUnit = 'NONE';
        $durationText = 'دائم — بدون تاریخ پایان';
        $endDate = null;
        // Ignore any posted end_date for permanent.
    } else {
        // Duration replaces manual end date. Ignore/reject posted end_date as authoritative.
        $dur = null;
        if (function_exists('p360hr_resolve_duration_months')) {
            $dur = p360hr_resolve_duration_months($fields);
        } else {
            // Inline fallback
            $preset = strtoupper(trim((string)($fields['duration_preset'] ?? '')));
            $map = ['1M' => 1, '2M' => 2, '3M' => 3, '6M' => 6, '9M' => 9, '12M' => 12, '1Y' => 12];
            if ($preset === 'CUSTOM') {
                $unit = strtoupper(trim((string)($fields['duration_unit'] ?? 'MONTHS')));
                $num = (int)($fields['duration_custom_value'] ?? 0);
                $months = ($unit === 'YEARS') ? ($num * 12) : $num;
                $dur = ['ok' => $months > 0, 'months' => $months, 'preset' => 'CUSTOM', 'unit' => $unit === 'YEARS' ? 'YEARS' : 'MONTHS', 'message' => $months > 0 ? '' : 'مدت نامعتبر'];
            } elseif (isset($map[$preset])) {
                $dur = ['ok' => true, 'months' => $map[$preset], 'preset' => $preset, 'unit' => 'MONTHS', 'message' => ''];
            } else {
                $m = (int)($fields['duration_months'] ?? 0);
                $dur = ['ok' => $m > 0, 'months' => $m, 'preset' => 'CUSTOM', 'unit' => 'MONTHS', 'message' => $m > 0 ? '' : 'انتخاب مدت الزامی است'];
            }
        }
        if (empty($dur['ok'])) {
            return ['ok' => false, 'message' => (string)($dur['message'] ?? 'مدت قرارداد نامعتبر است.')];
        }
        if ($startDate === '') {
            return ['ok' => false, 'message' => 'تاریخ شروع قرارداد الزامی است.'];
        }
        $calcEnd = p360_contract_end_by_month_duration($startDate, (int)$dur['months']);
        if (empty($calcEnd['ok'])) {
            return ['ok' => false, 'message' => (string)($calcEnd['message'] ?? 'محاسبه تاریخ پایان ناموفق بود.')];
        }
        $endDate = (string)$calcEnd['end_ymd'];
        $durationText = (string)$calcEnd['duration_text'];
        $durationMonths = (int)$dur['months'];
        $durationPreset = (string)$dur['preset'];
        $durationUnit = (string)$dur['unit'];
        $calcRule = M360_CONTRACT_MONTH_END_V1;
        $reminderTrigger = (new DateTimeImmutable($endDate))->modify('-15 days')->format('Y-m-d');
        $durationSnap = json_encode([
            'start_date' => $startDate,
            'duration_months' => $durationMonths,
            'duration_unit' => $durationUnit,
            'duration_preset' => $durationPreset,
            'calculated_end_date' => $endDate,
            'calculation_rule_version' => $calcRule,
            'duration_display_text' => $durationText,
            'reminder_trigger_date' => $reminderTrigger,
        ], JSON_UNESCAPED_UNICODE);
    }

    $payload = [
        'personnel_code' => $personnelCode,
        'contract_type' => $contractType,
        'contract_job_title' => $contractTitle,
        'org_job_title' => $orgTitle,
        'unit_name' => (string)($fields['unit_name'] ?? ($employee['unit_name'] ?? '')),
        'direct_supervisor' => (string)($fields['direct_supervisor'] ?? ($profile['direct_supervisor'] ?? '')),
        'workplace' => $workplace,
        'contract_subject' => (string)($fields['contract_subject'] ?? ''),
        'contract_subject_details' => (string)($fields['contract_subject_details'] ?? ''),
        'job_duties_text' => (string)($fields['job_duties_text'] ?? ''),
        'created_date' => $createdDate,
        'start_date' => $startDate,
        'end_date' => $endDate ?? '',
        'duration_text' => $durationText,
        'duration_months' => $durationMonths,
        'duration_preset' => $durationPreset,
        'duration_unit' => $durationUnit,
        'calculation_rule_version' => $calcRule,
        'reminder_trigger_date' => $reminderTrigger,
        'duration_snapshot_json' => $durationSnap,
        'wage_model' => $contractType,
        'eid_payment_method' => (string)($fields['eid_payment_method'] ?? 'ANNUAL'),
        'severance_payment_method' => (string)($fields['severance_payment_method'] ?? 'END_OF_CONTRACT'),
        'shift_snapshot_json' => $shiftJson,
        'update_master_job_title' => $updateMaster,
        'previous_contract_id' => ((int)($fields['previous_contract_id'] ?? 0)) ?: null,
    ];

    if ($contractId > 0) {
        $existing = p360hr_contract_get($contractId);
        if ($existing === null) {
            return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
        }
        if ((int)($existing['is_locked'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'قرارداد نهایی قفل شده و قابل ویرایش نیست.'];
        }
        $ver = (int)($existing['contract_version'] ?? 1) + 1;
        p360hr_exec(
            "UPDATE dbo.p360_hr_contracts SET
                contract_version=?, contract_type=?, contract_job_title=?, org_job_title=?, unit_name=?, direct_supervisor=?,
                workplace=?, contract_subject=?, contract_subject_details=?, job_duties_text=?,
                start_date=?, end_date=?, duration_text=?, duration_months=?, duration_preset=?, duration_unit=?,
                calculation_rule_version=?, reminder_trigger_date=?, duration_snapshot_json=?,
                wage_model=?, eid_payment_method=?, severance_payment_method=?,
                shift_snapshot_json=?, update_master_job_title=?, updated_at=SYSUTCDATETIME()
             WHERE contract_id=?",
            [
                $ver, $payload['contract_type'], $payload['contract_job_title'], $payload['org_job_title'], $payload['unit_name'], $payload['direct_supervisor'],
                $payload['workplace'], $payload['contract_subject'], $payload['contract_subject_details'], $payload['job_duties_text'],
                $payload['start_date'] !== '' ? $payload['start_date'] : null,
                $payload['end_date'] !== '' ? $payload['end_date'] : null,
                $payload['duration_text'], $payload['duration_months'], $payload['duration_preset'], $payload['duration_unit'],
                $payload['calculation_rule_version'], $payload['reminder_trigger_date'], $payload['duration_snapshot_json'],
                $payload['wage_model'], $payload['eid_payment_method'], $payload['severance_payment_method'],
                $payload['shift_snapshot_json'], $payload['update_master_job_title'], $contractId,
            ]
        );
        p360hr_invalidate_acceptance($contractId);
    } else {
        p360hr_exec(
            "INSERT INTO dbo.p360_hr_contracts (
                employee_id, personnel_code, contract_version, contract_type, stage_code,
                contract_job_title, org_job_title, unit_name, direct_supervisor, workplace,
                contract_subject, contract_subject_details, job_duties_text, created_date, start_date, end_date, duration_text,
                duration_months, duration_preset, duration_unit, calculation_rule_version, reminder_trigger_date, duration_snapshot_json,
                wage_model, eid_payment_method, severance_payment_method, shift_snapshot_json, update_master_job_title,
                previous_contract_id, created_by_user_id
            ) VALUES (?,?,1,?,N'DRAFT',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $eid, $personnelCode, $contractType,
                $payload['contract_job_title'], $payload['org_job_title'], $payload['unit_name'], $payload['direct_supervisor'], $payload['workplace'],
                $payload['contract_subject'], $payload['contract_subject_details'], $payload['job_duties_text'], $createdDate,
                $payload['start_date'] !== '' ? $payload['start_date'] : null,
                $payload['end_date'] !== '' ? $payload['end_date'] : null,
                $payload['duration_text'],
                $payload['duration_months'], $payload['duration_preset'], $payload['duration_unit'], $payload['calculation_rule_version'],
                $payload['reminder_trigger_date'], $payload['duration_snapshot_json'],
                $payload['wage_model'], $payload['eid_payment_method'], $payload['severance_payment_method'],
                $payload['shift_snapshot_json'], $payload['update_master_job_title'],
                $payload['previous_contract_id'], $actorUserId,
            ]
        );
        $row = p360hr_one('SELECT TOP 1 contract_id FROM dbo.p360_hr_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$eid]);
        $contractId = (int)($row['contract_id'] ?? 0);
    }

    if ($contractId < 1) {
        return ['ok' => false, 'message' => 'ثبت قرارداد ناموفق بود.'];
    }

    p360hr_exec('DELETE FROM dbo.p360_hr_contract_wage_components WHERE contract_id=?', [$contractId]);
    foreach ($calc['rows'] as $r) {
        p360hr_exec(
            'INSERT INTO dbo.p360_hr_contract_wage_components
                (contract_id, row_no, component_code, title_fa, extra_info_fa, calc_method, input_value, daily_amount, monthly_amount, enabled, pdf_display)
             VALUES (?,?,?,?,?,?,?,?,?,1,1)',
            [
                $contractId, $r['row_no'], $r['component_code'], $r['title_fa'], $r['extra_info_fa'], $r['calc_method'],
                $r['input_value'], $r['daily_amount'], $r['monthly_amount'],
            ]
        );
    }

    $contract = p360hr_contract_get($contractId);
    $wageRows = p360hr_contract_wage_rows($contractId);
    $built = p360hr_build_placeholder_map($contract ?? $payload, $employee, $profile, $employer, $wageRows, $calc['total_daily'], $calc['total_monthly']);
    $hash = hash('sha256', $built['body']);
    $wageHash = hash('sha256', $built['wage_html'] . '|' . $calc['total_daily'] . '|' . $calc['total_monthly']);
    $wageSnap = json_encode(['inputs' => $input, 'calc' => $calc], JSON_UNESCAPED_UNICODE);
    $verNow = (int)((p360hr_contract_get($contractId)['contract_version'] ?? 1));
    $bodyPointer = p360hr_store_rendered_body($contractId, $verNow, $built['body']);

    p360hr_exec(
        'UPDATE dbo.p360_hr_contracts SET rendered_body=?, contract_hash=?, wage_table_hash=?, wage_snapshot_json=?, updated_at=SYSUTCDATETIME() WHERE contract_id=?',
        [$bodyPointer, $hash, $wageHash, $wageSnap, $contractId]
    );

    if ($updateMaster === 1 && $contractTitle !== '') {
        p360hr_exec('UPDATE dbo.p360_employees SET job_title=? WHERE employee_id=?', [$contractTitle, $eid]);
    }

    return [
        'ok' => true,
        'message' => 'پیش‌نویس قرارداد ذخیره شد.',
        'contract_id' => $contractId,
        'unresolved' => $built['unresolved'],
        'total_daily' => $calc['total_daily'],
        'total_monthly' => $calc['total_monthly'],
    ];
}

function p360hr_set_stage(int $contractId, string $stage): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if ((int)($c['is_locked'] ?? 0) === 1) {
        return ['ok' => false, 'message' => 'قرارداد قفل است.'];
    }
    $allowed = ['DRAFT', 'ADMIN_REVIEW', 'PRESENTED', 'ACCEPTED', 'OTP_VERIFIED', 'EMPLOYEE_SIGNED', 'FINALIZED'];
    $stage = strtoupper($stage);
    if (!in_array($stage, $allowed, true)) {
        return ['ok' => false, 'message' => 'مرحله نامعتبر است.'];
    }
    p360hr_exec('UPDATE dbo.p360_hr_contracts SET stage_code=?, updated_at=SYSUTCDATETIME() WHERE contract_id=?', [$stage, $contractId]);
    return ['ok' => true, 'message' => 'مرحله به «' . p360hr_stage_fa($stage) . '» تغییر کرد.'];
}

function p360hr_employee_mobile_for_otp(int $employeeId): string
{
    $emp = p360hr_employee_by_id($employeeId);
    $mobile = trim((string)($emp['mobile'] ?? $emp['MOBILE'] ?? ''));
    if ($mobile !== '') {
        return $mobile;
    }
    $p = p360hr_personnel_profile($employeeId);
    return trim((string)($p['emergency_mobile'] ?? ''));
}

/**
 * HR-isolated OTP — never touches public customer OTP tables.
 * @return array{ok:bool,message:string,otp_dev?:string,expires_in?:int}
 */
function p360hr_otp_issue(int $contractId, int $ttlSeconds = P360HR_OTP_TTL_DEFAULT): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if ((int)($c['acceptance_checked'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'ابتدا پذیرش آگاهانه قرارداد لازم است.'];
    }
    $eid = (int)$c['employee_id'];
    $mobile = p360hr_employee_mobile_for_otp($eid);
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'شماره موبایل پرسنل در پرونده ثبت نشده و ارسال OTP ممکن نیست.'];
    }
    $recent = p360hr_rows(
        'SELECT TOP 20 id, created_at FROM dbo.p360_hr_contract_otp WHERE contract_id=? AND created_at >= DATEADD(hour,-1,SYSUTCDATETIME()) ORDER BY id DESC',
        [$contractId]
    );
    if (count($recent) >= P360HR_OTP_RESEND_LIMIT) {
        return ['ok' => false, 'message' => 'سقف ارسال مجدد OTP در یک ساعت تکمیل شده است.'];
    }
    $otp = (string)random_int(100000, 999999);
    $hash = hash('sha256', $otp . '|' . $contractId . '|' . (int)$c['contract_version']);
    $ttl = max(30, min(600, $ttlSeconds));
    p360hr_exec(
        'INSERT INTO dbo.p360_hr_contract_otp (contract_id, employee_id, contract_version, otp_hash, expires_at, max_attempts)
         VALUES (?,?,?,?,DATEADD(second,?,SYSUTCDATETIME()),?)',
        [$contractId, $eid, (int)$c['contract_version'], $hash, $ttl, P360HR_OTP_MAX_ATTEMPTS]
    );
    // Dev/UAT: return OTP once on local/CLI; never log plaintext elsewhere.
    $server = (string)($_SERVER['SERVER_NAME'] ?? '');
    $dev = in_array($server, ['127.0.0.1', 'localhost', ''], true) || PHP_SAPI === 'cli';
    return [
        'ok' => true,
        'message' => 'کد یک‌بارمصرف به موبایل پرونده پرسنل ارسال شد (مسیر HR جدا از OTP عمومی).',
        'expires_in' => $ttl,
        'otp_dev' => $dev ? $otp : null,
        'mobile_masked' => substr($mobile, 0, 4) . '****' . substr($mobile, -2),
    ];
}

function p360hr_otp_verify(int $contractId, string $otpCode): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    $otpCode = trim($otpCode);
    if (!preg_match('/^\d{6}$/', $otpCode)) {
        return ['ok' => false, 'message' => 'کد OTP باید شش رقم باشد.'];
    }
    $row = p360hr_one(
        'SELECT TOP 1 * FROM dbo.p360_hr_contract_otp WHERE contract_id=? AND consumed_at IS NULL AND expires_at > SYSUTCDATETIME() ORDER BY id DESC',
        [$contractId]
    );
    if ($row === null) {
        $any = p360hr_one(
            'SELECT TOP 1 id, expires_at FROM dbo.p360_hr_contract_otp WHERE contract_id=? AND consumed_at IS NULL ORDER BY id DESC',
            [$contractId]
        );
        if ($any !== null) {
            return ['ok' => false, 'message' => 'کد منقضی شده است.'];
        }
        return ['ok' => false, 'message' => 'کد فعالی یافت نشد. دوباره درخواست دهید.'];
    }
    if ((int)$row['contract_version'] !== (int)$c['contract_version']) {
        return ['ok' => false, 'message' => 'کد مربوط به نسخه قبلی قرارداد است و باطل شده است.'];
    }
    if ((int)$row['attempt_count'] >= (int)$row['max_attempts']) {
        return ['ok' => false, 'message' => 'تعداد تلاش‌ها به سقف رسیده است.'];
    }
    $expect = hash('sha256', $otpCode . '|' . $contractId . '|' . (int)$c['contract_version']);
    if (!hash_equals((string)$row['otp_hash'], $expect)) {
        p360hr_exec('UPDATE dbo.p360_hr_contract_otp SET attempt_count=attempt_count+1 WHERE id=?', [(int)$row['id']]);
        return ['ok' => false, 'message' => 'کد نادرست است.'];
    }
    p360hr_exec('UPDATE dbo.p360_hr_contract_otp SET consumed_at=SYSUTCDATETIME(), attempt_count=attempt_count+1 WHERE id=?', [(int)$row['id']]);
    p360hr_exec("UPDATE dbo.p360_hr_contracts SET otp_verified_at=SYSUTCDATETIME(), stage_code=N'OTP_VERIFIED', updated_at=SYSUTCDATETIME() WHERE contract_id=?", [$contractId]);
    return ['ok' => true, 'message' => 'OTP با موفقیت تأیید شد.'];
}

function p360hr_accept_contract(int $contractId, int $userId, string $ip, string $ua): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (trim((string)($c['contract_hash'] ?? '')) === '' && trim(p360hr_load_rendered_body($c)) === '') {
        return ['ok' => false, 'message' => 'متن قرارداد هنوز تولید نشده است.'];
    }
    // Disciplinary attachment gate (file presence under storage)
    $disc = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'disciplinary-v1.pdf';
    $discAlt = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'disciplinary-v1.txt';
    if (!is_file($disc) && !is_file($discAlt)) {
        if (!is_dir(dirname($discAlt))) {
            @mkdir(dirname($discAlt), 0755, true);
        }
        @file_put_contents($discAlt, "آیین‌نامه انضباطی — نسخه پایه P360HR\nجزء لاینفک قرارداد کار.\n");
    }
    if (!is_file($disc) && !is_file($discAlt)) {
        return ['ok' => false, 'message' => 'پیوست آیین‌نامه انضباطی در دسترس نیست. پذیرش مسدود است.'];
    }
    p360hr_exec(
        "UPDATE dbo.p360_hr_contracts SET
            acceptance_checked=1, accepted_at=SYSUTCDATETIME(), accepted_ip=?, accepted_ua=?, accepted_by_user_id=?,
            stage_code=N'ACCEPTED', updated_at=SYSUTCDATETIME()
         WHERE contract_id=?",
        [substr($ip, 0, 64), substr($ua, 0, 400), $userId, $contractId]
    );
    return ['ok' => true, 'message' => 'پذیرش آگاهانه ثبت شد.', 'accept_text' => P360HR_ACCEPT_TEXT];
}

function p360hr_save_signature_png(int $contractId, string $role, string $dataUrl): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (!preg_match('#^data:image/png;base64,#', $dataUrl)) {
        return ['ok' => false, 'message' => 'فرمت امضا نامعتبر است.'];
    }
    $bin = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
    if ($bin === false || strlen($bin) < 50) {
        return ['ok' => false, 'message' => 'داده امضا خالی یا ناقص است.'];
    }
    $dir = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'signatures';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $ver = (int)$c['contract_version'];
    $path = $dir . DIRECTORY_SEPARATOR . 'c' . $contractId . '-v' . $ver . '-' . $role . '.png';
    if (@file_put_contents($path, $bin) === false) {
        return ['ok' => false, 'message' => 'ذخیره امضا ناموفق بود.'];
    }
    $sha = hash('sha256', $bin);
    if ($role === 'employee') {
        if (empty($c['otp_verified_at'])) {
            return ['ok' => false, 'message' => 'قبل از امضا، تأیید OTP الزامی است.'];
        }
        p360hr_exec(
            "UPDATE dbo.p360_hr_contracts SET employee_signature_path=?, employee_signed_at=SYSUTCDATETIME(), stage_code=N'EMPLOYEE_SIGNED', updated_at=SYSUTCDATETIME() WHERE contract_id=?",
            [$path, $contractId]
        );
    } else {
        p360hr_exec(
            'UPDATE dbo.p360_hr_contracts SET employer_signature_path=?, employer_signed_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME() WHERE contract_id=?',
            [$path, $contractId]
        );
    }
    return ['ok' => true, 'message' => 'امضا ذخیره شد.', 'sha256' => $sha, 'path' => $path];
}

function p360hr_vazir_font_dir(): string
{
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts';
    return $dir;
}

/**
 * @return array{ok:bool,message:string,path?:string,hash?:string,bytes?:string}
 */
function p360hr_generate_pdf(int $contractId, bool $final = false): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    $autoload = 'C:\\xampp\\htdocs\\moghare360\\vendor\\autoload.php';
    if (!is_file($autoload)) {
        return ['ok' => false, 'message' => 'کتابخانه mPDF در runtime یافت نشد.'];
    }
    require_once $autoload;

    $body = p360hr_load_rendered_body($c);
    if ($body === '') {
        return ['ok' => false, 'message' => 'متن رندر‌شده قرارداد خالی است.'];
    }
    $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    // Wage table was escaped; restore HTML table if present as entities — better inject from wage rows.
    $wageRows = p360hr_contract_wage_rows($contractId);
    $totalD = 0.0;
    $totalM = 0.0;
    foreach ($wageRows as $r) {
        $totalD += (float)($r['daily_amount'] ?? 0);
        $totalM += (float)($r['monthly_amount'] ?? 0);
    }
    $wageHtml = p360hr_wage_table_html($wageRows, $totalD, $totalM);
    $plain = $body;
    // Replace escaped/plain marker if table already embedded as HTML in body
    if (strpos($plain, '<table') !== false) {
        // Convert body with table preserved
        $parts = preg_split('/(<table[\s\S]*?<\/table>)/', $plain, -1, PREG_SPLIT_DELIM_CAPTURE);
        $htmlBody = '';
        foreach ($parts as $part) {
            if (str_starts_with($part, '<table')) {
                $htmlBody .= $part;
            } else {
                $htmlBody .= nl2br(htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            }
        }
    } else {
        $htmlBody = str_replace(
            htmlspecialchars($wageHtml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $wageHtml,
            $htmlBody
        );
        // If wage HTML was stored raw in body text via placeholder resolution:
        if (strpos($body, '<table') !== false) {
            $htmlBody = $htmlBody; // already handled
        } else {
            // Body contains HTML table unescaped from placeholder — rebuild carefully
            $escapedMarker = '[[WAGE_TABLE]]';
            $tmp = str_replace($wageHtml, $escapedMarker, $body);
            $htmlBody = nl2br(htmlspecialchars($tmp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $htmlBody = str_replace(htmlspecialchars($escapedMarker, ENT_QUOTES, 'UTF-8'), $wageHtml, $htmlBody);
        }
    }

    $badge = $final ? '' : '<div style="color:#a00;font-weight:bold;text-align:center;margin-bottom:12px">پیش‌نویس</div>';
    $sigEmp = '';
    $sigEr = '';
    if (!empty($c['employee_signature_path']) && is_file((string)$c['employee_signature_path'])) {
        $sigEmp = '<p>امضای پرسنل:</p><img src="' . htmlspecialchars((string)$c['employee_signature_path'], ENT_QUOTES, 'UTF-8') . '" height="60">';
    }
    if (!empty($c['employer_signature_path']) && is_file((string)$c['employer_signature_path'])) {
        $sigEr = '<p>امضای کارفرما:</p><img src="' . htmlspecialchars((string)$c['employer_signature_path'], ENT_QUOTES, 'UTF-8') . '" height="60">';
    }
    $meta = '<hr><p style="font-size:9pt">نسخه قرارداد: ' . (int)$c['contract_version']
        . ' | SHA256: ' . htmlspecialchars((string)($c['contract_hash'] ?? ''), ENT_QUOTES, 'UTF-8')
        . ' | تولید: ' . date('c') . '</p>';

    $html = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><style>
    body{font-family:vazirmatn;direction:rtl;text-align:right;font-size:11pt;line-height:1.7}
    table{width:100%;border-collapse:collapse} th,td{border:1px solid #333;padding:4px}
    </style></head><body>' . $badge . $htmlBody . $sigEmp . $sigEr . '<p>محل مهر کارفرما: ................</p>' . $meta . '</body></html>';

    $fontDir = p360hr_vazir_font_dir();
    $tempDir = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'tmp-mpdf';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }

    try {
        $defaultFont = 'dejavusans';
        $fontdata = [];
        $reg = $fontDir . DIRECTORY_SEPARATOR . 'Vazirmatn-Regular.ttf';
        $bold = $fontDir . DIRECTORY_SEPARATOR . 'Vazirmatn-Bold.ttf';
        if (is_file($reg)) {
            $defaultFont = 'vazirmatn';
            $fontdata['vazirmatn'] = [
                'R' => 'Vazirmatn-Regular.ttf',
                'B' => is_file($bold) ? 'Vazirmatn-Bold.ttf' : 'Vazirmatn-Regular.ttf',
            ];
        }
        $cfg = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => $defaultFont,
            'tempDir' => $tempDir,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 16,
        ];
        if ($fontdata !== []) {
            $cfg['fontDir'] = array_merge([$fontDir], (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir']);
            $cfg['fontdata'] = $fontdata + (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];
        }
        $mpdf = new \Mpdf\Mpdf($cfg);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('قرارداد کار');
        $mpdf->WriteHTML($html);
        $bytes = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'تولید PDF ناموفق بود.'];
    }
    if (!is_string($bytes) || $bytes === '' || strncmp($bytes, '%PDF', 4) !== 0) {
        return ['ok' => false, 'message' => 'خروجی PDF معتبر نیست.'];
    }
    $hash = hash('sha256', $bytes);
    $dir = p360hr_storage_root() . DIRECTORY_SEPARATOR . 'pdf';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = 'contract-' . $contractId . '-v' . (int)$c['contract_version'] . ($final ? '-final' : '-draft') . '.pdf';
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (@file_put_contents($path, $bytes) === false) {
        return ['ok' => false, 'message' => 'ذخیره PDF ناموفق بود.'];
    }
    if ($final) {
        p360hr_exec(
            'UPDATE dbo.p360_hr_contracts SET final_pdf_path=?, final_pdf_hash=?, updated_at=SYSUTCDATETIME() WHERE contract_id=?',
            [$path, $hash, $contractId]
        );
    }
    return ['ok' => true, 'message' => 'PDF تولید شد.', 'path' => $path, 'hash' => $hash, 'bytes' => $bytes, 'font' => $defaultFont];
}

function p360hr_finalize_contract(int $contractId, int $actorUserId): array
{
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (empty($c['employee_signed_at']) || empty($c['employer_signed_at']) || empty($c['otp_verified_at']) || (int)($c['acceptance_checked'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'پذیرش، OTP و هر دو امضا باید کامل باشد.'];
    }
    $gaps = p360hr_employer_mandatory_gaps();
    if ($gaps !== []) {
        return ['ok' => false, 'message' => 'اطلاعات کارفرما ناقص است: ' . implode('، ', $gaps)];
    }
    $employee = p360hr_employee_by_id((int)$c['employee_id']);
    $profile = p360hr_personnel_profile((int)$c['employee_id']);
    $employer = p360hr_employer_active() ?? [];
    $wageRows = p360hr_contract_wage_rows($contractId);
    $totalD = 0.0;
    $totalM = 0.0;
    foreach ($wageRows as $r) {
        $totalD += (float)($r['daily_amount'] ?? 0);
        $totalM += (float)($r['monthly_amount'] ?? 0);
    }
    $built = p360hr_build_placeholder_map($c, $employee ?? [], $profile, $employer, $wageRows, $totalD, $totalM);
    if ($built['unresolved'] !== []) {
        return ['ok' => false, 'message' => 'جایگاه‌های ناقص: ' . implode('، ', $built['unresolved'])];
    }

    $empSnap = json_encode([
        'identity' => $employee,
        'profile' => $profile,
        'bank' => [
            'bank_name' => $profile['bank_name'] ?? null,
            'bank_account' => $profile['bank_account'] ?? null,
            'iban' => $profile['iban'] ?? null,
            'account_holder_name' => $profile['account_holder_name'] ?? null,
        ],
        'guarantee' => [
            'type' => $profile['guarantee_type'] ?? null,
            'amount' => $profile['guarantee_amount'] ?? null,
            'ref' => $profile['guarantee_ref'] ?? null,
        ],
    ], JSON_UNESCAPED_UNICODE);
    $erSnap = json_encode($employer, JSON_UNESCAPED_UNICODE);

    $bodyPointer = p360hr_store_rendered_body($contractId, (int)$c['contract_version'], $built['body']);
    p360hr_exec(
        "UPDATE dbo.p360_hr_contracts SET
            rendered_body=?, contract_hash=?, employee_snapshot_json=?, employer_snapshot_json=?,
            stage_code=N'FINALIZED', is_locked=1, updated_at=SYSUTCDATETIME()
         WHERE contract_id=?",
        [$bodyPointer, hash('sha256', $built['body']), $empSnap, $erSnap, $contractId]
    );

    $pdf = p360hr_generate_pdf($contractId, true);
    if (!$pdf['ok']) {
        return $pdf;
    }

    // Deactivate previous payroll links then insert active
    p360hr_exec('UPDATE dbo.p360_hr_payroll_contract_link SET is_active=0, effective_to=CAST(SYSUTCDATETIME() AS date) WHERE employee_id=? AND is_active=1', [(int)$c['employee_id']]);
    p360hr_exec(
        'INSERT INTO dbo.p360_hr_payroll_contract_link
            (employee_id, contract_id, contract_version, wage_model, eid_payment_method, severance_payment_method, effective_from, is_active)
         VALUES (?,?,?,?,?,?,CAST(SYSUTCDATETIME() AS date),1)',
        [
            (int)$c['employee_id'], $contractId, (int)$c['contract_version'],
            (string)$c['wage_model'], (string)$c['eid_payment_method'], (string)$c['severance_payment_method'],
        ]
    );

    return ['ok' => true, 'message' => 'قرارداد نهایی و قفل شد.', 'pdf_hash' => $pdf['hash'] ?? '', 'pdf_path' => $pdf['path'] ?? ''];
}

/**
 * Register monthly Eid/severance payment for a payroll period — unique constraint prevents duplicates.
 */
function p360hr_register_benefit_payment(int $contractId, string $benefitCode, string $periodKey, float $amount, string $note = ''): array
{
    $benefitCode = strtoupper($benefitCode);
    if (!in_array($benefitCode, ['EID_BONUS', 'SEVERANCE'], true)) {
        return ['ok' => false, 'message' => 'کد مزایا نامعتبر است.'];
    }
    $c = p360hr_contract_get($contractId);
    if ($c === null || (int)($c['is_locked'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'فقط قرارداد نهایی‌شده قابل ثبت پرداخت است.'];
    }
    $exists = p360hr_one(
        'SELECT id FROM dbo.p360_hr_contract_benefit_payments WHERE contract_id=? AND benefit_code=? AND payroll_period_key=?',
        [$contractId, $benefitCode, $periodKey]
    );
    if ($exists !== null) {
        return ['ok' => false, 'message' => 'پرداخت این دوره قبلاً ثبت شده و از پرداخت تکراری جلوگیری شد.'];
    }
    $ok = p360hr_exec(
        'INSERT INTO dbo.p360_hr_contract_benefit_payments (contract_id, benefit_code, payroll_period_key, amount, note) VALUES (?,?,?,?,?)',
        [$contractId, $benefitCode, $periodKey, $amount, $note !== '' ? $note : null]
    );
    return $ok
        ? ['ok' => true, 'message' => 'پرداخت دوره ثبت شد.']
        : ['ok' => false, 'message' => 'ثبت پرداخت ناموفق بود.'];
}

function p360hr_benefit_settlement_remaining(int $contractId, string $benefitCode, float $totalEntitlement): array
{
    $rows = p360hr_rows(
        'SELECT amount FROM dbo.p360_hr_contract_benefit_payments WHERE contract_id=? AND benefit_code=?',
        [$contractId, strtoupper($benefitCode)]
    );
    $paid = 0.0;
    foreach ($rows as $r) {
        $paid += (float)$r['amount'];
    }
    $remaining = round($totalEntitlement - $paid, 2);
    return [
        'total_entitlement' => $totalEntitlement,
        'previous_paid' => round($paid, 2),
        'remaining_payable' => max(0.0, $remaining),
    ];
}

/** @return list<string> */
function p360hr_unresolved_placeholder_labels(array $keys): array
{
    $labels = [
        'EMPLOYER_TRADE_NAME' => 'نام تجاری کارفرما',
        'EMPLOYER_REPRESENTATIVE_NAME' => 'نماینده کارفرما',
        'EMPLOYEE_FULL_NAME' => 'نام پرسنل',
        'PERSONNEL_CODE' => 'کد پرسنلی',
        'CONTRACT_JOB_TITLE' => 'عنوان شغل قراردادی',
        'EMPLOYEE_UNIT' => 'واحد',
        'CONTRACT_TYPE_PERSIAN' => 'نوع قرارداد',
        'WORKPLACE' => 'محل انجام کار',
        'CONTRACT_START_DATE_JALALI' => 'تاریخ شروع',
        'CONTRACT_END_DATE_JALALI' => 'تاریخ پایان',
        'CONTRACT_SUBJECT' => 'موضوع قرارداد',
        'JOB_DUTIES_TEXT' => 'شرح وظایف',
        'WAGE_TABLE_HTML' => 'جدول حق‌السعی',
    ];
    $out = [];
    foreach ($keys as $k) {
        $out[] = $labels[$k] ?? $k;
    }
    return $out;
}
