<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online customer request intake helper.
 * Shared by public API and reception workflow.
 */

const M360_ONLINE_REQ_STATUS_NEW = 'NEW';
const M360_ONLINE_REQ_STATUS_PENDING = 'PENDING';
const M360_ONLINE_REQ_STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
const M360_ONLINE_REQ_STATUS_ACCEPTED = 'ACCEPTED';
const M360_ONLINE_REQ_STATUS_CONVERTED = 'CONVERTED_TO_JOBCARD';
const M360_ONLINE_REQ_STATUS_REJECTED = 'REJECTED';

const M360_ONLINE_REQ_SOURCE_PUBLIC = 'PUBLIC_SITE';

const M360_ONLINE_REQ_HISTORY_CREATED = 'ONLINE_REQUEST_CREATED';
const M360_ONLINE_REQ_HISTORY_UNDER_REVIEW = 'ONLINE_REQUEST_UNDER_REVIEW';
const M360_ONLINE_REQ_HISTORY_ACCEPTED = 'ONLINE_REQUEST_ACCEPTED';
const M360_ONLINE_REQ_HISTORY_CONVERTED = 'ONLINE_REQUEST_CONVERTED_TO_JOBCARD';
const M360_ONLINE_REQ_HISTORY_REJECTED = 'ONLINE_REQUEST_REJECTED';

/** @var array<string, string> */
const M360_ONLINE_REQ_STATUS_LABELS_FA = [
    M360_ONLINE_REQ_STATUS_NEW => 'جدید',
    M360_ONLINE_REQ_STATUS_PENDING => 'در انتظار بررسی',
    M360_ONLINE_REQ_STATUS_UNDER_REVIEW => 'در حال بررسی',
    M360_ONLINE_REQ_STATUS_ACCEPTED => 'پذیرفته‌شده',
    M360_ONLINE_REQ_STATUS_CONVERTED => 'تبدیل به کارت کار',
    M360_ONLINE_REQ_STATUS_REJECTED => 'رد شده',
];

function m360_online_req_table(): string
{
    return 'erp_customer_online_requests';
}

function m360_online_req_history_table(): string
{
    return 'erp_customer_online_request_history';
}

function m360_online_req_has_column($conn, string $column): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [m360_online_req_table(), $column])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

function m360_online_req_initial_status(): string
{
    return M360_ONLINE_REQ_STATUS_NEW;
}

/** @return list<string> */
function m360_online_req_filter_statuses(): array
{
    return [
        M360_ONLINE_REQ_STATUS_NEW,
        M360_ONLINE_REQ_STATUS_PENDING,
        M360_ONLINE_REQ_STATUS_UNDER_REVIEW,
        M360_ONLINE_REQ_STATUS_ACCEPTED,
        M360_ONLINE_REQ_STATUS_CONVERTED,
        M360_ONLINE_REQ_STATUS_REJECTED,
    ];
}

function m360_online_req_canonical_status(string $status): string
{
    $status = strtoupper(trim($status));
    if ($status === M360_ONLINE_REQ_STATUS_PENDING) {
        return M360_ONLINE_REQ_STATUS_NEW;
    }
    return $status;
}

function m360_online_req_status_label_fa(string $status): string
{
    $status = strtoupper(trim($status));
    return M360_ONLINE_REQ_STATUS_LABELS_FA[$status]
        ?? M360_ONLINE_REQ_STATUS_LABELS_FA[m360_online_req_canonical_status($status)]
        ?? $status;
}

function m360_online_req_normalize_plate(string $plate): string
{
    $plate = preg_replace('/\s+/u', ' ', trim($plate)) ?? trim($plate);
    return $plate;
}

/** @return array<string, mixed> */
function m360_online_req_parse_payload(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function m360_online_req_resolve_customer_id($conn, int $companyId, string $mobile): ?int
{
    if (!is_resource($conn) || $mobile === '') {
        return null;
    }

    if (m360_online_req_has_column($conn, 'customer_id') === false) {
        // column probe on customers table
    }

    $hasCompany = false;
    $custCols = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='erp_customers' AND COLUMN_NAME='company_id'";
    $cStmt = @odbc_exec($conn, $custCols);
    if ($cStmt !== false && ($cRow = odbc_fetch_array($cStmt))) {
        $hasCompany = (int)($cRow['c'] ?? 0) > 0;
    }

    $sql = $hasCompany
        ? 'SELECT TOP 1 customer_id FROM dbo.erp_customers WHERE company_id = ? AND primary_mobile = ? ORDER BY customer_id DESC'
        : 'SELECT TOP 1 customer_id FROM dbo.erp_customers WHERE primary_mobile = ? ORDER BY customer_id DESC';
    $params = $hasCompany ? [$companyId, $mobile] : [$mobile];
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt !== false && @odbc_execute($stmt, $params)) {
        $row = odbc_fetch_array($stmt);
        if ($row !== false && (int)($row['customer_id'] ?? 0) > 0) {
            return (int)$row['customer_id'];
        }
    }

    $phoneSql = 'SELECT TOP 1 p.customer_id
                 FROM dbo.erp_customer_phones p
                 INNER JOIN dbo.erp_customers c ON c.customer_id = p.customer_id
                 WHERE p.phone_number = ?'
        . ($hasCompany ? ' AND c.company_id = ?' : '')
        . ' ORDER BY p.is_primary DESC, p.phone_id DESC';
    $phoneParams = $hasCompany ? [$mobile, $companyId] : [$mobile];
    $pStmt = @odbc_prepare($conn, $phoneSql);
    if ($pStmt !== false && @odbc_execute($pStmt, $phoneParams)) {
        $pRow = odbc_fetch_array($pStmt);
        if ($pRow !== false && (int)($pRow['customer_id'] ?? 0) > 0) {
            return (int)$pRow['customer_id'];
        }
    }

    return null;
}

function m360_online_req_resolve_vehicle_id($conn, ?int $customerId, string $plate): ?int
{
    if (!is_resource($conn) || $plate === '') {
        return null;
    }

    $normalized = m360_online_req_normalize_plate($plate);

    if ($customerId !== null && $customerId > 0) {
        $sql = 'SELECT TOP 1 v.vehicle_id
                FROM dbo.erp_customer_vehicle_relations r
                INNER JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
                WHERE r.customer_id = ? AND v.plate_number = ?
                ORDER BY r.is_primary_owner DESC, r.relation_id DESC';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$customerId, $normalized])) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false && (int)($row['vehicle_id'] ?? 0) > 0) {
                return (int)$row['vehicle_id'];
            }
        }
    }

    $sql = 'SELECT TOP 1 vehicle_id FROM dbo.erp_vehicles WHERE plate_number = ? ORDER BY vehicle_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt !== false && @odbc_execute($stmt, [$normalized])) {
        $row = odbc_fetch_array($stmt);
        if ($row !== false && (int)($row['vehicle_id'] ?? 0) > 0) {
            return (int)$row['vehicle_id'];
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $fields
 * @return array{ok:bool,online_request_id:int,status:string,profile_required:bool,customer_id:?int,vehicle_id:?int}
 */
function m360_online_req_insert($conn, int $companyId, array $fields): array
{
    if (!is_resource($conn)) {
        return ['ok' => false, 'online_request_id' => 0, 'status' => '', 'profile_required' => true, 'customer_id' => null, 'vehicle_id' => null];
    }

    $name = trim((string)($fields['customer_name'] ?? ''));
    $mobile = trim((string)($fields['mobile'] ?? ''));
    $plate = m360_online_req_normalize_plate((string)($fields['vehicle_plate'] ?? ''));
    $note = trim((string)($fields['service_note'] ?? ''));
    $requestType = trim((string)($fields['request_type'] ?? ''));
    $sourceChannel = trim((string)($fields['source_channel'] ?? M360_ONLINE_REQ_SOURCE_PUBLIC));
    $payloadJson = (string)($fields['request_payload_json'] ?? '{}');
    $visitDate = trim((string)($fields['visit_date'] ?? ''));
    $status = m360_online_req_initial_status();

    $customerId = m360_online_req_resolve_customer_id($conn, $companyId, $mobile);
    $vehicleId = m360_online_req_resolve_vehicle_id($conn, $customerId, $plate);
    $profileRequired = $customerId === null;

    $payload = m360_online_req_parse_payload($payloadJson);
    $payload['otp_verified'] = 1;
    $payload['source'] = M360_ONLINE_REQ_SOURCE_PUBLIC;
    if ($customerId !== null) {
        $payload['customer_id'] = $customerId;
    }
    if ($vehicleId !== null) {
        $payload['vehicle_id'] = $vehicleId;
    }
    $payload['profile_required'] = $profileRequired;
    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encodedPayload === false) {
        $encodedPayload = '{}';
    }

    $hasPayload = m360_online_req_has_column($conn, 'request_payload_json');
    $hasReqType = m360_online_req_has_column($conn, 'request_type');
    $hasCustomerId = m360_online_req_has_column($conn, 'customer_id');
    $hasVehicleId = m360_online_req_has_column($conn, 'vehicle_id');
    $hasVisitDate = m360_online_req_has_column($conn, 'visit_date');
    $hasOtpVerified = m360_online_req_has_column($conn, 'otp_verified');

    $columns = ['company_id', 'customer_name', 'mobile', 'vehicle_plate', 'service_note', 'request_status', 'source_channel'];
    $values = [$companyId, $name, $mobile, $plate, $note, $status, $sourceChannel];

    if ($hasReqType) {
        $columns[] = 'request_type';
        $values[] = $requestType;
    }
    if ($hasPayload) {
        $columns[] = 'request_payload_json';
        $values[] = $encodedPayload;
    }
    if ($hasCustomerId && $customerId !== null) {
        $columns[] = 'customer_id';
        $values[] = $customerId;
    }
    if ($hasVehicleId && $vehicleId !== null) {
        $columns[] = 'vehicle_id';
        $values[] = $vehicleId;
    }
    if ($hasVisitDate && $visitDate !== '') {
        $columns[] = 'visit_date';
        $values[] = $visitDate;
    }
    if ($hasOtpVerified) {
        $columns[] = 'otp_verified';
        $values[] = 1;
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $columnList = implode(', ', $columns);
    $sql = 'INSERT INTO dbo.' . m360_online_req_table() . ' (' . $columnList . ') VALUES (' . $placeholders . ')';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $values)) {
        return ['ok' => false, 'online_request_id' => 0, 'status' => '', 'profile_required' => $profileRequired, 'customer_id' => $customerId, 'vehicle_id' => $vehicleId];
    }

    $newId = 0;
    $idRes = @odbc_exec($conn, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id');
    if ($idRes !== false && ($row = odbc_fetch_array($idRes))) {
        $newId = (int)($row['new_id'] ?? 0);
    }

    m360_online_req_write_history($conn, $newId, M360_ONLINE_REQ_HISTORY_CREATED, null, $status, 'Public site intake', null);

    return [
        'ok' => $newId > 0,
        'online_request_id' => $newId,
        'status' => $status,
        'profile_required' => $profileRequired,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
    ];
}

function m360_online_req_history_table_exists($conn): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [m360_online_req_history_table()])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

function m360_online_req_write_history(
    $conn,
    int $onlineRequestId,
    string $eventType,
    ?string $previousStatus,
    ?string $newStatus,
    ?string $note,
    ?int $userId
): bool {
    if (!m360_online_req_history_table_exists($conn) || $onlineRequestId < 1) {
        return false;
    }

    $sql = 'INSERT INTO dbo.' . m360_online_req_history_table() . '
        (online_request_id, event_type, previous_status, new_status, event_note, changed_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }

    return @odbc_execute($stmt, [
        $onlineRequestId,
        $eventType,
        $previousStatus,
        $newStatus,
        $note,
        $userId,
    ]);
}

/** @return array<string, mixed>|null */
function m360_online_req_fetch_by_id($conn, int $requestId): ?array
{
    if (!is_resource($conn) || $requestId < 1) {
        return null;
    }

    $sql = 'SELECT TOP 1 * FROM dbo.' . m360_online_req_table() . ' WHERE online_request_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$requestId])) {
        return null;
    }

    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }

    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[strtolower((string)$key)] = $value === null ? '' : (string)$value;
    }

    return $normalized;
}

function m360_online_req_payload_otp_verified(array $requestRow): bool
{
    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    if (isset($payload['otp_verified']) && (int)$payload['otp_verified'] === 1) {
        return true;
    }
    if (isset($requestRow['otp_verified']) && (string)$requestRow['otp_verified'] === '1') {
        return true;
    }
    return false;
}

function m360_online_req_is_converted(array $requestRow): bool
{
    $status = strtoupper(trim((string)($requestRow['request_status'] ?? '')));
    if ($status === M360_ONLINE_REQ_STATUS_CONVERTED) {
        return true;
    }
    $jobcardId = (int)($requestRow['converted_jobcard_id'] ?? 0);
    return $jobcardId > 0;
}

function m360_online_req_converted_jobcard_id(array $requestRow): int
{
    return (int)($requestRow['converted_jobcard_id'] ?? 0);
}
