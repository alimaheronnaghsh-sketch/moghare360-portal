/*
================================================================================
MOGHARE360 ERP — Mission 26
Script: mission_26_purchase_request_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 26 Purchase Request foundation tables.
Design reference: docs/missions/mission_25_purchase_approval_foundation_design/

Creates if missing:
  1. dbo.erp_purchase_requests
  2. dbo.erp_purchase_request_history

Depends on:
  dbo.erp_jobcards (Mission 17)
  dbo.erp_service_operations (Mission 20)
  dbo.erp_parts (Mission 22)

No stock receipt / finance / payment / invoice / supplier / purchase order tables.
Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_purchase_requests
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_purchase_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_purchase_requests
    (
        purchase_request_id     INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        service_operation_id    INT             NULL,
        part_id                 INT             NULL,
        requested_part_name     NVARCHAR(200)   NOT NULL,
        requested_quantity      DECIMAL(18, 3)  NOT NULL,
        request_reason          NVARCHAR(MAX)   NULL,
        request_status          NVARCHAR(30)    NOT NULL,
        requested_by_user_id    INT             NOT NULL,
        requested_at            DATETIME2       NOT NULL
            CONSTRAINT DF_erp_purchase_requests_requested_at DEFAULT (SYSUTCDATETIME()),
        approved_by_user_id     INT             NULL,
        approved_at             DATETIME2       NULL,
        rejected_by_user_id     INT             NULL,
        rejected_at             DATETIME2       NULL,
        supplier_id             INT             NULL,
        estimated_unit_cost     DECIMAL(18, 2)  NULL,
        currency_code           NVARCHAR(10)    NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_purchase_requests_is_active DEFAULT (1),
        CONSTRAINT PK_erp_purchase_requests PRIMARY KEY CLUSTERED (purchase_request_id),
        CONSTRAINT CK_erp_purchase_requests_status CHECK (
            request_status IN (
                N'DRAFT',
                N'SUBMITTED',
                N'APPROVED',
                N'REJECTED',
                N'CANCELLED',
                N'ORDERED',
                N'RECEIVED',
                N'CLOSED'
            )
        ),
        CONSTRAINT CK_erp_purchase_requests_quantity_positive CHECK (requested_quantity > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_purchase_requests_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_purchase_requests
        ADD CONSTRAINT FK_erp_purchase_requests_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_purchase_requests_service_operation', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_purchase_requests
        ADD CONSTRAINT FK_erp_purchase_requests_service_operation
            FOREIGN KEY (service_operation_id) REFERENCES dbo.erp_service_operations (service_operation_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_purchase_requests_part', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_purchase_requests
        ADD CONSTRAINT FK_erp_purchase_requests_part
            FOREIGN KEY (part_id) REFERENCES dbo.erp_parts (part_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_purchase_requests_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_purchase_requests', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_purchase_requests_jobcard_id
        ON dbo.erp_purchase_requests (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_purchase_requests_request_status'
      AND object_id = OBJECT_ID(N'dbo.erp_purchase_requests', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_purchase_requests_request_status
        ON dbo.erp_purchase_requests (request_status);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_purchase_requests_requested_at'
      AND object_id = OBJECT_ID(N'dbo.erp_purchase_requests', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_purchase_requests_requested_at
        ON dbo.erp_purchase_requests (requested_at DESC);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_purchase_request_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_purchase_request_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_purchase_request_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        purchase_request_id     INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        service_operation_id    INT             NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_purchase_request_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_purchase_request_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_purchase_request_history_purchase_request', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_purchase_request_history
        ADD CONSTRAINT FK_erp_purchase_request_history_purchase_request
            FOREIGN KEY (purchase_request_id) REFERENCES dbo.erp_purchase_requests (purchase_request_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_purchase_request_history_purchase_request_id'
      AND object_id = OBJECT_ID(N'dbo.erp_purchase_request_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_purchase_request_history_purchase_request_id
        ON dbo.erp_purchase_request_history (purchase_request_id);
END;
GO

PRINT N'Mission 26 purchase request foundation script completed.';
GO
