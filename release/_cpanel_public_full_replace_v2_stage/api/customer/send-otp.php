<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

m360_otp_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    m360_otp_json_fail('فقط POST مجاز است.', 405);
}

$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = $_POST;
}

$phone = trim((string)($body['phone'] ?? $body['mobile'] ?? ''));
$result = m360_otp_send($phone);

if (!$result['ok']) {
    m360_otp_json_fail($result['message'], 400);
}

$responseData = ['expires_in' => M360_OTP_TTL_SECONDS];
if (!empty($result['test_mode'])) {
    $responseData['test_mode'] = true;
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => $result['message'],
        'test_mode' => true,
        'data' => $responseData,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

m360_otp_json_ok($result['message'], $responseData);
