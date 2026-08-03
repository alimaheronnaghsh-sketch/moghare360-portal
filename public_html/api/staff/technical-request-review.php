<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Only POST is allowed.';
    exit;
}
$technicalRequestId = (int)($_POST['technical_request_id'] ?? 0);
$decision = (string)($_POST['decision'] ?? '');
$note = (string)($_POST['review_note'] ?? '');
m360_ws_assert_request_object_scope($conn, $technicalRequestId);
$req = is_resource($conn) ? m360_fulljob_fetch_request($conn, $technicalRequestId) : null;
$jcId = (int)($req['jobcard_id'] ?? 0);
$perm = m360_ws_part_request_permission_for_decision($decision);
m360_ws_require($perm, $jcId > 0 ? $jcId : null);
if (trim($note) === '' && in_array(strtoupper(trim($decision)), ['REJECT', 'NEEDS_MORE_EVIDENCE'], true)) {
    m360_am_forbidden('برای رد یا برگشت درخواست قطعه، ثبت توضیح الزامی است.');
}
$result = is_resource($conn)
    ? m360_fulljob_review_request($conn, $technicalRequestId, $decision, $note, $actor)
    : ['ok' => false, 'message' => 'DB unavailable'];
header('Location: ../../erp-technical-request-detail.php?technical_request_id=' . $technicalRequestId . '&msg=' . rawurlencode((string)$result['message']), true, 302);
exit;
