/*
================================================================================
MOGHARE360 ERP — WAVE 8A
Script: wave_8a_soft_run_findings_register.sql
================================================================================

Official SQL Server foundation for Soft Run Findings & Corrective Action Register.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No table drops.

Internal Soft Run findings/corrective action log only — NOT final vehicle delivery.
NOT delivery completion. NOT legal e-signature. NOT payment/accounting.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_findings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_findings
    (
        finding_id INT IDENTITY(1,1) NOT NULL,
        finding_code NVARCHAR(80) NOT NULL,
        execution_id INT NULL,
        jobcard_id INT NULL,
        finding_type NVARCHAR(40) NOT NULL,
        severity_level NVARCHAR(40) NOT NULL,
        finding_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_soft_run_findings_finding_status DEFAULT (N'OPEN'),
        corrective_action_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_soft_run_findings_corrective_action_status DEFAULT (N'NOT_STARTED'),
        finding_title NVARCHAR(250) NOT NULL,
        finding_description NVARCHAR(1500) NULL,
        expected_behavior NVARCHAR(1000) NULL,
        actual_behavior NVARCHAR(1000) NULL,
        corrective_action NVARCHAR(1500) NULL,
        owner_user_id INT NULL,
        due_at DATETIME2 NULL,
        resolved_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_soft_run_findings_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2 NULL,
        created_by_user_id INT NULL,
        updated_by_user_id INT NULL,

        CONSTRAINT PK_erp_soft_run_findings
            PRIMARY KEY CLUSTERED (finding_id ASC),

        CONSTRAINT UQ_erp_soft_run_findings_finding_code
            UNIQUE (finding_code),

        CONSTRAINT CK_erp_soft_run_findings_finding_type
            CHECK (finding_type IN (
                N'ISSUE', N'BUG', N'OBSERVATION', N'RISK', N'PROCESS_GAP', N'TRAINING_NEED'
            )),

        CONSTRAINT CK_erp_soft_run_findings_severity_level
            CHECK (severity_level IN (
                N'LOW', N'MEDIUM', N'HIGH', N'CRITICAL'
            )),

        CONSTRAINT CK_erp_soft_run_findings_finding_status
            CHECK (finding_status IN (
                N'OPEN', N'UNDER_REVIEW', N'ACTION_REQUIRED', N'RESOLVED', N'CLOSED', N'CANCELLED'
            )),

        CONSTRAINT CK_erp_soft_run_findings_corrective_action_status
            CHECK (corrective_action_status IN (
                N'NOT_STARTED', N'IN_PROGRESS', N'DONE', N'NOT_REQUIRED', N'BLOCKED'
            )),

        CONSTRAINT CK_erp_soft_run_findings_finding_title
            CHECK (LEN(LTRIM(RTRIM(finding_title))) > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_finding_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_finding_history
    (
        history_id INT IDENTITY(1,1) NOT NULL,
        finding_id INT NOT NULL,
        old_finding_status NVARCHAR(40) NULL,
        new_finding_status NVARCHAR(40) NOT NULL,
        old_corrective_action_status NVARCHAR(40) NULL,
        new_corrective_action_status NVARCHAR(40) NULL,
        change_reason NVARCHAR(1000) NULL,
        changed_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_soft_run_finding_history_changed_at DEFAULT (SYSUTCDATETIME()),
        changed_by_user_id INT NULL,

        CONSTRAINT PK_erp_soft_run_finding_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_soft_run_finding_history_new_finding_status
            CHECK (LEN(LTRIM(RTRIM(new_finding_status))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_soft_run_finding_history_finding'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_soft_run_finding_history')
)
BEGIN
    ALTER TABLE dbo.erp_soft_run_finding_history
        ADD CONSTRAINT FK_erp_soft_run_finding_history_finding
            FOREIGN KEY (finding_id)
            REFERENCES dbo.erp_soft_run_findings(finding_id);
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_executions', N'U') IS NOT NULL
AND NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_soft_run_findings_execution'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_soft_run_findings')
)
BEGIN
    ALTER TABLE dbo.erp_soft_run_findings
        ADD CONSTRAINT FK_erp_soft_run_findings_execution
            FOREIGN KEY (execution_id)
            REFERENCES dbo.erp_soft_run_pilot_executions(execution_id);
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
AND NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_soft_run_findings_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_soft_run_findings')
)
BEGIN
    ALTER TABLE dbo.erp_soft_run_findings
        ADD CONSTRAINT FK_erp_soft_run_findings_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_findings_execution_id'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_findings')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_findings_execution_id
        ON dbo.erp_soft_run_findings(execution_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_findings_finding_status'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_findings')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_findings_finding_status
        ON dbo.erp_soft_run_findings(finding_status);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_finding_history_finding_id'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_finding_history')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_finding_history_finding_id
        ON dbo.erp_soft_run_finding_history(finding_id);
END;
GO

SELECT
    'WAVE_8A_SOFT_RUN_FINDINGS_REGISTER_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_soft_run_findings', N'U') AS findings_table_object_id,
    OBJECT_ID(N'dbo.erp_soft_run_finding_history', N'U') AS history_table_object_id;
GO
