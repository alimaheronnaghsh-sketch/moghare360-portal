<?php
/**
 * MOGHARE360 ERP Protected Test Page
 *
 * Phase 1A protected page test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/erp-auth-helper.php';

erp_auth_require_login();

$currentUser = erp_auth_current_user();
erp_auth_touch_activity();

function erp_safe_text(mixed $value): string
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

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>MOGHARE360 ERP Protected Test</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            margin: 40px;
            background: #f7f7f7;
            color: #222;
        }

        .box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
            max-width: 760px;
            margin: 0 auto;
            border-radius: 8px;
        }

        h1 {
            margin-top: 0;
            font-size: 22px;
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

        .status {
            font-weight: bold;
            color: #0a7a28;
            margin-top: 12px;
        }

        .links {
            margin-top: 20px;
        }

        .links a {
            margin-left: 12px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>ERP Protected Page OK</h1>

        <div class="status">
            ERP session validation passed.
        </div>

        <table>
            <tr>
                <th>user_id</th>
                <td><?php echo erp_safe_text($currentUser['user_id'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>username</th>
                <td><?php echo erp_safe_text($currentUser['username'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>full_name</th>
                <td><?php echo erp_safe_text($currentUser['full_name'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>is_system_owner</th>
                <td><?php echo erp_safe_text($currentUser['is_system_owner'] ?? false); ?></td>
            </tr>
            <tr>
                <th>roles</th>
                <td><?php echo erp_safe_text($currentUser['roles'] ?? []); ?></td>
            </tr>
            <tr>
                <th>login_time</th>
                <td><?php echo erp_safe_text($currentUser['login_time'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>last_activity</th>
                <td><?php echo erp_safe_text($currentUser['last_activity'] ?? ''); ?></td>
            </tr>
        </table>

        <div class="links">
            <a href="erp-admin-logout.php">ERP Logout</a>
            <a href="erp-admin-login.php">ERP Login</a>
        </div>
    </div>
</body>
</html>
