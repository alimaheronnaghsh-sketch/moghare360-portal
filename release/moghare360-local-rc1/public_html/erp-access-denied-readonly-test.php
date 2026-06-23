<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Browser Read-Only Access Denied Test
 *
 * Mission 11 - Simulation only. No form. No write. No login replacement.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function erp_m11_browser_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m11_browser_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

try {
    erp_m11_browser_require_first_existing(
        erp_m11_browser_helper_candidates('erp-access-denied-handler.php'),
        'erp-access-denied-handler.php'
    );
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Access Denied Test Error</title></head><body>';
    echo '<p>ERP access denied test could not be loaded.</p>';
    echo '<p>Required helper files were not found in approved include paths.</p>';
    echo '</body></html>';
    exit(1);
}

function erp_m11_browser_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$phpVersion = PHP_VERSION;
$overallOk = false;
$errorMessage = '';

$actorUserId = 10001;
$actionKey = 'admin.dashboard.view';
$permissionKey = 'placeholder_admin_dashboard_view';
$targetEntity = 'admin_dashboard';
$targetId = 'local-readonly-test';
$reason = 'Missing placeholder permission in Mission 11 simulation';

$testMode = '—';
$decision = '—';
$safeMessage = '—';
$eventShapeLabel = 'FAIL';
$auditWriteLabel = 'NOT PERFORMED';
$noSensitiveErrorOk = true;

try {
    $testMode = erp_access_denied_mode();

    $event = erp_access_denied_event_shape(
        $actorUserId,
        $actionKey,
        $permissionKey,
        $targetEntity,
        $targetId,
        $reason
    );

    $decision = (string)($event['decision'] ?? '—');
    $validation = erp_access_denied_validate_event($event);
    $simulation = erp_access_denied_simulate($event);

    $safeMessage = erp_access_denied_safe_message();
    $eventShapeLabel = !empty($validation['valid']) && !empty($simulation['simulated']) ? 'OK' : 'FAIL';

    if (erp_access_denied_should_write_audit()) {
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
            break;
        }
    }

    $overallOk = $testMode === 'SIMULATION_ONLY'
        && $decision === 'DENIED'
        && $eventShapeLabel === 'OK'
        && $auditWriteLabel === 'NOT PERFORMED'
        && $noSensitiveErrorOk
        && empty($simulation['write_performed'])
        && $safeMessage === 'Access denied. You do not have permission to perform this action.';
} catch (Throwable $exception) {
    $errorMessage = 'ERP access denied test could not be completed.';
    $noSensitiveErrorOk = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Mission 11 - Access Denied Read-Only Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #1f2937;
            line-height: 1.5;
        }

        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }

        .banner {
            background: #7f1d1d;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            width: 280px;
        }

        .ok {
            color: #166534;
            font-weight: bold;
        }

        .fail {
            color: #b91c1c;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY ACCESS DENIED TEST - SIMULATION ONLY - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Browser Read-Only Access Denied Test</h1>
        <p>Mission 11 - Simulation only - No form - No write</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="card">
            <p class="fail"><?= erp_m11_browser_h($errorMessage) ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m11_browser_h($phpVersion) ?></td></tr>
                <tr><th>test mode</th><td><?= erp_m11_browser_h($testMode) ?></td></tr>
                <tr><th>actor_user_id</th><td><?= erp_m11_browser_h((string)$actorUserId) ?></td></tr>
                <tr><th>action_key</th><td><?= erp_m11_browser_h($actionKey) ?></td></tr>
                <tr><th>permission_key</th><td><?= erp_m11_browser_h($permissionKey) ?></td></tr>
                <tr><th>target_entity</th><td><?= erp_m11_browser_h($targetEntity) ?></td></tr>
                <tr><th>target_id</th><td><?= erp_m11_browser_h($targetId) ?></td></tr>
                <tr><th>decision</th><td><?= erp_m11_browser_h($decision) ?></td></tr>
                <tr><th>safe access denied message</th><td><?= erp_m11_browser_h($safeMessage) ?></td></tr>
                <tr><th>event shape</th><td class="<?= $eventShapeLabel === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m11_browser_h($eventShapeLabel) ?></td></tr>
                <tr><th>audit write</th><td><?= erp_m11_browser_h($auditWriteLabel) ?></td></tr>
                <tr><th>No sensitive error exposed</th><td class="<?= $noSensitiveErrorOk ? 'ok' : 'fail' ?>"><?= $noSensitiveErrorOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
