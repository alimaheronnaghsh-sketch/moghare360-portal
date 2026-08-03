<?php
declare(strict_types=1);

/**
 * Canonical local UAT host enforcement.
 * Prefer relative in-app URLs. Absolute outbound uses 127.0.0.1:8080/moghare360.
 * Browsing localhost must redirect to 127 preserving path/query.
 */

if (defined('M360_CANONICAL_HOST_HELPER_LOADED')) {
    return;
}
define('M360_CANONICAL_HOST_HELPER_LOADED', true);

const M360_CANONICAL_LOCAL_HOST = '127.0.0.1';
const M360_CANONICAL_LOCAL_PORT = '8080';
const M360_CANONICAL_LOCAL_BASE = 'http://127.0.0.1:8080/moghare360';

function m360_canonical_request_host(): string
{
    return strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
}

function m360_canonical_is_localhost_host(?string $host = null): bool
{
    $host = strtolower(trim((string)($host ?? m360_canonical_request_host())));
    if ($host === '') {
        return false;
    }
    $bare = explode(':', $host, 2)[0];

    return $bare === 'localhost' || str_ends_with($bare, '.localhost');
}

/**
 * Redirect localhost → 127.0.0.1 for local UAT (preserves path + query).
 * No-op for production / non-local hosts.
 */
function m360_canonical_local_host_enforce(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    // Keep GET localhost→127. Never redirect POST/PUT/PATCH/DELETE (breaks form/API bodies).
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        return;
    }
    $host = m360_canonical_request_host();
    if (!m360_canonical_is_localhost_host($host)) {
        return;
    }
    $port = M360_CANONICAL_LOCAL_PORT;
    if (preg_match('/:(\d+)$/', $host, $m) === 1) {
        $port = $m[1];
    } else {
        $serverPort = trim((string)($_SERVER['SERVER_PORT'] ?? ''));
        if ($serverPort !== '' && $serverPort !== '80' && $serverPort !== '443') {
            $port = $serverPort;
        }
    }
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/moghare360/');
    if ($uri === '') {
        $uri = '/moghare360/';
    }
    $target = 'http://' . M360_CANONICAL_LOCAL_HOST . ':' . $port . $uri;
    header('Location: ' . $target, true, 302);
    header('Cache-Control: no-store');
    exit;
}
