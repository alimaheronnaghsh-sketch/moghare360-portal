<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-intake-contracts.php');
    exit;
}

m360_intake_contract_require_staff();
erp_csrf_require_valid(M360_CONTRACT_CSRF_PURPOSE, $_POST['erp_csrf_token'] ?? null);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$contractId = isset($_POST['contract_id']) ? (int)$_POST['contract_id'] : 0;
$detailUrl = 'erp-intake-contract-detail.php?contract_id=' . $contractId;
$conn = customer_core_db();

if ($conn === false || $contractId < 1) {
    header('Location: erp-intake-contracts.php?msg=' . rawurlencode('قرارداد معتبر نیست.') . '&ok=0');
    exit;
}

$row = m360_intake_contract_fetch_by_id($conn, $contractId);
if ($row === null) {
    header('Location: erp-intake-contracts.php?msg=' . rawurlencode('قرارداد یافت نشد.') . '&ok=0');
    exit;
}

$rawToken = (string)($_SESSION['m360_contract_last_raw_token_' . $contractId] ?? '');
if ($rawToken === '') {
    $gen = m360_intake_contract_generate_token();
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET secure_token_hash = ?, secure_token_expires_at = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [$gen['hash'], $gen['expires_at'], $contractId]
    );
    $rawToken = $gen['raw'];
    $_SESSION['m360_contract_last_raw_token_' . $contractId] = $rawToken;
}

$url = m360_intake_contract_customer_url($rawToken);
$sms = m360_contract_send_link_sms(trim((string)$row['mobile']), $url);
if (!$sms['ok']) {
    header('Location: ' . $detailUrl . '&msg=' . rawurlencode((string)$sms['message']) . '&ok=0');
    exit;
}

m360_intake_contract_mark_sent($conn, $contractId);
$msg = 'لینک قرارداد برای مشتری ارسال شد.';
if (!empty($sms['test_mode'])) {
    $msg = 'در محیط توسعه: ' . $url;
}
header('Location: ' . $detailUrl . '&msg=' . rawurlencode($msg) . '&ok=1');
exit;
