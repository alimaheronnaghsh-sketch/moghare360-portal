<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-delivery-readiness-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'reception-ui-helper.php';

const M360_FULLJOB_REQUEST_TYPES = [
    'TECHNICAL_ADDITIONAL_WORK',
    'PARTS_MATERIALS_REQUISITION',
    'EXTERNAL_SERVICE_REQUEST',
    'CUSTOMER_CLARIFICATION_REQUEST',
    'WORK_HOLD_SAFETY_STOP',
];

const M360_FULLJOB_PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT', 'SAFETY_CRITICAL'];
const M360_FULLJOB_RISKS = ['NO_RISK', 'QUALITY_RISK', 'TIME_RISK', 'COST_RISK', 'SAFETY_RISK', 'LEGAL_RISK'];

/** Canonical specialist unit codes — source of truth is erp_jobcard_assignments.team_code */
const M360_FULLJOB_TEAM_CODES = ['MECHANICAL', 'ELECTRICAL', 'OPTIONS'];

function m360_fulljob_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return list<string> */
function m360_fulljob_team_codes(): array
{
    return M360_FULLJOB_TEAM_CODES;
}

function m360_fulljob_is_valid_team_code(string $teamCode): bool
{
    return in_array(strtoupper(trim($teamCode)), M360_FULLJOB_TEAM_CODES, true);
}

function m360_fulljob_team_label_fa(string $teamCode): string
{
    $code = strtoupper(trim($teamCode));
    if ($code === '') {
        return '';
    }
    $map = [
        'MECHANICAL' => 'واحد مکانیک',
        'ELECTRICAL' => 'واحد برق',
        'OPTIONS' => 'واحد آپشن',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('team_code', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

/** Explicit test/UAT source_channel markers on erp_customer_online_requests (not name-based). */
const M360_FULLJOB_TEST_SOURCE_CHANNELS = ['CANONICAL_TEST', 'SMOKE_TEST'];

function m360_fulljob_log_unknown_display_code(string $kind, string $code): void
{
    if ($code === '') {
        return;
    }
    @error_log('m360_fulljob unknown display code kind=' . $kind . ' code=' . $code);
}

function m360_fulljob_assignment_type_label_fa(string $type): string
{
    $code = strtoupper(trim($type));
    $map = [
        'TEAM_ASSIGNMENT' => 'تخصیص واحد',
        'TECHNICIAN_ASSIGNMENT' => 'تخصیص تکنسین',
        'HALL_INTAKE' => 'تحویل به مدیر سالن',
    ];
    if ($code === '') {
        return '';
    }
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('assignment_type', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

/**
 * Reception / intake channel → Persian (display only).
 */
function m360_fulljob_reception_type_label_fa(string $sourceChannel): string
{
    $code = strtoupper(trim($sourceChannel));
    if ($code === '') {
        return 'نامشخص';
    }
    $map = [
        'WALKIN' => 'حضوری',
        'STAFF_ASSISTED_WALKIN' => 'حضوری',
        'ONLINE' => 'آنلاین',
        'ONLINE_PORTAL' => 'آنلاین',
        'PUBLIC_SITE' => 'آنلاین',
        'PUBLIC_WEB' => 'آنلاین',
        'CANONICAL_TEST' => 'آزمایشی',
        'SMOKE_TEST' => 'آزمایشی',
        'MIRROR' => 'آینه',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('source_channel', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

function m360_fulljob_is_test_source_channel(string $sourceChannel): bool
{
    return in_array(strtoupper(trim($sourceChannel)), M360_FULLJOB_TEST_SOURCE_CHANNELS, true);
}

function m360_fulljob_user_display_name($conn, int $userId): string
{
    if (!is_resource($conn) || $userId < 1) {
        return 'تکنسین ثبت‌شده';
    }
    $name = trim((string)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 full_name FROM dbo.core_users WHERE user_id = ?',
        [$userId]
    ) ?? ''));
    if ($name !== '') {
        return $name;
    }

    return 'تکنسین ثبت‌شده';
}

function m360_fulljob_tx_begin($conn): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    if (!@odbc_autocommit($conn, false)) {
        return false;
    }
    @odbc_exec($conn, 'SET XACT_ABORT ON');

    return true;
}

function m360_fulljob_tx_commit($conn): void
{
    if (!is_resource($conn)) {
        return;
    }
    @odbc_commit($conn);
    @odbc_autocommit($conn, true);
}

function m360_fulljob_tx_rollback($conn): void
{
    if (!is_resource($conn)) {
        return;
    }
    @odbc_rollback($conn);
    @odbc_autocommit($conn, true);
}

const M360_FULLJOB_HALL_FLASH_KEY = 'm360_fulljob_hall_flash';

/** @param array{ok?:bool,message?:string,type?:string,idempotent?:bool} $flash */
function m360_fulljob_set_hall_flash(array $flash): void
{
    erp_auth_context_start();
    $_SESSION[M360_FULLJOB_HALL_FLASH_KEY] = $flash;
}

/** @return array{ok?:bool,message?:string,type?:string,idempotent?:bool} */
function m360_fulljob_consume_hall_flash(): array
{
    erp_auth_context_start();
    $flash = $_SESSION[M360_FULLJOB_HALL_FLASH_KEY] ?? [];
    unset($_SESSION[M360_FULLJOB_HALL_FLASH_KEY]);

    return is_array($flash) ? $flash : [];
}

/**
 * Legacy assigned_team_id mapping (no FK / no team catalog).
 * OPTIONS has no catalog ID — returns null (nullable column).
 */
function m360_fulljob_legacy_assigned_team_id(string $teamCode): ?int
{
    return match (strtoupper(trim($teamCode))) {
        'MECHANICAL' => 10,
        'ELECTRICAL' => 20,
        'OPTIONS' => null,
        default => null,
    };
}

function m360_fulljob_request_type_label_fa(string $type): string
{
    $code = strtoupper(trim($type));
    if ($code === '') {
        return '';
    }
    $map = [
        'TECHNICAL_ADDITIONAL_WORK' => 'درخواست کار فنی اضافه',
        'PARTS_MATERIALS_REQUISITION' => 'درخواست قطعه / مواد',
        'EXTERNAL_SERVICE_REQUEST' => 'درخواست خدمت خارج از مجموعه',
        'CUSTOMER_CLARIFICATION_REQUEST' => 'درخواست شفاف‌سازی از مشتری',
        'WORK_HOLD_SAFETY_STOP' => 'توقف کار / توقف ایمنی',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('request_type', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

function m360_fulljob_status_label_fa(string $status): string
{
    $code = strtoupper(trim($status));
    if ($code === '') {
        return '';
    }
    $map = [
        'UNDER_HALL_REVIEW' => 'در بررسی مدیر سالن',
        'HALL_REVIEW' => 'در بررسی مدیر سالن',
        'NEEDS_MORE_EVIDENCE' => 'نیازمند شواهد بیشتر',
        'SENT_TO_INVENTORY' => 'ارسال‌شده به انبار',
        'SENT_TO_PURCHASE' => 'ارسال‌شده به خرید',
        'SENT_TO_CRM' => 'ارسال‌شده به CRM',
        'SENT_TO_CUSTOMER' => 'در انتظار پاسخ مشتری',
        'EXECUTION_BLOCKED' => 'اجرای کار مسدود است',
        'APPROVED' => 'تأیید شده',
        'REJECTED' => 'رد شده',
        'CLOSED' => 'بسته‌شده',
        'OPEN' => 'باز',
        'ACTIVE' => 'فعال',
        'ASSIGNED' => 'تخصیص داده‌شده',
        'TEAM_ASSIGNED' => 'تخصیص‌شده به واحد',
        'TECHNICIAN_ASSIGNED' => 'تخصیص‌شده به تکنسین',
        'IN_PROGRESS' => 'در حال انجام',
        'WORK_STARTED' => 'در حال انجام',
        'SERVICE_IN_PROGRESS' => 'در حال انجام',
        'PAUSED' => 'متوقف‌شده',
        'ON_HOLD' => 'متوقف‌شده',
        'WAITING_FOR_PARTS' => 'در انتظار قطعه',
        'WAITING_FOR_CUSTOMER' => 'در انتظار تأیید مشتری',
        'WAITING_FOR_APPROVAL' => 'در انتظار تأیید مشتری',
        'READY_FOR_QC' => 'آماده کنترل کیفیت',
        'QC_FAILED' => 'برگشت از کنترل کیفیت',
        'REWORK_REQUIRED' => 'برگشت از کنترل کیفیت',
        'TECHNICAL_COMPLETED' => 'تکمیل‌شده',
        'TECHNICAL_DONE' => 'تکمیل‌شده',
        'TECHNICAL_COMPLETION_REVIEW' => 'تکمیل‌شده و در بررسی مدیر سالن',
        'SERVICE_COMPLETED' => 'تکمیل‌شده',
        'COMPLETED' => 'تکمیل‌شده',
        'CANCELLED' => 'لغوشده',
        'REASSIGNED' => 'تخصیص مجدد',
        'USED' => 'مصرف‌شده',
        'CREATED' => 'ایجادشده',
        'STARTED' => 'شروع‌شده',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('status', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

function m360_fulljob_priority_label_fa(string $priority): string
{
    $code = strtoupper(trim($priority));
    if ($code === '') {
        return '';
    }
    $map = [
        'LOW' => 'کم',
        'NORMAL' => 'عادی',
        'HIGH' => 'بالا',
        'URGENT' => 'فوری',
        'SAFETY_CRITICAL' => 'بحرانی / ایمنی',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('priority', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

function m360_fulljob_risk_label_fa(string $risk): string
{
    $code = strtoupper(trim($risk));
    if ($code === '') {
        return '';
    }
    $map = [
        'NO_RISK' => 'بدون ریسک',
        'QUALITY_RISK' => 'ریسک کیفیت',
        'TIME_RISK' => 'ریسک زمان',
        'COST_RISK' => 'ریسک هزینه',
        'SAFETY_RISK' => 'ریسک ایمنی',
        'LEGAL_RISK' => 'ریسک حقوقی',
    ];
    if (!isset($map[$code])) {
        m360_fulljob_log_unknown_display_code('risk', $code);

        return 'نامشخص';
    }

    return $map[$code];
}

function m360_fulljob_current_actor($conn): array
{
    erp_auth_context_start();
    $userId = (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
    $companyId = (int)($_SESSION['erp_company_id'] ?? 1);
    $roleCode = $userId > 0 ? m360_staff_home_resolve_role_code($conn, $userId, $companyId) : 'UNKNOWN';

    return [
        'user_id' => $userId,
        'company_id' => $companyId,
        'role_code' => $roleCode,
        'is_staff' => $userId > 0,
    ];
}

function m360_fulljob_require_role($conn, array $allowedRoles): array
{
    $actor = m360_fulljob_current_actor($conn);
    if (empty($actor['is_staff'])) {
        header('Location: staff-login.php');
        exit;
    }
    if (!in_array((string)$actor['role_code'], $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>دسترسی مجاز نیست</title><link rel="stylesheet" href="assets/css/m360-staff-home.css"></head>';
        echo '<body class="m360-staff-page"><main class="m360-staff-wrap"><section class="m360-staff-hero">';
        echo '<h1>دسترسی مجاز نیست.</h1><p>این مسیر فقط برای نقش‌های مجاز تعریف شده است.</p>';
        echo '<p><a class="m360-staff-btn" href="erp-staff-home.php">بازگشت به میز کار</a></p>';
        echo '</section></main></body></html>';
        exit;
    }

    return $actor;
}

function m360_fulljob_role_can_hall(string $roleCode): bool
{
    return in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER'], true);
}

function m360_fulljob_role_can_technician(string $roleCode): bool
{
    return in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN'], true);
}

function m360_fulljob_fetch_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
                v.plate_number, v.brand, v.model
         FROM dbo.erp_jobcards j
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE j.jobcard_id = ?',
        [$jobcardId]
    );

    return $rows[0] ?? null;
}

function m360_fulljob_find_jobcard_by_request($conn, int $requestId): ?array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE online_request_id = ? ORDER BY jobcard_id DESC',
        [$requestId]
    );

    return $rows[0] ?? null;
}

/**
 * Display wrappers — reuse canonical reception-ui Jalali helpers.
 */
function m360_fulljob_display_jalali_date(?string $raw): string
{
    return m360_rui_jalali_date($raw, false);
}

function m360_fulljob_display_jalali_datetime(?string $raw, bool $withSeconds = false): string
{
    return m360_rui_jalali_datetime($raw, true, $withSeconds);
}

function m360_fulljob_to_persian_digits(string $value): string
{
    return m360_rui_to_persian_digits($value);
}

/**
 * Prefer request.created_at (real clock). reception_at is often date-only midnight — avoid fake 00:00.
 */
function m360_fulljob_resolve_reception_created_at(?string $requestCreatedAt, ?string $jobcardCreatedAt, ?string $receptionAt): ?string
{
    $req = trim((string)$requestCreatedAt);
    if ($req !== '') {
        return $req;
    }
    $jc = trim((string)$jobcardCreatedAt);
    if ($jc !== '') {
        return $jc;
    }
    $rec = trim((string)$receptionAt);
    if ($rec === '') {
        return null;
    }

    return $rec;
}

/**
 * Hall intake queue. Test channels (CANONICAL_TEST / SMOKE_TEST) excluded by default.
 * Includes reception + Hall-referral timestamps in one join (no N+1).
 *
 * @return list<array<string, mixed>>
 */
function m360_fulljob_hall_cartable($conn, bool $includeTestRecords = false): array
{
    $sql = "SELECT a.*, j.jobcard_number, j.online_request_id, j.customer_id, j.vehicle_id,
                   j.reception_at, j.created_at AS jobcard_created_at, j.contract_signed_at,
                   a.created_at AS hall_referral_at,
                   c.full_name AS customer_name, v.plate_number, v.brand, v.model,
                   r.source_channel AS request_source_channel, r.source AS request_source,
                   r.created_at AS request_created_at
            FROM dbo.erp_jobcard_assignments a
            INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            LEFT JOIN dbo.erp_customer_online_requests r ON r.online_request_id = j.online_request_id
            WHERE a.assignment_type = N'HALL_INTAKE' AND a.status = N'ACTIVE'";
    if (!$includeTestRecords) {
        $sql .= " AND (r.source_channel IS NULL OR r.source_channel NOT IN (N'CANONICAL_TEST', N'SMOKE_TEST'))";
    }
    $sql .= ' ORDER BY a.assignment_id DESC';

    return customer_core_fetch_rows($conn, $sql);
}

function m360_fulljob_list_assignments($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT a.*,
                tu.full_name AS technician_full_name,
                au.full_name AS assistant_full_name
         FROM dbo.erp_jobcard_assignments a
         LEFT JOIN dbo.core_users tu ON tu.user_id = a.assigned_to_user_id
         LEFT JOIN dbo.core_users au ON au.user_id = a.assistant_user_id
         WHERE a.jobcard_id = ?
         ORDER BY a.assignment_id DESC',
        [$jobcardId]
    );
}

/**
 * Active TECHNICIAN_ASSIGNMENT rows for a JobCard.
 *
 * @return list<array<string, mixed>>
 */
function m360_fulljob_active_technician_assignments($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        "SELECT a.*, tu.full_name AS technician_full_name
         FROM dbo.erp_jobcard_assignments a
         LEFT JOIN dbo.core_users tu ON tu.user_id = a.assigned_to_user_id
         WHERE a.jobcard_id = ? AND a.assignment_type = N'TECHNICIAN_ASSIGNMENT' AND a.status = N'ACTIVE'
         ORDER BY a.assignment_id DESC",
        [$jobcardId]
    );
}

/**
 * Earliest history/event timestamp for a JobCard change/event name (parameterized).
 */
function m360_fulljob_first_history_at($conn, int $jobcardId, string $changeType): ?string
{
    if (!is_resource($conn) || $jobcardId < 1 || $changeType === '') {
        return null;
    }
    if (!customer_core_table_exists($conn, 'erp_jobcard_change_history')) {
        return null;
    }
    $v = customer_core_scalar(
        $conn,
        'SELECT TOP 1 changed_at FROM dbo.erp_jobcard_change_history
         WHERE jobcard_id = ? AND change_type = ? ORDER BY history_id ASC',
        [$jobcardId, $changeType]
    );

    return $v !== null && trim($v) !== '' ? $v : null;
}

function m360_fulljob_first_work_event_at($conn, int $jobcardId, string $eventName): ?string
{
    if (!is_resource($conn) || $jobcardId < 1 || $eventName === '') {
        return null;
    }
    if (!customer_core_table_exists($conn, 'erp_work_execution_events')) {
        return null;
    }
    $v = customer_core_scalar(
        $conn,
        'SELECT TOP 1 created_at FROM dbo.erp_work_execution_events
         WHERE jobcard_id = ? AND event_name = ? ORDER BY event_id ASC',
        [$jobcardId, $eventName]
    );

    return $v !== null && trim($v) !== '' ? $v : null;
}

/**
 * Build operational timeline from reliable existing evidence only (no fabrication).
 *
 * @return array{events:list<array<string,mixed>>,missing:list<array<string,string>>}
 */
function m360_fulljob_build_jobcard_timeline($conn, int $jobcardId): array
{
    $events = [];
    $missing = [];
    $push = static function (array &$events, string $title, ?string $at, string $source, string $unit = '', string $actor = '', string $status = '') : void {
        $at = $at !== null ? trim($at) : '';
        if ($at === '') {
            return;
        }
        $events[] = [
            'title' => $title,
            'at' => $at,
            'source' => $source,
            'unit' => $unit,
            'actor' => $actor,
            'status' => $status,
            'sort' => $at,
        ];
    };
    $miss = static function (array &$missing, string $title, string $reason) : void {
        $missing[] = ['title' => $title, 'reason' => $reason];
    };

    if (!is_resource($conn) || $jobcardId < 1) {
        return ['events' => [], 'missing' => [['title' => 'پرونده', 'reason' => 'منابع زمان مشخص نیست']]];
    }

    $jc = m360_fulljob_fetch_jobcard($conn, $jobcardId);
    if ($jc === null) {
        return ['events' => [], 'missing' => [['title' => 'پرونده', 'reason' => 'منابع زمان مشخص نیست']]];
    }

    $reqCreated = null;
    $onlineRequestId = (int)($jc['online_request_id'] ?? 0);
    if ($onlineRequestId > 0) {
        $reqCreated = customer_core_scalar(
            $conn,
            'SELECT TOP 1 created_at FROM dbo.erp_customer_online_requests WHERE online_request_id = ?',
            [$onlineRequestId]
        );
    }

    $receptionAt = m360_fulljob_resolve_reception_created_at(
        $reqCreated !== null ? (string)$reqCreated : null,
        (string)($jc['created_at'] ?? ''),
        (string)($jc['reception_at'] ?? '')
    );
    if ($receptionAt) {
        $push($events, 'ثبت پذیرش', $receptionAt, $reqCreated ? 'erp_customer_online_requests.created_at' : 'erp_jobcards.created_at');
    } else {
        $miss($missing, 'ثبت پذیرش', 'منابع زمان مشخص نیست');
    }

    $completed = trim((string)($jc['contract_signed_at'] ?? ''));
    if ($completed !== '') {
        $push($events, 'تکمیل پذیرش', $completed, 'erp_jobcards.contract_signed_at');
    } else {
        $miss($missing, 'تکمیل پذیرش', 'contract_signed_at خالی است');
    }

    $assignments = m360_fulljob_list_assignments($conn, $jobcardId);
    $hallReferral = null;
    foreach ($assignments as $a) {
        if (strtoupper(trim((string)($a['assignment_type'] ?? ''))) === 'HALL_INTAKE') {
            $cand = trim((string)($a['created_at'] ?? ''));
            if ($cand !== '' && ($hallReferral === null || $cand < $hallReferral)) {
                $hallReferral = $cand;
            }
        }
    }
    if ($hallReferral === null || $hallReferral === '') {
        $hallReferral = trim((string)($jc['ready_for_technical_at'] ?? ''));
        if ($hallReferral !== '') {
            $push($events, 'ارجاع به مدیر سالن', $hallReferral, 'erp_jobcards.ready_for_technical_at');
        } else {
            $miss($missing, 'ارجاع به مدیر سالن', 'HALL_INTAKE.created_at / ready_for_technical_at موجود نیست');
        }
    } else {
        $push($events, 'ارجاع به مدیر سالن', $hallReferral, 'erp_jobcard_assignments.created_at (HALL_INTAKE)');
    }

    // No dedicated Hall-acceptance timestamp in current schema.
    $miss($missing, 'پذیرش توسط مدیر سالن', 'ستون/رویداد اختصاصی در سوابق فعلی وجود ندارد');

    foreach ($assignments as $a) {
        $type = strtoupper(trim((string)($a['assignment_type'] ?? '')));
        $created = trim((string)($a['created_at'] ?? ''));
        $closed = trim((string)($a['closed_at'] ?? ''));
        $team = strtoupper(trim((string)($a['team_code'] ?? '')));
        $status = strtoupper(trim((string)($a['status'] ?? '')));

        if ($type === 'TEAM_ASSIGNMENT' && $created !== '') {
            $unitLabel = $team !== '' ? m360_fulljob_team_label_fa($team) : 'واحد';
            $push($events, 'ارجاع به ' . $unitLabel, $created, 'erp_jobcard_assignments.created_at (TEAM_ASSIGNMENT)', $unitLabel, '', m360_fulljob_status_label_fa($status));
            if ($closed !== '') {
                $push($events, 'اتمام کار ' . $unitLabel, $closed, 'erp_jobcard_assignments.closed_at (TEAM_ASSIGNMENT)', $unitLabel, '', m360_fulljob_status_label_fa($status));
                $push($events, 'بازگشت به مدیر سالن', $closed, 'erp_jobcard_assignments.closed_at (unit completion)', $unitLabel);
            }
        }
        if ($type === 'TECHNICIAN_ASSIGNMENT' && $created !== '') {
            $techName = trim((string)($a['technician_full_name'] ?? ''));
            if ($techName === '') {
                $techName = 'تکنسین ثبت‌شده';
            }
            $push($events, 'تخصیص تکنسین', $created, 'erp_jobcard_assignments.created_at (TECHNICIAN_ASSIGNMENT)', '', $techName, m360_fulljob_status_label_fa($status));
        }
    }

    if (customer_core_table_exists($conn, 'erp_external_service_requests')) {
        $extRows = customer_core_fetch_rows(
            $conn,
            'SELECT created_at, vendor_name, status FROM dbo.erp_external_service_requests WHERE jobcard_id = ? ORDER BY external_service_request_id ASC',
            [$jobcardId]
        );
        foreach ($extRows as $ext) {
            $at = trim((string)($ext['created_at'] ?? ''));
            if ($at === '') {
                continue;
            }
            $vendor = trim((string)($ext['vendor_name'] ?? ''));
            $push(
                $events,
                'ارجاع به خدمات بیرونی',
                $at,
                'erp_external_service_requests.created_at',
                $vendor !== '' ? $vendor : 'خدمات بیرونی',
                '',
                m360_fulljob_status_label_fa((string)($ext['status'] ?? ''))
            );
        }
    }

    $workStart = trim((string)($jc['work_started_at'] ?? ''));
    if ($workStart === '') {
        $workStart = (string)(m360_fulljob_first_work_event_at($conn, $jobcardId, 'JOBCARD_WORK_STARTED')
            ?? m360_fulljob_first_history_at($conn, $jobcardId, 'JOBCARD_WORK_STARTED')
            ?? '');
    }
    if ($workStart !== '') {
        $push($events, 'شروع کار واحد', $workStart, 'erp_jobcards.work_started_at / work event');
    } else {
        $miss($missing, 'شروع کار واحد', 'work_started_at / JOBCARD_WORK_STARTED موجود نیست');
    }

    $pauseAt = m360_fulljob_first_history_at($conn, $jobcardId, 'JOBCARD_WORK_EXECUTION_ON_HOLD');
    if ($pauseAt === null) {
        $pauseAt = m360_fulljob_first_work_event_at($conn, $jobcardId, 'JOBCARD_WORK_EXECUTION_ON_HOLD');
    }
    if ($pauseAt !== null) {
        $push($events, 'توقف کار', $pauseAt, 'erp_jobcard_change_history / work event (ON_HOLD)');
    } else {
        $miss($missing, 'توقف کار', 'رویداد توقف در سوابق فعلی ثبت نشده است');
    }

    // No dedicated resume timestamp/event in current model.
    $miss($missing, 'ادامه کار', 'رویداد/ستون اختصاصی ادامه کار وجود ندارد');

    $qcAt = trim((string)($jc['ready_for_qc_at'] ?? ''));
    if ($qcAt === '') {
        $qcAt = (string)(m360_fulljob_first_work_event_at($conn, $jobcardId, 'JOBCARD_READY_FOR_QC')
            ?? m360_fulljob_first_history_at($conn, $jobcardId, 'JOBCARD_READY_FOR_QC')
            ?? '');
    }
    if ($qcAt !== '') {
        $push($events, 'ارسال به کنترل کیفیت', $qcAt, 'erp_jobcards.ready_for_qc_at / READY_FOR_QC event');
    } else {
        $miss($missing, 'ارسال به کنترل کیفیت', 'ready_for_qc_at / JOBCARD_READY_FOR_QC موجود نیست');
    }

    usort($events, static function (array $a, array $b): int {
        return strcmp((string)$a['sort'], (string)$b['sort']);
    });

    return ['events' => $events, 'missing' => $missing];
}

/**
 * Map work-execution history/event codes to Persian (display only).
 */
function m360_fulljob_work_event_label_fa(string $code): string
{
    $c = strtoupper(trim($code));
    if ($c === '') {
        return '';
    }
    $map = [
        'JOBCARD_WORK_QUEUE' => 'انتقال به صف کار',
        'JOBCARD_WORK_STARTED' => 'شروع کار',
        'JOBCARD_WAITING_FOR_PARTS' => 'انتظار قطعه',
        'JOBCARD_PART_CONSUMED' => 'مصرف قطعه',
        'JOBCARD_TECHNICAL_COMPLETION_NOTES_SAVED' => 'ذخیره یادداشت تکمیل',
        'JOBCARD_TECHNICAL_WORK_COMPLETED' => 'اتمام کار واحد',
        'JOBCARD_READY_FOR_QC' => 'ارسال به کنترل کیفیت',
        'JOBCARD_WORK_EXECUTION_ON_HOLD' => 'توقف کار',
        'JOBCARD_WORK_EXECUTION_CANCELLED' => 'لغو اجرا',
        'JOBCARD_SERVICE_OPERATION_EXECUTION_STARTED' => 'شروع عملیات سرویس',
        'JOBCARD_SERVICE_OPERATION_EXECUTION_COMPLETED' => 'تکمیل عملیات سرویس',
        'SERVICE_OPERATION_EXECUTION_STARTED' => 'شروع عملیات سرویس',
        'SERVICE_OPERATION_EXECUTION_COMPLETED' => 'تکمیل عملیات سرویس',
        'JOBCARD_CREATE_V2' => 'ایجاد پرونده کار',
        'JOBCARD_APPROVED_FOR_WORK' => 'تأیید برای اجرا',
    ];
    if (!isset($map[$c])) {
        // Fall back to status map, then نامشخص — never leak raw code
        $asStatus = m360_fulljob_status_label_fa($c);
        if ($asStatus !== 'نامشخص' && $asStatus !== '') {
            return $asStatus;
        }
        m360_fulljob_log_unknown_display_code('work_event', $c);

        return 'نامشخص';
    }

    return $map[$c];
}

/**
 * Active TEAM_ASSIGNMENT rows for a JobCard (other units remain open).
 *
 * @return list<array<string, mixed>>
 */
function m360_fulljob_active_team_assignments($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        "SELECT * FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'TEAM_ASSIGNMENT' AND status = N'ACTIVE'
         ORDER BY assignment_id DESC",
        [$jobcardId]
    );
}

/**
 * Unit work board rows filtered by exact team_code (parameterized).
 *
 * @return list<array<string, mixed>>
 */
function m360_fulljob_unit_work_board($conn, string $teamCode): array
{
    $teamCode = strtoupper(trim($teamCode));
    if (!m360_fulljob_is_valid_team_code($teamCode)) {
        return [];
    }

    return customer_core_fetch_rows(
        $conn,
        "SELECT a.assignment_id, a.jobcard_id, a.team_code, a.assignment_description, a.priority,
                a.status AS assignment_status, a.created_at AS assigned_at, a.closed_at, a.updated_at,
                a.assigned_to_user_id, a.assigned_by_user_id,
                tu.full_name AS technician_full_name,
                j.jobcard_number, j.technical_status, j.work_execution_status, j.jobcard_status, j.qc_status,
                j.work_started_at, j.work_completed_at,
                c.full_name AS customer_name, v.plate_number, v.brand, v.model
         FROM dbo.erp_jobcard_assignments a
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         LEFT JOIN dbo.core_users tu ON tu.user_id = a.assigned_to_user_id
         WHERE a.assignment_type = N'TEAM_ASSIGNMENT'
           AND a.team_code = ?
         ORDER BY
           CASE WHEN a.status = N'ACTIVE' THEN 0 ELSE 1 END,
           a.assignment_id DESC",
        [$teamCode]
    );
}

/**
 * Display group for unit board queues (Persian label only — stored values unchanged).
 */
function m360_fulljob_unit_board_queue_group(array $row): string
{
    $assignStatus = strtoupper(trim((string)($row['assignment_status'] ?? '')));
    $tech = strtoupper(trim((string)($row['technical_status'] ?? '')));
    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));

    if (in_array($qc, ['QC_FAILED', 'REWORK_REQUIRED'], true)) {
        return 'برگشت از کنترل کیفیت';
    }
    if ($assignStatus === 'CLOSED' || in_array($tech, ['UNDER_HALL_REVIEW', 'TECHNICAL_DONE'], true)
        || $wx === 'TECHNICAL_COMPLETION_REVIEW' || $wx === 'TECHNICAL_COMPLETED') {
        if ($tech === 'UNDER_HALL_REVIEW' || $wx === 'TECHNICAL_COMPLETION_REVIEW'
            || (string)($row['jobcard_status'] ?? '') === 'HALL_REVIEW') {
            return 'تکمیل‌شده و ارسال‌شده برای بررسی مدیر سالن';
        }
        if ($assignStatus === 'CLOSED') {
            return 'تکمیل‌شده و ارسال‌شده برای بررسی مدیر سالن';
        }
    }
    if ($tech === 'UNDER_HALL_REVIEW' || (string)($row['jobcard_status'] ?? '') === 'HALL_REVIEW') {
        return 'در حال بررسی مدیر سالن';
    }
    if (in_array($wx, ['ON_HOLD', 'PAUSED'], true) || $tech === 'EXECUTION_BLOCKED') {
        return 'متوقف‌شده';
    }
    if (in_array($wx, ['WAITING_FOR_PARTS', 'PARTS_CONSUMPTION_PENDING'], true)) {
        return 'در انتظار قطعه';
    }
    if (in_array($tech, ['WAITING_FOR_APPROVAL', 'SENT_TO_CUSTOMER', 'SENT_TO_CRM'], true)
        || $wx === 'WAITING_FOR_CUSTOMER') {
        return 'در انتظار تأیید مشتری';
    }
    if (in_array($wx, ['WORK_STARTED', 'SERVICE_IN_PROGRESS', 'PARTS_CONSUMED', 'SERVICE_COMPLETED'], true)) {
        return 'در حال انجام';
    }
    if (in_array($wx, ['APPROVED_FOR_WORK', 'WORK_QUEUE'], true) || $tech === 'TECHNICIAN_ASSIGNED') {
        return 'آماده شروع';
    }
    if ($assignStatus === 'ACTIVE' || $tech === 'TEAM_ASSIGNED') {
        return 'کارهای جدید';
    }

    return 'کارهای جدید';
}

/**
 * After unit completion: close only the matching active TEAM_ASSIGNMENT and return JobCard to Hall.
 * Does not set READY_FOR_QC. Does not close other unit assignments.
 * Requires an ACTIVE matching assignment — no JobCard write if missing.
 *
 * @return array{ok:bool,message:string}
 */
function m360_fulljob_return_unit_completion_to_hall($conn, int $jobcardId, string $teamCode, int $actorUserId): array
{
    $teamCode = strtoupper(trim($teamCode));
    if (!m360_fulljob_is_valid_team_code($teamCode)) {
        return ['ok' => false, 'message' => 'کد واحد نامعتبر است.'];
    }
    if ($jobcardId < 1) {
        return ['ok' => false, 'message' => 'پرونده کار نامعتبر است.'];
    }

    $active = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'TEAM_ASSIGNMENT' AND status = N'ACTIVE' AND team_code = ?
         ORDER BY assignment_id DESC",
        [$jobcardId, $teamCode]
    );
    if ($active === []) {
        return ['ok' => false, 'message' => 'تخصیص فعال و معتبری برای تکمیل این کار یافت نشد.'];
    }

    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcard_assignments
         SET status = N'CLOSED', closed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME()
         WHERE assignment_id = ? AND jobcard_id = ? AND status = N'ACTIVE'",
        [(int)$active[0]['assignment_id'], $jobcardId]
    );

    $hall = m360_fulljob_ensure_hall_cartable($conn, $jobcardId, $actorUserId);
    if (empty($hall['ok'])) {
        return ['ok' => false, 'message' => 'بازگشت به کارتابل مدیر سالن ناموفق بود.'];
    }

    $sets = [
        "jobcard_status = N'HALL_REVIEW'",
        "technical_status = N'UNDER_HALL_REVIEW'",
        'updated_at = SYSUTCDATETIME()',
    ];
    $params = [];
    if (customer_core_column_exists($conn, 'erp_jobcards', 'work_execution_status')) {
        $sets[] = "work_execution_status = N'TECHNICAL_COMPLETION_REVIEW'";
    }
    $params[] = $jobcardId;
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
        $params
    );

    return [
        'ok' => true,
        'message' => 'کار واحد تکمیل و برای بررسی مدیر سالن ارسال شد.',
    ];
}

/**
 * Resolve/validate unit completion context before any write.
 *
 * @param array{user_id?:int,role_code?:string} $actor
 * @return array{ok:bool,http:int,team_code:string,message:string,inferred:bool,assignment_id:int}
 */
function m360_fulljob_validate_unit_completion_context($conn, int $jobcardId, string $rawTeam, array $actor): array
{
    $fail = static function (int $http, string $message): array {
        return ['ok' => false, 'http' => $http, 'team_code' => '', 'message' => $message, 'inferred' => false, 'assignment_id' => 0];
    };

    if (!is_resource($conn) || $jobcardId < 1) {
        return $fail(422, 'پرونده کار نامعتبر است.');
    }

    $jobcard = m360_fulljob_fetch_jobcard($conn, $jobcardId);
    if ($jobcard === null) {
        return $fail(422, 'پرونده کار یافت نشد.');
    }

    if (!m360_fulljob_technician_can_open($conn, $jobcardId, $actor)) {
        return $fail(403, 'دسترسی به این پرونده برای کاربر جاری مجاز نیست.');
    }

    $rawTeam = strtoupper(trim($rawTeam));
    $active = m360_fulljob_active_team_assignments($conn, $jobcardId);

    if ($rawTeam !== '' && !m360_fulljob_is_valid_team_code($rawTeam)) {
        return $fail(422, 'کد واحد نامعتبر است.');
    }

    if ($rawTeam !== '') {
        $match = null;
        foreach ($active as $row) {
            if (strtoupper(trim((string)($row['team_code'] ?? ''))) === $rawTeam) {
                $match = $row;
                break;
            }
        }
        if ($match === null) {
            return $fail(422, 'تخصیص فعال و معتبری برای تکمیل این کار یافت نشد.');
        }

        return [
            'ok' => true,
            'http' => 200,
            'team_code' => $rawTeam,
            'message' => '',
            'inferred' => false,
            'assignment_id' => (int)$match['assignment_id'],
        ];
    }

    if ($active === []) {
        return $fail(422, 'تخصیص فعال و معتبری برای تکمیل این کار یافت نشد.');
    }

    if (count($active) > 1) {
        return $fail(409, 'برای این پرونده چند واحد فعال وجود دارد. واحد انجام‌دهنده کار باید مشخص شود.');
    }

    $only = $active[0];
    $code = strtoupper(trim((string)($only['team_code'] ?? '')));
    if (!m360_fulljob_is_valid_team_code($code)) {
        return $fail(422, 'تخصیص فعال و معتبری برای تکمیل این کار یافت نشد.');
    }

    return [
        'ok' => true,
        'http' => 200,
        'team_code' => $code,
        'message' => 'واحد از تنها تخصیص فعال استنتاج شد.',
        'inferred' => true,
        'assignment_id' => (int)$only['assignment_id'],
    ];
}

/**
 * @deprecated Prefer m360_fulljob_validate_unit_completion_context
 */
function m360_fulljob_resolve_completion_team_code($conn, int $jobcardId, string $rawTeam): string
{
    $actor = ['user_id' => 0, 'role_code' => 'SERVICE_MANAGER'];
    $ctx = m360_fulljob_validate_unit_completion_context($conn, $jobcardId, $rawTeam, $actor);
    return !empty($ctx['ok']) ? (string)$ctx['team_code'] : '';
}

function m360_fulljob_can_render_ready_for_qc(string $roleCode, array $jobcardRow): bool
{
    if (!m360_fulljob_role_can_hall($roleCode)) {
        return false;
    }
    $js = strtoupper(trim((string)($jobcardRow['jobcard_status'] ?? '')));
    $tech = strtoupper(trim((string)($jobcardRow['technical_status'] ?? '')));
    $wx = strtoupper(trim((string)($jobcardRow['work_execution_status'] ?? '')));

    return $js === 'HALL_REVIEW'
        || $tech === 'UNDER_HALL_REVIEW'
        || in_array($wx, ['TECHNICAL_COMPLETION_REVIEW', 'TECHNICAL_COMPLETED'], true);
}

function m360_fulljob_list_requests($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_jobcard_technical_requests WHERE jobcard_id = ? ORDER BY technical_request_id DESC',
        [$jobcardId]
    );
}

function m360_fulljob_list_requests_by_status($conn, array $statuses): array
{
    if ($statuses === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    return customer_core_fetch_rows(
        $conn,
        "SELECT tr.*, j.jobcard_number
         FROM dbo.erp_jobcard_technical_requests tr
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = tr.jobcard_id
         WHERE tr.status IN ($placeholders)
         ORDER BY tr.technical_request_id DESC",
        $statuses
    );
}

function m360_fulljob_fetch_request($conn, int $technicalRequestId): ?array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcard_technical_requests WHERE technical_request_id = ?',
        [$technicalRequestId]
    );

    return $rows[0] ?? null;
}

function m360_fulljob_request_events($conn, int $technicalRequestId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_jobcard_technical_request_events WHERE technical_request_id = ? ORDER BY event_id DESC',
        [$technicalRequestId]
    );
}

function m360_fulljob_record_request_event(
    $conn,
    int $technicalRequestId,
    int $jobcardId,
    string $eventName,
    ?string $oldStatus,
    ?string $newStatus,
    ?string $note,
    array $actor,
    array $metadata = []
): void {
    $encoded = $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_jobcard_technical_request_events
            (technical_request_id, jobcard_id, event_name, old_status, new_status, event_note, actor_user_id, actor_role, event_metadata_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $technicalRequestId,
            $jobcardId,
            $eventName,
            $oldStatus,
            $newStatus,
            $note,
            (int)($actor['user_id'] ?? 0) ?: null,
            (string)($actor['role_code'] ?? ''),
            $encoded,
        ]
    );
}

function m360_fulljob_ensure_hall_cartable($conn, int $jobcardId, int $actorUserId): array
{
    $existing = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'HALL_INTAKE' AND status = N'ACTIVE'
         ORDER BY assignment_id DESC",
        [$jobcardId]
    );
    if ($existing !== []) {
        return ['ok' => true, 'assignment_id' => (int)$existing[0]['assignment_id'], 'created' => false];
    }

    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_jobcard_assignments
            (jobcard_id, assignment_type, assigned_by_user_id, priority, assignment_description)
         VALUES (?, N'HALL_INTAKE', ?, N'NORMAL', N'ارجاع به صف مدیر سالن پس از آماده شدن پرونده.')",
        [$jobcardId, $actorUserId]
    );
    if ($ok === false) {
        return ['ok' => false, 'assignment_id' => 0, 'created' => false];
    }
    $assignmentId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments WHERE jobcard_id = ? ORDER BY assignment_id DESC', [$jobcardId]) ?? 0);
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcards
         SET jobcard_status = N'HALL_REVIEW',
             technical_status = N'HALL_REVIEW',
             ready_for_technical_at = COALESCE(ready_for_technical_at, SYSUTCDATETIME()),
             updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ?",
        [$jobcardId]
    );

    return ['ok' => true, 'assignment_id' => $assignmentId, 'created' => true];
}

/**
 * Assign specialist unit. Idempotent for identical ACTIVE TEAM_ASSIGNMENT + team_code.
 * Does not close/recreate on repeat. Concurrent duplicates blocked via UPDLOCK/HOLDLOCK.
 *
 * @return array{ok:bool,message:string,idempotent?:bool,conflict?:bool,http_status?:int}
 */
function m360_fulljob_assign_team($conn, int $jobcardId, string $teamCode, int $actorUserId, string $description = ''): array
{
    $teamCode = strtoupper(trim($teamCode));
    if ($jobcardId < 1) {
        return ['ok' => false, 'message' => 'پرونده کار نامعتبر است.', 'http_status' => 422];
    }
    if (!m360_fulljob_is_valid_team_code($teamCode)) {
        return ['ok' => false, 'message' => 'نوع تیم نامعتبر است.', 'http_status' => 422];
    }
    if (!m360_fulljob_tx_begin($conn)) {
        return ['ok' => false, 'message' => 'شروع تراکنش ناموفق بود.', 'http_status' => 500];
    }

    try {
        $existingId = (int)(customer_core_scalar(
            $conn,
            "SELECT TOP 1 assignment_id
             FROM dbo.erp_jobcard_assignments WITH (UPDLOCK, HOLDLOCK)
             WHERE jobcard_id = ?
               AND assignment_type = N'TEAM_ASSIGNMENT'
               AND team_code = ?
               AND status = N'ACTIVE'",
            [$jobcardId, $teamCode]
        ) ?? 0);

        if ($existingId > 0) {
            m360_fulljob_tx_commit($conn);

            return [
                'ok' => true,
                'idempotent' => true,
                'conflict' => true,
                'http_status' => 409,
                'message' => 'این پرونده قبلاً به این واحد تخصیص داده شده است.',
                'assignment_id' => $existingId,
            ];
        }

        $desc = $description !== '' ? $description : ('تخصیص ' . m360_fulljob_team_label_fa($teamCode));
        $ok = customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_jobcard_assignments
                (jobcard_id, assignment_type, team_code, assigned_by_user_id, priority, assignment_description)
             VALUES (?, N'TEAM_ASSIGNMENT', ?, ?, N'HIGH', ?)",
            [$jobcardId, $teamCode, $actorUserId, $desc]
        );
        if ($ok === false) {
            throw new RuntimeException('team_assignment_insert_failed');
        }

        // team_code is canonical. assigned_team_id is optional legacy (nullable; OPTIONS has no catalog ID).
        $legacyTeamId = m360_fulljob_legacy_assigned_team_id($teamCode);
        if ($legacyTeamId === null) {
            $jobOk = customer_core_execute(
                $conn,
                "UPDATE dbo.erp_jobcards
                 SET assigned_team_id = NULL,
                     technical_status = N'TEAM_ASSIGNED',
                     updated_at = SYSUTCDATETIME()
                 WHERE jobcard_id = ?",
                [$jobcardId]
            );
        } else {
            $jobOk = customer_core_execute(
                $conn,
                "UPDATE dbo.erp_jobcards
                 SET assigned_team_id = ?,
                     technical_status = N'TEAM_ASSIGNED',
                     updated_at = SYSUTCDATETIME()
                 WHERE jobcard_id = ?",
                [$legacyTeamId, $jobcardId]
            );
        }
        if ($jobOk === false) {
            throw new RuntimeException('jobcard_team_update_failed');
        }

        m360_fulljob_tx_commit($conn);

        return [
            'ok' => true,
            'idempotent' => false,
            'message' => m360_fulljob_team_label_fa($teamCode) . ' ثبت شد.',
        ];
    } catch (Throwable $e) {
        m360_fulljob_tx_rollback($conn);
        @error_log('m360_fulljob_assign_team failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'ثبت تیم ناموفق بود.', 'http_status' => 500];
    }
}

/**
 * Assign technician. Idempotent for identical ACTIVE TECHNICIAN_ASSIGNMENT + user.
 * Does not close/recreate on repeat. Different active technician requires explicit reassignment.
 *
 * @return array{ok:bool,message:string,idempotent?:bool,conflict?:bool,http_status?:int}
 */
function m360_fulljob_assign_technician(
    $conn,
    int $jobcardId,
    int $technicianUserId,
    ?int $assistantUserId,
    int $actorUserId,
    string $priority,
    string $description
): array {
    $priority = in_array($priority, M360_FULLJOB_PRIORITIES, true) ? $priority : 'NORMAL';
    if ($jobcardId < 1) {
        return ['ok' => false, 'message' => 'پرونده کار نامعتبر است.', 'http_status' => 422];
    }
    if ($technicianUserId < 1) {
        return ['ok' => false, 'message' => 'تکنسین معتبر نیست.', 'http_status' => 422];
    }
    if (!m360_fulljob_tx_begin($conn)) {
        return ['ok' => false, 'message' => 'شروع تراکنش ناموفق بود.', 'http_status' => 500];
    }

    try {
        $activeRows = customer_core_fetch_rows(
            $conn,
            "SELECT assignment_id, assigned_to_user_id
             FROM dbo.erp_jobcard_assignments WITH (UPDLOCK, HOLDLOCK)
             WHERE jobcard_id = ?
               AND assignment_type = N'TECHNICIAN_ASSIGNMENT'
               AND status = N'ACTIVE'
             ORDER BY assignment_id DESC",
            [$jobcardId]
        );

        foreach ($activeRows as $row) {
            $activeTechId = (int)($row['assigned_to_user_id'] ?? 0);
            if ($activeTechId === $technicianUserId) {
                m360_fulljob_tx_commit($conn);

                return [
                    'ok' => true,
                    'idempotent' => true,
                    'conflict' => true,
                    'http_status' => 409,
                    'message' => 'این تکنسین قبلاً برای این کار تخصیص داده شده است.',
                    'assignment_id' => (int)($row['assignment_id'] ?? 0),
                ];
            }
        }

        if ($activeRows !== []) {
            m360_fulljob_tx_commit($conn);

            return [
                'ok' => false,
                'conflict' => true,
                'http_status' => 409,
                'message' => 'تکنسین فعال دیگری برای این کار وجود دارد. تخصیص مجدد فقط از مسیر صریح بازتخصیص مجاز است.',
            ];
        }

        $ok = customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_jobcard_assignments
                (jobcard_id, assignment_type, assigned_to_user_id, assistant_user_id, assigned_by_user_id, priority, due_at, assignment_description)
             VALUES (?, N'TECHNICIAN_ASSIGNMENT', ?, ?, ?, ?, DATEADD(hour, 6, SYSUTCDATETIME()), ?)",
            [$jobcardId, $technicianUserId, $assistantUserId, $actorUserId, $priority, $description]
        );
        if ($ok === false) {
            throw new RuntimeException('technician_assignment_insert_failed');
        }

        $jobOk = customer_core_execute(
            $conn,
            "UPDATE dbo.erp_jobcards
             SET assigned_technician_user_id = ?,
                 technical_status = N'TECHNICIAN_ASSIGNED',
                 updated_at = SYSUTCDATETIME()
             WHERE jobcard_id = ?",
            [$technicianUserId, $jobcardId]
        );
        if ($jobOk === false) {
            throw new RuntimeException('jobcard_technician_update_failed');
        }

        m360_fulljob_tx_commit($conn);

        return ['ok' => true, 'idempotent' => false, 'message' => 'تکنسین ثبت شد.'];
    } catch (Throwable $e) {
        m360_fulljob_tx_rollback($conn);
        @error_log('m360_fulljob_assign_technician failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'ثبت تکنسین ناموفق بود.', 'http_status' => 500];
    }
}

function m360_fulljob_assigned_technician_jobs($conn, array $actor): array
{
    $roleCode = (string)($actor['role_code'] ?? '');
    if (m360_fulljob_role_can_hall($roleCode)) {
        return customer_core_fetch_rows(
            $conn,
            "SELECT a.*, j.jobcard_number, j.customer_id, j.vehicle_id, j.technical_status, j.work_execution_status,
                    c.full_name AS customer_name, v.plate_number
             FROM dbo.erp_jobcard_assignments a
             INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
             LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
             LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
             WHERE a.assignment_type = N'TECHNICIAN_ASSIGNMENT' AND a.status = N'ACTIVE'
             ORDER BY a.assignment_id DESC"
        );
    }

    return customer_core_fetch_rows(
        $conn,
        "SELECT a.*, j.jobcard_number, j.customer_id, j.vehicle_id, j.technical_status, j.work_execution_status,
                c.full_name AS customer_name, v.plate_number
         FROM dbo.erp_jobcard_assignments a
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE a.assignment_type = N'TECHNICIAN_ASSIGNMENT'
           AND a.status = N'ACTIVE'
           AND (a.assigned_to_user_id = ? OR a.assistant_user_id = ?)
         ORDER BY a.assignment_id DESC",
        [(int)$actor['user_id'], (int)$actor['user_id']]
    );
}

function m360_fulljob_technician_can_open($conn, int $jobcardId, array $actor): bool
{
    if (m360_fulljob_role_can_hall((string)$actor['role_code'])) {
        return true;
    }
    $count = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'TECHNICIAN_ASSIGNMENT' AND status = N'ACTIVE'
           AND (assigned_to_user_id = ? OR assistant_user_id = ?)",
        [$jobcardId, (int)$actor['user_id'], (int)$actor['user_id']]
    ) ?? 0);

    return $count > 0;
}

function m360_fulljob_create_request($conn, int $jobcardId, array $input, array $actor): array
{
    if (!m360_fulljob_technician_can_open($conn, $jobcardId, $actor)) {
        return ['ok' => false, 'message' => 'این کاربر مجاز به ثبت درخواست برای این JobCard نیست.', 'technical_request_id' => 0];
    }
    $requestType = strtoupper(trim((string)($input['request_type'] ?? '')));
    if (!in_array($requestType, M360_FULLJOB_REQUEST_TYPES, true)) {
        return ['ok' => false, 'message' => 'نوع درخواست معتبر نیست.', 'technical_request_id' => 0];
    }
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    if ($title === '' || $description === '') {
        return ['ok' => false, 'message' => 'عنوان و شرح درخواست الزامی است.', 'technical_request_id' => 0];
    }
    $priority = strtoupper(trim((string)($input['priority'] ?? 'NORMAL')));
    $risk = strtoupper(trim((string)($input['risk_level'] ?? 'NO_RISK')));
    $priority = in_array($priority, M360_FULLJOB_PRIORITIES, true) ? $priority : 'NORMAL';
    $risk = in_array($risk, M360_FULLJOB_RISKS, true) ? $risk : 'NO_RISK';
    $uid = trim((string)($input['request_uid'] ?? ''));
    if ($uid === '') {
        $uid = hash('sha256', $jobcardId . '|' . $requestType . '|' . $title . '|' . (string)$actor['user_id']);
    }
    $existing = customer_core_fetch_rows($conn, 'SELECT TOP 1 technical_request_id FROM dbo.erp_jobcard_technical_requests WHERE request_uid = ?', [$uid]);
    if ($existing !== []) {
        return ['ok' => true, 'message' => 'درخواست قبلاً ثبت شده است.', 'technical_request_id' => (int)$existing[0]['technical_request_id']];
    }
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_jobcard_technical_requests
            (request_uid, jobcard_id, request_type, requested_by_user_id, requested_by_role, assigned_operation_id,
             title, description, priority, risk_level, evidence_files, estimated_cost_impact, estimated_time_impact_minutes,
             requires_customer_approval, requires_part, requires_external_service, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $uid,
            $jobcardId,
            $requestType,
            (int)$actor['user_id'],
            (string)$actor['role_code'],
            (int)($input['assigned_operation_id'] ?? 0) > 0 ? (int)$input['assigned_operation_id'] : null,
            mb_substr($title, 0, 300),
            $description,
            $priority,
            $risk,
            trim((string)($input['evidence_files'] ?? '')) ?: null,
            (float)($input['estimated_cost_impact'] ?? 0),
            (int)($input['estimated_time_impact_minutes'] ?? 0),
            !empty($input['requires_customer_approval']) ? 1 : 0,
            !empty($input['requires_part']) ? 1 : 0,
            !empty($input['requires_external_service']) ? 1 : 0,
            'UNDER_HALL_REVIEW',
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت درخواست ناموفق بود.', 'technical_request_id' => 0];
    }
    $technicalRequestId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 technical_request_id FROM dbo.erp_jobcard_technical_requests WHERE request_uid = ?', [$uid]) ?? 0);
    m360_fulljob_record_request_event($conn, $technicalRequestId, $jobcardId, 'TECH_REQUEST_SUBMITTED', 'DRAFT', 'UNDER_HALL_REVIEW', $title, $actor);

    return ['ok' => true, 'message' => 'درخواست ثبت شد.', 'technical_request_id' => $technicalRequestId];
}

function m360_fulljob_review_request($conn, int $technicalRequestId, string $decision, string $note, array $actor): array
{
    if (!m360_fulljob_role_can_hall((string)$actor['role_code'])) {
        return ['ok' => false, 'message' => 'این نقش مجاز به بررسی درخواست نیست.'];
    }
    $request = m360_fulljob_fetch_request($conn, $technicalRequestId);
    if ($request === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    if ((int)$request['requested_by_user_id'] === (int)$actor['user_id'] && (string)$actor['role_code'] === 'TECHNICIAN') {
        return ['ok' => false, 'message' => 'تکنسین نمی‌تواند درخواست خودش را تأیید کند.'];
    }
    $decision = strtoupper(trim($decision));
    $note = trim($note);
    if ($note === '') {
        return ['ok' => false, 'message' => 'یادداشت بررسی درخواست برای این اقدام الزامی است.'];
    }
    $statusMap = [
        'APPROVE_ESTIMATE_REVISION' => 'APPROVED_FOR_ESTIMATE_REVISION',
        'REJECT' => 'REJECTED_BY_HALL',
        'NEEDS_MORE_EVIDENCE' => 'NEEDS_MORE_EVIDENCE',
        'ROUTE_OTHER_TEAM' => 'ROUTED_TO_OTHER_TEAM',
        'SEND_INVENTORY' => 'SENT_TO_INVENTORY',
        'SEND_PURCHASE' => 'SENT_TO_PURCHASE',
        'SEND_CRM' => 'SENT_TO_CRM',
        'SEND_CUSTOMER' => 'SENT_TO_CUSTOMER',
        'ISSUE_HOLD' => 'EXECUTION_BLOCKED',
        'ALLOW_EXECUTION' => 'EXECUTION_ALLOWED',
        'CLOSE' => 'CLOSED',
    ];
    if (!isset($statusMap[$decision])) {
        return ['ok' => false, 'message' => 'تصمیم معتبر نیست.'];
    }
    $oldStatus = (string)($request['status'] ?? '');
    $newStatus = $statusMap[$decision];
    $jobcardId = (int)$request['jobcard_id'];
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcard_technical_requests
         SET status = ?, reviewed_by_user_id = ?, review_decision = ?, review_note = ?, reviewed_at = SYSUTCDATETIME(),
             closed_at = CASE WHEN ? IN (N\'REJECTED_BY_HALL\', N\'CLOSED\') THEN SYSUTCDATETIME() ELSE closed_at END,
             updated_at = SYSUTCDATETIME()
         WHERE technical_request_id = ?',
        [$newStatus, (int)$actor['user_id'], $decision, $note, $newStatus, $technicalRequestId]
    );
    if ($decision === 'ISSUE_HOLD') {
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_jobcards
             SET work_execution_status = N'ON_HOLD', technical_status = N'WORK_HOLD', updated_at = SYSUTCDATETIME()
             WHERE jobcard_id = ?",
            [$jobcardId]
        );
    }
    if ($decision === 'ALLOW_EXECUTION') {
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_jobcards
             SET work_execution_status = N'WORK_QUEUE', updated_at = SYSUTCDATETIME()
             WHERE jobcard_id = ?",
            [$jobcardId]
        );
    }
    m360_fulljob_record_request_event($conn, $technicalRequestId, $jobcardId, 'HALL_REVIEW_' . $decision, $oldStatus, $newStatus, $note, $actor);

    return ['ok' => true, 'message' => 'بررسی درخواست ثبت شد.', 'status' => $newStatus];
}

function m360_fulljob_ensure_service_operation($conn, int $jobcardId, int $technicianUserId, int $actorUserId): int
{
    $existing = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY service_operation_id DESC",
        [$jobcardId]
    );
    if ($existing !== []) {
        return (int)$existing[0]['service_operation_id'];
    }
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_service_operations
            (jobcard_id, service_title, service_description, assigned_to_user_id, service_status, created_by_user_id, is_active)
         VALUES (?, N'AUTO-UAT-FULLJOB technical operation', N'Synthetic full job lifecycle operation', ?, N'IN_PROGRESS', ?, 1)",
        [$jobcardId, $technicianUserId, $actorUserId]
    );

    return (int)(customer_core_scalar($conn, 'SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations WHERE jobcard_id = ? ORDER BY service_operation_id DESC', [$jobcardId]) ?? 0);
}

function m360_fulljob_create_external_service($conn, int $jobcardId, int $technicalRequestId, int $actorUserId): int
{
    $existing = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 external_service_request_id FROM dbo.erp_external_service_requests WHERE technical_request_id = ? ORDER BY external_service_request_id DESC',
        [$technicalRequestId]
    );
    if ($existing !== []) {
        return (int)$existing[0]['external_service_request_id'];
    }
    $custody = json_encode([
        'synthetic_case' => 'AUTO-UAT-FULLJOB-20260716',
        'vehicle_or_part_release_requires_audit' => true,
        'handoff' => 'Hall manager approved external service request; physical send-out pending customer approval and dispatch audit',
    ], JSON_UNESCAPED_UNICODE);
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_external_service_requests
            (technical_request_id, jobcard_id, vendor_name, service_title, vehicle_component, send_out_at,
             responsible_user_id, expected_return_at, estimated_cost, requires_customer_approval,
             chain_of_custody_json, status, created_by_user_id)
         VALUES (?, ?, N'AUTO-UAT external vendor', N'خدمت خارج از مجموعه', N'AUTO-UAT component',
                 NULL, ?, DATEADD(day, 1, SYSUTCDATETIME()), 2500000, 1, ?, N'APPROVED', ?)",
        [$technicalRequestId, $jobcardId, $actorUserId, $custody, $actorUserId]
    );

    return (int)(customer_core_scalar($conn, 'SELECT TOP 1 external_service_request_id FROM dbo.erp_external_service_requests WHERE technical_request_id = ? ORDER BY external_service_request_id DESC', [$technicalRequestId]) ?? 0);
}

function m360_fulljob_list_external_services($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_external_service_requests WHERE jobcard_id = ? ORDER BY external_service_request_id DESC',
        [$jobcardId]
    );
}

function m360_fulljob_request_counts($conn, int $jobcardId): array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT status, COUNT(*) AS c FROM dbo.erp_jobcard_technical_requests WHERE jobcard_id = ? GROUP BY status',
        [$jobcardId]
    );
    $counts = [];
    foreach ($rows as $row) {
        $counts[(string)$row['status']] = (int)$row['c'];
    }

    return $counts;
}

function m360_fulljob_open_request_count($conn, int $jobcardId): int
{
    return (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_technical_requests
         WHERE jobcard_id = ? AND status NOT IN (N'CLOSED', N'REJECTED_BY_HALL', N'CUSTOMER_REJECTED')",
        [$jobcardId]
    ) ?? 0);
}

function m360_fulljob_customer_approval_blocked($conn, int $jobcardId): array
{
    $estimate = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($estimate === null) {
        return ['ok' => false, 'message' => 'برآورد فعال یافت نشد.'];
    }
    $status = strtoupper((string)($estimate['estimate_status'] ?? ''));
    if (in_array($status, [M360_EST_STATUS_APPROVED, M360_EST_STATUS_APPROVED_WORK], true)) {
        return ['ok' => true, 'message' => 'برآورد تأیید شده است.'];
    }

    return ['ok' => false, 'message' => 'تا تأیید مشتری، اقدام پرسنل به‌جای مشتری مجاز نیست.'];
}

