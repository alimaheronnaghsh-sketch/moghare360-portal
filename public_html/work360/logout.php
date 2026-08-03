<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_logout();
header('Location: login.php');
exit;
