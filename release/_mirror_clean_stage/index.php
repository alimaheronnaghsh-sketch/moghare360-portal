<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';

mirror_render_head('ورود به MOGHARE360', 'index');
?>
<section class="m360-hero">
    <h2>خوش آمدید</h2>
    <p>پورتال یکپارچه خدمات خودرو — لطفاً نوع ورود خود را انتخاب کنید.</p>
</section>

<div class="m360-grid m360-role-grid m360-role-grid-dual">
    <a class="m360-card m360-role-card" href="customer-request.php">
        <h3>مشتری</h3>
        <p>ثبت درخواست آنلاین، مشاوره و پیگیری خدمات</p>
    </a>
    <div class="m360-card m360-role-card m360-role-card-static">
        <a href="staff-login.php" class="m360-role-card-link">
            <h3>پرسنل</h3>
            <p>ورود پرسنل با نام کاربری و رمز عبور</p>
        </a>
        <p class="m360-mgmt-link"><a href="owner-login.php">ورود مدیریتی</a></p>
    </div>
</div>
<?php mirror_render_foot(); ?>
