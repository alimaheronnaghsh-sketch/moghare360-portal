<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Delivery Control — Read + Controlled Release
 *
 * Mission 30 - View delivery control; POST release when READY and allowed.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M30_PLATFORM_OWNER_ID = 10001;
const ERP_M30_CSRF_SESSION_KEY = 'm30_delivery_control_csrf';
const ERP_M30_VIEW_ACTION = 'delivery.control.view';
const ERP_M30_RELEASE_ACTION = 'delivery.control.release';

/** @var array<string, string> */
const ERP_M30_PLACEHOLDER_ACTIONS = [
    'qc.check.create' => 'placeholder_qc_check_create',
    'delivery.control.view' => 'placeholder_delivery_control_view',
    'delivery.control.release' => 'placeholder_delivery_control_release',
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
];

function erp_m30_dc_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m30_dc_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m30_dc_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m30_dc_display(string $value): string
{
    return erp_m30_dc_h(trim($value) === '' ? '—' : $value);
}

function erp_m30_dc_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function m30_dc_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m30_dc_csrf_get_token(): string
{
    m30_dc_csrf_ensure_session();

    if (empty($_SESSION[ERP_M30_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M30_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M30_CSRF_SESSION_KEY];
}

function m30_dc_csrf_validate(string $postedToken): bool
{
    m30_dc_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M30_CSRF_SESSION_KEY], $postedToken);
}

function erp_m30_dc_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

/**
 * @return list<array<string, string>>
 */
function erp_m30_dc_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m30_dc_execute($connection, $sql, $params);

    if ($statement === false) {
        return [];
    }

    $rows = [];

    while (@odbc_fetch_row($statement)) {
        $row = [];
        $columnCount = @odbc_num_fields($statement);

        if ($columnCount === false || $columnCount < 1) {
            continue;
        }

        for ($i = 1; $i <= $columnCount; $i++) {
            $name = @odbc_field_name($statement, $i);

            if ($name === false) {
                continue;
            }

            $value = @odbc_result($statement, $i);
            $row[strtolower((string)$name)] = $value === false || $value === null ? '' : (string)$value;
        }

        if ($row !== []) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function m30_dc_safe_sql_error($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    return $message === '' ? 'No ODBC error message available.' : $message;
}

/**
 * @return array<string, mixed>
 */
function erp_m30_dc_guard_eval($connection, int $userId, string $actionKey, bool $allowAdminPlaceholder = false): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $result['label'] = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $result['label'] = 'PLACEHOLDER';
        }

        return $result;
    }

    if (!isset(ERP_M30_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M30_PLATFORM_OWNER_ID || $allowAdminPlaceholder) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

function erp_m30_dc_parse_jobcard_id(): int
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = erp_m30_dc_post_string('jobcard_id');

        if ($raw !== '' && ctype_digit($raw)) {
            return (int)$raw;
        }
    }

    if (!isset($_GET['jobcard_id'])) {
        return 1;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 1;
}

function erp_m30_dc_resolve_active_jobcard($connection, int $jobcardId): ?int
{
    $rows = erp_m30_dc_fetch_rows(
        $connection,
        'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE jobcard_id = ? AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId]
    );

    return $rows === [] ? null : (int)($rows[0]['jobcard_id'] ?? 0);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m30_dc_release_delivery($connection, int $userId, int $deliveryControlId, int $jobcardId, string $oldStatus, array &$diagnostic): void
{
    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        $transactionStarted = true;

        if (erp_m30_dc_execute(
            $connection,
            'UPDATE dbo.erp_delivery_controls
             SET delivery_status = ?, released_by_user_id = ?, released_at = SYSUTCDATETIME()
             WHERE delivery_control_id = ? AND jobcard_id = ? AND delivery_status = ? AND delivery_allowed = 1',
            ['RELEASED', $userId, $deliveryControlId, $jobcardId, 'READY']
        ) === false) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        if (erp_m30_dc_execute(
            $connection,
            'INSERT INTO dbo.erp_delivery_control_history (
                delivery_control_id, jobcard_id, action_code, old_status, new_status, changed_by_user_id, change_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $deliveryControlId,
                $jobcardId,
                'DELIVERY_RELEASED',
                $oldStatus,
                'RELEASED',
                $userId,
                'Delivery released via Mission 30 prototype.',
            ]
        ) === false) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        if (!@odbc_commit($connection)) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        @odbc_autocommit($connection, true);
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
        }

        @odbc_autocommit($connection, true);

        throw $exception;
    }
}

try {
erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('m360-staff-home-helper.php'), 'm360-staff-home-helper.php');
erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('m360-case-stage-tree-helper.php'), 'm360-case-stage-tree-helper.php');
erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('m360-case-stage-header.php'), 'm360-case-stage-header.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>خطای کنترل تحویل</title><link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css"></head><body class="m360-delivery-page"><div class="m360-delivery-wrap"><section class="m360-delivery-card"><p>صفحه کنترل تحویل بارگذاری نشد.</p></section></div></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$userId = 0;
$roleCode = 'UNKNOWN';
$guardViewLabel = 'FAIL';
$guardReleaseLabel = 'FAIL';
$errorMessage = '';
$successMessage = '';
$deliveryRow = [];
$historyRows = [];
$selectedJobcardId = 1;
$deliveryControlId = 0;
$canRelease = false;
$csrfToken = '';
$connection = false;
$m360StageTree = m360_case_stage_tree_resolve(false, []);

try {
    erp_auth_context_start();
    m360_staff_home_require_session();
    m30_dc_csrf_ensure_session();

    $connection = erp_auth_create_local_odbc_connection();

    $sessionUserId = erp_auth_context_session_user_id();
    if ($sessionUserId === null || $sessionUserId <= 0) {
        throw new RuntimeException('دسترسی مجاز نیست.');
    }
    $userId = $sessionUserId;

    if (erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('دسترسی مجاز نیست.');
    }

    $companyId = (int)($_SESSION['erp_company_id'] ?? 1);
    $roleCode = m360_staff_home_resolve_role_code($connection, $userId, $companyId);
    $isDeliveryAdmin = in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN'], true)
        || erp_auth_is_system_owner($connection, $userId);

    if (!$isDeliveryAdmin) {
        http_response_code(403);
        throw new RuntimeException('دسترسی مجاز نیست.');
    }

    $guardView = erp_m30_dc_guard_eval($connection, $userId, ERP_M30_VIEW_ACTION, $isDeliveryAdmin);
    $guardRelease = erp_m30_dc_guard_eval($connection, $userId, ERP_M30_RELEASE_ACTION, $isDeliveryAdmin);
    $guardViewLabel = (string)($guardView['label'] ?? 'FAIL');
    $guardReleaseLabel = (string)($guardRelease['label'] ?? 'FAIL');

    if (empty($guardView['allowed'])) {
        throw new RuntimeException('دسترسی مجاز نیست.');
    }

    $selectedJobcardId = erp_m30_dc_parse_jobcard_id();
    $resolvedJobcardId = erp_m30_dc_resolve_active_jobcard($connection, $selectedJobcardId);

    if ($resolvedJobcardId === null) {
        throw new RuntimeException('JobCard فعال نیست یا وجود ندارد.');
    }

    $selectedJobcardId = $resolvedJobcardId;
    $csrfToken = m30_dc_csrf_get_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && erp_m30_dc_post_string('action') === 'release') {
        if (empty($guardRelease['allowed'])) {
            throw new RuntimeException('دسترسی مجاز نیست.');
        }

        if (!m30_dc_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('توکن امنیتی معتبر نیست.');
        }

        $deliveryRows = erp_m30_dc_fetch_rows(
            $connection,
            'SELECT TOP 1 delivery_control_id, delivery_status, delivery_allowed
             FROM dbo.erp_delivery_controls
             WHERE jobcard_id = ? AND is_active = 1
             ORDER BY delivery_control_id DESC',
            [$selectedJobcardId]
        );

        if ($deliveryRows === []) {
            throw new RuntimeException('رکورد کنترل تحویل یافت نشد.');
        }

        $deliveryControlId = (int)($deliveryRows[0]['delivery_control_id'] ?? 0);
        $deliveryStatus = strtoupper(trim((string)($deliveryRows[0]['delivery_status'] ?? '')));
        $deliveryAllowed = (int)($deliveryRows[0]['delivery_allowed'] ?? 0);

        if ($deliveryAllowed !== 1 || $deliveryStatus !== 'READY') {
            throw new RuntimeException('ثبت مجوز تحویل در وضعیت فعلی مجاز نیست.');
        }

        /** @var array<string, mixed> */
        $releaseDiagnostic = [];

        erp_m30_dc_release_delivery($connection, $userId, $deliveryControlId, $selectedJobcardId, $deliveryStatus, $releaseDiagnostic);
        $successMessage = 'مجوز تحویل ثبت شد.';
    }

    $deliveryRows = erp_m30_dc_fetch_rows(
        $connection,
        'SELECT TOP 1 *
         FROM dbo.erp_delivery_controls
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY delivery_control_id DESC',
        [$selectedJobcardId]
    );

    if ($deliveryRows !== []) {
        $deliveryRow = $deliveryRows[0];
        $deliveryControlId = (int)($deliveryRow['delivery_control_id'] ?? 0);

        $historyRows = erp_m30_dc_fetch_rows(
            $connection,
            'SELECT history_id, action_code, old_status, new_status, changed_by_user_id, changed_at, change_note
             FROM dbo.erp_delivery_control_history
             WHERE delivery_control_id = ?
             ORDER BY history_id',
            [$deliveryControlId]
        );

        $canRelease = (int)($deliveryRow['delivery_allowed'] ?? 0) === 1
            && strtoupper((string)($deliveryRow['delivery_status'] ?? '')) === 'READY'
            && !empty($guardRelease['allowed']);
    }

    $m360StageTree = m360_case_stage_tree_resolve($connection, [
        'jobcard_id' => $selectedJobcardId,
        'delivery_control_id' => $deliveryControlId,
    ]);
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'کنترل تحویل تکمیل نشد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>کنترل تحویل</title>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-delivery-page">
<div class="m360-delivery-wrap">
    <div class="m360-lux-hero"><h1>کنترل تحویل</h1><p>تحویل فقط زمانی مجاز است که QC، وضعیت مالی، تأیید نهایی و درخواست‌های باز بسته شده باشند.</p></div>

    <?= m360_render_case_stage_header($m360StageTree) ?>

    <div class="m360-delivery-card">
        <h2>وضعیت پرونده تحویل</h2>
        <p>
            <a class="m360-lux-link" href="erp-qc-check.php">بررسی QC</a>
            <a class="m360-lux-link" href="erp-soft-run-readiness.php?jobcard_id=<?= erp_m30_dc_h((string)$selectedJobcardId) ?>">آمادگی مسیر</a>
        </p>
        <?php if ($successMessage !== ''): ?><p class="ok"><?= erp_m30_dc_h($successMessage) ?></p><?php endif; ?>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m30_dc_h($errorMessage) ?></p><?php endif; ?>
        <p>شناسه JobCard: <?= erp_m30_dc_h((string)$selectedJobcardId) ?></p>
    </div>

    <?php if ($deliveryRow === []): ?>
        <div class="m360-delivery-card m360-lux-block"><p>رکورد کنترل تحویل یافت نشد. ابتدا باید QC ثبت شود.</p></div>
    <?php else: ?>
        <div class="m360-delivery-card">
            <h2>آخرین وضعیت کنترل تحویل</h2>
            <table>
                <tbody>
                    <tr><th>شناسه کنترل تحویل</th><td><?= erp_m30_dc_display($deliveryRow['delivery_control_id'] ?? '') ?></td></tr>
                    <tr><th>شناسه QC</th><td><?= erp_m30_dc_display($deliveryRow['qc_check_id'] ?? '') ?></td></tr>
                    <tr><th>وضعیت تحویل</th><td><?= erp_m30_dc_display($deliveryRow['delivery_status'] ?? '') ?></td></tr>
                    <tr><th>تحویل مجاز است؟</th><td><?= erp_m30_dc_display($deliveryRow['delivery_allowed'] ?? '') ?></td></tr>
                    <tr><th>علت انسداد</th><td><?= erp_m30_dc_display($deliveryRow['block_reason'] ?? '') ?></td></tr>
                    <tr><th>آزادسازی توسط</th><td><?= erp_m30_dc_display($deliveryRow['released_by_user_id'] ?? '') ?></td></tr>
                    <tr><th>زمان آزادسازی</th><td><?= erp_m30_dc_display($deliveryRow['released_at'] ?? '') ?></td></tr>
                </tbody>
            </table>
            <?php if (!$canRelease): ?>
                <p class="m360-lux-block">تحویل فعلاً مسدود است: تا عبور QC، تسویه/وضعیت مالی، تأیید نهایی و بسته شدن درخواست‌های باز، تحویل مجاز نیست.</p>
            <?php endif; ?>

            <?php if ($canRelease): ?>
                <form method="post" style="margin-top: 12px;">
                    <input type="hidden" name="csrf_token" value="<?= erp_m30_dc_h($csrfToken) ?>">
                    <input type="hidden" name="action" value="release">
                    <input type="hidden" name="jobcard_id" value="<?= erp_m30_dc_h((string)$selectedJobcardId) ?>">
                    <button type="submit">ثبت مجوز تحویل</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="m360-delivery-card">
            <h2>تاریخچه تحویل</h2>
            <?php if ($historyRows === []): ?>
                <p>رویدادی ثبت نشده است.</p>
            <?php else: ?>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>اقدام</th><th>قبلی</th><th>جدید</th><th>توسط</th><th>زمان</th><th>یادداشت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= erp_m30_dc_display($historyRow['history_id'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['action_code'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['old_status'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['new_status'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['changed_by_user_id'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['changed_at'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['change_note'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
