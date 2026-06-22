<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Technician Board (read-only)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

$filterStatus = strtoupper(operation_engine_get_string('status'));
$connection = false;
$errorMessage = '';
$steps = [];

$validFilters = ['', ...ERP_PHASE2_STEP_STATUSES];

if ($filterStatus !== '' && !in_array($filterStatus, ERP_PHASE2_STEP_STATUSES, true)) {
    $filterStatus = '';
}

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.technician.board.view');

    if (operation_engine_table_exists($connection, 'erp_operation_service_steps')
        && operation_engine_table_exists($connection, 'erp_operation_cases')) {
        $sql = 'SELECT TOP 50
                    s.service_step_id,
                    s.operation_case_id,
                    s.step_title,
                    s.step_type,
                    s.step_status,
                    s.progress_percent,
                    s.assigned_technician_text,
                    s.started_at,
                    s.completed_at,
                    c.operation_code,
                    c.current_stage,
                    c.current_status
                FROM dbo.erp_operation_service_steps s
                INNER JOIN dbo.erp_operation_cases c ON c.operation_case_id = s.operation_case_id
                WHERE s.step_status <> ?';

        $params = ['CANCELLED'];

        if ($filterStatus !== '') {
            $sql .= ' AND s.step_status = ?';
            $params[] = $filterStatus;
        }

        $sql .= ' ORDER BY s.service_step_id DESC';
        $steps = operation_engine_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'نمایش تابلوی تکنسین با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

operation_engine_render_head('تابلوی تکنسین', true);

echo '<div class="p2oe-hero">';
echo '<h1>تابلوی تکنسین</h1>';
echo '<p>نمایش مراحل سرویس بر اساس تکنسین و وضعیت</p>';
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p2oe-section-title">فیلتر وضعیت</h2>';
echo '<form method="get" class="p2oe-form-inline">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="status">وضعیت مرحله</label>';
echo '<select class="p1cc-select" id="status" name="status">';
echo '<option value="">همه</option>';

foreach (ERP_PHASE2_STEP_STATUSES as $status) {
    $selected = $filterStatus === $status ? ' selected' : '';
    echo '<option value="' . operation_engine_h($status) . '"' . $selected . '>' . operation_engine_h($status) . '</option>';
}

echo '</select></div>';
echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">اعمال فیلتر</button>';
echo '</form></div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . operation_engine_h($errorMessage) . '</p></div>';
} elseif ($steps === []) {
    echo '<div class="p1cc-card"><p class="p1cc-hint">مرحله سرویسی یافت نشد. ابتدا SQL فاز ۲ را اجرا و پرونده عملیاتی بسازید.</p></div>';
} else {
    echo '<div class="p1cc-card"><table class="p1cc-table"><thead><tr>';
    echo '<th>کد عملیات</th><th>عنوان مرحله</th><th>نوع</th><th>تکنسین</th><th>وضعیت</th><th>پیشرفت</th><th>Stage پرونده</th><th></th>';
    echo '</tr></thead><tbody>';

    foreach ($steps as $step) {
        $caseId = $step['operation_case_id'] ?? '';
        echo '<tr>';
        echo '<td><span class="p1cc-badge p1cc-badge-new m360-ltr">' . operation_engine_h($step['operation_code'] ?? '') . '</span></td>';
        echo '<td>' . operation_engine_h($step['step_title'] ?? '') . '</td>';
        echo '<td>' . operation_engine_h($step['step_type'] ?? '') . '</td>';
        echo '<td>' . operation_engine_h($step['assigned_technician_text'] !== '' ? $step['assigned_technician_text'] : '—') . '</td>';
        echo '<td><span class="p1cc-badge ' . operation_engine_badge_class($step['step_status'] ?? '') . '">' . operation_engine_h($step['step_status'] ?? '') . '</span></td>';
        echo '<td><span class="m360-num">' . operation_engine_h($step['progress_percent'] ?? '0') . '%</span></td>';
        echo '<td><span class="p1cc-badge ' . operation_engine_badge_class($step['current_stage'] ?? '') . '">' . operation_engine_h($step['current_stage'] ?? '') . '</span></td>';
        echo '<td><a class="p2oe-link" href="erp-jobcard-operation-flow.php?operation_case_id=' . operation_engine_h($caseId) . '">جزئیات</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

echo '<div class="p1cc-card"><p class="p1cc-hint"><a class="p2oe-link" href="erp-hr-dashboard.php">داشبورد HR (Phase 7)</a> — پورتال اداری داخلی، بدون اتصال پرسنلی تکنسین</p></div>';

operation_engine_render_foot();
