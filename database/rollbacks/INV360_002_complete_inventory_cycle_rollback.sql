/*
 * Rollback INV360_002 — drops only additive Inv360* objects / added columns.
 * Does not drop legacy Parts/Warehouses/Users data.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

IF DB_NAME() <> N'MOGHARE360_StockCenter'
    THROW 56002, 'INV360_002 rollback must run on MOGHARE360_StockCenter only.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.Inv360ToolEvents', N'U') IS NOT NULL DROP TABLE dbo.Inv360ToolEvents;
    IF OBJECT_ID(N'dbo.Inv360ToolsAssets', N'U') IS NOT NULL DROP TABLE dbo.Inv360ToolsAssets;
    IF OBJECT_ID(N'dbo.Inv360LogisticsRequests', N'U') IS NOT NULL DROP TABLE dbo.Inv360LogisticsRequests;
    IF OBJECT_ID(N'dbo.Inv360LandedCosts', N'U') IS NOT NULL DROP TABLE dbo.Inv360LandedCosts;
    IF OBJECT_ID(N'dbo.Inv360CustomerReturns', N'U') IS NOT NULL DROP TABLE dbo.Inv360CustomerReturns;
    IF OBJECT_ID(N'dbo.Inv360SupplierReturns', N'U') IS NOT NULL DROP TABLE dbo.Inv360SupplierReturns;
    IF OBJECT_ID(N'dbo.Inv360QcEvents', N'U') IS NOT NULL DROP TABLE dbo.Inv360QcEvents;
    IF OBJECT_ID(N'dbo.Inv360GoodsReceiptLines', N'U') IS NOT NULL DROP TABLE dbo.Inv360GoodsReceiptLines;
    IF OBJECT_ID(N'dbo.Inv360GoodsReceipts', N'U') IS NOT NULL DROP TABLE dbo.Inv360GoodsReceipts;
    IF OBJECT_ID(N'dbo.Inv360PurchaseOrderLines', N'U') IS NOT NULL DROP TABLE dbo.Inv360PurchaseOrderLines;
    IF OBJECT_ID(N'dbo.Inv360PurchaseOrders', N'U') IS NOT NULL DROP TABLE dbo.Inv360PurchaseOrders;
    IF OBJECT_ID(N'dbo.Inv360Rfqs', N'U') IS NOT NULL DROP TABLE dbo.Inv360Rfqs;
    IF OBJECT_ID(N'dbo.Inv360PurchaseRequests', N'U') IS NOT NULL DROP TABLE dbo.Inv360PurchaseRequests;
    IF OBJECT_ID(N'dbo.Inv360Suppliers', N'U') IS NOT NULL DROP TABLE dbo.Inv360Suppliers;
    IF OBJECT_ID(N'dbo.Inv360Reservations', N'U') IS NOT NULL DROP TABLE dbo.Inv360Reservations;
    IF OBJECT_ID(N'dbo.Inv360StockDocumentLines', N'U') IS NOT NULL DROP TABLE dbo.Inv360StockDocumentLines;
    IF OBJECT_ID(N'dbo.Inv360StockDocuments', N'U') IS NOT NULL DROP TABLE dbo.Inv360StockDocuments;
    IF OBJECT_ID(N'dbo.Inv360StockBalances', N'U') IS NOT NULL DROP TABLE dbo.Inv360StockBalances;
    IF OBJECT_ID(N'dbo.Inv360AppAudit', N'U') IS NOT NULL DROP TABLE dbo.Inv360AppAudit;
    IF OBJECT_ID(N'dbo.Inv360Settings', N'U') IS NOT NULL DROP TABLE dbo.Inv360Settings;

    /* Added columns on legacy tables are left in place to avoid data loss on rollback of cycle features.
       Documented intentional soft-rollback behavior. */

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
