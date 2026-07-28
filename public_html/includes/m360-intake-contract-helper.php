<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1.5 — Intake contract helper (ERP + P2 gate).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-contract-template-render.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-document-vault-helper.php';

const M360_CONTRACT_TABLE = 'erp_intake_contracts';
const M360_CONTRACT_SIG_TABLE = 'erp_intake_contract_signatures';
const M360_CONTRACT_EVT_TABLE = 'erp_intake_contract_events';

const M360_CONTRACT_CSRF_PURPOSE = 'intake_contract_reception';

const M360_CONTRACT_STATUS_DRAFT = 'DRAFT';
const M360_CONTRACT_STATUS_GENERATED = 'GENERATED';
const M360_CONTRACT_STATUS_SENT = 'SENT';
const M360_CONTRACT_STATUS_VIEWED = 'VIEWED';
const M360_CONTRACT_STATUS_OTP_SENT = 'OTP_SENT';
const M360_CONTRACT_STATUS_SIGNED = 'SIGNED';
const M360_CONTRACT_STATUS_EXPIRED = 'EXPIRED';
const M360_CONTRACT_STATUS_CANCELLED = 'CANCELLED';
const M360_CONTRACT_STATUS_OVERRIDDEN = 'OVERRIDDEN';

const M360_CONTRACT_TOKEN_TTL_SECONDS = 259200;

/** @var array<string, string> */
const M360_CONTRACT_STATUS_LABELS_FA = [
    M360_CONTRACT_STATUS_DRAFT => 'پیش‌نویس',
    M360_CONTRACT_STATUS_GENERATED => 'تولید شده',
    M360_CONTRACT_STATUS_SENT => 'ارسال شده',
    M360_CONTRACT_STATUS_VIEWED => 'مشاهده شده',
    M360_CONTRACT_STATUS_OTP_SENT => 'کد تأیید ارسال شد',
    M360_CONTRACT_STATUS_SIGNED => 'امضا شده',
    M360_CONTRACT_STATUS_EXPIRED => 'منقضی',
    M360_CONTRACT_STATUS_CANCELLED => 'لغو شده',
    M360_CONTRACT_STATUS_OVERRIDDEN => 'تأیید مدیریتی',
];

function m360_intake_contract_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_intake_contract_require_staff(): void
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();
    if ($userId === null || $userId <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_intake_contract_table_exists($conn, string $table): bool
{
    return customer_core_table_exists($conn, $table);
}

function m360_intake_contract_hash(string $value): string
{
    return hash('sha256', $value);
}

function m360_intake_contract_generate_token(): array
{
    $raw = bin2hex(random_bytes(32));
    return [
        'raw' => $raw,
        'hash' => m360_intake_contract_hash($raw),
        'expires_at' => gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS),
    ];
}

/**
 * Absolute public base for rare external/SMS links only.
 * Prefer relative routes in-app. Canonical local UAT host is 127.0.0.1:8080 (not localhost).
 */
function m360_intake_contract_public_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        $host = trim((string)($_SERVER['SERVER_NAME'] ?? ''));
    }
    $m = [];
    // Normalize localhost → 127.0.0.1 for absolute outbound links (canonical local UAT).
    if ($host === '' || preg_match('/^localhost(?::(\d+))?$/i', $host, $m) === 1) {
        $port = (isset($m[1]) && $m[1] !== '') ? $m[1] : '';
        if ($port === '') {
            $serverPort = trim((string)($_SERVER['SERVER_PORT'] ?? ''));
            if ($serverPort !== '' && $serverPort !== '80' && $serverPort !== '443') {
                $port = $serverPort;
            } else {
                $port = '8080';
            }
        }
        $host = '127.0.0.1:' . $port;
    }
    $appRoot = '/moghare360';
    if (function_exists('m360_rw_customer_portal_app_root_web_path')) {
        $detected = trim((string)m360_rw_customer_portal_app_root_web_path());
        if ($detected !== '' && $detected !== '/') {
            $appRoot = rtrim($detected, '/');
        }
    } else {
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== '') {
            $detected = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($detected !== '' && $detected !== '/' && $detected !== '.') {
                $appRoot = $detected;
            }
        }
    }

    return ($https ? 'https' : 'http') . '://' . $host . $appRoot;
}

/**
 * In-app customer contract routes stay relative so localhost vs 127.0.0.1 sessions stay intact.
 */
function m360_intake_contract_customer_url(string $rawToken): string
{
    return 'customer-intake-contract.php?token=' . rawurlencode($rawToken);
}

function m360_intake_contract_sign_url(string $rawToken): string
{
    return 'customer-intake-contract-sign.php?token=' . rawurlencode($rawToken);
}

/** Absolute URL only when an external channel (SMS) needs a full link. */
function m360_intake_contract_customer_absolute_url(string $rawToken): string
{
    return rtrim(m360_intake_contract_public_base_url(), '/') . '/' . ltrim(m360_intake_contract_customer_url($rawToken), '/');
}

function m360_intake_contract_sign_absolute_url(string $rawToken): string
{
    return rtrim(m360_intake_contract_public_base_url(), '/') . '/' . ltrim(m360_intake_contract_sign_url($rawToken), '/');
}

function m360_intake_contract_service_type_fa(string $requestType, string $route = ''): string
{
    $requestType = strtolower(trim($requestType));
    $route = strtolower(trim($route));
    $map = [
        'diagnostic_inspection' => 'عیب‌یابی',
        'diagnostic' => 'عیب‌یابی',
        'diag' => 'عیب‌یابی',
        'buy_sell_inspection' => 'کارشناسی خرید/فروش',
        'inspection' => 'بازدید / کارشناسی',
        'trade' => 'کارشناسی خرید/فروش',
        'periodic_service' => 'سرویس دوره‌ای',
        'periodic' => 'سرویس دوره‌ای',
        'repair' => 'تعمیر',
        'option_add' => 'افزودن آپشن',
        'options' => 'افزودن آپشن',
        'other' => 'سایر',
    ];
    if ($requestType !== '' && isset($map[$requestType])) {
        return $map[$requestType];
    }
    if ($route !== '' && isset($map[$route])) {
        return $map[$route];
    }
    if ($requestType !== '' && !preg_match('/^[a-z0-9_\-]+$/i', $requestType)) {
        return $requestType;
    }
    if ($route !== '' && !preg_match('/^[a-z0-9_\-]+$/i', $route)) {
        return $route;
    }

    return 'نامشخص';
}

/**
 * @return array<string, mixed>
 */
function m360_intake_contract_build_snapshot($conn, ?int $jobcardId, ?int $onlineRequestId): array
{
    $data = [
        'customer_name' => '-',
        'mobile' => '-',
        'vehicle' => '-',
        'plate' => '-',
        'vin' => '-',
        'odometer' => '-',
        'fuel_level' => '-',
        'service_type' => '-',
        'request_description' => '-',
        'cost_range' => '',
        'prepayment' => '',
        'purchase_limit' => '',
        'test_drive_allowed' => '',
        'body_insurance_status' => '',
        'third_party_insurance' => '',
        'other_agreements_note' => '',
        'service_cost_min' => '',
        'service_cost_max' => '',
        'checklist_summary' => '',
        'jobcard_id' => $jobcardId !== null ? (string)$jobcardId : '-',
        'online_request_id' => $onlineRequestId !== null ? (string)$onlineRequestId : '-',
        'visit_date' => date('Y-m-d H:i'),
        'reception_date' => date('Y-m-d'),
        'contract_hash' => '-',
    ];

    if ($conn !== false && $jobcardId !== null && $jobcardId > 0 && customer_core_table_exists($conn, 'erp_jobcards')) {
        $sql = 'SELECT TOP 1 j.jobcard_id, j.customer_id, j.vehicle_id, j.intake_mileage, j.customer_complaint, j.reception_at,
                       c.full_name, c.primary_mobile, v.plate_number, v.brand, v.model, v.vin
                FROM dbo.erp_jobcards j
                LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
                LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
                WHERE j.jobcard_id = ?';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$jobcardId])) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false) {
                $data['customer_name'] = trim((string)($row['full_name'] ?? '')) ?: '-';
                $data['mobile'] = trim((string)($row['primary_mobile'] ?? '')) ?: '-';
                $data['vehicle'] = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? ''))) ?: '-';
                $data['plate'] = trim((string)($row['plate_number'] ?? '')) ?: '-';
                $data['vin'] = trim((string)($row['vin'] ?? '')) ?: '-';
                $data['odometer'] = trim((string)($row['intake_mileage'] ?? '')) ?: '-';
                $data['reception_date'] = substr((string)($row['reception_at'] ?? date('Y-m-d')), 0, 10);
                $data['jobcard_id'] = (string)$jobcardId;
            }
        }
    }

    if ($onlineRequestId !== null && $onlineRequestId > 0) {
        $req = m360_online_req_fetch_by_id($conn, $onlineRequestId);
        if ($req !== null) {
            $payload = m360_online_req_parse_payload($req['request_payload_json'] ?? null);
            $ri = is_array($payload['reception_intake'] ?? null) ? $payload['reception_intake'] : [];
            $vehicle = is_array($ri['vehicle'] ?? null) ? $ri['vehicle'] : [];
            $service = is_array($ri['service_classification'] ?? null) ? $ri['service_classification'] : [];
            $docs = is_array($ri['documents'] ?? null) ? $ri['documents'] : [];
            $condition = is_array($ri['condition'] ?? null) ? $ri['condition'] : [];

            $customerName = trim((string)($req['customer_name'] ?? $payload['customer_name'] ?? $payload['full_name'] ?? ''));
            if ($customerName === '' && is_resource($conn) && (int)($req['customer_id'] ?? 0) > 0 && customer_core_table_exists($conn, 'erp_customers')) {
                $cRows = customer_core_fetch_rows(
                    $conn,
                    'SELECT TOP 1 full_name, primary_mobile FROM dbo.erp_customers WHERE customer_id = ?',
                    [(int)$req['customer_id']]
                );
                if (is_array($cRows[0] ?? null)) {
                    $customerName = trim((string)($cRows[0]['full_name'] ?? ''));
                    if ($data['mobile'] === '-' || $data['mobile'] === '') {
                        $data['mobile'] = trim((string)($cRows[0]['primary_mobile'] ?? '')) ?: $data['mobile'];
                    }
                }
            }
            if ($customerName !== '') {
                $data['customer_name'] = $customerName;
            }

            $mobile = trim((string)($req['mobile'] ?? $payload['mobile'] ?? $payload['normalized_mobile'] ?? ''));
            if ($mobile !== '') {
                $data['mobile'] = $mobile;
            }

            $brand = trim((string)($vehicle['brand'] ?? $payload['brand'] ?? $payload['vehicle_brand'] ?? ''));
            $model = trim((string)($vehicle['model'] ?? $vehicle['vehicle_class'] ?? $payload['model'] ?? $payload['vehicle_model'] ?? ''));
            $vehicleType = trim((string)($vehicle['vehicle_type'] ?? $payload['vehicle_type'] ?? ''));
            if (is_resource($conn) && (int)($req['vehicle_id'] ?? 0) > 0 && customer_core_table_exists($conn, 'erp_vehicles')) {
                $vRows = customer_core_fetch_rows(
                    $conn,
                    'SELECT TOP 1 brand, model, plate_number, vin, mileage FROM dbo.erp_vehicles WHERE vehicle_id = ?',
                    [(int)$req['vehicle_id']]
                );
                if (is_array($vRows[0] ?? null)) {
                    if ($brand === '') {
                        $brand = trim((string)($vRows[0]['brand'] ?? ''));
                    }
                    if ($model === '') {
                        $model = trim((string)($vRows[0]['model'] ?? ''));
                    }
                    if ($data['plate'] === '-' || $data['plate'] === '') {
                        $data['plate'] = trim((string)($vRows[0]['plate_number'] ?? '')) ?: $data['plate'];
                    }
                    if ($data['vin'] === '-' || $data['vin'] === '') {
                        $data['vin'] = trim((string)($vRows[0]['vin'] ?? '')) ?: $data['vin'];
                    }
                    if (($data['odometer'] === '-' || $data['odometer'] === '') && trim((string)($vRows[0]['mileage'] ?? '')) !== '') {
                        $data['odometer'] = trim((string)$vRows[0]['mileage']);
                    }
                }
            }
            if ($brand !== '' || $model !== '') {
                $data['vehicle'] = trim($brand . ' ' . $model);
                $data['brand'] = $brand;
                $data['model'] = $model;
                $data['vehicle_class'] = $model;
            }
            if ($vehicleType !== '') {
                $data['vehicle_type'] = $vehicleType;
            }
            $data['online_request_id'] = (string)$onlineRequestId;

            $plate = trim((string)($vehicle['plate'] ?? $vehicle['plate_number'] ?? $vehicle['plate_display'] ?? $req['vehicle_plate'] ?? $payload['plate_display'] ?? ''));
            if ($plate !== '') {
                $data['plate'] = $plate;
            }
            $vin = trim((string)($vehicle['vin'] ?? $payload['vin'] ?? ''));
            if ($vin !== '') {
                $data['vin'] = $vin;
            }
            $odometer = trim((string)($vehicle['odometer_km'] ?? $vehicle['mileage'] ?? $payload['odometer_km'] ?? $payload['mileage'] ?? ''));
            if ($odometer !== '') {
                $data['odometer'] = $odometer;
            }
            $fuel = trim((string)($vehicle['fuel_level'] ?? $payload['fuel_level'] ?? ''));
            if ($fuel !== '') {
                $data['fuel_level'] = $fuel;
            }

            $requestType = trim((string)($req['request_type'] ?? $payload['request_type'] ?? $service['request_type'] ?? ''));
            $route = trim((string)($service['route'] ?? $service['main'] ?? $payload['service_route'] ?? ''));
            $data['service_type'] = m360_intake_contract_service_type_fa($requestType, $route);

            $desc = trim((string)($service['description'] ?? $service['customer_complaint'] ?? $payload['request_description'] ?? $payload['service_description'] ?? ''));
            if ($desc !== '') {
                $data['request_description'] = $desc;
            }

            $cost = trim((string)($docs['cost_agreement'] ?? $payload['cost_agreement'] ?? ''));
            if ($cost !== '') {
                $data['cost_range'] = $cost;
            }
            $costNote = trim((string)($docs['cost_agreement_note'] ?? $payload['cost_agreement_note'] ?? ''));
            if ($costNote !== '') {
                $data['prepayment'] = $costNote;
            }

            $agreements = is_array($ri['agreements'] ?? null) ? $ri['agreements'] : [];
            if ($agreements !== []) {
                $thirdFa = trim((string)($agreements['third_party_insurance_fa'] ?? ''));
                if ($thirdFa === '' || $thirdFa === '—') {
                    $thirdFa = function_exists('m360_contract_agreement_yes_no_display')
                        ? m360_contract_agreement_yes_no_display((string)($agreements['third_party_insurance'] ?? ''))
                        : (function_exists('m360_rw_intake_agreement_yes_no_fa')
                            ? m360_rw_intake_agreement_yes_no_fa((string)($agreements['third_party_insurance'] ?? ''))
                            : '');
                }
                $bodyFa = trim((string)($agreements['body_insurance_fa'] ?? ''));
                if ($bodyFa === '' || $bodyFa === '—') {
                    $bodyFa = function_exists('m360_contract_agreement_yes_no_display')
                        ? m360_contract_agreement_yes_no_display((string)($agreements['body_insurance'] ?? ''))
                        : (function_exists('m360_rw_intake_agreement_yes_no_fa')
                            ? m360_rw_intake_agreement_yes_no_fa((string)($agreements['body_insurance'] ?? ''))
                            : '');
                }
                $testFa = trim((string)($agreements['test_drive_permission_fa'] ?? ''));
                if ($testFa === '' || $testFa === '—') {
                    $testFa = function_exists('m360_contract_agreement_yes_no_display')
                        ? m360_contract_agreement_yes_no_display((string)($agreements['test_drive_permission'] ?? ''))
                        : (function_exists('m360_rw_intake_agreement_yes_no_fa')
                            ? m360_rw_intake_agreement_yes_no_fa((string)($agreements['test_drive_permission'] ?? ''))
                            : '');
                }
                $purchaseFa = trim((string)($agreements['part_purchase_authorization_fa'] ?? ''));
                if ($purchaseFa === '' || $purchaseFa === '—') {
                    $purchaseFa = function_exists('m360_contract_part_purchase_display')
                        ? m360_contract_part_purchase_display((string)($agreements['part_purchase_authorization'] ?? ''))
                        : (function_exists('m360_rw_intake_part_purchase_authorization_fa')
                            ? m360_rw_intake_part_purchase_authorization_fa((string)($agreements['part_purchase_authorization'] ?? ''))
                            : '');
                }

                // Always map min/max independently of range FA (comma-safe digit normalize).
                $min = function_exists('m360_contract_normalize_money_digits')
                    ? m360_contract_normalize_money_digits($agreements['service_cost_min'] ?? '')
                    : preg_replace('/[^\d]/', '', (string)($agreements['service_cost_min'] ?? '')) ?? '';
                $max = function_exists('m360_contract_normalize_money_digits')
                    ? m360_contract_normalize_money_digits($agreements['service_cost_max'] ?? '')
                    : preg_replace('/[^\d]/', '', (string)($agreements['service_cost_max'] ?? '')) ?? '';
                if ($min !== '') {
                    $data['service_cost_min'] = $min;
                }
                if ($max !== '') {
                    $data['service_cost_max'] = $max;
                }

                $rangeFa = trim((string)($agreements['service_cost_range_fa'] ?? ''));
                if ($rangeFa === '' && $min !== '' && $max !== '') {
                    $rangeFa = 'از ' . m360_format_number((int)$min) . ' تا ' . m360_format_number((int)$max) . ' ریال';
                }
                // Legacy: parse «از X تا Y» from cost_agreement / range text when min/max keys missing.
                if (($min === '' || $max === '') && function_exists('m360_contract_parse_cost_range_bounds')) {
                    $parsed = m360_contract_parse_cost_range_bounds($rangeFa !== '' ? $rangeFa : (string)($data['cost_range'] ?? ''));
                    if ($min === '' && $parsed['min'] !== '') {
                        $min = $parsed['min'];
                        $data['service_cost_min'] = $min;
                    }
                    if ($max === '' && $parsed['max'] !== '') {
                        $max = $parsed['max'];
                        $data['service_cost_max'] = $max;
                    }
                }
                $otherNote = trim((string)($agreements['other_agreements_note'] ?? ''));

                $data['third_party_insurance'] = function_exists('m360_contract_normalize_agreement_display')
                    ? m360_contract_normalize_agreement_display($thirdFa)
                    : (($thirdFa !== '' && $thirdFa !== '—') ? $thirdFa : '');
                $data['body_insurance_status'] = function_exists('m360_contract_normalize_agreement_display')
                    ? m360_contract_normalize_agreement_display($bodyFa)
                    : (($bodyFa !== '' && $bodyFa !== '—') ? $bodyFa : '');
                $data['test_drive_allowed'] = function_exists('m360_contract_normalize_agreement_display')
                    ? m360_contract_normalize_agreement_display($testFa)
                    : (($testFa !== '' && $testFa !== '—') ? $testFa : '');
                $data['purchase_limit'] = function_exists('m360_contract_normalize_agreement_display')
                    ? m360_contract_normalize_agreement_display($purchaseFa)
                    : (($purchaseFa !== '' && $purchaseFa !== '—') ? $purchaseFa : '');
                if ($rangeFa !== '' && !(function_exists('m360_contract_is_generic_placeholder') && m360_contract_is_generic_placeholder($rangeFa))) {
                    $data['cost_range'] = $rangeFa;
                }
                $data['other_agreements_note'] = $otherNote;
            } elseif (function_exists('m360_contract_parse_cost_range_bounds')) {
                // No agreements object yet: still try to recover min/max from legacy cost_agreement text.
                $parsed = m360_contract_parse_cost_range_bounds((string)($data['cost_range'] ?? ''));
                if ($parsed['min'] !== '') {
                    $data['service_cost_min'] = $parsed['min'];
                }
                if ($parsed['max'] !== '') {
                    $data['service_cost_max'] = $parsed['max'];
                }
            }

            if (isset($payload['estimated_cost_range'])) {
                $est = trim((string)$payload['estimated_cost_range']);
                if ($est !== '' && !(function_exists('m360_contract_is_generic_placeholder') && m360_contract_is_generic_placeholder($est))) {
                    $data['cost_range'] = $est;
                }
            }
            if (isset($payload['prepayment_amount'])) {
                $data['prepayment'] = (string)$payload['prepayment_amount'];
            }
            if (isset($payload['purchase_limit']) && trim((string)($data['purchase_limit'] ?? '')) === '') {
                $purchaseRaw = trim((string)$payload['purchase_limit']);
                $data['purchase_limit'] = function_exists('m360_contract_part_purchase_display')
                    ? m360_contract_part_purchase_display($purchaseRaw)
                    : (function_exists('m360_rw_intake_part_purchase_authorization_fa')
                        ? m360_rw_intake_part_purchase_authorization_fa($purchaseRaw)
                        : $purchaseRaw);
            }
            if (isset($payload['test_drive_allowed']) && trim((string)($data['test_drive_allowed'] ?? '')) === '') {
                $data['test_drive_allowed'] = function_exists('m360_contract_agreement_yes_no_display')
                    ? m360_contract_agreement_yes_no_display((string)$payload['test_drive_allowed'])
                    : (string)$payload['test_drive_allowed'];
            }
            if (isset($payload['body_insurance_status']) && trim((string)($data['body_insurance_status'] ?? '')) === '') {
                $data['body_insurance_status'] = function_exists('m360_contract_agreement_yes_no_display')
                    ? m360_contract_agreement_yes_no_display((string)$payload['body_insurance_status'])
                    : (string)$payload['body_insurance_status'];
            }

            // Final sanitize: never leave generic placeholders in snapshot agreement fields.
            foreach (['third_party_insurance', 'body_insurance_status', 'test_drive_allowed', 'purchase_limit', 'other_agreements_note', 'cost_range', 'prepayment', 'checklist_summary'] as $agreeKey) {
                if (function_exists('m360_contract_normalize_agreement_display')) {
                    $data[$agreeKey] = m360_contract_normalize_agreement_display($data[$agreeKey] ?? '');
                }
            }

            $trunk = trim((string)($condition['trunk_belongings_note'] ?? ''));
            $damage = trim((string)($condition['damage_zones_note'] ?? ''));
            $checklist = trim($trunk . ($trunk !== '' && $damage !== '' ? ' | ' : '') . $damage);
            if ($checklist !== '') {
                $data['checklist_summary'] = $checklist;
            }

            $visit = trim((string)($req['visit_date'] ?? $vehicle['visit_date'] ?? $payload['visit_date'] ?? ''));
            if ($visit !== '') {
                $data['visit_date'] = $visit;
                $data['reception_date'] = substr($visit, 0, 10);
            }
            $data['online_request_id'] = (string)$onlineRequestId;
        }
    }

    return $data;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_fetch_by_id($conn, int $contractId): ?array
{
    if (!is_resource($conn) || $contractId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 * FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE contract_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    return $normalized;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_fetch_by_token_hash($conn, string $tokenHash): ?array
{
    if (!is_resource($conn) || $tokenHash === '') {
        return null;
    }
    $sql = 'SELECT TOP 1 * FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE secure_token_hash = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$tokenHash])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    return $normalized;
}

function m360_intake_contract_token_valid(array $contractRow): bool
{
    if ((string)($contractRow['contract_status'] ?? '') === M360_CONTRACT_STATUS_CANCELLED) {
        return false;
    }
    if ((string)($contractRow['contract_status'] ?? '') === M360_CONTRACT_STATUS_EXPIRED) {
        return false;
    }
    $expires = trim((string)($contractRow['secure_token_expires_at'] ?? ''));
    if ($expires === '') {
        return true;
    }
    return strtotime($expires) >= time();
}

function m360_intake_contract_is_signed(array $contractRow): bool
{
    $status = strtoupper((string)($contractRow['contract_status'] ?? ''));
    return $status === M360_CONTRACT_STATUS_SIGNED || $status === M360_CONTRACT_STATUS_OVERRIDDEN;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_find_active_for_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $sql = "SELECT TOP 1 * FROM dbo." . M360_CONTRACT_TABLE . "
            WHERE jobcard_id = ? AND contract_status NOT IN (N'CANCELLED', N'EXPIRED')
            ORDER BY contract_id DESC";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    if (m360_intake_contract_is_signed($normalized)) {
        return $normalized;
    }
    if (in_array(strtoupper((string)$normalized['contract_status']), [M360_CONTRACT_STATUS_GENERATED, M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_VIEWED, M360_CONTRACT_STATUS_OTP_SENT], true)) {
        return $normalized;
    }
    return null;
}

/** @return array<string, mixed>|null */
function m360_intake_contract_find_active_for_online_request($conn, int $onlineRequestId): ?array
{
    if (!is_resource($conn) || $onlineRequestId < 1) {
        return null;
    }
    $sql = "SELECT TOP 1 * FROM dbo." . M360_CONTRACT_TABLE . "
            WHERE online_request_id = ? AND contract_status NOT IN (N'CANCELLED', N'EXPIRED')
            ORDER BY contract_id DESC";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$onlineRequestId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    if (m360_intake_contract_is_signed($normalized)) {
        return $normalized;
    }
    if (in_array(strtoupper((string)$normalized['contract_status']), [M360_CONTRACT_STATUS_GENERATED, M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_VIEWED, M360_CONTRACT_STATUS_OTP_SENT], true)) {
        return $normalized;
    }

    return null;
}

/**
 * @param array<string, mixed> $snapshotData
 * @return array{ok:bool,message:string,contract_id:?int,reused:bool}
 */
function m360_intake_contract_generate_for_online_request(
    $conn,
    int $onlineRequestId,
    string $rawToken,
    string $tokenExpiresAt,
    string $mobile,
    ?int $customerId,
    ?int $vehicleId,
    array $snapshotData
): array {
    if (!is_resource($conn) || $onlineRequestId < 1 || trim($rawToken) === '') {
        return ['ok' => false, 'message' => 'اطلاعات قرارداد معتبر نیست.', 'contract_id' => null, 'reused' => false];
    }

    $existing = m360_intake_contract_find_active_for_online_request($conn, $onlineRequestId);
    if ($existing !== null) {
        if (m360_intake_contract_is_signed($existing)) {
            return ['ok' => false, 'message' => 'قرارداد این درخواست قبلاً امضا شده است.', 'contract_id' => (int)$existing['contract_id'], 'reused' => true];
        }

        // Revision/resend: refresh unsigned contract snapshot + token without new schema/version table.
        $contractId = (int)($existing['contract_id'] ?? 0);
        $priorSnapshots = [];
        $oldJsonRaw = trim((string)($existing['contract_data_json'] ?? ''));
        if ($oldJsonRaw !== '') {
            $oldDecoded = json_decode($oldJsonRaw, true);
            if (is_array($oldDecoded)) {
                $priorSnapshots = is_array($oldDecoded['prior_snapshots'] ?? null) ? $oldDecoded['prior_snapshots'] : [];
                $priorSnapshots[] = [
                    'snapshot_hash' => (string)($existing['contract_body_hash'] ?? $oldDecoded['contract_hash'] ?? ''),
                    'contract_version' => defined('M360_CONTRACT_VERSION') ? M360_CONTRACT_VERSION : 'MOGHARE360-INTAKE-V1',
                    'service_cost_min' => (string)($oldDecoded['service_cost_min'] ?? ''),
                    'service_cost_max' => (string)($oldDecoded['service_cost_max'] ?? ''),
                    'cost_range' => (string)($oldDecoded['cost_range'] ?? ''),
                    'superseded_at' => gmdate('c'),
                    'status' => 'SUPERSEDED',
                    'source' => 'CONTRACT_SNAPSHOT',
                    'pdf_type' => 'none',
                ];
                if (count($priorSnapshots) > 20) {
                    $priorSnapshots = array_slice($priorSnapshots, -20);
                }
            }
        }
        $snapshot = m360_intake_contract_build_snapshot($conn, null, $onlineRequestId);
        // Overlay only non-blank bootstrap fields so empty dash placeholders cannot wipe agreements.
        foreach ($snapshotData as $k => $v) {
            if (is_string($v) || is_int($v) || is_float($v)) {
                $blank = function_exists('m360_contract_is_blank_display')
                    ? m360_contract_is_blank_display($v)
                    : (trim((string)$v) === '' || trim((string)$v) === '-' || trim((string)$v) === '—');
                if ($blank) {
                    continue;
                }
            }
            $snapshot[$k] = $v;
        }
        $snapshot['online_request_id'] = (string)$onlineRequestId;
        $snapshot['revision_at'] = gmdate('c');
        $snapshot['revision_note'] = 'قرارداد برای ارسال مجدد/اصلاح به‌روزرسانی شد.';
        $snapshot['prior_snapshots'] = $priorSnapshots;
        $html = m360_contract_render_html($snapshot, true);
        $bodyHash = m360_intake_contract_hash($html);
        $snapshot['contract_hash'] = $bodyHash;
        $snapshot['workflow'] = [
            'review_completed_at' => '',
            'consent_at' => '',
            'consent_text' => '',
            'signature_draft_hash' => '',
            'signature_draft_at' => '',
            'superseded_prior_customer_approval' => true,
        ];
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }
        $tokenHash = m360_intake_contract_hash(trim($rawToken));
        $expires = trim($tokenExpiresAt) !== '' ? $tokenExpiresAt : gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS);
        $mobileNorm = trim($mobile);
        if ($mobileNorm === '' || $mobileNorm === '-') {
            $mobileNorm = trim((string)($existing['mobile'] ?? ''));
        }
        $ok = customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . '
             SET contract_status = ?,
                 contract_body_hash = ?,
                 contract_data_json = ?,
                 secure_token_hash = ?,
                 secure_token_expires_at = ?,
                 mobile = COALESCE(NULLIF(?, N\'\'), mobile),
                 viewed_at = NULL,
                 updated_at = SYSUTCDATETIME()
             WHERE contract_id = ? AND contract_status NOT IN (?, ?)',
            [
                M360_CONTRACT_STATUS_SENT,
                $bodyHash,
                $json,
                $tokenHash,
                $expires,
                $mobileNorm,
                $contractId,
                M360_CONTRACT_STATUS_SIGNED,
                M360_CONTRACT_STATUS_OVERRIDDEN,
            ]
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'به‌روزرسانی قرارداد برای ارسال مجدد ناموفق بود.', 'contract_id' => $contractId, 'reused' => true];
        }
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_REVISED_FOR_RESEND', 'snapshot_refreshed', null);

        return ['ok' => true, 'message' => 'قرارداد برای ارسال مجدد به‌روزرسانی شد.', 'contract_id' => $contractId, 'reused' => true];
    }

    $snapshot = m360_intake_contract_build_snapshot($conn, null, $onlineRequestId);
    foreach ($snapshotData as $k => $v) {
        if (is_string($v) || is_int($v) || is_float($v)) {
            $blank = function_exists('m360_contract_is_blank_display')
                ? m360_contract_is_blank_display($v)
                : (trim((string)$v) === '' || trim((string)$v) === '-' || trim((string)$v) === '—');
            if ($blank) {
                continue;
            }
        }
        $snapshot[$k] = $v;
    }
    $snapshot['online_request_id'] = (string)$onlineRequestId;
    $html = m360_contract_render_html($snapshot, true);
    $bodyHash = m360_intake_contract_hash($html);
    $snapshot['contract_hash'] = $bodyHash;
    $snapshot['workflow'] = [
        'review_completed_at' => '',
        'consent_at' => '',
        'consent_text' => '',
        'signature_draft_hash' => '',
        'signature_draft_at' => '',
    ];
    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{}';
    }

    $tokenHash = m360_intake_contract_hash(trim($rawToken));
    $expires = trim($tokenExpiresAt) !== '' ? $tokenExpiresAt : gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS);
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $mobile = trim($mobile);
    if ($mobile === '' || $mobile === '-') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری برای قرارداد یافت نشد.', 'contract_id' => null, 'reused' => false];
    }

    $statement = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_TABLE . ' (
            contract_version, online_request_id, jobcard_id, customer_id, vehicle_id, mobile,
            contract_status, contract_title, contract_body_hash, contract_data_json,
            secure_token_hash, secure_token_expires_at, created_by_user_id
        ) OUTPUT INSERTED.contract_id
          VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            M360_CONTRACT_VERSION,
            $onlineRequestId,
            $customerId !== null && $customerId > 0 ? $customerId : null,
            $vehicleId !== null && $vehicleId > 0 ? $vehicleId : null,
            $mobile,
            M360_CONTRACT_STATUS_SENT,
            M360_CONTRACT_TITLE,
            $bodyHash,
            $json,
            $tokenHash,
            $expires,
            $userId,
        ]
    );

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return ['ok' => false, 'message' => 'ثبت قرارداد ناموفق بود.', 'contract_id' => null, 'reused' => false];
    }

    $newContractId = @odbc_result($statement, 1);
    $contractId = $newContractId === false || $newContractId === null ? 0 : (int)$newContractId;
    if ($contractId > 0) {
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_ISSUED', 'online_request #' . $onlineRequestId, $userId);
        m360_intake_contract_record_event($conn, $contractId, 'CUSTOMER_SIGNATURE_TASK_CREATED', null, $userId);
    }

    if ($contractId > 0 && function_exists('m360_rw_intake_ensure_canonical_contract_cartable_task')) {
        $requestRow = function_exists('m360_online_req_fetch_by_id')
            ? m360_online_req_fetch_by_id($conn, $onlineRequestId)
            : null;
        $payload = [];
        if (is_array($requestRow)) {
            $payload = function_exists('m360_online_req_parse_payload')
                ? m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null)
                : [];
            if (function_exists('m360_rw_intake_payload_for_recovery')) {
                $payload = m360_rw_intake_payload_for_recovery($payload);
            }
        }
        m360_rw_intake_ensure_canonical_contract_cartable_task(
            $conn,
            $onlineRequestId,
            is_array($requestRow) ? $requestRow : ['customer_id' => $customerId, 'mobile' => $mobile],
            $payload,
            $contractId,
            $tokenHash,
            $expires,
            'STAFF',
            (string)$userId
        );
    }

    return ['ok' => true, 'message' => 'قرارداد پذیرش تولید شد.', 'contract_id' => $contractId, 'reused' => false];
}

/** @return array<string, mixed> */
function m360_intake_contract_get_workflow_meta(array $contractRow): array
{
    $json = trim((string)($contractRow['contract_data_json'] ?? ''));
    if ($json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $workflow = $decoded['workflow'] ?? [];

    return is_array($workflow) ? $workflow : [];
}

/**
 * @param array<string, mixed> $patch
 * @return array{ok:bool,message:string}
 */
function m360_intake_contract_patch_workflow_meta($conn, int $contractId, array $patch): array
{
    if (!is_resource($conn) || $contractId < 1) {
        return ['ok' => false, 'message' => 'قرارداد معتبر نیست.'];
    }
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (m360_intake_contract_is_signed($row)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }

    $json = trim((string)($row['contract_data_json'] ?? ''));
    $data = $json !== '' ? json_decode($json, true) : [];
    if (!is_array($data)) {
        $data = [];
    }
    $workflow = is_array($data['workflow'] ?? null) ? $data['workflow'] : [];
    $data['workflow'] = array_merge($workflow, $patch);
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'خطا در ذخیره وضعیت قرارداد.'];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_data_json = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [$encoded, $contractId]
    );

    return ['ok' => $ok !== false, 'message' => $ok !== false ? '' : 'ذخیره وضعیت قرارداد ناموفق بود.'];
}

/**
 * @return array<string, string>
 */
function m360_intake_contract_pre_signature_workflow_reset_patch(): array
{
    return [
        'review_completed_at' => '',
        'consent_at' => '',
        'consent_text' => '',
        'signature_draft_hash' => '',
        'signature_draft_at' => '',
        'signature_confirmed_at' => '',
        'signature_confirmed_hash' => '',
        'signature_contract_body_hash' => '',
        'signature_contract_version' => '',
    ];
}

function m360_intake_contract_count_signature_rows($conn, int $contractId): int
{
    if (!is_resource($conn) || $contractId < 1 || !m360_intake_contract_table_exists($conn, M360_CONTRACT_SIG_TABLE)) {
        return 0;
    }
    $sql = 'SELECT COUNT(1) AS cnt FROM dbo.' . M360_CONTRACT_SIG_TABLE . ' WHERE contract_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return 0;
    }
    $row = odbc_fetch_array($stmt);
    if (!is_array($row)) {
        return 0;
    }

    return (int)($row['cnt'] ?? 0);
}

/**
 * Controlled reset of reversible pre-signature workflow state for UAT recovery.
 *
 * @return array{ok:bool,message:string,before?:array<string,mixed>,after?:array<string,mixed>,event_recorded?:bool}
 */
function m360_intake_contract_reset_pre_signature_uat_state(
    $conn,
    int $contractId,
    string $reason = 'owner_clean_end_to_end_signature_uat',
    string $phase = 'WAVE_1C_B2_12'
): array {
    if (!is_resource($conn) || $contractId < 1) {
        return ['ok' => false, 'message' => 'شناسه قرارداد معتبر نیست.'];
    }

    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (m360_intake_contract_is_signed($row)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً امضا شده و قابل بازنشانی UAT نیست.'];
    }
    if (trim((string)($row['signed_at'] ?? '')) !== '') {
        return ['ok' => false, 'message' => 'signed_at برای این قرارداد مقدار دارد.'];
    }
    if (m360_intake_contract_count_signature_rows($conn, $contractId) > 0) {
        return ['ok' => false, 'message' => 'رد امضای نهایی برای این قرارداد وجود دارد.'];
    }

    $status = strtoupper((string)($row['contract_status'] ?? ''));
    $allowedStatuses = [
        M360_CONTRACT_STATUS_SENT,
        M360_CONTRACT_STATUS_VIEWED,
        M360_CONTRACT_STATUS_GENERATED,
        M360_CONTRACT_STATUS_OTP_SENT,
    ];
    if (!in_array($status, $allowedStatuses, true)) {
        return ['ok' => false, 'message' => 'وضعیت قرارداد برای بازنشانی UAT مجاز نیست: ' . $status];
    }

    $beforeWorkflow = m360_intake_contract_get_workflow_meta($row);
    $before = [
        'contract_status' => $status,
        'review_completed_at' => (string)($beforeWorkflow['review_completed_at'] ?? ''),
        'consent_at' => (string)($beforeWorkflow['consent_at'] ?? ''),
        'signature_confirmed_at' => (string)($beforeWorkflow['signature_confirmed_at'] ?? ''),
        'signature_confirmed_hash' => (string)($beforeWorkflow['signature_confirmed_hash'] ?? ''),
    ];

    $patchResult = m360_intake_contract_patch_workflow_meta($conn, $contractId, m360_intake_contract_pre_signature_workflow_reset_patch());
    if (!$patchResult['ok']) {
        return $patchResult;
    }

    if ($status !== M360_CONTRACT_STATUS_SENT) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status <> ?',
            [M360_CONTRACT_STATUS_SENT, $contractId, M360_CONTRACT_STATUS_SIGNED]
        );
    }

    $metadata = json_encode([
        'reason' => $reason,
        'scope' => 'pre_signature_reversible_state',
        'phase' => $phase,
    ], JSON_UNESCAPED_UNICODE);
    if ($metadata === false) {
        $metadata = '{"reason":"owner_clean_end_to_end_signature_uat","scope":"pre_signature_reversible_state","phase":"WAVE_1C_B2_12"}';
    }
    m360_intake_contract_record_event($conn, $contractId, 'UAT_STATE_RESET', $metadata, null);

    $afterRow = m360_intake_contract_fetch_by_id($conn, $contractId) ?? $row;
    $afterWorkflow = m360_intake_contract_get_workflow_meta($afterRow);
    $after = [
        'contract_status' => strtoupper((string)($afterRow['contract_status'] ?? '')),
        'review_completed_at' => (string)($afterWorkflow['review_completed_at'] ?? ''),
        'consent_at' => (string)($afterWorkflow['consent_at'] ?? ''),
        'signature_confirmed_at' => (string)($afterWorkflow['signature_confirmed_at'] ?? ''),
        'signature_confirmed_hash' => (string)($afterWorkflow['signature_confirmed_hash'] ?? ''),
    ];

    return [
        'ok' => true,
        'message' => 'وضعیت UAT پیش از امضا بازنشانی شد.',
        'before' => $before,
        'after' => $after,
        'event_recorded' => true,
    ];
}

function m360_intake_contract_record_event(
    $conn,
    int $contractId,
    string $eventName,
    ?string $note = null,
    ?int $userId = null
): void {
    if (!m360_intake_contract_table_exists($conn, M360_CONTRACT_EVT_TABLE) || $contractId < 1) {
        return;
    }
    $ip = customer_core_client_ip();
    $ua = customer_core_user_agent();
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_EVT_TABLE . ' (contract_id, event_name, event_note, event_ip, event_user_agent, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$contractId, $eventName, $note, $ip, $ua, $userId]
    );
}

/**
 * @return array{ok:bool,message:string,contract_id:?int,raw_token:?string,reused:bool}
 */
function m360_intake_contract_generate_for_jobcard(int $jobcardId, ?int $onlineRequestId = null): array
{
    $conn = customer_core_db();
    if ($conn === false || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'شناسه کارت کار معتبر نیست.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $existing = m360_intake_contract_find_active_for_jobcard($conn, $jobcardId);
    if ($existing !== null) {
        if (m360_intake_contract_is_signed($existing)) {
            return ['ok' => false, 'message' => 'قرارداد این پرونده قبلاً امضا شده است.', 'contract_id' => (int)$existing['contract_id'], 'raw_token' => null, 'reused' => true];
        }
        return ['ok' => true, 'message' => 'قرارداد فعال موجود استفاده شد.', 'contract_id' => (int)$existing['contract_id'], 'raw_token' => null, 'reused' => true];
    }

    $snapshot = m360_intake_contract_build_snapshot($conn, $jobcardId, $onlineRequestId);
    $html = m360_contract_render_html($snapshot, true);
    $bodyHash = m360_intake_contract_hash($html);
    $snapshot['contract_hash'] = $bodyHash;
    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{}';
    }

    $token = m360_intake_contract_generate_token();
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $mobile = trim((string)($snapshot['mobile'] ?? ''));
    if ($mobile === '' || $mobile === '-') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری برای قرارداد یافت نشد.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $statement = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_TABLE . ' (
            contract_version, online_request_id, jobcard_id, customer_id, vehicle_id, mobile,
            contract_status, contract_title, contract_body_hash, contract_data_json,
            secure_token_hash, secure_token_expires_at, created_by_user_id
        ) OUTPUT INSERTED.contract_id
          VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            M360_CONTRACT_VERSION,
            $onlineRequestId,
            $jobcardId,
            $mobile,
            M360_CONTRACT_STATUS_GENERATED,
            M360_CONTRACT_TITLE,
            $bodyHash,
            $json,
            $token['hash'],
            $token['expires_at'],
            $userId,
        ]
    );

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return ['ok' => false, 'message' => 'ثبت قرارداد ناموفق بود.', 'contract_id' => null, 'raw_token' => null, 'reused' => false];
    }

    $newContractId = @odbc_result($statement, 1);
    $contractId = $newContractId === false || $newContractId === null ? 0 : (int)$newContractId;
    if ($contractId > 0) {
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_GENERATED', 'JobCard #' . $jobcardId, $userId);
    }

    return ['ok' => true, 'message' => 'قرارداد پذیرش تولید شد.', 'contract_id' => $contractId, 'raw_token' => $token['raw'], 'reused' => false];
}

/** @return array{ok:bool,message:string} */
function m360_intake_contract_mark_sent($conn, int $contractId): array
{
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.'];
    }
    if (m360_intake_contract_is_signed($row)) {
        return ['ok' => false, 'message' => 'قرارداد امضا شده قابل ارسال مجدد نیست.'];
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, sent_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_SENT, $contractId]
    );
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_SENT', null, erp_auth_current_user_id());
    return ['ok' => true, 'message' => 'وضعیت ارسال ثبت شد.'];
}

function m360_intake_contract_mark_viewed($conn, int $contractId): void
{
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row === null || m360_intake_contract_is_signed($row)) {
        return;
    }
    $status = strtoupper((string)$row['contract_status']);
    if ($status === M360_CONTRACT_STATUS_SENT || $status === M360_CONTRACT_STATUS_GENERATED) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, viewed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
            [M360_CONTRACT_STATUS_VIEWED, $contractId]
        );
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_VIEWED', null, null);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function m360_intake_contract_list($conn, ?string $statusFilter = null, int $limit = 100): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $params = [];
    $where = '1=1';
    if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'ALL') {
        $where .= ' AND c.contract_status = ?';
        $params[] = strtoupper($statusFilter);
    }
    $sql = 'SELECT TOP ' . $limit . ' c.*, j.jobcard_number
            FROM dbo.' . M360_CONTRACT_TABLE . ' c
            LEFT JOIN dbo.erp_jobcards j ON j.jobcard_id = c.jobcard_id
            WHERE ' . $where . ' ORDER BY c.contract_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $normalized;
    }
    return $rows;
}

/** @return list<array<string, mixed>> */
function m360_intake_contract_events($conn, int $contractId): array
{
    if (!m360_intake_contract_table_exists($conn, M360_CONTRACT_EVT_TABLE) || $contractId < 1) {
        return [];
    }
    $stmt = @odbc_prepare($conn, 'SELECT * FROM dbo.' . M360_CONTRACT_EVT_TABLE . ' WHERE contract_id = ? ORDER BY event_id DESC');
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $normalized;
    }
    return $rows;
}

function m360_contract_required_for_jobcard(int $jobcardId): bool
{
    return $jobcardId > 0;
}

function m360_contract_signed_for_jobcard(int $jobcardId): bool
{
    $conn = customer_core_db();
    if ($conn === false || $jobcardId < 1) {
        return false;
    }
    $sql = "SELECT TOP 1 contract_status, manager_override FROM dbo." . M360_CONTRACT_TABLE . "
            WHERE jobcard_id = ? ORDER BY contract_id DESC";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return false;
    }
    $status = strtoupper((string)($row['contract_status'] ?? ''));
    if ($status === M360_CONTRACT_STATUS_SIGNED) {
        return true;
    }
    if ($status === M360_CONTRACT_STATUS_OVERRIDDEN && (int)($row['manager_override'] ?? 0) === 1) {
        return true;
    }
    return false;
}

function m360_contract_can_continue_to_p2(int $jobcardId): bool
{
    if (!m360_contract_required_for_jobcard($jobcardId)) {
        return false;
    }
    return m360_contract_signed_for_jobcard($jobcardId);
}

function m360_contract_generate_for_jobcard(int $jobcardId): array
{
    return m360_intake_contract_generate_for_jobcard($jobcardId);
}

function m360_contract_record_event($conn, int $contractId, string $eventName, ?string $note = null, ?int $userId = null): void
{
    m360_intake_contract_record_event($conn, $contractId, $eventName, $note, $userId);
}

/** @return array{ok:bool,message:string} */
function m360_intake_contract_apply_manager_override($conn, int $contractId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل تأیید مدیریتی الزامی است.'];
    }
    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, manager_override = 1, manager_override_reason = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_OVERRIDDEN, $reason, $contractId]
    );
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_MANAGER_OVERRIDE', $reason, $userId);
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    $jobcardId = (int)($row['jobcard_id'] ?? 0);
    if ($jobcardId > 0 && customer_core_column_exists($conn, 'erp_jobcards', 'contract_status')) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET contract_status = ?, intake_contract_id = ?, contract_signed_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
            ['OVERRIDDEN', $contractId, $jobcardId]
        );
    }
    return ['ok' => true, 'message' => 'تأیید مدیریتی ثبت شد.'];
}

function m360_intake_contract_snapshot_from_row(array $row): array
{
    $live = m360_intake_contract_build_snapshot(customer_core_db(), (int)($row['jobcard_id'] ?? 0), (int)($row['online_request_id'] ?? 0) ?: null);
    $data = $live;
    $json = trim((string)($row['contract_data_json'] ?? ''));
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $data = array_merge($live, $decoded);
            // Unsigned: do not let frozen dash placeholders hide live agreements min/max.
            if (!m360_intake_contract_is_signed($row)) {
                $preferLiveKeys = [
                    'service_cost_min',
                    'service_cost_max',
                    'cost_range',
                    'third_party_insurance',
                    'body_insurance_status',
                    'test_drive_allowed',
                    'purchase_limit',
                    'other_agreements_note',
                ];
                foreach ($preferLiveKeys as $key) {
                    $frozen = $decoded[$key] ?? null;
                    $fromLive = $live[$key] ?? null;
                    $frozenBlank = function_exists('m360_contract_is_blank_display')
                        ? m360_contract_is_blank_display($frozen)
                        : (trim((string)$frozen) === '' || trim((string)$frozen) === '-' || trim((string)$frozen) === '—');
                    $liveBlank = function_exists('m360_contract_is_blank_display')
                        ? m360_contract_is_blank_display($fromLive)
                        : (trim((string)$fromLive) === '' || trim((string)$fromLive) === '-' || trim((string)$fromLive) === '—');
                    if ($frozenBlank && !$liveBlank) {
                        $data[$key] = $fromLive;
                    }
                }
            }
        }
    }
    $data['contract_hash'] = (string)($row['contract_body_hash'] ?? $data['contract_hash']);
    // Never leave generic intake placeholders in contract/PDF snapshot fields.
    foreach ([
        'third_party_insurance',
        'body_insurance_status',
        'test_drive_allowed',
        'purchase_limit',
        'other_agreements_note',
        'cost_range',
        'prepayment',
        'checklist_summary',
        'service_cost_min',
        'service_cost_max',
    ] as $agreeKey) {
        if (function_exists('m360_contract_normalize_agreement_display')) {
            $data[$agreeKey] = m360_contract_normalize_agreement_display($data[$agreeKey] ?? '');
        }
    }
    return $data;
}

/**
 * Resolve Composer autoload for mPDF without exposing absolute paths to callers.
 */
function m360_contract_pdf_autoload(): bool
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }
    $candidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
        'C:\\xampp\\htdocs\\moghare360\\vendor\\autoload.php',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            require_once $path;
            $loaded = class_exists('\\Mpdf\\Mpdf');
            return $loaded;
        }
    }
    $loaded = false;

    return false;
}

function m360_contract_pdf_engine_ready(): bool
{
    return m360_contract_pdf_autoload();
}

/**
 * @return array{ok:bool,message:string,absolute:string,relative:string}
 */
function m360_contract_pdf_storage_paths(int $onlineRequestId, string $filename): array
{
    $onlineRequestId = max(1, $onlineRequestId);
    $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?? 'contract.pdf';
    $relativeDir = 'storage/contracts/' . $onlineRequestId . '/pdf';
    $absDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($absDir) && !@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        return ['ok' => false, 'message' => 'ایجاد مسیر ذخیره PDF ناموفق بود.', 'absolute' => '', 'relative' => ''];
    }
    $relative = $relativeDir . '/' . $filename;
    $absolute = $absDir . DIRECTORY_SEPARATOR . $filename;

    return ['ok' => true, 'message' => '', 'absolute' => $absolute, 'relative' => $relative];
}

/**
 * @param array<string, mixed> $contractRow
 */
function m360_contract_pdf_status_fa(array $contractRow, array $snapshot = []): string
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return 'تأییدشده';
    }
    $workflow = is_array($snapshot['workflow'] ?? null) ? $snapshot['workflow'] : m360_intake_contract_get_workflow_meta($contractRow);
    if (!empty($workflow['superseded_prior_customer_approval']) || trim((string)($workflow['customer_correction_note'] ?? '')) !== '') {
        return 'برگشت برای اصلاح';
    }
    $status = strtoupper(trim((string)($contractRow['contract_status'] ?? '')));
    if ($status === M360_CONTRACT_STATUS_VIEWED) {
        return 'مشاهده‌شده — در انتظار امضا';
    }
    if (in_array($status, [M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_OTP_SENT, M360_CONTRACT_STATUS_GENERATED], true)) {
        return 'ارسال‌شده — در انتظار امضا';
    }

    return 'پیش‌نویس';
}

/**
 * @param array<string, mixed> $contractRow
 */
function m360_contract_pdf_type_for_row(array $contractRow): string
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return 'signed';
    }
    $status = strtoupper(trim((string)($contractRow['contract_status'] ?? '')));
    if ($status === M360_CONTRACT_STATUS_DRAFT) {
        return 'draft';
    }
    if (in_array($status, [M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_VIEWED, M360_CONTRACT_STATUS_OTP_SENT, M360_CONTRACT_STATUS_GENERATED], true)) {
        return 'sent';
    }

    return 'revised';
}

/**
 * @param array<string, mixed> $dataJson
 * @return list<array<string, mixed>>
 */
function m360_contract_pdf_list_from_data(array $dataJson): array
{
    $list = is_array($dataJson['contract_pdfs'] ?? null) ? $dataJson['contract_pdfs'] : [];
    $out = [];
    foreach ($list as $item) {
        if (is_array($item)) {
            $out[] = $item;
        }
    }

    return $out;
}

/**
 * @param array<string, mixed> $contractRow
 * @return array{ok:bool,message:string,meta:?array<string,mixed>,bytes:string,created:bool}
 */
function m360_intake_contract_ensure_pdf(
    $conn,
    array $contractRow,
    string $actor = 'staff',
    ?int $generatedByUserId = null,
    bool $recordDownloadEvent = false
): array {
    if (!is_resource($conn)) {
        return ['ok' => false, 'message' => 'اتصال پایگاه داده برقرار نشد.', 'meta' => null, 'bytes' => '', 'created' => false];
    }
    if (!m360_contract_pdf_engine_ready()) {
        return ['ok' => false, 'message' => 'موتور PDF (mPDF) در این محیط در دسترس نیست.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    $contractId = (int)($contractRow['contract_id'] ?? 0);
    if ($contractId < 1) {
        return ['ok' => false, 'message' => 'قرارداد معتبر نیست.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
    $jsonRaw = trim((string)($contractRow['contract_data_json'] ?? ''));
    $dataJson = $jsonRaw !== '' ? json_decode($jsonRaw, true) : [];
    if (!is_array($dataJson)) {
        $dataJson = [];
    }
    // Keep prior_snapshots / workflow from stored JSON.
    if (isset($dataJson['prior_snapshots']) && is_array($dataJson['prior_snapshots'])) {
        $snapshot['prior_snapshots'] = $dataJson['prior_snapshots'];
    }
    if (isset($dataJson['workflow']) && is_array($dataJson['workflow'])) {
        $snapshot['workflow'] = $dataJson['workflow'];
    }

    $pdfType = m360_contract_pdf_type_for_row($contractRow);
    $snapshotHash = trim((string)($contractRow['contract_body_hash'] ?? $snapshot['contract_hash'] ?? ''));
    $pdfs = m360_contract_pdf_list_from_data($dataJson);
    $pdfFormatVersion = 'COMPLETE_V2';

    $generatedAt = gmdate('c');
    $statusFa = m360_contract_pdf_status_fa($contractRow, $snapshot);
    $acceptance = 'ثبت نشده';
    $signed = m360_intake_contract_is_signed($contractRow);
    $signedAt = trim((string)($contractRow['signed_at'] ?? ''));
    $method = 'OTP / امضای دیجیتال / ثبت سیستمی';
    if ($signed) {
        $acceptance = 'تأیید شده توسط مشتری'
            . ($signedAt !== '' ? (' — ' . $signedAt) : '');
    } elseif ($statusFa === 'ارسال‌شده' || $statusFa === 'مشاهده‌شده') {
        $acceptance = 'در انتظار تأیید و امضای مشتری';
    } elseif ($statusFa === 'برگشت برای اصلاح') {
        $acceptance = 'مشتری قرارداد را برای اصلاح برگرداند';
    }

    $pdfData = $snapshot;
    if ((int)($pdfData['online_request_id'] ?? 0) < 1 && (int)($contractRow['online_request_id'] ?? 0) > 0) {
        $pdfData['online_request_id'] = (string)(int)$contractRow['online_request_id'];
    }
    $pdfData['contract_title'] = M360_CONTRACT_TITLE;
    $pdfData['contract_version'] = defined('M360_CONTRACT_VERSION') ? M360_CONTRACT_VERSION : 'MOGHARE360-INTAKE-V1';
    $pdfData['contract_hash'] = $snapshotHash !== '' ? $snapshotHash : (string)($snapshot['contract_hash'] ?? '');
    $pdfData['pdf_status_fa'] = $statusFa;
    $pdfData['pdf_acceptance_info'] = $acceptance;
    $pdfData['pdf_customer_signed'] = $signed;
    $pdfData['pdf_customer_signed_at'] = $signedAt;
    $pdfData['pdf_acceptance_method'] = $method;
    $pdfData['documents_summary'] = (string)($snapshot['checklist_summary'] ?? '');

    // Attach real signature image when present in SQL / vault; never invent.
    $sigImage = '';
    $sigRows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 signature_image_data FROM dbo.' . M360_CONTRACT_SIG_TABLE . ' WHERE contract_id = ? ORDER BY signature_id DESC',
        [$contractId]
    );
    if (is_array($sigRows[0] ?? null)) {
        $sigImage = trim((string)($sigRows[0]['signature_image_data'] ?? ''));
    }
    if ($sigImage === '' && isset($dataJson['workflow']) && is_array($dataJson['workflow'])) {
        $sigImage = trim((string)($dataJson['workflow']['signature_image_data'] ?? ''));
    }
    if ($sigImage !== '' && !str_starts_with($sigImage, 'data:image/') && preg_match('/^[A-Za-z0-9+\/=]+$/', $sigImage) === 1 && strlen($sigImage) > 80) {
        $sigImage = 'data:image/png;base64,' . $sigImage;
    }
    if (($sigImage === '' || !str_starts_with($sigImage, 'data:image/')) && isset($dataJson['workflow']) && is_array($dataJson['workflow'])) {
        $sigVaultBlobId = (int)($dataJson['workflow']['signature_vault_blob_id'] ?? 0);
        if ($sigVaultBlobId > 0 && m360_vault_table_exists($conn)) {
            $vaultLoad = m360_vault_load_bytes($conn, $sigVaultBlobId);
            if (!empty($vaultLoad['ok']) && (string)($vaultLoad['bytes'] ?? '') !== '') {
                $ctype = 'image/png';
                $metaRows = customer_core_fetch_rows(
                    $conn,
                    'SELECT TOP 1 content_type FROM dbo.erp_document_blobs WHERE document_blob_id = ?',
                    [$sigVaultBlobId]
                );
                $metaType = trim((string)($metaRows[0]['content_type'] ?? ''));
                if ($metaType !== '' && str_starts_with(strtolower($metaType), 'image/')) {
                    $ctype = $metaType;
                }
                $sigImage = 'data:' . $ctype . ';base64,' . base64_encode((string)$vaultLoad['bytes']);
            }
        }
    }
    if ($sigImage !== '' && str_starts_with($sigImage, 'data:image/')) {
        $pdfData['pdf_signature_image_data'] = $sigImage;
    } elseif ($signed) {
        $pdfData['pdf_signature_missing_note'] = 'امضای دیجیتال با OTP ثبت شده اما تصویر امضا در آرشیو موجود نیست';
    }

    // Content hash must ignore generated_at so identical source reuses the same ACTIVE PDF.
    $pdfDataForHash = $pdfData;
    $pdfDataForHash['pdf_generated_at'] = 'STABLE';
    $htmlForHash = m360_contract_render_pdf_html($pdfDataForHash);
    $contentHash = hash('sha256', $htmlForHash . '|' . $pdfFormatVersion . '|' . $pdfType);

    foreach ($pdfs as $existing) {
        $status = strtoupper((string)($existing['status'] ?? ''));
        $type = (string)($existing['pdf_type'] ?? '');
        $format = (string)($existing['pdf_format_version'] ?? '');
        $existingContent = (string)($existing['content_hash'] ?? '');
        $rel = trim((string)($existing['relative_url'] ?? ''));
        if ($status !== 'ACTIVE' || $type !== $pdfType || $format !== $pdfFormatVersion) {
            continue;
        }
        if ($existingContent === '' || !hash_equals($existingContent, $contentHash)) {
            continue;
        }
        $existingBlobId = (int)($existing['document_blob_id'] ?? 0);
        if ($existingBlobId > 0 && m360_vault_table_exists($conn)) {
            $vaultLoad = m360_vault_load_bytes($conn, $existingBlobId);
            if ($vaultLoad['ok'] && str_starts_with($vaultLoad['bytes'], '%PDF')) {
                if ($recordDownloadEvent) {
                    m360_intake_contract_record_event(
                        $conn,
                        $contractId,
                        'CONTRACT_PDF_DOWNLOADED',
                        'pdf_id=' . (string)($existing['pdf_id'] ?? '') . ';actor=' . $actor . ';source=vault',
                        $generatedByUserId
                    );
                }

                return ['ok' => true, 'message' => '', 'meta' => $existing, 'bytes' => $vaultLoad['bytes'], 'created' => false];
            }
        }
        if ($rel === '' || !str_starts_with($rel, 'storage/')) {
            continue;
        }
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($abs)) {
            continue;
        }
        $bytes = (string)@file_get_contents($abs);
        if ($bytes === '' || !str_starts_with($bytes, '%PDF')) {
            continue;
        }
        if ($recordDownloadEvent) {
            m360_intake_contract_record_event(
                $conn,
                $contractId,
                'CONTRACT_PDF_DOWNLOADED',
                'pdf_id=' . (string)($existing['pdf_id'] ?? '') . ';actor=' . $actor,
                $generatedByUserId
            );
        }

        return ['ok' => true, 'message' => '', 'meta' => $existing, 'bytes' => $bytes, 'created' => false];
    }

    $pdfData['pdf_generated_at'] = $generatedAt;
    $html = m360_contract_render_pdf_html($pdfData);

    $tempDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mpdf';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }

    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 16,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle(M360_CONTRACT_TITLE);
        $mpdf->SetAuthor(M360_CONTRACT_COMPANY);
        $mpdf->SetHTMLFooter('<div style="font-family:dejavusans; font-size:8pt; text-align:center; direction:rtl;">صفحه {PAGENO} از {nbpg} | ' . htmlspecialchars(M360_CONTRACT_VERSION, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>');
        $mpdf->WriteHTML($html);
        $bytes = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    } catch (\Throwable $e) {
        return ['ok' => false, 'message' => 'تولید PDF ناموفق بود.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    if (!is_string($bytes) || $bytes === '' || !str_starts_with($bytes, '%PDF')) {
        return ['ok' => false, 'message' => 'خروجی PDF معتبر نیست.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    $pdfId = 'pdf_' . bin2hex(random_bytes(8));
    $onlineRequestId = (int)($contractRow['online_request_id'] ?? $snapshot['online_request_id'] ?? 0);
    $filename = 'contract-req' . max(0, $onlineRequestId) . '-c' . $contractId . '-' . $pdfType . '-' . substr($pdfId, -8) . '.pdf';
    $paths = m360_contract_pdf_storage_paths($onlineRequestId > 0 ? $onlineRequestId : 1, $filename);
    if (!$paths['ok']) {
        return ['ok' => false, 'message' => $paths['message'], 'meta' => null, 'bytes' => '', 'created' => false];
    }
    if (@file_put_contents($paths['absolute'], $bytes) === false) {
        return ['ok' => false, 'message' => 'ذخیره فایل PDF ناموفق بود.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    $vaultBlobId = 0;
    $vaultSha = '';
    if (m360_vault_table_exists($conn)) {
        $vaultResult = m360_vault_store_contract_pdf(
            $conn,
            $contractId,
            $onlineRequestId,
            (int)($contractRow['customer_id'] ?? 0),
            $bytes,
            $filename,
            $paths['relative'],
            $generatedByUserId
        );
        if ($vaultResult['ok']) {
            $vaultBlobId = (int)$vaultResult['blob_id'];
            $vaultSha = (string)$vaultResult['sha256'];
        }
    }

    foreach ($pdfs as $idx => $old) {
        if (!is_array($old)) {
            continue;
        }
        if (strtoupper((string)($old['status'] ?? '')) === 'ACTIVE') {
            $pdfs[$idx]['status'] = 'SUPERSEDED';
            $pdfs[$idx]['superseded_at'] = $generatedAt;
        }
    }

    $meta = [
        'pdf_id' => $pdfId,
        'online_request_id' => $onlineRequestId > 0 ? (string)$onlineRequestId : '',
        'contract_id' => (string)$contractId,
        'contract_version' => defined('M360_CONTRACT_VERSION') ? M360_CONTRACT_VERSION : 'MOGHARE360-INTAKE-V1',
        'pdf_format_version' => $pdfFormatVersion,
        'pdf_type' => $pdfType,
        'relative_url' => $paths['relative'],
        'filename' => $filename,
        'generated_at' => $generatedAt,
        'generated_by_user_id' => $generatedByUserId !== null && $generatedByUserId > 0 ? (string)$generatedByUserId : '',
        'generated_for_customer_id' => (string)((int)($contractRow['customer_id'] ?? 0)),
        'snapshot_hash' => $snapshotHash,
        'content_hash' => $contentHash,
        'status' => 'ACTIVE',
        'source' => 'CONTRACT_SNAPSHOT',
        'actor' => $actor,
    ];
    if ($vaultBlobId > 0) {
        $meta['document_blob_id'] = (string)$vaultBlobId;
        $meta['sha256_hash'] = $vaultSha;
        $meta['vault_canonical'] = '1';
    }
    $pdfs[] = $meta;
    if (count($pdfs) > 40) {
        $pdfs = array_slice($pdfs, -40);
    }
    $dataJson['contract_pdfs'] = $pdfs;
    $dataJson['contract_hash'] = $snapshotHash !== '' ? $snapshotHash : (string)($dataJson['contract_hash'] ?? '');
    $encoded = json_encode($dataJson, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'ثبت متادیتای PDF در SQL ناموفق بود.', 'meta' => null, 'bytes' => '', 'created' => false];
    }
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_data_json = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
        [$encoded, $contractId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'به‌روزرسانی قرارداد برای متادیتای PDF ناموفق بود.', 'meta' => null, 'bytes' => '', 'created' => false];
    }

    $eventName = $pdfType === 'signed' ? 'CONTRACT_PDF_SIGNED_VERSION_GENERATED' : 'CONTRACT_PDF_GENERATED';
    m360_intake_contract_record_event($conn, $contractId, $eventName, 'pdf_id=' . $pdfId . ';type=' . $pdfType . ';format=' . $pdfFormatVersion, $generatedByUserId);
    if ($recordDownloadEvent) {
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_PDF_DOWNLOADED', 'pdf_id=' . $pdfId . ';actor=' . $actor, $generatedByUserId);
    }

    return ['ok' => true, 'message' => '', 'meta' => $meta, 'bytes' => $bytes, 'created' => true];
}

/**
 * Public relative download URL (no absolute filesystem path).
 */
function m360_contract_pdf_download_url(int $contractId, string $actor = 'staff', string $token = ''): string
{
    $url = 'contract-pdf-download.php?contract_id=' . max(0, $contractId) . '&actor=' . rawurlencode($actor);
    if ($token !== '') {
        $url .= '&token=' . rawurlencode($token);
    }

    return $url;
}

/**
 * @param array<string, mixed>|null $contractRow
 */
function m360_contract_pdf_download_label(?array $contractRow): string
{
    if (is_array($contractRow) && m360_intake_contract_is_signed($contractRow)) {
        return 'دانلود PDF قرارداد تأییدشده';
    }

    return 'دانلود PDF قرارداد';
}

/**
 * Render a safe download anchor when engine + contract are available.
 *
 * @param array<string, mixed>|null $contractRow
 */
function m360_contract_pdf_render_download_button(?array $contractRow, string $actor = 'staff', string $token = '', string $cssClass = 'm360-rw-btn m360-rw-btn-secondary'): void
{
    if (!is_array($contractRow) || (int)($contractRow['contract_id'] ?? 0) < 1) {
        return;
    }
    if (!m360_contract_pdf_engine_ready()) {
        echo '<p class="m360-rw-muted">دانلود PDF قرارداد فعلاً در این محیط فعال نیست (موتور mPDF).</p>';

        return;
    }
    $label = m360_contract_pdf_download_label($contractRow);
    $href = m360_contract_pdf_download_url((int)$contractRow['contract_id'], $actor, $token);
    echo '<p class="m360-contract-pdf-download"><a class="' . htmlspecialchars($cssClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" href="'
        . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';
}
