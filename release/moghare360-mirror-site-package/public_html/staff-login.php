<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$result = null;
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $result = mirror_api_staff_login([
        'username' => $username,
        'password' => $password,
        'mirror' => true,
    ]);
}

mirror_render_head('ورود پرسنل — MOGHARE360', 'staff');
?>
<section class="m360-hero">
    <h2>ورود پرسنل</h2>
    <p>احراز هویت از Master Server — بدون ذخیره رمز یا session حساس روی هاست.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
        <?php if (!($result['ok'] ?? false)): ?>
            <p style="margin:0.5rem 0 0;font-size:0.85rem">Master API endpoint implementation required on local server</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form" style="max-width:480px;margin:0 auto">
    <form method="post" action="staff-login.php">
        <label for="username">نام کاربری</label>
        <input type="text" id="username" name="username" required autocomplete="username" value="<?= mirror_h($username) ?>">
        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit" class="m360-btn">ورود از طریق Master Server</button>
    </form>
</section>
<?php mirror_render_foot(); ?>
