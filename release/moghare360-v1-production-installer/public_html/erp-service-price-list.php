<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Service Price List
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

$connection = false;
$errorMessage = '';
$servicePrices = [];
$labourRates = [];
$marginRules = [];
$legacyServices = [];
$flash = match (pricing_get_string('ok')) {
    'service' => 'قیمت خدمت ثبت شد.',
    'labour' => 'نرخ اجرت ثبت شد.',
    'margin' => 'قانون حاشیه ثبت شد.',
    default => '',
};

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = pricing_post_string('form_action');
        pricing_require_auth($connection, 'finance.pricing.write');

        if ($action === 'add_service') {
            erp_csrf_require_valid('finance_service_price', $_POST['erp_csrf_token'] ?? null);
            $code = pricing_post_string('service_code') ?: ('SVC-' . date('Ymd') . '-' . random_int(1000, 9999));
            $name = pricing_post_string('service_name');
            $category = pricing_post_string('service_category');
            $basePrice = pricing_post_float('base_price') ?? 0.0;
            $labourHours = pricing_post_float('labour_hours');
            $notes = pricing_post_string('notes');
            if ($name === '') {
                throw new RuntimeException('نام خدمت الزامی است.');
            }
            pricing_execute($connection, 'INSERT INTO dbo.erp_finance_service_price_list (service_code, service_name, service_category, base_price, labour_hours, notes, created_by) VALUES (?,?,?,?,?,?,?)', [$code, $name, $category ?: null, $basePrice, $labourHours, $notes ?: null, pricing_safe_current_user()]);
            pricing_safe_redirect('erp-service-price-list.php?ok=service');
        }

        if ($action === 'add_labour') {
            erp_csrf_require_valid('finance_labour_rate', $_POST['erp_csrf_token'] ?? null);
            $code = pricing_post_string('rate_code') ?: ('LAB-' . date('Ymd') . '-' . random_int(1000, 9999));
            $name = pricing_post_string('rate_name');
            $hourly = pricing_post_float('hourly_rate') ?? 0.0;
            $level = pricing_post_string('technician_level');
            if ($name === '') {
                throw new RuntimeException('نام نرخ اجرت الزامی است.');
            }
            pricing_execute($connection, 'INSERT INTO dbo.erp_finance_labour_rates (rate_code, rate_name, hourly_rate, technician_level, created_by) VALUES (?,?,?,?,?)', [$code, $name, $hourly, $level ?: null, pricing_safe_current_user()]);
            pricing_safe_redirect('erp-service-price-list.php?ok=labour');
        }

        if ($action === 'add_margin') {
            erp_csrf_require_valid('finance_margin_rule', $_POST['erp_csrf_token'] ?? null);
            $code = pricing_post_string('rule_code') ?: ('MGN-' . date('Ymd') . '-' . random_int(1000, 9999));
            $name = pricing_post_string('rule_name');
            $category = pricing_post_string('item_category');
            $pct = pricing_post_float('margin_percent') ?? 0.0;
            if ($name === '') {
                throw new RuntimeException('نام قانون حاشیه الزامی است.');
            }
            pricing_execute($connection, 'INSERT INTO dbo.erp_finance_part_margin_rules (rule_code, rule_name, item_category, margin_percent, created_by) VALUES (?,?,?,?,?)', [$code, $name, $category ?: null, $pct, pricing_safe_current_user()]);
            pricing_safe_redirect('erp-service-price-list.php?ok=margin');
        }
    }

    pricing_require_auth($connection, 'finance.pricing.view');

    if (pricing_table_exists($connection, 'erp_finance_service_price_list')) {
        $servicePrices = pricing_fetch_rows($connection, 'SELECT TOP 50 service_price_id, service_code, service_name, service_category, base_price, labour_hours, is_active FROM dbo.erp_finance_service_price_list ORDER BY service_price_id DESC');
    }
    if (pricing_table_exists($connection, 'erp_finance_labour_rates')) {
        $labourRates = pricing_fetch_rows($connection, 'SELECT TOP 50 labour_rate_id, rate_code, rate_name, hourly_rate, technician_level, is_active FROM dbo.erp_finance_labour_rates ORDER BY labour_rate_id DESC');
    }
    if (pricing_table_exists($connection, 'erp_finance_part_margin_rules')) {
        $marginRules = pricing_fetch_rows($connection, 'SELECT TOP 50 margin_rule_id, rule_code, rule_name, item_category, margin_percent, is_active FROM dbo.erp_finance_part_margin_rules ORDER BY margin_rule_id DESC');
    }
    if (pricing_table_exists($connection, 'erp_service_operations')) {
        $legacyServices = pricing_fetch_rows($connection, 'SELECT TOP 30 service_operation_id, service_code, service_title FROM dbo.erp_service_operations ORDER BY service_operation_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'لیست قیمت خدمات قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_render_head('لیست قیمت خدمات');

echo '<div class="p5fs-hero"><h1>لیست قیمت خدمات</h1><p>قیمت خدمت، نرخ اجرت و حاشیه قطعات</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . pricing_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    pricing_error('لیست قیمت', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">افزودن قیمت خدمت</h2><form method="post">';
echo '<input type="hidden" name="form_action" value="add_service">';
echo erp_csrf_input('finance_service_price');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نام خدمت *</label><input class="p1cc-input" name="service_name" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد</label><input class="p1cc-input m360-ltr" name="service_code" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">دسته</label><input class="p1cc-input" name="service_category" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">قیمت پایه</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="base_price" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">ساعت اجرت</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="labour_hours"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="notes" maxlength="1000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت قیمت خدمت</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">افزودن نرخ اجرت</h2><form method="post">';
echo '<input type="hidden" name="form_action" value="add_labour">';
echo erp_csrf_input('finance_labour_rate');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نام نرخ *</label><input class="p1cc-input" name="rate_name" required maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد</label><input class="p1cc-input m360-ltr" name="rate_code" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نرخ ساعتی</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="hourly_rate" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">سطح تکنسین</label><input class="p1cc-input" name="technician_level" maxlength="100"></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت نرخ اجرت</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">افزودن قانون حاشیه قطعه</h2><form method="post">';
echo '<input type="hidden" name="form_action" value="add_margin">';
echo erp_csrf_input('finance_margin_rule');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نام قانون *</label><input class="p1cc-input" name="rule_name" required maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد</label><input class="p1cc-input m360-ltr" name="rule_code" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">دسته قطعه</label><input class="p1cc-input" name="item_category" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">درصد حاشیه</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="margin_percent" value="0"></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت قانون حاشیه</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">قیمت خدمات</h2>';
if ($servicePrices === []) {
    echo '<p class="p1cc-hint">قیمتی ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>دسته</th><th>قیمت</th><th>ساعت</th></tr></thead><tbody>';
    foreach ($servicePrices as $r) {
        echo '<tr><td class="m360-ltr">' . pricing_h($r['service_code'] ?? '') . '</td><td>' . pricing_h($r['service_name'] ?? '') . '</td><td>' . pricing_h($r['service_category'] ?? '—') . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($r['base_price'] ?? '0')) . '</td><td class="m360-ltr">' . pricing_h($r['labour_hours'] ?? '—') . '</td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">نرخ اجرت</h2>';
if ($labourRates === []) {
    echo '<p class="p1cc-hint">نرخی ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>ساعتی</th><th>سطح</th></tr></thead><tbody>';
    foreach ($labourRates as $r) {
        echo '<tr><td class="m360-ltr">' . pricing_h($r['rate_code'] ?? '') . '</td><td>' . pricing_h($r['rate_name'] ?? '') . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($r['hourly_rate'] ?? '0')) . '</td><td>' . pricing_h($r['technician_level'] ?? '—') . '</td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">قوانین حاشیه قطعه</h2>';
if ($marginRules === []) {
    echo '<p class="p1cc-hint">قانونی ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>دسته</th><th>درصد</th></tr></thead><tbody>';
    foreach ($marginRules as $r) {
        echo '<tr><td class="m360-ltr">' . pricing_h($r['rule_code'] ?? '') . '</td><td>' . pricing_h($r['rule_name'] ?? '') . '</td><td>' . pricing_h($r['item_category'] ?? '—') . '</td><td class="m360-ltr">' . pricing_h($r['margin_percent'] ?? '0') . '%</td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

if ($legacyServices !== []) {
    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">خدمات Legacy (فقط خواندنی)</h2>';
    echo '<table class="p1cc-table"><thead><tr><th>شناسه</th><th>کد</th><th>عنوان</th></tr></thead><tbody>';
    foreach ($legacyServices as $r) {
        echo '<tr><td>' . pricing_h($r['service_operation_id'] ?? '') . '</td><td class="m360-ltr">' . pricing_h($r['service_code'] ?? '') . '</td><td>' . pricing_h($r['service_title'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

pricing_render_foot();
