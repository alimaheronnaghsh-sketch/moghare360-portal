/*
================================================================================
MOGHARE360 ERP — Mission 20
Script: mission_20_service_operation_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 20 Service Operation foundation tables.
Design reference: docs/missions/mission_19_service_operation_foundation_design/

Creates if missing:
  1. dbo.erp_service_operations
  2. dbo.erp_service_operation_change_history

Depends on Mission 17 foundation:
  dbo.erp_jobcards

Idempotent: skips CREATE TABLE when table already exists.
No DROP. No TRUNCATE. No destructive migration. No legacy table modification.

Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_service_operations
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_service_operations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_service_operations
    (
        service_operation_id    INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        service_title           NVARCHAR(200)   NOT NULL,
        service_description     NVARCHAR(MAX)   NULL,
        assigned_to_user_id     INT             NULL,
        service_status          NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_service_operations_service_status DEFAULT (N'ASSIGNED'),
        created_by_user_id      INT             NOT NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_service_operations_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_service_operations_is_active DEFAULT (1),
        CONSTRAINT PK_erp_service_operations PRIMARY KEY CLUSTERED (service_operation_id),
        CONSTRAINT CK_erp_service_operations_service_status CHECK (
            service_status IN (
                N'DRAFT',
                N'ASSIGNED',
                N'IN_PROGRESS',
                N'WAITING_PARTS',
                N'DONE',
                N'QC_REJECTED',
                N'CANCELLED'
            )
        )
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_service_operations_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_service_operations
        ADD CONSTRAINT FK_erp_service_operations_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_service_operation_change_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_service_operation_change_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_service_operation_change_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        service_operation_id    INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_service_operation_change_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_service_operation_change_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_service_operation_change_history_operation', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_service_operation_change_history
        ADD CONSTRAINT FK_erp_service_operation_change_history_operation
            FOREIGN KEY (service_operation_id) REFERENCES dbo.erp_service_operations (service_operation_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_service_operation_change_history_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_service_operation_change_history
        ADD CONSTRAINT FK_erp_service_operation_change_history_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operations_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operations_jobcard_id
        ON dbo.erp_service_operations (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operations_service_status'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operations_service_status
        ON dbo.erp_service_operations (service_status);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operations_assigned_to_user_id'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operations_assigned_to_user_id
        ON dbo.erp_service_operations (assigned_to_user_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operations_created_at'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operations_created_at
        ON dbo.erp_service_operations (created_at);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operation_change_history_service_operation_id'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operation_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operation_change_history_service_operation_id
        ON dbo.erp_service_operation_change_history (service_operation_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operation_change_history_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operation_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operation_change_history_jobcard_id
        ON dbo.erp_service_operation_change_history (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_service_operation_change_history_action_code'
      AND object_id = OBJECT_ID(N'dbo.erp_service_operation_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_service_operation_change_history_action_code
        ON dbo.erp_service_operation_change_history (action_code);
END;
GO

SELECT N'Mission 20 Service Operation SQL foundation script completed.' AS mission_20_status;
GO
