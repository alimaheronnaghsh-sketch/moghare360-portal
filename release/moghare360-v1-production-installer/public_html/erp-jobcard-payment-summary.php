<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Payment Summary (Read-Only)
 *
 * Mission 28 - SELECT aggregates only. No form. No POST. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M28_PLATFORM_OWNER_ID = 10001;
const ERP_M28_SUMMARY_ACTION = 'payment.summary.view';

/** @var array<string, string> */
const ERP_M28_PLACEHOLDER_ACTIONS = [
    'payment.create' => 'placeholder_payment_create',
    'payment.view' => 'placeholder_payment_view',
    'payment.list' => 'placeholder_payment_list',
    'payment.summary.view' => 'placeholder_payment_summary_view',
    'payment.cancel' => 'placeholder_payment_cancel',
    'payment.reverse' => 'placeholder_payment_reverse',
];

function erp_m28_summary_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m28_summary_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

/**
 * @return list<array<string, string>>
 */
function erp_m28_summary_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
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

function erp_m28_summary_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return null;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = @odbc_result($statement, 1);

    return $value === false || $value === null ? null : (string)$value;
}

function erp_m28_summary_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m28_summary_display(string $value): string
{
    return erp_m28_summary_h(trim($value) === '' ? '—' : $value);
}

function erp_m28_summary_parse_jobcard_id(): int
{
    if (!isset($_GET['jobcard_id'])) {
        return 0;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m28_summary_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M28_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M28_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

try {
    erp_m28_summary_require_first_existing(erp_m28_summary_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m28_summary_require_first_existing(erp_m28_summary_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Summary Error</title></head><body>';
    echo '<p>ERP JobCard payment summary could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');
$userId = ERP_M28_PLATFORM_OWNER_ID;
$username = '—';
$guardSummaryLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$overallOk = false;
$jobcardRow = [];
$paymentRows = [];
$selectedJobcardId = 0;
$totalReceived = '0';
$paymentCount = '0';
$outstandingPlaceholder = 'TBD';
$connection = false;

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    if (erp_auth_current_user_id() !== $userId) {
        throw new RuntimeException('Access denied.');
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        throw new RuntimeException('Access denied.');
    }

    $username = (string)($user['username'] ?? '—');

    $guardSummary = erp_m28_summary_guard_eval($connection, $userId, ERP_M28_SUMMARY_ACTION);
    $guardSummaryLabel = (string)($guardSummary['label'] ?? 'FAIL');

    if (empty($guardSummary['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $selectedJobcardId = erp_m28_summary_parse_jobcard_id();

    if ($selectedJobcardId <= 0) {
        $latestRows = erp_m28_summary_fetch_rows(
            $connection,
            'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE lifecycle_state = \'ACTIVE\' ORDER BY jobcard_id ASC'
        );

        if ($latestRows !== []) {
            $selectedJobcardId = (int)($latestRows[0]['jobcard_id'] ?? 0);
        }
    }

    if ($selectedJobcardId > 0) {
        $jobcardRows = erp_m28_summary_fetch_rows(
            $connection,
            'SELECT TOP 1
                j.jobcard_id,
                j.jobcard_number,
                j.jobcard_status,
                j.lifecycle_state,
                j.customer_id,
                c.full_name
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            WHERE j.jobcard_id = ?',
            [$selectedJobcardId]
        );

        if ($jobcardRows !== []) {
            $jobcardRow = $jobcardRows[0];
        }

        $totalReceived = erp_m28_summary_scalar(
            $connection,
            'SELECT COALESCE(SUM(payment_amount), 0)
             FROM dbo.erp_payments
             WHERE jobcard_id = ?
               AND payment_status = ?
               AND is_active = 1',
            [$selectedJobcardId, 'RECEIVED']
        ) ?? '0';

        $paymentCount = erp_m28_summary_scalar(
            $connection,
            'SELECT COUNT(*)
             FROM dbo.erp_payments
             WHERE jobcard_id = ?
               AND payment_status = ?
               AND is_active = 1',
            [$selectedJobcardId, 'RECEIVED']
        ) ?? '0';

        $paymentRows = erp_m28_summary_fetch_rows(
            $connection,
            'SELECT
                payment_id,
                payment_type,
                payment_method,
                payment_amount,
                currency_code,
                payment_status,
                received_at
            FROM dbo.erp_payments
            WHERE jobcard_id = ?
              AND is_active = 1
            ORDER BY payment_id DESC',
            [$selectedJobcardId]
        );
    }

    $overallOk = $connectionStatus === 'OK'
        && $jobcardRow !== []
        && in_array($guardSummaryLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'ERP JobCard payment summary could not be completed.';
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
    <title>Mission 28 - JobCard Payment Summary</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 240px; }
        .list-table th { width: auto; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY JOBCARD PAYMENT SUMMARY - NO WRITE</div>

    <div class="card">
        <h1>JobCard Payment Summary</h1>
        <p>Mission 28 - Calculated totals only - No balance write - No delivery release</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m28_summary_h($errorMessage) ?></p>
        <?php endif; ?>
        <p>
            <a href="erp-payment-create.php">Open create prototype</a>
            |
            <a href="erp-payment-readonly-list.php">Open payment list</a>
        </p>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m28_summary_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection</th><td><?= erp_m28_summary_h($connectionStatus) ?> — <?= erp_m28_summary_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m28_summary_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m28_summary_h($username) ?></td></tr>
                <tr><th>guard payment.summary.view</th><td><?= erp_m28_summary_h($guardSummaryLabel) ?></td></tr>
                <tr><th>Selected jobcard_id</th><td><?= erp_m28_summary_h((string)$selectedJobcardId) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($jobcardRow === []): ?>
        <div class="card">
            <p>No JobCard record found for the selected jobcard_id.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>JobCard Header</h2>
            <table>
                <tbody>
                    <tr><th>JobCard ID</th><td><?= erp_m28_summary_display($jobcardRow['jobcard_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard Number</th><td><?= erp_m28_summary_display($jobcardRow['jobcard_number'] ?? '') ?></td></tr>
                    <tr><th>JobCard Status</th><td><?= erp_m28_summary_display($jobcardRow['jobcard_status'] ?? '') ?></td></tr>
                    <tr><th>Customer</th><td><?= erp_m28_summary_display($jobcardRow['full_name'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Payment Totals (Calculated)</h2>
            <table>
                <tbody>
                    <tr><th>Payment Count (RECEIVED)</th><td><?= erp_m28_summary_h($paymentCount) ?></td></tr>
                    <tr><th>Total Received</th><td><?= erp_m28_summary_h($totalReceived) ?></td></tr>
                    <tr><th>Outstanding Balance</th><td><?= erp_m28_summary_h($outstandingPlaceholder) ?> (expected total not defined)</td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Payments for JobCard</h2>
            <?php if ($paymentRows === []): ?>
                <p>No payment rows found for this JobCard.</p>
            <?php else: ?>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Received At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentRows as $paymentRow): ?>
                            <tr>
                                <td><?= erp_m28_summary_display($paymentRow['payment_id'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['payment_type'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['payment_method'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['payment_amount'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['currency_code'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['payment_status'] ?? '') ?></td>
                                <td><?= erp_m28_summary_display($paymentRow['received_at'] ?? '') ?></td>
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
