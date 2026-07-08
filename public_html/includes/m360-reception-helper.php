<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Reception dashboard helper for online requests.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-customer-v2-write-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-vehicle-v2-write-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-v2-write-helper.php';

const M360_RECEPTION_CSRF_PURPOSE = 'online_request_reception';

function m360_reception_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Stable session-scope CSRF token for reception intake (reused across page renders/tabs). */
function m360_reception_csrf_token_value(): string
{
    if (!function_exists('erp_csrf_get_or_create_token')) {
        return '';
    }

    return erp_csrf_get_or_create_token(M360_RECEPTION_CSRF_PURPOSE);
}

/** Single CSRF hidden input for all reception action forms on one page. */
function m360_reception_csrf_input_html(): string
{
    $token = m360_reception_csrf_token_value();
    if ($token === '') {
        return '';
    }

    return '<input type="hidden" name="erp_csrf_token" value="' .
        m360_reception_h($token) . '">';
}

function m360_reception_csrf_is_valid(?string $token): bool
{
    if (!function_exists('erp_csrf_validate_token')) {
        return false;
    }

    return erp_csrf_validate_token(M360_RECEPTION_CSRF_PURPOSE, trim((string)($token ?? '')));
}

function m360_reception_intake_recover_step_key(string $activeStep): string
{
    $activeStep = trim($activeStep);
    $allowed = ['otp', 'vehicle', 'condition', 'service', 'referral', 'photos', 'documents', 'signature', 'locked_summary'];

    return in_array($activeStep, $allowed, true) ? $activeStep : 'documents';
}

function m360_reception_intake_step_hash(string $stepKey): string
{
    return match (m360_reception_intake_recover_step_key($stepKey)) {
        'otp' => 'step-otp',
        'vehicle' => 'step-vehicle',
        'condition' => 'step-condition',
        'service' => 'step-service',
        'referral' => 'step-referral',
        'photos' => 'step-photos',
        'documents' => 'step-documents',
        'signature' => 'step-signature',
        'locked_summary' => 'step-locked',
        default => 'step-documents',
    };
}

/** @return array{title:string,text:string,button:string,detail_href:string} */
function m360_reception_action_error_content(string $type, int $requestId, string $context = 'default', string $activeStep = 'documents'): array
{
    if ($type === 'csrf' && $context === 'intake') {
        if ($requestId > 0) {
            $step = m360_reception_intake_recover_step_key($activeStep);
            $hash = m360_reception_intake_step_hash($step);
            $detailHref = 'erp-reception-intake-file.php?online_request_id=' . $requestId
                . '&active_step=' . rawurlencode($step)
                . '#' . rawurlencode($hash);
            $button = $step === 'documents' ? 'بازگشت به مرحله مستندات' : 'بازگشت به پرونده پذیرش';
        } else {
            $detailHref = 'erp-reception-workbench.php';
            $button = 'بازگشت به میز کار پذیرش';
        }

        return [
            'title' => 'اعتبار امنیتی فرم منقضی شده است',
            'text' => 'اعتبار امنیتی فرم منقضی شده است؛ لطفاً صفحه را تازه‌سازی و دوباره تلاش کنید.',
            'button' => $button,
            'detail_href' => $detailHref,
        ];
    }

    $detailHref = $requestId > 0
        ? 'erp-reception-online-request-detail.php?request_id=' . $requestId
        : 'erp-reception-online-requests.php';

    if ($type === 'convert_prereq') {
        return [
            'title' => 'تبدیل به کارت کار نیازمند تکمیل پیش‌نیاز است',
            'text' => 'برای تبدیل این درخواست به کارت کار، ابتدا باید وضعیت تأیید مشتری و اطلاعات پذیرش تکمیل شود.',
            'button' => 'بازگشت به جزئیات درخواست',
            'detail_href' => $detailHref,
        ];
    }

    return [
        'title' => 'اعتبار امنیتی درخواست نامعتبر یا منقضی شده است',
        'text' => 'برای ادامه، لطفاً به صفحه جزئیات درخواست بازگردید و عملیات را دوباره انجام دهید.',
        'button' => $requestId > 0 ? 'بازگشت به جزئیات درخواست' : 'بازگشت به فهرست درخواست‌ها',
        'detail_href' => $detailHref,
    ];
}

function m360_reception_render_action_error_page(string $type, int $requestId = 0, string $context = 'default', string $activeStep = 'documents'): void
{
    $content = m360_reception_action_error_content($type, $requestId, $context, $activeStep);
    http_response_code($type === 'csrf' ? 403 : 200);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . m360_reception_h($content['title']) . '</title>';
    echo '<link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">';
    echo '<style>.p1-gate-wrap{max-width:480px;margin:2rem auto;padding:0 1rem;}';
    echo '.p1-gate-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.5rem;text-align:center;}';
    echo '.p1-gate-card h1{margin:0 0 .75rem;font-size:1.1rem;color:#991b1b;}';
    echo '.p1-gate-card p{margin:0 0 1.25rem;color:#52525b;line-height:1.75;font-size:.95rem;}';
    echo '.p1-gate-btn{display:inline-block;padding:.65rem 1.25rem;background:#166534;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;}</style>';
    echo '</head><body style="background:#f8fafc;margin:0;color:#18181b;">';
    echo '<div class="p1-gate-wrap"><div class="p1-gate-card">';
    echo '<h1>' . m360_reception_h($content['title']) . '</h1>';
    echo '<p>' . m360_reception_h($content['text']) . '</p>';
    echo '<a class="p1-gate-btn" href="' . m360_reception_h($content['detail_href']) . '">' . m360_reception_h($content['button']) . '</a>';
    echo '</div></div></body></html>';
}

function m360_reception_is_convert_prerequisite_message(string $message): bool
{
    $message = trim($message);
    $needles = [
        'بدون تأیید OTP',
        'OTP',
        'پلاک خودرو مشخص نیست',
        'مشتری یافت نشد',
        'خودرو یافت نشد',
        'ایجاد مشتری ناموفق',
        'ایجاد خودرو ناموفق',
        'رابطه مشتری-خودرو',
    ];
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($message, $needle)) {
            return true;
        }
    }

    return false;
}

/** @return array<string, string> filter code => Persian label */
function m360_reception_list_filter_labels(): array
{
    return [
        'ALL' => 'همه',
        M360_ONLINE_REQ_STATUS_NEW => 'جدید',
        M360_ONLINE_REQ_STATUS_PENDING => 'در انتظار بررسی',
        M360_ONLINE_REQ_STATUS_UNDER_REVIEW => 'در حال بررسی',
        M360_ONLINE_REQ_STATUS_ACCEPTED => 'پذیرفته‌شده',
        M360_ONLINE_REQ_STATUS_CONVERTED => 'تبدیل به کارت کار',
        M360_ONLINE_REQ_STATUS_REJECTED => 'رد شده',
    ];
}

/** @return array<string, int> */
function m360_reception_status_counts($conn): array
{
    if (!is_resource($conn)) {
        return [];
    }

    $where = '1=1';
    if (m360_online_req_has_column($conn, 'otp_verified')) {
        $where .= ' AND ISNULL(r.otp_verified, 0) = 1';
    }

    $sql = 'SELECT r.request_status, COUNT(*) AS cnt
            FROM dbo.' . m360_online_req_table() . ' r
            WHERE ' . $where . '
            GROUP BY r.request_status';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [])) {
        return [];
    }

    $byDb = [];
    $total = 0;
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $status = strtoupper(trim((string)($row['request_status'] ?? $row['REQUEST_STATUS'] ?? '')));
        if ($status === '') {
            continue;
        }
        $cnt = (int)($row['cnt'] ?? $row['CNT'] ?? 0);
        $byDb[$status] = ($byDb[$status] ?? 0) + $cnt;
        $total += $cnt;
    }

    $counts = ['ALL' => $total];
    foreach (array_keys(m360_reception_list_filter_labels()) as $code) {
        if ($code === 'ALL') {
            continue;
        }
        if ($code === M360_ONLINE_REQ_STATUS_NEW) {
            $counts[$code] = (int)(($byDb[M360_ONLINE_REQ_STATUS_NEW] ?? 0) + ($byDb[M360_ONLINE_REQ_STATUS_PENDING] ?? 0));
        } else {
            $counts[$code] = (int)($byDb[$code] ?? 0);
        }
    }

    return $counts;
}

function m360_reception_require_staff(): void
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();
    if ($userId === null || $userId <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_reception_default_company_id($conn): int
{
    if (!is_resource($conn)) {
        return 1;
    }
    $sql = "SELECT TOP 1 company_id FROM dbo.erp_companies WHERE company_code = N'MOGHAREH_MAIN' ORDER BY company_id";
    $row = @odbc_exec($conn, $sql);
    if ($row !== false && ($data = odbc_fetch_array($row))) {
        return (int)($data['company_id'] ?? 1);
    }
    return 1;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_reception_list_requests($conn, ?string $statusFilter = null, int $limit = 100): array
{
    if (!is_resource($conn)) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $params = [];
    $where = '1=1';

    if (m360_online_req_has_column($conn, 'otp_verified')) {
        $where .= ' AND ISNULL(r.otp_verified, 0) = 1';
    }

    if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'ALL') {
        $canonical = strtoupper(trim($statusFilter));
        if ($canonical === M360_ONLINE_REQ_STATUS_NEW) {
            $where .= " AND r.request_status IN (N'NEW', N'PENDING')";
        } else {
            $where .= ' AND r.request_status = ?';
            $params[] = $canonical;
        }
    }

    $fetchLimit = min(500, max($limit, $limit * 3));
    $sql = 'SELECT TOP ' . $fetchLimit . '
            r.online_request_id,
            r.company_id,
            r.customer_name,
            r.mobile,
            r.vehicle_plate,
            r.service_note,
            r.request_status,
            r.source_channel,
            r.request_type,
            r.visit_date,
            r.customer_id,
            r.vehicle_id,
            r.converted_jobcard_id,
            r.created_at,
            r.request_payload_json,
            r.otp_verified,
            c.full_name AS erp_customer_name,
            v.brand AS vehicle_brand,
            v.model AS vehicle_model
        FROM dbo.' . m360_online_req_table() . ' r
        LEFT JOIN dbo.erp_customers c ON c.customer_id = r.customer_id
        LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
        WHERE ' . $where . '
        ORDER BY r.online_request_id DESC';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }

    $hasOtpColumn = m360_online_req_list_has_otp_verified_column($conn);
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower((string)$key)] = $value === null ? '' : (string)$value;
        }
        if ($hasOtpColumn) {
            if (!m360_online_req_payload_otp_verified_for_list($conn, $normalized)) {
                continue;
            }
        } else {
            $normalized = m360_online_req_hydrate_row_payload_json($conn, $normalized);
            if (!m360_online_req_payload_otp_verified($normalized)) {
                continue;
            }
        }
        $rows[] = $normalized;
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_reception_update_status($conn, int $requestId, string $newStatus, string $eventType, ?string $note = null): array
{
    if (!is_resource($conn) || $requestId < 1) {
        return ['ok' => false, 'message' => 'درخواست معتبر نیست.'];
    }

    $row = m360_online_req_fetch_by_id($conn, $requestId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }

    if (m360_online_req_is_converted($row)) {
        return ['ok' => false, 'message' => 'این درخواست قبلاً به کارت کار تبدیل شده است.'];
    }

    $previous = (string)($row['request_status'] ?? '');
    $newStatus = strtoupper(trim($newStatus));

    $sets = ['request_status = ?'];
    $params = [$newStatus];
    if (m360_online_req_has_column($conn, 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }

    $sql = 'UPDATE dbo.' . m360_online_req_table() . ' SET ' . implode(', ', $sets) . ' WHERE online_request_id = ?';
    $params[] = $requestId;

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return ['ok' => false, 'message' => 'به‌روزرسانی وضعیت ناموفق بود.'];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    m360_online_req_write_history($conn, $requestId, $eventType, $previous, $newStatus, $note, $userId);

    return ['ok' => true, 'message' => 'وضعیت درخواست به‌روزرسانی شد.'];
}

function m360_reception_ensure_customer(array $requestRow): array
{
    $customerId = (int)($requestRow['customer_id'] ?? 0);
    if ($customerId > 0) {
        return ['ok' => true, 'customer_id' => $customerId, 'created' => false, 'error' => ''];
    }

    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    $mobile = trim((string)($requestRow['mobile'] ?? ''));
    $name = trim((string)($requestRow['customer_name'] ?? ''));

    $write = moghare360_customer_v2_write([
        'customer_name' => $name !== '' ? $name : 'مشتری آنلاین',
        'mobile' => $mobile,
        'national_id' => trim((string)($payload['national_id'] ?? '')),
        'customer_channel' => 'PUBLIC_SITE',
        'customer_class' => 'ONLINE',
        'notes' => 'Created from online request #' . (int)($requestRow['online_request_id'] ?? 0),
    ]);

    if (!$write['ok'] || (int)($write['customer_id'] ?? 0) < 1) {
        return ['ok' => false, 'customer_id' => null, 'created' => false, 'error' => (string)($write['error'] ?? 'ایجاد مشتری ناموفق بود.')];
    }

    return ['ok' => true, 'customer_id' => (int)$write['customer_id'], 'created' => true, 'error' => ''];
}

function m360_reception_build_vehicle_clean(array $requestRow): array
{
    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    $plateDisplay = trim((string)($requestRow['vehicle_plate'] ?? $payload['plate_display'] ?? ''));

    $plate = null;
    if (isset($payload['plate_parts']) && is_array($payload['plate_parts'])) {
        $pp = $payload['plate_parts'];
        $plate = [
            'province' => (string)($pp['region_2'] ?? $pp['series'] ?? '00'),
            'letter' => (string)($pp['letter'] ?? 'الف'),
            'number' => (string)($pp['middle_3'] ?? $pp['number'] ?? '000'),
            'series' => (string)($pp['left_2'] ?? $pp['province'] ?? '00'),
        ];
    } elseif (preg_match('/^(\d{2})([^\d]+)(\d{3})-?(\d{2})$/u', $plateDisplay, $m) === 1) {
        $plate = [
            'series' => $m[1],
            'letter' => $m[2],
            'number' => $m[3],
            'province' => $m[4],
        ];
    }

    return [
        'plate' => $plate,
        'plate_display' => $plateDisplay,
        'vin' => trim((string)($payload['vin'] ?? '')),
        'brand_id' => '0',
        'model_id' => '0',
        'vehicle_class' => trim((string)($payload['vehicle_class'] ?? '')),
        'vehicle_notes' => 'Online request #' . (int)($requestRow['online_request_id'] ?? 0),
        'brand_text' => trim((string)($payload['brand'] ?? $payload['vehicle_brand'] ?? '')),
        'model_text' => trim((string)($payload['model'] ?? '')),
    ];
}

function m360_reception_ensure_vehicle($conn, array $requestRow, int $customerId): array
{
    $vehicleId = (int)($requestRow['vehicle_id'] ?? 0);
    $plate = trim((string)($requestRow['vehicle_plate'] ?? ''));
    if ($vehicleId < 1) {
        $vehicleId = (int)(m360_online_req_resolve_vehicle_id($conn, $customerId, $plate) ?? 0);
    }
    if ($vehicleId > 0) {
        return ['ok' => true, 'vehicle_id' => $vehicleId, 'created' => false, 'error' => ''];
    }

    $clean = m360_reception_build_vehicle_clean($requestRow);
    if (is_array($clean['plate'])) {
        $write = moghare360_vehicle_v2_write($clean);
        if ($write['ok'] && (int)($write['vehicle_id'] ?? 0) > 0) {
            return ['ok' => true, 'vehicle_id' => (int)$write['vehicle_id'], 'created' => true, 'error' => ''];
        }
    }

    if ($plate === '') {
        return ['ok' => false, 'vehicle_id' => null, 'created' => false, 'error' => 'پلاک خودرو مشخص نیست.'];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $vehicleCode = 'P1V-' . date('Ymd-His') . '-' . random_int(1000, 9999);
    $brand = $clean['brand_text'] !== '' ? $clean['brand_text'] : 'ONLINE';
    $model = $clean['model_text'] !== '' ? $clean['model_text'] : 'REQUEST';

    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_vehicles (vehicle_code, plate_number, brand, model, lifecycle_state, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$vehicleCode, m360_online_req_normalize_plate($plate), $brand, $model, 'ACTIVE', $userId]
    );

    if ($insertOk === false) {
        return ['ok' => false, 'vehicle_id' => null, 'created' => false, 'error' => 'ایجاد خودرو ناموفق بود.'];
    }

    $newVehicleId = (int)(customer_core_scope_identity($conn) ?? 0);
    if ($newVehicleId < 1) {
        $newVehicleId = (int)(customer_core_scalar(
            $conn,
            'SELECT vehicle_id FROM dbo.erp_vehicles WHERE vehicle_code = ?',
            [$vehicleCode]
        ) ?? 0);
    }

    if ($newVehicleId < 1) {
        return ['ok' => false, 'vehicle_id' => null, 'created' => false, 'error' => 'شناسه خودرو دریافت نشد.'];
    }

    return ['ok' => true, 'vehicle_id' => $newVehicleId, 'created' => true, 'error' => ''];
}

function m360_reception_ensure_relation($conn, int $customerId, int $vehicleId): array
{
    if (!is_resource($conn) || $customerId < 1 || $vehicleId < 1) {
        return ['ok' => false, 'relation_id' => null, 'error' => 'شناسه مشتری یا خودرو نامعتبر است.'];
    }

    $existing = customer_core_scalar(
        $conn,
        'SELECT TOP 1 relation_id FROM dbo.erp_customer_vehicle_relations
         WHERE customer_id = ? AND vehicle_id = ? AND lifecycle_state = ?',
        [$customerId, $vehicleId, 'ACTIVE']
    );

    if ($existing !== null && (int)$existing > 0) {
        return ['ok' => true, 'relation_id' => (int)$existing, 'created' => false, 'error' => ''];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;

    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_customer_vehicle_relations
            (customer_id, vehicle_id, relation_type, is_primary_owner, lifecycle_state, created_by_user_id)
         VALUES (?, ?, ?, 1, ?, ?)',
        [$customerId, $vehicleId, 'OWNER', 'ACTIVE', $userId]
    );

    if ($insertOk === false) {
        return ['ok' => false, 'relation_id' => null, 'created' => false, 'error' => 'ایجاد رابطه مشتری-خودرو ناموفق بود.'];
    }

    $relationId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 relation_id FROM dbo.erp_customer_vehicle_relations
         WHERE customer_id = ? AND vehicle_id = ? ORDER BY relation_id DESC',
        [$customerId, $vehicleId]
    ) ?? 0);

    return ['ok' => $relationId > 0, 'relation_id' => $relationId > 0 ? $relationId : null, 'created' => true, 'error' => ''];
}

function m360_reception_bind_request_entities($conn, int $requestId, int $customerId, int $vehicleId): void
{
    if (!is_resource($conn) || $requestId < 1) {
        return;
    }

    $sets = [];
    $params = [];
    if (m360_online_req_has_column($conn, 'customer_id')) {
        $sets[] = 'customer_id = ?';
        $params[] = $customerId;
    }
    if (m360_online_req_has_column($conn, 'vehicle_id')) {
        $sets[] = 'vehicle_id = ?';
        $params[] = $vehicleId;
    }
    if ($sets === []) {
        return;
    }
    if (m360_online_req_has_column($conn, 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $requestId;
    $sql = 'UPDATE dbo.' . m360_online_req_table() . ' SET ' . implode(', ', $sets) . ' WHERE online_request_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt !== false) {
        @odbc_execute($stmt, $params);
    }
}

/**
 * @return array{ok:bool,message:string,jobcard_id:?int,jobcard_number:?string,already_converted:bool}
 */
function m360_reception_convert_to_jobcard(int $requestId): array
{
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.', 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }

    $row = m360_online_req_fetch_by_id($conn, $requestId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.', 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }

    $existingJobcard = m360_online_req_converted_jobcard_id($row);
    if ($existingJobcard > 0 || m360_online_req_is_converted($row)) {
        return [
            'ok' => true,
            'message' => 'این درخواست قبلاً به کارت کار تبدیل شده است.',
            'jobcard_id' => $existingJobcard > 0 ? $existingJobcard : null,
            'jobcard_number' => null,
            'already_converted' => true,
        ];
    }

    if (!m360_online_req_payload_otp_verified($row)) {
        return ['ok' => false, 'message' => 'درخواست بدون تأیید OTP قابل تبدیل نیست.', 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }

    $status = strtoupper(trim((string)($row['request_status'] ?? '')));
    if ($status === M360_ONLINE_REQ_STATUS_REJECTED) {
        return ['ok' => false, 'message' => 'درخواست رد شده قابل تبدیل نیست.', 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }

    $customer = m360_reception_ensure_customer($row);
    if (!$customer['ok'] || (int)($customer['customer_id'] ?? 0) < 1) {
        return ['ok' => false, 'message' => (string)($customer['error'] ?? 'مشتری یافت نشد.'), 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }
    $customerId = (int)$customer['customer_id'];

    $vehicle = m360_reception_ensure_vehicle($conn, $row, $customerId);
    if (!$vehicle['ok'] || (int)($vehicle['vehicle_id'] ?? 0) < 1) {
        return ['ok' => false, 'message' => (string)($vehicle['error'] ?? 'خودرو یافت نشد.'), 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }
    $vehicleId = (int)$vehicle['vehicle_id'];

    $relation = m360_reception_ensure_relation($conn, $customerId, $vehicleId);
    if (!$relation['ok']) {
        return ['ok' => false, 'message' => (string)($relation['error'] ?? 'رابطه مشتری-خودرو تأیید نشد.'), 'jobcard_id' => null, 'jobcard_number' => null, 'already_converted' => false];
    }

    m360_reception_bind_request_entities($conn, $requestId, $customerId, $vehicleId);

    $payload = m360_online_req_parse_payload($row['request_payload_json'] ?? null);
    $visitDate = trim((string)($row['visit_date'] ?? $payload['visit_date'] ?? ''));
    $complaint = trim((string)($row['service_note'] ?? ''));
    if ($complaint === '') {
        $complaint = 'درخواست آنلاین از سایت عمومی';
    }

    $jobcardWrite = moghare360_jobcard_v2_write([
        'customer_id' => (string)$customerId,
        'vehicle_id' => (string)$vehicleId,
        'reception_date' => $visitDate !== '' ? $visitDate : date('Y-m-d'),
        'odometer' => trim((string)($payload['odometer_km'] ?? '')),
        'complaint_text' => $complaint,
        'jobcard_type' => 'online_request',
        'service_category' => trim((string)($row['request_type'] ?? 'general')),
    ], 'P1 online request #' . $requestId);

    if (!$jobcardWrite['ok'] || (int)($jobcardWrite['jobcard_id'] ?? 0) < 1) {
        return [
            'ok' => false,
            'message' => (string)($jobcardWrite['error'] ?? 'ایجاد کارت کار ناموفق بود.'),
            'jobcard_id' => null,
            'jobcard_number' => null,
            'already_converted' => false,
        ];
    }

    $jobcardId = (int)$jobcardWrite['jobcard_id'];
    $previousStatus = (string)($row['request_status'] ?? '');
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;

    $sets = ['request_status = ?'];
    $params = [M360_ONLINE_REQ_STATUS_CONVERTED];
    if (m360_online_req_has_column($conn, 'converted_jobcard_id')) {
        $sets[] = 'converted_jobcard_id = ?';
        $params[] = $jobcardId;
    }
    if (m360_online_req_has_column($conn, 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $requestId;
    $updateSql = 'UPDATE dbo.' . m360_online_req_table() . ' SET ' . implode(', ', $sets) . ' WHERE online_request_id = ?';
    $uStmt = @odbc_prepare($conn, $updateSql);
    if ($uStmt !== false) {
        @odbc_execute($uStmt, $params);
    }

    m360_online_req_write_history(
        $conn,
        $requestId,
        M360_ONLINE_REQ_HISTORY_CONVERTED,
        $previousStatus,
        M360_ONLINE_REQ_STATUS_CONVERTED,
        'JobCard #' . $jobcardId,
        $userId
    );

    return [
        'ok' => true,
        'message' => 'درخواست با موفقیت به کارت کار تبدیل شد.',
        'jobcard_id' => $jobcardId,
        'jobcard_number' => (string)($jobcardWrite['jobcard_number'] ?? ''),
        'already_converted' => false,
    ];
}

/** @return list<array<string, mixed>> */
function m360_reception_fetch_history($conn, int $requestId): array
{
    if (!m360_online_req_history_table_exists($conn) || $requestId < 1) {
        return [];
    }

    $sql = 'SELECT history_id, event_type, previous_status, new_status, event_note, changed_by_user_id, created_at
            FROM dbo.' . m360_online_req_history_table() . '
            WHERE online_request_id = ?
            ORDER BY history_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$requestId])) {
        return [];
    }

    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower((string)$key)] = $value === null ? '' : (string)$value;
        }
        $rows[] = $normalized;
    }

    return $rows;
}
