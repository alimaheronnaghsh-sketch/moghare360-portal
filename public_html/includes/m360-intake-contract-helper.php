<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1.5 — Intake contract helper (ERP + P2 gate).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-contract-template-render.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';

const M360_CONTRACT_TABLE = 'erp_intake_contracts';
const M360_CONTRACT_SIG_TABLE = 'erp_intake_contract_signatures';
const M360_CONTRACT_EVT_TABLE = 'erp_intake_contract_events';

const M360_CONTRACT_CSRF_PURPOSE = 'intake_contract_reception';

const M360_CONTRACT_STATUS_DRAFT = 'DRAFT';
const M360_CONTRACT_STATUS_GENERATED = 'GENERATED';
const M360_CONTRACT_STATUS_SENT = 'SENT';
const M360_CONTRACT_STATUS_VIEWED = 'VIEWED';
const M360_CONTRACT_STATUS_OTP_SENT = 'OTP_SENT';
const M360_CONTRACT_STATUS_SIGNED = 'SIGNED';
const M360_CONTRACT_STATUS_EXPIRED = 'EXPIRED';
const M360_CONTRACT_STATUS_CANCELLED = 'CANCELLED';
const M360_CONTRACT_STATUS_OVERRIDDEN = 'OVERRIDDEN';

const M360_CONTRACT_TOKEN_TTL_SECONDS = 259200;

/** @var array<string, string> */
const M360_CONTRACT_STATUS_LABELS_FA = [
    M360_CONTRACT_STATUS_DRAFT => 'پیش‌نویس',
    M360_CONTRACT_STATUS_GENERATED => 'تولید شده',
    M360_CONTRACT_STATUS_SENT => 'ارسال شده',
    M360_CONTRACT_STATUS_VIEWED => 'مشاهده شده',
    M360_CONTRACT_STATUS_OTP_SENT => 'کد تأیید ارسال شد',
    M360_CONTRACT_STATUS_SIGNED => 'امضا شده',
    M360_CONTRACT_STATUS_EXPIRED => 'منقضی',
    M360_CONTRACT_STATUS_CANCELLED => 'لغو شده',
    M360_CONTRACT_STATUS_OVERRIDDEN => 'تأیید مدیریتی',
];

function m360_intake_contract_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_intake_contract_require_staff(): void
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();
    if ($userId === null || $userId <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_intake_contract_table_exists($conn, string $table): bool
{
    return customer_core_table_exists($conn, $table);
}

function m360_intake_contract_hash(string $value): string
{
    return hash('sha256', $value);
}

function m360_intake_contract_generate_token(): array
{
    $raw = bin2hex(random_bytes(32));
    return [
        'raw' => $raw,
        'hash' => m360_intake_contract_hash($raw),
        'expires_at' => gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS),
    ];
}

function m360_intake_contract_public_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    if ($host === '' && PHP_SAPI === 'cli') {
        return 'http://localhost/moghare360';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

function m360_intake_contract_customer_url(string $rawToken): string
{
    return m360_intake_contract_public_base_url() . '/customer-intake-contract.php?token=' . rawurlencode($rawToken);
}

function m360_intake_contract_sign_url(string $rawToken): string
{
    return m360_intake_contract_public_base_url() . '/customer-intake-contract-sign.php?token=' . rawurlencode($rawToken);
}

/**
 * @return array<string, mixed>
 */
function m360_intake_contract_build_snapshot($conn, ?int $jobcardId, ?int $onlineRequestId): array
{
    $data = [
        'customer_name' => '-',
        'mobile' => '-',
        'vehicle' => '-',
        'plate' => '-',
        'vin' => '-',
        'odometer' => '-',
        'service_type' => '-',
        'cost_range' => 'پس از کارشناسی اعلام می‌شود',
        'prepayment' => 'مطابق اعلام پذیرش',
        'purchase_limit' => 'مطابق انتخاب پذیرش',
        'test_drive_allowed' => 'مطابق انتخاب پرونده',
        'body_insurance_status' => 'مطابق انتخاب پرونده',
        'checklist_summary' => 'ثبت‌شده در پرونده پذیرش',
        'jobcard_id' => $jobcardId !== null ? (string)$jobcardId : '-',
        'online_request_id' => $onlineRequestId !== null ? (string)$onlineRequestId : '-',
        'visit_date' => date('Y-m-d H:i'),
        'reception_date' => date('Y-m-d'),
        'contract_hash' => '-',
    ];

    if ($conn !== false && $jobcardId !== null && $jobcardId > 0 && customer_core_table_exists($conn, 'erp_jobcards')) {
        $sql = 'SELECT TOP 1 j.jobcard_id, j.customer_id, j.vehicle_id, j.intake_mileage, j.customer_complaint, j.reception_at,
                       c.full_name, c.primary_mobile, v.plate_number, v.brand, v.model, v.vin
                FROM dbo.erp_jobcards j
                LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
                LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
                WHERE j.jobcard_id = ?';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$jobcardId])) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false) {
                $data['customer_name'] = trim((string)($row['full_name'] ?? '')) ?: '-';
                $data['mobile'] = trim((string)($row['primary_mobile'] ?? '')) ?: '-';
                $data['vehicle'] = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? ''))) ?: '-';
                $data['plate'] = trim((string)($row['plate_number'] ?? '')) ?: '-';
                $data['vin'] = trim((string)($row['vin'] ?? '')) ?: '-';
                $data['odometer'] = trim((string)($row['intake_mileage'] ?? '')) ?: '-';
                $data['service_type'] = trim((string)($row['customer_complaint'] ?? '')) ?: '-';
                $data['reception_date'] = substr((string)($row['reception_at'] ?? date('Y-m-d')), 0, 10);
                $data['jobcard_id'] = (string)$jobcardId;
            }
        }
    }

    if ($onlineRequestId !== null && $onlineRequestId > 0) {
        $req = m360_online_req_fetch_by_id($conn, $onlineRequestId);
        if ($req !== null) {
            $payload = m360_online_req_parse_payload($req['request_payload_json'] ?? null);
            if ($data['customer_name'] === '-') {
                $data['customer_name'] = (string)($req['customer_name'] ?? '-');
            }
            if ($data['mobile'] === '-') {
                $data['mobile'] = (string)($req['mobile'] ?? '-');
            }
            if ($data['plate'] === '-') {
                $data['plate'] = (string)($req['vehicle_plate'] ?? '-');
            }
            if ($data['visit_date'] === date('Y-m-d H:i')) {
                $visit = trim((string)($req['visit_date'] ?? ($payload['visit_date'] ?? '')));
                if ($visit !== '') {
                    $data['visit_date'] = $visit;
                }
            }
            $data['online_request_id'] = (string)$onlineRequestId;
            if (isset($payload['estimated_cost_range'])) {
                $data['cost_range'] = (string)$payload['estimated_cost_range'];
            }
            if (isset($payload['prepayment_amount'])) {
                $data['prepayment'] = (string)$payload['prepayment_amount'];
            }
            if (isset($payload['purchase_limit'])) {
                $data['purchase_limit'] = (string)$payload['purchase_limit'];
            }
            if (isset($payload['test_drive_allowed'])) {
                $data['test_drive_allowed'] = (string)$payload['test_drive_allowed'];
            }
            if (isset($payload['body_insurance_status'])) {
                $data['body_insurance_status'] = (string)$payload['body_insurance_status'];
            }
        }
    }

    return $data;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_fetch_by_id($conn, int $contractId): ?array
{
    if (!is_resource($conn) || $contractId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 * FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE contract_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    return $normalized;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_fetch_by_token_hash($conn, string $tokenHash): ?array
{
    if (!is_resource($conn) || $tokenHash === '') {
        return null;
    }
    $sql = 'SELECT TOP 1 * FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE secure_token_hash = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$tokenHash])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    return $normalized;
}

function m360_intake_contract_token_valid(array $contractRow): bool
{
    if ((string)($contractRow['contract_status'] ?? '') === M360_CONTRACT_STATUS_CANCELLED) {
        return false;
    }
    if ((string)($contractRow['contract_status'] ?? '') === M360_CONTRACT_STATUS_EXPIRED) {
        return false;
    }
    $expires = trim((string)($contractRow['secure_token_expires_at'] ?? ''));
    if ($expires === '') {
        return true;
    }
    return strtotime($expires) >= time();
}

function m360_intake_contract_is_signed(array $contractRow): bool
{
    $status = strtoupper((string)($contractRow['contract_status'] ?? ''));
    return $status === M360_CONTRACT_STATUS_SIGNED || $status === M360_CONTRACT_STATUS_OVERRIDDEN;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_find_active_for_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $sql = "SELECT TOP 1 * FROM dbo." . M360_CONTRACT_TABLE . "
            WHERE jobcard_id = ? AND contract_status NOT IN (N'CANCELLED', N'EXPIRED')
            ORDER BY contract_id DESC";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    if (m360_intake_contract_is_signed($normalized)) {
        return $normalized;
    }
    if (in_array(strtoupper((string)$normalized['contract_status']), [M360_CONTRACT_STATUS_GENERATED, M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_VIEWED, M360_CONTRACT_STATUS_OTP_SENT], true)) {
        return $normalized;
    }
    return null;
}

function m360_intake_contract_record_event(
    $conn,
    int $contractId,
    string $eventName,
    ?string $note = null,
    ?int $userId = null
): void {
    if (!m360_intake_contract_table_exists($conn, M360_CONTRACT_EVT_TABLE) || $contractId < 1) {
        return;
    }
    $ip = customer_core_client_ip();
    $ua = customer_core_user_agent();
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_EVT_TABLE . ' (contract_id, event_name, event_note, event_ip, event_user_agent, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$contractId, $eventName, $note, $ip, $ua, $userId]
    );
}

/**
 * @return array{ok:bool,message:string,contract_id:?int,raw_token:?string,reused:bool}
 */
function m360_intake_contract_generate_for_jobcard(int $jobcardId, ?int $onlineRequestId = null): array
{
    $conn = customer_core_db();
    if ($conn === false || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'شناسه کارت کار معتبر نیست.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $existing = m360_intake_contract_find_active_for_jobcard($conn, $jobcardId);
    if ($existing !== null) {
        if (m360_intake_contract_is_signed($existing)) {
            return ['ok' => false, 'message' => 'قرارداد این پرونده قبلاً امضا شده است.', 'contract_id' => (int)$existing['contract_id'], 'raw_token' => null, 'reused' => true];
        }
        return ['ok' => true, 'message' => 'قرارداد فعال موجود استفاده شد.', 'contract_id' => (int)$existing['contract_id'], 'raw_token' => null, 'reused' => true];
    }

    $snapshot = m360_intake_contract_build_snapshot($conn, $jobcardId, $onlineRequestId);
    $html = m360_contract_render_html($snapshot, true);
    $bodyHash = m360_intake_contract_hash($html);
    $snapshot['contract_hash'] = $bodyHash;
    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{}';
    }

    $token = m360_intake_contract_generate_token();
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $mobile = trim((string)($snapshot['mobile'] ?? ''));
    if ($mobile === '' || $mobile === '-') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری برای قرارداد یافت نشد.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_TABLE . ' (
            contract_version, online_request_id, jobcard_id, customer_id, vehicle_id, mobile,
            contract_status, contract_title, contract_body_hash, contract_data_json,
            secure_token_hash, secure_token_expires_at, created_by_user_id
        ) VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            M360_CONTRACT_VERSION,
            $onlineRequestId,
            $jobcardId,
            $mobile,
            M360_CONTRACT_STATUS_GENERATED,
            M360_CONTRACT_TITLE,
            $bodyHash,
            $json,
            $token['hash'],
            $token['expires_at'],
            $userId,
        ]
    );

    if ($insertOk === false) {
        return ['ok' => false, 'message' => 'ثبت قرارداد ناموفق بود.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $contractId = (int)(customer_core_scope_identity($conn) ?? 0);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_GENERATED', 'JobCard #' . $jobcardId, $userId);

    return ['ok' => true, 'message' => 'قرارداد پذیرش تولید شد.', 'contract_id' => $contractId, 'raw_token' => $token['raw'], 'reused' => false];
}

/** @return array{ok:bool,message:string} */
function m360_intake_contract_mark_sent($conn, int $contractId): array
{
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (m360_intake_contract_is_signed($row)) {
        return ['ok' => false, 'message' => 'قرارداد امضا شده قابل ارسال مجدد نیست.'];
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, sent_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_SENT, $contractId]
    );
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_SENT', null, erp_auth_current_user_id());
    return ['ok' => true, 'message' => 'وضعیت ارسال ثبت شد.'];
}

function m360_intake_contract_mark_viewed($conn, int $contractId): void
{
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null || m360_intake_contract_is_signed($row)) {
        return;
    }
    $status = strtoupper((string)$row['contract_status']);
    if ($status === M360_CONTRACT_STATUS_SENT || $status === M360_CONTRACT_STATUS_GENERATED) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, viewed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
            [M360_CONTRACT_STATUS_VIEWED, $contractId]
        );
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_VIEWED', null, null);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function m360_intake_contract_list($conn, ?string $statusFilter = null, int $limit = 100): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $params = [];
    $where = '1=1';
    if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'ALL') {
        $where .= ' AND c.contract_status = ?';
        $params[] = strtoupper($statusFilter);
    }
    $sql = 'SELECT TOP ' . $limit . ' c.*, j.jobcard_number
            FROM dbo.' . M360_CONTRACT_TABLE . ' c
            LEFT JOIN dbo.erp_jobcards j ON j.jobcard_id = c.jobcard_id
            WHERE ' . $where . ' ORDER BY c.contract_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $normalized;
    }
    return $rows;
}

/** @return list<array<string, mixed>> */
function m360_intake_contract_events($conn, int $contractId): array
{
    if (!m360_intake_contract_table_exists($conn, M360_CONTRACT_EVT_TABLE) || $contractId < 1) {
        return [];
    }
    $stmt = @odbc_prepare($conn, 'SELECT * FROM dbo.' . M360_CONTRACT_EVT_TABLE . ' WHERE contract_id = ? ORDER BY event_id DESC');
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $normalized;
    }
    return $rows;
}

function m360_contract_required_for_jobcard(int $jobcardId): bool
{
    return $jobcardId > 0;
}

function m360_contract_signed_for_jobcard(int $jobcardId): bool
{
    $conn = customer_core_db();
    if ($conn === false || $jobcardId < 1) {
        return false;
    }
    $sql = "SELECT TOP 1 contract_status, manager_override FROM dbo." . M360_CONTRACT_TABLE . "
            WHERE jobcard_id = ? ORDER BY contract_id DESC";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return false;
    }
    $status = strtoupper((string)($row['contract_status'] ?? ''));
    if ($status === M360_CONTRACT_STATUS_SIGNED) {
        return true;
    }
    if ($status === M360_CONTRACT_STATUS_OVERRIDDEN && (int)($row['manager_override'] ?? 0) === 1) {
        return true;
    }
    return false;
}

function m360_contract_can_continue_to_p2(int $jobcardId): bool
{
    if (!m360_contract_required_for_jobcard($jobcardId)) {
        return false;
    }
    return m360_contract_signed_for_jobcard($jobcardId);
}

function m360_contract_generate_for_jobcard(int $jobcardId): array
{
    return m360_intake_contract_generate_for_jobcard($jobcardId);
}

function m360_contract_record_event($conn, int $contractId, string $eventName, ?string $note = null, ?int $userId = null): void
{
    m360_intake_contract_record_event($conn, $contractId, $eventName, $note, $userId);
}

/** @return array{ok:bool,message:string} */
function m360_intake_contract_apply_manager_override($conn, int $contractId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل تأیید مدیریتی الزامی است.'];
    }
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, manager_override = 1, manager_override_reason = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_OVERRIDDEN, $reason, $contractId]
    );
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_MANAGER_OVERRIDE', $reason, $userId);
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    $jobcardId = (int)($row['jobcard_id'] ?? 0);
    if ($jobcardId > 0 && customer_core_column_exists($conn, 'erp_jobcards', 'contract_status')) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET contract_status = ?, intake_contract_id = ?, contract_signed_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
            ['OVERRIDDEN', $contractId, $jobcardId]
        );
    }
    return ['ok' => true, 'message' => 'تأیید مدیریتی ثبت شد.'];
}

function m360_intake_contract_snapshot_from_row(array $row): array
{
    $data = m360_intake_contract_build_snapshot(customer_core_db(), (int)($row['jobcard_id'] ?? 0), (int)($row['online_request_id'] ?? 0) ?: null);
    $json = trim((string)($row['contract_data_json'] ?? ''));
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $data = array_merge($data, $decoded);
        }
    }
    $data['contract_hash'] = (string)($row['contract_body_hash'] ?? $data['contract_hash']);
    return $data;
}
