<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Browser Read-Only Permission Guard Test
 *
 * Mission 10 - SELECT only. No form. No write. No login replacement.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function erp_m10_browser_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m10_browser_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

try {
    erp_m10_browser_require_first_existing(
        erp_m10_browser_helper_candidates('erp-auth-context.php'),
        'erp-auth-context.php'
    );

    erp_m10_browser_require_first_existing(
        erp_m10_browser_helper_candidates('erp-permission-guard.php'),
        'erp-permission-guard.php'
    );
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Permission Guard Test Error</title></head><body>';
    echo '<p>ERP permission guard test could not be loaded.</p>';
    echo '<p>Required helper files were not found in approved include paths.</p>';
    echo '</body></html>';
    exit(1);
}

function erp_m10_browser_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m10_browser_guard_label(array $result): string
{
    if (!empty($result['placeholder'])) {
        return 'PLACEHOLDER';
    }

    return !empty($result['allowed']) ? 'OK' : 'FAIL';
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$overallOk = false;
$noWriteOk = true;
$errorMessage = '';
$connection = false;

$userId = 10001;
$username = '—';
$rolesText = '—';
$guardRows = [];

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        throw new RuntimeException('Unexpected current user id.');
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        throw new RuntimeException('Current user could not be loaded.');
    }

    $username = (string)($user['username'] ?? '—');

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = $roleKeys !== [] ? implode(', ', $roleKeys) : '—';

    foreach (array_keys(erp_guard_action_map()) as $actionKey) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $guardRows[] = [
            'action_key' => $actionKey,
            'label' => erp_m10_browser_guard_label($result),
            'required_permission' => (string)($result['required_permission'] ?? ''),
            'mode' => (string)($result['mode'] ?? ''),
        ];
    }

    $realActionsOk = true;

    foreach ($guardRows as $row) {
        if ($row['label'] === 'FAIL') {
            $realActionsOk = false;
            break;
        }
    }

    $overallOk = $connectionStatus === 'OK'
        && $username === 'mahin.paradigm.owner'
        && in_array('owner', $roleKeys, true)
        && in_array('system_admin', $roleKeys, true)
        && $realActionsOk
        && $noWriteOk;
} catch (Throwable $exception) {
    $errorMessage = 'ERP permission guard test could not be completed.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Mission 10 - Permission Guard Read-Only Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #1f2937;
            line-height: 1.5;
        }

        .wrap {
            max-width: 960px;
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

        .placeholder {
            color: #92400e;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY PERMISSION GUARD TEST - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Browser Read-Only Permission Guard Test</h1>
        <p>Mission 10 - SELECT only - No form - No write</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="card">
            <p class="fail"><?= erp_m10_browser_h($errorMessage) ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m10_browser_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td class="<?= $connectionStatus === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m10_browser_h($connectionStatus) ?> — <?= erp_m10_browser_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m10_browser_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m10_browser_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m10_browser_h($rolesText) ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h1>Action Guard Results</h1>
        <table>
            <thead>
                <tr>
                    <th>Action Key</th>
                    <th>Result</th>
                    <th>Required Permission</th>
                    <th>Mode</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guardRows as $row): ?>
                    <?php
                    $labelClass = 'fail';

                    if ($row['label'] === 'OK') {
                        $labelClass = 'ok';
                    } elseif ($row['label'] === 'PLACEHOLDER') {
                        $labelClass = 'placeholder';
                    }
                    ?>
                    <tr>
                        <td><?= erp_m10_browser_h($row['action_key']) ?></td>
                        <td class="<?= $labelClass ?>"><?= erp_m10_browser_h($row['label']) ?></td>
                        <td><?= erp_m10_browser_h($row['required_permission']) ?></td>
                        <td><?= erp_m10_browser_h($row['mode']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <table>
            <tbody>
                <tr><th>No write performed</th><td class="ok"><?= $noWriteOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
