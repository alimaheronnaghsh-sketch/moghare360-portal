<?php
declare(strict_types=1);

/**
 * Personnel access recommendations API — advisory only.
 * Forbidden: apply package, assign role, ALLOW/DENY.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$m360AmApiRequire = static function (string $fileName): void {
    $root = dirname(__DIR__, 2);
    $candidates = [
        $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname($root) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            require_once $candidate;
            return;
        }
    }
    throw new RuntimeException('Required file not found: ' . $fileName);
};

$m360AmApiRequire('erp-auth-context.php');
$m360AmApiRequire('m360-access-recommendation-helper.php');

erp_auth_require_login();
$conn = m360_am_db();
$actorId = m360_am_actor_user_id();

if ($conn === false || $actorId < 1) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'نشست معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!m360_am_can_manage_matrix($conn, $actorId)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'مجوز مدیریت ماتریس دسترسی را ندارید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $code = trim((string)($_GET['employee_code'] ?? ''));
    try {
        m360_rec_ensure_tables($conn);
        $map = m360_rec_load_latest_map($conn);
        if ($map === []) {
            $built = m360_rec_build_all($conn);
            m360_rec_persist_all($conn, $built, $actorId);
            $map = m360_rec_load_latest_map($conn);
        }
        if ($code !== '') {
            $one = $map[$code] ?? null;
            if ($one === null) {
                echo json_encode(['ok' => false, 'message' => 'پیشنهادی یافت نشد.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode(['ok' => true, 'recommendation' => $one], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'formula' => 'LOCKED_PROFILE + PRIMARY + SECONDARY + SCOPE + AUTHORITY + SENSITIVE_GATES + SOD + OWNER_EXCEPTION',
            'packages' => array_values(m360_access_package_definitions()),
            'recommendations' => array_values($map),
            'apply_forbidden' => true,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'خطای پیشنهاد: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    if (!is_array($body)) {
        $body = [];
    }
    $action = strtolower(trim((string)($body['action'] ?? $_POST['action'] ?? '')));
    $token = (string)($body['erp_csrf_token'] ?? $_POST['erp_csrf_token'] ?? '');

    if (!function_exists('erp_csrf_validate_token') || !erp_csrf_validate_token(M360_ACCESS_MATRIX_CSRF, $token)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'توکن امنیتی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Hard deny any apply/assign verbs
    if (in_array($action, ['apply', 'assign', 'allow', 'deny', 'apply_package', 'grant'], true)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'message' => 'اعمال بسته / تخصیص نقش / ALLOW-DENY در این مأموریت ممنوع است.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($action === 'rebuild') {
            $built = m360_rec_build_all($conn);
            $persist = m360_rec_persist_all($conn, $built, $actorId);
            $previewDir = dirname(__DIR__, 2) . '/tools/_generated';
            if (!is_dir($previewDir)) {
                @mkdir($previewDir, 0777, true);
            }
            m360_rec_write_preview_tsv($built, $previewDir . '/personnel_access_recommendations_preview.tsv');
            echo json_encode([
                'ok' => true,
                'message' => 'پیشنهادها بازسازی شد (بدون تغییر دسترسی مؤثر).',
                'saved' => $persist['saved'],
                'recommendations' => array_values(m360_rec_load_latest_map($conn)),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'mark_owner_review') {
            $code = trim((string)($body['employee_code'] ?? ''));
            $note = trim((string)($body['note'] ?? 'علامت برای بررسی مالک'));
            if ($code === '') {
                echo json_encode(['ok' => false, 'message' => 'کد پرسنلی لازم است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $ok = m360_rec_mark_owner_review($conn, $code, $actorId, $note);
            echo json_encode([
                'ok' => $ok,
                'message' => $ok ? 'برای بررسی مالک علامت‌گذاری شد.' : 'پیشنهاد یافت نشد.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['ok' => false, 'message' => 'عملیات نامعتبر.'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'message' => 'روش نامعتبر.'], JSON_UNESCAPED_UNICODE);
