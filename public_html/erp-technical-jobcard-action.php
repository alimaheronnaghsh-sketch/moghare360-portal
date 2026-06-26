<?php
declare(strict_types=1);

/**
 * MOGHARE360 P3 — Technical JobCard action handler (POST only).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-technical-board.php');
    exit;
}

m360_technical_require_staff();
erp_csrf_require_valid(M360_TECHNICAL_CSRF_PURPOSE, $_POST['erp_csrf_token'] ?? null);

$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$action = strtolower(trim((string)($_POST['action'] ?? '')));
$detailUrl = 'erp-technical-jobcard-detail.php?jobcard_id=' . max(0, $jobcardId);

if ($jobcardId < 1) {
    header('Location: erp-technical-board.php?msg=' . rawurlencode('شناسه کارت کار نامعتبر است.') . '&ok=0');
    exit;
}

$allowed = array_keys(m360_technician_workflow_history_event_map());
if (!in_array($action, $allowed, true)) {
    header('Location: ' . $detailUrl . '&msg=' . rawurlencode('عملیات نامعتبر است.') . '&ok=0');
    exit;
}

$conn = customer_core_db();
if ($conn === false) {
    header('Location: ' . $detailUrl . '&msg=' . rawurlencode('اتصال به پایگاه داده برقرار نشد.') . '&ok=0');
    exit;
}

erp_auth_context_start();
$userId = erp_auth_current_user_id() ?? 0;

$payload = [
    'technician_user_id' => $_POST['technician_user_id'] ?? '',
    'technician_notes' => $_POST['technician_notes'] ?? '',
    'diagnosis_summary' => $_POST['diagnosis_summary'] ?? '',
    'operation_title' => $_POST['operation_title'] ?? '',
    'operation_description' => $_POST['operation_description'] ?? '',
    'service_operation_id' => $_POST['service_operation_id'] ?? '',
];

$result = m360_technical_apply_action($conn, $jobcardId, $action, $payload, (int)$userId);

header('Location: ' . $detailUrl . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
