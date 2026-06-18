<?php
/**
 * MOGHARE360 ERP Admin Dashboard
 *
 * Phase 1A protected read-only dashboard.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/erp-auth-helper.php';

erp_auth_require_login();

$currentUser = erp_auth_current_user();
erp_auth_touch_activity();

function erp_dashboard_safe_text(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        return htmlspecialchars(implode(', ', array_map('strval', $value)), ENT_QUOTES, 'UTF-8');
    }

    if ($value === null) {
        return '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$safeLinks = [
    [
        'label' => 'ERP Admin Readonly Dashboard',
        'href' => 'erp-admin-readonly-dashboard.php',
        'description' => 'Read-only ERP admin diagnostics',
    ],
    [
        'label' => 'ERP Access Lifecycle Readonly Dashboard',
        'href' => 'erp-access-lifecycle-readonly-dashboard.php',
        'description' => 'Read-only access lifecycle diagnostics',
    ],
    [
        'label' => 'ERP Bootstrap Status',
        'href' => 'erp-bootstrap-status.php',
        'description' => 'Read-only bootstrap owner status',
    ],
    [
        'label' => 'ERP Protected Test Page',
        'href' => 'erp-admin-protected-test.php',
        'description' => 'Protected page validation test',
    ],
    [
        'label' => 'ERP Logout',
        'href' => 'erp-admin-logout.php',
        'description' => 'Clear ERP-specific session keys',
    ],
];

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>MOGHARE360 ERP Admin Dashboard</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            margin: 40px;
            background: #f7f7f7;
            color: #222;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        .box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 18px;
        }

        .status {
            font-weight: bold;
            color: #0a7a28;
            margin-top: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }

        th {
            background: #f0f0f0;
            width: 220px;
        }

        .links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .link-card {
            border: 1px solid #ddd;
            background: #fafafa;
            padding: 14px;
            border-radius: 6px;
        }

        .link-card a {
            font-weight: bold;
            text-decoration: none;
        }

        .link-card p {
            margin: 8px 0 0;
            color: #555;
        }

        .notice {
            color: #555;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h1>ERP Admin Dashboard OK</h1>

            <div class="status">
                ERP dashboard session validation passed.
            </div>

            <p class="notice">
                This dashboard is protected and read-only. It does not perform database operations, SQL execution, audit writes, or write-enabled actions.
            </p>
        </div>

        <div class="box">
            <h2>Current ERP User</h2>

            <table>
                <tr>
                    <th>user_id</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['user_id'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>username</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['username'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>full_name</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['full_name'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>is_system_owner</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['is_system_owner'] ?? false); ?></td>
                </tr>
                <tr>
                    <th>roles</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['roles'] ?? []); ?></td>
                </tr>
                <tr>
                    <th>login_time</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['login_time'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>last_activity</th>
                    <td><?php echo erp_dashboard_safe_text($currentUser['last_activity'] ?? ''); ?></td>
                </tr>
            </table>
        </div>

        <div class="box">
            <h2>Safe ERP Navigation</h2>

            <div class="links">
                <?php foreach ($safeLinks as $link): ?>
                    <div class="link-card">
                        <a href="<?php echo erp_dashboard_safe_text($link['href']); ?>">
                            <?php echo erp_dashboard_safe_text($link['label']); ?>
                        </a>
                        <p><?php echo erp_dashboard_safe_text($link['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
