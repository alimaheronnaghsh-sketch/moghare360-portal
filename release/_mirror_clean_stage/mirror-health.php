<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$config = mirror_config();
$health = mirror_api_health();
$online = ($health['ok'] ?? false);

mirror_render_head('وضعیت سرویس', 'index');
?>
<section class="m360-card" style="max-width:520px;margin:0 auto;text-align:center">
    <h3>وضعیت سرویس</h3>
    <p>
        <span class="m360-badge <?= $online ? 'm360-badge-ok' : 'm360-badge-warn' ?>">
            <?= $online ? 'در دسترس' : 'موقتاً در دسترس نیست' ?>
        </span>
    </p>
    <p class="m360-muted" style="margin-top:1rem"><a href="index.php">بازگشت به صفحه اصلی</a></p>
</section>
<?php mirror_render_foot(); ?>
