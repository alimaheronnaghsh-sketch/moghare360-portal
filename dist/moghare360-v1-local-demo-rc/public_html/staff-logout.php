<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();
unset($_SESSION['staff_user']);
flash('از حساب پرسنل خارج شدید.');
redirect('index.php');
