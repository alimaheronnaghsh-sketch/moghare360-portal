<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-approval-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$token = trim((string)($body['token'] ?? ''));
$taskId = (int)($body['task_id'] ?? 0);
$conn = customer_core_db();
if ($conn === false) {
    mogh_api_fail('سرویس در دسترس نیست.', 503);
}

$context = m360_estimate_resolve_customer_context($conn, ['token' => $token, 'task_id' => $taskId]);
if (!$context['ok']) {
    mogh_api_fail((string)$context['message'], 403);
}

$result = m360_estimate_customer_decision($context, [
    'decision' => (string)($body['decision'] ?? 'approve'),
    'confirm_viewed' => $body['confirm_viewed'] ?? null,
    'confirm_amount' => $body['confirm_amount'] ?? null,
    'confirm_hidden' => $body['confirm_hidden'] ?? null,
    'otp_code' => trim((string)($body['otp_code'] ?? '')),
    'reject_reason' => trim((string)($body['reject_reason'] ?? '')),
    'customer_note' => trim((string)($body['customer_note'] ?? '')),
    'content_hash' => trim((string)($body['content_hash'] ?? ($context['version']['content_hash'] ?? ''))),
    'erp_csrf_token' => $body['erp_csrf_token'] ?? ($body['csrf_token'] ?? null),
]);

if (!$result['ok']) {
    mogh_api_fail($result['message'], 400);
}
mogh_api_ok($result['message']);
