/*
================================================================================
MOGHARE360 ERP — WAVE 9B
Script: wave_9b_executive_go_no_go_decision_log.sql
================================================================================

Official SQL Server foundation for Executive Soft Run Go/No-Go Decision Log.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No table drops.

Internal executive Go/No-Go review decision log only — NOT final vehicle delivery.
NOT delivery completion. NOT legal e-signature. NOT payment/accounting.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_executive_soft_run_decisions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_executive_soft_run_decisions
    (
        decision_id INT IDENTITY(1,1) NOT NULL,
        decision_code NVARCHAR(80) NOT NULL,
        executive_readiness_status NVARCHAR(60) NOT NULL,
        wave6_status NVARCHAR(80) NULL,
        wave7_status NVARCHAR(80) NULL,
        wave8_status NVARCHAR(80) NULL,
        decision_type NVARCHAR(40) NOT NULL,
        decision_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_executive_soft_run_decisions_decision_status DEFAULT (N'RECORDED'),
        decision_title NVARCHAR(250) NOT NULL,
        decision_summary NVARCHAR(1500) NULL,
        management_reason NVARCHAR(1500) NOT NULL,
        required_action_summary NVARCHAR(1500) NULL,
        risk_note NVARCHAR(1500) NULL,
        finding_id INT NULL,
        pilot_execution_id INT NULL,
        decided_by_user_id INT NULL,
        decision_due_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_executive_soft_run_decisions_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2 NULL,
        created_by_user_id INT NULL,
        updated_by_user_id INT NULL,

        CONSTRAINT PK_erp_executive_soft_run_decisions
            PRIMARY KEY CLUSTERED (decision_id ASC),

        CONSTRAINT UQ_erp_executive_soft_run_decisions_decision_code
            UNIQUE (decision_code),

        CONSTRAINT CK_erp_executive_soft_run_decisions_decision_type
            CHECK (decision_type IN (
                N'GO_REVIEW', N'CONDITIONAL_GO', N'HOLD', N'NO_GO', N'REVIEW_REQUIRED'
            )),

        CONSTRAINT CK_erp_executive_soft_run_decisions_decision_status
            CHECK (decision_status IN (
                N'RECORDED', N'UNDER_REVIEW', N'ACTION_REQUIRED', N'ACCEPTED', N'CLOSED', N'CANCELLED'
            )),

        CONSTRAINT CK_erp_executive_soft_run_decisions_executive_readiness_status
            CHECK (executive_readiness_status IN (
                N'EXECUTIVE_REVIEW_READY',
                N'GO_REVIEW_REQUIRED',
                N'BLOCKED',
                N'EMPTY',
                N'ERROR'
            )),

        CONSTRAINT CK_erp_executive_soft_run_decisions_decision_title
            CHECK (LEN(LTRIM(RTRIM(decision_title))) > 0),

        CONSTRAINT CK_erp_executive_soft_run_decisions_management_reason
            CHECK (LEN(LTRIM(RTRIM(management_reason))) > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_executive_soft_run_decision_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_executive_soft_run_decision_history
    (
        history_id INT IDENTITY(1,1) NOT NULL,
        decision_id INT NOT NULL,
        old_decision_status NVARCHAR(40) NULL,
        new_decision_status NVARCHAR(40) NOT NULL,
        old_decision_type NVARCHAR(40) NULL,
        new_decision_type NVARCHAR(40) NULL,
        change_reason NVARCHAR(1000) NULL,
        changed_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_executive_soft_run_decision_history_changed_at DEFAULT (SYSUTCDATETIME()),
        changed_by_user_id INT NULL,

        CONSTRAINT PK_erp_executive_soft_run_decision_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_executive_soft_run_decision_history_new_decision_status
            CHECK (LEN(LTRIM(RTRIM(new_decision_status))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_executive_soft_run_decision_history_decision'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decision_history')
)
BEGIN
    ALTER TABLE dbo.erp_executive_soft_run_decision_history
        ADD CONSTRAINT FK_erp_executive_soft_run_decision_history_decision
            FOREIGN KEY (decision_id)
            REFERENCES dbo.erp_executive_soft_run_decisions(decision_id);
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_findings', N'U') IS NOT NULL
AND NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_executive_soft_run_decisions_finding'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decisions')
)
BEGIN
    ALTER TABLE dbo.erp_executive_soft_run_decisions
        ADD CONSTRAINT FK_erp_executive_soft_run_decisions_finding
            FOREIGN KEY (finding_id)
            REFERENCES dbo.erp_soft_run_findings(finding_id);
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_executions', N'U') IS NOT NULL
AND NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_executive_soft_run_decisions_pilot_execution'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decisions')
)
BEGIN
    ALTER TABLE dbo.erp_executive_soft_run_decisions
        ADD CONSTRAINT FK_erp_executive_soft_run_decisions_pilot_execution
            FOREIGN KEY (pilot_execution_id)
            REFERENCES dbo.erp_soft_run_pilot_executions(execution_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_executive_soft_run_decisions_decision_status'
      AND object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decisions')
)
BEGIN
    CREATE INDEX IX_erp_executive_soft_run_decisions_decision_status
        ON dbo.erp_executive_soft_run_decisions(decision_status);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_executive_soft_run_decisions_decision_type'
      AND object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decisions')
)
BEGIN
    CREATE INDEX IX_erp_executive_soft_run_decisions_decision_type
        ON dbo.erp_executive_soft_run_decisions(decision_type);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_executive_soft_run_decision_history_decision_id'
      AND object_id = OBJECT_ID(N'dbo.erp_executive_soft_run_decision_history')
)
BEGIN
    CREATE INDEX IX_erp_executive_soft_run_decision_history_decision_id
        ON dbo.erp_executive_soft_run_decision_history(decision_id);
END;
GO

SELECT
    'WAVE_9B_EXECUTIVE_GO_NO_GO_DECISION_LOG_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_executive_soft_run_decisions', N'U') AS decisions_table_object_id,
    OBJECT_ID(N'dbo.erp_executive_soft_run_decision_history', N'U') AS history_table_object_id;
GO
