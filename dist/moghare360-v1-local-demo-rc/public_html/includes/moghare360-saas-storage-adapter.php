<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';

/**
 * MOGHARE360 V1 — SaaS storage adapter (hosted path on server, never in ZIP).
 */

function mogh_storage_root(): string
{
    $cfg = mogh_saas_load_config();
    $root = (string)($cfg['storage_root'] ?? '');
    if ($root === '') {
        $root = mogh_saas_repo_root() . DIRECTORY_SEPARATOR . 'storage';
    }
    if (!is_dir($root)) {
        @mkdir($root, 0755, true);
    }
    return $root;
}

function mogh_storage_company_path(int $companyId, string $bucket): string
{
    $bucket = preg_replace('/[^a-z0-9_\-]/i', '', $bucket) ?? 'general';
    $path = mogh_storage_root()
        . DIRECTORY_SEPARATOR . 'company_' . $companyId
        . DIRECTORY_SEPARATOR . $bucket;
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    return $path;
}

/** @return array{ok: bool, path: string, relative_key: string} */
function mogh_storage_put_meta(int $companyId, string $bucket, string $objectKey, array $meta = []): array
{
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $objectKey) ?? 'object';
    $dir = mogh_storage_company_path($companyId, $bucket);
    $metaPath = $dir . DIRECTORY_SEPARATOR . $safeKey . '.meta.json';
    $payload = array_merge($meta, [
        'company_id' => $companyId,
        'bucket' => $bucket,
        'object_key' => $safeKey,
        'stored_at' => gmdate('c'),
    ]);
    file_put_contents($metaPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return [
        'ok' => true,
        'path' => $metaPath,
        'relative_key' => 'company_' . $companyId . '/' . $bucket . '/' . $safeKey,
    ];
}

function mogh_storage_mode(): string
{
    $cfg = mogh_saas_load_config();
    return !empty($cfg['config_missing']) ? 'unconfigured' : 'hosted_local';
}
