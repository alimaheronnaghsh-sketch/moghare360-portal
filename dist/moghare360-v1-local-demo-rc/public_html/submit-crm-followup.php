<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 Submit CRM Follow-up Record
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    crm_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('crm_followup_record', $_POST['erp_csrf_token'] ?? null);

$scheduleId = crm_post_int('followup_schedule_id');
$channel = crm_post_string('contact_channel');
$result = crm_post_string('contact_result');
$sentiment = crm_post_string('customer_sentiment');
$note = crm_post_string('followup_note');
$nextAt = crm_post_string('next_followup_at');

if ($scheduleId === null || $channel === '' || $result === '') {
    crm_error('خطای اعتبارسنجی', 'شناسه پیگیری، کانال و نتیجه تماس الزامی است.');
}

$connection = false;

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    crm_require_auth($connection, 'crm.followup.write');

    $schedule = crm_get_schedule($connection, $scheduleId);
    if ($schedule === null) {
        throw new RuntimeException('پیگیری یافت نشد.');
    }

    $nextFollowupSql = $nextAt !== '' ? str_replace('T', ' ', $nextAt) : null;
    $newStatus = crm_map_contact_to_status($result, $nextFollowupSql);

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت تماس انجام نشد.');
    }

    $ok = crm_execute(
        $connection,
        'INSERT INTO dbo.erp_crm_followup_records (followup_schedule_id, customer_id, operation_case_id, contact_channel, contact_result, customer_sentiment, followup_note, next_followup_at, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [
            $scheduleId,
            ctype_digit((string)($schedule['customer_id'] ?? '')) ? (int)$schedule['customer_id'] : null,
            ctype_digit((string)($schedule['operation_case_id'] ?? '')) ? (int)$schedule['operation_case_id'] : null,
            $channel,
            $result,
            $sentiment ?: null,
            $note ?: null,
            $nextFollowupSql,
            crm_safe_current_user(),
            crm_client_ip(),
            crm_user_agent(),
        ]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت تماس انجام نشد.');
    }

    $recordId = crm_scope_identity($connection);

    crm_execute(
        $connection,
        'UPDATE dbo.erp_crm_followup_schedules SET followup_status=?, updated_at=SYSUTCDATETIME(), updated_by=? WHERE followup_schedule_id=?',
        [$newStatus, crm_safe_current_user(), $scheduleId]
    );

    if ($nextFollowupSql !== null) {
        crm_create_post_delivery_followup($connection, [
            'customer_id' => $schedule['customer_id'] ?? null,
            'intake_id' => $schedule['intake_id'] ?? null,
            'operation_case_id' => $schedule['operation_case_id'] ?? null,
            'vehicle_binding_id' => $schedule['vehicle_binding_id'] ?? null,
            'followup_reason' => 'MANUAL',
            'priority_level' => $schedule['priority_level'] ?? 'NORMAL',
            'scheduled_at' => $nextFollowupSql,
            'source_note' => 'پیگیری مجدد پس از تماس — ' . ($note ?: ''),
        ]);
    }

    crm_insert_history($connection, 'FOLLOWUP_RECORD', $recordId, 'CREATE', 'ثبت نتیجه تماس', $schedule['followup_status'] ?? null, $newStatus);

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('ثبت تماس انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    crm_error('خطا', 'ثبت نتیجه تماس انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_safe_redirect('erp-crm-followup-detail.php?followup_schedule_id=' . $scheduleId . '&ok=followup_ok');
