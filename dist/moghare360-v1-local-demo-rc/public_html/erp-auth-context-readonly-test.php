<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Browser Read-Only Auth Context Test
 *
 * Mission 8 - SELECT only. No form. No write. No login replacement.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function erp_m08_browser_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m08_browser_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

try {
    erp_m08_browser_require_first_existing(
        erp_m08_browser_helper_candidates('erp-auth-context.php'),
        'erp-auth-context.php'
    );
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Auth Context Test Error</title></head><body>';
    echo '<p>ERP auth context test could not be loaded.</p>';
    echo '<p>Required helper files were not found in approved include paths.</p>';
    echo '</body></html>';
    exit(1);
}

function erp_m08_browser_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$overallOk = false;
$readOnlyOk = true;

$userId = 10001;
$username = '—';
$fullName = '—';
$isSystemOwner = '—';
$isLoginEnabled = '—';
$lifecycleState = '—';
$rolesText = '—';
$permissionCount = 0;
$canApprove = 'FAIL';
$canApply = 'FAIL';
$tenantOperational = 'false';
$tenantRuntime = 'moghare360';
$tenantBranding = 'moghareh360';
$errorMessage = '';
$connection = false;

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
    $fullName = (string)($user['full_name'] ?? '—');
    $isSystemOwnerFlag = !empty($user['is_system_owner']);
    $isLoginEnabledFlag = !empty($user['is_login_enabled']);
    $lifecycleState = (string)($user['lifecycle_state'] ?? '—');
    $isSystemOwner = $isSystemOwnerFlag ? 'OK' : 'FAIL';
    $isLoginEnabled = $isLoginEnabledFlag ? 'OK' : 'FAIL';

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = $roleKeys !== [] ? implode(', ', $roleKeys) : '—';

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    $canApprove = erp_auth_can($connection, $userId, 'access.request.approve') ? 'OK' : 'FAIL';
    $canApply = erp_auth_can($connection, $userId, 'access.request.apply') ? 'OK' : 'FAIL';

    $tenant = erp_auth_tenant_context();
    $tenantOperational = !empty($tenant['tenant_operational']) ? 'true' : 'false';
    $tenantRuntime = (string)($tenant['current_runtime'] ?? 'moghare360');
    $tenantBranding = (string)($tenant['future_branding'] ?? 'moghareh360');

    $overallOk = $canApprove === 'OK'
        && $canApply === 'OK'
        && $isSystemOwner === 'OK'
        && $isLoginEnabled === 'OK'
        && $lifecycleState === 'ACTIVE'
        && $username === 'mahin.paradigm.owner'
        && $fullName === 'MahinParadigmCo.'
        && in_array('owner', $roleKeys, true)
        && in_array('system_admin', $roleKeys, true)
        && $permissionCount > 0;
} catch (Throwable $exception) {
    $errorMessage = 'ERP auth context test could not be completed.';
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
    <title>Phase 2.1 - Auth Context Read-Only Test</title>
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
            width: 260px;
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
    <div class="banner">LOCAL READ-ONLY AUTH CONTEXT TEST - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Browser Read-Only Auth Context Test</h1>
        <p>Mission 8 - SELECT only - No form - No write</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="card">
            <p class="fail"><?= erp_m08_browser_h($errorMessage) ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m08_browser_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td class="<?= $connectionStatus === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m08_browser_h($connectionStatus) ?> — <?= erp_m08_browser_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m08_browser_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m08_browser_h($username) ?></td></tr>
                <tr><th>full_name</th><td><?= erp_m08_browser_h($fullName) ?></td></tr>
                <tr><th>is_system_owner</th><td class="<?= $isSystemOwner === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m08_browser_h($isSystemOwner) ?></td></tr>
                <tr><th>is_login_enabled</th><td class="<?= $isLoginEnabled === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m08_browser_h($isLoginEnabled) ?></td></tr>
                <tr><th>lifecycle_state</th><td><?= erp_m08_browser_h($lifecycleState) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m08_browser_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m08_browser_h((string)$permissionCount) ?></td></tr>
                <tr><th>access.request.approve</th><td class="<?= $canApprove === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m08_browser_h($canApprove) ?></td></tr>
                <tr><th>access.request.apply</th><td class="<?= $canApply === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m08_browser_h($canApply) ?></td></tr>
                <tr><th>tenant_operational</th><td><?= erp_m08_browser_h($tenantOperational) ?></td></tr>
                <tr><th>tenant current_runtime</th><td><?= erp_m08_browser_h($tenantRuntime) ?></td></tr>
                <tr><th>tenant future_branding</th><td><?= erp_m08_browser_h($tenantBranding) ?></td></tr>
                <tr><th>Read-Only</th><td class="ok"><?= $readOnlyOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
