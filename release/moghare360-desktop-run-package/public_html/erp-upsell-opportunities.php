<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 Upsell Opportunities
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

$connection = false;
$errorMessage = '';
$opportunities = [];
$filterStatus = crm_get_string('opportunity_status');
$filterType = crm_get_string('opportunity_type');
$flash = crm_flash(crm_get_string('ok'));

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = crm_post_string('form_action');
        crm_require_auth($connection, 'crm.upsell.write');

        if ($action === 'create') {
            erp_csrf_require_valid('crm_upsell_create', $_POST['erp_csrf_token'] ?? null);
            $title = crm_post_string('opportunity_title');
            if ($title === '') {
                throw new RuntimeException('عنوان فرصت الزامی است.');
            }
            $dueAt = crm_post_string('due_at');
            $ok = crm_execute(
                $connection,
                'INSERT INTO dbo.erp_upsell_opportunities (customer_id, intake_id, vehicle_binding_id, operation_case_id, opportunity_code, opportunity_type, opportunity_title, opportunity_description, estimated_value, next_action, due_at, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    crm_post_int('customer_id'),
                    crm_post_int('intake_id'),
                    crm_post_int('vehicle_binding_id'),
                    crm_post_int('operation_case_id'),
                    crm_generate_upsell_code(),
                    crm_post_string('opportunity_type') ?: 'MANUAL',
                    $title,
                    crm_post_string('opportunity_description') ?: null,
                    crm_post_float('estimated_value'),
                    crm_post_string('next_action') ?: null,
                    $dueAt !== '' ? str_replace('T', ' ', $dueAt) : null,
                    crm_safe_current_user(),
                ]
            );
            if ($ok === false) {
                throw new RuntimeException('ثبت فرصت فروش انجام نشد.');
            }
            crm_safe_redirect('erp-upsell-opportunities.php?ok=upsell_ok');
        }

        if ($action === 'update_status') {
            erp_csrf_require_valid('crm_upsell_status', $_POST['erp_csrf_token'] ?? null);
            $upsellId = crm_post_int('upsell_id');
            $newStatus = crm_post_string('new_status');
            $allowed = ['OPEN', 'CONTACTED', 'WON', 'LOST', 'CANCELLED'];
            if ($upsellId === null || !in_array($newStatus, $allowed, true)) {
                throw new RuntimeException('وضعیت نامعتبر است.');
            }
            $old = crm_scalar($connection, 'SELECT opportunity_status FROM dbo.erp_upsell_opportunities WHERE upsell_id=?', [$upsellId]);
            crm_execute(
                $connection,
                'UPDATE dbo.erp_upsell_opportunities SET opportunity_status=?, updated_at=SYSUTCDATETIME(), updated_by=? WHERE upsell_id=?',
                [$newStatus, crm_safe_current_user(), $upsellId]
            );
            crm_insert_history($connection, 'UPSELL', $upsellId, 'STATUS_UPDATE', 'تغییر وضعیت فرصت فروش', $old, $newStatus);
            crm_safe_redirect('erp-upsell-opportunities.php?ok=status_ok');
        }
    }

    crm_require_auth($connection, 'crm.upsell.view');

    if (crm_table_exists($connection, 'erp_upsell_opportunities')) {
        $sql = 'SELECT TOP 100 upsell_id, opportunity_code, opportunity_type, opportunity_title, estimated_value, opportunity_status, customer_id, due_at, created_at FROM dbo.erp_upsell_opportunities WHERE 1=1';
        $params = [];
        if ($filterStatus !== '') {
            $sql .= ' AND opportunity_status = ?';
            $params[] = $filterStatus;
        }
        if ($filterType !== '') {
            $sql .= ' AND opportunity_type = ?';
            $params[] = $filterType;
        }
        $sql .= ' ORDER BY upsell_id DESC';
        $opportunities = crm_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'فرصت‌های فروش قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_render_head('فرصت‌های فروش');

echo '<div class="p6crm-hero"><h1>فرصت‌های فروش</h1><p>پیشنهاد خدمات/قطعات — بدون فاکتور رسمی و بدون ارسال پیام</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . crm_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    crm_error('فرصت فروش', $errorMessage);
}

echo '<div class="p1cc-card"><form method="get" class="p1cc-form-grid" style="align-items:end">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت</label><select class="p1cc-select" name="opportunity_status"><option value="">همه</option>';
foreach (['OPEN','CONTACTED','WON','LOST','CANCELLED'] as $st) {
    echo '<option value="' . $st . '"' . ($filterStatus === $st ? ' selected' : '') . '>' . $st . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="opportunity_type"><option value="">همه</option>';
foreach (['SERVICE_REMINDER','PART_RECOMMENDATION','WARRANTY_CHECK','SEASONAL_SERVICE','VIP_OFFER','MANUAL'] as $t) {
    echo '<option value="' . $t . '"' . ($filterType === $t ? ' selected' : '') . '>' . $t . '</option>';
}
echo '</select></div><button class="p1cc-btn" type="submit">فیلتر</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">ایجاد فرصت فروش</h2><form method="post">';
echo '<input type="hidden" name="form_action" value="create">';
echo erp_csrf_input('crm_upsell_create');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">عنوان *</label><input class="p1cc-input" name="opportunity_title" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="opportunity_type">';
foreach (['SERVICE_REMINDER','PART_RECOMMENDATION','WARRANTY_CHECK','SEASONAL_SERVICE','VIP_OFFER','MANUAL'] as $t) {
    echo '<option value="' . $t . '">' . $t . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مشتری</label><input class="p1cc-input m360-ltr" name="customer_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Intake</label><input class="p1cc-input m360-ltr" name="intake_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">پرونده</label><input class="p1cc-input m360-ltr" name="operation_case_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">ارزش تخمینی</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="estimated_value"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">اقدام بعدی</label><input class="p1cc-input" name="next_action" maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">موعد</label><input class="p1cc-input m360-ltr" type="datetime-local" name="due_at"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">توضیح</label><textarea class="p1cc-textarea" name="opportunity_description" maxlength="2000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت فرصت</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">لیست فرصت‌ها</h2>';
if ($opportunities === []) {
    echo '<p class="p1cc-hint">فرصتی ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نوع</th><th>عنوان</th><th>ارزش</th><th>وضعیت</th><th>موعد</th><th>تغییر وضعیت</th></tr></thead><tbody>';
    foreach ($opportunities as $o) {
        $uid = (int)($o['upsell_id'] ?? 0);
        $status = (string)($o['opportunity_status'] ?? '');
        echo '<tr>';
        echo '<td class="m360-ltr">' . crm_h($o['opportunity_code'] ?? '') . '</td>';
        echo '<td>' . crm_h($o['opportunity_type'] ?? '') . '</td>';
        echo '<td>' . crm_h($o['opportunity_title'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . crm_h($o['estimated_value'] !== '' ? number_format((float)$o['estimated_value'], 0) : '—') . '</td>';
        echo '<td><span class="p1cc-badge ' . crm_badge_class($status) . '">' . crm_h($status) . '</span></td>';
        echo '<td>' . crm_h($o['due_at'] !== '' ? $o['due_at'] : '—') . '</td>';
        echo '<td><form method="post" style="display:flex;gap:.35rem"><input type="hidden" name="form_action" value="update_status"><input type="hidden" name="upsell_id" value="' . $uid . '">';
        echo erp_csrf_input('crm_upsell_status');
        echo '<select class="p1cc-select" name="new_status">';
        foreach (['OPEN','CONTACTED','WON','LOST','CANCELLED'] as $st) {
            if ($st === $status) continue;
            echo '<option value="' . $st . '">' . $st . '</option>';
        }
        echo '</select><button class="p1cc-btn" type="submit">تغییر</button></form></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

crm_render_foot();
