/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_05_seed_org.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Seeds Version 0 organization foundation:
  - core_departments (14 internal units)
  - core_positions (Manager, Staff, and department-specific roles)

Does NOT seed: users, roles, permissions, manager_user_id, suggested_role_id.

Design reference:
  docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md
  docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md

Prerequisites: core_v0_02_master_tables.sql
Idempotent: MERGE by dept_key / (department_id + position_key). No DELETE/TRUNCATE.
Does NOT touch: moghare360, moghare360_StockCenter, moghare360D
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. core_departments
---------------------------------------------------------------------------- */
MERGE dbo.core_departments AS tgt
USING (
    VALUES
        (N'executive_management',    N'مدیریت ارشد',              10),
        (N'operations',             N'عملیات',                   20),
        (N'reception',              N'پذیرش',                    30),
        (N'crm',                      N'ارتباط با مشتریان',        40),
        (N'mechanical',             N'مکانیک',                   50),
        (N'electrical_options',     N'برق و آپشن',               60),
        (N'suspension_undercarriage', N'زیروبند و تعلیق',        70),
        (N'technical_management',   N'مدیریت فنی',               80),
        (N'inventory',              N'انبار',                    90),
        (N'purchase',               N'خرید',                    100),
        (N'finance',                N'مالی',                    110),
        (N'hr',                     N'منابع انسانی و اداری',    120),
        (N'marketing_sales',        N'بازاریابی و فروش',        130),
        (N'system_administration',  N'مدیریت سیستم',            140)
) AS src (dept_key, dept_name, sort_order)
    ON tgt.dept_key = src.dept_key
WHEN NOT MATCHED BY TARGET THEN
    INSERT (dept_key, dept_name, is_active, sort_order, created_at)
    VALUES (src.dept_key, src.dept_name, 1, src.sort_order, SYSUTCDATETIME())
WHEN MATCHED AND (
        tgt.dept_name <> src.dept_name
        OR tgt.sort_order <> src.sort_order
        OR tgt.is_active <> 1
    ) THEN
    UPDATE SET
        tgt.dept_name   = src.dept_name,
        tgt.sort_order  = src.sort_order,
        tgt.is_active   = 1,
        tgt.updated_at  = SYSUTCDATETIME();
GO

/* ----------------------------------------------------------------------------
   2. core_positions
---------------------------------------------------------------------------- */
MERGE dbo.core_positions AS tgt
USING (
    SELECT
        d.department_id,
        p.position_key,
        p.position_name,
        p.sort_order
    FROM (
        VALUES
            /* Executive Management */
            (N'executive_management',   N'owner',                   N'مالک',                         5),
            (N'executive_management',   N'general_manager',         N'مدیر کل',                     8),
            (N'executive_management',   N'manager',                 N'مدیر واحد',                  10),
            (N'executive_management',   N'staff',                   N'کارشناس / پرسنل',             90),

            /* Operations */
            (N'operations',             N'operations_manager',      N'مدیر عملیات',                8),
            (N'operations',             N'manager',                 N'مدیر واحد',                  10),
            (N'operations',             N'staff',                   N'کارشناس / پرسنل',             90),

            /* Reception */
            (N'reception',              N'reception_officer',         N'پذیرشگر',                    8),
            (N'reception',              N'manager',                 N'مدیر واحد',                  10),
            (N'reception',              N'staff',                   N'کارشناس / پرسنل',             90),

            /* CRM */
            (N'crm',                    N'crm_officer',             N'کارشناس ارتباط با مشتریان',  8),
            (N'crm',                    N'manager',                 N'مدیر واحد',                  10),
            (N'crm',                    N'staff',                   N'کارشناس / پرسنل',             90),

            /* Mechanical */
            (N'mechanical',             N'master_mechanic',         N'استادکار',                   8),
            (N'mechanical',             N'manager',                 N'مدیر واحد',                  10),
            (N'mechanical',             N'staff',                   N'کارشناس / پرسنل',             90),

            /* Electrical and Options */
            (N'electrical_options',     N'electrical_specialist',   N'کارشناس برق و آپشن',         8),
            (N'electrical_options',     N'manager',                 N'مدیر واحد',                  10),
            (N'electrical_options',     N'staff',                   N'کارشناس / پرسنل',             90),

            /* Suspension and Undercarriage */
            (N'suspension_undercarriage', N'suspension_specialist', N'کارشناس زیروبند و تعلیق',    8),
            (N'suspension_undercarriage', N'manager',               N'مدیر واحد',                  10),
            (N'suspension_undercarriage', N'staff',                 N'کارشناس / پرسنل',             90),

            /* Technical Management */
            (N'technical_management',   N'technical_manager',       N'مدیر فنی',                   8),
            (N'technical_management',   N'manager',                 N'مدیر واحد',                  10),
            (N'technical_management',   N'staff',                   N'کارشناس / پرسنل',             90),

            /* Inventory */
            (N'inventory',              N'warehouse_officer',         N'انباردار',                   8),
            (N'inventory',              N'manager',                 N'مدیر واحد',                  10),
            (N'inventory',              N'staff',                   N'کارشناس / پرسنل',             90),

            /* Purchase */
            (N'purchase',               N'purchase_officer',          N'کارشناس خرید',               8),
            (N'purchase',               N'manager',                 N'مدیر واحد',                  10),
            (N'purchase',               N'staff',                   N'کارشناس / پرسنل',             90),

            /* Finance */
            (N'finance',                N'finance_officer',           N'کارشناس مالی',               8),
            (N'finance',                N'manager',                 N'مدیر واحد',                  10),
            (N'finance',                N'staff',                   N'کارشناس / پرسنل',             90),

            /* HR */
            (N'hr',                     N'hr_officer',                N'کارشناس منابع انسانی',       8),
            (N'hr',                     N'manager',                 N'مدیر واحد',                  10),
            (N'hr',                     N'staff',                   N'کارشناس / پرسنل',             90),

            /* Marketing and Sales */
            (N'marketing_sales',        N'marketing_officer',         N'کارشناس بازاریابی و فروش',  8),
            (N'marketing_sales',        N'manager',                 N'مدیر واحد',                  10),
            (N'marketing_sales',        N'staff',                   N'کارشناس / پرسنل',             90),

            /* System Administration */
            (N'system_administration',  N'system_admin',              N'ادمین سیستم',                8),
            (N'system_administration',  N'manager',                 N'مدیر واحد',                  10),
            (N'system_administration',  N'staff',                   N'کارشناس / پرسنل',             90)
    ) AS p (dept_key, position_key, position_name, sort_order)
    INNER JOIN dbo.core_departments AS d
        ON d.dept_key = p.dept_key
) AS src
    ON tgt.department_id = src.department_id
   AND tgt.position_key = src.position_key
WHEN NOT MATCHED BY TARGET THEN
    INSERT (department_id, position_key, position_name, is_active, sort_order, created_at)
    VALUES (src.department_id, src.position_key, src.position_name, 1, src.sort_order, SYSUTCDATETIME())
WHEN MATCHED AND (
        tgt.position_name <> src.position_name
        OR tgt.sort_order <> src.sort_order
        OR tgt.is_active <> 1
    ) THEN
    UPDATE SET
        tgt.position_name = src.position_name,
        tgt.sort_order    = src.sort_order,
        tgt.is_active     = 1,
        tgt.updated_at    = SYSUTCDATETIME();
GO

/* ----------------------------------------------------------------------------
   Verification — departments
---------------------------------------------------------------------------- */
SELECT
    department_id,
    dept_key,
    dept_name,
    is_active,
    sort_order
FROM dbo.core_departments
ORDER BY sort_order, department_id;
GO

/* ----------------------------------------------------------------------------
   Verification — positions with department names
---------------------------------------------------------------------------- */
SELECT
    p.position_id,
    d.dept_key,
    d.dept_name,
    p.position_key,
    p.position_name,
    p.is_active,
    p.sort_order
FROM dbo.core_positions AS p
INNER JOIN dbo.core_departments AS d
    ON d.department_id = p.department_id
ORDER BY d.sort_order, p.sort_order, p.position_id;
GO
