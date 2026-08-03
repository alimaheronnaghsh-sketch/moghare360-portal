<?php
declare(strict_types=1);

/**
 * MOGHARE360 intake prepayment gate helper.
 *
 * Uses existing request payload/history and existing payment preview tables only.
 * No payment gateway, official accounting, SCM, procurement, warehouse, or logistics writes.
 */

const M360_INTAKE_PREPAYMENT_GATE_CONTRACT_NOT_SIGNED = 'CONTRACT_NOT_SIGNED';
const M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_REQUIRED = 'PREPAYMENT_REQUIRED';
const M360_INTAKE_PREPAYMENT_GATE_PENDING_CONFIRMATION = 'PREPAYMENT_PENDING_CONFIRMATION';
const M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED = 'OWNER_APPROVAL_REQUIRED';
const M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED = 'OWNER_REJECTED_START_WITHOUT_PREPAYMENT';
const M360_INTAKE_PREPAYMENT_GATE_WAITING_PAYMENT = 'WAITING_FOR_PREPAYMENT';
const M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_CONFIRMED = 'PREPAYMENT_CONFIRMED';
const M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED = 'OWNER_APPROVED_START_WITHOUT_PREPAYMENT';
const M360_INTAKE_PREPAYMENT_OWNER_PERMISSION = 'intake.prepayment_gate.approve';

/** @return array<string, mixed> */
function m360_intake_prepayment_default_payment_summary(): array
{
    return [
        'payment_backend_available' => false,
        'payment_backend_source' => '',
        'payment_registered' => false,
        'payment_confirmed' => false,
        'registered_amount' => 0.0,
        'confirmed_amount' => 0.0,
        'paid_amount' => 0.0,
        'latest_payment_status' => '',
        'required_amount' => 0.0,
        'finance_required' => true,
        'missing_reason' => 'payment_backend_not_available',
    ];
}

function m360_intake_prepayment_truthy_gate_value($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (float)$value > 0;
    }

    return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'ok', 'paid', 'approved', 'received', 'confirmed'], true);
}

function m360_intake_prepayment_amount($value): float
{
    if (is_int($value) || is_float($value)) {
        return max(0.0, (float)$value);
    }
    $raw = trim((string)$value);
    if ($raw === '') {
        return 0.0;
    }
    $normalized = str_replace([',', '،', ' '], '', $raw);
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $normalized = str_replace($persian, $latin, $normalized);
    $normalized = str_replace($arabic, $latin, $normalized);

    return is_numeric($normalized) ? max(0.0, (float)$normalized) : 0.0;
}

/** @return array<string, mixed> */
function m360_intake_prepayment_payload(array $payload): array
{
    $ri = is_array($payload['reception_intake'] ?? null) ? $payload['reception_intake'] : [];
    $operation = is_array($ri['operation_gate'] ?? null) ? $ri['operation_gate'] : [];
    $sources = [
        is_array($operation['prepayment_gate'] ?? null) ? $operation['prepayment_gate'] : null,
        is_array($ri['prepayment_gate'] ?? null) ? $ri['prepayment_gate'] : null,
        is_array($ri['financial_gate'] ?? null) ? $ri['financial_gate'] : null,
        is_array($payload['prepayment_gate'] ?? null) ? $payload['prepayment_gate'] : null,
        is_array($payload['financial_gate'] ?? null) ? $payload['financial_gate'] : null,
        is_array($payload['payment_gate'] ?? null) ? $payload['payment_gate'] : null,
        is_array($payload['payment'] ?? null) ? $payload['payment'] : null,
        is_array($payload['finance'] ?? null) ? $payload['finance'] : null,
    ];

    $out = [];
    foreach ($sources as $source) {
        if (is_array($source)) {
            $out = array_merge($out, $source);
        }
    }

    foreach ([
        'prepayment_required',
        'required_amount',
        'prepayment_amount',
        'advance_required_amount',
        'paid_amount',
        'prepayment_status',
        'payment_status',
        'prepayment_paid',
        'advance_paid',
        'owner_start_without_prepayment_approved',
        'manager_start_without_prepayment_approved',
        'owner_prepayment_decision',
        'owner_decision',
        'owner_decision_status',
        'manager_decision',
    ] as $key) {
        if (array_key_exists($key, $payload) && !array_key_exists($key, $out)) {
            $out[$key] = $payload[$key];
        }
    }

    return $out;
}

function m360_intake_prepayment_payload_required_amount(array $payload, array $gate): float
{
    foreach ([
        $gate['required_amount'] ?? null,
        $gate['prepayment_amount'] ?? null,
        $gate['advance_required_amount'] ?? null,
        $payload['required_prepayment_amount'] ?? null,
        $payload['prepayment_amount'] ?? null,
        $payload['advance_required_amount'] ?? null,
        $payload['estimated_advance_amount'] ?? null,
    ] as $candidate) {
        $amount = m360_intake_prepayment_amount($candidate);
        if ($amount > 0) {
            return $amount;
        }
    }

    return 0.0;
}

function m360_intake_prepayment_contract_signed_from_payload(array $payload): bool
{
    $contractStatus = strtolower(trim((string)($payload['contract_status'] ?? '')));
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $documents = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $task = is_array($payload['reception_intake']['customer_cartable']['contract_task'] ?? null)
        ? $payload['reception_intake']['customer_cartable']['contract_task']
        : [];

    foreach ([
        $contractStatus,
        strtolower(trim((string)($contract['status'] ?? ''))),
        strtolower(trim((string)($documents['contract_status'] ?? ''))),
        strtolower(trim((string)($task['status'] ?? ''))),
    ] as $status) {
        if (in_array($status, ['customer_accepted', 'signed', 'confirmed', 'contract_signed', 'customer_signed'], true)) {
            return true;
        }
    }

    return false;
}

/** @return array<string, mixed> */
function m360_intake_prepayment_fetch_estimate_requirement($conn, int $jobcardId): array
{
    $out = ['required_amount' => 0.0, 'finance_required' => true, 'estimate_id' => 0];
    if (!is_resource($conn) || $jobcardId < 1 || !function_exists('customer_core_table_exists') || !customer_core_table_exists($conn, 'erp_estimates')) {
        return $out;
    }
    foreach (['jobcard_id', 'estimate_id'] as $column) {
        if (function_exists('customer_core_column_exists') && !customer_core_column_exists($conn, 'erp_estimates', $column)) {
            return $out;
        }
    }

    $select = ['estimate_id', 'jobcard_id'];
    foreach (['advance_required_amount', 'total_amount', 'finance_required', 'estimate_status', 'created_at'] as $column) {
        if (!function_exists('customer_core_column_exists') || customer_core_column_exists($conn, 'erp_estimates', $column)) {
            $select[] = $column;
        }
    }
    $order = in_array('created_at', $select, true) ? 'created_at DESC, estimate_id DESC' : 'estimate_id DESC';
    $rows = function_exists('customer_core_fetch_rows')
        ? customer_core_fetch_rows($conn, 'SELECT TOP 1 ' . implode(', ', $select) . ' FROM dbo.erp_estimates WHERE jobcard_id = ? ORDER BY ' . $order, [$jobcardId])
        : [];
    if ($rows === []) {
        return $out;
    }

    $row = $rows[0];
    $advance = m360_intake_prepayment_amount($row['advance_required_amount'] ?? null);
    if ($advance <= 0) {
        $total = m360_intake_prepayment_amount($row['total_amount'] ?? null);
        $advance = $total > 0 ? $total * 0.5 : 0.0;
    }
    $financeRequiredRaw = $row['finance_required'] ?? true;

    return [
        'required_amount' => $advance,
        'finance_required' => m360_intake_prepayment_truthy_gate_value($financeRequiredRaw),
        'estimate_id' => (int)($row['estimate_id'] ?? 0),
    ];
}

/** @return array<string, mixed> */
function m360_intake_prepayment_fetch_payment_summary($conn, int $jobcardId, int $customerId = 0): array
{
    $summary = m360_intake_prepayment_default_payment_summary();
    if (!is_resource($conn) || !function_exists('customer_core_table_exists')) {
        return $summary;
    }

    $sourceNames = [];
    if ($jobcardId > 0 && customer_core_table_exists($conn, 'erp_payments')
        && (!function_exists('customer_core_column_exists')
            || (customer_core_column_exists($conn, 'erp_payments', 'jobcard_id')
                && customer_core_column_exists($conn, 'erp_payments', 'payment_amount')
                && customer_core_column_exists($conn, 'erp_payments', 'payment_status')))
    ) {
        $where = ['jobcard_id = ?'];
        $params = [$jobcardId];
        if (function_exists('customer_core_column_exists') && customer_core_column_exists($conn, 'erp_payments', 'is_active')) {
            $where[] = 'is_active = 1';
        }
        $sql = "SELECT
                    ISNULL(SUM(CASE WHEN payment_status IN (N'RECEIVED', N'PARTIAL', N'CONFIRMED', N'PAID_CONFIRMED', N'PREPAYMENT_CONFIRMED') THEN payment_amount ELSE 0 END), 0) AS registered_amount,
                    ISNULL(SUM(CASE WHEN payment_status IN (N'CONFIRMED', N'PAID_CONFIRMED', N'PREPAYMENT_CONFIRMED') THEN payment_amount ELSE 0 END), 0) AS confirmed_amount,
                    SUM(CASE WHEN payment_status IN (N'RECEIVED', N'PARTIAL', N'CONFIRMED', N'PAID_CONFIRMED', N'PREPAYMENT_CONFIRMED') THEN 1 ELSE 0 END) AS registered_count,
                    SUM(CASE WHEN payment_status IN (N'CONFIRMED', N'PAID_CONFIRMED', N'PREPAYMENT_CONFIRMED') THEN 1 ELSE 0 END) AS confirmed_count
                FROM dbo.erp_payments WHERE " . implode(' AND ', $where);
        $rows = function_exists('customer_core_fetch_rows') ? customer_core_fetch_rows($conn, $sql, $params) : [];
        if ($rows !== []) {
            $row = $rows[0];
            $summary['payment_backend_available'] = true;
            $summary['registered_amount'] += m360_intake_prepayment_amount($row['registered_amount'] ?? 0);
            $summary['confirmed_amount'] += m360_intake_prepayment_amount($row['confirmed_amount'] ?? 0);
            $summary['payment_registered'] = $summary['payment_registered'] || (int)($row['registered_count'] ?? 0) > 0;
            $summary['payment_confirmed'] = $summary['payment_confirmed'] || (int)($row['confirmed_count'] ?? 0) > 0;
            $sourceNames[] = 'erp_payments';
        }
    }

    if ($jobcardId > 0 && customer_core_table_exists($conn, 'erp_payment_records')
        && (!function_exists('customer_core_column_exists')
            || (customer_core_column_exists($conn, 'erp_payment_records', 'jobcard_id')
                && customer_core_column_exists($conn, 'erp_payment_records', 'payment_amount')
                && customer_core_column_exists($conn, 'erp_payment_records', 'payment_status')))
    ) {
        $rows = function_exists('customer_core_fetch_rows')
            ? customer_core_fetch_rows(
                $conn,
                "SELECT
                    ISNULL(SUM(CASE WHEN payment_status IN (N'RECORDED', N'RECEIVED', N'PARTIAL', N'CONFIRMED') THEN payment_amount ELSE 0 END), 0) AS registered_amount,
                    ISNULL(SUM(CASE WHEN payment_status IN (N'CONFIRMED') THEN payment_amount ELSE 0 END), 0) AS confirmed_amount,
                    SUM(CASE WHEN payment_status IN (N'RECORDED', N'RECEIVED', N'PARTIAL', N'CONFIRMED') THEN 1 ELSE 0 END) AS registered_count,
                    SUM(CASE WHEN payment_status IN (N'CONFIRMED') THEN 1 ELSE 0 END) AS confirmed_count
                 FROM dbo.erp_payment_records WHERE jobcard_id = ?",
                [$jobcardId]
            )
            : [];
        if ($rows !== []) {
            $row = $rows[0];
            $summary['payment_backend_available'] = true;
            $summary['registered_amount'] += m360_intake_prepayment_amount($row['registered_amount'] ?? 0);
            $summary['confirmed_amount'] += m360_intake_prepayment_amount($row['confirmed_amount'] ?? 0);
            $summary['payment_registered'] = $summary['payment_registered'] || (int)($row['registered_count'] ?? 0) > 0;
            $summary['payment_confirmed'] = $summary['payment_confirmed'] || (int)($row['confirmed_count'] ?? 0) > 0;
            $sourceNames[] = 'erp_payment_records';
        }
    }

    $summary['paid_amount'] = max((float)$summary['registered_amount'], (float)$summary['confirmed_amount']);
    if ($sourceNames !== []) {
        $summary['payment_backend_source'] = implode('+', array_values(array_unique($sourceNames)));
        $summary['missing_reason'] = '';
    }
    if ($customerId > 0) {
        $summary['customer_id'] = $customerId;
    }

    return $summary;
}

/** @return array<string, mixed> */
function m360_intake_prepayment_fetch_backend_summary($conn, int $jobcardId, int $customerId = 0): array
{
    $payment = m360_intake_prepayment_fetch_payment_summary($conn, $jobcardId, $customerId);
    $estimate = m360_intake_prepayment_fetch_estimate_requirement($conn, $jobcardId);

    if ((float)($estimate['required_amount'] ?? 0) > 0) {
        $payment['required_amount'] = (float)$estimate['required_amount'];
    }
    $payment['finance_required'] = (bool)($estimate['finance_required'] ?? true);
    if ((int)($estimate['estimate_id'] ?? 0) > 0) {
        $payment['estimate_id'] = (int)$estimate['estimate_id'];
    }

    return $payment;
}

function m360_intake_prepayment_normalize_owner_decision(array $gate): string
{
    $raw = strtoupper(trim((string)(
        $gate['owner_decision_status']
        ?? $gate['owner_decision']
        ?? $gate['owner_prepayment_decision']
        ?? $gate['manager_decision']
        ?? ''
    )));
    return match ($raw) {
        'APPROVED', 'APPROVED_WITHOUT_PREPAYMENT', 'OWNER_APPROVED_WITHOUT_PREPAYMENT', 'MANAGER_APPROVED_WITHOUT_PREPAYMENT', M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED => M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED,
        'REJECTED', 'REJECTED_WITHOUT_PREPAYMENT', 'OWNER_REJECTED_WITHOUT_PREPAYMENT', 'MANAGER_REJECTED_WITHOUT_PREPAYMENT', M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED => M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED,
        'REQUESTED', 'PENDING_OWNER_APPROVAL', 'OWNER_APPROVAL_REQUESTED', M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED => M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED,
        'RETURNED', 'RETURNED_FOR_CORRECTION' => 'RETURNED_FOR_CORRECTION',
        default => $raw,
    };
}

/** @return array<string, mixed> */
function m360_intake_prepayment_gate_evaluate(array $payload, bool $contractSigned, array $backendSummary = []): array
{
    $backendSummary = array_merge(m360_intake_prepayment_default_payment_summary(), $backendSummary);
    $gate = m360_intake_prepayment_payload($payload);
    $requiredAmount = max(
        m360_intake_prepayment_payload_required_amount($payload, $gate),
        m360_intake_prepayment_amount($backendSummary['required_amount'] ?? 0)
    );
    $paidAmount = max(
        m360_intake_prepayment_amount($gate['paid_amount'] ?? 0),
        m360_intake_prepayment_amount($gate['payment_amount'] ?? 0),
        m360_intake_prepayment_amount($backendSummary['paid_amount'] ?? 0),
        m360_intake_prepayment_amount($backendSummary['registered_amount'] ?? 0)
    );
    $confirmedAmount = max(
        m360_intake_prepayment_amount($gate['confirmed_amount'] ?? 0),
        m360_intake_prepayment_amount($backendSummary['confirmed_amount'] ?? 0)
    );
    $status = strtoupper(trim((string)($gate['status'] ?? $gate['prepayment_status'] ?? $gate['payment_status'] ?? '')));
    $ownerDecision = m360_intake_prepayment_normalize_owner_decision($gate);
    // Default: require prepayment only when a positive amount is known.
    // Sticky payload flags with amount=0 must not permanently block Stage 8 / JobCard handoff.
    $prepaymentRequired = array_key_exists('prepayment_required', $gate)
        ? m360_intake_prepayment_truthy_gate_value($gate['prepayment_required'])
        : ($requiredAmount > 0);
    if ($requiredAmount <= 0) {
        $prepaymentRequired = false;
    }

    $paymentRegistered = m360_intake_prepayment_truthy_gate_value($gate['payment_registered'] ?? false)
        || m360_intake_prepayment_truthy_gate_value($gate['prepayment_registered'] ?? false)
        || m360_intake_prepayment_truthy_gate_value($backendSummary['payment_registered'] ?? false)
        || in_array($status, ['REGISTERED', 'RECORDED', 'RECEIVED', 'PARTIAL', 'PENDING_CONFIRMATION', 'PREPAYMENT_PENDING_CONFIRMATION'], true);
    $explicitConfirmed = m360_intake_prepayment_truthy_gate_value($gate['payment_confirmed'] ?? false)
        || m360_intake_prepayment_truthy_gate_value($gate['prepayment_confirmed'] ?? false)
        || m360_intake_prepayment_truthy_gate_value($gate['prepayment_paid'] ?? false)
        || m360_intake_prepayment_truthy_gate_value($gate['advance_paid'] ?? false)
        || in_array($status, ['PAID', 'CONFIRMED', 'PAID_CONFIRMED', 'PAYMENT_CONFIRMED', 'PREPAYMENT_PAID', 'PREPAYMENT_CONFIRMED'], true);
    $paymentConfirmed = $explicitConfirmed
        || m360_intake_prepayment_truthy_gate_value($backendSummary['payment_confirmed'] ?? false)
        || ($confirmedAmount > 0 && ($requiredAmount <= 0 || $confirmedAmount >= $requiredAmount));

    $code = M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_REQUIRED;
    $label = 'پیش‌پرداخت لازم است';
    $message = 'پیش‌پرداخت تأیید نشده است یا مجوز مالک/مدیر مجاز ثبت نشده است.';
    $allow = false;
    $nextActor = 'مالی یا مالک/مدیر مجاز';
    $ownerDecisionRequired = true;

    if (!$contractSigned) {
        $code = M360_INTAKE_PREPAYMENT_GATE_CONTRACT_NOT_SIGNED;
        $label = 'قرارداد مشتری هنوز امضا و تأیید نشده است';
        $message = 'ابتدا مشتری باید قرارداد را در مسیر مشتری با امضا و OTP تأیید کند.';
        $nextActor = 'مشتری';
        $ownerDecisionRequired = false;
    } elseif (!$prepaymentRequired) {
        $code = M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_CONFIRMED;
        $label = 'پیش‌پرداخت برای این پرونده لازم نیست';
        $message = '';
        $allow = true;
        $nextActor = 'مسئول سالن';
        $ownerDecisionRequired = false;
    } elseif ($paymentConfirmed) {
        $code = M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_CONFIRMED;
        $label = 'پیش‌پرداخت تأیید شده است';
        $message = '';
        $allow = true;
        $nextActor = 'مسئول سالن';
        $ownerDecisionRequired = false;
    } elseif ($ownerDecision === M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED) {
        $code = M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED;
        $label = 'شروع بدون پیش‌پرداخت توسط مالک/مدیر مجاز تأیید شد';
        $message = '';
        $allow = true;
        $nextActor = 'مسئول سالن';
        $ownerDecisionRequired = false;
    } elseif ($ownerDecision === M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED) {
        $code = M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED;
        $label = 'شروع بدون پیش‌پرداخت رد شد';
        $message = 'مالک/مدیر مجاز شروع بدون پیش‌پرداخت را رد کرده است.';
        $nextActor = 'مشتری/مالی';
        $ownerDecisionRequired = false;
    } elseif ($paymentRegistered) {
        $code = M360_INTAKE_PREPAYMENT_GATE_PENDING_CONFIRMATION;
        $label = 'پرداخت ثبت شده و در انتظار تأیید است';
        $message = 'پرداخت ثبت شده است اما هنوز برای گیت Step 8 تأیید نهایی نشده است.';
        $nextActor = 'مالی یا مالک/مدیر مجاز';
    } elseif ($ownerDecision === M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED || $ownerDecision === 'RETURNED_FOR_CORRECTION') {
        $code = M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED;
        $label = $ownerDecision === 'RETURNED_FOR_CORRECTION'
            ? 'درخواست برای اصلاح برگشت داده شده است'
            : 'در انتظار تصمیم مالک/مدیر مجاز';
        $message = 'شروع بدون پیش‌پرداخت در انتظار تصمیم مالک/مدیر مجاز است.';
        $nextActor = 'مالک/مدیر مجاز';
    } elseif (in_array($status, ['WAITING_PAYMENT', 'PENDING_PAYMENT', 'WAIT_FOR_PAYMENT', 'PENDING'], true)) {
        $code = M360_INTAKE_PREPAYMENT_GATE_WAITING_PAYMENT;
        $label = 'در انتظار پرداخت پیش‌پرداخت';
        $message = 'پرداخت پیش‌پرداخت هنوز ثبت نشده است.';
        $nextActor = 'مشتری/مالی';
        $ownerDecisionRequired = false;
    }

    return [
        'code' => $code,
        'label' => $label,
        'message' => $message,
        'allow_handoff' => $allow,
        'backend_ready' => true,
        'payment_backend_available' => !empty($backendSummary['payment_backend_available']),
        'payment_backend_source' => (string)($backendSummary['payment_backend_source'] ?? ''),
        'payment_backend_gap' => empty($backendSummary['payment_backend_available']),
        'contract_signed' => $contractSigned,
        'prepayment_required' => $prepaymentRequired,
        'required_amount' => $requiredAmount,
        'paid_amount' => $paidAmount,
        'confirmed_amount' => $confirmedAmount,
        'payment_registered' => $paymentRegistered,
        'payment_confirmed' => $paymentConfirmed,
        'owner_decision_status' => $ownerDecision !== '' ? $ownerDecision : 'NONE',
        'gate_status' => $code,
        'next_actor' => $nextActor,
        'owner_decision_required' => $ownerDecisionRequired,
        'events' => is_array($gate['events'] ?? null) ? $gate['events'] : [],
    ];
}

/** @return array<string, mixed> */
function m360_intake_prepayment_actor_context($conn, ?int $userId = null): array
{
    $userId = $userId !== null ? $userId : (function_exists('erp_auth_current_user_id') ? (erp_auth_current_user_id() ?? 0) : 0);
    $roles = [];
    $permissions = [];
    $isSystemOwner = false;
    if (is_resource($conn) && $userId > 0) {
        if (function_exists('erp_auth_is_system_owner')) {
            $isSystemOwner = erp_auth_is_system_owner($conn, $userId);
        }
        if (function_exists('erp_auth_current_roles')) {
            $roleResult = erp_auth_current_roles($conn, $userId);
            $roles = is_array($roleResult['role_keys'] ?? null) ? $roleResult['role_keys'] : [];
        }
        if (function_exists('erp_auth_current_permissions')) {
            $permissionResult = erp_auth_current_permissions($conn, $userId);
            $permissions = is_array($permissionResult['permission_keys'] ?? null) ? $permissionResult['permission_keys'] : [];
        }
    }
    $roles = array_values(array_unique(array_map(static fn ($role): string => strtolower(trim((string)$role)), $roles)));
    $permissions = array_values(array_unique(array_map(static fn ($permission): string => trim((string)$permission), $permissions)));
    $ownerRole = in_array('owner', $roles, true) || in_array('system_admin', $roles, true);
    $delegate = in_array(M360_INTAKE_PREPAYMENT_OWNER_PERMISSION, $permissions, true)
        || in_array('finance.preview.approve', $permissions, true);

    return [
        'user_id' => $userId,
        'role_keys' => $roles,
        'permission_keys' => $permissions,
        'is_system_owner' => $isSystemOwner,
        'can_request' => $userId > 0,
        'can_approve' => $isSystemOwner || $ownerRole || $delegate,
    ];
}

/** @return array<string, mixed> */
function m360_intake_prepayment_append_event(array $gate, string $eventType, int $actorUserId, string $beforeStatus, string $afterStatus, string $reason, array $payload = []): array
{
    $events = is_array($gate['events'] ?? null) ? $gate['events'] : [];
    $events[] = [
        'event_type' => $eventType,
        'actor_user_id' => (string)$actorUserId,
        'before_status' => $beforeStatus,
        'after_status' => $afterStatus,
        'reason' => $reason,
        'payload' => $payload,
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    $gate['events'] = array_slice($events, -50);

    return $gate;
}
