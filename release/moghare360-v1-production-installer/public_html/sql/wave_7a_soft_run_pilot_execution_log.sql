/*
================================================================================
MOGHARE360 ERP — WAVE 7A
Script: wave_7a_soft_run_pilot_execution_log.sql
================================================================================

Official SQL Server foundation for Soft Run Pilot Execution Log.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No table drops.

Internal Soft Run pilot execution log only — NOT final vehicle delivery.
NOT delivery completion. NOT legal e-signature. NOT payment/accounting.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_executions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_executions
    (
        execution_id INT IDENTITY(1,1) NOT NULL,
        execution_code NVARCHAR(80) NOT NULL,
        jobcard_id INT NULL,
        scenario_key NVARCHAR(120) NOT NULL,
        scenario_title NVARCHAR(250) NOT NULL,
        operator_user_id INT NULL,
        execution_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_soft_run_pilot_executions_execution_status DEFAULT (N'DRAFT'),
        evidence_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_soft_run_pilot_executions_evidence_status DEFAULT (N'PENDING_REVIEW'),
        result_status NVARCHAR(40) NOT NULL
            CONSTRAINT DF_erp_soft_run_pilot_executions_result_status DEFAULT (N'NOT_EVALUATED'),
        observed_summary NVARCHAR(1000) NULL,
        expected_evidence NVARCHAR(1000) NULL,
        actual_evidence NVARCHAR(1000) NULL,
        blocker_notes NVARCHAR(1000) NULL,
        internal_notes NVARCHAR(1000) NULL,
        started_at DATETIME2 NULL,
        completed_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_soft_run_pilot_executions_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2 NULL,
        created_by_user_id INT NULL,
        updated_by_user_id INT NULL,

        CONSTRAINT PK_erp_soft_run_pilot_executions
            PRIMARY KEY CLUSTERED (execution_id ASC),

        CONSTRAINT UQ_erp_soft_run_pilot_executions_execution_code
            UNIQUE (execution_code),

        CONSTRAINT CK_erp_soft_run_pilot_executions_execution_status
            CHECK (execution_status IN (
                N'DRAFT', N'STARTED', N'OBSERVED', N'PASSED', N'FAILED', N'BLOCKED', N'CANCELLED'
            )),

        CONSTRAINT CK_erp_soft_run_pilot_executions_evidence_status
            CHECK (evidence_status IN (
                N'PENDING_REVIEW', N'VISIBLE', N'MISSING', N'NOT_REQUIRED'
            )),

        CONSTRAINT CK_erp_soft_run_pilot_executions_result_status
            CHECK (result_status IN (
                N'NOT_EVALUATED', N'PASS', N'FAIL', N'BLOCKED', N'NEEDS_REVIEW'
            )),

        CONSTRAINT CK_erp_soft_run_pilot_executions_scenario_key
            CHECK (LEN(LTRIM(RTRIM(scenario_key))) > 0),

        CONSTRAINT CK_erp_soft_run_pilot_executions_scenario_title
            CHECK (LEN(LTRIM(RTRIM(scenario_title))) > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_execution_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_execution_history
    (
        history_id INT IDENTITY(1,1) NOT NULL,
        execution_id INT NOT NULL,
        old_execution_status NVARCHAR(40) NULL,
        new_execution_status NVARCHAR(40) NOT NULL,
        old_result_status NVARCHAR(40) NULL,
        new_result_status NVARCHAR(40) NULL,
        change_reason NVARCHAR(1000) NULL,
        changed_at DATETIME2 NOT NULL
            CONSTRAINT DF_erp_soft_run_pilot_execution_history_changed_at DEFAULT (SYSUTCDATETIME()),
        changed_by_user_id INT NULL,

        CONSTRAINT PK_erp_soft_run_pilot_execution_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_soft_run_pilot_execution_history_new_execution_status
            CHECK (LEN(LTRIM(RTRIM(new_execution_status))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_soft_run_pilot_execution_history_execution'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_execution_history')
)
BEGIN
    ALTER TABLE dbo.erp_soft_run_pilot_execution_history
        ADD CONSTRAINT FK_erp_soft_run_pilot_execution_history_execution
            FOREIGN KEY (execution_id)
            REFERENCES dbo.erp_soft_run_pilot_executions(execution_id);
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
AND NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_soft_run_pilot_executions_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_executions')
)
BEGIN
    ALTER TABLE dbo.erp_soft_run_pilot_executions
        ADD CONSTRAINT FK_erp_soft_run_pilot_executions_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_pilot_executions_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_executions')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_pilot_executions_jobcard_id
        ON dbo.erp_soft_run_pilot_executions(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_pilot_executions_execution_status'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_executions')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_pilot_executions_execution_status
        ON dbo.erp_soft_run_pilot_executions(execution_status);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_soft_run_pilot_execution_history_execution_id'
      AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_execution_history')
)
BEGIN
    CREATE INDEX IX_erp_soft_run_pilot_execution_history_execution_id
        ON dbo.erp_soft_run_pilot_execution_history(execution_id);
END;
GO

SELECT
    'WAVE_7A_SOFT_RUN_PILOT_EXECUTION_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_soft_run_pilot_executions', N'U') AS executions_table_object_id,
    OBJECT_ID(N'dbo.erp_soft_run_pilot_execution_history', N'U') AS history_table_object_id;
GO
