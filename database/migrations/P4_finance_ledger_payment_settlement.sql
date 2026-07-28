/*
 * MOGHARE360 Prompt 4 additive — customer ledger + invoice payment snapshot columns.
 * Local application only. Idempotent and non-destructive.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_final_invoices', N'U') IS NULL
        THROW 54001, 'P4 finance preflight failed: erp_final_invoices missing.', 1;
    IF OBJECT_ID(N'dbo.erp_payments', N'U') IS NULL
        THROW 54002, 'P4 finance preflight failed: erp_payments missing.', 1;

    IF COL_LENGTH(N'dbo.erp_final_invoices', N'paid_amount') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_fi_paid DEFAULT (0);
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'balance_amount') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD balance_amount DECIMAL(18,2) NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'invoice_type') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD invoice_type NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_fi_type DEFAULT (N'settlement');
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'currency_code') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD currency_code NVARCHAR(10) NOT NULL CONSTRAINT DF_erp_fi_currency DEFAULT (N'IRR');
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'online_request_id') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD online_request_id INT NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'voided_at') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD voided_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'void_reason') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD void_reason NVARCHAR(500) NULL;

    IF OBJECT_ID(N'dbo.erp_customer_ledger_entries', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_customer_ledger_entries (
            ledger_entry_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            customer_id INT NOT NULL,
            jobcard_id INT NULL,
            invoice_id INT NULL,
            payment_id INT NULL,
            entry_type NVARCHAR(20) NOT NULL,
            debit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_ledger_debit DEFAULT (0),
            credit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_ledger_credit DEFAULT (0),
            balance_after DECIMAL(18,2) NULL,
            reference_code NVARCHAR(80) NULL,
            entry_note NVARCHAR(1000) NULL,
            is_reversed BIT NOT NULL CONSTRAINT DF_erp_ledger_reversed DEFAULT (0),
            reversal_of_entry_id INT NULL,
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ledger_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT CK_erp_ledger_entry_type CHECK (entry_type IN (N'DEBIT', N'CREDIT', N'REVERSAL')),
            CONSTRAINT CK_erp_ledger_amounts CHECK (debit_amount >= 0 AND credit_amount >= 0)
        );
        CREATE INDEX IX_erp_customer_ledger_customer ON dbo.erp_customer_ledger_entries(customer_id, created_at);
    END;

    IF OBJECT_ID(N'dbo.erp_finance_audit_events', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_finance_audit_events (
            finance_audit_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            entity_type NVARCHAR(40) NOT NULL,
            entity_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            event_note NVARCHAR(1000) NULL,
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_fin_audit_created DEFAULT (SYSUTCDATETIME())
        );
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
