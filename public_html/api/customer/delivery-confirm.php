<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-delivery-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$token = trim((string)($body['token'] ?? ''));
$resolved = m360_delivery_resolve_token($token);

if (!$resolved['ok'] || !is_array($resolved['invoice'])) {
    mogh_api_fail($resolved['message'], 403);
}

$result = m360_delivery_confirm(
    $resolved['invoice'],
    $token,
    trim((string)($body['otp_code'] ?? '')),
    trim((string)($body['signature_data'] ?? '')),
    !empty($body['confirm_vehicle']) || !empty($body['c1']),
    !empty($body['confirm_services']) || !empty($body['c2']),
    !empty($body['confirm_finance']) || !empty($body['c3']),
    !empty($body['confirm_terms']) || !empty($body['c4'])
);

if (!$result['ok']) {
    mogh_api_fail($result['message'], 400);
}
mogh_api_ok($result['message']);
