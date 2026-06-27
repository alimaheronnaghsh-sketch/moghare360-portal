<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-final-invoice-board.php');
    exit;
}

m360_fi_require_staff();
erp_csrf_require_valid(M360_FI_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$invoiceId = isset($_POST['final_invoice_id']) ? (int)$_POST['final_invoice_id'] : 0;

if ($jobcardId < 1 && $invoiceId < 1) {
    header('Location: erp-final-invoice-board.php?msg=' . rawurlencode('درخواست نامعتبر است.') . '&ok=0');
    exit;
}

if ($action === '') {
    header('Location: erp-final-invoice-board.php?msg=' . rawurlencode('عملیات مشخص نشده است.') . '&ok=0');
    exit;
}

$redirect = $jobcardId > 0
    ? 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId
    : 'erp-final-invoice-detail.php?final_invoice_id=' . $invoiceId;
if ($jobcardId > 0 && $invoiceId > 0) {
    $redirect .= '&final_invoice_id=' . $invoiceId;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

if ($jobcardId < 1 && $invoiceId > 0) {
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv !== null) {
        $jobcardId = (int)($inv['jobcard_id'] ?? 0);
        $redirect = 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId . '&final_invoice_id=' . $invoiceId;
    }
}

if ($jobcardId < 1) {
    header('Location: erp-final-invoice-board.php?msg=' . rawurlencode('کارت کار یافت نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = (int)(erp_auth_current_user_id() ?? 0);

$result = m360_fi_apply_action($conn, $action, $userId, $jobcardId, $invoiceId > 0 ? $invoiceId : null, $_POST);

if ($result['ok'] && (int)($result['final_invoice_id'] ?? 0) > 0) {
    $newId = (int)$result['final_invoice_id'];
    $redirect = 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId . '&final_invoice_id=' . $newId;
} elseif ($invoiceId < 1) {
    $active = m360_fi_fetch_active($conn, $jobcardId);
    if ($active !== null) {
        $redirect = 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId . '&final_invoice_id=' . (int)$active['final_invoice_id'];
    }
}

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
