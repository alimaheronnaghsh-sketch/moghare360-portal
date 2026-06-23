<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');

function syncJson(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function syncGetHeaderToken(): string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    foreach ($headers as $key => $value) {
        if (strtolower((string)$key) === 'x-moghare-sync-token') {
            return trim((string)$value);
        }
    }

    if (!empty($_SERVER['HTTP_X_MOGHARE_SYNC_TOKEN'])) {
        return trim((string)$_SERVER['HTTP_X_MOGHARE_SYNC_TOKEN']);
    }

    if (!empty($_SERVER['REDIRECT_HTTP_X_MOGHARE_SYNC_TOKEN'])) {
        return trim((string)$_SERVER['REDIRECT_HTTP_X_MOGHARE_SYNC_TOKEN']);
    }

    if (!empty($_GET['token'])) {
        return trim((string)$_GET['token']);
    }

    return '';
}

function syncRequireToken(): void
{
    global $syncApiToken;

    $expected = trim((string)($syncApiToken ?? ''));
    $given = syncGetHeaderToken();

    if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
        syncJson([
            'ok' => false,
            'error' => 'Forbidden'
        ], 403);
    }
}