<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Authenticated stream for dbo.erp_document_blobs (no raw filesystem paths).
 */

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-document-vault-helper.php';

$blobId = isset($_GET['blob_id']) ? (int)$_GET['blob_id'] : 0;
$actor = strtolower(trim((string)($_GET['actor'] ?? 'staff')));
if (!in_array($actor, ['staff', 'customer'], true)) {
    $actor = 'staff';
}

if ($blobId < 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'شناسه سند نامعتبر است.';
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'سرویس در دسترس نیست.';
    exit;
}

$meta = m360_vault_fetch_meta($conn, $blobId);
if ($meta === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'سند یافت نشد.';
    exit;
}

$auth = m360_vault_authorize_stream($conn, $meta, $actor);
if (!$auth['ok']) {
    m360_vault_audit_event($conn, 'DOCUMENT_BLOB_DOWNLOAD_DENIED', $blobId, [
        'actor' => $actor,
        'reason' => $auth['message'],
    ], null);
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'دسترسی به این سند مجاز نیست.';
    exit;
}

$load = m360_vault_load_bytes($conn, $blobId);
if (!$load['ok'] || $load['bytes'] === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'محتوای سند در دسترس نیست.';
    exit;
}

$actorUserId = $actor === 'staff' ? (int)(erp_auth_current_user_id() ?? 0) : null;
m360_vault_audit_event($conn, 'DOCUMENT_BLOB_STREAMED', $blobId, [
    'actor' => $actor,
    'source' => $load['source'],
    'content_type' => (string)($meta['content_type'] ?? ''),
], $actorUserId);

$contentType = trim((string)($meta['content_type'] ?? 'application/octet-stream'));
$filename = trim((string)($meta['original_file_name'] ?? 'document'));
$filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?? 'document';
$disposition = str_starts_with($contentType, 'image/') ? 'inline' : 'attachment';

header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
header('Content-Length: ' . (string)strlen($load['bytes']));
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
echo $load['bytes'];
exit;
