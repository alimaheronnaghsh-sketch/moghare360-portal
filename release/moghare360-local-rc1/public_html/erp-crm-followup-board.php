<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 CRM Follow-up Board
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

$connection = false;
$errorMessage = '';
$schedules = [];
$summary = ['PENDING' => 0, 'CONTACTED' => 0, 'COMPLETED' => 0, 'NO_ANSWER' => 0, 'RESCHEDULED' => 0, 'COMPLAINT' => 0];
$filterStatus = crm_get_string('followup_status');
$filterReason = crm_get_string('followup_reason');
$filterDate = crm_get_string('scheduled_date');
$flash = crm_flash(crm_get_string('ok'));

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && crm_post_string('form_action') === 'create_manual') {
        erp_csrf_require_valid('crm_followup_create', $_POST['erp_csrf_token'] ?? null);
        crm_require_auth($connection, 'crm.followup.write');

        $scheduledAt = crm_post_string('scheduled_at');
        if ($scheduledAt === '') {
            $scheduledAt = date('Y-m-d H:i:s', strtotime('+3 days'));
        }

        $code = crm_generate_followup_code();
        $ok = crm_execute(
            $connection,
            'INSERT INTO dbo.erp_crm_followup_schedules (customer_id, intake_id, vehicle_binding_id, operation_case_id, followup_code, followup_reason, scheduled_at, priority_level, assigned_to_text, source_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                crm_post_int('customer_id'),
                crm_post_int('intake_id'),
                crm_post_int('vehicle_binding_id'),
                crm_post_int('operation_case_id'),
                $code,
                crm_post_string('followup_reason') ?: 'MANUAL',
                str_replace('T', ' ', $scheduledAt),
                crm_post_string('priority_level') ?: 'NORMAL',
                crm_post_string('assigned_to_text') ?: null,
                crm_post_string('source_note') ?: null,
                crm_safe_current_user(),
            ]
        );
        if ($ok === false) {
            throw new RuntimeException('ثبت پیگیری انجام نشد.');
        }
        $sid = crm_scope_identity($connection);
        crm_insert_history($connection, 'FOLLOWUP_SCHEDULE', $sid, 'CREATE', 'ثبت پیگیری دستی', null, $code);
        crm_safe_redirect('erp-crm-followup-board.php?ok=schedule_ok');
    }

    crm_require_auth($connection, 'crm.followup.view');

    if (crm_table_exists($connection, 'erp_crm_followup_schedules')) {
        foreach (array_keys($summary) as $st) {
            if ($st === 'COMPLAINT') {
                continue;
            }
            $summary[$st] = (int)(crm_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_crm_followup_schedules WHERE followup_status=?', [$st]) ?? '0');
        }
        if (crm_table_exists($connection, 'erp_crm_followup_records')) {
            $summary['COMPLAINT'] = (int)(crm_scalar(
                $connection,
                "SELECT COUNT(*) FROM dbo.erp_crm_followup_records WHERE contact_result IN ('COMPLAINT','NEEDS_MANAGER')"
            ) ?? '0');
        }

        $sql = 'SELECT TOP 100 followup_schedule_id, followup_code, followup_reason, scheduled_at, followup_status, priority_level, customer_id, operation_case_id, assigned_to_text FROM dbo.erp_crm_followup_schedules WHERE 1=1';
        $params = [];
        if ($filterStatus !== '') {
            $sql .= ' AND followup_status = ?';
            $params[] = $filterStatus;
        }
        if ($filterReason !== '') {
            $sql .= ' AND followup_reason = ?';
            $params[] = $filterReason;
        }
        if ($filterDate !== '') {
            $sql .= ' AND CAST(scheduled_at AS DATE) = ?';
            $params[] = $filterDate;
        }
        $sql .= ' ORDER BY scheduled_at ASC, followup_schedule_id DESC';
        $schedules = crm_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'تابلو پیگیری CRM قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_render_head('تابلو پیگیری CRM');

echo '<div class="p6crm-hero"><h1>تابلو پیگیری CRM</h1><p>پیگیری پس از تحویل — بدون ارسال SMS/WhatsApp/Email</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . crm_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    crm_error('تابلو پیگیری', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">خلاصه وضعیت</h2><div class="p6crm-kpi-grid">';
foreach (['PENDING' => 'در انتظار', 'CONTACTED' => 'تماس گرفته', 'COMPLETED' => 'تکمیل', 'NO_ANSWER' => 'بدون پاسخ', 'RESCHEDULED' => 'زمان‌بندی مجدد', 'COMPLAINT' => 'شکایت/مدیر'] as $k => $l) {
    echo '<div class="p6crm-kpi"><div class="label">' . $l . '</div><div class="value m360-num">' . crm_h((string)$summary[$k]) . '</div></div>';
}
echo '</div></div>';

echo '<div class="p1cc-card"><form method="get" class="p1cc-form-grid" style="align-items:end">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت</label><select class="p1cc-select" name="followup_status"><option value="">همه</option>';
foreach (['PENDING','CONTACTED','NO_ANSWER','RESCHEDULED','COMPLETED','CANCELLED'] as $st) {
    echo '<option value="' . $st . '"' . ($filterStatus === $st ? ' selected' : '') . '>' . $st . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">دلیل</label><select class="p1cc-select" name="followup_reason"><option value="">همه</option>';
foreach (['POST_DELIVERY','SATISFACTION','COMPLAINT','VIP_CARE','UPSELL','PAYMENT_REMINDER','MANUAL'] as $r) {
    echo '<option value="' . $r . '"' . ($filterReason === $r ? ' selected' : '') . '>' . $r . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ</label><input class="p1cc-input m360-ltr" type="date" name="scheduled_date" value="' . crm_h($filterDate) . '"></div>';
echo '<button class="p1cc-btn" type="submit">فیلتر</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">لیست پیگیری‌ها</h2>';
if ($schedules === []) {
    echo '<p class="p1cc-hint">پیگیری‌ای ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>دلیل</th><th>زمان</th><th>وضعیت</th><th>اولویت</th><th>مشتری</th><th></th></tr></thead><tbody>';
    foreach ($schedules as $row) {
        $id = (int)($row['followup_schedule_id'] ?? 0);
        echo '<tr>';
        echo '<td class="m360-ltr">' . crm_h($row['followup_code'] ?? '') . '</td>';
        echo '<td>' . crm_h($row['followup_reason'] ?? '') . '</td>';
        echo '<td>' . crm_h($row['scheduled_at'] ?? '') . '</td>';
        echo '<td><span class="p1cc-badge ' . crm_badge_class($row['followup_status'] ?? '') . '">' . crm_h($row['followup_status'] ?? '') . '</span></td>';
        echo '<td>' . crm_h($row['priority_level'] ?? '') . '</td>';
        echo '<td>' . crm_h($row['customer_id'] !== '' ? $row['customer_id'] : '—') . '</td>';
        echo '<td><a href="erp-crm-followup-detail.php?followup_schedule_id=' . $id . '">جزئیات</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">ثبت پیگیری دستی</h2><form method="post">';
echo '<input type="hidden" name="form_action" value="create_manual">';
echo erp_csrf_input('crm_followup_create');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مشتری</label><input class="p1cc-input m360-ltr" name="customer_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Intake</label><input class="p1cc-input m360-ltr" name="intake_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">پرونده عملیات</label><input class="p1cc-input m360-ltr" name="operation_case_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">اتصال خودرو</label><input class="p1cc-input m360-ltr" name="vehicle_binding_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">دلیل</label><select class="p1cc-select" name="followup_reason">';
foreach (['POST_DELIVERY','SATISFACTION','COMPLAINT','VIP_CARE','UPSELL','PAYMENT_REMINDER','MANUAL'] as $r) {
    echo '<option value="' . $r . '">' . $r . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">زمان پیگیری</label><input class="p1cc-input m360-ltr" type="datetime-local" name="scheduled_at"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">اولویت</label><select class="p1cc-select" name="priority_level"><option value="NORMAL">NORMAL</option><option value="HIGH">HIGH</option><option value="URGENT">URGENT</option></select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مسئول</label><input class="p1cc-input" name="assigned_to_text" maxlength="200"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="source_note" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت پیگیری</button></form></div>';

crm_render_foot();
