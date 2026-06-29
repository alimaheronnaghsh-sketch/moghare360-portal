/*
================================================================================
MOGHARE360 P11.9-A — Read-Only Dry Run Preflight Check
File: P11_9_A_READONLY_PREFLIGHT_CHECK.sql
================================================================================

PURPOSE: Report-only checks before One-Day Run dry run preparation.
MODE:    READ-ONLY — reporting queries only (no data or schema mutations).

Execute manually in SSMS. Review output only.
P11.9-A does NOT auto-run this script.

Schema references: core_users, erp_company_users, erp_jobcards,
                   mission_15 customers/vehicles, P1-P7 tables.
================================================================================
*/

USE MOGHARE360_ERP;
GO

SET NOCOUNT ON;

PRINT N'=== MOGHARE360 P11.9-A Read-Only Preflight ===';
PRINT N'Timestamp (UTC): ' + CONVERT(NVARCHAR(30), SYSUTCDATETIME(), 126);
PRINT N'';

/* --------------------------------------------------------------------------
   1. Owner users
-------------------------------------------------------------------------- */
PRINT N'--- 1. Owner / system owner users ---';
IF OBJECT_ID(N'dbo.core_users', N'U') IS NULL
    PRINT N'WARNING: dbo.core_users missing';
ELSE
BEGIN
    SELECT
        user_id,
        username,
        full_name,
        lifecycle_state,
        is_login_enabled,
        is_system_owner
    FROM dbo.core_users
    WHERE is_system_owner = 1
    ORDER BY user_id;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   2. Staff users by role (login-enabled)
-------------------------------------------------------------------------- */
PRINT N'--- 2. Active login-enabled staff by erp_company_users.role_code ---';
IF OBJECT_ID(N'dbo.erp_company_users', N'U') IS NULL
    PRINT N'WARNING: dbo.erp_company_users missing';
ELSE IF OBJECT_ID(N'dbo.core_users', N'U') IS NULL
    PRINT N'WARNING: dbo.core_users missing — cannot join';
ELSE
BEGIN
    SELECT
        cu.role_code,
        COUNT(*) AS company_user_rows,
        SUM(CASE WHEN u.is_login_enabled = 1 AND u.lifecycle_state = N'ACTIVE' THEN 1 ELSE 0 END) AS login_enabled_active
    FROM dbo.erp_company_users cu
    INNER JOIN dbo.core_users u ON u.user_id = cu.user_id
    WHERE cu.is_active = 1
    GROUP BY cu.role_code
    ORDER BY cu.role_code;

    SELECT
        N'Minimum roles check' AS check_name,
        CASE
            WHEN SUM(CASE WHEN cu.role_code = N'RECEPTION' THEN 1 ELSE 0 END) >= 1
             AND SUM(CASE WHEN cu.role_code = N'SERVICE_MANAGER' THEN 1 ELSE 0 END) >= 1
             AND SUM(CASE WHEN cu.role_code = N'TECHNICIAN' THEN 1 ELSE 0 END) >= 1
             AND SUM(CASE WHEN cu.role_code = N'PARTS' THEN 1 ELSE 0 END) >= 1
             AND SUM(CASE WHEN cu.role_code = N'FINANCE' THEN 1 ELSE 0 END) >= 1
             AND SUM(CASE WHEN cu.role_code = N'QC' THEN 1 ELSE 0 END) >= 1
            THEN N'PASS — all 6 line roles have at least one assignment'
            ELSE N'WARNING — one or more line roles missing in erp_company_users'
        END AS result
    FROM dbo.erp_company_users cu
    WHERE cu.is_active = 1;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   3. Suggested demo usernames
-------------------------------------------------------------------------- */
PRINT N'--- 3. Suggested demo usernames (existence check) ---';
IF OBJECT_ID(N'dbo.core_users', N'U') IS NOT NULL
BEGIN
    ;WITH suggested(username) AS (
        SELECT N'demo.reception' UNION ALL
        SELECT N'demo.service.manager' UNION ALL
        SELECT N'demo.technician' UNION ALL
        SELECT N'demo.parts' UNION ALL
        SELECT N'demo.finance' UNION ALL
        SELECT N'demo.qc'
    )
    SELECT
        s.username,
        CASE WHEN u.user_id IS NULL THEN N'MISSING' ELSE N'EXISTS' END AS status,
        u.user_id,
        u.lifecycle_state,
        u.is_login_enabled
    FROM suggested s
    LEFT JOIN dbo.core_users u ON u.username = s.username
    ORDER BY s.username;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   4. M360-DEMO JobCard
-------------------------------------------------------------------------- */
PRINT N'--- 4. M360-DEMO JobCard ---';
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
    PRINT N'WARNING: dbo.erp_jobcards missing';
ELSE
BEGIN
    SELECT COUNT(*) AS m360_demo_jobcard_count
    FROM dbo.erp_jobcards
    WHERE jobcard_number LIKE N'M360-DEMO%';

    SELECT TOP 5
        jobcard_id,
        jobcard_number,
        jobcard_status,
        customer_id,
        vehicle_id,
        created_at
    FROM dbo.erp_jobcards
    WHERE jobcard_number LIKE N'M360-DEMO%'
    ORDER BY jobcard_id DESC;

    SELECT
        CASE WHEN EXISTS (SELECT 1 FROM dbo.erp_jobcards WHERE jobcard_number LIKE N'M360-DEMO%')
            THEN N'PASS — M360-DEMO JobCard found'
            ELSE N'WARNING — no M360-DEMO JobCard — create before dry run'
        END AS demo_jobcard_readiness;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   5. JobCard ID 1 (not recommended)
-------------------------------------------------------------------------- */
PRINT N'--- 5. JobCard ID 1 (informational — NOT recommended for dry run) ---';
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    SELECT
        jobcard_id,
        jobcard_number,
        jobcard_status,
        N'NOT RECOMMENDED — use fresh M360-DEMO per P11.9-1' AS dry_run_note
    FROM dbo.erp_jobcards
    WHERE jobcard_id = 1;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   6. Core roles / mappings
-------------------------------------------------------------------------- */
PRINT N'--- 6. core_roles first-wave keys ---';
IF OBJECT_ID(N'dbo.core_roles', N'U') IS NULL
    PRINT N'WARNING: dbo.core_roles missing';
ELSE
BEGIN
    SELECT role_key, role_name, is_active
    FROM dbo.core_roles
    WHERE role_key IN (
        N'owner', N'system_admin', N'reception_staff', N'operations_manager',
        N'mechanical_staff', N'inventory_staff', N'finance_staff', N'technical_manager'
    )
    ORDER BY role_key;
END;
PRINT N'';

/* --------------------------------------------------------------------------
   7. P1-P7 table existence
-------------------------------------------------------------------------- */
PRINT N'--- 7. P1-P7 related tables ---';
;WITH required_tables(table_name) AS (
    SELECT N'erp_customer_online_requests' UNION ALL
    SELECT N'erp_intake_contracts' UNION ALL
    SELECT N'erp_jobcards' UNION ALL
    SELECT N'erp_customers' UNION ALL
    SELECT N'erp_vehicles' UNION ALL
    SELECT N'erp_estimates' UNION ALL
    SELECT N'erp_final_invoices' UNION ALL
    SELECT N'erp_qc_checks'
)
SELECT
    r.table_name,
    CASE WHEN OBJECT_ID(N'dbo.' + r.table_name, N'U') IS NOT NULL THEN N'EXISTS' ELSE N'MISSING' END AS status
FROM required_tables r
ORDER BY r.table_name;
PRINT N'';

/* --------------------------------------------------------------------------
   8. M360-DEMO customer/vehicle markers
-------------------------------------------------------------------------- */
PRINT N'--- 8. M360-DEMO customer/vehicle markers ---';
IF OBJECT_ID(N'dbo.erp_customers', N'U') IS NOT NULL
    SELECT COUNT(*) AS m360_demo_customer_count
    FROM dbo.erp_customers
    WHERE customer_code LIKE N'M360-DEMO%' OR full_name LIKE N'M360 Demo%';

IF OBJECT_ID(N'dbo.erp_vehicles', N'U') IS NOT NULL
    SELECT COUNT(*) AS m360_demo_vehicle_count
    FROM dbo.erp_vehicles
    WHERE vehicle_code LIKE N'M360-DEMO%' OR plate_number LIKE N'M360-DEMO%';

PRINT N'';
PRINT N'=== Preflight complete — review WARNING rows before Go/No-Go ===';
GO
