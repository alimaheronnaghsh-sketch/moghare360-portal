<?php
declare(strict_types=1);

/**
 * Access matrix schema + catalog seed (moghare360_ERP only).
 * CLI-only. Idempotent. No DROP/TRUNCATE/blanket DELETE.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/includes/erp-config-loader.php';
require_once $repoRoot . '/public_html/includes/m360-access-matrix-catalog.php';

function am_conn()
{
    $c = erp_load_config()['database'];
    if (trim((string)$c['name']) !== 'moghare360_ERP') {
        throw new RuntimeException('Refuse non-canonical database');
    }
    $server = trim((string)$c['server']);
    $trusted = (bool)$c['trusted_connection'];
    $user = $trusted ? '' : (string)$c['username'];
    $pass = $trusted ? '' : (string)$c['password'];
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $d) {
        $dsn = "Driver={{$d}};Server={$server};Database=moghare360_ERP;";
        if ($trusted) {
            $dsn .= 'Trusted_Connection=Yes;';
        }
        if ($d === 'ODBC Driver 18 for SQL Server') {
            $dsn .= 'TrustServerCertificate=Yes;';
        }
        $conn = @odbc_connect($dsn, $user, $pass);
        if ($conn !== false) {
            return $conn;
        }
    }
    throw new RuntimeException('connect failed');
}

function am_exec($conn, string $sql, array $params = []): void
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 220));
    }
}

function am_one($conn, string $sql, array $params = []): ?array
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn));
    }
    $r = odbc_fetch_array($st);
    if (!is_array($r)) {
        return null;
    }
    $n = [];
    foreach ($r as $k => $v) {
        $n[strtolower((string)$k)] = $v;
    }
    return $n;
}

function am_exists($conn, string $table): bool
{
    return am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]) !== null;
}

function am_col($conn, string $table, string $col): bool
{
    return am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $col]) !== null;
}

function am_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (!am_col($conn, $table, $col)) {
        am_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}");
        echo "COL {$table}.{$col}\n";
    }
}

$conn = am_conn();
@odbc_autocommit($conn, false);

try {
    // Metadata columns on core_permissions
    am_ensure_col($conn, 'core_permissions', 'page_key', 'page_key nvarchar(80) NULL');
    am_ensure_col($conn, 'core_permissions', 'description_fa', 'description_fa nvarchar(400) NULL');
    am_ensure_col($conn, 'core_permissions', 'risk_level', "risk_level nvarchar(20) NOT NULL CONSTRAINT DF_core_perm_risk DEFAULT N'MEDIUM'");
    am_ensure_col($conn, 'core_permissions', 'is_base_self_service', 'is_base_self_service bit NOT NULL CONSTRAINT DF_core_perm_base DEFAULT 0');
    am_ensure_col($conn, 'core_permissions', 'contains_financial_data', 'contains_financial_data bit NOT NULL CONSTRAINT DF_core_perm_fin DEFAULT 0');
    am_ensure_col($conn, 'core_permissions', 'contains_private_hr_data', 'contains_private_hr_data bit NOT NULL CONSTRAINT DF_core_perm_hr DEFAULT 0');
    am_ensure_col($conn, 'core_permissions', 'requires_maker_checker', 'requires_maker_checker bit NOT NULL CONSTRAINT DF_core_perm_mc DEFAULT 0');
    am_ensure_col($conn, 'core_permissions', 'is_owner_only', 'is_owner_only bit NOT NULL CONSTRAINT DF_core_perm_oo DEFAULT 0');
    am_ensure_col($conn, 'core_permissions', 'updated_at', 'updated_at datetime2 NULL');
    am_ensure_col($conn, 'core_permissions', 'group_key', 'group_key nvarchar(80) NULL');
    am_ensure_col($conn, 'core_permissions', 'group_title_fa', 'group_title_fa nvarchar(120) NULL');
    am_ensure_col($conn, 'core_permissions', 'enforcement_state', "enforcement_state nvarchar(40) NOT NULL CONSTRAINT DF_core_perm_enf DEFAULT N'NOT_YET_ENFORCED'");
    am_ensure_col($conn, 'core_permissions', 'is_assignable', 'is_assignable bit NOT NULL CONSTRAINT DF_core_perm_asg DEFAULT 0');

    if (!am_exists($conn, 'core_user_permission_overrides')) {
        am_exec($conn, "
        CREATE TABLE dbo.core_user_permission_overrides (
            override_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            company_id int NOT NULL,
            user_id int NOT NULL,
            permission_id int NOT NULL,
            effect nvarchar(10) NOT NULL,
            effective_from datetime2 NULL,
            effective_to datetime2 NULL,
            reason nvarchar(400) NOT NULL,
            requested_by_user_id int NULL,
            approved_by_user_id int NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_cupo_cr DEFAULT SYSUTCDATETIME(),
            updated_at datetime2 NULL,
            revoked_at datetime2 NULL,
            revoked_by_user_id int NULL,
            request_id bigint NULL,
            CONSTRAINT CK_cupo_effect CHECK (effect IN (N'ALLOW', N'DENY')),
            CONSTRAINT FK_cupo_user FOREIGN KEY (user_id) REFERENCES dbo.core_users(user_id),
            CONSTRAINT FK_cupo_perm FOREIGN KEY (permission_id) REFERENCES dbo.core_permissions(permission_id)
        )");
        am_exec($conn, "CREATE UNIQUE INDEX UX_cupo_active ON dbo.core_user_permission_overrides (company_id, user_id, permission_id) WHERE revoked_at IS NULL");
        echo "CREATED core_user_permission_overrides\n";
    }

    if (!am_exists($conn, 'core_access_route_map')) {
        am_exec($conn, "
        CREATE TABLE dbo.core_access_route_map (
            map_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            route_pattern nvarchar(260) NOT NULL,
            permission_key nvarchar(120) NOT NULL,
            http_methods nvarchar(40) NOT NULL CONSTRAINT DF_carm_http DEFAULT N'GET,POST',
            is_active bit NOT NULL CONSTRAINT DF_carm_act DEFAULT 1,
            module_key nvarchar(80) NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_carm_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT UX_carm UNIQUE (route_pattern, permission_key)
        )");
        echo "CREATED core_access_route_map\n";
    }

    if (!am_exists($conn, 'core_access_matrix_pending')) {
        am_exec($conn, "
        CREATE TABLE dbo.core_access_matrix_pending (
            pending_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            company_id int NOT NULL,
            target_user_id int NOT NULL,
            permission_id int NOT NULL,
            desired_effect nvarchar(10) NOT NULL,
            status nvarchar(20) NOT NULL CONSTRAINT DF_camp_st DEFAULT N'SUBMITTED',
            reason nvarchar(400) NOT NULL,
            requested_by_user_id int NOT NULL,
            reviewed_by_user_id int NULL,
            review_note nvarchar(400) NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_camp_cr DEFAULT SYSUTCDATETIME(),
            reviewed_at datetime2 NULL,
            applied_at datetime2 NULL,
            CONSTRAINT CK_camp_effect CHECK (desired_effect IN (N'ALLOW', N'DENY', N'CLEAR')),
            CONSTRAINT CK_camp_status CHECK (status IN (N'DRAFT', N'SUBMITTED', N'APPROVED', N'REJECTED', N'RETURNED', N'CANCELLED', N'APPLIED', N'REVOKED')),
            CONSTRAINT FK_camp_user FOREIGN KEY (target_user_id) REFERENCES dbo.core_users(user_id),
            CONSTRAINT FK_camp_perm FOREIGN KEY (permission_id) REFERENCES dbo.core_permissions(permission_id)
        )");
        echo "CREATED core_access_matrix_pending\n";
    }

    $seeded = 0;
    $mapped = 0;
    foreach (m360_access_matrix_catalog_definitions() as $def) {
        $ex = am_one($conn, 'SELECT permission_id FROM dbo.core_permissions WHERE permission_key=?', [$def['permission_key']]);
        $active = ($def['enforcement_state'] === 'LEGACY_QUARANTINED') ? 1 : 1; // keep rows; matrix filters by module/group
        if ($ex === null) {
            am_exec($conn, 'INSERT INTO dbo.core_permissions
                (permission_key, module_key, action_key, permission_label, sort_order, is_active,
                 page_key, description_fa, risk_level, is_base_self_service, contains_financial_data,
                 contains_private_hr_data, requires_maker_checker, is_owner_only, updated_at,
                 group_key, group_title_fa, enforcement_state, is_assignable)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,SYSUTCDATETIME(),?,?,?,?)', [
                $def['permission_key'], $def['module_key'], $def['action_key'], $def['title_fa'], $def['sort_order'], $active,
                $def['page_key'], $def['description'], $def['risk_level'], $def['is_base_self_service'],
                $def['contains_financial_data'], $def['contains_private_hr_data'], $def['requires_maker_checker'],
                $def['is_owner_only'],
                $def['group_key'], $def['group_title_fa'], $def['enforcement_state'], $def['is_assignable'],
            ]);
            $seeded++;
        } else {
            am_exec($conn, 'UPDATE dbo.core_permissions SET
                module_key=?, action_key=?, permission_label=?, sort_order=?, page_key=?, description_fa=?,
                risk_level=?, is_base_self_service=?, contains_financial_data=?, contains_private_hr_data=?,
                requires_maker_checker=?, is_owner_only=?, updated_at=SYSUTCDATETIME(), is_active=?,
                group_key=?, group_title_fa=?, enforcement_state=?, is_assignable=?
             WHERE permission_key=?', [
                $def['module_key'], $def['action_key'], $def['title_fa'], $def['sort_order'], $def['page_key'],
                $def['description'], $def['risk_level'], $def['is_base_self_service'], $def['contains_financial_data'],
                $def['contains_private_hr_data'], $def['requires_maker_checker'], $def['is_owner_only'], $active,
                $def['group_key'], $def['group_title_fa'], $def['enforcement_state'], $def['is_assignable'],
                $def['permission_key'],
            ]);
        }
        foreach ($def['route_patterns'] as $route) {
            $route = trim(str_replace('\\', '/', (string)$route));
            if ($route === '') {
                continue;
            }
            $mx = am_one($conn, 'SELECT map_id FROM dbo.core_access_route_map WHERE route_pattern=? AND permission_key=?', [$route, $def['permission_key']]);
            if ($mx === null) {
                am_exec($conn, 'INSERT INTO dbo.core_access_route_map (route_pattern, permission_key, module_key) VALUES (?,?,?)', [
                    $route, $def['permission_key'], $def['module_key'],
                ]);
                $mapped++;
            }
        }
    }

    $ver = am_one($conn, "SELECT version_key FROM dbo.p360_hr_schema_version WHERE version_key=N'ACCESS_MATRIX_R0B_WS_20260803'");
    if ($ver === null && am_exists($conn, 'p360_hr_schema_version')) {
        am_exec($conn, "INSERT INTO dbo.p360_hr_schema_version (version_key, notes) VALUES (N'ACCESS_MATRIX_R0B_WS_20260803', N'Workshop R0B taxonomy groups + enforcement metadata')");
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    $pc = am_one($conn, 'SELECT COUNT(*) c FROM dbo.core_permissions');
    $rc = am_one($conn, 'SELECT COUNT(*) c FROM dbo.core_access_route_map');
    echo "TRANSACTION=COMMITTED\nSEEDED_NEW={$seeded}\nROUTE_MAPS_ADDED={$mapped}\nPERM_TOTAL=" . (int)($pc['c'] ?? 0) . "\nROUTE_MAP_TOTAL=" . (int)($rc['c'] ?? 0) . "\nMIGRATION_OK\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    echo "TRANSACTION=ROLLED_BACK\nERROR=" . $e->getMessage() . "\n";
    exit(1);
}
