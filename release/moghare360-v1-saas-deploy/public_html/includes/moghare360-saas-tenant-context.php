<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';

/**
 * MOGHARE360 V1 — Tenant / company context (mandatory company_id scoping).
 */

function mogh_tenant_db_connect()
{
    if (!extension_loaded('odbc')) {
        throw new RuntimeException('ODBC extension is not available.');
    }

    try {
        $saasCfg = mogh_saas_load_config();
        if (isset($saasCfg['erp_config']['database']) && is_array($saasCfg['erp_config']['database'])) {
            $db = $saasCfg['erp_config']['database'];
        } else {
            mogh_saas_require_file('erp-config-loader.php');
            $cfg = erp_load_config();
            $db = $cfg['database'];
        }
        $server = (string)$db['server'];
        $name = (string)$db['name'];
        $trusted = !empty($db['trusted_connection']);
        $user = (string)($db['username'] ?? '');
        $pass = (string)($db['password'] ?? '');

        $dsnCandidates = [];
        if ($trusted) {
            $dsnCandidates[] = "Driver={ODBC Driver 17 for SQL Server};Server={$server};Database={$name};Trusted_Connection=Yes;";
            $dsnCandidates[] = "Driver={ODBC Driver 18 for SQL Server};Server={$server};Database={$name};Trusted_Connection=Yes;TrustServerCertificate=Yes;";
        } else {
            $dsnCandidates[] = "Driver={ODBC Driver 17 for SQL Server};Server={$server};Database={$name};";
            $dsnCandidates[] = "Driver={ODBC Driver 18 for SQL Server};Server={$server};Database={$name};TrustServerCertificate=Yes;";
        }

        foreach ($dsnCandidates as $dsn) {
            $conn = $trusted ? @odbc_connect($dsn, '', '') : @odbc_connect($dsn, $user, $pass);
            if ($conn !== false) {
                return $conn;
            }
        }
    } catch (Throwable) {
        mogh_saas_require_file('erp-auth-context.php');
        return erp_auth_create_local_odbc_connection();
    }

    throw new RuntimeException('Database connection failed.');
}

/** @return array{company_id: int, company_code: string, company_name: string} */
function mogh_tenant_resolve_from_request(): array
{
    $cfg = mogh_saas_load_config();
    $headerId = isset($_SERVER['HTTP_X_MOGHARE360_COMPANY_ID'])
        ? (int)$_SERVER['HTTP_X_MOGHARE360_COMPANY_ID'] : 0;
    $headerCode = isset($_SERVER['HTTP_X_MOGHARE360_COMPANY_CODE'])
        ? trim((string)$_SERVER['HTTP_X_MOGHARE360_COMPANY_CODE']) : '';

    $conn = mogh_tenant_db_connect();
    try {
        if ($headerId > 0) {
            $sql = "SELECT company_id, company_code, company_name FROM dbo.erp_companies WHERE company_id = ? AND is_active = 1";
            $stmt = @odbc_prepare($conn, $sql);
            if ($stmt !== false && @odbc_execute($stmt, [$headerId])) {
                if ($row = odbc_fetch_array($stmt)) {
                    return mogh_tenant_normalize_row($row);
                }
            }
        }

        if ($headerCode !== '') {
            $sql = "SELECT company_id, company_code, company_name FROM dbo.erp_companies WHERE company_code = ? AND is_active = 1";
            $stmt = odbc_prepare($conn, $sql);
            if ($stmt !== false && @odbc_execute($stmt, [$headerCode])) {
                if ($row = odbc_fetch_array($stmt)) {
                    return mogh_tenant_normalize_row($row);
                }
            }
        }

        $defaultCode = (string)($cfg['default_company_code'] ?? 'MOGHAREH_MAIN');
        $sql = "SELECT company_id, company_code, company_name FROM dbo.erp_companies WHERE company_code = ? AND is_active = 1";
        $stmt = odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$defaultCode])) {
            if ($row = odbc_fetch_array($stmt)) {
                return mogh_tenant_normalize_row($row);
            }
        }

        return [
            'company_id' => 1,
            'company_code' => $defaultCode,
            'company_name' => 'MOGHAREH MAIN',
        ];
    } finally {
        @odbc_close($conn);
    }
}

/** @param array<string, mixed> $row */
function mogh_tenant_normalize_row(array $row): array
{
    return [
        'company_id' => (int)($row['company_id'] ?? 0),
        'company_code' => (string)($row['company_code'] ?? ''),
        'company_name' => (string)($row['company_name'] ?? ''),
    ];
}

function mogh_tenant_assert_company_scope(int $recordCompanyId, int $contextCompanyId): void
{
    if ($recordCompanyId !== $contextCompanyId) {
        throw new RuntimeException('دسترسی بین tenant مجاز نیست.');
    }
}

function mogh_tenant_sql_company_filter(): string
{
    return 'company_id = ?';
}
