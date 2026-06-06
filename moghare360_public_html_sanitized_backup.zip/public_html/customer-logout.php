<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

unset($_SESSION['customer_profile_old'], $_SESSION['customer_profile_errors']);
customerLogout();
flash('از حساب مشتری خارج شدید.');
redirect('index.php');
