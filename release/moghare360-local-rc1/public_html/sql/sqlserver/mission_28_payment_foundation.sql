/*
================================================================================
MOGHARE360 ERP — Mission 28
Script: mission_28_payment_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 28 Payment foundation tables.
Design reference: docs/missions/mission_27_finance_payment_foundation_design/

Creates if missing:
  1. dbo.erp_payments
  2. dbo.erp_payment_history

Depends on:
  dbo.erp_jobcards (Mission 17)
  dbo.erp_customers (Mission 17)

No invoice / accounting export / supplier payment / tax / delivery / purchase / stock tables.
Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_payments
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_payments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_payments
    (
        payment_id              INT             NOT NULL IDENTITY(1, 1),
        jobcard_id              INT             NOT NULL,
        customer_id             INT             NULL,
        payment_type            NVARCHAR(30)    NOT NULL,
        payment_method          NVARCHAR(30)    NOT NULL,
        payment_amount          DECIMAL(18, 2)  NOT NULL,
        currency_code           NVARCHAR(10)    NOT NULL,
        payment_status          NVARCHAR(30)    NOT NULL,
        payment_reference       NVARCHAR(100)   NULL,
        payment_note            NVARCHAR(MAX)   NULL,
        received_by_user_id     INT             NOT NULL,
        received_at             DATETIME2       NOT NULL
            CONSTRAINT DF_erp_payments_received_at DEFAULT (SYSUTCDATETIME()),
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_payments_created_at DEFAULT (SYSUTCDATETIME()),
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_payments_is_active DEFAULT (1),
        CONSTRAINT PK_erp_payments PRIMARY KEY CLUSTERED (payment_id),
        CONSTRAINT CK_erp_payments_status CHECK (
            payment_status IN (N'DRAFT', N'RECEIVED', N'CANCELLED', N'REVERSED')
        ),
        CONSTRAINT CK_erp_payments_type CHECK (
            payment_type IN (N'ADVANCE', N'PARTIAL', N'FULL', N'REFUND_PLACEHOLDER')
        ),
        CONSTRAINT CK_erp_payments_method CHECK (
            payment_method IN (N'CASH', N'CARD', N'BANK_TRANSFER', N'POS', N'OTHER')
        ),
        CONSTRAINT CK_erp_payments_amount_positive CHECK (payment_amount > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_payments_jobcard', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_payments
        ADD CONSTRAINT FK_erp_payments_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_payments_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_payments', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_payments_jobcard_id
        ON dbo.erp_payments (jobcard_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_payments_payment_status'
      AND object_id = OBJECT_ID(N'dbo.erp_payments', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_payments_payment_status
        ON dbo.erp_payments (payment_status);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_payments_received_at'
      AND object_id = OBJECT_ID(N'dbo.erp_payments', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_payments_received_at
        ON dbo.erp_payments (received_at DESC);
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_payment_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_payment_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_payment_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        payment_id              INT             NOT NULL,
        jobcard_id              INT             NOT NULL,
        action_code             NVARCHAR(80)    NOT NULL,
        old_status              NVARCHAR(30)    NULL,
        new_status              NVARCHAR(30)    NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_payment_history_changed_at DEFAULT (SYSUTCDATETIME()),
        change_note             NVARCHAR(MAX)   NULL,
        CONSTRAINT PK_erp_payment_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_payment_history_payment', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_payment_history
        ADD CONSTRAINT FK_erp_payment_history_payment
            FOREIGN KEY (payment_id) REFERENCES dbo.erp_payments (payment_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_payment_history_payment_id'
      AND object_id = OBJECT_ID(N'dbo.erp_payment_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_payment_history_payment_id
        ON dbo.erp_payment_history (payment_id);
END;
GO

PRINT N'Mission 28 payment foundation script completed.';
GO
