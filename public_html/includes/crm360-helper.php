<?php
declare(strict_types=1);

function crm360_db()
{
    static $conn = null;
    if (is_resource($conn)) {
        return $conn;
    }
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC required for CRM360.');
    }
    $server = 'localhost\\SQLEXPRESS';
    $name = 'moghare360_ERP';
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $driver) {
        $dsn = 'Driver={' . $driver . '};Server=' . $server . ';Database=' . $name . ';CharacterSet=UTF-8;Trusted_Connection=Yes;';
        if ($driver === 'ODBC Driver 18 for SQL Server') {
            $dsn .= 'TrustServerCertificate=Yes;';
        }
        $conn = @odbc_connect($dsn, '', '');
        if ($conn !== false) {
            return $conn;
        }
    }
    throw new RuntimeException('CRM360 DB connection failed.');
}

function crm360_exec($conn, string $sql, array $params = []): bool
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    return (bool)@odbc_execute($stmt, $params);
}

function crm360_rows($conn, string $sql, array $params = []): array
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while ($r = odbc_fetch_array($stmt)) {
        $norm = [];
        foreach ($r as $k => $v) {
            $norm[strtolower((string)$k)] = $v;
        }
        $rows[] = $norm;
    }
    return $rows;
}

function crm360_one($conn, string $sql, array $params = []): ?array
{
    $rows = crm360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function crm360_scalar($conn, string $sql, array $params = [])
{
    $row = crm360_one($conn, $sql, $params);
    if (!$row) {
        return null;
    }
    return array_values($row)[0] ?? null;
}

function crm360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function crm360_actor(): array
{
    $name = 'local_owner';
    $dev = true;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    foreach (['staff_username', 'username', 'user_name', 'display_name'] as $k) {
        if (!empty($_SESSION[$k])) {
            $name = (string)$_SESSION[$k];
            $dev = false;
            break;
        }
    }
    if (!empty($_SESSION['staff_user']) && is_array($_SESSION['staff_user'])) {
        $su = $_SESSION['staff_user'];
        $name = (string)($su['username'] ?? $su['display_name'] ?? $name);
        $dev = false;
    }
    return ['actor' => $name, 'dev_mode' => $dev];
}

function crm360_csrf_boot(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['crm360_csrf'])) {
        $_SESSION['crm360_csrf'] = bin2hex(random_bytes(16));
    }
}

function crm360_csrf_token(): string
{
    crm360_csrf_boot();
    return (string)$_SESSION['crm360_csrf'];
}

function crm360_csrf_field(): string
{
    return '<input type="hidden" name="crm360_csrf" value="' . crm360_h(crm360_csrf_token()) . '">';
}

function crm360_csrf_require(): void
{
    crm360_csrf_boot();
    $expected = (string)($_SESSION['crm360_csrf'] ?? '');
    $got = (string)($_POST['crm360_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $got)) {
        http_response_code(403);
        echo 'CSRF نامعتبر.';
        exit;
    }
}

function crm360_next_code($conn, string $prefix, string $table, string $col): string
{
    $c = (int)(crm360_scalar($conn, "SELECT COUNT(*)+1 FROM dbo.$table", []) ?? 1);
    return $prefix . '-' . str_pad((string)$c, 5, '0', STR_PAD_LEFT);
}

function crm360_audit($conn, string $action, string $entity, ?string $entityId, $before, $after, ?string $reason = null, ?string $approver = null): void
{
    $actor = crm360_actor();
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $page = (string)($_SERVER['PHP_SELF'] ?? 'erp-reception-board.php');
    crm360_exec(
        $conn,
        'INSERT INTO dbo.crm360_audit_log (actor_user, action_code, entity_name, entity_id, before_json, after_json, reason, ip_address, device_info, source_page, approver_user) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [
            $actor['actor'],
            $action,
            $entity,
            $entityId,
            $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            $reason,
            $ip,
            $ua,
            $page,
            $approver,
        ]
    );
}

function crm360_brands(): array
{
    return ['Mercedes-Benz', 'BMW', 'Porsche', 'Volvo', 'Volkswagen'];
}

function crm360_brand_options(string $selected = ''): string
{
    $html = '<option value="">— انتخاب برند —</option>';
    foreach (crm360_brands() as $b) {
        $sel = $selected === $b ? ' selected' : '';
        $html .= '<option value="' . crm360_h($b) . '"' . $sel . '>' . crm360_h($b) . '</option>';
    }
    return $html;
}

function crm360_doc_types(): array
{
    return [
        'NATIONAL_CARD' => 'کارت ملی',
        'VEHICLE_CARD' => 'کارت خودرو',
        'INSURANCE' => 'بیمه‌نامه',
        'CONTRACT' => 'قرارداد',
        'CONSENT' => 'رضایت‌نامه',
    ];
}

function crm360_doc_type_label(string $type): string
{
    return crm360_doc_types()[$type] ?? $type;
}

/**
 * @return array{percent:int,missing:array<int,string>,docs_ok:bool}
 */
function crm360_calc_case_completion($conn, int $caseId): array
{
    $missing = [];
    $checks = 0;
    $passed = 0;

    $case = crm360_one(
        $conn,
        'SELECT c.*, cu.full_name, cu.mobile, v.brand, v.model, v.plate_no, v.vin
         FROM dbo.crm360_reception_cases c
         INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id = c.customer_profile_id
         INNER JOIN dbo.crm360_vehicle_profiles v ON v.vehicle_profile_id = c.vehicle_profile_id
         WHERE c.case_id=?',
        [$caseId]
    );
    if (!$case) {
        return ['percent' => 0, 'missing' => ['پرونده یافت نشد'], 'docs_ok' => false];
    }

    $checks++;
    if (trim((string)($case['full_name'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'نام کامل مشتری';
    }

    $checks++;
    if (trim((string)($case['mobile'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'موبایل مشتری';
    }

    $checks++;
    if (trim((string)($case['brand'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'برند خودرو';
    }

    $checks++;
    if (trim((string)($case['model'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'مدل خودرو';
    }

    $checks++;
    $plate = trim((string)($case['plate_no'] ?? ''));
    $vin = trim((string)($case['vin'] ?? ''));
    if ($plate !== '' || $vin !== '') {
        $passed++;
    } else {
        $missing[] = 'پلاک یا VIN خودرو';
    }

    $checks++;
    if (trim((string)($case['service_type'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'نوع خدمت پرونده';
    }

    $checks++;
    if (trim((string)($case['responsible_staff'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'مسئول پرونده';
    }

    $checks++;
    if (trim((string)($case['case_type'] ?? '')) !== '') {
        $passed++;
    } else {
        $missing[] = 'نوع پرونده';
    }

    $docCount = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_case_documents WHERE case_id=?', [$caseId]) ?? 0);
    $checks++;
    $docsOk = $docCount >= 5;
    if ($docsOk) {
        $passed++;
    } else {
        $missing[] = 'چک‌لیست مدارک (۵ مورد)';
    }

    $percent = $checks > 0 ? (int)round(($passed / $checks) * 100) : 0;
    return ['percent' => $percent, 'missing' => $missing, 'docs_ok' => $docsOk];
}

function crm360_sync_case_completion($conn, int $caseId): void
{
    $calc = crm360_calc_case_completion($conn, $caseId);
    $docsStatus = $calc['docs_ok'] ? 'COMPLETE' : 'MISSING';
    if ($calc['percent'] >= 100) {
        $caseStatus = 'READY';
    } elseif ($calc['percent'] >= 50) {
        $caseStatus = 'IN_PROGRESS';
    } else {
        $caseStatus = 'DRAFT';
    }
    crm360_exec(
        $conn,
        'UPDATE dbo.crm360_reception_cases SET profile_completion_percent=?, required_docs_status=?, case_status=?, updated_at=SYSUTCDATETIME() WHERE case_id=?',
        [$calc['percent'], $docsStatus, $caseStatus, $caseId]
    );
}

function crm360_ensure_club_row($conn, int $customerProfileId): void
{
    $exists = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_customer_club WHERE customer_profile_id=?', [$customerProfileId]) ?? 0);
    if ($exists === 0) {
        crm360_exec(
            $conn,
            'INSERT INTO dbo.crm360_customer_club (customer_profile_id, tier_code, points_balance, visit_count) VALUES (?,?,0,0)',
            [$customerProfileId, 'NEW']
        );
    }
}

function crm360_generate_docs_checklist($conn, int $caseId, string $actor): bool
{
    $case = crm360_one($conn, 'SELECT customer_profile_id, vehicle_profile_id FROM dbo.crm360_reception_cases WHERE case_id=?', [$caseId]);
    if (!$case) {
        return false;
    }
    foreach (crm360_doc_types() as $code => $title) {
        $exists = (int)(crm360_scalar(
            $conn,
            'SELECT COUNT(*) FROM dbo.crm360_case_documents WHERE case_id=? AND document_type=?',
            [$caseId, $code]
        ) ?? 0);
        if ($exists === 0) {
            crm360_exec(
                $conn,
                'INSERT INTO dbo.crm360_case_documents (case_id, customer_profile_id, vehicle_profile_id, document_type, document_title, document_status, created_by) VALUES (?,?,?,?,?,N\'MISSING\',?)',
                [$caseId, (int)$case['customer_profile_id'], (int)$case['vehicle_profile_id'], $code, $title, $actor]
            );
        }
    }
    crm360_sync_case_completion($conn, $caseId);
    return true;
}

function crm360_table_exists($conn, string $table): bool
{
    $row = crm360_one($conn, 'SELECT CASE WHEN OBJECT_ID(?) IS NOT NULL THEN 1 ELSE 0 END AS ok', ['dbo.' . $table]);
    return !empty($row['ok']);
}

function crm360_online_requests($conn, int $limit = 50): array
{
    if (!crm360_table_exists($conn, 'erp_customer_online_requests')) {
        return [];
    }
    return crm360_rows(
        $conn,
        'SELECT TOP ' . (int)$limit . ' online_request_id, mobile, vehicle_plate, request_status, created_at
         FROM dbo.erp_customer_online_requests ORDER BY online_request_id DESC'
    );
}

function crm360_gauge(int $pct, string $label, string $color = '#3ecf8e'): string
{
    $pct = max(0, min(100, $pct));
    $c = 2 * M_PI * 45;
    $off = $c * (1 - $pct / 100);
    return '<div class="c360-gauge-card"><div class="c360-gauge"><svg viewBox="0 0 100 100"><circle class="c360-gauge-track" cx="50" cy="50" r="45"/><circle class="c360-gauge-value" cx="50" cy="50" r="45" stroke="' . crm360_h($color) . '" stroke-dasharray="' . $c . '" stroke-dashoffset="' . $off . '"/></svg><div class="c360-gauge-center"><strong>' . $pct . '%</strong><span>وضعیت</span></div></div><h4>' . crm360_h($label) . '</h4></div>';
}

function crm360_customer_options($conn): string
{
    $html = '<option value="">— انتخاب مشتری —</option>';
    foreach (crm360_rows($conn, 'SELECT customer_profile_id, full_name, mobile FROM dbo.crm360_customer_profiles ORDER BY customer_profile_id DESC') as $c) {
        $html .= '<option value="' . (int)$c['customer_profile_id'] . '">' . crm360_h((string)$c['full_name']) . ' — ' . crm360_h((string)($c['mobile'] ?? '')) . '</option>';
    }
    return $html;
}

function crm360_vehicle_options($conn): string
{
    $html = '<option value="">— انتخاب خودرو —</option>';
    foreach (crm360_rows($conn, 'SELECT vehicle_profile_id, brand, model, plate_no FROM dbo.crm360_vehicle_profiles ORDER BY vehicle_profile_id DESC') as $v) {
        $html .= '<option value="' . (int)$v['vehicle_profile_id'] . '">' . crm360_h((string)$v['brand']) . ' ' . crm360_h((string)$v['model']) . ' — ' . crm360_h((string)($v['plate_no'] ?? '')) . '</option>';
    }
    return $html;
}

function crm360_case_options($conn): string
{
    $html = '<option value="">— انتخاب پرونده —</option>';
    foreach (crm360_rows($conn, 'SELECT case_id, case_code FROM dbo.crm360_reception_cases ORDER BY case_id DESC') as $c) {
        $html .= '<option value="' . (int)$c['case_id'] . '">' . crm360_h((string)$c['case_code']) . '</option>';
    }
    return $html;
}

function crm360_promotion_options($conn): string
{
    $html = '<option value="">— انتخاب پروموشن —</option>';
    foreach (crm360_rows($conn, "SELECT promotion_id, promotion_code, title FROM dbo.crm360_promotions WHERE promotion_status IN ('ACTIVE','DRAFT') ORDER BY promotion_id DESC") as $p) {
        $html .= '<option value="' . (int)$p['promotion_id'] . '">' . crm360_h((string)$p['promotion_code']) . ' — ' . crm360_h((string)$p['title']) . '</option>';
    }
    return $html;
}

/** @return array{actor:string,dev_mode:bool,role:string,role_label:string} */
function crm360_auth_context(): array
{
    $base = crm360_actor();
    $role = 'RECEPTION';
    if (!empty($_SESSION['staff_role'])) {
        $role = strtoupper((string)$_SESSION['staff_role']);
    } elseif (!empty($_SESSION['staff_user']['role'])) {
        $role = strtoupper((string)$_SESSION['staff_user']['role']);
    } elseif ($base['dev_mode'] || strcasecmp((string)$base['actor'], 'local_owner') === 0) {
        $role = 'OWNER';
    }
    $map = [
        'OWNER' => 'مالک',
        'MANAGER' => 'مدیر',
        'CRM_MANAGER' => 'مدیر ارتباط با مشتریان',
        'RECEPTION' => 'پذیرش',
    ];
    if (!isset($map[$role])) {
        $role = 'RECEPTION';
    }
    return [
        'actor' => (string)$base['actor'],
        'dev_mode' => (bool)$base['dev_mode'],
        'role' => $role,
        'role_label' => $map[$role],
    ];
}

function crm360_can(string $capability, ?array $auth = null): bool
{
    $auth = $auth ?? crm360_auth_context();
    $role = $auth['role'];
    $matrix = [
        'customer_create' => ['OWNER', 'MANAGER', 'CRM_MANAGER', 'RECEPTION'],
        'vehicle_create' => ['OWNER', 'MANAGER', 'CRM_MANAGER', 'RECEPTION'],
        'legacy_draft' => ['OWNER', 'MANAGER', 'CRM_MANAGER'],
        'legacy_approve' => ['OWNER', 'MANAGER'],
        'vip_nominate' => ['OWNER', 'MANAGER', 'CRM_MANAGER', 'RECEPTION'],
        'vip_approve' => ['OWNER', 'MANAGER', 'CRM_MANAGER'],
        'vip_rules' => ['OWNER', 'MANAGER'],
    ];
    $allowed = $matrix[$capability] ?? [];
    return in_array($role, $allowed, true);
}

function crm360_hub_layers(): array
{
    return [
        'dashboard' => 'داشبورد',
        'reception' => 'پرونده‌های پذیرش',
        'customers' => 'مشتریان و خودروها',
        'legacy' => 'ورود اطلاعات قدیمی',
        'vip' => 'VIP و باشگاه مشتریان',
        'experience' => 'تجربه و پیگیری',
        'audit' => 'گزارش و Audit',
    ];
}

/** Map legacy fine-grained tabs into layered hub + panel. */
function crm360_resolve_hub_tab(string $rawTab): array
{
    $alias = [
        'vehicles' => ['customers', 'vehicles'],
        'cases' => ['reception', 'cases'],
        'documents' => ['reception', 'documents'],
        'cartable' => ['experience', 'cartable'],
        'satisfaction' => ['experience', 'satisfaction'],
        'complaints' => ['experience', 'complaints'],
        'club' => ['vip', 'club'],
        'reminders' => ['experience', 'reminders'],
        'returns' => ['experience', 'returns'],
        'promotions' => ['experience', 'promotions'],
        'sms' => ['experience', 'sms'],
    ];
    $tab = preg_replace('/[^a-z_]/', '', $rawTab) ?: 'dashboard';
    $panel = preg_replace('/[^a-z_]/', '', (string)($_GET['panel'] ?? '')) ?: '';
    if (isset($alias[$tab])) {
        [$tab, $defaultPanel] = $alias[$tab];
        if ($panel === '') {
            $panel = $defaultPanel;
        }
    }
    $layers = crm360_hub_layers();
    if (!isset($layers[$tab])) {
        $tab = 'dashboard';
        $panel = '';
    }
    return ['tab' => $tab, 'panel' => $panel];
}

function crm360_active_vip_rules($conn): array
{
    if (!crm360_table_exists($conn, 'crm360_vip_rules')) {
        return [];
    }
    return crm360_rows(
        $conn,
        "SELECT * FROM dbo.crm360_vip_rules WHERE is_active=1 AND approval_status=N'APPROVED' AND (effective_to IS NULL OR effective_to >= CONVERT(date, SYSUTCDATETIME())) ORDER BY vip_rule_id"
    );
}

function crm360_vip_candidates($conn, int $limit = 50): array
{
    if (!crm360_table_exists($conn, 'crm360_customer_profiles')) {
        return [];
    }
    $rules = crm360_active_vip_rules($conn);
    $visitTh = 4.0;
    $revTh = 1000000000.0;
    foreach ($rules as $r) {
        $type = strtoupper((string)($r['rule_type'] ?? ''));
        $th = (float)($r['threshold_value'] ?? 0);
        if ($type === 'VISIT_COUNT' && $th > 0) {
            $visitTh = $th;
        }
        if ($type === 'REVENUE' && $th > 0) {
            $revTh = $th;
        }
    }
    $sql = 'SELECT TOP ' . (int)$limit . ' c.customer_profile_id, c.full_name, c.mobile, c.vip_level,
            ISNULL(cl.visit_count,0) AS visit_count, ISNULL(cl.total_revenue_amount,0) AS total_revenue_amount,
            ISNULL(cl.tier_code,N\'NEW\') AS tier_code
            FROM dbo.crm360_customer_profiles c
            LEFT JOIN dbo.crm360_customer_club cl ON cl.customer_profile_id=c.customer_profile_id
            WHERE UPPER(ISNULL(c.vip_level,N\'NONE\')) NOT IN (N\'VIP\',N\'GOLD\',N\'PLATINUM\')
              AND (
                ISNULL(cl.visit_count,0) >= ' . (float)$visitTh . '
                OR ISNULL(cl.total_revenue_amount,0) >= ' . (float)$revTh . '
              )
            ORDER BY ISNULL(cl.visit_count,0) DESC, ISNULL(cl.total_revenue_amount,0) DESC';
    $rows = crm360_rows($conn, $sql);
    foreach ($rows as &$row) {
        $reasons = [];
        if ((int)($row['visit_count'] ?? 0) >= (int)$visitTh) {
            $reasons[] = 'مراجعه ≥ ' . (int)$visitTh;
        }
        if ((float)($row['total_revenue_amount'] ?? 0) >= $revTh) {
            $reasons[] = 'درآمد ≥ آستانه';
        }
        $row['suggested_reason'] = implode(' / ', $reasons);
    }
    unset($row);
    return $rows;
}

function crm360_parse_legacy_customer_csv(string $raw): array
{
    $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
    $out = [];
    $rowNo = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $rowNo++;
        if ($rowNo === 1 && preg_match('/full_name|نام/i', $line)) {
            continue;
        }
        $parts = str_getcsv($line);
        $name = trim((string)($parts[0] ?? ''));
        $mobile = trim((string)($parts[1] ?? ''));
        $type = strtoupper(trim((string)($parts[2] ?? 'PERSON'))) ?: 'PERSON';
        $out[] = [
            'row_no' => $rowNo,
            'full_name' => $name,
            'mobile' => $mobile,
            'customer_type' => in_array($type, ['PERSON', 'COMPANY'], true) ? $type : 'PERSON',
            'raw' => $line,
        ];
    }
    return $out;
}
