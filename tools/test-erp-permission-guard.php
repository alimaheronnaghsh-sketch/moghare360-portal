<?php
/**
 * MOGHARE360 ERP Permission Guard CLI Test
 *
 * Mission 10 - CLI test only. Read-only guard evaluation. No writes.
 */

declare(strict_types=1);

function erp_m10_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m10_cli_helper_candidates(string $fileName): array
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

function erp_m10_cli_guard_status(array $result): string
{
    if (!empty($result['placeholder'])) {
        return 'PLACEHOLDER';
    }

    return !empty($result['allowed']) ? 'OK' : 'FAIL';
}

erp_m10_cli_require_first_existing(
    erp_m10_cli_helper_candidates('erp-auth-context.php'),
    'erp-auth-context.php'
);

erp_m10_cli_require_first_existing(
    erp_m10_cli_helper_candidates('erp-permission-guard.php'),
    'erp-permission-guard.php'
);

$userId = 10001;
$rolesText = '';
$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$requiredRealActions = [
    'access.request.approve',
    'access.request.apply',
    'access.request.view',
    'access.request.list',
    'admin.workflow.viewer.view',
];

$guardResults = [];

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = implode(', ', $roleKeys);

    if (!in_array('owner', $roleKeys, true) || !in_array('system_admin', $roleKeys, true)) {
        $overallOk = false;
        $failures[] = 'roles mismatch';
    }

    foreach (array_keys(erp_guard_action_map()) as $actionKey) {
        $guardResults[$actionKey] = erp_guard_action($connection, $userId, $actionKey);
    }

    foreach ($requiredRealActions as $actionKey) {
        $result = $guardResults[$actionKey] ?? null;

        if ($result === null || empty($result['allowed'])) {
            $overallOk = false;
            $failures[] = $actionKey . ' failed';
        }
    }

    $dashboardResult = $guardResults['admin.dashboard.view'] ?? null;

    if ($dashboardResult === null || empty($dashboardResult['placeholder'])) {
        $overallOk = false;
        $failures[] = 'admin.dashboard.view placeholder not documented';
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'exception during test';
    $exceptionMessage = $exception->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

echo 'M10 PERMISSION GUARD TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;

foreach ($requiredRealActions as $actionKey) {
    $status = erp_m10_cli_guard_status($guardResults[$actionKey] ?? ['allowed' => false]);
    echo $actionKey . ' = ' . $status . PHP_EOL;
}

$dashboardStatus = erp_m10_cli_guard_status($guardResults['admin.dashboard.view'] ?? ['allowed' => false]);
echo 'admin.dashboard.view = ' . ($dashboardStatus === 'PLACEHOLDER' ? 'PLACEHOLDER' : 'DOCUMENTED') . PHP_EOL;
echo 'No write performed = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL;
    echo $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
