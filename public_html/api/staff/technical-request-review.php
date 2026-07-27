<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Only POST is allowed.';
    exit;
}
$technicalRequestId = (int)($_POST['technical_request_id'] ?? 0);
$result = is_resource($conn)
    ? m360_fulljob_review_request($conn, $technicalRequestId, (string)($_POST['decision'] ?? ''), (string)($_POST['review_note'] ?? ''), $actor)
    : ['ok' => false, 'message' => 'DB unavailable'];
header('Location: ../../erp-technical-request-detail.php?technical_request_id=' . $technicalRequestId . '&msg=' . rawurlencode((string)$result['message']), true, 302);
exit;
