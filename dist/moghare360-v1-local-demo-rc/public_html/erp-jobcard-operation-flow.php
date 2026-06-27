<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 JobCard Operation Flow
 *
 * List / detail / controlled forms for service, QC, delivery.
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

$operationCaseId = operation_engine_get_int('operation_case_id');
$flashMessage = operation_engine_flash_message(operation_engine_get_string('phase2'));
$connection = false;
$errorMessage = '';
$caseList = [];
$case = null;
$steps = [];
$qcDecisions = [];
$deliveryChecks = [];
$history = [];
$jobcard = null;
$intake = null;
$binding = null;
$contract = null;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.flow.view');

    if (!operation_engine_table_exists($connection, 'erp_operation_cases')) {
        throw new RuntimeException('جدول erp_operation_cases یافت نشد. ابتدا SQL فاز ۲ را اجرا کنید.');
    }

    if ($operationCaseId === null) {
        $caseList = operation_engine_fetch_rows(
            $connection,
            'SELECT TOP 30 operation_case_id, operation_code, current_stage, current_status,
                    priority_level, jobcard_id, customer_id, created_at
             FROM dbo.erp_operation_cases
             ORDER BY operation_case_id DESC'
        );
    } else {
        $case = operation_engine_load_case($connection, $operationCaseId);

        if ($case === null) {
            throw new RuntimeException('پرونده عملیاتی یافت نشد.');
        }

        if (operation_engine_table_exists($connection, 'erp_operation_service_steps')) {
            $steps = operation_engine_fetch_rows(
                $connection,
                'SELECT service_step_id, step_type, step_title, step_status, progress_percent,
                        assigned_technician_text, started_at, completed_at
                 FROM dbo.erp_operation_service_steps
                 WHERE operation_case_id = ?
                 ORDER BY service_step_id ASC',
                [$operationCaseId]
            );
        }

        if (operation_engine_table_exists($connection, 'erp_operation_qc_decisions')) {
            $qcDecisions = operation_engine_fetch_rows(
                $connection,
                'SELECT qc_decision_id, decision_status, decision_note, return_to_stage, decided_at, decided_by
                 FROM dbo.erp_operation_qc_decisions
                 WHERE operation_case_id = ?
                 ORDER BY decided_at DESC',
                [$operationCaseId]
            );
        }

        if (operation_engine_table_exists($connection, 'erp_operation_delivery_checks')) {
            $deliveryChecks = operation_engine_fetch_rows(
                $connection,
                'SELECT delivery_check_id, is_ready_for_delivery, final_note, checked_at, checked_by
                 FROM dbo.erp_operation_delivery_checks
                 WHERE operation_case_id = ?
                 ORDER BY checked_at DESC',
                [$operationCaseId]
            );
        }

        if (operation_engine_table_exists($connection, 'erp_operation_history')) {
            $history = operation_engine_fetch_rows(
                $connection,
                'SELECT TOP 30 history_id, entity_type, action_type, action_summary, created_at, created_by
                 FROM dbo.erp_operation_history
                 WHERE entity_id = ? OR (entity_type = ? AND entity_id = ?)
                 ORDER BY history_id DESC',
                [$operationCaseId, 'erp_operation_cases', $operationCaseId]
            );
        }

        $jobcardId = ($case['jobcard_id'] ?? '') !== '' ? (int)$case['jobcard_id'] : null;

        if ($jobcardId !== null && operation_engine_table_exists($connection, 'erp_jobcards')) {
            $jcRows = operation_engine_fetch_rows(
                $connection,
                'SELECT TOP 1 jobcard_id, jobcard_number, customer_id, vehicle_id, jobcard_status, lifecycle_state
                 FROM dbo.erp_jobcards WHERE jobcard_id = ?',
                [$jobcardId]
            );
            $jobcard = $jcRows[0] ?? null;
        }

        $intakeId = ($case['intake_id'] ?? '') !== '' ? (int)$case['intake_id'] : null;

        if ($intakeId !== null && operation_engine_table_exists($connection, 'erp_customer_intakes')) {
            $inRows = operation_engine_fetch_rows(
                $connection,
                'SELECT TOP 1 intake_id, full_name, mobile, license_plate, status
                 FROM dbo.erp_customer_intakes WHERE intake_id = ?',
                [$intakeId]
            );
            $intake = $inRows[0] ?? null;
        }

        $bindingId = ($case['vehicle_binding_id'] ?? '') !== '' ? (int)$case['vehicle_binding_id'] : null;

        if ($bindingId !== null && operation_engine_table_exists($connection, 'erp_customer_vehicle_bindings')) {
            $vbRows = operation_engine_fetch_rows(
                $connection,
                'SELECT TOP 1 binding_id, license_plate, vin, brand, model, binding_status
                 FROM dbo.erp_customer_vehicle_bindings WHERE binding_id = ?',
                [$bindingId]
            );
            $binding = $vbRows[0] ?? null;
        }

        $contractId = ($case['contract_id'] ?? '') !== '' ? (int)$case['contract_id'] : null;

        if ($contractId !== null && operation_engine_table_exists($connection, 'erp_customer_contracts')) {
            $ctRows = operation_engine_fetch_rows(
                $connection,
                'SELECT TOP 1 contract_id, contract_code, contract_type, status
                 FROM dbo.erp_customer_contracts WHERE contract_id = ?',
                [$contractId]
            );
            $contract = $ctRows[0] ?? null;
        }
    }
} catch (Throwable) {
    $errorMessage = 'نمایش جریان عملیاتی با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

operation_engine_render_head('جریان عملیاتی JobCard', $operationCaseId === null);

echo '<div class="p2oe-hero">';
echo '<h1>جریان عملیاتی JobCard</h1>';
echo '<p>اتصال Phase 1 Customer Core به JobCard / Service / QC / Delivery</p>';
echo '<p style="margin-top:0.5rem;font-size:0.85rem"><a class="p2oe-link" href="erp-rule-test-console.php" style="color:#fff;text-decoration:underline">کنسول Rule Check (Phase 3)</a></p>';
echo '</div>';

if ($flashMessage !== '') {
    echo '<div class="p1cc-flash">' . operation_engine_h($flashMessage) . '</div>';
}

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . operation_engine_h($errorMessage) . '</p></div>';
    operation_engine_render_foot();
    exit;
}

if ($operationCaseId === null) {
    echo '<div class="p1cc-card"><h2 class="p2oe-section-title">ایجاد پرونده عملیاتی جدید</h2>';
    echo '<form method="post" action="submit-operation-case-create.php">';
    echo erp_csrf_input('operation_case_create');
    echo '<div class="p1cc-form-grid">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">JobCard ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="jobcard_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">Intake ID (Phase 1)</label><input class="p1cc-input p1cc-input-ltr" type="number" name="intake_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">Customer ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="customer_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">Vehicle Binding ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="vehicle_binding_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">Contract ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="contract_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">اولویت</label><select class="p1cc-select" name="priority_level"><option value="NORMAL">NORMAL</option><option value="HIGH">HIGH</option><option value="URGENT">URGENT</option></select></div>';
    echo '<div class="p1cc-form-group full"><label class="p1cc-label">خلاصه پذیرش</label><textarea class="p1cc-textarea" name="reception_summary" maxlength="1500"></textarea></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">عنوان مرحله اول (اختیاری)</label><input class="p1cc-input" type="text" name="step_title" maxlength="300" placeholder="مثلاً: بازدید اولیه"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع مرحله</label><select class="p1cc-select" name="step_type">';
    foreach (ERP_PHASE2_STEP_TYPES as $type) {
        echo '<option value="' . operation_engine_h($type) . '">' . operation_engine_h($type) . '</option>';
    }
    echo '</select></div>';
    echo '</div><div class="p1cc-btn-row"><button class="p1cc-btn p1cc-btn-primary" type="submit">ایجاد پرونده</button></div>';
    echo '</form></div>';

    echo '<div class="p1cc-card"><h2 class="p2oe-section-title">آخرین پرونده‌های عملیاتی</h2>';

    if ($caseList === []) {
        echo '<p class="p1cc-hint">هنوز پرونده‌ای ثبت نشده است.</p>';
    } else {
        echo '<table class="p1cc-table p2oe-flow-list"><thead><tr>';
        echo '<th>کد</th><th>Stage</th><th>Status</th><th>JobCard</th><th>تاریخ</th><th></th></tr></thead><tbody>';

        foreach ($caseList as $row) {
            $id = $row['operation_case_id'] ?? '';
            echo '<tr>';
            echo '<td class="m360-ltr">' . operation_engine_h($row['operation_code'] ?? '') . '</td>';
            echo '<td><span class="p1cc-badge ' . operation_engine_badge_class($row['current_stage'] ?? '') . '">' . operation_engine_h($row['current_stage'] ?? '') . '</span></td>';
            echo '<td><span class="p1cc-badge ' . operation_engine_badge_class($row['current_status'] ?? '') . '">' . operation_engine_h($row['current_status'] ?? '') . '</span></td>';
            echo '<td>' . operation_engine_h($row['jobcard_id'] !== '' ? $row['jobcard_id'] : '—') . '</td>';
            echo '<td>' . operation_engine_h($row['created_at'] ?? '') . '</td>';
            echo '<td><a class="p2oe-link" href="?operation_case_id=' . operation_engine_h($id) . '">مشاهده</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p2oe-section-title">پرونده: <span class="m360-ltr">' . operation_engine_h($case['operation_code'] ?? '') . '</span></h2>';
    echo '<div class="p2oe-meta-grid">';
    echo '<div class="p2oe-meta-item"><span>Stage</span><span class="p1cc-badge ' . operation_engine_badge_class($case['current_stage'] ?? '') . '">' . operation_engine_h($case['current_stage'] ?? '') . '</span></div>';
    echo '<div class="p2oe-meta-item"><span>Status</span><span class="p1cc-badge ' . operation_engine_badge_class($case['current_status'] ?? '') . '">' . operation_engine_h($case['current_status'] ?? '') . '</span></div>';
    echo '<div class="p2oe-meta-item"><span>اولویت</span>' . operation_engine_h($case['priority_level'] ?? '') . '</div>';
    echo '<div class="p2oe-meta-item"><span>JobCard ID</span>' . operation_engine_h($case['jobcard_id'] !== '' ? $case['jobcard_id'] : '—') . '</div>';
    echo '<div class="p2oe-meta-item"><span>Customer ID</span>' . operation_engine_h($case['customer_id'] !== '' ? $case['customer_id'] : '—') . '</div>';
    echo '</div>';
    echo '<p style="margin-top:0.75rem"><a class="p2oe-link" href="erp-jobcard-cost-preview.php?operation_case_id=' . (int)$operationCaseId . '">هزینه JobCard (Phase 5)</a></p>';

    if (($case['reception_summary'] ?? '') !== '') {
        echo '<p class="p1cc-hint" style="margin-top:0.75rem">' . operation_engine_h($case['reception_summary']) . '</p>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h3 class="p2oe-section-title">پیوندها (Phase 1 + M17)</h3><div class="p2oe-meta-grid">';

    if ($jobcard !== null) {
        echo '<div class="p2oe-meta-item"><span>JobCard</span>' . operation_engine_h($jobcard['jobcard_number'] ?? '') . ' — ' . operation_engine_h($jobcard['jobcard_status'] ?? '') . '</div>';
    } else {
        echo '<div class="p2oe-meta-item"><span>JobCard</span><em>در دسترس نیست</em></div>';
    }

    if ($intake !== null) {
        echo '<div class="p2oe-meta-item"><span>Intake</span>' . operation_engine_h($intake['full_name'] ?? '') . ' — ' . operation_engine_h($intake['mobile'] ?? '') . '</div>';
    }

    if ($binding !== null) {
        echo '<div class="p2oe-meta-item"><span>خودرو</span>' . operation_engine_h($binding['license_plate'] ?? '') . ' ' . operation_engine_h(trim(($binding['brand'] ?? '') . ' ' . ($binding['model'] ?? ''))) . '</div>';
    }

    if ($contract !== null) {
        echo '<div class="p2oe-meta-item"><span>قرارداد</span>' . operation_engine_h($contract['contract_code'] ?? '') . '</div>';
    }

    echo '</div></div>';

    echo '<div class="p1cc-card"><h3 class="p2oe-section-title">مراحل سرویس</h3>';

    if ($steps === []) {
        echo '<p class="p1cc-hint">مرحله‌ای ثبت نشده است.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr><th>#</th><th>عنوان</th><th>نوع</th><th>تکنسین</th><th>وضعیت</th><th>پیشرفت</th></tr></thead><tbody>';

        foreach ($steps as $step) {
            echo '<tr>';
            echo '<td>' . operation_engine_h($step['service_step_id'] ?? '') . '</td>';
            echo '<td>' . operation_engine_h($step['step_title'] ?? '') . '</td>';
            echo '<td>' . operation_engine_h($step['step_type'] ?? '') . '</td>';
            echo '<td>' . operation_engine_h($step['assigned_technician_text'] !== '' ? $step['assigned_technician_text'] : '—') . '</td>';
            echo '<td><span class="p1cc-badge ' . operation_engine_badge_class($step['step_status'] ?? '') . '">' . operation_engine_h($step['step_status'] ?? '') . '</span></td>';
            echo '<td class="m360-num">' . operation_engine_h($step['progress_percent'] ?? '0') . '%</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '<h4 style="margin-top:1rem">افزودن / به‌روزرسانی مرحله سرویس</h4>';
    echo '<form method="post" action="submit-service-status-update.php">';
    echo erp_csrf_input('operation_service_update');
    echo '<input type="hidden" name="operation_case_id" value="' . operation_engine_h((string)$operationCaseId) . '">';
    echo '<div class="p2oe-form-inline">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">Service Step ID (برای ویرایش)</label><input class="p1cc-input p1cc-input-ltr" type="number" name="service_step_id" min="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">عنوان مرحله جدید</label><input class="p1cc-input" type="text" name="step_title" maxlength="300"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="step_type">';
    foreach (ERP_PHASE2_STEP_TYPES as $type) {
        echo '<option value="' . operation_engine_h($type) . '">' . operation_engine_h($type) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">تکنسین</label><input class="p1cc-input" type="text" name="assigned_technician_text" maxlength="200"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت</label><select class="p1cc-select" name="step_status">';
    foreach (ERP_PHASE2_STEP_STATUSES as $st) {
        echo '<option value="' . operation_engine_h($st) . '">' . operation_engine_h($st) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">پیشرفت %</label><input class="p1cc-input p1cc-input-ltr" type="number" name="progress_percent" min="0" max="100" value="0"></div>';
    echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت سرویس</button>';
    echo '</div></form></div>';

    echo '<div class="p1cc-card"><h3 class="p2oe-section-title">تصمیم QC</h3>';

    if ($qcDecisions !== []) {
        echo '<table class="p1cc-table"><thead><tr><th>وضعیت</th><th>یادداشت</th><th>تاریخ</th></tr></thead><tbody>';

        foreach ($qcDecisions as $qc) {
            echo '<tr><td><span class="p1cc-badge ' . operation_engine_badge_class($qc['decision_status'] ?? '') . '">' . operation_engine_h($qc['decision_status'] ?? '') . '</span></td>';
            echo '<td>' . operation_engine_h($qc['decision_note'] !== '' ? $qc['decision_note'] : '—') . '</td>';
            echo '<td>' . operation_engine_h($qc['decided_at'] ?? '') . '</td></tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="p1cc-hint">هنوز تصمیم QC ثبت نشده است.</p>';
    }

    echo '<form method="post" action="submit-qc-decision.php" style="margin-top:1rem">';
    echo erp_csrf_input('operation_qc_decision');
    echo '<input type="hidden" name="operation_case_id" value="' . operation_engine_h((string)$operationCaseId) . '">';
    echo '<div class="p2oe-form-inline">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">تصمیم</label><select class="p1cc-select" name="decision_status" required>';
    foreach (ERP_PHASE2_QC_DECISION_STATUSES as $ds) {
        echo '<option value="' . operation_engine_h($ds) . '">' . operation_engine_h($ds) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">یادداشت</label><input class="p1cc-input" type="text" name="decision_note" maxlength="1500"></div>';
    echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت QC</button>';
    echo '</div></form></div>';

    echo '<div class="p1cc-card"><h3 class="p2oe-section-title">بررسی نهایی تحویل</h3>';

    if ($deliveryChecks !== []) {
        echo '<table class="p1cc-table"><thead><tr><th>آماده تحویل</th><th>یادداشت</th><th>تاریخ</th></tr></thead><tbody>';

        foreach ($deliveryChecks as $dc) {
            $ready = ($dc['is_ready_for_delivery'] ?? '0') === '1' ? 'بله' : 'خیر';
            echo '<tr><td>' . operation_engine_h($ready) . '</td>';
            echo '<td>' . operation_engine_h($dc['final_note'] !== '' ? $dc['final_note'] : '—') . '</td>';
            echo '<td>' . operation_engine_h($dc['checked_at'] ?? '') . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    echo '<form method="post" action="submit-delivery-final-check.php" style="margin-top:1rem">';
    echo erp_csrf_input('operation_delivery_check');
    echo '<input type="hidden" name="operation_case_id" value="' . operation_engine_h((string)$operationCaseId) . '">';
    echo '<div class="p2oe-form-inline">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">آماده تحویل</label><select class="p1cc-select" name="is_ready_for_delivery"><option value="0">خیر</option><option value="1">بله</option></select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">نتیجه</label><select class="p1cc-select" name="delivery_outcome"><option value="READY_FOR_DELIVERY">READY_FOR_DELIVERY</option><option value="DELIVERED">DELIVERED</option><option value="HOLD">HOLD</option></select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">یادداشت</label><input class="p1cc-input" type="text" name="final_note" maxlength="1500"></div>';
    echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت تحویل</button>';
    echo '</div></form></div>';

    if ($history !== []) {
        echo '<div class="p1cc-card"><h3 class="p2oe-section-title">تاریخچه</h3><table class="p1cc-table"><thead><tr><th>عملیات</th><th>خلاصه</th><th>تاریخ</th></tr></thead><tbody>';

        foreach ($history as $h) {
            echo '<tr><td>' . operation_engine_h($h['action_type'] ?? '') . '</td>';
            echo '<td>' . operation_engine_h($h['action_summary'] ?? '') . '</td>';
            echo '<td>' . operation_engine_h($h['created_at'] ?? '') . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    echo '<p><a class="p2oe-link" href="erp-jobcard-operation-flow.php">← بازگشت به لیست</a></p>';
}

operation_engine_render_foot();
