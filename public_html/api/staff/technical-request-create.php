<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Only POST is allowed.';
    exit;
}
$jobcardId = (int)($_POST['jobcard_id'] ?? 0);
$result = is_resource($conn)
    ? m360_fulljob_create_request($conn, $jobcardId, $_POST, $actor)
    : ['ok' => false, 'message' => 'DB unavailable', 'technical_request_id' => 0];
$redirect = '../../erp-technical-request-center.php?jobcard_id=' . $jobcardId . '&msg=' . rawurlencode((string)$result['message']);
header('Location: ' . $redirect, true, 302);
exit;
