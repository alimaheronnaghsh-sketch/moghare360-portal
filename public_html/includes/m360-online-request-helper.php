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
const M360_ONLINE_REQ_SOURCE_STAFF_WALKIN = 'STAFF_ASSISTED_WALKIN';

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
    static $cached = [];
    if (!is_resource($conn)) {
        return false;
    }
    $cacheKey = m360_online_req_table() . ':' . $column;
    if (array_key_exists($cacheKey, $cached)) {
        return $cached[$cacheKey];
    }
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [m360_online_req_table(), $column])) {
        if (is_resource($stmt)) {
            @odbc_free_result($stmt);
        }
        $cached[$cacheKey] = false;

        return false;
    }
    $row = odbc_fetch_array($stmt);
    if (is_resource($stmt)) {
        @odbc_free_result($stmt);
    }
    $cached[$cacheKey] = $row !== false && (int)($row['c'] ?? 0) > 0;

    return $cached[$cacheKey];
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

function m360_online_req_source_channel(array $requestRow): string
{
    $source = strtoupper(trim((string)($requestRow['source_channel'] ?? $requestRow['source'] ?? '')));
    if ($source === '') {
        $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
        $source = strtoupper(trim((string)($payload['source_channel'] ?? $payload['source'] ?? '')));
    }

    return $source;
}

function m360_online_req_is_staff_walkin(array $requestRow): bool
{
    return m360_online_req_source_channel($requestRow) === M360_ONLINE_REQ_SOURCE_STAFF_WALKIN;
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
    if ($sourceChannel === '') {
        $sourceChannel = M360_ONLINE_REQ_SOURCE_PUBLIC;
    }
    $payloadJson = (string)($fields['request_payload_json'] ?? '{}');
    $visitDate = trim((string)($fields['visit_date'] ?? ''));
    $status = m360_online_req_initial_status();

    $customerId = m360_online_req_resolve_customer_id($conn, $companyId, $mobile);
    $vehicleId = m360_online_req_resolve_vehicle_id($conn, $customerId, $plate);
    $profileRequired = $customerId === null;

    $payload = m360_online_req_parse_payload($payloadJson);
    $otpVerified = (int)($fields['otp_verified'] ?? ($payload['otp_verified'] ?? 1));
    $payload['otp_verified'] = $otpVerified;
    $payload['source'] = $sourceChannel;
    $payload['source_channel'] = $sourceChannel;
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
        $values[] = $otpVerified;
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $columnList = implode(', ', $columns);
    $sql = 'INSERT INTO dbo.' . m360_online_req_table() . ' (' . $columnList . ') VALUES (' . $placeholders . ')';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $values)) {
        return [
            'ok' => false,
            'online_request_id' => 0,
            'status' => '',
            'profile_required' => $profileRequired,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'error_code' => 'online_request_insert_failed',
            'insert_detail' => 'odbc_execute_failed',
        ];
    }

    $newId = 0;
    $idRes = @odbc_exec($conn, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id');
    if ($idRes !== false && ($row = odbc_fetch_array($idRes))) {
        $newId = (int)($row['new_id'] ?? 0);
    }
    if ($newId < 1) {
        $fallbackSql = 'SELECT TOP 1 online_request_id FROM dbo.' . m360_online_req_table()
            . ' WHERE company_id = ? AND mobile = ? ORDER BY online_request_id DESC';
        $fStmt = @odbc_prepare($conn, $fallbackSql);
        if ($fStmt !== false && @odbc_execute($fStmt, [$companyId, $mobile])) {
            $fRow = odbc_fetch_array($fStmt);
            if ($fRow !== false) {
                $newId = (int)($fRow['online_request_id'] ?? 0);
            }
        }
    }

    m360_online_req_write_history($conn, $newId, M360_ONLINE_REQ_HISTORY_CREATED, null, $status, 'Public site intake', null);

    return [
        'ok' => $newId > 0,
        'online_request_id' => $newId,
        'status' => $status,
        'profile_required' => $profileRequired,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
        'error_code' => $newId > 0 ? '' : 'online_request_identity_missing',
        'insert_detail' => $newId > 0 ? '' : 'scope_identity_empty',
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

/**
 * SQL NVARCHAR character length of stored request_payload_json (not PHP bytes).
 */
function m360_online_req_payload_sql_char_length($conn, int $requestId): int
{
    if (!is_resource($conn) || $requestId < 1) {
        return 0;
    }
    if (!m360_online_req_has_column($conn, 'request_payload_json')) {
        return 0;
    }
    $sql = 'SELECT LEN(request_payload_json) AS payload_len FROM dbo.'
        . m360_online_req_table() . ' WHERE online_request_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return 0;
    }
    if (!@odbc_execute($stmt, [$requestId])) {
        if (is_resource($stmt)) {
            @odbc_free_result($stmt);
        }

        return 0;
    }
    $row = odbc_fetch_array($stmt);
    if (is_resource($stmt)) {
        @odbc_free_result($stmt);
    }
    if ($row === false) {
        return 0;
    }

    return (int)($row['payload_len'] ?? $row['PAYLOAD_LEN'] ?? 0);
}

/**
 * ODBC-safe NVARCHAR(MAX) read — 200 SQL NVARCHAR characters per chunk (driver-safe literals).
 *
 * @return array{json:string,sql_len:int,read_len:int,ok:bool,error:string}
 */
function m360_online_req_read_payload_json_chunked($conn, int $requestId): array
{
    $fail = static function (array $chunks, int $sqlLen, int $readLen): array {
        return [
            'json' => implode('', $chunks),
            'sql_len' => $sqlLen,
            'read_len' => $readLen,
            'ok' => false,
            'error' => 'RUNTIME_PAYLOAD_READ_FAILED',
        ];
    };

    $empty = ['json' => '', 'sql_len' => 0, 'read_len' => 0, 'ok' => true, 'error' => ''];
    if (!is_resource($conn) || $requestId < 1) {
        return $empty;
    }
    if (!m360_online_req_has_column($conn, 'request_payload_json')) {
        return $empty;
    }

    $sqlLen = m360_online_req_payload_sql_char_length($conn, $requestId);
    if ($sqlLen < 1) {
        return $empty;
    }

    $chunkSize = 200;
    $maxChunks = 100;
    $expectedChunks = (int)ceil($sqlLen / $chunkSize) + 2;
    $loopLimit = min($maxChunks, max(1, $expectedChunks));
    $chunks = [];
    $prevChunk = null;
    $accumulatedSqlChars = 0;
    $table = m360_online_req_table();

    for ($i = 0; $i < $loopLimit; $i++) {
        $offset = $accumulatedSqlChars + 1;
        if ($offset > $sqlLen) {
            break;
        }
        if ($offset < 1 || $chunkSize < 1) {
            return $fail($chunks, $sqlLen, $accumulatedSqlChars);
        }

        $sql = 'SELECT SUBSTRING(request_payload_json, ' . $offset . ', ' . $chunkSize . ') AS chunk, '
            . 'LEN(SUBSTRING(request_payload_json, ' . $offset . ', ' . $chunkSize . ')) AS chunk_chars '
            . 'FROM dbo.' . $table . ' WHERE online_request_id = ?';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt === false || !@odbc_execute($stmt, [$requestId])) {
            if (is_resource($stmt)) {
                @odbc_free_result($stmt);
            }

            return $fail($chunks, $sqlLen, $accumulatedSqlChars);
        }
        $row = odbc_fetch_array($stmt);
        if (is_resource($stmt)) {
            @odbc_free_result($stmt);
        }
        if ($row === false) {
            return $fail($chunks, $sqlLen, $accumulatedSqlChars);
        }

        $rawChunk = $row['chunk'] ?? $row['CHUNK'] ?? null;
        if ($rawChunk === null) {
            break;
        }
        $chunk = (string)$rawChunk;
        if ($chunk === '') {
            break;
        }
        if ($prevChunk !== null && $chunk === $prevChunk) {
            return $fail($chunks, $sqlLen, $accumulatedSqlChars);
        }

        $chunkChars = (int)($row['chunk_chars'] ?? $row['CHUNK_CHARS'] ?? 0);
        if ($chunkChars < 1) {
            break;
        }

        $chunks[] = $chunk;
        $prevChunk = $chunk;
        $accumulatedSqlChars += $chunkChars;

        if ($accumulatedSqlChars >= $sqlLen) {
            break;
        }
        if ($chunkChars < $chunkSize && $accumulatedSqlChars >= $sqlLen) {
            break;
        }
    }

    $json = implode('', $chunks);
    $readLen = $accumulatedSqlChars;
    $ok = $readLen >= $sqlLen;

    return [
        'json' => $json,
        'sql_len' => $sqlLen,
        'read_len' => $readLen,
        'ok' => $ok,
        'error' => $ok ? '' : 'RUNTIME_PAYLOAD_READ_FAILED',
    ];
}

/** @param array<string, mixed> $row */
function m360_online_req_hydrate_row_payload_json($conn, array $row): array
{
    if (!is_resource($conn)) {
        return $row;
    }
    $requestId = (int)($row['online_request_id'] ?? 0);
    if ($requestId < 1) {
        return $row;
    }
    $result = m360_online_req_read_payload_json_chunked($conn, $requestId);
    $row['_payload_read_ok'] = !empty($result['ok']);
    $row['_payload_sql_len'] = (int)($result['sql_len'] ?? 0);
    $row['_payload_read_len'] = (int)($result['read_len'] ?? 0);
    $row['_payload_read_error'] = (string)($result['error'] ?? '');
    if ((string)($result['json'] ?? '') !== '') {
        $row['request_payload_json'] = (string)$result['json'];
    }

    return $row;
}

/** @return array<string, mixed>|null */
function m360_online_req_fetch_by_id($conn, int $requestId): ?array
{
    if (!is_resource($conn) || $requestId < 1) {
        return null;
    }

    $sql = 'SELECT TOP 1 * FROM dbo.' . m360_online_req_table() . ' WHERE online_request_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return null;
    }
    if (!@odbc_execute($stmt, [$requestId])) {
        if (is_resource($stmt)) {
            @odbc_free_result($stmt);
        }
        return null;
    }

    $row = odbc_fetch_array($stmt);
    if (is_resource($stmt)) {
        @odbc_free_result($stmt);
    }
    if ($row === false) {
        return null;
    }

    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[strtolower((string)$key)] = $value === null ? '' : (string)$value;
    }

    return m360_online_req_hydrate_row_payload_json($conn, $normalized);
}

function m360_online_req_list_has_otp_verified_column($conn): bool
{
    static $cached = [];
    $key = is_resource($conn) ? 'default' : 'none';
    if (!isset($cached[$key])) {
        $cached[$key] = is_resource($conn) && m360_online_req_has_column($conn, 'otp_verified');
    }

    return $cached[$key];
}

function m360_online_req_row_otp_verified_column_value(array $requestRow): ?bool
{
    if (!array_key_exists('otp_verified', $requestRow)) {
        return null;
    }
    $raw = $requestRow['otp_verified'];
    if ($raw === '' || $raw === null) {
        return null;
    }
    if ($raw === true || $raw === 1 || $raw === '1') {
        return true;
    }
    if ($raw === false || $raw === 0 || $raw === '0') {
        return false;
    }
    $text = strtolower(trim((string)$raw));
    if ($text === '1' || $text === 'true') {
        return true;
    }
    if ($text === '0' || $text === 'false') {
        return false;
    }

    return null;
}

function m360_online_req_payload_otp_verified_for_list($conn, array $requestRow): bool
{
    if (m360_online_req_list_has_otp_verified_column($conn)) {
        $columnValue = m360_online_req_row_otp_verified_column_value($requestRow);
        if ($columnValue === true) {
            return true;
        }
        if ($columnValue === false) {
            return false;
        }
    }

    return m360_online_req_payload_otp_verified($requestRow);
}

function m360_online_req_payload_otp_verified(array $requestRow): bool
{
    $columnValue = m360_online_req_row_otp_verified_column_value($requestRow);
    if ($columnValue === true) {
        return true;
    }

    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    if (isset($payload['otp_verified']) && (int)$payload['otp_verified'] === 1) {
        return true;
    }
    if ($columnValue === false) {
        return false;
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
