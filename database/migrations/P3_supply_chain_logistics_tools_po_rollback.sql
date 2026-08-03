/*
 * Rollback for P3_supply_chain_logistics_tools_po.sql
 * Drops only Prompt-3 additive tables created by that migration.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_tool_issue_events', N'U') IS NOT NULL
        DROP TABLE dbo.erp_tool_issue_events;
    IF OBJECT_ID(N'dbo.erp_tools_assets', N'U') IS NOT NULL
        DROP TABLE dbo.erp_tools_assets;
    IF OBJECT_ID(N'dbo.erp_logistics_requests', N'U') IS NOT NULL
        DROP TABLE dbo.erp_logistics_requests;
    IF OBJECT_ID(N'dbo.erp_purchase_order_lines', N'U') IS NOT NULL
        DROP TABLE dbo.erp_purchase_order_lines;
    IF OBJECT_ID(N'dbo.erp_purchase_orders', N'U') IS NOT NULL
        DROP TABLE dbo.erp_purchase_orders;
    IF OBJECT_ID(N'dbo.erp_supply_chain_audit_events', N'U') IS NOT NULL
        DROP TABLE dbo.erp_supply_chain_audit_events;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
