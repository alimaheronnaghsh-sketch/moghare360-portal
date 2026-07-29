<?php
require_once __DIR__ . '/includes/inv360-bootstrap.php';
inv360_logout();
header('Location: login.php');
exit;
