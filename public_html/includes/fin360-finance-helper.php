<?php
declare(strict_types=1);

function fin360_db()
{
    static $conn = null;
    if (is_resource($conn)) {
        return $conn;
    }
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC required for Finance360.');
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
    throw new RuntimeException('Finance360 DB connection failed.');
}

function fin360_exec($conn, string $sql, array $params = []): bool
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    return (bool)@odbc_execute($stmt, $params);
}

function fin360_rows($conn, string $sql, array $params = []): array
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

function fin360_one($conn, string $sql, array $params = []): ?array
{
    $rows = fin360_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function fin360_scalar($conn, string $sql, array $params = [])
{
    $row = fin360_one($conn, $sql, $params);
    if (!$row) {
        return null;
    }
    return array_values($row)[0] ?? null;
}

function fin360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fin360_to_english_digits(string $input): string
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $s = str_replace($persian, $en, $input);
    return str_replace($arabic, $en, $s);
}

function fin360_parse_money($input): float
{
    $s = fin360_to_english_digits(trim((string)$input));
    $s = str_replace([' ', ',', '،', '٬'], '', $s);
    if ($s === '' || $s === '-') {
        return 0.0;
    }
    return (float)$s;
}

function fin360_format_money($amount, int $decimals = 0): string
{
    return number_format((float)$amount, $decimals, '.', ',');
}

function fin360_money($n): string
{
    return fin360_format_money($n, 0);
}

function fin360_money_input(string $name, $value = '', bool $required = false, string $extraAttrs = ''): string
{
    $num = (float)$value;
    $val = $num != 0.0 ? fin360_format_money($num) : '';
    $req = $required ? ' required' : '';
    return '<input class="f360-money-input" name="' . fin360_h($name) . '" type="text" inputmode="decimal" autocomplete="off" value="' . fin360_h($val) . '"' . $req . ($extraAttrs !== '' ? ' ' . $extraAttrs : '') . ' placeholder="0">';
}

function fin360_party_types(): array
{
    return [
        'CUSTOMER', 'SUPPLIER', 'CONTRACTOR', 'EMPLOYEE', 'OWNER', 'PARTNER', 'SHAREHOLDER',
        'INSURANCE', 'BANK', 'GOVERNMENT', 'TAX_ORG', 'SOCIAL_SECURITY', 'BROKER', 'COURIER',
        'SERVICE_PROVIDER', 'EXPENSE_PERSON', 'THIRD_PARTY', 'WALK_IN', 'OTHER',
    ];
}

function fin360_party_type_label(string $type): string
{
    $map = [
        'CUSTOMER' => 'مشتری',
        'SUPPLIER' => 'تأمین‌کننده',
        'CONTRACTOR' => 'پیمانکار',
        'EMPLOYEE' => 'کارمند',
        'OWNER' => 'مالک',
        'PARTNER' => 'شریک',
        'SHAREHOLDER' => 'سهامدار',
        'INSURANCE' => 'بیمه',
        'BANK' => 'بانک',
        'GOVERNMENT' => 'دولت',
        'TAX_ORG' => 'اداره مالیات',
        'SOCIAL_SECURITY' => 'تأمین اجتماعی',
        'BROKER' => 'کارگزار',
        'COURIER' => 'پیک',
        'SERVICE_PROVIDER' => 'خدمات‌دهنده',
        'EXPENSE_PERSON' => 'شخص هزینه',
        'THIRD_PARTY' => 'شخص ثالث',
        'WALK_IN' => 'مراجع حضوری',
        'OTHER' => 'سایر',
    ];
    return $map[strtoupper($type)] ?? $type;
}

function fin360_party_type_options(string $selected = ''): string
{
    $html = '';
    foreach (fin360_party_types() as $t) {
        $sel = strtoupper($selected) === $t ? ' selected' : '';
        $html .= '<option value="' . fin360_h($t) . '"' . $sel . '>' . fin360_h(fin360_party_type_label($t)) . ' (' . fin360_h($t) . ')</option>';
    }
    return $html;
}

function fin360_receipt_document_type(string $partyType): string
{
    return strtoupper($partyType) === 'CUSTOMER' ? 'CUSTOMER_RECEIPT' : 'GENERAL_RECEIPT';
}

function fin360_payment_document_type(string $partyType): string
{
    return strtoupper($partyType) === 'SUPPLIER' ? 'SUPPLIER_PAYMENT' : 'GENERAL_PAYMENT';
}

function fin360_ap_party_types(): array
{
    return ['SUPPLIER', 'CONTRACTOR', 'EMPLOYEE', 'OWNER', 'PARTNER', 'TAX_ORG', 'SOCIAL_SECURITY', 'BANK', 'BROKER', 'COURIER', 'SERVICE_PROVIDER', 'EXPENSE_PERSON', 'GOVERNMENT', 'OTHER'];
}

/**
 * Resolve party from existing id or quick-create fields.
 * @return array{party_id:?int,party_type:?string,display_name:?string,error:?string}
 */
function fin360_resolve_party($conn, array $post, string $direction = 'IN'): array
{
    $idKeys = $direction === 'IN'
        ? ['payer_party_id', 'party_id']
        : ['payee_party_id', 'party_id'];
    $typeKeys = $direction === 'IN'
        ? ['payer_type', 'party_type']
        : ['payee_type', 'party_type'];
    $nameKeys = $direction === 'IN'
        ? ['payer_display_name', 'display_name']
        : ['payee_display_name', 'display_name'];

    $partyId = 0;
    foreach ($idKeys as $k) {
        if (!empty($post[$k])) {
            $partyId = (int)$post[$k];
            break;
        }
    }
    if ($partyId > 0) {
        $p = fin360_one($conn, 'SELECT party_id, party_type, display_name FROM dbo.fin360_parties WHERE party_id=? AND is_active=1', [$partyId]);
        if ($p) {
            return [
                'party_id' => (int)$p['party_id'],
                'party_type' => (string)$p['party_type'],
                'display_name' => (string)$p['display_name'],
                'error' => null,
            ];
        }
        return ['party_id' => null, 'party_type' => null, 'display_name' => null, 'error' => 'طرف حساب انتخاب‌شده یافت نشد.'];
    }

    $type = 'OTHER';
    foreach ($typeKeys as $k) {
        if (!empty($post[$k])) {
            $type = strtoupper(trim((string)$post[$k]));
            break;
        }
    }
    $name = '';
    foreach ($nameKeys as $k) {
        if (!empty($post[$k])) {
            $name = trim((string)$post[$k]);
            break;
        }
    }
    if ($name === '') {
        return ['party_id' => null, 'party_type' => null, 'display_name' => null, 'error' => 'پرداخت‌کننده/دریافت‌کننده یا طرف حساب مالی الزامی است.'];
    }
    if (!in_array($type, fin360_party_types(), true)) {
        $type = 'OTHER';
    }
    $mobile = trim((string)($post['mobile'] ?? $post['payer_mobile'] ?? $post['payee_mobile'] ?? ''));
    $sourceRef = trim((string)($post['source_ref_text'] ?? ''));
    $sourceType = trim((string)($post['source_type'] ?? 'MANUAL'));
    $ok = fin360_exec(
        $conn,
        'INSERT INTO dbo.fin360_parties (party_type, display_name, mobile, source_type, source_ref_text, is_active) VALUES (?,?,?,?,?,1)',
        [$type, $name, $mobile !== '' ? $mobile : null, $sourceType !== '' ? $sourceType : null, $sourceRef !== '' ? $sourceRef : null]
    );
    if (!$ok) {
        return ['party_id' => null, 'party_type' => null, 'display_name' => null, 'error' => 'ایجاد طرف حساب مالی ناموفق بود.'];
    }
    $newId = (int)fin360_scalar($conn, 'SELECT MAX(party_id) FROM dbo.fin360_parties WHERE display_name=? AND party_type=?', [$name, $type]);
    fin360_audit($conn, 'CREATE', 'fin360_parties', (string)$newId, null, ['name' => $name, 'type' => $type, 'quick' => true]);
    return ['party_id' => $newId, 'party_type' => $type, 'display_name' => $name, 'error' => null];
}

function fin360_counterparty_role_label(string $direction): string
{
    return $direction === 'IN' ? 'پرداخت‌کننده' : 'دریافت‌کننده';
}

function fin360_actor(): array
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

function fin360_csrf_boot(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['fin360_csrf'])) {
        $_SESSION['fin360_csrf'] = bin2hex(random_bytes(16));
    }
}

function fin360_csrf_token(): string
{
    fin360_csrf_boot();
    return (string)$_SESSION['fin360_csrf'];
}

function fin360_csrf_field(): string
{
    return '<input type="hidden" name="fin360_csrf" value="' . fin360_h(fin360_csrf_token()) . '">';
}

function fin360_csrf_require(): void
{
    fin360_csrf_boot();
    $expected = (string)($_SESSION['fin360_csrf'] ?? '');
    $got = (string)($_POST['fin360_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $got)) {
        http_response_code(403);
        echo 'CSRF نامعتبر.';
        exit;
    }
}

function fin360_next_code($conn, string $prefix, string $table, string $col): string
{
    $n = (int)(fin360_scalar($conn, "SELECT ISNULL(MAX($col),0) FROM dbo.$table", []) ?? 0);
    // Prefer identity-ish sequence from count
    $c = (int)(fin360_scalar($conn, "SELECT COUNT(*)+1 FROM dbo.$table", []) ?? 1);
    return $prefix . '-' . str_pad((string)$c, 5, '0', STR_PAD_LEFT);
}

function fin360_audit($conn, string $action, string $entity, ?string $entityId, $before, $after, ?string $reason = null, ?string $approver = null): void
{
    $actor = fin360_actor();
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $page = (string)($_SERVER['PHP_SELF'] ?? 'erp-final-invoice-board.php');
    fin360_exec(
        $conn,
        'INSERT INTO dbo.fin360_audit_log (actor_user, action_code, entity_name, entity_id, before_json, after_json, reason, ip_address, device_info, source_page, approver_user) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
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
