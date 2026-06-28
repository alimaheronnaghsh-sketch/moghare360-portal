<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';

$result = null;
$username = '';
$redirectPending = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $result = mirror_api_owner_login([
        'username' => $username,
        'password' => $password,
        'mirror' => true,
    ]);

    if (($result['ok'] ?? false) === true) {
        $apiRoot = is_array($result['data'] ?? null) ? $result['data'] : [];
        $payload = is_array($apiRoot['data'] ?? null) ? $apiRoot['data'] : [];
        $redirectPending = m360_owner_login_redirect_after_success($payload);
    }
}

mirror_render_head('ورود مدیریت', 'staff');
?>
<section class="m360-hero">
    <h2>ورود مدیریت</h2>
    <p>این بخش مخصوص مدیران مجاز است. پس از ورود، سطح دسترسی شما به‌صورت خودکار تعیین می‌شود.</p>
</section>

<?php if ($redirectPending): ?>
    <div class="m360-alert m360-alert-info">ورود موفق بود، در حال انتقال به داشبورد...</div>
    <p><a class="m360-btn" href="<?= mirror_h(m360_owner_login_resolve_redirect_path()) ?>">ادامه به داشبورد</a></p>
<?php endif; ?>

<?php if ($result !== null && !$redirectPending): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form" style="max-width:480px;margin:0 auto">
    <form method="post" action="owner-login.php">
        <label for="username">نام کاربری</label>
        <input type="text" id="username" name="username" required autocomplete="username" value="<?= mirror_h($username) ?>">
        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit" class="m360-btn">ورود</button>
    </form>
    <p style="margin-top:1rem"><a href="user-access-request.php">درخواست دسترسی</a> · <a href="staff-login.php">ورود پرسنل</a></p>
</section>
<?php mirror_render_foot(); ?>
