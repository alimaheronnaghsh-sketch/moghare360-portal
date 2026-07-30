<?php
declare(strict_types=1);

/**
 * MOGHARE360 CRM — unified erp_* customer/vehicle search API.
 * GET only. Max 10 rows. No mutations. No crm360_* masters.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/includes/crm360-helper.php';
if (is_file(__DIR__ . '/includes/reception-ui-helper.php')) {
    require_once __DIR__ . '/includes/reception-ui-helper.php';
}

$auth = crm360_auth_context();
$revealSensitive = ($auth['role'] ?? '') === 'OWNER'
    || strcasecmp((string)($auth['actor'] ?? ''), 'local_owner') === 0;

$entity = strtolower(trim((string)($_GET['entity'] ?? 'unified')));
$q = trim((string)($_GET['q'] ?? ''));
$customerId = (int)($_GET['customer_id'] ?? 0);

// Accept legacy entity=customer|vehicle for pickers; always return unified erp_* rows.
if (!in_array($entity, ['unified', 'customer', 'vehicle', ''], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'INVALID_ENTITY'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($q) < 2) {
    echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = crm360_db();
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'DB_UNAVAILABLE'], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = crm360_erp_unified_search($conn, $q, 10, $revealSensitive);
if ($customerId > 0) {
    $items = array_values(array_filter(
        $items,
        static fn ($it) => (int)($it['customer_id'] ?? 0) === $customerId
    ));
}

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
exit;
