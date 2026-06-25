<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$result = null;
$input = ['requested_name' => '', 'requested_role' => '', 'justification' => '', 'contact_mobile' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach (array_keys($input) as $key) {
        $input[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $result = mirror_api_access_request([
        'requested_name' => $input['requested_name'],
        'requested_role' => $input['requested_role'],
        'justification' => $input['justification'],
        'contact_mobile' => $input['contact_mobile'],
        'mirror' => true,
    ]);
}

mirror_render_head('درخواست دسترسی کاربر — MOGHARE360', 'owner');
?>
<section class="m360-hero">
    <h2>درخواست ایجاد کاربر / دسترسی</h2>
    <p>درخواست به Master Server ارسال می‌شود. هیچ User واقعی روی هاست ساخته نمی‌شود.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form" style="max-width:560px;margin:0 auto">
    <form method="post" action="user-access-request.php">
        <label for="requested_name">نام درخواست‌دهنده</label>
        <input type="text" id="requested_name" name="requested_name" required value="<?= mirror_h($input['requested_name']) ?>">
        <label for="requested_role">نقش درخواستی</label>
        <select id="requested_role" name="requested_role" required>
            <option value="">انتخاب</option>
            <option value="staff">پرسنل</option>
            <option value="manager">مدیر</option>
            <option value="technician">تکنسین</option>
            <option value="company_owner">مالک کمپانی</option>
        </select>
        <label for="contact_mobile">موبایل تماس</label>
        <input type="tel" id="contact_mobile" name="contact_mobile" value="<?= mirror_h($input['contact_mobile']) ?>">
        <label for="justification">توضیح / دلیل</label>
        <textarea id="justification" name="justification" required><?= mirror_h($input['justification']) ?></textarea>
        <button type="submit" class="m360-btn">ارسال درخواست</button>
    </form>
    <p style="margin-top:1rem"><a href="owner-login.php">بازگشت به ورود مالک</a></p>
</section>
<?php mirror_render_foot(); ?>
