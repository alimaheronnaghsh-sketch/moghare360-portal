/*
 * Rollback for P4_finance_ledger_payment_settlement.sql
 * Drops only Prompt-4 additive tables/columns created by that migration.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_finance_audit_events', N'U') IS NOT NULL
        DROP TABLE dbo.erp_finance_audit_events;
    IF OBJECT_ID(N'dbo.erp_customer_ledger_entries', N'U') IS NOT NULL
        DROP TABLE dbo.erp_customer_ledger_entries;

    IF COL_LENGTH(N'dbo.erp_final_invoices', N'void_reason') IS NOT NULL
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN void_reason;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'voided_at') IS NOT NULL
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN voided_at;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'online_request_id') IS NOT NULL
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN online_request_id;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'currency_code') IS NOT NULL
    BEGIN
        DECLARE @df1 SYSNAME;
        SELECT @df1 = dc.name FROM sys.default_constraints dc
        WHERE dc.parent_object_id = OBJECT_ID(N'dbo.erp_final_invoices') AND COL_NAME(dc.parent_object_id, dc.parent_column_id) = N'currency_code';
        IF @df1 IS NOT NULL EXEC(N'ALTER TABLE dbo.erp_final_invoices DROP CONSTRAINT [' + @df1 + N']');
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN currency_code;
    END;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'invoice_type') IS NOT NULL
    BEGIN
        DECLARE @df2 SYSNAME;
        SELECT @df2 = dc.name FROM sys.default_constraints dc
        WHERE dc.parent_object_id = OBJECT_ID(N'dbo.erp_final_invoices') AND COL_NAME(dc.parent_object_id, dc.parent_column_id) = N'invoice_type';
        IF @df2 IS NOT NULL EXEC(N'ALTER TABLE dbo.erp_final_invoices DROP CONSTRAINT [' + @df2 + N']');
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN invoice_type;
    END;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'balance_amount') IS NOT NULL
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN balance_amount;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'paid_amount') IS NOT NULL
    BEGIN
        DECLARE @df3 SYSNAME;
        SELECT @df3 = dc.name FROM sys.default_constraints dc
        WHERE dc.parent_object_id = OBJECT_ID(N'dbo.erp_final_invoices') AND COL_NAME(dc.parent_object_id, dc.parent_column_id) = N'paid_amount';
        IF @df3 IS NOT NULL EXEC(N'ALTER TABLE dbo.erp_final_invoices DROP CONSTRAINT [' + @df3 + N']');
        ALTER TABLE dbo.erp_final_invoices DROP COLUMN paid_amount;
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
