<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-intake-contracts.php');
    exit;
}

m360_intake_contract_require_staff();
erp_csrf_require_valid(M360_CONTRACT_CSRF_PURPOSE, $_POST['erp_csrf_token'] ?? null);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$onlineRequestId = isset($_POST['online_request_id']) ? (int)$_POST['online_request_id'] : 0;
$detailUrl = 'erp-intake-contract-detail.php?contract_id=';

if ($jobcardId < 1 && $onlineRequestId < 1) {
    header('Location: erp-intake-contracts.php?msg=' . rawurlencode('شناسه کارت کار یا درخواست آنلاین الزامی است.') . '&ok=0');
    exit;
}

if ($jobcardId < 1 && $onlineRequestId > 0) {
    $conn = customer_core_db();
    if ($conn !== false) {
        $req = m360_online_req_fetch_by_id($conn, $onlineRequestId);
        $jobcardId = (int)($req['converted_jobcard_id'] ?? 0);
    }
}

if ($jobcardId < 1) {
    header('Location: erp-intake-contracts.php?msg=' . rawurlencode('ابتدا درخواست باید به کارت کار تبدیل شود.') . '&ok=0');
    exit;
}

$result = m360_intake_contract_generate_for_jobcard($jobcardId, $onlineRequestId > 0 ? $onlineRequestId : null);
$cid = (int)($result['contract_id'] ?? 0);
if (!$result['ok'] && $cid < 1) {
    header('Location: erp-intake-contracts.php?msg=' . rawurlencode((string)$result['message']) . '&ok=0');
    exit;
}

$msg = (string)$result['message'];
if (!empty($result['raw_token'])) {
    $_SESSION['m360_contract_last_raw_token_' . $cid] = $result['raw_token'];
    $msg .= ' (توکن ارسال آماده است)';
}

header('Location: ' . $detailUrl . $cid . '&msg=' . rawurlencode($msg) . '&ok=1');
exit;
