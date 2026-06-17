<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1A Admin Logout Prototype
 *
 * Clears ERP-specific session keys only.
 * No database. No audit. No portal login dependency.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$erpSessionKeys = [
    'erp_user_id',
    'erp_username',
    'erp_full_name',
    'erp_is_system_owner',
    'erp_roles',
    'erp_login_time',
    'erp_last_activity',
    'erp_session_token',
];

foreach ($erpSessionKeys as $key) {
    unset($_SESSION[$key]);
}

function erp_logout_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>ERP Admin Logout Prototype</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; color: #1f2937; }
    .wrap { max-width: 420px; margin: 40px auto; }
    .card { background: #fff; border: 1px solid #d8dee4; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .warn { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; }
    .success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 10px 12px; border-radius: 8px; font-weight: bold; text-align: center; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="warn">LOCAL ERP ADMIN LOGOUT PROTOTYPE</div>
      <div class="success"><?= erp_logout_h('ERP Admin Logout OK') ?></div>
    </div>
  </div>
</body>
</html>
