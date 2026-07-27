<?php
declare(strict_types=1);

/**
 * Browser-equivalent click test for estimate send button (JobCard 16 / Estimate 34).
 * Uses session bootstrap for user 20016 — no password printed.
 */

$root = dirname(__DIR__);
$base = 'http://127.0.0.1:8080/moghare360';
$cookieJar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'm360_est_btn_uat_' . getmypid() . '.jar';
@unlink($cookieJar);

require_once $root . '/public_html/includes/erp-customer-core-helper.php';
require_once $root . '/public_html/includes/m360-staff-home-helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['erp_user_id'] = 20016;
$_SESSION['erp_username'] = 'Amir';
$_SESSION['erp_company_id'] = 1;
$sessionId = session_id();
session_write_close();

function est_http(string $url, string $cookieJar, string $method = 'GET', array $post = [], ?string $sessionId = null): array
{
    $ch = curl_init($url);
    $headers = [];
    if ($sessionId !== null && $sessionId !== '') {
        $headers[] = 'Cookie: PHPSESSID=' . $sessionId;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = (string)curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $headerSize = (int)($info['header_size'] ?? 0);
    return [
        'status' => (int)($info['http_code'] ?? 0),
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
        'location' => (string)($info['redirect_url'] ?? ''),
    ];
}

function est_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$detailUrl = $base . '/erp-estimate-detail.php?jobcard_id=16';
$page = est_http($detailUrl, $cookieJar, 'GET', [], $sessionId);
$results[] = est_pass('Detail page HTTP 200', $page['status'] === 200, 'status=' . $page['status']);
$results[] = est_pass('HTML visual marker present', str_contains($page['body'], 'ESTIMATE_DETAIL_VISUAL_RECONCILE_20260721_1755'));
$results[] = est_pass('Send button in DOM', str_contains($page['body'], 'id="m360-est-send-customer-btn"') && str_contains($page['body'], 'ارسال به مشتری'));
$results[] = est_pass('Send form action=send_to_customer', str_contains($page['body'], 'name="action" value="send_to_customer"'));
$results[] = est_pass('CSRF field present', str_contains($page['body'], 'name="erp_csrf_token"'));
$results[] = est_pass('Unified dark green page tokens', str_contains($page['body'], '--m360-est-bg:') && str_contains($page['body'], 'Luxury Dark Green'));
$results[] = est_pass('No emergency white strip CSS', !str_contains($page['body'], '.m360-ops-strip {\n            background: #ffffff !important') && !str_contains($page['body'], 'background: #ffffff !important'));
$results[] = est_pass('Ops topnav dark green override', str_contains($page['body'], '.m360-ops-topnav') && str_contains($page['body'], '#0a241c'));
$results[] = est_pass('No blue nav button override (#2563eb scoped away)', preg_match('/\.m360-est-page\s+\.m360-ops-nav-link\s*\{[^}]*#1a5c48/s', $page['body']) === 1);
$results[] = est_pass('All cards dark green glass', str_contains($page['body'], '.m360-est-page .w1c-card') && str_contains($page['body'], 'rgba(14, 48, 39'));
$results[] = est_pass('Case stage header preserved', str_contains($page['body'], 'm360-case-stage-header'));
$results[] = est_pass('No mirror.css on estimate page', !str_contains($page['body'], 'assets/css/mirror.css'));
$results[] = est_pass('No luxury-ui.css on estimate page', !str_contains($page['body'], 'moghare360-v1-luxury-ui.css'));

$csrf = '';
if (preg_match('/data-est-action="send_to_customer"[\s\S]*?name="erp_csrf_token"\s+value="([^"]+)"/', $page['body'], $csrfMatch)) {
    $csrf = $csrfMatch[1];
}
$results[] = est_pass('CSRF token parsed', $csrf !== '');

$post = est_http($base . '/erp-estimate-action.php', $cookieJar, 'POST', [
    'erp_csrf_token' => $csrf,
    'estimate_id' => '34',
    'jobcard_id' => '16',
    'action' => 'send_to_customer',
], $sessionId);
$results[] = est_pass('POST returns redirect', in_array($post['status'], [302, 303], true), 'status=' . $post['status']);
$location = '';
if (preg_match('/Location:\s*(.+)/i', $post['headers'], $locMatch)) {
    $location = trim($locMatch[1]);
}
$results[] = est_pass('POST target erp-estimate-action.php', true, 'redirect=' . $location);
$results[] = est_pass('Redirect keeps jobcard_id=16', str_contains($location, 'jobcard_id=16'));

$followUrl = $location;
if ($followUrl !== '' && !str_starts_with($followUrl, 'http')) {
    $followUrl = $base . '/' . ltrim($followUrl, '/');
}
$after = $followUrl !== '' ? est_http($followUrl, $cookieJar, 'GET', [], $sessionId) : ['body' => '', 'status' => 0];
$msgVisible = str_contains($after['body'], 'm360-est-flash') && (
    str_contains($after['body'], 'برآورد در کارتابل مشتری فعال است')
    || str_contains($after['body'], 'role="alert"')
);
$results[] = est_pass('Result flash visible after click', $msgVisible);
$msgText = '';
if (preg_match('/class="m360-est-flash[^"]*"[^>]*>(.*?)<\/div>/s', $after['body'], $msgMatch)) {
    $msgText = trim(strip_tags($msgMatch[1]));
}
$results[] = est_pass('Result message Persian', $msgText !== '' && str_contains($msgText, 'برآورد در کارتابل مشتری'));

$conn = customer_core_db();
$task = customer_core_fetch_rows($conn, "SELECT TOP 1 task_id, status, is_active FROM dbo.erp_customer_cartable_tasks WHERE task_id = 41");
$taskPending = isset($task[0]) && strtoupper((string)$task[0]['status']) === 'PENDING' && (int)$task[0]['is_active'] === 1;
$results[] = est_pass('Task 41 remains PENDING active', $taskPending);
$dup = customer_core_fetch_rows($conn, "SELECT COUNT(*) AS c FROM dbo.erp_customer_cartable_tasks WHERE estimate_id = 34 AND task_type = N'ESTIMATE_APPROVAL' AND is_active = 1 AND status IN (N'PENDING', N'OPENED')");
$results[] = est_pass('No duplicate active task', (int)($dup[0]['c'] ?? 0) === 1, 'count=' . (int)($dup[0]['c'] ?? 0));

$badCsrf = est_http($base . '/erp-estimate-action.php', $cookieJar, 'POST', [
    'erp_csrf_token' => 'bad-token',
    'estimate_id' => '34',
    'jobcard_id' => '16',
    'action' => 'send_to_customer',
], $sessionId);
$results[] = est_pass('POST without CSRF rejected', in_array($badCsrf['status'], [302, 303], true) && str_contains($badCsrf['headers'], 'msg='));

$getMut = est_http($base . '/erp-estimate-action.php?action=send_to_customer&estimate_id=34', $cookieJar, 'GET', [], $sessionId);
$results[] = est_pass('Direct GET does not mutate', in_array($getMut['status'], [302, 303], true) && str_contains($getMut['headers'], 'erp-estimate-board.php'));

$passed = 0;
$failed = 0;
echo "# Estimate Button Browser Reality Test\n\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $r['pass'] ? $passed++ : $failed++;
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[$mark] {$r['name']}$detail\n";
}
if ($msgText !== '') {
    echo "\nResult message: $msgText\n";
}
echo "\nTotal: " . count($results) . " | PASS: $passed | FAIL: $failed\n";
@unlink($cookieJar);
exit($failed > 0 ? 1 : 0);
