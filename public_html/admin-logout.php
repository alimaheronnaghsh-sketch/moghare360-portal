<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();
unset($_SESSION['admin_ok']);
flash('از پنل ادمین خارج شدید.');
redirect('index.php');
