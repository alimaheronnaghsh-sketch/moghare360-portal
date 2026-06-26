<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCard action handler (POST only).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-jobcard-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-reception-jobcards.php');
    exit;
}

m360_reception_jobcard_require_staff();
erp_csrf_require_valid(M360_RECEPTION_JOBCARD_CSRF_PURPOSE, $_POST['erp_csrf_token'] ?? null);

$jobcardId = isset($_POST['jobcard_id']) ? (int)$_POST['jobcard_id'] : 0;
$action = strtolower(trim((string)($_POST['action'] ?? '')));
$detailUrl = 'erp-reception-jobcard-detail.php?jobcard_id=' . max(0, $jobcardId);

if ($jobcardId < 1) {
    header('Location: erp-reception-jobcards.php?msg=' . rawurlencode('شناسه کارت کار نامعتبر است.') . '&ok=0');
    exit;
}

$allowedActions = array_keys(m360_jobcard_workflow_history_event_map());
$allowedActions[] = 'manager_override_contract_gate';
if (!in_array($action, $allowedActions, true)) {
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
    'customer_complaint' => $_POST['customer_complaint'] ?? '',
    'reception_notes' => $_POST['reception_notes'] ?? '',
    'initial_inspection_notes' => $_POST['initial_inspection_notes'] ?? '',
    'override_reason' => $_POST['override_reason'] ?? '',
];

$result = m360_reception_jobcard_apply_action($conn, $jobcardId, $action, $payload, (int)$userId);

header('Location: ' . $detailUrl . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
exit;
