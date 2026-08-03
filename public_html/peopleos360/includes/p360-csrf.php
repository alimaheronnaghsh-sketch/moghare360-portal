<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-auth.php';
function p360_csrf_token(): string {
    p360_session_start();
    $k = (string)(p360_config()['app']['csrf_key'] ?? 'p360_csrf');
    if (empty($_SESSION[$k])) $_SESSION[$k] = bin2hex(random_bytes(16));
    return (string)$_SESSION[$k];
}
function p360_csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(p360_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function p360_csrf_verify(): bool {
    p360_session_start();
    $k = (string)(p360_config()['app']['csrf_key'] ?? 'p360_csrf');
    $sent = (string)($_POST['_csrf'] ?? '');
    $exp = (string)($_SESSION[$k] ?? '');
    return $sent !== '' && hash_equals($exp, $sent);
}