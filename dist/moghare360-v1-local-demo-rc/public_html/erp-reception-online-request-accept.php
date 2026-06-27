<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online request reception actions (POST only).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-reception-online-requests.php');
    exit;
}

m360_reception_require_staff();
erp_csrf_require_valid(M360_RECEPTION_CSRF_PURPOSE, $_POST['erp_csrf_token'] ?? null);

$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action = strtolower(trim((string)($_POST['action'] ?? '')));

if ($requestId < 1) {
    header('Location: erp-reception-online-requests.php?msg=' . rawurlencode('شناسه درخواست نامعتبر است.') . '&ok=0');
    exit;
}

$detailUrl = 'erp-reception-online-request-detail.php?request_id=' . $requestId;
$conn = customer_core_db();

if ($conn === false) {
    header('Location: ' . $detailUrl . '&msg=' . rawurlencode('اتصال به پایگاه داده برقرار نشد.') . '&ok=0');
    exit;
}

$result = ['ok' => false, 'message' => 'اقدام نامعتبر است.'];

switch ($action) {
    case 'under_review':
        $result = m360_reception_update_status(
            $conn,
            $requestId,
            M360_ONLINE_REQ_STATUS_UNDER_REVIEW,
            M360_ONLINE_REQ_HISTORY_UNDER_REVIEW,
            'Marked under review by reception'
        );
        break;

    case 'accept':
        $result = m360_reception_update_status(
            $conn,
            $requestId,
            M360_ONLINE_REQ_STATUS_ACCEPTED,
            M360_ONLINE_REQ_HISTORY_ACCEPTED,
            'Accepted by reception'
        );
        break;

    case 'reject':
        $result = m360_reception_update_status(
            $conn,
            $requestId,
            M360_ONLINE_REQ_STATUS_REJECTED,
            M360_ONLINE_REQ_HISTORY_REJECTED,
            'Rejected by reception'
        );
        break;

    case 'convert_to_jobcard':
        $result = m360_reception_convert_to_jobcard($requestId);
        if ($result['ok'] && (int)($result['jobcard_id'] ?? 0) > 0) {
            $msg = $result['message'];
            if (!empty($result['jobcard_number'])) {
                $msg .= ' شماره: ' . $result['jobcard_number'];
            }
            header('Location: ' . $detailUrl . '&msg=' . rawurlencode($msg) . '&ok=1');
            exit;
        }
        break;

    default:
        $result = ['ok' => false, 'message' => 'اقدام نامعتبر است.'];
}

header('Location: ' . $detailUrl . '&msg=' . rawurlencode((string)$result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
