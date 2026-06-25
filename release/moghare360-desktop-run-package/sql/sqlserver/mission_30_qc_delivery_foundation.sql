/*
================================================================================
MOGHARE360 ERP — Mission 30
Script: mission_30_qc_delivery_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 30 QC / Delivery foundation tables.
Design reference: docs/missions/mission_29_qc_delivery_foundation_design/

Creates if missing:
  1. dbo.erp_qc_checks
  2. dbo.erp_qc_check_history
  3. dbo.erp_delivery_controls
  4. dbo.erp_delivery_control_history

Depends on:
  dbo.erp_jobcards (Mission 17)
  dbo.erp_service_operations (Mission 20)

No invoice / customer signature / customer portal / accounting / supplier / tax / stock / purchase tables.
Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_qc_checks
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_qc_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_checks
    (
        qc_check_id             INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        service_operation_id    INT             NULL,
        qc_status               NVARCHAR(30)    NOT NULL,
        checked_by_user_id      INT             NOT NULL,
        checked_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_qc_checks_checked_at DEFAULT (SYSUTCDATETIME()),
        qc_note                 NVARCHAR(MAX)   NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_qc_checks_is_active DEFAULT (1),
        CONSTRAINT PK_erp_qc_checks PRIMARY KEY CLUSTERED (qc_check_id),
        CONSTRAINT CK_erp_qc_checks_status CHECK (
            qc_status IN (N'PENDING', N'PASSED', N'FAILED', N'RECHECK_REQUIRED', N'CANCELLED')
        )
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_qc_checks_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_qc_checks
        ADD CONSTRAINT FK_erp_qc_checks_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_qc_checks_service_operation', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_qc_checks
        ADD CONSTRAINT FK_erp_qc_checks_service_operation
            FOREIGN KEY (service_operation_id) REFERENCES dbo.erp_service_operations (service_operation_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_qc_checks_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_qc_checks', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_qc_checks_jobcard_id ON dbo.erp_qc_checks (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_qc_checks_qc_status'
      AND object_id = OBJECT_ID(N'dbo.erp_qc_checks', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_qc_checks_qc_status ON dbo.erp_qc_checks (qc_status);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_qc_check_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_qc_check_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_check_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        qc_check_id             INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_qc_check_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_qc_check_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_qc_check_history_qc_check', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_qc_check_history
        ADD CONSTRAINT FK_erp_qc_check_history_qc_check
            FOREIGN KEY (qc_check_id) REFERENCES dbo.erp_qc_checks (qc_check_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_qc_check_history_qc_check_id'
      AND object_id = OBJECT_ID(N'dbo.erp_qc_check_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_qc_check_history_qc_check_id
        ON dbo.erp_qc_check_history (qc_check_id);
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_delivery_controls
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_delivery_controls', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_delivery_controls
    (
        delivery_control_id     INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        qc_check_id             INT             NULL,
        delivery_status         NVARCHAR(30)    NOT NULL,
        delivery_allowed        BIT             NOT NULL,
        block_reason            NVARCHAR(200)   NULL,
        released_by_user_id     INT             NULL,
        released_at             DATETIME2       NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_delivery_controls_created_at DEFAULT (SYSUTCDATETIME()),
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_delivery_controls_is_active DEFAULT (1),
        CONSTRAINT PK_erp_delivery_controls PRIMARY KEY CLUSTERED (delivery_control_id),
        CONSTRAINT CK_erp_delivery_controls_status CHECK (
            delivery_status IN (N'BLOCKED', N'READY', N'RELEASED', N'CANCELLED')
        )
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_delivery_controls_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_delivery_controls
        ADD CONSTRAINT FK_erp_delivery_controls_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_delivery_controls_qc_check', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_delivery_controls
        ADD CONSTRAINT FK_erp_delivery_controls_qc_check
            FOREIGN KEY (qc_check_id) REFERENCES dbo.erp_qc_checks (qc_check_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_delivery_controls_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_delivery_controls', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_delivery_controls_jobcard_id
        ON dbo.erp_delivery_controls (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_delivery_controls_delivery_status'
      AND object_id = OBJECT_ID(N'dbo.erp_delivery_controls', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_delivery_controls_delivery_status
        ON dbo.erp_delivery_controls (delivery_status);
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_delivery_control_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_delivery_control_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_delivery_control_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        delivery_control_id     INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_delivery_control_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_delivery_control_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_delivery_control_history_delivery_control', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_delivery_control_history
        ADD CONSTRAINT FK_erp_delivery_control_history_delivery_control
            FOREIGN KEY (delivery_control_id) REFERENCES dbo.erp_delivery_controls (delivery_control_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_delivery_control_history_delivery_control_id'
      AND object_id = OBJECT_ID(N'dbo.erp_delivery_control_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_delivery_control_history_delivery_control_id
        ON dbo.erp_delivery_control_history (delivery_control_id);
END;
GO

PRINT N'Mission 30 QC / delivery foundation script completed.';
GO
