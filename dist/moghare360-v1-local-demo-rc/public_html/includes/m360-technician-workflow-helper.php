<?php
declare(strict_types=1);

/**
 * MOGHARE360 P3 — Technician workflow statuses and transitions.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-jobcard-workflow-helper.php';

const M360_TECH_STATUS_READY_FOR_TECHNICAL = 'READY_FOR_TECHNICAL';
const M360_TECH_STATUS_TECHNICAL_QUEUE = 'TECHNICAL_QUEUE';
const M360_TECH_STATUS_ASSIGNED = 'ASSIGNED_TO_TECHNICIAN';
const M360_TECH_STATUS_DIAGNOSIS_STARTED = 'DIAGNOSIS_STARTED';
const M360_TECH_STATUS_DIAGNOSIS_COMPLETED = 'DIAGNOSIS_COMPLETED';
const M360_TECH_STATUS_SERVICE_STARTED = 'SERVICE_OPERATION_STARTED';
const M360_TECH_STATUS_SERVICE_COMPLETED = 'SERVICE_OPERATION_COMPLETED';
const M360_TECH_STATUS_TECHNICAL_REVIEW = 'TECHNICAL_REVIEW';
const M360_TECH_STATUS_WAITING_APPROVAL = 'WAITING_FOR_APPROVAL';
const M360_TECH_STATUS_TECHNICAL_DONE = 'TECHNICAL_DONE';
const M360_TECH_STATUS_ON_HOLD = 'ON_HOLD';
const M360_TECH_STATUS_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_TECH_STATUS_LABELS_FA = [
    M360_TECH_STATUS_READY_FOR_TECHNICAL => 'آماده فنی',
    M360_TECH_STATUS_TECHNICAL_QUEUE => 'صف فنی',
    M360_TECH_STATUS_ASSIGNED => 'اختصاص به تکنسین',
    M360_TECH_STATUS_DIAGNOSIS_STARTED => 'عیب‌یابی شروع شد',
    M360_TECH_STATUS_DIAGNOSIS_COMPLETED => 'عیب‌یابی تکمیل',
    M360_TECH_STATUS_SERVICE_STARTED => 'عملیات سرویس شروع',
    M360_TECH_STATUS_SERVICE_COMPLETED => 'عملیات سرویس تکمیل',
    M360_TECH_STATUS_TECHNICAL_REVIEW => 'بازبینی فنی',
    M360_TECH_STATUS_WAITING_APPROVAL => 'منتظر تأیید',
    M360_TECH_STATUS_TECHNICAL_DONE => 'پایان فنی',
    M360_TECH_STATUS_ON_HOLD => 'معلق',
    M360_TECH_STATUS_CANCELLED => 'لغو شده',
];

/** @return list<string> */
function m360_technician_workflow_board_statuses(): array
{
    return [
        M360_TECH_STATUS_READY_FOR_TECHNICAL,
        M360_TECH_STATUS_TECHNICAL_QUEUE,
        M360_TECH_STATUS_ASSIGNED,
        M360_TECH_STATUS_DIAGNOSIS_STARTED,
        M360_TECH_STATUS_DIAGNOSIS_COMPLETED,
        M360_TECH_STATUS_SERVICE_STARTED,
        M360_TECH_STATUS_SERVICE_COMPLETED,
        M360_TECH_STATUS_TECHNICAL_REVIEW,
        M360_TECH_STATUS_WAITING_APPROVAL,
        M360_TECH_STATUS_TECHNICAL_DONE,
        M360_TECH_STATUS_ON_HOLD,
    ];
}

function m360_technician_workflow_status_label(string $status): string
{
    $status = strtoupper(trim($status));
    return M360_TECH_STATUS_LABELS_FA[$status] ?? $status;
}

/**
 * @param array<string, mixed> $row
 */
function m360_technician_workflow_effective_status(array $row): string
{
    $tech = strtoupper(trim((string)($row['technical_status'] ?? '')));
    if ($tech !== '') {
        return $tech;
    }
    $jc = m360_jobcard_workflow_normalize_status((string)($row['jobcard_status'] ?? ''));
    if ($jc === M360_JC_STATUS_READY_FOR_TECHNICAL) {
        return M360_TECH_STATUS_READY_FOR_TECHNICAL;
    }
    return '';
}

/**
 * @param array<string, mixed> $row
 */
function m360_technician_workflow_is_on_board(array $row): bool
{
    $jc = m360_jobcard_workflow_normalize_status((string)($row['jobcard_status'] ?? ''));
    $tech = trim((string)($row['technical_status'] ?? ''));
    if ($tech !== '') {
        return true;
    }
    return $jc === M360_JC_STATUS_READY_FOR_TECHNICAL;
}

/**
 * @param array<string, mixed> $row
 */
function m360_technician_workflow_is_p2_ready(array $row): bool
{
    $jc = m360_jobcard_workflow_normalize_status((string)($row['jobcard_status'] ?? ''));
    $tech = trim((string)($row['technical_status'] ?? ''));
    if ($tech !== '') {
        return true;
    }
    return $jc === M360_JC_STATUS_READY_FOR_TECHNICAL;
}

function m360_technician_workflow_is_terminal(string $status): bool
{
    return in_array(strtoupper(trim($status)), [
        M360_TECH_STATUS_TECHNICAL_DONE,
        M360_TECH_STATUS_CANCELLED,
    ], true);
}

/**
 * @return array{ok:bool,message:string,new_status:?string}
 */
function m360_technician_workflow_validate_action(string $effectiveStatus, string $action): array
{
    $current = strtoupper(trim($effectiveStatus));
    $action = strtolower(trim($action));

    if (m360_technician_workflow_is_terminal($current) && !in_array($action, ['save_technician_notes'], true)) {
        return ['ok' => false, 'message' => 'این پرونده در وضعیت پایانی فنی است.', 'new_status' => null];
    }

    switch ($action) {
        case 'move_to_technical_queue':
            if ($current === M360_TECH_STATUS_READY_FOR_TECHNICAL) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_TECHNICAL_QUEUE];
            }
            if ($current === M360_TECH_STATUS_TECHNICAL_QUEUE) {
                return ['ok' => true, 'message' => 'پرونده در صف فنی است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'انتقال به صف فنی مجاز نیست.', 'new_status' => null];

        case 'assign_technician':
            if (in_array($current, [M360_TECH_STATUS_TECHNICAL_QUEUE, M360_TECH_STATUS_ASSIGNED], true)) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_ASSIGNED];
            }
            if (in_array($current, [M360_TECH_STATUS_DIAGNOSIS_STARTED, M360_TECH_STATUS_DIAGNOSIS_COMPLETED, M360_TECH_STATUS_SERVICE_STARTED, M360_TECH_STATUS_SERVICE_COMPLETED, M360_TECH_STATUS_TECHNICAL_REVIEW, M360_TECH_STATUS_WAITING_APPROVAL], true)) {
                return ['ok' => true, 'message' => 'تکنسین قبلاً اختصاص داده شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'ابتدا پرونده باید در صف فنی باشد.', 'new_status' => null];

        case 'start_diagnosis':
            if (in_array($current, [M360_TECH_STATUS_ASSIGNED, M360_TECH_STATUS_TECHNICAL_QUEUE], true)) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_DIAGNOSIS_STARTED];
            }
            if ($current === M360_TECH_STATUS_DIAGNOSIS_STARTED) {
                return ['ok' => true, 'message' => 'عیب‌یابی قبلاً شروع شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'شروع عیب‌یابی در این وضعیت مجاز نیست.', 'new_status' => null];

        case 'save_technician_notes':
            if ($current === '' || $current === M360_TECH_STATUS_READY_FOR_TECHNICAL) {
                return ['ok' => false, 'message' => 'ابتدا پرونده باید وارد جریان فنی شود.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => null];

        case 'complete_diagnosis':
            if ($current === M360_TECH_STATUS_DIAGNOSIS_STARTED) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_DIAGNOSIS_COMPLETED];
            }
            if ($current === M360_TECH_STATUS_DIAGNOSIS_COMPLETED) {
                return ['ok' => true, 'message' => 'عیب‌یابی قبلاً تکمیل شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'تکمیل عیب‌یابی فقط پس از شروع مجاز است.', 'new_status' => null];

        case 'create_service_operation':
            if (in_array($current, [M360_TECH_STATUS_DIAGNOSIS_COMPLETED, M360_TECH_STATUS_SERVICE_STARTED, M360_TECH_STATUS_SERVICE_COMPLETED, M360_TECH_STATUS_TECHNICAL_REVIEW], true)) {
                return ['ok' => true, 'message' => '', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'ثبت عملیات سرویس پس از تکمیل عیب‌یابی مجاز است.', 'new_status' => null];

        case 'start_service_operation':
            if (in_array($current, [M360_TECH_STATUS_DIAGNOSIS_COMPLETED, M360_TECH_STATUS_SERVICE_STARTED], true)) {
                $new = $current === M360_TECH_STATUS_DIAGNOSIS_COMPLETED ? M360_TECH_STATUS_SERVICE_STARTED : null;
                return ['ok' => true, 'message' => '', 'new_status' => $new];
            }
            return ['ok' => false, 'message' => 'شروع عملیات سرویس مجاز نیست.', 'new_status' => null];

        case 'complete_service_operation':
            if ($current === M360_TECH_STATUS_SERVICE_STARTED) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_SERVICE_COMPLETED];
            }
            if ($current === M360_TECH_STATUS_SERVICE_COMPLETED) {
                return ['ok' => true, 'message' => 'عملیات سرویس قبلاً تکمیل شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'تکمیل عملیات سرویس مجاز نیست.', 'new_status' => null];

        case 'technical_review':
            if ($current === M360_TECH_STATUS_SERVICE_COMPLETED) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_TECHNICAL_REVIEW];
            }
            if ($current === M360_TECH_STATUS_TECHNICAL_REVIEW) {
                return ['ok' => true, 'message' => 'بازبینی فنی قبلاً ثبت شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'بازبینی فنی پس از تکمیل سرویس مجاز است.', 'new_status' => null];

        case 'waiting_for_approval':
            if ($current === M360_TECH_STATUS_TECHNICAL_REVIEW) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_WAITING_APPROVAL];
            }
            if ($current === M360_TECH_STATUS_WAITING_APPROVAL) {
                return ['ok' => true, 'message' => 'پرونده در انتظار تأیید است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'انتقال به انتظار تأیید مجاز نیست.', 'new_status' => null];

        case 'technical_done':
            if ($current === M360_TECH_STATUS_WAITING_APPROVAL) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_TECHNICAL_DONE];
            }
            if ($current === M360_TECH_STATUS_TECHNICAL_DONE) {
                return ['ok' => true, 'message' => 'پرونده قبلاً فنی تکمیل شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'پایان فنی پس از تأیید مجاز است.', 'new_status' => null];

        case 'hold':
            if (in_array($current, [M360_TECH_STATUS_TECHNICAL_DONE, M360_TECH_STATUS_CANCELLED], true)) {
                return ['ok' => false, 'message' => 'تعلیق در این وضعیت مجاز نیست.', 'new_status' => null];
            }
            if ($current === M360_TECH_STATUS_ON_HOLD) {
                return ['ok' => true, 'message' => 'پرونده در حالت تعلیق است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_TECH_STATUS_ON_HOLD];

        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر است.', 'new_status' => null];
    }
}

/** @return array<string, string> */
function m360_technician_workflow_history_event_map(): array
{
    return [
        'move_to_technical_queue' => 'JOBCARD_MOVED_TO_TECHNICAL_QUEUE',
        'assign_technician' => 'JOBCARD_TECHNICIAN_ASSIGNED',
        'start_diagnosis' => 'JOBCARD_DIAGNOSIS_STARTED',
        'save_technician_notes' => 'JOBCARD_TECHNICIAN_NOTES_SAVED',
        'complete_diagnosis' => 'JOBCARD_DIAGNOSIS_COMPLETED',
        'create_service_operation' => 'JOBCARD_SERVICE_OPERATION_CREATED',
        'start_service_operation' => 'JOBCARD_SERVICE_OPERATION_STARTED',
        'complete_service_operation' => 'JOBCARD_SERVICE_OPERATION_COMPLETED',
        'technical_review' => 'JOBCARD_TECHNICAL_REVIEW',
        'waiting_for_approval' => 'JOBCARD_WAITING_FOR_APPROVAL',
        'technical_done' => 'JOBCARD_TECHNICAL_DONE',
        'hold' => 'JOBCARD_TECHNICAL_ON_HOLD',
    ];
}

function m360_technician_workflow_history_event(string $action): string
{
    $map = m360_technician_workflow_history_event_map();
    return $map[strtolower(trim($action))] ?? 'JOBCARD_TECHNICAL_ACTION_' . strtoupper($action);
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function m360_technician_workflow_allowed_actions(array $row, bool $gatesOk): array
{
    if (!$gatesOk) {
        return [];
    }
    $effective = m360_technician_workflow_effective_status($row);
    $actions = [];

    $candidates = [
        'move_to_technical_queue',
        'assign_technician',
        'start_diagnosis',
        'save_technician_notes',
        'complete_diagnosis',
        'create_service_operation',
        'start_service_operation',
        'complete_service_operation',
        'technical_review',
        'waiting_for_approval',
        'technical_done',
        'hold',
    ];

    foreach ($candidates as $action) {
        $v = m360_technician_workflow_validate_action($effective, $action);
        if ($v['ok']) {
            $actions[] = $action;
        }
    }

    return $actions;
}
