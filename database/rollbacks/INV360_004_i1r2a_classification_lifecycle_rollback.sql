/* INV360_004 rollback — I1R2-A. Removes additive structures only. Does not drop inv360_items. */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;

IF DB_NAME() <> N'moghare360_ERP'
    THROW 57004, 'INV360_004 rollback must run on moghare360_ERP only.', 1;

BEGIN TRY
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.inv360_item_missing_fields', N'U') IS NOT NULL
    DROP TABLE dbo.inv360_item_missing_fields;

IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_inv360_items_category')
    ALTER TABLE dbo.inv360_items DROP CONSTRAINT FK_inv360_items_category;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_lifecycle' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    DROP INDEX IX_inv360_items_lifecycle ON dbo.inv360_items;
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_category_id' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    DROP INDEX IX_inv360_items_category_id ON dbo.inv360_items;
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_market_grade' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    DROP INDEX IX_inv360_items_market_grade ON dbo.inv360_items;
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_mfr_pn' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    DROP INDEX IX_inv360_items_mfr_pn ON dbo.inv360_items;

DECLARE @cols TABLE(name sysname);
INSERT INTO @cols(name) VALUES
 (N'category_id'),(N'market_grade'),(N'part_condition'),(N'authenticity_grade'),
 (N'stock_authenticity'),(N'quality_grade'),(N'test_status'),(N'warranty_days'),
 (N'donor_vehicle_info'),(N'physical_condition_notes'),(N'manufacturer_part_number'),
 (N'supplier_code'),(N'item_notes'),(N'lifecycle_status'),(N'completeness_status'),
 (N'completeness_score'),(N'missing_field_count'),(N'last_completeness_check_at');

DECLARE @n sysname, @sql nvarchar(400);
DECLARE c CURSOR LOCAL FAST_FORWARD FOR SELECT name FROM @cols;
OPEN c;
FETCH NEXT FROM c INTO @n;
WHILE @@FETCH_STATUS = 0
BEGIN
    IF COL_LENGTH(N'dbo.inv360_items', @n) IS NOT NULL
    BEGIN
        IF @n = N'lifecycle_status' AND EXISTS (
            SELECT 1 FROM sys.default_constraints dc
            JOIN sys.columns col ON col.default_object_id = dc.object_id
            WHERE dc.parent_object_id = OBJECT_ID(N'dbo.inv360_items') AND col.name = N'lifecycle_status'
        )
        BEGIN
            SELECT @sql = N'ALTER TABLE dbo.inv360_items DROP CONSTRAINT ' + QUOTENAME(dc.name)
            FROM sys.default_constraints dc
            JOIN sys.columns col ON col.default_object_id = dc.object_id
            WHERE dc.parent_object_id = OBJECT_ID(N'dbo.inv360_items') AND col.name = N'lifecycle_status';
            EXEC sp_executesql @sql;
        END
        SET @sql = N'ALTER TABLE dbo.inv360_items DROP COLUMN ' + QUOTENAME(@n);
        EXEC sp_executesql @sql;
    END
    FETCH NEXT FROM c INTO @n;
END
CLOSE c; DEALLOCATE c;

IF OBJECT_ID(N'dbo.inv360_item_categories', N'U') IS NOT NULL
    DROP TABLE dbo.inv360_item_categories;

COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    DECLARE @msg NVARCHAR(4000) = ERROR_MESSAGE();
    THROW 57006, @msg, 1;
END CATCH;
