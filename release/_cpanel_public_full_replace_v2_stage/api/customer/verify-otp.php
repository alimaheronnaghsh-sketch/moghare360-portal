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
$otp = trim((string)($body['otp'] ?? $body['code'] ?? ''));

$result = m360_otp_verify($phone, $otp);

if (!$result['ok']) {
    m360_otp_json_fail($result['message'], 400);
}

m360_otp_json_ok($result['message'], [
    'verified' => true,
    'token' => $result['token'] ?? '',
]);
