<?php
declare(strict_types=1);

/**
 * MOGHARE360 P8 — Financial control summary (read-only; no payment entry).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

/**
 * @return array<string, mixed>
 */
function m360_financial_control_summary($conn): array
{
    if (!is_resource($conn)) {
        return m360_financial_control_empty();
    }

    if (m360_mgmt_view_exists($conn, M360_MGMT_VIEW_FINANCIAL)) {
        $rows = customer_core_fetch_rows($conn, 'SELECT * FROM dbo.' . M360_MGMT_VIEW_FINANCIAL);
    } else {
        $rows = m360_mgmt_fetch_pipeline($conn, 500);
    }

    $invoiceTotal = 0.0;
    $paidTotal = 0.0;
    $remainingTotal = 0.0;
    $settlementPending = 0;
    $partialSettled = 0;
    $settled = 0;
    $managerRelease = 0;
    $releasedWithBalance = 0;
    $deliveryReadyUnpaid = 0;
    $varianceCases = 0;
    $balanceCaseCount = 0;

    foreach ($rows as $row) {
        $due = (float)($row['total_due_amount'] ?? $row['final_invoice_amount'] ?? $row['invoice_total'] ?? 0);
        $paid = (float)($row['total_paid_amount'] ?? $row['settlement_amount_paid'] ?? 0);
        $rem = (float)($row['remaining_amount'] ?? $row['settlement_remaining_amount'] ?? max(0.0, $due - $paid));
        $invoiceTotal += $due;
        $paidTotal += $paid;
        $remainingTotal += $rem;

        $settle = strtoupper(trim((string)($row['settlement_status'] ?? '')));
        if (in_array($settle, ['PAYMENT_PENDING', ''], true) && $due > 0) {
            $settlementPending++;
        } elseif ($settle === 'PARTIAL_SETTLED') {
            $partialSettled++;
        } elseif ($settle === 'SETTLED') {
            $settled++;
        } elseif ($settle === 'MANAGER_RELEASE_APPROVED' || !empty($row['manager_release_approved'])) {
            $managerRelease++;
        }

        if (!empty($row['is_unpaid_delivery_ready']) || in_array('DELIVERY_READY_UNPAID', $row['risk_flags'] ?? [], true)) {
            $deliveryReadyUnpaid++;
        }
        if (!empty($row['is_released_with_balance']) || in_array('RELEASED_NOT_CLOSED', $row['risk_flags'] ?? [], true)) {
            if ($rem > 0) {
                $releasedWithBalance++;
            }
        }
        if ((float)($row['variance_amount'] ?? 0) !== 0.0) {
            $varianceCases++;
        }
        if ($rem > 0.01) {
            $balanceCaseCount++;
        }
    }

    return [
        'final_invoice_total' => round($invoiceTotal, 2),
        'paid_total' => round($paidTotal, 2),
        'remaining_total' => round($remainingTotal, 2),
        'settlement_pending_count' => $settlementPending,
        'partial_settled_count' => $partialSettled,
        'settled_count' => $settled,
        'manager_release_count' => $managerRelease,
        'released_with_balance_count' => $releasedWithBalance,
        'delivery_ready_unpaid_count' => $deliveryReadyUnpaid,
        'variance_cases_count' => $varianceCases,
        'balance_case_count' => $balanceCaseCount,
        'read_only' => true,
        'no_payment_gateway' => true,
        'no_accounting_voucher' => true,
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_financial_control_empty(): array
{
    return [
        'final_invoice_total' => 0.0,
        'paid_total' => 0.0,
        'remaining_total' => 0.0,
        'settlement_pending_count' => 0,
        'partial_settled_count' => 0,
        'settled_count' => 0,
        'manager_release_count' => 0,
        'released_with_balance_count' => 0,
        'delivery_ready_unpaid_count' => 0,
        'variance_cases_count' => 0,
        'balance_case_count' => 0,
        'read_only' => true,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_financial_control_rows($conn, int $limit = 50): array
{
    $rows = m360_mgmt_fetch_pipeline($conn, 500);
    $filtered = [];
    foreach ($rows as $row) {
        $rem = (float)($row['remaining_amount'] ?? $row['settlement_remaining_amount'] ?? 0);
        $flags = $row['risk_flags'] ?? [];
        if ($rem > 0 || in_array('INVOICE_VARIANCE', $flags, true) || in_array('DELIVERY_READY_UNPAID', $flags, true)) {
            $filtered[] = $row;
        }
    }
    return array_slice($filtered, 0, max(1, min(100, $limit)));
}
