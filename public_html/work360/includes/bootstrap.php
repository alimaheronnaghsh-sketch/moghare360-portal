<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
work360_session_start();
