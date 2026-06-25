<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config-sync.php';

    if (!function_exists('getPdo')) {
        throw new RuntimeException('تابع getPdo پیدا نشد.');
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = $headers['X-MOGHARE-SYNC-TOKEN']
        ?? $headers['x-moghare-sync-token']
        ?? $_SERVER['HTTP_X_MOGHARE_SYNC_TOKEN']
        ?? '';

    $expectedToken = $syncApiToken
        ?? (defined('SYNC_API_TOKEN') ? SYNC_API_TOKEN : 'MOGHARE360_SYNC_2026_7fA9xQ_ChangeMe');

    if (!hash_equals((string)$expectedToken, (string)$token)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'Forbidden'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $raw = file_get_contents('php://input') ?: '';
    $payload = json_decode($raw, true);

    file_put_contents(
        __DIR__ . '/ack_last_payload.json',
        json_encode([
            'time' => date('Y-m-d H:i:s'),
            'raw' => $raw,
            'decoded' => $payload
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );

    if (!is_array($payload)) {
        throw new RuntimeException('JSON ورودی ack.php معتبر نیست.');
    }

    $pdo = getPdo();

    $group = $payload['group']
        ?? $payload['table']
        ?? $payload['dataset']
        ?? $payload['key']
        ?? $payload['sync_key']
        ?? '';

    $successIds = $payload['success_ids']
        ?? $payload['synced_ids']
        ?? $payload['processed_ids']
        ?? $payload['imported_ids']
        ?? $payload['ids']
        ?? [];

    $errors = $payload['errors']
        ?? $payload['failed']
        ?? $payload['failed_errors']
        ?? [];

    if (isset($payload['inventory_items']) && is_array($payload['inventory_items'])) {
        $group = 'inventory_items';
        $successIds = $payload['inventory_items']['success_ids']
            ?? $payload['inventory_items']['synced_ids']
            ?? $payload['inventory_items']['ids']
            ?? $successIds;

        $errors = $payload['inventory_items']['errors']
            ?? $payload['inventory_items']['failed']
            ?? $errors;
    }

    if ($group === '') {
        $group = 'inventory_items';
    }

    $tableMap = [
        'inventory_items' => 'inventory_items_staging',
        'inventory_item' => 'inventory_items_staging',
        'inventory_items_staging' => 'inventory_items_staging'
    ];

    if (!isset($tableMap[$group])) {
        echo json_encode([
            'ok' => true,
            'warning' => 'گروه برای ack شناخته نشد، اما خطا برنگشت.',
            'group' => $group,
            'payload_saved' => 'ack_last_payload.json'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $table = $tableMap[$group];

    $columnsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = array_column($columnsStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $hasAttempt = in_array('sync_attempt_count', $columns, true);
    $hasLastAttempt = in_array('last_sync_attempt_at', $columns, true);
    $hasSyncedAt = in_array('synced_at', $columns, true);
    $hasSyncError = in_array('sync_error', $columns, true);

    $successIds = is_array($successIds) ? $successIds : [];
    $successIds = array_values(array_filter(array_map('intval', $successIds)));

    $syncedCount = 0;
    $errorCount = 0;

    if ($successIds) {
        $setParts = ["sync_status = 'Synced'"];

        if ($hasSyncError) {
            $setParts[] = "sync_error = NULL";
        }

        if ($hasAttempt) {
            $setParts[] = "sync_attempt_count = sync_attempt_count + 1";
        }

        if ($hasLastAttempt) {
            $setParts[] = "last_sync_attempt_at = NOW()";
        }

        if ($hasSyncedAt) {
            $setParts[] = "synced_at = NOW()";
        }

        $placeholders = implode(',', array_fill(0, count($successIds), '?'));

        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($successIds);

        $syncedCount = $stmt->rowCount();
    }

    if (is_array($errors)) {
        foreach ($errors as $key => $value) {
            $id = is_numeric($key) ? (int)$key : (int)($value['id'] ?? 0);
            $message = is_array($value) ? (string)($value['error'] ?? $value['message'] ?? 'Sync error') : (string)$value;

            if ($id <= 0) {
                continue;
            }

            $setParts = ["sync_status = 'Error'"];
            $params = [];

            if ($hasSyncError) {
                $setParts[] = "sync_error = ?";
                $params[] = $message;
            }

            if ($hasAttempt) {
                $setParts[] = "sync_attempt_count = sync_attempt_count + 1";
            }

            if ($hasLastAttempt) {
                $setParts[] = "last_sync_attempt_at = NOW()";
            }

            $params[] = $id;

            $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $errorCount += $stmt->rowCount();
        }
    }

    echo json_encode([
        'ok' => true,
        'group' => $group,
        'table' => $table,
        'received_success_ids' => count($successIds),
        'synced_count' => $syncedCount,
        'error_count' => $errorCount,
        'payload_saved' => 'ack_last_payload.json',
        'time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}