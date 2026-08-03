<?php
require_once __DIR__ . '/includes/p360-hr-central-bridge.php';
// Legacy PeopleOS self-service entry now bridges to Central ERP cartable.
p360hr_require_central_login();
if (p360hr_must_change_password()) {
    header('Location: my-password.php?forced=1');
    exit;
}
header('Location: my-cartable.php');
exit;
