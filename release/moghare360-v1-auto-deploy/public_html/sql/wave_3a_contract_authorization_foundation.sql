/*
================================================================================
MOGHARE360 ERP — WAVE 3A
Script: wave_3a_contract_authorization_foundation.sql
================================================================================

Official SQL Server foundation for JobCard Contract Authorization runtime.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No DROP TABLE.

NOT final legal e-signature. NOT public customer portal activation.
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
    THROW 53000, 'Required table dbo.erp_jobcards was not found. WAVE 3A stopped safely.', 1;
END;

DECLARE @jobcard_id_type_name NVARCHAR(128);

SELECT @jobcard_id_type_name = t.name
FROM sys.columns c
INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
WHERE c.object_id = OBJECT_ID(N'dbo.erp_jobcards')
  AND c.name = N'jobcard_id';

IF @jobcard_id_type_name IS NULL
BEGIN
    THROW 53001, 'Required column dbo.erp_jobcards.jobcard_id was not found. WAVE 3A stopped safely.', 1;
END;

IF @jobcard_id_type_name <> N'int'
BEGIN
    THROW 53002, 'dbo.erp_jobcards.jobcard_id must be INT for FK compatibility. WAVE 3A stopped safely.', 1;
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_authorizations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_authorizations
    (
        authorization_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id INT NOT NULL,

        authorization_type NVARCHAR(50) NOT NULL,
        authorization_status NVARCHAR(50) NOT NULL,
        authorization_method NVARCHAR(50) NOT NULL,

        customer_name NVARCHAR(200) NOT NULL,
        customer_mobile NVARCHAR(20) NOT NULL,
        authorization_note NVARCHAR(2000) NULL,

        is_active BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_authorizations_is_active DEFAULT (1),
        is_deleted BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_authorizations_is_deleted DEFAULT (0),

        created_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_authorizations_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_authorizations_updated_at DEFAULT (SYSUTCDATETIME()),

        CONSTRAINT PK_erp_jobcard_authorizations
            PRIMARY KEY CLUSTERED (authorization_id ASC),

        CONSTRAINT CK_erp_jobcard_authorizations_type
            CHECK (authorization_type IN (
                N'acceptance_contract',
                N'repair_permission',
                N'part_purchase_approval',
                N'additional_cost_approval',
                N'delivery_approval',
                N'diagnostic_authorization',
                N'other'
            )),

        CONSTRAINT CK_erp_jobcard_authorizations_status
            CHECK (authorization_status IN (
                N'draft',
                N'pending_customer_approval',
                N'approved',
                N'rejected',
                N'cancelled'
            )),

        CONSTRAINT CK_erp_jobcard_authorizations_method
            CHECK (authorization_method IN (
                N'internal_operator',
                N'phone_confirmation',
                N'in_person_confirmation',
                N'written_form',
                N'future_customer_portal_pending'
            )),

        CONSTRAINT CK_erp_jobcard_authorizations_customer_name
            CHECK (LEN(LTRIM(RTRIM(customer_name))) > 0),

        CONSTRAINT CK_erp_jobcard_authorizations_customer_mobile
            CHECK (LEN(LTRIM(RTRIM(customer_mobile))) >= 10)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_authorizations_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_authorizations')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_authorizations
        ADD CONSTRAINT FK_erp_jobcard_authorizations_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_authorizations_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_authorizations')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_authorizations_jobcard_id
        ON dbo.erp_jobcard_authorizations(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_authorizations_status'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_authorizations')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_authorizations_status
        ON dbo.erp_jobcard_authorizations(authorization_status);
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_authorization_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_authorization_history
    (
        history_id BIGINT IDENTITY(1,1) NOT NULL,
        authorization_id BIGINT NULL,
        jobcard_id INT NOT NULL,

        event_code NVARCHAR(100) NOT NULL,
        event_title NVARCHAR(255) NOT NULL,
        event_notes NVARCHAR(2000) NULL,

        old_status NVARCHAR(50) NULL,
        new_status NVARCHAR(50) NULL,

        event_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_authorization_history_event_at DEFAULT (SYSUTCDATETIME()),

        event_by NVARCHAR(100) NULL,

        CONSTRAINT PK_erp_jobcard_authorization_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_jobcard_authorization_history_event_code
            CHECK (LEN(LTRIM(RTRIM(event_code))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_authorization_history_authorization'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_authorization_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_authorization_history
        ADD CONSTRAINT FK_erp_jobcard_authorization_history_authorization
            FOREIGN KEY (authorization_id)
            REFERENCES dbo.erp_jobcard_authorizations(authorization_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_authorization_history_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_authorization_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_authorization_history
        ADD CONSTRAINT FK_erp_jobcard_authorization_history_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_authorization_history_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_authorization_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_authorization_history_jobcard_id
        ON dbo.erp_jobcard_authorization_history(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_authorization_history_authorization_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_authorization_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_authorization_history_authorization_id
        ON dbo.erp_jobcard_authorization_history(authorization_id);
END;
GO

SELECT
    'WAVE_3A_CONTRACT_AUTHORIZATION_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_jobcard_authorizations', N'U') AS authorization_table_object_id,
    OBJECT_ID(N'dbo.erp_jobcard_authorization_history', N'U') AS authorization_history_table_object_id;
GO
