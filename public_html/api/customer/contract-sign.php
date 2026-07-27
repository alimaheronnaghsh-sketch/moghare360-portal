<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function m360_contract_sign_app_root_web_path(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $root = dirname($script, 3);
    if ($root === '/' || $root === '.' || $root === '') {
        return '';
    }

    return rtrim($root, '/');
}

function m360_contract_sign_app_root_url(string $pathAndQuery): string
{
    $path = str_starts_with($pathAndQuery, '/') ? $pathAndQuery : '/' . ltrim($pathAndQuery, '/');
    $root = m360_contract_sign_app_root_web_path();

    return $root . $path;
}

function m360_contract_sign_redirect(string $pathAndQuery): never
{
    header('Location: ' . m360_contract_sign_app_root_url($pathAndQuery), true, 302);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    m360_contract_sign_redirect('/customer-intake-contract.php');
}

$token = trim((string)($_POST['token'] ?? $_POST['t'] ?? ''));
$reviewUrl = '/customer-intake-contract-review.php?t=' . rawurlencode($token);
$resolved = m360_contract_resolve_token($token);

if (!$resolved['ok'] || !is_array($resolved['contract'])) {
    m360_contract_sign_redirect($reviewUrl . '&msg=' . rawurlencode($resolved['message']) . '&ok=0');
}

$contractRow = $resolved['contract'];
if (m360_intake_contract_is_signed($contractRow)) {
    m360_contract_sign_redirect('/customer-profile.php?contract_signed=1');
}

$result = m360_contract_complete_signature(
    $contractRow,
    $token,
    trim((string)($_POST['signature_data'] ?? '')),
    isset($_POST['confirm_read']),
    isset($_POST['confirm_info']),
    isset($_POST['confirm_otp_terms']),
    trim((string)($_POST['otp_code'] ?? ''))
);

if ($result['ok']) {
    m360_contract_sign_redirect('/customer-profile.php?contract_signed=1');
}

m360_contract_sign_redirect($reviewUrl . '&msg=' . rawurlencode($result['message']) . '&ok=0');
