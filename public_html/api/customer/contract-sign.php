<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: customer-intake-contract.php');
    exit;
}

$token = trim((string)($_POST['token'] ?? ''));
$signUrl = 'customer-intake-contract-sign.php?token=' . rawurlencode($token);
$resolved = m360_contract_resolve_token($token);

if (!$resolved['ok'] || !is_array($resolved['contract'])) {
    header('Location: customer-intake-contract.php?token=' . rawurlencode($token) . '&msg=' . rawurlencode($resolved['message']) . '&ok=0');
    exit;
}

$result = m360_contract_complete_signature(
    $resolved['contract'],
    $token,
    trim((string)($_POST['signature_data'] ?? '')),
    isset($_POST['confirm_read']),
    isset($_POST['confirm_info']),
    isset($_POST['confirm_otp_terms']),
    trim((string)($_POST['otp_code'] ?? ''))
);

header('Location: ' . $signUrl . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
