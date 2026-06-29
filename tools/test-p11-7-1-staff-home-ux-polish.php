<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p1171u_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p1171u_workbench_files(string $role): array
{
    $files = [];
    foreach (m360_staff_home_workbench_items($role) as $item) {
        $files[] = (string)($item['file'] ?? '');
    }

    return $files;
}

function p1171u_has_preview(string $role): bool
{
    return in_array('erp-access-permission-preview.php', p1171u_workbench_files($role), true);
}

function p1171u_render_sample(string $role, int $userId = 1): string
{
    ob_start();
    foreach (m360_staff_home_workbench_items($role) as $item) {
        if (($item['card_type'] ?? '') === 'nav' && ($item['file'] ?? '') === 'erp-technical-board.php') {
            m360_staff_home_render_workbench_item($item, $userId);
            break;
        }
    }
    if (ob_get_length() === 0) {
        $items = m360_staff_home_workbench_items($role);
        if ($items !== []) {
            m360_staff_home_render_workbench_item($items[0], $userId);
        }
    }

    return (string)ob_get_clean();
}

$helper = (string)file_get_contents($pub . '/includes/m360-staff-home-helper.php');
$page = (string)file_get_contents($pub . '/erp-staff-home.php');
$css = (string)file_get_contents($pub . '/assets/css/m360-staff-home.css');

$results = [];

$results[] = p1171u_pass('role label map exists', str_contains($helper, 'function m360_staff_home_role_labels_fa'));
$results[] = p1171u_pass('usage path helper exists', str_contains($helper, 'function m360_staff_home_usage_path_fa'));
$results[] = p1171u_pass('scope backlog helper exists', str_contains($helper, 'function m360_staff_home_scope_backlog_items'));

foreach (['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'RECEPTION', 'TECHNICIAN', 'PARTS', 'FINANCE', 'QC'] as $code) {
    $label = m360_staff_home_role_label_fa($code);
    $results[] = p1171u_pass('Persian label for ' . $code, $label !== $code && $label !== '' && !str_contains($label, 'erp-'));
}

$results[] = p1171u_pass('OWNER label Persian', m360_staff_home_role_label_fa('OWNER') === 'مالک / مدیر ارشد');
$results[] = p1171u_pass('SERVICE_MANAGER label Persian', m360_staff_home_role_label_fa('SERVICE_MANAGER') === 'مدیر سرویس / سالن');

$results[] = p1171u_pass('identity label شناسه کاربری', str_contains($page, 'شناسه کاربری') && !str_contains($page, '>user_id<'));
$results[] = p1171u_pass('identity label نقش', str_contains($page, '<div class="lbl">نقش</div>') && !str_contains($page, '<div class="lbl">role_code</div>'));
$results[] = p1171u_pass('identity label سطح دسترسی', str_contains($page, 'سطح دسترسی') && !str_contains($page, 'Permission'));
$results[] = p1171u_pass('identity uses role_label_fa', str_contains($page, 'role_label_fa'));

$results[] = p1171u_pass('render hides m360-staff-file span', !str_contains($helper, 'm360-staff-file'));
$results[] = p1171u_pass('render uses data-route attribute', str_contains($helper, 'data-route'));

$sample = p1171u_render_sample('SERVICE_MANAGER');
$results[] = p1171u_pass('sample render no visible erp- filename in body', !preg_match('/>\s*erp-[a-z0-9-]+\.php\s*</', $sample));
$results[] = p1171u_pass('sample render has usage path', str_contains($sample, 'مسیر استفاده'));

$infoSample = '';
ob_start();
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['card_type'] ?? '') === 'info') {
        m360_staff_home_render_workbench_item($item, 1);
        $infoSample = (string)ob_get_clean();
        break;
    }
}
if ($infoSample === '') {
    ob_end_clean();
}
$results[] = p1171u_pass('info card guided note', str_contains($infoSample, M360_STAFF_HOME_INFO_ROUTE_FA));
$results[] = p1171u_pass('info card no visible filename text', !preg_match('/>\s*erp-[a-z0-9-]+\.php\s*</', $infoSample));

$noteSample = '';
ob_start();
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['card_type'] ?? '') === 'note') {
        m360_staff_home_render_workbench_item($item, 1);
        $noteSample = (string)ob_get_clean();
        break;
    }
}
if ($noteSample === '') {
    ob_end_clean();
}
$results[] = p1171u_pass('note card operation note', str_contains($noteSample, M360_STAFF_HOME_NOTE_ROUTE_FA));
$results[] = p1171u_pass('note card not clickable link', !str_contains($noteSample, 'href="erp-'));

$results[] = p1171u_pass('OWNER has permission preview', p1171u_has_preview('OWNER'));
$results[] = p1171u_pass('SYSTEM_ADMIN has permission preview', p1171u_has_preview('SYSTEM_ADMIN'));
$results[] = p1171u_pass('SERVICE_MANAGER no permission preview', !p1171u_has_preview('SERVICE_MANAGER'));
$results[] = p1171u_pass('RECEPTION no permission preview', !p1171u_has_preview('RECEPTION'));
$results[] = p1171u_pass('TECHNICIAN no permission preview', !p1171u_has_preview('TECHNICIAN'));

$ownerBacklog = array_filter(
    m360_staff_home_workbench_items('OWNER'),
    static fn(array $i): bool => ($i['card_type'] ?? '') === 'backlog' && str_contains((string)($i['label_fa'] ?? ''), 'مرجع')
);
$results[] = p1171u_pass('OWNER manager reference backlog', count($ownerBacklog) >= 1);

$hrBacklog = array_filter(
    m360_staff_home_workbench_items('RECEPTION'),
    static fn(array $i): bool => ($i['card_type'] ?? '') === 'backlog' && str_contains((string)($i['label_fa'] ?? ''), 'پروفایل شخصی')
);
$results[] = p1171u_pass('HR profile backlog card', count($hrBacklog) >= 1);

$leaveBacklog = array_filter(
    m360_staff_home_workbench_items('TECHNICIAN'),
    static fn(array $i): bool => ($i['card_type'] ?? '') === 'backlog' && str_contains((string)($i['label_fa'] ?? ''), 'مرخصی')
);
$results[] = p1171u_pass('leave backlog card', count($leaveBacklog) >= 1);

$results[] = p1171u_pass('encoding helper preserved', function_exists('m360_staff_home_text_from_odbc'));
$results[] = p1171u_pass('workbench matrix still has today group', isset(m360_staff_home_role_routes('RECEPTION')['workbench_groups'][M360_STAFF_HOME_GROUP_TODAY]));

$pass = 0;
$fail = 0;
echo "# P11.7.1 Staff Home UX Polish Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
