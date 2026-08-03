<?php
require_once __DIR__ . '/includes/inv360-bootstrap.php';
inv360_require_login();
header('Location: dashboard.php');
exit;
