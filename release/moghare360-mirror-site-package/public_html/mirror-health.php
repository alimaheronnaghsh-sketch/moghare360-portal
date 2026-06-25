<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$config = mirror_config();
$health = mirror_api_health();

mirror_render_head('سلامت آینه — MOGHARE360', 'index');
?>
<section class="m360-card">
    <h3>Mirror Health</h3>
    <table class="m360-table">
        <tr><th>Mirror Mode</th><td><?= !empty($config['MIRROR_MODE']) ? 'فعال' : 'غیرفعال' ?></td></tr>
        <tr><th>Host Database</th><td><?= !empty($config['HOST_DATABASE_ALLOWED']) ? 'مجاز (نباید)' : 'غیرمجاز ✓' ?></td></tr>
        <tr><th>Local Storage</th><td><?= !empty($config['LOCAL_STORAGE_ALLOWED']) ? 'مجاز (نباید)' : 'غیرمجاز ✓' ?></td></tr>
        <tr><th>Master URL</th><td><code style="direction:ltr"><?= mirror_h((string)($config['MASTER_SERVER_BASE_URL'] ?? '')) ?></code></td></tr>
        <tr><th>API Health</th><td>
            <span class="m360-badge <?= ($health['ok'] ?? false) ? 'm360-badge-ok' : 'm360-badge-warn' ?>">
                <?= mirror_h((string)($health['message'] ?? 'نامشخص')) ?>
            </span>
        </td></tr>
    </table>
</section>
<?php mirror_render_foot(); ?>
