<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Customer Contract Create Form (no direct write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.contract.create');
} catch (Throwable) {
    customer_core_render_error_page('ایجاد قرارداد', 'صفحه ایجاد قرارداد قابل بارگذاری نیست.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

customer_core_render_head('ایجاد قرارداد مشتری');

echo '<div class="p1cc-hero">';
echo '<h1>ایجاد قرارداد مشتری</h1>';
echo '<p>تعریف نوع قرارداد، حالت مجوزدهی و وضعیت اولیه</p>';
echo '</div>';

echo '<form class="p1cc-card" method="post" action="submit-customer-contract.php">';
echo erp_csrf_input('customer_core_contract');
echo '<div class="p1cc-form-grid">';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="customer_id">شناسه مشتری</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="customer_id" name="customer_id" min="1"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="intake_id">شناسه Intake</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="intake_id" name="intake_id" min="1"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="contract_type">نوع قرارداد *</label>';
echo '<select class="p1cc-select" id="contract_type" name="contract_type" required>';
echo '<option value="">انتخاب کنید</option>';
echo '<option value="PAY_PER_SERVICE">PAY_PER_SERVICE — پرداخت به ازای سرویس</option>';
echo '<option value="OPEN_AUTHORIZATION">OPEN_AUTHORIZATION — مجوز باز</option>';
echo '<option value="LIMITED_AUTHORIZATION">LIMITED_AUTHORIZATION — مجوز محدود</option>';
echo '<option value="CORPORATE_FLEET">CORPORATE_FLEET — ناوگان سازمانی</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="authorization_mode">حالت مجوزدهی *</label>';
echo '<select class="p1cc-select" id="authorization_mode" name="authorization_mode" required>';
echo '<option value="">انتخاب کنید</option>';
echo '<option value="NO_PREAUTH">NO_PREAUTH — بدون پیش‌مجوز</option>';
echo '<option value="PREAUTH_LIMITED">PREAUTH_LIMITED — پیش‌مجوز محدود</option>';
echo '<option value="PREAUTH_OPEN">PREAUTH_OPEN — پیش‌مجوز باز</option>';
echo '<option value="APPROVAL_REQUIRED">APPROVAL_REQUIRED — نیاز به تأیید</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="approval_threshold_amount">سقف تأیید (مبلغ)</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" step="0.01" id="approval_threshold_amount" name="approval_threshold_amount" min="0"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requires_operation_approval">نیاز به تأیید عملیات</label>';
echo '<select class="p1cc-select" id="requires_operation_approval" name="requires_operation_approval">';
echo '<option value="1" selected>بله</option><option value="0">خیر</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requires_parts_approval">نیاز به تأیید قطعات</label>';
echo '<select class="p1cc-select" id="requires_parts_approval" name="requires_parts_approval">';
echo '<option value="1" selected>بله</option><option value="0">خیر</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="initial_status">وضعیت اولیه *</label>';
echo '<select class="p1cc-select" id="initial_status" name="initial_status" required>';
echo '<option value="DRAFT">DRAFT — پیش‌نویس</option>';
echo '<option value="ACCEPTED">ACCEPTED — پذیرفته‌شده</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="terms_summary">خلاصه شرایط</label>';
echo '<textarea class="p1cc-textarea" id="terms_summary" name="terms_summary" maxlength="2000"></textarea></div>';

echo '</div>';
echo '<div class="p1cc-btn-row">';
echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت قرارداد</button>';
echo '<a class="p1cc-btn p1cc-btn-ghost" href="erp-customer-core-dashboard.php">انصراف</a>';
echo '</div>';
echo '</form>';

customer_core_render_foot();
