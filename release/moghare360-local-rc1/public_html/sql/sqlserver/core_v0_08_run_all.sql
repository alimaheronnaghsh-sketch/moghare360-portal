/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Runbook/Orchestrator: core_v0_08_run_all.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

WARNING:
  - Do NOT run on Production without explicit manual approval, backup, and
    change control.
  - SQLCMD mode MUST be enabled in SSMS before running this script.
    (SSMS: Query -> SQLCMD Mode)
  - Existing databases must NOT be dropped or altered.
  - This file does NOT create tables directly. It only includes other scripts.

Execution method:
  - Uses SQLCMD :r include directives with relative paths (same folder).

Design reference:
  docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md
================================================================================
*/

/* =============================================================================
   1) Create database moghare360_ERP (idempotent; safe)
   - Creates DB only if missing
   - Collation: Persian_100_CI_AS
   - Does not drop/alter other databases
============================================================================= */
:r .\core_v0_01_create_database.sql

/* =============================================================================
   2) Create Version 0 master tables (identity/org/roles/permissions)
   - core_users, core_departments, core_roles, core_permissions, core_positions,
     core_role_permissions, core_staff_profiles
============================================================================= */
:r .\core_v0_02_master_tables.sql

/* =============================================================================
   3) Create Version 0 workflow tables (requests/approvals/effective assignments)
   - core_access_requests, core_access_request_items, core_access_approvals,
     core_user_roles, core_access_suspensions, core_access_restrictions
============================================================================= */
:r .\core_v0_03_workflow_tables.sql

/* =============================================================================
   4) Create Version 0 immutable history and audit tables
   - core_access_change_history, core_audit_logs
============================================================================= */
:r .\core_v0_04_history_audit_tables.sql

/* =============================================================================
   5) Seed organization foundation (departments + positions)
   - Seeds 14 departments and base positions
============================================================================= */
:r .\core_v0_05_seed_org.sql

/* =============================================================================
   6) Seed staff roles + permissions + role-permission matrix (NO users)
   - Seeds core_roles, core_permissions, core_role_permissions
   - Does NOT seed CUSTOMER access level
============================================================================= */
:r .\core_v0_06_seed_roles_permissions.sql

/* =============================================================================
   7) Seed approval rule matrix for workflow validation
   - Creates/seeds core_access_approval_rules
============================================================================= */
:r .\core_v0_07_seed_approval_rules.sql

/* =============================================================================
   Final validation queries (post-run)
============================================================================= */

/* Database exists + collation check */
SELECT
    d.name           AS database_name,
    d.collation_name AS collation_name,
    d.create_date    AS create_date
FROM sys.databases AS d
WHERE d.name = N'moghare360_ERP';

USE [moghare360_ERP];
GO

/* Count core tables */
SELECT
    COUNT(*) AS core_table_count
FROM sys.tables AS t
WHERE SCHEMA_NAME(t.schema_id) = N'dbo'
  AND t.name LIKE N'core[_]%';

/* Seed counts */
SELECT COUNT(*) AS departments_count FROM dbo.core_departments;
SELECT COUNT(*) AS positions_count FROM dbo.core_positions;
SELECT COUNT(*) AS roles_count FROM dbo.core_roles;
SELECT COUNT(*) AS permissions_count FROM dbo.core_permissions;
SELECT COUNT(*) AS role_permissions_count FROM dbo.core_role_permissions;

/* Approval rules count (table created by v0_07 script) */
IF OBJECT_ID(N'dbo.core_access_approval_rules', N'U') IS NOT NULL
    SELECT COUNT(*) AS approval_rules_count FROM dbo.core_access_approval_rules;
ELSE
    SELECT CAST(0 AS INT) AS approval_rules_count;

/* Verify no CUSTOMER role/access-level is present in Version 0 seed */
SELECT
    COUNT(*) AS customer_access_level_roles_count
FROM dbo.core_roles
WHERE access_level = N'CUSTOMER'
   OR role_key = N'customer';

GO

