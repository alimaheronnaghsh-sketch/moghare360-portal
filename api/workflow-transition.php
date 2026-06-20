<?php
/**
 * MOGHARE360 ERP Workflow Transition API
 *
 * Phase 1A controlled workflow state transition endpoint.
 * JSON output only. Safe messages only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-workflow-engine.php';
require_once __DIR__ . '/../includes/erp-auth-helper.php';
require_once __DIR__ . '/../includes/erp-permission-helper.php';
require_once __DIR__ . '/../includes/erp-audit-helper.php';

header('Content-Type: application/json; charset=UTF-8');

function erp_wt_respond(bool $success, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function erp_wt_safe_action(?string $action): string
{
    return strtoupper(trim((string)$action));
}

function erp_wt_parse_request_id(): ?int
{
    if (!isset($_POST['request_id'])) {
        return null;
    }

    $value = trim((string)$_POST['request_id']);

    if ($value === '' || !ctype_digit($value)) {
        return null;
    }

    $requestId = (int)$value;

    if ($requestId <= 0) {
        return null;
    }

    return $requestId;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    erp_wt_respond(false, 'Invalid workflow request.', 405);
}

if (!erp_auth_is_logged_in()) {
    erp_wt_respond(false, 'ERP access denied.', 403);
}

if (!erp_permission_has_any_role(['owner', 'system_admin'])) {
    erp_wt_respond(false, 'ERP access denied.', 403);
}

$requestId = erp_wt_parse_request_id();
$action = erp_wt_safe_action($_POST['action'] ?? '');

if ($requestId === null || $action === '') {
    erp_wt_respond(false, 'Invalid workflow request.', 400);
}

$actionMap = [
    'SUBMIT' => [
        'from' => 'DRAFT',
        'to' => 'SUBMITTED',
    ],
    'APPROVE' => [
        'from' => 'UNDER_REVIEW',
        'to' => 'APPROVED',
    ],
    'REJECT' => [
        'from' => 'UNDER_REVIEW',
        'to' => 'REJECTED',
    ],
    'CANCEL' => [
        'from' => null,
        'to' => 'CANCELLED',
    ],
    'APPLY' => [
        'from' => 'APPROVED',
        'to' => 'APPLIED',
    ],
];

if (!isset($actionMap[$action])) {
    erp_wt_respond(false, 'Invalid workflow request.', 400);
}

$currentUser = erp_auth_current_user();
$userId = isset($currentUser['user_id']) ? (int)$currentUser['user_id'] : 0;

if ($userId <= 0) {
    erp_wt_respond(false, 'ERP access denied.', 403);
}

$engine = new ERP_Workflow_Engine();
$currentState = $engine->getNextState($requestId);

if ($currentState === false) {
    erp_wt_respond(false, 'Workflow transition could not be completed.', 404);
}

$targetState = $actionMap[$action]['to'];
$expectedFrom = $actionMap[$action]['from'];

if ($expectedFrom !== null && $currentState !== $expectedFrom) {
    erp_wt_respond(false, 'Workflow transition could not be completed.', 409);
}

if (!$engine->validateTransition((string)$currentState, $targetState)) {
    erp_wt_respond(false, 'Workflow transition could not be completed.', 409);
}

$userRoles = erp_permission_user_roles();
$canTransition = false;

foreach ($userRoles as $role) {
    if ($engine->canUserTransition($userId, (string)$role, (string)$currentState, $targetState)) {
        $canTransition = true;
        break;
    }
}

if (!$canTransition) {
    erp_wt_respond(false, 'ERP access denied.', 403);
}

$comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : null;

if ($comment === '') {
    $comment = null;
}

$result = $engine->applyTransition($requestId, $targetState, $userId, $comment);

if (!$result) {
    erp_wt_respond(false, 'Workflow transition could not be completed.', 500);
}

erp_wt_respond(true, 'Workflow transition completed.', 200);
