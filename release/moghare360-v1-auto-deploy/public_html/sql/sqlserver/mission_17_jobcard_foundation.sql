/*
================================================================================
MOGHARE360 ERP — Mission 17
Script: mission_17_jobcard_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 17 JobCard foundation tables.
Design reference: docs/missions/mission_16_reception_jobcard_foundation_design/

Creates if missing:
  1. dbo.erp_jobcards
  2. dbo.erp_jobcard_change_history

Depends on Mission 15 foundation:
  dbo.erp_customers
  dbo.erp_vehicles
  dbo.erp_customer_vehicle_relations

Idempotent: skips CREATE TABLE when table already exists.
No DROP. No TRUNCATE. No destructive migration. No legacy table modification.

Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_jobcards
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcards
    (
        jobcard_id                  INT             NOT NULL IDENTITY(1, 1),
        jobcard_number              NVARCHAR(60)    NOT NULL,
        customer_id                 INT             NOT NULL,
        vehicle_id                  INT             NOT NULL,
        relation_id                 INT             NULL,
        reception_user_id           INT             NOT NULL,
        assigned_team_id            INT             NULL,
        jobcard_status              NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_jobcards_jobcard_status DEFAULT (N'RECEIVED'),
        reception_at                DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_jobcards_reception_at DEFAULT (SYSUTCDATETIME()),
        promised_at                 DATETIME2(3)    NULL,
        intake_mileage              INT             NULL,
        fuel_level                  NVARCHAR(30)    NULL,
        customer_complaint          NVARCHAR(1000)  NULL,
        requested_services_summary  NVARCHAR(1000)  NULL,
        initial_vehicle_condition   NVARCHAR(1000)  NULL,
        internal_notes              NVARCHAR(1000)  NULL,
        priority_level              NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_jobcards_priority_level DEFAULT (N'NORMAL'),
        lifecycle_state             NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_jobcards_lifecycle_state DEFAULT (N'ACTIVE'),
        created_at                  DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_jobcards_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at                  DATETIME2(3)    NULL,
        created_by_user_id          INT             NOT NULL,
        updated_by_user_id          INT             NULL,
        CONSTRAINT PK_erp_jobcards PRIMARY KEY CLUSTERED (jobcard_id),
        CONSTRAINT UQ_erp_jobcards_jobcard_number UNIQUE (jobcard_number)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcards_customer', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcards
        ADD CONSTRAINT FK_erp_jobcards_customer
            FOREIGN KEY (customer_id) REFERENCES dbo.erp_customers (customer_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcards_vehicle', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcards
        ADD CONSTRAINT FK_erp_jobcards_vehicle
            FOREIGN KEY (vehicle_id) REFERENCES dbo.erp_vehicles (vehicle_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcards_relation', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcards
        ADD CONSTRAINT FK_erp_jobcards_relation
            FOREIGN KEY (relation_id) REFERENCES dbo.erp_customer_vehicle_relations (relation_id);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_jobcard_change_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcard_change_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_change_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        change_type             NVARCHAR(100)   NOT NULL,
        previous_status         NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        change_summary          NVARCHAR(1000)  NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_jobcard_change_history_changed_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_jobcard_change_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_jobcard_change_history_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_jobcard_change_history
        ADD CONSTRAINT FK_erp_jobcard_change_history_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_jobcard_number'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_jobcard_number
        ON dbo.erp_jobcards (jobcard_number);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_customer_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_customer_id
        ON dbo.erp_jobcards (customer_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_vehicle_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_vehicle_id
        ON dbo.erp_jobcards (vehicle_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_relation_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_relation_id
        ON dbo.erp_jobcards (relation_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_jobcard_status'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_jobcard_status
        ON dbo.erp_jobcards (jobcard_status);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcards_reception_at'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcards', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcards_reception_at
        ON dbo.erp_jobcards (reception_at);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_change_history_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_change_history_jobcard_id
        ON dbo.erp_jobcard_change_history (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_change_history_change_type'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_jobcard_change_history_change_type
        ON dbo.erp_jobcard_change_history (change_type);
END;
GO

SELECT N'Mission 17 JobCard SQL foundation script completed.' AS mission_17_status;
GO
