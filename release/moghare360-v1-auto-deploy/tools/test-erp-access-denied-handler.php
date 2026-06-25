<?php
/**
 * MOGHARE360 ERP Access Denied Handler CLI Test
 *
 * Mission 11 - CLI simulation test only. No writes.
 */

declare(strict_types=1);

function erp_m11_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m11_cli_helper_candidates(string $fileName): array
{
    $bases = [
        __DIR__,
        dirname(__DIR__),
        dirname(__DIR__, 2),
    ];

    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;

    $unique = [];

    foreach ($candidates as $candidate) {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);

        if (!in_array($normalized, $unique, true)) {
            $unique[] = $normalized;
        }
    }

    return $unique;
}

erp_m11_cli_require_first_existing(
    erp_m11_cli_helper_candidates('erp-access-denied-handler.php'),
    'erp-access-denied-handler.php'
);

$overallOk = true;
$failures = [];
$exceptionMessage = '';

$actorUserId = 10001;
$actionKey = 'admin.dashboard.view';
$permissionKey = 'placeholder_admin_dashboard_view';
$targetEntity = 'admin_dashboard';
$targetId = 'local-readonly-test';
$reason = 'Missing placeholder permission in Mission 11 simulation';

$mode = '';
$decision = '';
$safeMessageOk = false;
$eventShapeOk = false;
$auditWriteLabel = 'NOT PERFORMED';
$noSensitiveErrorOk = true;

try {
    $mode = erp_access_denied_mode();

    if ($mode !== 'SIMULATION_ONLY') {
        $overallOk = false;
        $failures[] = 'mode mismatch';
    }

    $event = erp_access_denied_event_shape(
        $actorUserId,
        $actionKey,
        $permissionKey,
        $targetEntity,
        $targetId,
        $reason
    );

    $decision = (string)($event['decision'] ?? '');

    if ($decision !== 'DENIED') {
        $overallOk = false;
        $failures[] = 'decision mismatch';
    }

    $validation = erp_access_denied_validate_event($event);
    $eventShapeOk = !empty($validation['valid']);

    if (!$eventShapeOk) {
        $overallOk = false;
        $failures[] = 'event shape invalid';
    }

    $simulation = erp_access_denied_simulate($event);

    if (empty($simulation['simulated'])) {
        $overallOk = false;
        $failures[] = 'simulation failed';
    }

    if (!empty($simulation['write_performed'])) {
        $overallOk = false;
        $failures[] = 'unexpected write_performed';
    }

    $safeMessage = erp_access_denied_safe_message();
    $expectedSafeMessage = 'Access denied. You do not have permission to perform this action.';
    $safeMessageOk = $safeMessage === $expectedSafeMessage
        && ($simulation['safe_message'] ?? '') === $expectedSafeMessage;

    if (!$safeMessageOk) {
        $overallOk = false;
        $failures[] = 'safe message mismatch';
    }

    if (erp_access_denied_should_write_audit()) {
        $overallOk = false;
        $failures[] = 'audit write enabled unexpectedly';
        $auditWriteLabel = 'PERFORMED';
    }

    $sensitivePatterns = [
        'SQLSTATE',
        'stack trace',
        'password_hash',
        'Exception in',
        'odbc_',
    ];

    $outputSample = json_encode($simulation, JSON_UNESCAPED_UNICODE);

    foreach ($sensitivePatterns as $pattern) {
        if (stripos($outputSample, $pattern) !== false) {
            $noSensitiveErrorOk = false;
            $overallOk = false;
            $failures[] = 'sensitive error exposed';
            break;
        }
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'exception during test';
    $exceptionMessage = $exception->getMessage();
    $noSensitiveErrorOk = false;
}

echo 'M11 ACCESS DENIED HANDLER TEST' . PHP_EOL;
echo 'Mode: ' . $mode . PHP_EOL;
echo 'actor_user_id = ' . $actorUserId . PHP_EOL;
echo 'action_key = ' . $actionKey . PHP_EOL;
echo 'permission_key = ' . $permissionKey . PHP_EOL;
echo 'decision = ' . $decision . PHP_EOL;
echo 'safe message = ' . ($safeMessageOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'event shape = ' . ($eventShapeOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'audit write = ' . $auditWriteLabel . PHP_EOL;
echo 'No sensitive error exposed = ' . ($noSensitiveErrorOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL;
    echo $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
