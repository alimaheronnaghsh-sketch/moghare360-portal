<?php
declare(strict_types=1);
/**
 * MOGHARE360 — Public lead form (cPanel template, placeholder deployment).
 * Copy to cPanel public_html. Posts to forward-lead.php on same host.
 */

$success = isset($_GET['ok']) && $_GET['ok'] === '1';
$error = isset($_GET['err']) && $_GET['err'] === '1';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>درخواست آنلاین — مقاره ۳۶۰</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #f4f6f8; margin: 0; padding: 1rem; color: #1e293b; }
        .wrap { max-width: 520px; margin: 2rem auto; background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 8px 24px rgba(15,23,42,.08); }
        h1 { font-size: 1.25rem; margin-top: 0; }
        label { display: block; margin: .75rem 0 .25rem; font-size: .9rem; }
        input, textarea, select { width: 100%; box-sizing: border-box; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 8px; }
        .hp { position: absolute; left: -9999px; opacity: 0; height: 0; width: 0; overflow: hidden; }
        button { margin-top: 1rem; width: 100%; padding: .75rem; background: #334155; color: #fff; border: 0; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        .msg-ok { background: #dcfce7; color: #166534; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; }
        .msg-err { background: #fee2e2; color: #991b1b; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>ثبت درخواست آنلاین</h1>
    <?php if ($success): ?>
        <div class="msg-ok">درخواست شما ثبت شد.</div>
    <?php elseif ($error): ?>
        <div class="msg-err">خطا در ثبت درخواست، لطفاً دوباره تلاش کنید.</div>
    <?php endif; ?>
    <form method="post" action="forward-lead.php" autocomplete="off">
        <label for="customer_name">نام مشتری *</label>
        <input id="customer_name" name="customer_name" required maxlength="200" placeholder="DEMO ONLINE TEST">

        <label for="mobile">موبایل *</label>
        <input id="mobile" name="mobile" required inputmode="tel" maxlength="11" placeholder="09xxxxxxxxx">

        <label for="vehicle_title">خودرو *</label>
        <input id="vehicle_title" name="vehicle_title" required maxlength="200" placeholder="DEMO VEHICLE">

        <label for="plate_masked">پلاک (اختیاری)</label>
        <input id="plate_masked" name="plate_masked" maxlength="50" placeholder="DEMO-PLATE">

        <label for="message">توضیحات</label>
        <textarea id="message" name="message" rows="3" maxlength="2000"></textarea>

        <label for="request_type">نوع درخواست</label>
        <select id="request_type" name="request_type">
            <option value="SERVICE">سرویس</option>
            <option value="REPAIR">تعمیر</option>
            <option value="OTHER">سایر</option>
        </select>

        <div class="hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <button type="submit">ارسال درخواست</button>
    </form>
</div>
</body>
</html>
