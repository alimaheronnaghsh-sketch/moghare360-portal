/*
================================================================================
MOGHARE360 ERP — Mission 24
Script: mission_24_jobcard_part_usage.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 24 JobCard part usage tables.
Design reference: docs/missions/mission_23_jobcard_part_usage_design/

Creates if missing:
  1. dbo.erp_jobcard_part_usage
  2. dbo.erp_jobcard_part_usage_history

Depends on:
  dbo.erp_jobcards (Mission 17)
  dbo.erp_service_operations (Mission 20)
  dbo.erp_parts, dbo.erp_stock_locations, dbo.erp_stock_movements (Mission 22)

Controlled optional seed (test only):
  SEED movement for part_id = 1 at MAIN location, quantity 5,
  only if part exists and MISSION_24_TEST_SEED not already present.

No finance / purchase / payment / invoice tables touched.
Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_jobcard_part_usage
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcard_part_usage', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_part_usage
    (
        part_usage_id           INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        service_operation_id    INT             NULL,
        part_id                 INT             NOT NULL,
        stock_location_id       INT             NOT NULL,
        quantity                DECIMAL(18, 3)  NOT NULL,
        usage_status            NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_jobcard_part_usage_usage_status DEFAULT (N'USED'),
        created_by_user_id      INT             NOT NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_jobcard_part_usage_created_at DEFAULT (SYSUTCDATETIME()),
        reversed_by_usage_id    INT             NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_jobcard_part_usage_is_active DEFAULT (1),
        CONSTRAINT PK_erp_jobcard_part_usage PRIMARY KEY CLUSTERED (part_usage_id),
        CONSTRAINT CK_erp_jobcard_part_usage_usage_status CHECK (
            usage_status IN (N'USED', N'RETURNED', N'REVERSED', N'CANCELLED')
        ),
        CONSTRAINT CK_erp_jobcard_part_usage_quantity_positive CHECK (quantity > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage
        ADD CONSTRAINT FK_erp_jobcard_part_usage_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_service_operation', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage
        ADD CONSTRAINT FK_erp_jobcard_part_usage_service_operation
            FOREIGN KEY (service_operation_id) REFERENCES dbo.erp_service_operations (service_operation_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_part', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage
        ADD CONSTRAINT FK_erp_jobcard_part_usage_part
            FOREIGN KEY (part_id) REFERENCES dbo.erp_parts (part_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_stock_location', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage
        ADD CONSTRAINT FK_erp_jobcard_part_usage_stock_location
            FOREIGN KEY (stock_location_id) REFERENCES dbo.erp_stock_locations (stock_location_id);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_jobcard_part_usage_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcard_part_usage_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_part_usage_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        part_usage_id           INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        service_operation_id    INT             NULL,
        part_id                 INT             NOT NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_jobcard_part_usage_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_jobcard_part_usage_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_history_usage', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage_history
        ADD CONSTRAINT FK_erp_jobcard_part_usage_history_usage
            FOREIGN KEY (part_usage_id) REFERENCES dbo.erp_jobcard_part_usage (part_usage_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_part_usage_history_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_part_usage_history
        ADD CONSTRAINT FK_erp_jobcard_part_usage_history_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_jobcard_id
        ON dbo.erp_jobcard_part_usage (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_service_operation_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_service_operation_id
        ON dbo.erp_jobcard_part_usage (service_operation_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_part_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_part_id
        ON dbo.erp_jobcard_part_usage (part_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_usage_status'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_usage_status
        ON dbo.erp_jobcard_part_usage (usage_status);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_history_part_usage_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_history_part_usage_id
        ON dbo.erp_jobcard_part_usage_history (part_usage_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_part_usage_history_action_code'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_part_usage_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_part_usage_history_action_code
        ON dbo.erp_jobcard_part_usage_history (action_code);
END;
GO

/* ----------------------------------------------------------------------------
   Controlled test seed — SEED for part_id = 1 at MAIN (optional)
---------------------------------------------------------------------------- */
IF EXISTS (SELECT 1 FROM dbo.erp_parts WHERE part_id = 1 AND is_active = 1)
   AND EXISTS (SELECT 1 FROM dbo.erp_stock_locations WHERE location_code = N'MAIN' AND is_active = 1)
   AND OBJECT_ID(N'dbo.erp_stock_movements', N'U') IS NOT NULL
   AND NOT EXISTS (
        SELECT 1
        FROM dbo.erp_stock_movements m
        WHERE m.part_id = 1
          AND m.movement_type = N'SEED'
          AND m.reference_type = N'MISSION_24_TEST_SEED'
   )
BEGIN
    INSERT INTO dbo.erp_stock_movements (
        part_id,
        stock_location_id,
        movement_type,
        quantity,
        reference_type,
        reference_id,
        movement_note,
        created_by_user_id
    )
    SELECT
        1,
        sl.stock_location_id,
        N'SEED',
        CAST(5 AS DECIMAL(18, 3)),
        N'MISSION_24_TEST_SEED',
        NULL,
        N'Mission 24 controlled test seed for part_id = 1 at MAIN.',
        10001
    FROM dbo.erp_stock_locations sl
    WHERE sl.location_code = N'MAIN'
      AND sl.is_active = 1;
END;
GO

SELECT N'Mission 24 JobCard part usage SQL foundation script completed.' AS mission_24_status;
GO
