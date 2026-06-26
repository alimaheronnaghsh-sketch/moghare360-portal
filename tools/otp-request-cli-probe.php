<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
chdir(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public_html');
require 'api/customer/request.php';
