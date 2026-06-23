/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_07_seed_approval_rules.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

This script defines the approval-rule matrix for access lifecycle request types.

Important:
  - Approval rules do NOT grant access to users.
  - Approval rules are used ONLY by workflow validation logic (who must approve).
  - This script does NOT create users, does NOT assign roles, does NOT create
    access requests, and does NOT modify any existing data outside its table.

Design reference:
  docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md
Policy reference:
  docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md

Idempotent:
  - Creates dbo.core_access_approval_rules if missing.
  - MERGE seeds rule rows; no DELETE/TRUNCATE.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   Create table if missing
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_approval_rules', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_approval_rules
    (
        approval_rule_id     BIGINT IDENTITY(1, 1)   NOT NULL,
        request_type         NVARCHAR(40)            NOT NULL,
        approver_capacity    NVARCHAR(40)            NOT NULL,
        required_order       INT                     NOT NULL,
        is_required          BIT                     NOT NULL
            CONSTRAINT DF_core_access_approval_rules_is_required DEFAULT (1),
        is_active            BIT                     NOT NULL
            CONSTRAINT DF_core_access_approval_rules_is_active DEFAULT (1),
        description          NVARCHAR(500)           NULL,
        created_at           DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_approval_rules_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at           DATETIME2(3)            NULL,
        CONSTRAINT PK_core_access_approval_rules PRIMARY KEY CLUSTERED (approval_rule_id),
        CONSTRAINT UQ_core_access_approval_rules_unique
            UNIQUE (request_type, approver_capacity, required_order),
        CONSTRAINT CK_core_access_approval_rules_request_type CHECK (
            request_type IN (
                N'ONBOARDING', N'ROLE_GRANT', N'TEMPORARY_ROLE_GRANT',
                N'ACCESS_UPGRADE', N'ACCESS_DOWNGRADE', N'PROMOTION',
                N'SUSPENSION', N'ACCESS_RESTRICTION', N'OFFBOARDING',
                N'EMERGENCY'
            )
        ),
        CONSTRAINT CK_core_access_approval_rules_capacity CHECK (
            approver_capacity IN (
                N'DEPARTMENT_MANAGER', N'SYSTEM_ADMIN', N'OPERATIONS_MANAGER',
                N'HR_MANAGER', N'OWNER'
            )
        ),
        CONSTRAINT CK_core_access_approval_rules_order CHECK (required_order >= 1)
    );

    CREATE INDEX IX_core_access_approval_rules_request_type
        ON dbo.core_access_approval_rules (request_type, required_order);

    CREATE INDEX IX_core_access_approval_rules_capacity
        ON dbo.core_access_approval_rules (approver_capacity);

    CREATE INDEX IX_core_access_approval_rules_is_active
        ON dbo.core_access_approval_rules (is_active);

    PRINT N'Created table dbo.core_access_approval_rules';
END
ELSE
    PRINT N'Table dbo.core_access_approval_rules already exists — skipped create.';
GO

/* ----------------------------------------------------------------------------
   Seed approval rules (Version 0)
---------------------------------------------------------------------------- */
MERGE dbo.core_access_approval_rules AS tgt
USING (
    VALUES
        /* 1) ONBOARDING */
        (N'ONBOARDING',          N'DEPARTMENT_MANAGER', 1, 1, 1, N'ورود نیروی جدید: ابتدا مدیر واحد'),
        (N'ONBOARDING',          N'SYSTEM_ADMIN',       2, 1, 1, N'ورود نیروی جدید: سپس ادمین سیستم'),

        /* 2) ROLE_GRANT */
        (N'ROLE_GRANT',          N'DEPARTMENT_MANAGER', 1, 1, 1, N'اعطای نقش: مدیر واحد'),

        /* 3) TEMPORARY_ROLE_GRANT */
        (N'TEMPORARY_ROLE_GRANT',N'DEPARTMENT_MANAGER', 1, 1, 1, N'دسترسی موقت: مدیر واحد'),

        /* 4) ACCESS_UPGRADE */
        (N'ACCESS_UPGRADE',      N'DEPARTMENT_MANAGER', 1, 1, 1, N'افزایش دسترسی: ابتدا مدیر واحد'),
        (N'ACCESS_UPGRADE',      N'SYSTEM_ADMIN',       2, 1, 1, N'افزایش دسترسی: سپس ادمین سیستم'),

        /* 5) ACCESS_DOWNGRADE */
        (N'ACCESS_DOWNGRADE',    N'DEPARTMENT_MANAGER', 1, 1, 1, N'کاهش دسترسی: مدیر واحد'),

        /* 6) PROMOTION */
        (N'PROMOTION',           N'DEPARTMENT_MANAGER', 1, 1, 1, N'ارتقای سمت: ابتدا مدیر واحد'),
        (N'PROMOTION',           N'OPERATIONS_MANAGER', 2, 1, 1, N'ارتقای سمت: سپس مدیر عملیات'),

        /* 7) SUSPENSION */
        (N'SUSPENSION',          N'DEPARTMENT_MANAGER', 1, 1, 1, N'تعلیق دسترسی: ابتدا مدیر واحد'),
        (N'SUSPENSION',          N'SYSTEM_ADMIN',       2, 1, 1, N'تعلیق دسترسی: سپس ادمین سیستم'),

        /* 8) ACCESS_RESTRICTION */
        (N'ACCESS_RESTRICTION',  N'DEPARTMENT_MANAGER', 1, 1, 1, N'محدودسازی (خطا/تخلف): ابتدا مدیر واحد'),
        (N'ACCESS_RESTRICTION',  N'SYSTEM_ADMIN',       2, 1, 1, N'محدودسازی (خطا/تخلف): سپس ادمین سیستم'),

        /* 9) OFFBOARDING */
        (N'OFFBOARDING',         N'HR_MANAGER',         1, 1, 1, N'خروج نیرو: ابتدا مدیر اداری/منابع انسانی'),
        (N'OFFBOARDING',         N'SYSTEM_ADMIN',       2, 1, 1, N'خروج نیرو: سپس ادمین سیستم'),

        /* 10) EMERGENCY */
        (N'EMERGENCY',           N'OWNER',              1, 1, 1, N'اضطراری: مالک سیستم (خودتأیید با ثبت دلیل)')
) AS src (request_type, approver_capacity, required_order, is_required, is_active, description)
    ON tgt.request_type = src.request_type
   AND tgt.approver_capacity = src.approver_capacity
   AND tgt.required_order = src.required_order
WHEN NOT MATCHED BY TARGET THEN
    INSERT (request_type, approver_capacity, required_order, is_required, is_active, description, created_at)
    VALUES (src.request_type, src.approver_capacity, src.required_order, src.is_required, src.is_active, src.description, SYSUTCDATETIME())
WHEN MATCHED AND (
        tgt.is_required <> src.is_required
        OR tgt.is_active <> src.is_active
        OR ISNULL(tgt.description, N'') <> ISNULL(src.description, N'')
    ) THEN
    UPDATE SET
        tgt.is_required = src.is_required,
        tgt.is_active = src.is_active,
        tgt.description = src.description,
        tgt.updated_at = SYSUTCDATETIME();
GO

/* ----------------------------------------------------------------------------
   Final verification
---------------------------------------------------------------------------- */
SELECT
    approval_rule_id,
    request_type,
    approver_capacity,
    required_order,
    is_required,
    is_active,
    description,
    created_at,
    updated_at
FROM dbo.core_access_approval_rules
ORDER BY request_type, required_order, approver_capacity;
GO

