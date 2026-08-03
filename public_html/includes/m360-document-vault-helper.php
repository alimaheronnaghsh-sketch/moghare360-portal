<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Canonical SQL Server document blob vault (PDF, photos, intake docs, signatures).
 * Disk paths remain optional mirrors; active business evidence is stored in dbo.erp_document_blobs.
 */

if (defined('M360_DOCUMENT_VAULT_HELPER_LOADED')) {
    return;
}
define('M360_DOCUMENT_VAULT_HELPER_LOADED', true);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-audit-helper.php';

const M360_VAULT_TABLE = 'erp_document_blobs';

/** Max binary stored for vehicle_video without owner decision (bytes). */
const M360_VAULT_SAFE_VIDEO_MAX_BYTES = 15 * 1024 * 1024;

/** Max single blob insert attempt (bytes). */
const M360_VAULT_MAX_BLOB_BYTES = 52 * 1024 * 1024;

function m360_vault_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_vault_table_exists($conn): bool
{
    return is_resource($conn) && customer_core_table_exists($conn, M360_VAULT_TABLE);
}

function m360_vault_sha256(string $binary): string
{
    return hash('sha256', $binary);
}

/**
 * @return array{ok:bool,blocked_owner_decision:bool,reason:string,max_bytes:int,size:int}
 */
function m360_vault_binary_policy(string $documentCategory, int $sizeBytes): array
{
    $cat = strtoupper(trim($documentCategory));
    if ($cat === 'VEHICLE_VIDEO' && $sizeBytes > M360_VAULT_SAFE_VIDEO_MAX_BYTES) {
        return [
            'ok' => false,
            'blocked_owner_decision' => true,
            'reason' => 'VIDEO_EXCEEDS_SAFE_DB_LIMIT',
            'max_bytes' => M360_VAULT_SAFE_VIDEO_MAX_BYTES,
            'size' => $sizeBytes,
        ];
    }
    if ($sizeBytes < 1 || $sizeBytes > M360_VAULT_MAX_BLOB_BYTES) {
        return [
            'ok' => false,
            'blocked_owner_decision' => false,
            'reason' => 'SIZE_OUT_OF_RANGE',
            'max_bytes' => M360_VAULT_MAX_BLOB_BYTES,
            'size' => $sizeBytes,
        ];
    }

    return ['ok' => true, 'blocked_owner_decision' => false, 'reason' => '', 'max_bytes' => M360_VAULT_MAX_BLOB_BYTES, 'size' => $sizeBytes];
}

function m360_vault_intake_document_category(string $documentType): string
{
    $type = strtolower(trim($documentType));
    if ($type === 'vehicle_video') {
        return 'VEHICLE_VIDEO';
    }

    return match ($type) {
        'diagnostic_report', 'scanner_report' => 'DIAGNOSTIC_REPORT',
        'expert_report' => 'EXPERT_REPORT',
        default => 'INTAKE_DOCUMENT',
    };
}

function m360_vault_audit_event(
    $conn,
    string $action,
    ?int $blobId,
    array $details,
    ?int $actorUserId = null
): void
{
    if (!is_resource($conn)) {
        return;
    }
    $actor = $actorUserId !== null && $actorUserId > 0 ? $actorUserId : (int)(erp_auth_current_user_id() ?? 0);
    if ($actor < 1) {
        $actor = ERP_PHASE1_PLATFORM_OWNER_ID;
    }
    $payload = $details;
    $payload['document_blob_id'] = $blobId;
    m360_access_audit_record_event(
        $conn,
        $actor,
        $action,
        null,
        null,
        'DOCUMENT_BLOB',
        $blobId,
        $payload
    );
}

/**
 * @param array<string, mixed> $opts
 * @return array{ok:bool,blob_id:int,sha256:string,created:bool,message:string,owner_decision_required:bool}
 */
function m360_vault_store($conn, array $opts): array
{
    $fail = static function (string $msg, bool $owner = false): array {
        return [
            'ok' => false,
            'blob_id' => 0,
            'sha256' => '',
            'created' => false,
            'message' => $msg,
            'owner_decision_required' => $owner,
        ];
    };

    if (!m360_vault_table_exists($conn)) {
        return $fail('جدول بایگانی سند (erp_document_blobs) در پایگاه داده موجود نیست. migration P_FINAL_00 را اجرا کنید.');
    }

    $binary = $opts['content_binary'] ?? '';
    if (!is_string($binary) || $binary === '') {
        return $fail('محتوای باینری خالی است.');
    }

    $category = strtoupper(trim((string)($opts['document_category'] ?? 'INTAKE_DOCUMENT')));
    $size = strlen($binary);
    $policy = m360_vault_binary_policy($category, $size);
    if (!$policy['ok']) {
        if ($policy['blocked_owner_decision']) {
            return $fail(
                'ویدئو از حد امن ذخیره در SQL Server بزرگ‌تر است (' . $size . ' بایت؛ حد ' . $policy['max_bytes'] . '). OWNER_DECISION_REQUIRED',
                true
            );
        }

        return $fail('حجم فایل برای بایگانی SQL مجاز نیست.');
    }

    $ownerType = strtoupper(trim((string)($opts['owner_type'] ?? 'ONLINE_REQUEST')));
    $ownerId = (int)($opts['owner_id'] ?? 0);
    if ($ownerId < 1) {
        return $fail('owner_id نامعتبر است.');
    }

    $sha = m360_vault_sha256($binary);
    $contentType = trim((string)($opts['content_type'] ?? 'application/octet-stream'));
    $ext = strtolower(trim((string)($opts['file_extension'] ?? '')));
    $origName = trim((string)($opts['original_file_name'] ?? ''));
    $mirror = trim((string)($opts['disk_mirror_path'] ?? ''));
    if ($mirror !== '' && (str_contains($mirror, '..') || preg_match('#^[a-zA-Z]:#', $mirror))) {
        $mirror = '';
    }

    $relatedCustomerId = isset($opts['related_customer_id']) ? (int)$opts['related_customer_id'] : null;
    $relatedVehicleId = isset($opts['related_vehicle_id']) ? (int)$opts['related_vehicle_id'] : null;
    $relatedRequestId = isset($opts['related_request_id']) ? (int)$opts['related_request_id'] : null;
    $relatedJobcardId = isset($opts['related_jobcard_id']) ? (int)$opts['related_jobcard_id'] : null;
    $createdBy = isset($opts['created_by']) ? (int)$opts['created_by'] : (int)(erp_auth_current_user_id() ?? 0);
    $notes = $opts['notes_json'] ?? null;
    $notesJson = is_string($notes) ? $notes : (is_array($notes) ? json_encode($notes, JSON_UNESCAPED_UNICODE) : null);
    $supersedeCategory = !empty($opts['supersede_same_category']);

    $versionNo = 1;
    $supersededIds = [];
    if ($supersedeCategory) {
        $activeRows = customer_core_fetch_rows(
            $conn,
            'SELECT document_blob_id, version_no FROM dbo.' . M360_VAULT_TABLE . '
             WHERE owner_type = ? AND owner_id = ? AND document_category = ? AND is_active = 1 AND deleted_at IS NULL',
            [$ownerType, $ownerId, $category]
        );
        foreach ($activeRows as $row) {
            $supersededIds[] = (int)($row['document_blob_id'] ?? 0);
            $vn = (int)($row['version_no'] ?? 0);
            if ($vn >= $versionNo) {
                $versionNo = $vn + 1;
            }
        }
    }

    $sql = 'INSERT INTO dbo.' . M360_VAULT_TABLE . ' (
        owner_type, owner_id, related_customer_id, related_vehicle_id, related_request_id, related_jobcard_id,
        document_category, original_file_name, content_type, file_extension, file_size_bytes, sha256_hash,
        content_binary, disk_mirror_path, version_no, is_active, created_by, notes_json
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)';

    $ok = customer_core_execute($conn, $sql, [
        $ownerType,
        $ownerId,
        $relatedCustomerId > 0 ? $relatedCustomerId : null,
        $relatedVehicleId > 0 ? $relatedVehicleId : null,
        $relatedRequestId > 0 ? $relatedRequestId : null,
        $relatedJobcardId > 0 ? $relatedJobcardId : null,
        $category,
        $origName !== '' ? $origName : null,
        $contentType,
        $ext !== '' ? $ext : null,
        $size,
        $sha,
        $binary,
        $mirror !== '' ? $mirror : null,
        $versionNo,
        $createdBy > 0 ? $createdBy : null,
        $notesJson,
    ]);

    if ($ok === false) {
        return $fail('ذخیره باینری در SQL Server ناموفق بود.');
    }

    $blobId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 document_blob_id FROM dbo.' . M360_VAULT_TABLE . '
         WHERE owner_type = ? AND owner_id = ? AND sha256_hash = ? AND is_active = 1
         ORDER BY document_blob_id DESC',
        [$ownerType, $ownerId, $sha]
    ) ?? 0);
    if ($blobId < 1) {
        $blobId = (int)(customer_core_scope_identity($conn) ?? 0);
    }
    if ($blobId < 1) {
        return $fail('شناسه blob پس از INSERT بازیابی نشد.');
    }

    foreach ($supersededIds as $oldId) {
        if ($oldId < 1) {
            continue;
        }
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_VAULT_TABLE . ' SET is_active = 0, superseded_by_blob_id = ?, updated_at = SYSUTCDATETIME()
             WHERE document_blob_id = ? AND is_active = 1',
            [$blobId, $oldId]
        );
        m360_vault_audit_event($conn, 'DOCUMENT_BLOB_SUPERSEDED', $oldId, ['superseded_by' => $blobId], $createdBy);
    }

    m360_vault_audit_event($conn, 'DOCUMENT_BLOB_CREATED', $blobId, [
        'owner_type' => $ownerType,
        'owner_id' => $ownerId,
        'document_category' => $category,
        'sha256' => $sha,
        'file_size_bytes' => $size,
        'disk_mirror_path' => $mirror !== '' ? 'set' : 'none',
    ], $createdBy);

    return [
        'ok' => true,
        'blob_id' => $blobId,
        'sha256' => $sha,
        'created' => true,
        'message' => '',
        'owner_decision_required' => false,
    ];
}

/**
 * @return array<string, string>|null metadata only
 */
function m360_vault_fetch_meta($conn, int $blobId): ?array
{
    if (!m360_vault_table_exists($conn) || $blobId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT document_blob_id, owner_type, owner_id, related_customer_id, related_vehicle_id, related_request_id,
                related_jobcard_id, document_category, original_file_name, content_type, file_extension,
                file_size_bytes, sha256_hash, disk_mirror_path, version_no, is_active, superseded_by_blob_id,
                created_by, created_at, updated_at, deleted_at
         FROM dbo.' . M360_VAULT_TABLE . '
         WHERE document_blob_id = ? AND deleted_at IS NULL',
        [$blobId]
    );

    return $rows[0] ?? null;
}

/**
 * @return array{ok:bool,bytes:string,meta:array<string,string>,source:string,message:string}
 */
function m360_vault_load_bytes($conn, int $blobId): array
{
    $empty = ['ok' => false, 'bytes' => '', 'meta' => [], 'source' => '', 'message' => ''];
    $meta = m360_vault_fetch_meta($conn, $blobId);
    if ($meta === null) {
        $empty['message'] = 'blob یافت نشد.';

        return $empty;
    }
    if ((int)($meta['is_active'] ?? 0) !== 1) {
        $empty['message'] = 'نسخه blob غیرفعال است.';
        $empty['meta'] = $meta;

        return $empty;
    }

    $expected = (int)($meta['file_size_bytes'] ?? 0);
    if ($expected < 1) {
        $lenRaw = customer_core_scalar(
            $conn,
            'SELECT CAST(DATALENGTH(content_binary) AS BIGINT) FROM dbo.' . M360_VAULT_TABLE . ' WHERE document_blob_id = ?',
            [$blobId]
        );
        $expected = (int)($lenRaw ?? 0);
    }

    $raw = m360_vault_fetch_binary_chunked($conn, $blobId, $expected);
    if ($raw !== '' && ($expected < 1 || strlen($raw) >= $expected || strlen($raw) > 4096)) {
        // Prefer full SQL payload when length matches or exceeds legacy 4KB truncation.
        if ($expected < 1 || strlen($raw) === $expected || (strlen($raw) > 4096 && strlen($raw) >= (int)floor($expected * 0.98))) {
            return ['ok' => true, 'bytes' => $raw, 'meta' => $meta, 'source' => 'sql', 'message' => ''];
        }
    }

    $mirror = trim((string)($meta['disk_mirror_path'] ?? ''));
    $bytes = m360_vault_read_disk_mirror($mirror);
    if ($bytes !== '') {
        return ['ok' => true, 'bytes' => $bytes, 'meta' => $meta, 'source' => 'disk_mirror', 'message' => ''];
    }
    if ($raw !== '') {
        return ['ok' => true, 'bytes' => $raw, 'meta' => $meta, 'source' => 'sql_partial', 'message' => 'partial_odbc_read'];
    }
    $empty['message'] = 'محتوای blob خالی است.';
    $empty['meta'] = $meta;

    return $empty;
}

/**
 * ODBC-safe VARBINARY(MAX) read via hex chunks (avoids 4096-byte odbc_result truncation).
 */
function m360_vault_fetch_binary_chunked($conn, int $blobId, int $expectedLen = 0): string
{
    if (!is_resource($conn) || $blobId < 1) {
        return '';
    }
    if ($expectedLen < 1) {
        $lenRaw = customer_core_scalar(
            $conn,
            'SELECT CAST(DATALENGTH(content_binary) AS BIGINT) FROM dbo.' . M360_VAULT_TABLE . ' WHERE document_blob_id = ?',
            [$blobId]
        );
        $expectedLen = (int)($lenRaw ?? 0);
    }
    if ($expectedLen < 1) {
        return '';
    }

    // 2000 binary bytes => 4000 hex chars (under typical ODBC long-read defaults).
    $chunkBytes = 2000;
    $out = '';
    for ($offset = 1; $offset <= $expectedLen; $offset += $chunkBytes) {
        $take = min($chunkBytes, $expectedLen - $offset + 1);
        // Interpolate trusted ints (like payload chunked reader) — ODBC params on SUBSTRING are unreliable.
        $sql = 'SELECT CONVERT(VARCHAR(4000), SUBSTRING(content_binary, ' . (int)$offset . ', ' . (int)$take . '), 2) AS hex_chunk'
            . ' FROM dbo.' . M360_VAULT_TABLE . ' WHERE document_blob_id = ?';
        $hex = customer_core_scalar($conn, $sql, [$blobId]);
        if ($hex === null || $hex === '') {
            // Fallback column name variants / fetch_rows
            $rows = customer_core_fetch_rows($conn, $sql, [$blobId]);
            $hex = (string)($rows[0]['hex_chunk'] ?? $rows[0]['HEX_CHUNK'] ?? '');
        }
        if ($hex === '') {
            break;
        }
        $bin = @hex2bin($hex);
        if ($bin === false) {
            break;
        }
        $out .= $bin;
        if (strlen($bin) < $take) {
            break;
        }
    }

    return $out;
}

function m360_vault_read_disk_mirror(string $diskMirrorPath): string
{
    $rel = str_replace('\\', '/', trim($diskMirrorPath));
    if ($rel === '' || str_contains($rel, '..')) {
        return '';
    }
    if (str_starts_with($rel, 'storage/')) {
        $rel = substr($rel, strlen('storage/'));
    }
    $candidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel),
        'C:\\xampp\\htdocs\\moghare360\\storage\\' . str_replace('/', DIRECTORY_SEPARATOR, $rel),
    ];
    foreach ($candidates as $abs) {
        if (is_file($abs)) {
            $bytes = (string)@file_get_contents($abs);
            if ($bytes !== '') {
                return $bytes;
            }
        }
    }

    return '';
}

/**
 * @param array<string, string> $meta
 * @return array{ok:bool,message:string}
 */
function m360_vault_authorize_stream($conn, array $meta, string $actorMode): array
{
    if ($actorMode === 'staff') {
        erp_auth_context_start();
        if (erp_auth_current_user_id() === null || (int)erp_auth_current_user_id() < 1) {
            return ['ok' => false, 'message' => 'staff_auth_required'];
        }

        return ['ok' => true, 'message' => ''];
    }

    if ($actorMode === 'customer') {
        if (!function_exists('m360_contract_require_verified_session_mobile_for_contract')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';
        }
        $customerId = (int)($meta['related_customer_id'] ?? 0);
        $requestId = (int)($meta['related_request_id'] ?? 0);
        if ($customerId < 1 && $requestId < 1) {
            return ['ok' => false, 'message' => 'customer_scope_missing'];
        }
        if (function_exists('m360_customer_session_mobile_normalized')) {
            $mobile = m360_customer_session_mobile_normalized();
            if ($mobile === '') {
                return ['ok' => false, 'message' => 'customer_session_required'];
            }
            if ($requestId > 0 && function_exists('m360_online_request_belongs_to_mobile')) {
                if (!m360_online_request_belongs_to_mobile($conn, $requestId, $mobile)) {
                    return ['ok' => false, 'message' => 'customer_ownership_denied'];
                }

                return ['ok' => true, 'message' => ''];
            }
        }

        return ['ok' => false, 'message' => 'customer_ownership_denied'];
    }

    return ['ok' => false, 'message' => 'invalid_actor'];
}

function m360_vault_stream_url(int $blobId, string $actor = 'staff'): string
{
    return 'm360-document-vault-stream.php?blob_id=' . max(0, $blobId) . '&actor=' . rawurlencode($actor);
}

/**
 * Prefer vault stream URL when blob id present; else legacy storage/ path (mirror).
 */
function m360_vault_media_src(?int $blobId, string $relativeStoragePath): string
{
    if ($blobId !== null && $blobId > 0) {
        return m360_vault_stream_url($blobId, 'staff');
    }
    $path = trim(str_replace('\\', '/', $relativeStoragePath));
    if ($path === '') {
        return '';
    }
    if (!str_starts_with($path, 'storage/') && !str_starts_with($path, 'reception-intake/')) {
        $path = 'reception-intake/' . ltrim($path, '/');
    }
    if (str_starts_with($path, 'reception-intake/')) {
        return 'storage/' . $path;
    }

    return $path;
}

/**
 * @param array<string, mixed> $context
 * @return array{ok:bool,blob_id:int,sha256:string,message:string,owner_decision_required:bool}
 */
function m360_vault_backfill_from_disk($conn, string $absolutePath, array $context): array
{
    if (!is_file($absolutePath)) {
        return ['ok' => false, 'blob_id' => 0, 'sha256' => '', 'message' => 'file_missing', 'owner_decision_required' => false];
    }
    $bytes = (string)@file_get_contents($absolutePath);
    if ($bytes === '') {
        return ['ok' => false, 'blob_id' => 0, 'sha256' => '', 'message' => 'empty_file', 'owner_decision_required' => false];
    }
    $context['content_binary'] = $bytes;
    if (empty($context['supersede_same_category'])) {
        $context['supersede_same_category'] = false;
    }
    $store = m360_vault_store($conn, $context);
    if ($store['ok']) {
        m360_vault_audit_event($conn, 'DOCUMENT_BLOB_BACKFILLED', $store['blob_id'], [
            'path' => basename($absolutePath),
        ], isset($context['created_by']) ? (int)$context['created_by'] : null);
    }

    return [
        'ok' => $store['ok'],
        'blob_id' => (int)$store['blob_id'],
        'sha256' => (string)$store['sha256'],
        'message' => (string)$store['message'],
        'owner_decision_required' => !empty($store['owner_decision_required']),
    ];
}

/**
 * @return array{ok:bool,blob_id:int,sha256:string,message:string,owner_decision_required:bool}
 */
function m360_vault_store_contract_pdf(
    $conn,
    int $contractId,
    int $onlineRequestId,
    int $customerId,
    string $pdfBytes,
    string $filename,
    string $diskMirrorRelative,
    ?int $actorUserId = null
): array {
    $store = m360_vault_store($conn, [
        'owner_type' => 'CONTRACT',
        'owner_id' => $contractId,
        'related_customer_id' => $customerId,
        'related_request_id' => $onlineRequestId,
        'document_category' => 'CONTRACT_PDF',
        'original_file_name' => $filename,
        'content_type' => 'application/pdf',
        'file_extension' => 'pdf',
        'content_binary' => $pdfBytes,
        'disk_mirror_path' => $diskMirrorRelative,
        'created_by' => $actorUserId,
        'supersede_same_category' => true,
        'notes_json' => ['source' => 'm360_intake_contract_ensure_pdf'],
    ]);

    return [
        'ok' => $store['ok'],
        'blob_id' => (int)$store['blob_id'],
        'sha256' => (string)$store['sha256'],
        'message' => (string)$store['message'],
        'owner_decision_required' => !empty($store['owner_decision_required']),
    ];
}

/**
 * @return array{ok:bool,blob_id:int,sha256:string,message:string,owner_decision_required:bool}
 */
function m360_vault_store_intake_photo(
    $conn,
    int $onlineRequestId,
    string $slotKey,
    string $imageBytes,
    string $diskMirrorRelative,
    ?int $customerId = null,
    ?int $vehicleId = null,
    ?int $actorUserId = null
): array {
    $ext = 'jpg';
    $mime = 'image/jpeg';
    if (str_starts_with($imageBytes, "\x89PNG")) {
        $ext = 'png';
        $mime = 'image/png';
    }
    $store = m360_vault_store($conn, [
        'owner_type' => 'ONLINE_REQUEST',
        'owner_id' => $onlineRequestId,
        'related_request_id' => $onlineRequestId,
        'related_customer_id' => $customerId,
        'related_vehicle_id' => $vehicleId,
        'document_category' => 'VEHICLE_PHOTO',
        'original_file_name' => 'photo_' . $slotKey . '.' . $ext,
        'content_type' => $mime,
        'file_extension' => $ext,
        'content_binary' => $imageBytes,
        'disk_mirror_path' => $diskMirrorRelative,
        'created_by' => $actorUserId,
        'supersede_same_category' => false,
        'notes_json' => ['photo_slot' => $slotKey],
    ]);

    return [
        'ok' => $store['ok'],
        'blob_id' => (int)$store['blob_id'],
        'sha256' => (string)$store['sha256'],
        'message' => (string)$store['message'],
        'owner_decision_required' => !empty($store['owner_decision_required']),
    ];
}

/**
 * @return array{ok:bool,blob_id:int,sha256:string,message:string,owner_decision_required:bool}
 */
function m360_vault_store_intake_document(
    $conn,
    int $onlineRequestId,
    string $documentType,
    string $bytes,
    string $originalName,
    string $mimeType,
    string $diskMirrorRelative,
    ?int $customerId = null,
    ?int $actorUserId = null
): array {
    $cat = m360_vault_intake_document_category($documentType);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $store = m360_vault_store($conn, [
        'owner_type' => 'INTAKE',
        'owner_id' => $onlineRequestId,
        'related_request_id' => $onlineRequestId,
        'related_customer_id' => $customerId,
        'document_category' => $cat,
        'original_file_name' => $originalName,
        'content_type' => $mimeType !== '' ? $mimeType : 'application/octet-stream',
        'file_extension' => $ext,
        'content_binary' => $bytes,
        'disk_mirror_path' => $diskMirrorRelative,
        'created_by' => $actorUserId,
        'supersede_same_category' => false,
        'notes_json' => ['document_type' => $documentType],
    ]);

    return [
        'ok' => $store['ok'],
        'blob_id' => (int)$store['blob_id'],
        'sha256' => (string)$store['sha256'],
        'message' => (string)$store['message'],
        'owner_decision_required' => !empty($store['owner_decision_required']),
    ];
}

/**
 * @return array{ok:bool,blob_id:int,sha256:string,message:string,owner_decision_required:bool}
 */
function m360_vault_store_signature_image(
    $conn,
    int $contractId,
    int $onlineRequestId,
    int $customerId,
    string $signatureDataUrl,
    ?int $actorUserId = null
): array {
    $raw = trim($signatureDataUrl);
    $binary = '';
    $mime = 'image/png';
    $ext = 'png';
    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/i', $raw, $m)) {
        $ext = strtolower($m[1]) === 'jpeg' || strtolower($m[1]) === 'jpg' ? 'jpg' : 'png';
        $mime = $ext === 'jpg' ? 'image/jpeg' : 'image/png';
        $binary = (string)base64_decode($m[2], true);
    }
    if ($binary === '' || strlen($binary) < 50) {
        return ['ok' => false, 'blob_id' => 0, 'sha256' => '', 'message' => 'invalid_signature', 'owner_decision_required' => false];
    }

    $store = m360_vault_store($conn, [
        'owner_type' => 'CONTRACT',
        'owner_id' => $contractId,
        'related_customer_id' => $customerId,
        'related_request_id' => $onlineRequestId,
        'document_category' => 'SIGNATURE_IMAGE',
        'original_file_name' => 'signature_c' . $contractId . '.' . $ext,
        'content_type' => $mime,
        'file_extension' => $ext,
        'content_binary' => $binary,
        'disk_mirror_path' => '',
        'created_by' => $actorUserId,
        'supersede_same_category' => true,
        'notes_json' => ['source' => 'contract_signature'],
    ]);

    return [
        'ok' => $store['ok'],
        'blob_id' => (int)$store['blob_id'],
        'sha256' => (string)$store['sha256'],
        'message' => (string)$store['message'],
        'owner_decision_required' => !empty($store['owner_decision_required']),
    ];
}

function m360_vault_safe_meta_public(array $meta): array
{
    return [
        'document_blob_id' => (int)($meta['document_blob_id'] ?? 0),
        'document_category' => (string)($meta['document_category'] ?? ''),
        'content_type' => (string)($meta['content_type'] ?? ''),
        'file_size_bytes' => (int)($meta['file_size_bytes'] ?? 0),
        'sha256_hash' => (string)($meta['sha256_hash'] ?? ''),
        'version_no' => (int)($meta['version_no'] ?? 0),
        'has_disk_mirror' => trim((string)($meta['disk_mirror_path'] ?? '')) !== '',
    ];
}
