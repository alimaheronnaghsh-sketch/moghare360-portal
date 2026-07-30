<?php
declare(strict_types=1);

/**
 * MOGHARE360 CRM — read-only customer/vehicle search API.
 * GET only. Max 10 rows. No mutations.
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

$entity = strtolower(trim((string)($_GET['entity'] ?? '')));
$q = trim((string)($_GET['q'] ?? ''));
$customerId = (int)($_GET['customer_id'] ?? 0);

if (!in_array($entity, ['customer', 'vehicle'], true)) {
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

$items = [];
if ($entity === 'customer') {
    $items = crm360_search_customers($conn, $q, 10);
} else {
    $items = crm360_search_vehicles($conn, $q, 10, $customerId > 0 ? $customerId : null);
}

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
exit;
