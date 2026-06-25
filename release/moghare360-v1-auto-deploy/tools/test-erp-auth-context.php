<?php
/**
 * MOGHARE360 ERP Auth Context CLI Test
 *
 * Mission 8 - CLI test only. SELECT only. No writes.
 */

declare(strict_types=1);

function erp_m08_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m08_cli_helper_candidates(string $fileName): array
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

erp_m08_cli_require_first_existing(
    erp_m08_cli_helper_candidates('erp-auth-context.php'),
    'erp-auth-context.php'
);

$overallOk = true;
$failures = [];

$userId = 10001;
$username = 'unknown';
$rolesText = '';
$permissionCount = 0;
$isSystemOwnerOk = false;
$canApplyOk = false;
$canApproveOk = false;
$exceptionMessage = '';
$connection = false;

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        $overallOk = false;
        $failures[] = 'user load failed';
    } else {
        $username = (string)($user['username'] ?? 'unknown');

        if ($username !== 'mahin.paradigm.owner') {
            $overallOk = false;
            $failures[] = 'username mismatch';
        }
    }

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = implode(', ', $roleKeys);

    if (!in_array('owner', $roleKeys, true) || !in_array('system_admin', $roleKeys, true)) {
        $overallOk = false;
        $failures[] = 'roles mismatch';
    }

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    if ($permissionCount <= 0) {
        $overallOk = false;
        $failures[] = 'permissions empty';
    }

    $isSystemOwnerOk = erp_auth_is_system_owner($connection, $userId);

    if (!$isSystemOwnerOk) {
        $overallOk = false;
        $failures[] = 'is_system_owner failed';
    }

    $canApplyOk = erp_auth_can($connection, $userId, 'access.request.apply');
    $canApproveOk = erp_auth_can($connection, $userId, 'access.request.approve');

    if (!$canApplyOk) {
        $overallOk = false;
        $failures[] = 'access.request.apply failed';
    }

    if (!$canApproveOk) {
        $overallOk = false;
        $failures[] = 'access.request.approve failed';
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

echo 'M08 CLI AUTH CONTEXT TEST' . PHP_EOL;
echo 'User: ' . $userId . ' / ' . $username . PHP_EOL;
echo 'Roles: ' . $rolesText . PHP_EOL;
echo 'Permissions loaded: ' . $permissionCount . PHP_EOL;
echo 'is_system_owner: ' . ($isSystemOwnerOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'can access.request.apply: ' . ($canApplyOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'can access.request.approve: ' . ($canApproveOk ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL;
    echo $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
