<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

if (!function_exists('m360_cst_stages')) {
    /**
     * @return array<int, array{label: string, cartable: string}>
     */
    function m360_cst_stages(): array
    {
        return [
            1 => ['label' => 'درخواست اولیه', 'cartable' => 'پذیرش'],
            2 => ['label' => 'پذیرش فیزیکی', 'cartable' => 'پذیرش'],
            3 => ['label' => 'قرارداد مشتری', 'cartable' => 'مشتری'],
            4 => ['label' => 'پیش‌پرداخت / تأیید مالک', 'cartable' => 'مالک / مدیر مجاز'],
            5 => ['label' => 'ارسال به سالن / JobCard', 'cartable' => 'پذیرش'],
            6 => ['label' => 'بررسی سالن', 'cartable' => 'مسئول سالن'],
            7 => ['label' => 'برآورد', 'cartable' => 'تکنسین'],
            8 => ['label' => 'تأیید مشتری', 'cartable' => 'مشتری'],
            9 => ['label' => 'انبار / قطعه', 'cartable' => 'انبار'],
            10 => ['label' => 'اجرای کار', 'cartable' => 'تکنسین'],
            11 => ['label' => 'کنترل کیفیت', 'cartable' => 'کنترل کیفیت'],
            12 => ['label' => 'فاکتور نهایی', 'cartable' => 'مالی'],
            13 => ['label' => 'تحویل', 'cartable' => 'تحویل'],
            14 => ['label' => 'بسته‌شده', 'cartable' => 'سیستم / بسته‌شده'],
        ];
    }
}

if (!function_exists('m360_cst_db_ready')) {
    function m360_cst_db_ready($db): bool
    {
        return $db instanceof PDO || is_resource($db);
    }
}

if (!function_exists('m360_cst_read_sql')) {
    function m360_cst_read_sql(string $sql): bool
    {
        $trimmed = ltrim($sql);
        return stripos($trimmed, 'SELECT') === 0 || stripos($trimmed, 'WITH') === 0;
    }
}

if (!function_exists('m360_cst_identifier_ok')) {
    function m360_cst_identifier_ok(string $identifier): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) === 1;
    }
}

if (!function_exists('m360_cst_fetch_rows')) {
    /**
     * @param list<mixed> $params
     * @return list<array<string, string>>
     */
    function m360_cst_fetch_rows($db, string $sql, array $params = []): array
    {
        if (!m360_cst_db_ready($db) || !m360_cst_read_sql($sql)) {
            return [];
        }

        if ($db instanceof PDO) {
            try {
                $statement = $db->prepare($sql);
                if ($statement === false || !$statement->execute($params)) {
                    return [];
                }
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable) {
                return [];
            }

            $normalized = [];
            foreach ($rows as $row) {
                $item = [];
                foreach ($row as $key => $value) {
                    $item[strtolower((string)$key)] = $value === null ? '' : (string)$value;
                }
                $normalized[] = $item;
            }

            return $normalized;
        }

        if (function_exists('customer_core_fetch_rows')) {
            return customer_core_fetch_rows($db, $sql, $params);
        }

        $statement = @odbc_prepare($db, $sql);
        if ($statement === false || !@odbc_execute($statement, $params)) {
            return [];
        }

        $rows = [];
        while (@odbc_fetch_row($statement)) {
            $row = [];
            $columnCount = @odbc_num_fields($statement);
            if ($columnCount === false || $columnCount < 1) {
                continue;
            }
            for ($i = 1; $i <= $columnCount; $i++) {
                $name = @odbc_field_name($statement, $i);
                if ($name === false) {
                    continue;
                }
                $value = @odbc_result($statement, $i);
                $row[strtolower((string)$name)] = $value === false || $value === null ? '' : (string)$value;
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('m360_cst_fetch_one')) {
    /**
     * @param list<mixed> $params
     * @return array<string, string>|null
     */
    function m360_cst_fetch_one($db, string $sql, array $params = []): ?array
    {
        $rows = m360_cst_fetch_rows($db, $sql, $params);

        return $rows[0] ?? null;
    }
}

if (!function_exists('m360_cst_table_exists')) {
    function m360_cst_table_exists($db, string $tableName): bool
    {
        if (!m360_cst_identifier_ok($tableName) || !m360_cst_db_ready($db)) {
            return false;
        }

        if ($db instanceof PDO) {
            $row = m360_cst_fetch_one(
                $db,
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                ['dbo', $tableName]
            );
            return $row !== null && (int)($row['table_count'] ?? 0) > 0;
        }

        if (function_exists('customer_core_table_exists')) {
            return customer_core_table_exists($db, $tableName);
        }

        $row = m360_cst_fetch_one(
            $db,
            'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            ['dbo', $tableName]
        );

        return $row !== null && (int)($row['table_count'] ?? 0) > 0;
    }
}

if (!function_exists('m360_cst_column_exists')) {
    function m360_cst_column_exists($db, string $tableName, string $columnName): bool
    {
        if (!m360_cst_identifier_ok($tableName) || !m360_cst_identifier_ok($columnName) || !m360_cst_db_ready($db)) {
            return false;
        }

        if ($db instanceof PDO) {
            $row = m360_cst_fetch_one(
                $db,
                'SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['dbo', $tableName, $columnName]
            );
            return $row !== null && (int)($row['column_count'] ?? 0) > 0;
        }

        if (function_exists('customer_core_column_exists')) {
            return customer_core_column_exists($db, $tableName, $columnName);
        }

        $row = m360_cst_fetch_one(
            $db,
            'SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['dbo', $tableName, $columnName]
        );

        return $row !== null && (int)($row['column_count'] ?? 0) > 0;
    }
}

if (!function_exists('m360_cst_fetch_by_id')) {
    /**
     * @return array<string, string>|null
     */
    function m360_cst_fetch_by_id($db, string $tableName, string $idColumn, int $id): ?array
    {
        if ($id < 1 || !m360_cst_identifier_ok($tableName) || !m360_cst_identifier_ok($idColumn) || !m360_cst_table_exists($db, $tableName)) {
            return null;
        }

        return m360_cst_fetch_one($db, 'SELECT TOP 1 * FROM dbo.' . $tableName . ' WHERE ' . $idColumn . ' = ?', [$id]);
    }
}

if (!function_exists('m360_cst_fetch_latest')) {
    /**
     * @param array<string, int|string> $where
     * @return array<string, string>|null
     */
    function m360_cst_fetch_latest($db, string $tableName, array $where, string $orderColumn): ?array
    {
        if (!m360_cst_identifier_ok($tableName) || !m360_cst_identifier_ok($orderColumn) || !m360_cst_table_exists($db, $tableName)) {
            return null;
        }

        $clauses = [];
        $params = [];
        foreach ($where as $column => $value) {
            if (!m360_cst_identifier_ok((string)$column) || !m360_cst_column_exists($db, $tableName, (string)$column)) {
                continue;
            }
            $clauses[] = $column . ' = ?';
            $params[] = $value;
        }

        if ($clauses === []) {
            return null;
        }

        return m360_cst_fetch_one(
            $db,
            'SELECT TOP 1 * FROM dbo.' . $tableName . ' WHERE ' . implode(' AND ', $clauses) . ' ORDER BY ' . $orderColumn . ' DESC',
            $params
        );
    }
}

if (!function_exists('m360_cst_context_int')) {
    /**
     * @param list<string> $keys
     */
    function m360_cst_context_int(array $context, array $keys): int
    {
        foreach ($keys as $key) {
            if (isset($context[$key]) && is_numeric($context[$key]) && (int)$context[$key] > 0) {
                return (int)$context[$key];
            }
        }

        return 0;
    }
}

if (!function_exists('m360_cst_row_int')) {
    function m360_cst_row_int(?array $row, string $key): int
    {
        if ($row === null || !isset($row[$key]) || !is_numeric($row[$key])) {
            return 0;
        }

        return (int)$row[$key];
    }
}

if (!function_exists('m360_cst_row_text')) {
    function m360_cst_row_text(?array $row, string $key): string
    {
        if ($row === null || !isset($row[$key])) {
            return '';
        }

        return trim((string)$row[$key]);
    }
}

if (!function_exists('m360_cst_upper')) {
    function m360_cst_upper(string $value): string
    {
        return strtoupper(trim($value));
    }
}

if (!function_exists('m360_cst_first_positive')) {
    function m360_cst_first_positive(int ...$values): int
    {
        foreach ($values as $value) {
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }
}

if (!function_exists('m360_cst_boolish')) {
    function m360_cst_boolish(mixed $value): bool
    {
        $text = m360_cst_upper((string)$value);

        return in_array($text, ['1', 'Y', 'YES', 'TRUE', 'ON', 'VERIFIED', 'APPROVED'], true);
    }
}

if (!function_exists('m360_cst_json_array')) {
    /**
     * @return array<string, mixed>
     */
    function m360_cst_json_array(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('m360_cst_payload_value')) {
    function m360_cst_payload_value(array $payload, string $key): mixed
    {
        if (array_key_exists($key, $payload)) {
            return $payload[$key];
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $found = m360_cst_payload_value($value, $key);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }
}

if (!function_exists('m360_cst_identity_clean')) {
    /**
     * @param array<string, int> $identity
     * @return array<string, int>
     */
    function m360_cst_identity_clean(array $identity): array
    {
        $identity['request_id'] = $identity['online_request_id'] ?? ($identity['request_id'] ?? 0);
        $identity['online_request_id'] = $identity['online_request_id'] ?? $identity['request_id'];

        foreach ($identity as $key => $value) {
            $identity[$key] = is_numeric($value) && (int)$value > 0 ? (int)$value : 0;
        }

        return $identity;
    }
}

if (!function_exists('m360_cst_make_result')) {
    /**
     * @param array<string, int> $identity
     * @param list<int> $completed
     * @param list<int> $locked
     * @param list<array<string, mixed>> $activeDecisions
     * @param array<string, mixed> $sourceSummary
     * @return array<string, mixed>
     */
    function m360_cst_make_result(
        array $identity,
        int $stageNumber,
        string $stageLabel,
        string $cartable,
        string $status,
        string $nextAction,
        string $actionOwner,
        string $blockerReason,
        array $completed,
        array $locked,
        array $activeDecisions,
        string $confidence,
        array $sourceSummary
    ): array {
        $identity = m360_cst_identity_clean($identity);

        return [
            'case_identity' => $identity,
            'current_stage_number' => $stageNumber,
            'current_stage' => $stageLabel,
            'current_cartable' => $cartable,
            'current_status' => $status,
            'next_action' => $nextAction,
            'action_owner' => $actionOwner,
            'blocker_reason' => $blockerReason,
            'completed_stages' => array_values(array_unique(array_map('intval', $completed))),
            'locked_stages' => array_values(array_unique(array_map('intval', $locked))),
            'active_decisions' => $activeDecisions,
            'confidence' => $confidence,
            'source_summary' => $sourceSummary,
            'all_stages' => m360_cst_stages(),
        ];
    }
}

if (!function_exists('m360_cst_unknown_result')) {
    /**
     * @param array<string, int> $identity
     * @param array<string, mixed> $sourceSummary
     * @return array<string, mixed>
     */
    function m360_cst_unknown_result(array $identity = [], array $sourceSummary = []): array
    {
        return m360_cst_make_result(
            $identity,
            0,
            'نامشخص',
            'نامشخص',
            'نامشخص',
            'نیازمند بررسی اطلاعات پرونده',
            'system',
            'داده کافی برای تشخیص مرحله وجود ندارد',
            [],
            [],
            [],
            'low',
            $sourceSummary
        );
    }
}

if (!function_exists('m360_cst_signed_contract')) {
    function m360_cst_signed_contract(?array $contract, ?array $jobcard): bool
    {
        $contractStatus = m360_cst_upper(m360_cst_row_text($contract, 'contract_status'));
        $jobcardContractStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'contract_status'));

        return in_array($contractStatus, ['SIGNED', 'ACTIVE', 'COMPLETED', 'LOCKED'], true)
            || in_array($jobcardContractStatus, ['SIGNED', 'ACTIVE', 'COMPLETED', 'LOCKED'], true)
            || m360_cst_row_text($contract, 'signed_at') !== ''
            || m360_cst_row_text($jobcard, 'contract_signed_at') !== '';
    }
}

if (!function_exists('m360_cst_estimate_approved')) {
    function m360_cst_estimate_approved(?array $estimate, ?array $jobcard, ?array $version): bool
    {
        $estimateStatus = m360_cst_upper(m360_cst_row_text($estimate, 'estimate_status'));
        $jobcardEstimateStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'estimate_status'));
        $versionStatus = m360_cst_upper(m360_cst_row_text($version, 'version_status'));

        return in_array($estimateStatus, ['APPROVED', 'APPROVED_FOR_WORK', 'CUSTOMER_APPROVED'], true)
            || in_array($jobcardEstimateStatus, ['APPROVED', 'APPROVED_FOR_WORK', 'CUSTOMER_APPROVED'], true)
            || in_array($versionStatus, ['ACCEPTED', 'APPROVED', 'CUSTOMER_APPROVED'], true)
            || m360_cst_row_text($estimate, 'approved_at') !== ''
            || m360_cst_row_text($jobcard, 'estimate_approved_at') !== '';
    }
}

if (!function_exists('m360_cst_estimate_sent')) {
    function m360_cst_estimate_sent(?array $estimate, ?array $jobcard, ?array $version): bool
    {
        $estimateStatus = m360_cst_upper(m360_cst_row_text($estimate, 'estimate_status'));
        $jobcardEstimateStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'estimate_status'));
        $versionStatus = m360_cst_upper(m360_cst_row_text($version, 'version_status'));

        return in_array($estimateStatus, ['SENT_TO_CUSTOMER', 'SENT', 'ISSUED', 'VIEWED', 'APPROVED', 'APPROVED_FOR_WORK'], true)
            || in_array($jobcardEstimateStatus, ['ESTIMATE_SENT', 'SENT_TO_CUSTOMER', 'APPROVED_FOR_WORK'], true)
            || in_array($versionStatus, ['ISSUED', 'VIEWED', 'ACCEPTED', 'APPROVED'], true)
            || m360_cst_row_text($estimate, 'sent_at') !== ''
            || m360_cst_row_text($version, 'issued_at') !== '';
    }
}

if (!function_exists('m360_cst_invoice_finalized')) {
    function m360_cst_invoice_finalized(?array $invoice, ?array $jobcard): bool
    {
        $invoiceStatus = m360_cst_upper(m360_cst_row_text($invoice, 'invoice_status'));
        $jobcardInvoiceStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'final_invoice_status'));

        return in_array($invoiceStatus, ['FINALIZED', 'ISSUED', 'LOCKED'], true)
            || in_array($jobcardInvoiceStatus, ['FINALIZED', 'ISSUED', 'LOCKED'], true)
            || m360_cst_row_text($invoice, 'finalized_at') !== '';
    }
}

if (!function_exists('m360_cst_delivery_released')) {
    function m360_cst_delivery_released(?array $delivery, ?array $jobcard, ?array $confirmation): bool
    {
        $deliveryStatus = m360_cst_upper(m360_cst_row_text($delivery, 'delivery_status'));
        $customerDeliveryStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'customer_delivery_status'));
        $jobcardStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'jobcard_status'));
        $confirmationStatus = m360_cst_upper(m360_cst_row_text($confirmation, 'confirmation_status'));

        return in_array($deliveryStatus, ['RELEASED', 'VEHICLE_RELEASED', 'DELIVERED'], true)
            || in_array($customerDeliveryStatus, ['VEHICLE_RELEASED', 'RELEASED', 'DELIVERED'], true)
            || in_array($jobcardStatus, ['CLOSED', 'DELIVERED'], true)
            || in_array($confirmationStatus, ['CONFIRMED', 'SIGNED', 'ACCEPTED'], true)
            || m360_cst_row_text($jobcard, 'vehicle_released_at') !== ''
            || m360_cst_row_text($delivery, 'released_at') !== '';
    }
}

if (!function_exists('m360_cst_qc_passed')) {
    function m360_cst_qc_passed(?array $qc, ?array $jobcard): bool
    {
        $qcStatus = m360_cst_upper(m360_cst_row_text($qc, 'qc_status'));
        $qcResult = m360_cst_upper(m360_cst_row_text($qc, 'qc_result'));
        $jobcardQc = m360_cst_upper(m360_cst_row_text($jobcard, 'qc_status'));

        return in_array($qcStatus, ['PASSED', 'PASS', 'DELIVERY_READY', 'CLEARED'], true)
            || in_array($qcResult, ['PASSED', 'PASS', 'OK'], true)
            || in_array($jobcardQc, ['PASSED', 'PASS', 'DELIVERY_READY', 'CLEARED'], true)
            || m360_cst_row_text($qc, 'passed_at') !== ''
            || m360_cst_row_text($jobcard, 'qc_passed_at') !== '';
    }
}

if (!function_exists('m360_cst_work_ready_for_qc')) {
    function m360_cst_work_ready_for_qc(?array $jobcard): bool
    {
        $workStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'work_execution_status'));

        return in_array($workStatus, ['READY_FOR_QC', 'COMPLETED', 'DONE', 'CLOSED'], true)
            || m360_cst_row_text($jobcard, 'ready_for_qc_at') !== ''
            || m360_cst_row_text($jobcard, 'work_completed_at') !== '';
    }
}

if (!function_exists('m360_cst_parts_cleared')) {
    function m360_cst_parts_cleared(?array $estimate, ?array $jobcard): bool
    {
        $partsRequired = m360_cst_boolish(m360_cst_row_text($estimate, 'parts_required'));
        $estimateGate = m360_cst_upper(m360_cst_row_text($estimate, 'parts_gate_status'));
        $jobcardGate = m360_cst_upper(m360_cst_row_text($jobcard, 'parts_gate_status'));
        $consumption = m360_cst_upper(m360_cst_row_text($jobcard, 'parts_consumption_status'));

        if (!$partsRequired && $estimateGate === '' && $jobcardGate === '') {
            return true;
        }

        return in_array($estimateGate, ['CLEARED', 'NOT_REQUIRED', 'APPROVED'], true)
            || in_array($jobcardGate, ['CLEARED', 'NOT_REQUIRED', 'APPROVED'], true)
            || in_array($consumption, ['CLEARED', 'NOT_REQUIRED', 'CONSUMED'], true);
    }
}

if (!function_exists('m360_cst_finance_cleared')) {
    function m360_cst_finance_cleared(?array $estimate, ?array $jobcard, ?array $settlement): bool
    {
        $financeRequired = m360_cst_boolish(m360_cst_row_text($estimate, 'finance_required'));
        $estimateGate = m360_cst_upper(m360_cst_row_text($estimate, 'finance_gate_status'));
        $jobcardGate = m360_cst_upper(m360_cst_row_text($jobcard, 'finance_gate_status'));
        $settlementStatus = m360_cst_upper(m360_cst_row_text($settlement, 'settlement_status'));
        $managerRelease = m360_cst_boolish(m360_cst_row_text($settlement, 'manager_release_approved'));

        if (!$financeRequired && $estimateGate === '' && $jobcardGate === '') {
            return true;
        }

        return in_array($estimateGate, ['CLEARED', 'NOT_REQUIRED', 'APPROVED'], true)
            || in_array($jobcardGate, ['CLEARED', 'NOT_REQUIRED', 'APPROVED'], true)
            || in_array($settlementStatus, ['PAID', 'SETTLED', 'MANAGER_RELEASE_APPROVED'], true)
            || $managerRelease;
    }
}

if (!function_exists('m360_cst_prepayment_blocked')) {
    function m360_cst_prepayment_blocked(?array $request): bool
    {
        $payload = m360_cst_json_array(m360_cst_row_text($request, 'request_payload_json'));
        if ($payload === []) {
            return false;
        }

        $required = m360_cst_payload_value($payload, 'prepayment_required');
        $amount = m360_cst_payload_value($payload, 'prepayment_amount');
        $approved = m360_cst_payload_value($payload, 'prepayment_approved');
        $ownerDecision = m360_cst_payload_value($payload, 'owner_prepayment_decision');

        $requiresPayment = m360_cst_boolish($required) || (is_numeric($amount) && (float)$amount > 0);
        $isApproved = m360_cst_boolish($approved) || in_array(m360_cst_upper((string)$ownerDecision), ['APPROVED', 'WAIVED', 'NOT_REQUIRED'], true);

        return $requiresPayment && !$isApproved;
    }
}

if (!function_exists('m360_cst_task_stage')) {
    /**
     * @return array{stage: int, next: string, blocker: string, decision_type: string}
     */
    function m360_cst_task_stage(string $taskType): array
    {
        $taskType = m360_cst_upper($taskType);

        if ($taskType === 'CONTRACT_SIGNATURE') {
            return [
                'stage' => 3,
                'next' => 'امضای قرارداد توسط مشتری',
                'blocker' => 'امضای قرارداد مشتری در انتظار است',
                'decision_type' => 'contract_customer_signature',
            ];
        }

        if ($taskType === 'DELIVERY_CONFIRMATION') {
            return [
                'stage' => 13,
                'next' => 'تأیید تحویل توسط مشتری',
                'blocker' => 'تأیید تحویل مشتری در انتظار است',
                'decision_type' => 'delivery_customer_confirmation',
            ];
        }

        return [
            'stage' => 8,
            'next' => 'تأیید برآورد توسط مشتری',
            'blocker' => 'تأیید مشتری در انتظار است',
            'decision_type' => 'estimate_customer_approval',
        ];
    }
}

if (!function_exists('m360_cst_fetch_active_tasks')) {
    /**
     * @param array<string, int> $identity
     * @return list<array<string, string>>
     */
    function m360_cst_fetch_active_tasks($db, array $identity, ?array $version, ?array $seedTask): array
    {
        if (!m360_cst_table_exists($db, 'erp_customer_cartable_tasks')) {
            return [];
        }

        $clauses = [];
        $params = [];
        foreach ([
            'online_request_id' => $identity['online_request_id'] ?? 0,
            'jobcard_id' => $identity['jobcard_id'] ?? 0,
            'contract_id' => $identity['contract_id'] ?? 0,
            'estimate_id' => $identity['estimate_id'] ?? 0,
            'invoice_id' => $identity['final_invoice_id'] ?? 0,
        ] as $column => $value) {
            if ((int)$value > 0) {
                $clauses[] = $column . ' = ?';
                $params[] = (int)$value;
            }
        }

        $versionId = m360_cst_row_int($version, 'estimate_version_id');
        if ($versionId > 0) {
            $clauses[] = '(source_entity_id = ? AND UPPER(source_entity_type) IN (\'ERP_ESTIMATE_VERSION\', \'ESTIMATE_VERSION\'))';
            $params[] = $versionId;
        }

        $rows = [];
        if ($clauses !== []) {
            $rows = m360_cst_fetch_rows(
                $db,
                'SELECT TOP 8 *
                 FROM dbo.erp_customer_cartable_tasks
                 WHERE is_active = 1
                   AND UPPER(status) IN (\'PENDING\', \'OPENED\', \'WAITING\')
                   AND (' . implode(' OR ', $clauses) . ')
                 ORDER BY
                   CASE UPPER(task_type)
                     WHEN \'ESTIMATE_APPROVAL\' THEN 1
                     WHEN \'CONTRACT_SIGNATURE\' THEN 2
                     WHEN \'DELIVERY_CONFIRMATION\' THEN 3
                     ELSE 9
                   END,
                   task_id DESC',
                $params
            );
        }

        if ($seedTask !== null
            && m360_cst_boolish($seedTask['is_active'] ?? '')
            && in_array(m360_cst_upper((string)($seedTask['status'] ?? '')), ['PENDING', 'OPENED', 'WAITING'], true)
        ) {
            $already = false;
            $seedId = m360_cst_row_int($seedTask, 'task_id');
            foreach ($rows as $row) {
                if (m360_cst_row_int($row, 'task_id') === $seedId) {
                    $already = true;
                    break;
                }
            }
            if (!$already) {
                array_unshift($rows, $seedTask);
            }
        }

        return $rows;
    }
}

if (!function_exists('m360_cst_active_decisions')) {
    /**
     * @param list<array<string, string>> $tasks
     * @return list<array<string, mixed>>
     */
    function m360_cst_active_decisions(array $tasks): array
    {
        $decisions = [];
        foreach ($tasks as $task) {
            $meta = m360_cst_task_stage((string)($task['task_type'] ?? ''));
            $decisions[] = [
                'task_id' => m360_cst_row_int($task, 'task_id'),
                'task_type' => (string)($task['task_type'] ?? ''),
                'decision_type' => $meta['decision_type'],
                'decision_status' => 'در انتظار',
                'action_owner' => 'customer',
                'source_module' => (string)($task['source_module'] ?? ''),
                'source_entity_type' => (string)($task['source_entity_type'] ?? ''),
                'source_entity_id' => m360_cst_row_int($task, 'source_entity_id'),
            ];
        }

        return $decisions;
    }
}

if (!function_exists('m360_cst_fetch_context_sources')) {
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    function m360_cst_fetch_context_sources($db, array $context): array
    {
        $identity = [
            'request_id' => m360_cst_context_int($context, ['request_id', 'online_request_id']),
            'online_request_id' => m360_cst_context_int($context, ['online_request_id', 'request_id']),
            'jobcard_id' => m360_cst_context_int($context, ['jobcard_id']),
            'contract_id' => m360_cst_context_int($context, ['contract_id']),
            'estimate_id' => m360_cst_context_int($context, ['estimate_id']),
            'final_invoice_id' => m360_cst_context_int($context, ['final_invoice_id', 'invoice_id']),
            'delivery_control_id' => m360_cst_context_int($context, ['delivery_control_id']),
            'customer_task_id' => m360_cst_context_int($context, ['customer_task_id', 'task_id']),
            'customer_id' => m360_cst_context_int($context, ['customer_id']),
            'vehicle_id' => m360_cst_context_int($context, ['vehicle_id']),
        ];

        $seedTask = null;
        if ($identity['customer_task_id'] > 0) {
            $seedTask = m360_cst_fetch_by_id($db, 'erp_customer_cartable_tasks', 'task_id', $identity['customer_task_id']);
            if ($seedTask !== null) {
                $identity['online_request_id'] = m360_cst_first_positive($identity['online_request_id'], m360_cst_row_int($seedTask, 'online_request_id'));
                $identity['request_id'] = $identity['online_request_id'];
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($seedTask, 'jobcard_id'));
                $identity['contract_id'] = m360_cst_first_positive($identity['contract_id'], m360_cst_row_int($seedTask, 'contract_id'));
                $identity['estimate_id'] = m360_cst_first_positive($identity['estimate_id'], m360_cst_row_int($seedTask, 'estimate_id'));
                $identity['final_invoice_id'] = m360_cst_first_positive($identity['final_invoice_id'], m360_cst_row_int($seedTask, 'invoice_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($seedTask, 'customer_id'));
            }
        }

        $delivery = null;
        if ($identity['delivery_control_id'] > 0) {
            $delivery = m360_cst_fetch_by_id($db, 'erp_delivery_controls', 'delivery_control_id', $identity['delivery_control_id']);
            if ($delivery !== null) {
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($delivery, 'jobcard_id'));
            }
        }

        $invoice = null;
        if ($identity['final_invoice_id'] > 0) {
            $invoice = m360_cst_fetch_by_id($db, 'erp_final_invoices', 'final_invoice_id', $identity['final_invoice_id']);
            if ($invoice !== null) {
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($invoice, 'jobcard_id'));
                $identity['estimate_id'] = m360_cst_first_positive($identity['estimate_id'], m360_cst_row_int($invoice, 'estimate_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($invoice, 'customer_id'));
                $identity['vehicle_id'] = m360_cst_first_positive($identity['vehicle_id'], m360_cst_row_int($invoice, 'vehicle_id'));
            }
        }

        $estimate = null;
        if ($identity['estimate_id'] > 0) {
            $estimate = m360_cst_fetch_by_id($db, 'erp_estimates', 'estimate_id', $identity['estimate_id']);
            if ($estimate !== null) {
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($estimate, 'jobcard_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($estimate, 'customer_id'));
                $identity['vehicle_id'] = m360_cst_first_positive($identity['vehicle_id'], m360_cst_row_int($estimate, 'vehicle_id'));
            }
        }

        $contract = null;
        if ($identity['contract_id'] > 0) {
            $contract = m360_cst_fetch_by_id($db, 'erp_intake_contracts', 'contract_id', $identity['contract_id']);
            if ($contract !== null) {
                $identity['online_request_id'] = m360_cst_first_positive($identity['online_request_id'], m360_cst_row_int($contract, 'online_request_id'));
                $identity['request_id'] = $identity['online_request_id'];
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($contract, 'jobcard_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($contract, 'customer_id'));
                $identity['vehicle_id'] = m360_cst_first_positive($identity['vehicle_id'], m360_cst_row_int($contract, 'vehicle_id'));
            }
        }

        $request = null;
        if ($identity['online_request_id'] > 0) {
            $request = m360_cst_fetch_by_id($db, 'erp_customer_online_requests', 'online_request_id', $identity['online_request_id']);
            if ($request !== null) {
                $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($request, 'converted_jobcard_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($request, 'customer_id'));
                $identity['vehicle_id'] = m360_cst_first_positive($identity['vehicle_id'], m360_cst_row_int($request, 'vehicle_id'));
            }
        }

        $jobcard = null;
        if ($identity['jobcard_id'] > 0) {
            $jobcard = m360_cst_fetch_by_id($db, 'erp_jobcards', 'jobcard_id', $identity['jobcard_id']);
            if ($jobcard !== null) {
                $identity['online_request_id'] = m360_cst_first_positive($identity['online_request_id'], m360_cst_row_int($jobcard, 'online_request_id'));
                $identity['request_id'] = $identity['online_request_id'];
                $identity['contract_id'] = m360_cst_first_positive($identity['contract_id'], m360_cst_row_int($jobcard, 'intake_contract_id'));
                $identity['estimate_id'] = m360_cst_first_positive($identity['estimate_id'], m360_cst_row_int($jobcard, 'current_estimate_id'));
                $identity['final_invoice_id'] = m360_cst_first_positive($identity['final_invoice_id'], m360_cst_row_int($jobcard, 'current_final_invoice_id'));
                $identity['customer_id'] = m360_cst_first_positive($identity['customer_id'], m360_cst_row_int($jobcard, 'customer_id'));
                $identity['vehicle_id'] = m360_cst_first_positive($identity['vehicle_id'], m360_cst_row_int($jobcard, 'vehicle_id'));
            }
        }

        if ($request === null && $identity['online_request_id'] > 0) {
            $request = m360_cst_fetch_by_id($db, 'erp_customer_online_requests', 'online_request_id', $identity['online_request_id']);
        }
        if ($contract === null && $identity['contract_id'] > 0) {
            $contract = m360_cst_fetch_by_id($db, 'erp_intake_contracts', 'contract_id', $identity['contract_id']);
        }
        if ($contract === null && $identity['jobcard_id'] > 0) {
            $contract = m360_cst_fetch_latest($db, 'erp_intake_contracts', ['jobcard_id' => $identity['jobcard_id']], 'contract_id');
        }
        if ($contract === null && $identity['online_request_id'] > 0) {
            $contract = m360_cst_fetch_latest($db, 'erp_intake_contracts', ['online_request_id' => $identity['online_request_id']], 'contract_id');
        }
        if ($contract !== null) {
            $identity['contract_id'] = m360_cst_first_positive($identity['contract_id'], m360_cst_row_int($contract, 'contract_id'));
        }

        if ($estimate === null && $identity['estimate_id'] > 0) {
            $estimate = m360_cst_fetch_by_id($db, 'erp_estimates', 'estimate_id', $identity['estimate_id']);
        }
        if ($estimate === null && $identity['jobcard_id'] > 0) {
            $estimate = m360_cst_fetch_latest($db, 'erp_estimates', ['jobcard_id' => $identity['jobcard_id']], 'estimate_id');
        }
        if ($estimate !== null) {
            $identity['estimate_id'] = m360_cst_first_positive($identity['estimate_id'], m360_cst_row_int($estimate, 'estimate_id'));
            $identity['jobcard_id'] = m360_cst_first_positive($identity['jobcard_id'], m360_cst_row_int($estimate, 'jobcard_id'));
        }

        $version = null;
        if ($seedTask !== null
            && m360_cst_row_int($seedTask, 'source_entity_id') > 0
            && in_array(m360_cst_upper(m360_cst_row_text($seedTask, 'source_entity_type')), ['ERP_ESTIMATE_VERSION', 'ESTIMATE_VERSION'], true)
        ) {
            $version = m360_cst_fetch_by_id($db, 'erp_estimate_versions', 'estimate_version_id', m360_cst_row_int($seedTask, 'source_entity_id'));
        }
        if ($version === null && $identity['estimate_id'] > 0) {
            $version = m360_cst_fetch_latest($db, 'erp_estimate_versions', ['estimate_id' => $identity['estimate_id']], 'estimate_version_id');
        }

        if ($invoice === null && $identity['final_invoice_id'] > 0) {
            $invoice = m360_cst_fetch_by_id($db, 'erp_final_invoices', 'final_invoice_id', $identity['final_invoice_id']);
        }
        if ($invoice === null && $identity['jobcard_id'] > 0) {
            $invoice = m360_cst_fetch_latest($db, 'erp_final_invoices', ['jobcard_id' => $identity['jobcard_id']], 'final_invoice_id');
        }
        if ($invoice !== null) {
            $identity['final_invoice_id'] = m360_cst_first_positive($identity['final_invoice_id'], m360_cst_row_int($invoice, 'final_invoice_id'));
        }

        if ($delivery === null && $identity['jobcard_id'] > 0) {
            $delivery = m360_cst_fetch_latest($db, 'erp_delivery_controls', ['jobcard_id' => $identity['jobcard_id']], 'delivery_control_id');
        }
        if ($delivery !== null) {
            $identity['delivery_control_id'] = m360_cst_first_positive($identity['delivery_control_id'], m360_cst_row_int($delivery, 'delivery_control_id'));
        }

        $confirmation = null;
        if ($identity['final_invoice_id'] > 0) {
            $confirmation = m360_cst_fetch_latest($db, 'erp_customer_delivery_confirmations', ['final_invoice_id' => $identity['final_invoice_id']], 'delivery_confirmation_id');
        }
        if ($confirmation === null && $identity['jobcard_id'] > 0) {
            $confirmation = m360_cst_fetch_latest($db, 'erp_customer_delivery_confirmations', ['jobcard_id' => $identity['jobcard_id']], 'delivery_confirmation_id');
        }

        $qc = null;
        if ($identity['jobcard_id'] > 0) {
            $qc = m360_cst_fetch_latest($db, 'erp_qc_checks', ['jobcard_id' => $identity['jobcard_id']], 'qc_check_id');
        }

        $settlement = null;
        if ($identity['final_invoice_id'] > 0) {
            $settlement = m360_cst_fetch_latest($db, 'erp_settlement_controls', ['final_invoice_id' => $identity['final_invoice_id']], 'settlement_id');
        }
        if ($settlement === null && $identity['jobcard_id'] > 0) {
            $settlement = m360_cst_fetch_latest($db, 'erp_settlement_controls', ['jobcard_id' => $identity['jobcard_id']], 'settlement_id');
        }

        $identity = m360_cst_identity_clean($identity);
        $activeTasks = m360_cst_fetch_active_tasks($db, $identity, $version, $seedTask);

        return [
            'identity' => $identity,
            'request' => $request,
            'jobcard' => $jobcard,
            'contract' => $contract,
            'estimate' => $estimate,
            'version' => $version,
            'invoice' => $invoice,
            'delivery' => $delivery,
            'confirmation' => $confirmation,
            'qc' => $qc,
            'settlement' => $settlement,
            'active_tasks' => $activeTasks,
        ];
    }
}

if (!function_exists('m360_cst_source_summary')) {
    /**
     * @param array<string, mixed> $sources
     * @return array<string, mixed>
     */
    function m360_cst_source_summary(array $sources): array
    {
        $activeTaskIds = [];
        foreach (($sources['active_tasks'] ?? []) as $task) {
            $activeTaskIds[] = m360_cst_row_int($task, 'task_id');
        }

        return [
            'request_status' => m360_cst_row_text($sources['request'] ?? null, 'request_status'),
            'jobcard_status' => m360_cst_row_text($sources['jobcard'] ?? null, 'jobcard_status'),
            'lifecycle_state' => m360_cst_row_text($sources['jobcard'] ?? null, 'lifecycle_state'),
            'contract_status' => m360_cst_row_text($sources['contract'] ?? null, 'contract_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'contract_status'),
            'technical_status' => m360_cst_row_text($sources['jobcard'] ?? null, 'technical_status'),
            'estimate_status' => m360_cst_row_text($sources['estimate'] ?? null, 'estimate_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'estimate_status'),
            'estimate_version_status' => m360_cst_row_text($sources['version'] ?? null, 'version_status'),
            'parts_gate_status' => m360_cst_row_text($sources['estimate'] ?? null, 'parts_gate_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'parts_gate_status'),
            'finance_gate_status' => m360_cst_row_text($sources['estimate'] ?? null, 'finance_gate_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'finance_gate_status'),
            'work_execution_status' => m360_cst_row_text($sources['jobcard'] ?? null, 'work_execution_status'),
            'qc_status' => m360_cst_row_text($sources['qc'] ?? null, 'qc_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'qc_status'),
            'invoice_status' => m360_cst_row_text($sources['invoice'] ?? null, 'invoice_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'final_invoice_status'),
            'settlement_status' => m360_cst_row_text($sources['settlement'] ?? null, 'settlement_status') ?: m360_cst_row_text($sources['jobcard'] ?? null, 'settlement_status'),
            'delivery_status' => m360_cst_row_text($sources['delivery'] ?? null, 'delivery_status'),
            'customer_delivery_status' => m360_cst_row_text($sources['jobcard'] ?? null, 'customer_delivery_status'),
            'active_task_ids' => $activeTaskIds,
        ];
    }
}

if (!function_exists('m360_cst_stage_result')) {
    /**
     * @param array<string, mixed> $sources
     * @param list<int>|null $completed
     * @param list<int>|null $locked
     * @param list<array<string, mixed>> $activeDecisions
     * @return array<string, mixed>
     */
    function m360_cst_stage_result(
        array $sources,
        int $stageNumber,
        string $status,
        string $nextAction,
        string $actionOwner,
        string $blockerReason,
        ?array $completed = null,
        ?array $locked = null,
        array $activeDecisions = [],
        string $confidence = 'medium',
        ?string $cartable = null
    ): array {
        $stages = m360_cst_stages();
        $stage = $stages[$stageNumber] ?? ['label' => 'نامشخص', 'cartable' => 'نامشخص'];

        return m360_cst_make_result(
            $sources['identity'] ?? [],
            $stageNumber,
            $stage['label'],
            $cartable ?? $stage['cartable'],
            $status,
            $nextAction,
            $actionOwner,
            $blockerReason,
            $completed ?? range(1, max(0, $stageNumber - 1)),
            $locked ?? range(min(14, $stageNumber + 1), 14),
            $activeDecisions,
            $confidence,
            m360_cst_source_summary($sources)
        );
    }
}

if (!function_exists('m360_cst_derive_result')) {
    /**
     * @param array<string, mixed> $sources
     * @return array<string, mixed>
     */
    function m360_cst_derive_result(array $sources): array
    {
        $identity = $sources['identity'] ?? [];
        $request = $sources['request'] ?? null;
        $jobcard = $sources['jobcard'] ?? null;
        $contract = $sources['contract'] ?? null;
        $estimate = $sources['estimate'] ?? null;
        $version = $sources['version'] ?? null;
        $invoice = $sources['invoice'] ?? null;
        $delivery = $sources['delivery'] ?? null;
        $confirmation = $sources['confirmation'] ?? null;
        $qc = $sources['qc'] ?? null;
        $settlement = $sources['settlement'] ?? null;
        $activeTasks = is_array($sources['active_tasks'] ?? null) ? $sources['active_tasks'] : [];

        if (!m360_cst_db_ready($sources['db'] ?? false)) {
            return m360_cst_unknown_result($identity, ['db' => 'unavailable']);
        }

        if ($request === null && $jobcard === null && $contract === null && $estimate === null && $invoice === null && $delivery === null) {
            return m360_cst_unknown_result($identity, m360_cst_source_summary($sources));
        }

        if (m360_cst_invoice_finalized($invoice, $jobcard) && m360_cst_delivery_released($delivery, $jobcard, $confirmation)) {
            return m360_cst_stage_result(
                $sources,
                14,
                'بسته شده',
                'چرخه بسته شده',
                'system',
                '',
                range(1, 14),
                [],
                [],
                'high'
            );
        }

        if ($activeTasks !== []) {
            $primary = $activeTasks[0];
            $meta = m360_cst_task_stage((string)($primary['task_type'] ?? ''));
            return m360_cst_stage_result(
                $sources,
                $meta['stage'],
                'در انتظار تأیید',
                $meta['next'],
                'customer',
                $meta['blocker'],
                range(1, max(0, $meta['stage'] - 1)),
                range(min(14, $meta['stage'] + 1), 14),
                m360_cst_active_decisions($activeTasks),
                'high'
            );
        }

        if (!m360_cst_signed_contract($contract, $jobcard)) {
            if ($contract !== null) {
                return m360_cst_stage_result(
                    $sources,
                    3,
                    'در انتظار تأیید',
                    'امضای قرارداد توسط مشتری',
                    'customer',
                    'امضای قرارداد مشتری در انتظار است',
                    [1, 2],
                    range(4, 14),
                    [],
                    'medium'
                );
            }

            return m360_cst_stage_result(
                $sources,
                $request !== null ? 2 : 1,
                'در انتظار',
                'ارسال لینک قرارداد',
                'reception',
                '',
                $request !== null ? [1] : [],
                range(3, 14),
                [],
                'medium'
            );
        }

        if (m360_cst_prepayment_blocked($request)) {
            return m360_cst_stage_result(
                $sources,
                4,
                'مسدود',
                'تعیین تکلیف پیش‌پرداخت یا تأیید شروع بدون پیش‌پرداخت',
                'owner',
                'تأیید مالک یا تعیین تکلیف پیش‌پرداخت در انتظار است',
                [1, 2, 3],
                range(5, 14),
                [],
                'medium'
            );
        }

        if ($jobcard === null || m360_cst_row_int($jobcard, 'jobcard_id') < 1) {
            return m360_cst_stage_result(
                $sources,
                5,
                'آماده مرحله بعد',
                'ارسال به مسئول سالن / ایجاد یا فعال‌سازی JobCard',
                'reception',
                '',
                [1, 2, 3, 4],
                range(6, 14),
                [],
                'medium'
            );
        }

        $assignedTechnicianId = m360_cst_row_int($jobcard, 'assigned_technician_user_id');
        $technicalStatus = m360_cst_upper(m360_cst_row_text($jobcard, 'technical_status'));
        if ($assignedTechnicianId < 1 && !in_array($technicalStatus, ['TECHNICAL_DONE', 'COMPLETED', 'READY_FOR_ESTIMATE'], true)) {
            return m360_cst_stage_result(
                $sources,
                6,
                'در حال انجام',
                'تخصیص تکنسین',
                'service_manager',
                '',
                range(1, 5),
                range(7, 14),
                [],
                'medium'
            );
        }

        if ($estimate === null || m360_cst_row_int($estimate, 'estimate_id') < 1) {
            return m360_cst_stage_result(
                $sources,
                7,
                'در حال انجام',
                'ثبت برآورد',
                'technician',
                '',
                range(1, 6),
                range(8, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_estimate_sent($estimate, $jobcard, $version) && !m360_cst_estimate_approved($estimate, $jobcard, $version)) {
            return m360_cst_stage_result(
                $sources,
                7,
                'در حال انجام',
                'ارسال برآورد برای مشتری',
                'technician',
                '',
                range(1, 6),
                range(8, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_estimate_approved($estimate, $jobcard, $version)) {
            return m360_cst_stage_result(
                $sources,
                8,
                'در انتظار تأیید',
                'تأیید برآورد توسط مشتری',
                'customer',
                'تأیید مشتری در انتظار است',
                range(1, 7),
                range(9, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_parts_cleared($estimate, $jobcard)) {
            return m360_cst_stage_result(
                $sources,
                9,
                'در انتظار',
                'رزرو یا خروج قطعه از انبار',
                'inventory',
                'وضعیت قطعه هنوز قطعی نشده است',
                range(1, 8),
                range(10, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_finance_cleared($estimate, $jobcard, $settlement)) {
            return m360_cst_stage_result(
                $sources,
                12,
                'در انتظار',
                'تکمیل تسویه یا تأیید مالی',
                'finance',
                'وضعیت مالی هنوز قطعی نشده است',
                range(1, 11),
                range(13, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_work_ready_for_qc($jobcard)) {
            return m360_cst_stage_result(
                $sources,
                10,
                'در حال انجام',
                'اجرای کار و ثبت نتیجه',
                'technician',
                '',
                range(1, 9),
                range(11, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_qc_passed($qc, $jobcard)) {
            return m360_cst_stage_result(
                $sources,
                11,
                'در انتظار',
                'ثبت کنترل کیفیت',
                'qc',
                'کنترل کیفیت هنوز عبور نکرده است',
                range(1, 10),
                range(12, 14),
                [],
                'medium'
            );
        }

        if (!m360_cst_invoice_finalized($invoice, $jobcard)) {
            return m360_cst_stage_result(
                $sources,
                12,
                'در انتظار',
                'نهایی‌سازی فاکتور',
                'finance',
                'فاکتور نهایی هنوز قطعی نشده است',
                range(1, 11),
                range(13, 14),
                [],
                'medium'
            );
        }

        return m360_cst_stage_result(
            $sources,
            13,
            'در انتظار',
            'ثبت تحویل خودرو',
            'delivery',
            'تحویل خودرو هنوز نهایی نشده است',
            range(1, 12),
            [14],
            [],
            'medium'
        );
    }
}

if (!function_exists('m360_case_stage_tree_resolve')) {
    /**
     * Read-only V0 resolver derived from existing operational data.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    function m360_case_stage_tree_resolve($db, array $context): array
    {
        if (!m360_cst_db_ready($db)) {
            return m360_cst_unknown_result([], ['db' => 'unavailable']);
        }

        $sources = m360_cst_fetch_context_sources($db, $context);
        $sources['db'] = $db;

        return m360_cst_derive_result($sources);
    }
}
