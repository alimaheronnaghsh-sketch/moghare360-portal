<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 Submit Customer Satisfaction
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    crm_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('crm_satisfaction', $_POST['erp_csrf_token'] ?? null);

$overall = crm_validate_score_1_to_10(crm_post_int('overall_score'), true);
if ($overall === null) {
    crm_error('خطای اعتبارسنجی', 'امتیاز کلی باید بین ۱ تا ۱۰ باشد.');
}

$scores = [
    'service_quality_score' => crm_validate_score_1_to_10(crm_post_int('service_quality_score')),
    'delivery_score' => crm_validate_score_1_to_10(crm_post_int('delivery_score')),
    'price_score' => crm_validate_score_1_to_10(crm_post_int('price_score')),
    'staff_behavior_score' => crm_validate_score_1_to_10(crm_post_int('staff_behavior_score')),
    'comeback_probability_score' => crm_validate_score_1_to_10(crm_post_int('comeback_probability_score')),
];

foreach ($scores as $key => $val) {
    if (crm_post_string($key) !== '' && $val === null) {
        crm_error('خطای اعتبارسنجی', 'همه امتیازها باید بین ۱ تا ۱۰ باشند.');
    }
}

$followupScheduleId = crm_post_int('followup_schedule_id');
$customerId = crm_post_int('customer_id');
$operationCaseId = crm_post_int('operation_case_id');
$complaint = crm_post_string('complaint_text');
$positive = crm_post_string('positive_note');

$connection = false;
$redirectSchedule = $followupScheduleId;

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    crm_require_auth($connection, 'crm.satisfaction.write');

    if (!crm_table_exists($connection, 'erp_customer_satisfaction_surveys')) {
        throw new RuntimeException('جدول رضایت‌سنجی یافت نشد. ابتدا SQL فاز ۶ را اجرا کنید.');
    }

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت رضایت‌سنجی انجام نشد.');
    }

    $ok = crm_execute(
        $connection,
        'INSERT INTO dbo.erp_customer_satisfaction_surveys (followup_schedule_id, customer_id, operation_case_id, overall_score, service_quality_score, delivery_score, price_score, staff_behavior_score, comeback_probability_score, complaint_text, positive_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $followupScheduleId,
            $customerId,
            $operationCaseId,
            $overall,
            $scores['service_quality_score'],
            $scores['delivery_score'],
            $scores['price_score'],
            $scores['staff_behavior_score'],
            $scores['comeback_probability_score'],
            $complaint ?: null,
            $positive ?: null,
            crm_safe_current_user(),
        ]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت رضایت‌سنجی انجام نشد.');
    }

    $satisfactionId = crm_scope_identity($connection);

    $customerPreview = crm_get_customer_preview($connection, $customerId, $customerId);
    $mobile = $customerPreview['mobile'] ?? null;

    $scoreData = crm_calculate_customer_score($connection, [
        'overall_score' => $overall,
        'comeback_probability_score' => $scores['comeback_probability_score'] ?? $overall,
        'complaint_text' => $complaint,
        'customer_id' => $customerId,
        'intake_id' => $customerId,
    ]);

    crm_insert_score_card($connection, array_merge($scoreData, [
        'customer_id' => $customerId,
        'intake_id' => $customerId,
        'mobile' => $mobile,
        'has_complaint' => $complaint !== '',
        'score_note' => 'محاسبه از رضایت‌سنجی #' . ($satisfactionId ?? ''),
    ]));

    crm_insert_history($connection, 'SATISFACTION', $satisfactionId, 'CREATE', 'ثبت رضایت‌سنجی مشتری', null, (string)$overall);

    if ($complaint !== '') {
        crm_insert_history($connection, 'SATISFACTION', $satisfactionId, 'COMPLAINT', 'ثبت شکایت مشتری', null, substr($complaint, 0, 200));
        if (crm_table_exists($connection, 'erp_upsell_opportunities') && $customerId !== null) {
            crm_execute(
                $connection,
                'INSERT INTO dbo.erp_upsell_opportunities (customer_id, operation_case_id, opportunity_code, opportunity_type, opportunity_title, opportunity_description, opportunity_status, next_action, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
                [$customerId, $operationCaseId, crm_generate_upsell_code(), 'MANUAL', 'پیگیری شکایت مشتری', $complaint, 'OPEN', 'تماس مدیر', crm_safe_current_user()]
            );
        }
    }

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('ثبت رضایت‌سنجی انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    crm_error('خطا', 'ثبت رضایت‌سنجی انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

if ($redirectSchedule !== null) {
    crm_safe_redirect('erp-crm-followup-detail.php?followup_schedule_id=' . $redirectSchedule . '&ok=satisfaction_ok');
}
crm_safe_redirect('erp-customer-score-board.php?ok=satisfaction_ok');
