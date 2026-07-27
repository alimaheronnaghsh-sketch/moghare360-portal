<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Real contract PDF download (mPDF).
 * Staff: ERP session. Customer: verified OTP session mobile binding (optional secure token).
 */

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$actor = strtolower(trim((string)($_GET['actor'] ?? 'staff')));
if (!in_array($actor, ['staff', 'customer'], true)) {
    $actor = 'staff';
}
$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
$onlineRequestId = isset($_GET['online_request_id']) ? (int)$_GET['online_request_id'] : 0;
$rawToken = trim((string)($_GET['token'] ?? ''));

$conn = customer_core_db();
if ($conn === false) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'سرویس در دسترس نیست.';
    exit;
}

$contractRow = null;
if ($contractId > 0) {
    $contractRow = m360_intake_contract_fetch_by_id($conn, $contractId);
} elseif ($onlineRequestId > 0) {
    $contractRow = m360_intake_contract_find_active_for_online_request($conn, $onlineRequestId);
}

if ($contractRow === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'قرارداد یافت نشد.';
    exit;
}

$contractId = (int)($contractRow['contract_id'] ?? 0);
$generatedByUserId = null;

if ($actor === 'staff') {
    if (!function_exists('m360_intake_contract_require_staff')) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'دسترسی مجاز نیست.';
        exit;
    }
    m360_intake_contract_require_staff();
    if (function_exists('erp_auth_context_start')) {
        erp_auth_context_start();
    }
    $generatedByUserId = function_exists('erp_auth_current_user_id') ? erp_auth_current_user_id() : null;
} else {
    // Customer: require verified session mobile bound to contract. Token is optional extra gate.
    $binding = m360_contract_require_verified_session_mobile_for_contract($contractRow);
    if (empty($binding['ok'])) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'برای دانلود PDF ابتدا وارد کارتابل مشتری شوید.';
        exit;
    }
    if ($rawToken !== '') {
        $tokenHash = m360_intake_contract_hash($rawToken);
        $storedHash = trim((string)($contractRow['secure_token_hash'] ?? ''));
        if ($storedHash === '' || !hash_equals($storedHash, $tokenHash)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'توکن دسترسی قرارداد نامعتبر است.';
            exit;
        }
    }
}

$result = m360_intake_contract_ensure_pdf($conn, $contractRow, $actor, $generatedByUserId !== null ? (int)$generatedByUserId : null, true);
if (!$result['ok'] || !is_string($result['bytes']) || $result['bytes'] === '' || !str_starts_with($result['bytes'], '%PDF')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo (string)($result['message'] ?? 'تولید PDF ناموفق بود.');
    exit;
}

$meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
$filename = trim((string)($meta['filename'] ?? ''));
if ($filename === '') {
    $filename = 'moghare360-contract-' . $contractId . '.pdf';
}
$filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?? 'contract.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . (string)strlen($result['bytes']));
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
echo $result['bytes'];
exit;
