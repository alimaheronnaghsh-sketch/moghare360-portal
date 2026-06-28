<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

erp_auth_context_start();
foreach (erp_auth_context_session_keys() as $key) {
    unset($_SESSION[$key]);
}
unset($_SESSION['erp_company_id'], $_SESSION['erp_is_owner']);

header('Location: staff-login.php');
exit;
