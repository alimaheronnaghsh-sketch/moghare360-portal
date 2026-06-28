<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';

$result = null;
$username = '';
$otpEnabled = mirror_sms_otp_enabled();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $result = mirror_api_staff_login([
        'username' => $username,
        'password' => $password,
        'mirror' => true,
    ]);

    if (($result['ok'] ?? false) === true) {
        $apiRoot = is_array($result['data'] ?? null) ? $result['data'] : [];
        $payload = is_array($apiRoot['data'] ?? null) ? $apiRoot['data'] : [];
        m360_staff_home_sync_session_from_login_payload($payload);

        $redirect = trim((string)($payload['redirect_url'] ?? M360_STAFF_HOME_REDIRECT_PATH));
        if ($redirect !== '' && !preg_match('#^[a-zA-Z0-9_./?=&%-]+$#', $redirect)) {
            $redirect = M360_STAFF_HOME_REDIRECT_PATH;
        }
        if (!str_contains($redirect, '://') && !str_starts_with($redirect, '//')) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

mirror_render_head('ورود پرسنل', 'staff');
?>
<section class="m360-hero">
    <h2>ورود پرسنل</h2>
    <p>لطفاً نام کاربری و رمز عبور خود را وارد کنید.</p>
</section>

<?php if (!$otpEnabled): ?>
    <p class="m360-otp-note">ورود با رمز فعال است. ورود پیامکی پس از فعال‌سازی پیامک در دسترس خواهد بود.</p>
<?php endif; ?>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form" style="max-width:480px;margin:0 auto">
    <form method="post" action="staff-login.php">
        <label for="username">نام کاربری</label>
        <input type="text" id="username" name="username" required autocomplete="username" value="<?= mirror_h($username) ?>">
        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <?php if ($otpEnabled): ?>
            <p class="m360-otp-note">ورود پیامکی نیز فعال است — از پنل مدیریت راهنمای ورود را ببینید.</p>
        <?php endif; ?>
        <button type="submit" class="m360-btn">ورود</button>
    </form>
    <p class="m360-mgmt-link"><a href="owner-login.php">ورود مدیریتی</a></p>
</section>
<?php mirror_render_foot(); ?>
