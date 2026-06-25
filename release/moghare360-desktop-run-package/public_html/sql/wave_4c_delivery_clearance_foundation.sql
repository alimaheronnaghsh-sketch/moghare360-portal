/*
================================================================================
MOGHARE360 ERP — WAVE 4C
Script: wave_4c_delivery_clearance_foundation.sql
================================================================================

Official SQL Server foundation for JobCard Delivery Clearance runtime.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No table drops.

Internal delivery clearance only — NOT final vehicle delivery.
NOT legal final e-signature. NOT payment/accounting/public portal.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
BEGIN
    THROW 54000, 'Required table dbo.erp_jobcards was not found. WAVE 4C stopped safely.', 1;
END;

DECLARE @jobcard_id_type_name NVARCHAR(128);

SELECT @jobcard_id_type_name = t.name
FROM sys.columns c
INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
WHERE c.object_id = OBJECT_ID(N'dbo.erp_jobcards')
  AND c.name = N'jobcard_id';

IF @jobcard_id_type_name IS NULL
BEGIN
    THROW 54001, 'Required column dbo.erp_jobcards.jobcard_id was not found. WAVE 4C stopped safely.', 1;
END;

IF @jobcard_id_type_name <> N'int'
BEGIN
    THROW 54002, 'dbo.erp_jobcards.jobcard_id must be INT for FK compatibility. WAVE 4C stopped safely.', 1;
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_delivery_clearances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_delivery_clearances
    (
        clearance_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id INT NOT NULL,

        clearance_status NVARCHAR(50) NOT NULL,
        clearance_decision NVARCHAR(80) NOT NULL,
        reviewer_name NVARCHAR(200) NOT NULL,
        clearance_note NVARCHAR(2000) NULL,

        is_active BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_delivery_clearances_is_active DEFAULT (1),
        is_deleted BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_delivery_clearances_is_deleted DEFAULT (0),

        created_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_delivery_clearances_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_delivery_clearances_updated_at DEFAULT (SYSUTCDATETIME()),

        CONSTRAINT PK_erp_jobcard_delivery_clearances
            PRIMARY KEY CLUSTERED (clearance_id ASC),

        CONSTRAINT CK_erp_jobcard_delivery_clearances_status
            CHECK (clearance_status IN (
                N'draft',
                N'clearance_requested',
                N'cleared',
                N'not_cleared',
                N'cancelled'
            )),

        CONSTRAINT CK_erp_jobcard_delivery_clearances_decision
            CHECK (clearance_decision IN (
                N'eligible_for_delivery_review',
                N'cleared_for_delivery_process',
                N'not_cleared_missing_requirements',
                N'cancelled_by_internal_review'
            )),

        CONSTRAINT CK_erp_jobcard_delivery_clearances_reviewer_name
            CHECK (LEN(LTRIM(RTRIM(reviewer_name))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_delivery_clearances_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearances')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_delivery_clearances
        ADD CONSTRAINT FK_erp_jobcard_delivery_clearances_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_delivery_clearances_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearances')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_delivery_clearances_jobcard_id
        ON dbo.erp_jobcard_delivery_clearances(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_delivery_clearances_status'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearances')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_delivery_clearances_status
        ON dbo.erp_jobcard_delivery_clearances(clearance_status);
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_delivery_clearance_history
    (
        history_id BIGINT IDENTITY(1,1) NOT NULL,
        clearance_id BIGINT NULL,
        jobcard_id INT NOT NULL,

        event_code NVARCHAR(100) NOT NULL,
        event_title NVARCHAR(255) NOT NULL,
        event_notes NVARCHAR(2000) NULL,

        old_status NVARCHAR(50) NULL,
        new_status NVARCHAR(50) NULL,
        clearance_decision NVARCHAR(80) NULL,

        event_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_delivery_clearance_history_event_at DEFAULT (SYSUTCDATETIME()),

        event_by NVARCHAR(100) NULL,

        CONSTRAINT PK_erp_jobcard_delivery_clearance_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_jobcard_delivery_clearance_history_event_code
            CHECK (LEN(LTRIM(RTRIM(event_code))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_delivery_clearance_history_clearance'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_delivery_clearance_history
        ADD CONSTRAINT FK_erp_jobcard_delivery_clearance_history_clearance
            FOREIGN KEY (clearance_id)
            REFERENCES dbo.erp_jobcard_delivery_clearances(clearance_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_delivery_clearance_history_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_delivery_clearance_history
        ADD CONSTRAINT FK_erp_jobcard_delivery_clearance_history_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_delivery_clearance_history_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_delivery_clearance_history_jobcard_id
        ON dbo.erp_jobcard_delivery_clearance_history(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_delivery_clearance_history_clearance_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_delivery_clearance_history_clearance_id
        ON dbo.erp_jobcard_delivery_clearance_history(clearance_id);
END;
GO

SELECT
    'WAVE_4C_DELIVERY_CLEARANCE_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_jobcard_delivery_clearances', N'U') AS clearance_table_object_id,
    OBJECT_ID(N'dbo.erp_jobcard_delivery_clearance_history', N'U') AS clearance_history_table_object_id;
GO
