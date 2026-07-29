<?php
declare(strict_types=1);

require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-auth.php';

function inv360_csrf_token(): string
{
    inv360_session_start();
    $key = (string)(inv360_config()['app']['csrf_key'] ?? 'inv360_csrf');
    if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(16));
    }
    return $_SESSION[$key];
}

function inv360_csrf_field(): string
{
    $t = htmlspecialchars(inv360_csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<input type="hidden" name="inv360_csrf" value="' . $t . '">';
}

function inv360_csrf_require(): void
{
    inv360_session_start();
    $key = (string)(inv360_config()['app']['csrf_key'] ?? 'inv360_csrf');
    $expected = (string)($_SESSION[$key] ?? '');
    $got = (string)($_POST['inv360_csrf'] ?? '');
    if ($expected === '' || $got === '' || !hash_equals($expected, $got)) {
        http_response_code(403);
        echo 'اعتبار امنیتی فرم نامعتبر است.';
        exit;
    }
}
