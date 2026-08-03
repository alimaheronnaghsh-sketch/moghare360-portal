/* INV360_004 —
   Apply with: sqlcmd -S ... -d moghare360_ERP -f 65001 -i ...
   INV360_004 — I1R2-A classification, category master, lifecycle, completeness.
   Additive/idempotent. Active DB: moghare360_ERP only. Canonical SKU: dbo.inv360_items. */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;

IF DB_NAME() <> N'moghare360_ERP'
    THROW 57004, 'INV360_004 must run on moghare360_ERP only.', 1;

BEGIN TRY
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.inv360_item_categories', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_item_categories (
        category_id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_inv360_item_categories PRIMARY KEY,
        category_code NVARCHAR(40) NOT NULL,
        category_name_fa NVARCHAR(120) NOT NULL,
        item_type NVARCHAR(40) NOT NULL,
        parent_category_id BIGINT NULL,
        sort_order INT NOT NULL CONSTRAINT DF_inv360_cat_sort DEFAULT (0),
        is_active BIT NOT NULL CONSTRAINT DF_inv360_cat_active DEFAULT (1),
        created_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_cat_created DEFAULT (SYSUTCDATETIME()),
        updated_by INT NULL,
        updated_at DATETIME2 NULL,
        CONSTRAINT UQ_inv360_item_categories_code UNIQUE (category_code),
        CONSTRAINT FK_inv360_item_categories_parent FOREIGN KEY (parent_category_id)
            REFERENCES dbo.inv360_item_categories(category_id)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_item_categories_type' AND object_id = OBJECT_ID(N'dbo.inv360_item_categories'))
    CREATE INDEX IX_inv360_item_categories_type ON dbo.inv360_item_categories(item_type, is_active, sort_order);

/* Additive columns on inv360_items */
IF COL_LENGTH(N'dbo.inv360_items', N'category_id') IS NULL
    ALTER TABLE dbo.inv360_items ADD category_id BIGINT NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'market_grade') IS NULL
    ALTER TABLE dbo.inv360_items ADD market_grade NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'part_condition') IS NULL
    ALTER TABLE dbo.inv360_items ADD part_condition NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'authenticity_grade') IS NULL
    ALTER TABLE dbo.inv360_items ADD authenticity_grade NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'stock_authenticity') IS NULL
    ALTER TABLE dbo.inv360_items ADD stock_authenticity NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'quality_grade') IS NULL
    ALTER TABLE dbo.inv360_items ADD quality_grade NVARCHAR(20) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'test_status') IS NULL
    ALTER TABLE dbo.inv360_items ADD test_status NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'warranty_days') IS NULL
    ALTER TABLE dbo.inv360_items ADD warranty_days INT NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'donor_vehicle_info') IS NULL
    ALTER TABLE dbo.inv360_items ADD donor_vehicle_info NVARCHAR(500) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'physical_condition_notes') IS NULL
    ALTER TABLE dbo.inv360_items ADD physical_condition_notes NVARCHAR(1000) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'manufacturer_part_number') IS NULL
    ALTER TABLE dbo.inv360_items ADD manufacturer_part_number NVARCHAR(100) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'supplier_code') IS NULL
    ALTER TABLE dbo.inv360_items ADD supplier_code NVARCHAR(100) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'item_notes') IS NULL
    ALTER TABLE dbo.inv360_items ADD item_notes NVARCHAR(2000) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'lifecycle_status') IS NULL
    ALTER TABLE dbo.inv360_items ADD lifecycle_status NVARCHAR(40) NOT NULL
        CONSTRAINT DF_inv360_items_lifecycle DEFAULT (N'ACTIVE');
IF COL_LENGTH(N'dbo.inv360_items', N'completeness_status') IS NULL
    ALTER TABLE dbo.inv360_items ADD completeness_status NVARCHAR(40) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'completeness_score') IS NULL
    ALTER TABLE dbo.inv360_items ADD completeness_score DECIMAL(5,2) NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'missing_field_count') IS NULL
    ALTER TABLE dbo.inv360_items ADD missing_field_count INT NULL;
IF COL_LENGTH(N'dbo.inv360_items', N'last_completeness_check_at') IS NULL
    ALTER TABLE dbo.inv360_items ADD last_completeness_check_at DATETIME2 NULL;

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_inv360_items_category'
)
BEGIN
    ALTER TABLE dbo.inv360_items WITH NOCHECK
    ADD CONSTRAINT FK_inv360_items_category FOREIGN KEY (category_id)
        REFERENCES dbo.inv360_item_categories(category_id);
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_lifecycle' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    CREATE INDEX IX_inv360_items_lifecycle ON dbo.inv360_items(lifecycle_status, is_deleted);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_category_id' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    CREATE INDEX IX_inv360_items_category_id ON dbo.inv360_items(category_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_market_grade' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    CREATE INDEX IX_inv360_items_market_grade ON dbo.inv360_items(market_grade);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_items_mfr_pn' AND object_id = OBJECT_ID(N'dbo.inv360_items'))
    CREATE INDEX IX_inv360_items_mfr_pn ON dbo.inv360_items(manufacturer_part_number);

IF OBJECT_ID(N'dbo.inv360_item_missing_fields', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_item_missing_fields (
        item_missing_field_id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_inv360_item_missing_fields PRIMARY KEY,
        item_id BIGINT NOT NULL,
        field_code NVARCHAR(80) NOT NULL,
        field_label_fa NVARCHAR(200) NOT NULL,
        severity NVARCHAR(20) NOT NULL CONSTRAINT DF_inv360_miss_sev DEFAULT (N'required'),
        detected_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_miss_det DEFAULT (SYSUTCDATETIME()),
        resolved_at DATETIME2 NULL,
        resolved_by INT NULL,
        source_code NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_miss_src DEFAULT (N'system'),
        CONSTRAINT FK_inv360_miss_item FOREIGN KEY (item_id) REFERENCES dbo.inv360_items(item_id)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_inv360_miss_item_open' AND object_id = OBJECT_ID(N'dbo.inv360_item_missing_fields'))
    CREATE INDEX IX_inv360_miss_item_open ON dbo.inv360_item_missing_fields(item_id, resolved_at);

/* Idempotent spare-part top-level category seed */
;WITH seed AS (
    SELECT * FROM (VALUES
        (N'SP_ENGINE', N'موتور و متعلقات', 10),
        (N'SP_TRANSMISSION', N'گیربکس، کلاچ و انتقال قدرت', 20),
        (N'SP_FUEL_AIR_EXHAUST', N'سوخت‌رسانی، هوا و اگزوز', 30),
        (N'SP_COOLING', N'سیستم خنک‌کاری', 40),
        (N'SP_SUSPENSION', N'تعلیق', 50),
        (N'SP_CHASSIS', N'زیربندی و شاسی', 60),
        (N'SP_STEERING', N'فرمان', 70),
        (N'SP_BRAKE', N'ترمز', 80),
        (N'SP_ELECTRICAL', N'برق، الکترونیک و آپشن', 90),
        (N'SP_HVAC', N'تهویه مطبوع', 100),
        (N'SP_BODY', N'بدنه و قطعات بیرونی', 110),
        (N'SP_INTERIOR', N'کابین، تریم و مبلمان داخلی', 120),
        (N'SP_GLASS_LIGHT', N'شیشه، آینه و روشنایی', 130),
        (N'SP_WHEEL', N'چرخ، تایر و رینگ', 140),
        (N'SP_HYBRID_EV', N'هیبرید و برقی', 150),
        (N'SP_FASTENER', N'پیچ، بست، واشر و آب‌بندی', 160),
        (N'SP_OTHER', N'سایر', 170)
    ) AS v(category_code, category_name_fa, sort_order)
)
MERGE dbo.inv360_item_categories AS t
USING seed AS s
ON t.category_code = s.category_code
WHEN NOT MATCHED THEN
    INSERT (category_code, category_name_fa, item_type, parent_category_id, sort_order, is_active, created_by)
    VALUES (s.category_code, s.category_name_fa, N'spare_part', NULL, s.sort_order, 1, NULL)
WHEN MATCHED AND (t.category_name_fa <> s.category_name_fa OR t.sort_order <> s.sort_order OR t.item_type <> N'spare_part')
THEN UPDATE SET
    t.category_name_fa = s.category_name_fa,
    t.sort_order = s.sort_order,
    t.item_type = N'spare_part',
    t.is_active = 1,
    t.updated_at = SYSUTCDATETIME();

/* I1R2-A.1 — Encoding-safe idempotent category_name_fa repair (NCHAR).
   Protects against sqlcmd client code-page mangling of UTF-8 N'...' literals.
   Updates by category_code only; does not change category_id or create duplicates. */
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1605) + NCHAR(1608) + NCHAR(1578) + NCHAR(1608) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1605) + NCHAR(1578) + NCHAR(1593) + NCHAR(1604) + NCHAR(1602) + NCHAR(1575) + NCHAR(1578), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_ENGINE' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1605) + NCHAR(1608) + NCHAR(1578) + NCHAR(1608) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1605) + NCHAR(1578) + NCHAR(1593) + NCHAR(1604) + NCHAR(1602) + NCHAR(1575) + NCHAR(1578));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1711) + NCHAR(1740) + NCHAR(1585) + NCHAR(1576) + NCHAR(1705) + NCHAR(1587) + NCHAR(1548) + NCHAR(32) + NCHAR(1705) + NCHAR(1604) + NCHAR(1575) + NCHAR(1670) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1575) + NCHAR(1606) + NCHAR(1578) + NCHAR(1602) + NCHAR(1575) + NCHAR(1604) + NCHAR(32) + NCHAR(1602) + NCHAR(1583) + NCHAR(1585) + NCHAR(1578), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_TRANSMISSION' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1711) + NCHAR(1740) + NCHAR(1585) + NCHAR(1576) + NCHAR(1705) + NCHAR(1587) + NCHAR(1548) + NCHAR(32) + NCHAR(1705) + NCHAR(1604) + NCHAR(1575) + NCHAR(1670) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1575) + NCHAR(1606) + NCHAR(1578) + NCHAR(1602) + NCHAR(1575) + NCHAR(1604) + NCHAR(32) + NCHAR(1602) + NCHAR(1583) + NCHAR(1585) + NCHAR(1578));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1587) + NCHAR(1608) + NCHAR(1582) + NCHAR(1578) + NCHAR(8204) + NCHAR(1585) + NCHAR(1587) + NCHAR(1575) + NCHAR(1606) + NCHAR(1740) + NCHAR(1548) + NCHAR(32) + NCHAR(1607) + NCHAR(1608) + NCHAR(1575) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1575) + NCHAR(1711) + NCHAR(1586) + NCHAR(1608) + NCHAR(1586), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_FUEL_AIR_EXHAUST' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1587) + NCHAR(1608) + NCHAR(1582) + NCHAR(1578) + NCHAR(8204) + NCHAR(1585) + NCHAR(1587) + NCHAR(1575) + NCHAR(1606) + NCHAR(1740) + NCHAR(1548) + NCHAR(32) + NCHAR(1607) + NCHAR(1608) + NCHAR(1575) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1575) + NCHAR(1711) + NCHAR(1586) + NCHAR(1608) + NCHAR(1586));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1587) + NCHAR(1740) + NCHAR(1587) + NCHAR(1578) + NCHAR(1605) + NCHAR(32) + NCHAR(1582) + NCHAR(1606) + NCHAR(1705) + NCHAR(8204) + NCHAR(1705) + NCHAR(1575) + NCHAR(1585) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_COOLING' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1587) + NCHAR(1740) + NCHAR(1587) + NCHAR(1578) + NCHAR(1605) + NCHAR(32) + NCHAR(1582) + NCHAR(1606) + NCHAR(1705) + NCHAR(8204) + NCHAR(1705) + NCHAR(1575) + NCHAR(1585) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1578) + NCHAR(1593) + NCHAR(1604) + NCHAR(1740) + NCHAR(1602), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_SUSPENSION' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1578) + NCHAR(1593) + NCHAR(1604) + NCHAR(1740) + NCHAR(1602));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1586) + NCHAR(1740) + NCHAR(1585) + NCHAR(1576) + NCHAR(1606) + NCHAR(1583) + NCHAR(1740) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1588) + NCHAR(1575) + NCHAR(1587) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_CHASSIS' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1586) + NCHAR(1740) + NCHAR(1585) + NCHAR(1576) + NCHAR(1606) + NCHAR(1583) + NCHAR(1740) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1588) + NCHAR(1575) + NCHAR(1587) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1601) + NCHAR(1585) + NCHAR(1605) + NCHAR(1575) + NCHAR(1606), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_STEERING' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1601) + NCHAR(1585) + NCHAR(1605) + NCHAR(1575) + NCHAR(1606));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1578) + NCHAR(1585) + NCHAR(1605) + NCHAR(1586), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_BRAKE' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1578) + NCHAR(1585) + NCHAR(1605) + NCHAR(1586));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1576) + NCHAR(1585) + NCHAR(1602) + NCHAR(1548) + NCHAR(32) + NCHAR(1575) + NCHAR(1604) + NCHAR(1705) + NCHAR(1578) + NCHAR(1585) + NCHAR(1608) + NCHAR(1606) + NCHAR(1740) + NCHAR(1705) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1570) + NCHAR(1662) + NCHAR(1588) + NCHAR(1606), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_ELECTRICAL' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1576) + NCHAR(1585) + NCHAR(1602) + NCHAR(1548) + NCHAR(32) + NCHAR(1575) + NCHAR(1604) + NCHAR(1705) + NCHAR(1578) + NCHAR(1585) + NCHAR(1608) + NCHAR(1606) + NCHAR(1740) + NCHAR(1705) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1570) + NCHAR(1662) + NCHAR(1588) + NCHAR(1606));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1578) + NCHAR(1607) + NCHAR(1608) + NCHAR(1740) + NCHAR(1607) + NCHAR(32) + NCHAR(1605) + NCHAR(1591) + NCHAR(1576) + NCHAR(1608) + NCHAR(1593), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_HVAC' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1578) + NCHAR(1607) + NCHAR(1608) + NCHAR(1740) + NCHAR(1607) + NCHAR(32) + NCHAR(1605) + NCHAR(1591) + NCHAR(1576) + NCHAR(1608) + NCHAR(1593));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1576) + NCHAR(1583) + NCHAR(1606) + NCHAR(1607) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1602) + NCHAR(1591) + NCHAR(1593) + NCHAR(1575) + NCHAR(1578) + NCHAR(32) + NCHAR(1576) + NCHAR(1740) + NCHAR(1585) + NCHAR(1608) + NCHAR(1606) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_BODY' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1576) + NCHAR(1583) + NCHAR(1606) + NCHAR(1607) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1602) + NCHAR(1591) + NCHAR(1593) + NCHAR(1575) + NCHAR(1578) + NCHAR(32) + NCHAR(1576) + NCHAR(1740) + NCHAR(1585) + NCHAR(1608) + NCHAR(1606) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1705) + NCHAR(1575) + NCHAR(1576) + NCHAR(1740) + NCHAR(1606) + NCHAR(1548) + NCHAR(32) + NCHAR(1578) + NCHAR(1585) + NCHAR(1740) + NCHAR(1605) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1605) + NCHAR(1576) + NCHAR(1604) + NCHAR(1605) + NCHAR(1575) + NCHAR(1606) + NCHAR(32) + NCHAR(1583) + NCHAR(1575) + NCHAR(1582) + NCHAR(1604) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_INTERIOR' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1705) + NCHAR(1575) + NCHAR(1576) + NCHAR(1740) + NCHAR(1606) + NCHAR(1548) + NCHAR(32) + NCHAR(1578) + NCHAR(1585) + NCHAR(1740) + NCHAR(1605) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1605) + NCHAR(1576) + NCHAR(1604) + NCHAR(1605) + NCHAR(1575) + NCHAR(1606) + NCHAR(32) + NCHAR(1583) + NCHAR(1575) + NCHAR(1582) + NCHAR(1604) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1588) + NCHAR(1740) + NCHAR(1588) + NCHAR(1607) + NCHAR(1548) + NCHAR(32) + NCHAR(1570) + NCHAR(1740) + NCHAR(1606) + NCHAR(1607) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1585) + NCHAR(1608) + NCHAR(1588) + NCHAR(1606) + NCHAR(1575) + NCHAR(1740) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_GLASS_LIGHT' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1588) + NCHAR(1740) + NCHAR(1588) + NCHAR(1607) + NCHAR(1548) + NCHAR(32) + NCHAR(1570) + NCHAR(1740) + NCHAR(1606) + NCHAR(1607) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1585) + NCHAR(1608) + NCHAR(1588) + NCHAR(1606) + NCHAR(1575) + NCHAR(1740) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1670) + NCHAR(1585) + NCHAR(1582) + NCHAR(1548) + NCHAR(32) + NCHAR(1578) + NCHAR(1575) + NCHAR(1740) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1585) + NCHAR(1740) + NCHAR(1606) + NCHAR(1711), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_WHEEL' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1670) + NCHAR(1585) + NCHAR(1582) + NCHAR(1548) + NCHAR(32) + NCHAR(1578) + NCHAR(1575) + NCHAR(1740) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1585) + NCHAR(1740) + NCHAR(1606) + NCHAR(1711));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1607) + NCHAR(1740) + NCHAR(1576) + NCHAR(1585) + NCHAR(1740) + NCHAR(1583) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1576) + NCHAR(1585) + NCHAR(1602) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_HYBRID_EV' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1607) + NCHAR(1740) + NCHAR(1576) + NCHAR(1585) + NCHAR(1740) + NCHAR(1583) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1576) + NCHAR(1585) + NCHAR(1602) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1662) + NCHAR(1740) + NCHAR(1670) + NCHAR(1548) + NCHAR(32) + NCHAR(1576) + NCHAR(1587) + NCHAR(1578) + NCHAR(1548) + NCHAR(32) + NCHAR(1608) + NCHAR(1575) + NCHAR(1588) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1570) + NCHAR(1576) + NCHAR(8204) + NCHAR(1576) + NCHAR(1606) + NCHAR(1583) + NCHAR(1740), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_FASTENER' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1662) + NCHAR(1740) + NCHAR(1670) + NCHAR(1548) + NCHAR(32) + NCHAR(1576) + NCHAR(1587) + NCHAR(1578) + NCHAR(1548) + NCHAR(32) + NCHAR(1608) + NCHAR(1575) + NCHAR(1588) + NCHAR(1585) + NCHAR(32) + NCHAR(1608) + NCHAR(32) + NCHAR(1570) + NCHAR(1576) + NCHAR(8204) + NCHAR(1576) + NCHAR(1606) + NCHAR(1583) + NCHAR(1740));
UPDATE dbo.inv360_item_categories SET category_name_fa = NCHAR(1587) + NCHAR(1575) + NCHAR(1740) + NCHAR(1585), updated_at = SYSUTCDATETIME() WHERE category_code = N'SP_OTHER' AND item_type = N'spare_part' AND category_name_fa <> (NCHAR(1587) + NCHAR(1575) + NCHAR(1740) + NCHAR(1585));


/* Existing live items remain ACTIVE operationally */
EXEC sp_executesql N'
UPDATE dbo.inv360_items
SET lifecycle_status = N''ACTIVE''
WHERE ISNULL(is_deleted, 0) = 0
  AND (lifecycle_status IS NULL OR LTRIM(RTRIM(lifecycle_status)) = N'''');
';

COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    DECLARE @msg NVARCHAR(4000) = ERROR_MESSAGE();
    THROW 57005, @msg, 1;
END CATCH;
