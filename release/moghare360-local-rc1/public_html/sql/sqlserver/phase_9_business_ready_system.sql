/*
================================================================================
MOGHARE360 ERP — Phase 9 Business Ready System
Script: phase_9_business_ready_system.sql
================================================================================

Management reporting foundation: KPI snapshots, Soft Run audit checks,
report history. No official accounting/tax/final invoice.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_business_kpi_snapshots', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_business_kpi_snapshots
    (
        kpi_snapshot_id             BIGINT          NOT NULL IDENTITY(1, 1),
        snapshot_code               NVARCHAR(100)   NOT NULL,
        snapshot_scope              NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_biz_kpi_scope DEFAULT (N'GLOBAL'),
        operation_open_count        INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_op_open DEFAULT (0),
        operation_ready_count       INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_op_ready DEFAULT (0),
        operation_delivered_count   INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_op_del DEFAULT (0),
        waiting_approval_count      INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_wait_appr DEFAULT (0),
        waiting_parts_count         INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_wait_parts DEFAULT (0),
        unpaid_count                INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_unpaid DEFAULT (0),
        partial_paid_count          INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_partial DEFAULT (0),
        paid_count                  INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_paid DEFAULT (0),
        crm_pending_followup_count  INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_crm_pending DEFAULT (0),
        inventory_low_pressure_count INT            NOT NULL
            CONSTRAINT DF_erp_biz_kpi_inv_low DEFAULT (0),
        active_employee_count       INT             NOT NULL
            CONSTRAINT DF_erp_biz_kpi_hr_active DEFAULT (0),
        total_payable               DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_biz_kpi_payable DEFAULT (0),
        total_paid                  DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_biz_kpi_paid_amt DEFAULT (0),
        total_remaining             DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_biz_kpi_remain DEFAULT (0),
        readiness_score             DECIMAL(9, 2)   NOT NULL
            CONSTRAINT DF_erp_biz_kpi_ready DEFAULT (0),
        snapshot_note               NVARCHAR(1500)  NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_biz_kpi_created DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_business_kpi_snapshots PRIMARY KEY CLUSTERED (kpi_snapshot_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_audit_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_audit_checks
    (
        audit_check_id      BIGINT          NOT NULL IDENTITY(1, 1),
        check_code          NVARCHAR(100)   NOT NULL,
        check_group         NVARCHAR(100)   NOT NULL,
        check_title         NVARCHAR(300)   NOT NULL,
        check_status        NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_soft_run_audit_status DEFAULT (N'PENDING'),
        check_score         DECIMAL(9, 2)   NOT NULL
            CONSTRAINT DF_erp_soft_run_audit_score DEFAULT (0),
        check_note          NVARCHAR(1500)  NULL,
        checked_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_soft_run_audit_checked DEFAULT (SYSUTCDATETIME()),
        checked_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_soft_run_audit_checks PRIMARY KEY CLUSTERED (audit_check_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_management_report_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_management_report_history
    (
        report_history_id   BIGINT          NOT NULL IDENTITY(1, 1),
        report_code         NVARCHAR(100)   NOT NULL,
        report_type         NVARCHAR(100)   NOT NULL,
        report_title        NVARCHAR(300)   NOT NULL,
        report_status       NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_mgmt_report_status DEFAULT (N'GENERATED'),
        report_summary      NVARCHAR(2000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_mgmt_report_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_management_report_history PRIMARY KEY CLUSTERED (report_history_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_biz_kpi_snapshot_code' AND object_id = OBJECT_ID(N'dbo.erp_business_kpi_snapshots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_biz_kpi_snapshot_code ON dbo.erp_business_kpi_snapshots (snapshot_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_biz_kpi_snapshot_scope' AND object_id = OBJECT_ID(N'dbo.erp_business_kpi_snapshots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_biz_kpi_snapshot_scope ON dbo.erp_business_kpi_snapshots (snapshot_scope); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_soft_run_audit_check_code' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_audit_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_soft_run_audit_check_code ON dbo.erp_soft_run_audit_checks (check_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_soft_run_audit_check_group' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_audit_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_soft_run_audit_check_group ON dbo.erp_soft_run_audit_checks (check_group); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_soft_run_audit_check_status' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_audit_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_soft_run_audit_check_status ON dbo.erp_soft_run_audit_checks (check_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_mgmt_report_code' AND object_id = OBJECT_ID(N'dbo.erp_management_report_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_mgmt_report_code ON dbo.erp_management_report_history (report_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_mgmt_report_type' AND object_id = OBJECT_ID(N'dbo.erp_management_report_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_mgmt_report_type ON dbo.erp_management_report_history (report_type); END;
GO

/* Seed audit checks */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'CUSTOMER_CORE_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'CUSTOMER_CORE_READY', N'PHASE_1', N'Customer Core System', N'PENDING', 0, N'Seed — run audit after SQL', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'OPERATION_ENGINE_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'OPERATION_ENGINE_READY', N'PHASE_2', N'Operation Engine', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'RULE_ENGINE_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'RULE_ENGINE_READY', N'PHASE_3', N'Rule Engine', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'INVENTORY_PURCHASE_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'INVENTORY_PURCHASE_READY', N'PHASE_4', N'Inventory & Purchase', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'FINANCIAL_PREVIEW_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'FINANCIAL_PREVIEW_READY', N'PHASE_5', N'Financial Preview System', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'CRM_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'CRM_READY', N'PHASE_6', N'CRM System', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'HR_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'HR_READY', N'PHASE_7', N'HR & Internal Admin', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'UI_PRODUCTIZED')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'UI_PRODUCTIZED', N'PHASE_8', N'UI Productization Layer', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'BUSINESS_REPORTING_READY')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'BUSINESS_REPORTING_READY', N'PHASE_9', N'Business Reporting', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_audit_checks WHERE check_code = N'COMMERCIAL_PENDING')
INSERT INTO dbo.erp_soft_run_audit_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'COMMERCIAL_PENDING', N'PHASE_10', N'Commercial Readiness', N'PENDING', 0, N'Phase 10 not started', N'SYSTEM');
GO
