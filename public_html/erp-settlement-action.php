<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-jobcard-close-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-final-invoice-board.php');
    exit;
}

m360_fi_require_staff();
erp_csrf_require_valid(M360_FI_CSRF, $_POST['erp_csrf_token'] ?? null);

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$settlementId = isset($_POST['settlement_id']) ? (int)$_POST['settlement_id'] : 0;
$reason = trim((string)($_POST['reason'] ?? ''));
$redirect = 'erp-settlement-detail.php?jobcard_id=' . $jobcardId;

if ($jobcardId < 1 || $action === '') {
    header('Location: erp-final-invoice-board.php?msg=' . rawurlencode('درخواست نامعتبر است.') . '&ok=0');
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $redirect . '&msg=' . rawurlencode('اتصال برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = (int)(erp_auth_current_user_id() ?? 0);

$result = ['ok' => false, 'message' => 'عملیات نامعتبر است.'];

switch ($action) {
    case 'recalculate_settlement':
        $invoice = m360_fi_fetch_active($conn, $jobcardId);
        if ($invoice === null || strtoupper((string)($invoice['invoice_status'] ?? '')) !== M360_FI_FINALIZED) {
            $result = ['ok' => false, 'message' => 'فاکتور نهایی باید نهایی‌شده باشد.'];
            break;
        }
        $finalInvoiceId = (int)($invoice['final_invoice_id'] ?? 0);
        $totalDue = (float)($invoice['total_amount'] ?? 0);
        $result = m360_settlement_recalculate($conn, $jobcardId, $finalInvoiceId, $totalDue, $userId);
        break;

    case 'mark_settled':
        if ($settlementId < 1) {
            $active = m360_settlement_fetch_active($conn, $jobcardId);
            $settlementId = (int)($active['settlement_id'] ?? 0);
        }
        $result = m360_settlement_mark_settled($conn, $jobcardId, $settlementId, $userId);
        break;

    case 'manager_release_approval':
        if ($settlementId < 1) {
            $active = m360_settlement_fetch_active($conn, $jobcardId);
            $settlementId = (int)($active['settlement_id'] ?? 0);
        }
        $result = m360_settlement_manager_release($conn, $jobcardId, $settlementId, $reason, $userId);
        break;

    case 'block_delivery':
        if ($settlementId < 1) {
            $active = m360_settlement_fetch_active($conn, $jobcardId);
            $settlementId = (int)($active['settlement_id'] ?? 0);
        }
        $result = m360_settlement_block_delivery($conn, $jobcardId, $settlementId, $reason, $userId);
        break;

    case 'release_vehicle':
        $result = m360_vehicle_release($conn, $jobcardId, $userId);
        break;

    case 'close_jobcard':
        $result = m360_jobcard_close($conn, $jobcardId, $userId);
        break;
}

header('Location: ' . $redirect . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
