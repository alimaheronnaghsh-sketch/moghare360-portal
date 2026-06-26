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
$resolved = m360_estimate_resolve_token($token);

if (!$resolved['ok'] || !is_array($resolved['estimate'])) {
    mogh_api_fail($resolved['message'], 403);
}

$result = m360_estimate_customer_decision(
    $resolved['estimate'],
    $token,
    (string)($body['decision'] ?? 'approve'),
    !empty($body['confirm_viewed']),
    !empty($body['confirm_amount']),
    !empty($body['confirm_hidden']),
    trim((string)($body['otp_code'] ?? '')),
    trim((string)($body['reject_reason'] ?? ''))
);

if (!$result['ok']) {
    mogh_api_fail($result['message'], 400);
}
mogh_api_ok($result['message']);
