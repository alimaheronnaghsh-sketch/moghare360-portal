<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — JobCard reception workflow statuses and transitions.
 */

const M360_JC_STATUS_RECEIVED = 'RECEIVED';
const M360_JC_STATUS_ARRIVED = 'ARRIVED';
const M360_JC_STATUS_CHECKED_IN = 'CHECKED_IN';
const M360_JC_STATUS_INITIAL_REVIEW = 'INITIAL_REVIEW';
const M360_JC_STATUS_READY_FOR_TECHNICAL = 'READY_FOR_TECHNICAL';
const M360_JC_STATUS_IN_PROGRESS = 'IN_PROGRESS';
const M360_JC_STATUS_ON_HOLD = 'ON_HOLD';
const M360_JC_STATUS_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_JC_STATUS_LABELS_FA = [
    M360_JC_STATUS_RECEIVED => 'پذیرش شده',
    M360_JC_STATUS_ARRIVED => 'خودرو رسید',
    M360_JC_STATUS_CHECKED_IN => 'ثبت ورود',
    M360_JC_STATUS_INITIAL_REVIEW => 'بررسی اولیه',
    M360_JC_STATUS_READY_FOR_TECHNICAL => 'آماده فنی',
    M360_JC_STATUS_IN_PROGRESS => 'در حال انجام',
    M360_JC_STATUS_ON_HOLD => 'معلق',
    M360_JC_STATUS_CANCELLED => 'لغو شده',
];

/** @return list<string> */
function m360_jobcard_workflow_statuses(): array
{
    return [
        M360_JC_STATUS_RECEIVED,
        M360_JC_STATUS_ARRIVED,
        M360_JC_STATUS_CHECKED_IN,
        M360_JC_STATUS_INITIAL_REVIEW,
        M360_JC_STATUS_READY_FOR_TECHNICAL,
        M360_JC_STATUS_IN_PROGRESS,
        M360_JC_STATUS_ON_HOLD,
        M360_JC_STATUS_CANCELLED,
    ];
}

function m360_jobcard_workflow_status_label(string $status): string
{
    $status = strtoupper(trim($status));
    return M360_JC_STATUS_LABELS_FA[$status] ?? $status;
}

function m360_jobcard_workflow_normalize_status(string $status): string
{
    $status = strtoupper(trim($status));
    if ($status === '') {
        return M360_JC_STATUS_RECEIVED;
    }
    return $status;
}

function m360_jobcard_workflow_is_terminal(string $status): bool
{
    return in_array(m360_jobcard_workflow_normalize_status($status), [
        M360_JC_STATUS_CANCELLED,
    ], true);
}

/**
 * @return array{ok:bool,message:string,new_status:?string}
 */
function m360_jobcard_workflow_validate_action(string $currentStatus, string $action): array
{
    $current = m360_jobcard_workflow_normalize_status($currentStatus);
    $action = strtolower(trim($action));

    if (m360_jobcard_workflow_is_terminal($current) && !in_array($action, ['save_customer_complaint', 'save_reception_notes', 'save_initial_inspection'], true)) {
        return ['ok' => false, 'message' => 'این پرونده در وضعیت پایانی است.', 'new_status' => null];
    }

    switch ($action) {
        case 'mark_arrived':
            if (in_array($current, [M360_JC_STATUS_RECEIVED], true)) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_JC_STATUS_ARRIVED];
            }
            if (in_array($current, [M360_JC_STATUS_ARRIVED, M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_READY_FOR_TECHNICAL, M360_JC_STATUS_IN_PROGRESS, M360_JC_STATUS_ON_HOLD], true)) {
                return ['ok' => true, 'message' => 'خودرو قبلاً رسیده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'انتقال وضعیت نامعتبر است.', 'new_status' => null];

        case 'check_in':
            if ($current === M360_JC_STATUS_ARRIVED) {
                return ['ok' => true, 'message' => '', 'new_status' => M360_JC_STATUS_CHECKED_IN];
            }
            if (in_array($current, [M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_READY_FOR_TECHNICAL, M360_JC_STATUS_IN_PROGRESS], true)) {
                return ['ok' => true, 'message' => 'ورود قبلاً ثبت شده است.', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'ابتدا خودرو باید رسیده باشد.', 'new_status' => null];

        case 'save_customer_complaint':
        case 'save_reception_notes':
            return ['ok' => true, 'message' => '', 'new_status' => null];

        case 'save_initial_inspection':
            if (in_array($current, [M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_READY_FOR_TECHNICAL, M360_JC_STATUS_IN_PROGRESS, M360_JC_STATUS_ON_HOLD], true)) {
                $new = $current === M360_JC_STATUS_CHECKED_IN ? M360_JC_STATUS_INITIAL_REVIEW : null;
                return ['ok' => true, 'message' => '', 'new_status' => $new];
            }
            return ['ok' => false, 'message' => 'ابتدا ثبت ورود خودرو انجام شود.', 'new_status' => null];

        case 'ready_for_technical':
            if (!in_array($current, [M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_ON_HOLD], true)) {
                return ['ok' => false, 'message' => 'برای آماده‌سازی فنی، بررسی اولیه لازم است.', 'new_status' => null];
            }
            if ($current === M360_JC_STATUS_READY_FOR_TECHNICAL) {
                return ['ok' => true, 'message' => 'پرونده قبلاً آماده فنی شده است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_JC_STATUS_READY_FOR_TECHNICAL];

        case 'hold':
            if (in_array($current, [M360_JC_STATUS_CANCELLED, M360_JC_STATUS_READY_FOR_TECHNICAL], true)) {
                return ['ok' => false, 'message' => 'انتقال به حالت تعلیق مجاز نیست.', 'new_status' => null];
            }
            if ($current === M360_JC_STATUS_ON_HOLD) {
                return ['ok' => true, 'message' => 'پرونده در حالت تعلیق است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_JC_STATUS_ON_HOLD];

        case 'cancel':
            if ($current === M360_JC_STATUS_CANCELLED) {
                return ['ok' => true, 'message' => 'پرونده قبلاً لغو شده است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_JC_STATUS_CANCELLED];

        case 'manager_override_contract_gate':
            return ['ok' => true, 'message' => '', 'new_status' => null];

        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر است.', 'new_status' => null];
    }
}

/** @return array<string, string> */
function m360_jobcard_workflow_history_event_map(): array
{
    return [
        'mark_arrived' => 'JOBCARD_ARRIVED',
        'check_in' => 'JOBCARD_CHECKED_IN',
        'save_customer_complaint' => 'JOBCARD_CUSTOMER_COMPLAINT_SAVED',
        'save_reception_notes' => 'JOBCARD_RECEPTION_NOTES_SAVED',
        'save_initial_inspection' => 'JOBCARD_INITIAL_INSPECTION_SAVED',
        'ready_for_technical' => 'JOBCARD_READY_FOR_TECHNICAL',
        'hold' => 'JOBCARD_ON_HOLD',
        'cancel' => 'JOBCARD_CANCELLED',
        'manager_override_contract_gate' => 'JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE',
    ];
}

function m360_jobcard_workflow_history_event(string $action): string
{
    $map = m360_jobcard_workflow_history_event_map();
    return $map[strtolower(trim($action))] ?? 'JOBCARD_ACTION_' . strtoupper($action);
}
