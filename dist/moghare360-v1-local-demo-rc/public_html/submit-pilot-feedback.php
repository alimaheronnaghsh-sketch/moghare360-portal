<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    pilot_error('خطا', 'فقط درخواست POST مجاز است.');
}

pilot_csrf_require_valid(PILOT_CSRF_FEEDBACK_CREATE, $_POST['erp_csrf_token'] ?? null, 'erp-pilot-feedback.php');

$scenarioRaw = trim((string)($_POST['scenario_id'] ?? ''));
$scenarioId = $scenarioRaw !== '' && is_numeric($scenarioRaw) ? (int)$scenarioRaw : null;
$role = strtolower(trim((string)($_POST['feedback_role'] ?? '')));
$pageModule = trim((string)($_POST['page_or_module'] ?? ''));
$issueType = strtolower(trim((string)($_POST['issue_type'] ?? '')));
$severity = strtolower(trim((string)($_POST['severity'] ?? 'low')));
$note = trim((string)($_POST['feedback_note'] ?? ''));
$suggestedFix = trim((string)($_POST['suggested_fix'] ?? ''));

$validRoles = ['owner', 'reception', 'technician', 'warehouse', 'finance', 'crm', 'hr', 'manager'];
$validTypes = ['bug', 'ux', 'missing_flow', 'data_problem', 'training_need', 'other'];
$validSev = ['low', 'medium', 'high', 'blocker'];

if ($note === '') {
    pilot_error('خطای اعتبارسنجی', 'توضیح بازخورد الزامی است.');
}
if (!in_array($role, $validRoles, true)) {
    pilot_error('خطای اعتبارسنجی', 'نقش نامعتبر است.');
}
if (!in_array($issueType, $validTypes, true)) {
    pilot_error('خطای اعتبارسنجی', 'نوع مسئله نامعتبر است.');
}
if (!in_array($severity, $validSev, true)) {
    pilot_error('خطای اعتبارسنجی', 'شدت نامعتبر است.');
}

$c = false;
try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('اتصال برقرار نشد.');
    }
    pilot_require_auth($c, 'pilot.feedback.write');

    if (!pilot_table_exists($c, 'erp_soft_run_pilot_feedback')) {
        throw new RuntimeException('جداول Pilot یافت نشد.');
    }

    if ($scenarioId !== null && $scenarioId > 0 && pilot_get_scenario($c, $scenarioId) === null) {
        $scenarioId = null;
    }

    $ok = pilot_execute(
        $c,
        'INSERT INTO dbo.erp_soft_run_pilot_feedback
         (scenario_id, feedback_role, page_or_module, issue_type, severity, feedback_note, suggested_fix, feedback_status, created_by)
         VALUES (?,?,?,?,?,?,?,N\'OPEN\',?)',
        [$scenarioId, $role, $pageModule, $issueType, $severity, $note, $suggestedFix, pilot_safe_current_user()]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت بازخورد انجام نشد.');
    }

    $fbId = pilot_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT)');
    $entityId = ($fbId !== null && is_numeric($fbId)) ? (int)$fbId : null;
    pilot_insert_history($c, 'feedback', $entityId, 'CREATE', 'Pilot feedback: ' . $issueType . ' / ' . $severity, null, mb_substr($note, 0, 500));
} catch (Throwable) {
    pilot_error('خطا', 'ثبت بازخورد Pilot انجام نشد.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$redirect = 'erp-pilot-feedback.php?ok=feedback_ok';
if ($scenarioId !== null && $scenarioId > 0) {
    $redirect .= '&scenario_id=' . $scenarioId;
}
pilot_safe_redirect($redirect);
