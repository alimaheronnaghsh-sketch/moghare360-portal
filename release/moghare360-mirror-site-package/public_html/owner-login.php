<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$result = null;
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $result = mirror_api_owner_login([
        'username' => $username,
        'password' => $password,
        'mirror' => true,
        'role' => 'system_owner',
    ]);
}

mirror_render_head('ورود مالک سیستم — MOGHARE360', 'owner');
?>
<section class="m360-hero">
    <h2>ورود مالک سیستم</h2>
    <p>دسترسی به داشبوردهای مالک از Master Server. ایجاد کاربر واقعی فقط روی Master انجام می‌شود.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form" style="max-width:480px;margin:0 auto">
    <form method="post" action="owner-login.php">
        <label for="username">نام کاربری مالک</label>
        <input type="text" id="username" name="username" required value="<?= mirror_h($username) ?>">
        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="m360-btn">ورود مالک</button>
    </form>
    <p style="margin-top:1rem"><a href="user-access-request.php">درخواست ایجاد کاربر / دسترسی</a></p>
</section>
<?php mirror_render_foot(); ?>
