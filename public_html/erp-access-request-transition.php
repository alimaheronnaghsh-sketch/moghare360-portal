<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Controlled Access Request Transition Page
 *
 * Phase 2 controlled browser action prototype.
 *
 * This page validates the first controlled workflow transition preview:
 * Entity: access_request
 * Transition: DRAFT -> SUBMITTED
 * Permission concept: access_request.submit
 * CSRF form key: access_request_submit
 *
 * This page does not connect to database.
 * This page does not update workflow state.
 * This page does not insert audit/history records.
 * This page does not perform database writes.
 */

if (!function_exists('erp_access_request_transition_require_helper')) {
    function erp_access_request_transition_require_helper(string $helper_file): void
    {
        $helper_file = trim($helper_file);

        if ($helper_file === '') {
            throw new RuntimeException('ERP helper file name is required.');
        }

        $candidate_paths = [
            __DIR__ . '/../includes/' . $helper_file,
            __DIR__ . '/includes/' . $helper_file,
        ];

        foreach ($candidate_paths as $candidate_path) {
            if (is_file($candidate_path)) {
                require_once $candidate_path;
                return;
            }
        }

        throw new RuntimeException('Required ERP helper not found: ' . $helper_file);
    }
}

erp_access_request_transition_require_helper('erp-auth-context.php');
erp_access_request_transition_require_helper('erp-csrf.php');
erp_access_request_transition_require_helper('erp-permission-check.php');
erp_access_request_transition_require_helper('erp-workflow-engine.php');

const ERP_ACCESS_REQUEST_TRANSITION_FORM_KEY = 'access_request_submit';
const ERP_ACCESS_REQUEST_TRANSITION_PERMISSION = 'access_request.submit';
const ERP_ACCESS_REQUEST_TRANSITION_ENTITY = 'access_request';
const ERP_ACCESS_REQUEST_TRANSITION_FROM_STATE = 'DRAFT';
const ERP_ACCESS_REQUEST_TRANSITION_TO_STATE = 'SUBMITTED';

if (!function_exists('erp_access_request_transition_escape')) {
    function erp_access_request_transition_escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$status_message = '';
$error_message = '';
$transition_result = null;
$context = [];

try {
    $context = erp_auth_get_current_context();

    if (!is_array($context)) {
        throw new RuntimeException('ERP auth context is invalid.');
    }

    erp_auth_require_current_user();

    $request_method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($request_method === 'POST') {
        $posted_action = $_POST['transition_action'] ?? '';
        $posted_token = $_POST['csrf_token'] ?? '';

        if (!is_string($posted_action) || trim($posted_action) !== 'submit_access_request') {
            throw new RuntimeException('Invalid ERP transition action.');
        }

        if (!is_string($posted_token)) {
            throw new RuntimeException('Invalid ERP CSRF token format.');
        }

        erp_csrf_require_valid_token(ERP_ACCESS_REQUEST_TRANSITION_FORM_KEY, $posted_token);

        erp_permission_require($context, ERP_ACCESS_REQUEST_TRANSITION_PERMISSION);

        $transition_result = erp_workflow_build_transition_result(
            ERP_ACCESS_REQUEST_TRANSITION_ENTITY,
            ERP_ACCESS_REQUEST_TRANSITION_FROM_STATE,
            ERP_ACCESS_REQUEST_TRANSITION_TO_STATE
        );

        $status_message = 'Controlled transition preview approved. No database state was changed.';
    }
} catch (Throwable $exception) {
    $error_message = $exception->getMessage();
}

$csrf_token = erp_csrf_create_token(ERP_ACCESS_REQUEST_TRANSITION_FORM_KEY);

$current_user_id = '';
$current_username = '';
$current_full_name = '';

if (isset($context['user_id'])) {
    $current_user_id = (string)$context['user_id'];
} elseif (isset($context['current_user_id'])) {
    $current_user_id = (string)$context['current_user_id'];
}

if (isset($context['username']) && is_string($context['username'])) {
    $current_username = $context['username'];
}

if (isset($context['full_name']) && is_string($context['full_name'])) {
    $current_full_name = $context['full_name'];
}
?>

<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MOGHARE360 ERP - Access Request Transition Preview</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 32px;
            background: #f7f7f7;
            color: #222;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
        }

        h1 {
            margin-top: 0;
        }

        .box {
            border: 1px solid #ddd;
            padding: 16px;
            margin: 16px 0;
            background: #fafafa;
        }

        .success {
            border-color: #2e7d32;
            background: #eef8ee;
        }

        .error {
            border-color: #c62828;
            background: #fff0f0;
        }

        .blocked {
            border-color: #ef6c00;
            background: #fff7ed;
        }

        code {
            background: #eee;
            padding: 2px 5px;
        }

        button {
            padding: 10px 16px;
            cursor: pointer;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            width: 240px;
        }
    </style>
</head>
<body>
<div class="page">
    <h1>MOGHARE360 ERP - Access Request Transition Preview</h1>

    <div class="box blocked">
        <strong>Controlled Prototype Boundary</strong>
        <p>This page validates the transition preview only.</p>
        <p>No database connection, no workflow state update, no audit insert, and no history insert are performed.</p>
    </div>

    <div class="box">
        <h2>Current Prototype Actor</h2>
        <table>
            <tr>
                <th>User ID</th>
                <td><?php echo erp_access_request_transition_escape($current_user_id); ?></td>
            </tr>
            <tr>
                <th>Username</th>
                <td><?php echo erp_access_request_transition_escape($current_username); ?></td>
            </tr>
            <tr>
                <th>Full Name</th>
                <td><?php echo erp_access_request_transition_escape($current_full_name); ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Controlled Transition</h2>
        <table>
            <tr>
                <th>Entity</th>
                <td><code><?php echo erp_access_request_transition_escape(ERP_ACCESS_REQUEST_TRANSITION_ENTITY); ?></code></td>
            </tr>
            <tr>
                <th>From State</th>
                <td><code><?php echo erp_access_request_transition_escape(ERP_ACCESS_REQUEST_TRANSITION_FROM_STATE); ?></code></td>
            </tr>
            <tr>
                <th>To State</th>
                <td><code><?php echo erp_access_request_transition_escape(ERP_ACCESS_REQUEST_TRANSITION_TO_STATE); ?></code></td>
            </tr>
            <tr>
                <th>Permission</th>
                <td><code><?php echo erp_access_request_transition_escape(ERP_ACCESS_REQUEST_TRANSITION_PERMISSION); ?></code></td>
            </tr>
            <tr>
                <th>CSRF Form Key</th>
                <td><code><?php echo erp_access_request_transition_escape(ERP_ACCESS_REQUEST_TRANSITION_FORM_KEY); ?></code></td>
            </tr>
        </table>
    </div>

    <?php if ($status_message !== ''): ?>
        <div class="box success">
            <strong>Success</strong>
            <p><?php echo erp_access_request_transition_escape($status_message); ?></p>

            <?php if (is_array($transition_result)): ?>
                <table>
                    <tr>
                        <th>Result</th>
                        <td><?php echo !empty($transition_result['ok']) ? 'OK' : 'FAILED'; ?></td>
                    </tr>
                    <tr>
                        <th>Transition</th>
                        <td><?php echo erp_access_request_transition_escape((string)($transition_result['transition'] ?? '')); ?></td>
                    </tr>
                    <tr>
                        <th>Database Update</th>
                        <td>Blocked</td>
                    </tr>
                    <tr>
                        <th>Audit / History Write</th>
                        <td>Blocked</td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="box error">
            <strong>Blocked</strong>
            <p><?php echo erp_access_request_transition_escape($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2>Preview Action</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo erp_access_request_transition_escape($csrf_token); ?>">
            <input type="hidden" name="transition_action" value="submit_access_request">
            <button type="submit">Preview DRAFT to SUBMITTED Transition</button>
        </form>
    </div>
</div>
</body>
</html>

