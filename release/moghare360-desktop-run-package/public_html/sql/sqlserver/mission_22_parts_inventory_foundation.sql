/*
================================================================================
MOGHARE360 ERP — Mission 22
Script: mission_22_parts_inventory_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 22 Parts / Inventory foundation tables.
Design reference: docs/missions/mission_21_parts_inventory_foundation_design/

Creates if missing:
  1. dbo.erp_parts
  2. dbo.erp_stock_locations
  3. dbo.erp_stock_movements

Controlled seed:
  - MAIN stock location only (if missing)
  - No stock movement seed
  - No JobCard usage
  - No finance / purchase write

Idempotent: skips CREATE TABLE when table already exists.
No DROP. No TRUNCATE. No destructive migration. No legacy table modification.

Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_parts
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_parts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_parts
    (
        part_id                 INT             NOT NULL IDENTITY(1, 1),
        part_code               NVARCHAR(80)    NOT NULL,
        part_name               NVARCHAR(200)   NOT NULL,
        brand                   NVARCHAR(120)   NULL,
        manufacturer            NVARCHAR(120)   NULL,
        oem_number              NVARCHAR(120)   NULL,
        aftermarket_number      NVARCHAR(120)   NULL,
        category                NVARCHAR(120)   NULL,
        unit_of_measure         NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_parts_unit_of_measure DEFAULT (N'PCS'),
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_parts_is_active DEFAULT (1),
        created_by_user_id      INT             NOT NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_parts_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        CONSTRAINT PK_erp_parts PRIMARY KEY CLUSTERED (part_id),
        CONSTRAINT UQ_erp_parts_part_code UNIQUE (part_code)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_stock_locations
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_locations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_stock_locations
    (
        stock_location_id       INT             NOT NULL IDENTITY(1, 1),
        location_code           NVARCHAR(80)    NOT NULL,
        location_name           NVARCHAR(200)   NOT NULL,
        location_type           NVARCHAR(50)    NOT NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_stock_locations_is_active DEFAULT (1),
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_stock_locations_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_stock_locations PRIMARY KEY CLUSTERED (stock_location_id),
        CONSTRAINT UQ_erp_stock_locations_location_code UNIQUE (location_code),
        CONSTRAINT CK_erp_stock_locations_location_type CHECK (
            location_type IN (
                N'WAREHOUSE',
                N'SHELF',
                N'BIN',
                N'STAGING',
                N'QUARANTINE'
            )
        )
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_stock_movements
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_movements', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_stock_movements
    (
        stock_movement_id       INT             NOT NULL IDENTITY(1, 1),
        part_id                 INT             NOT NULL,
        stock_location_id       INT             NOT NULL,
        movement_type           NVARCHAR(30)    NOT NULL,
        quantity                DECIMAL(18, 3)  NOT NULL,
        reference_type          NVARCHAR(80)    NULL,
        reference_id            INT             NULL,
        movement_note           NVARCHAR(MAX)   NULL,
        created_by_user_id      INT             NOT NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_stock_movements_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_stock_movements PRIMARY KEY CLUSTERED (stock_movement_id),
        CONSTRAINT CK_erp_stock_movements_movement_type CHECK (
            movement_type IN (
                N'SEED',
                N'RECEIPT',
                N'ISSUE',
                N'RETURN',
                N'ADJUSTMENT',
                N'REVERSAL'
            )
        ),
        CONSTRAINT CK_erp_stock_movements_quantity_positive CHECK (quantity > 0)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_stock_movements_part', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_stock_movements
        ADD CONSTRAINT FK_erp_stock_movements_part
            FOREIGN KEY (part_id) REFERENCES dbo.erp_parts (part_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_stock_movements_stock_location', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_stock_movements
        ADD CONSTRAINT FK_erp_stock_movements_stock_location
            FOREIGN KEY (stock_location_id) REFERENCES dbo.erp_stock_locations (stock_location_id);
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_parts_part_name'
      AND object_id = OBJECT_ID(N'dbo.erp_parts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_parts_part_name
        ON dbo.erp_parts (part_name);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_parts_category'
      AND object_id = OBJECT_ID(N'dbo.erp_parts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_parts_category
        ON dbo.erp_parts (category);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_parts_is_active'
      AND object_id = OBJECT_ID(N'dbo.erp_parts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_parts_is_active
        ON dbo.erp_parts (is_active);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_stock_locations_location_type'
      AND object_id = OBJECT_ID(N'dbo.erp_stock_locations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_stock_locations_location_type
        ON dbo.erp_stock_locations (location_type);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_stock_movements_part_location'
      AND object_id = OBJECT_ID(N'dbo.erp_stock_movements', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_stock_movements_part_location
        ON dbo.erp_stock_movements (part_id, stock_location_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_stock_movements_movement_type'
      AND object_id = OBJECT_ID(N'dbo.erp_stock_movements', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_stock_movements_movement_type
        ON dbo.erp_stock_movements (movement_type);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_stock_movements_reference'
      AND object_id = OBJECT_ID(N'dbo.erp_stock_movements', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_stock_movements_reference
        ON dbo.erp_stock_movements (reference_type, reference_id);
END;
GO

/* ----------------------------------------------------------------------------
   Controlled seed — MAIN location only
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM dbo.erp_stock_locations
    WHERE location_code = N'MAIN'
)
BEGIN
    INSERT INTO dbo.erp_stock_locations (
        location_code,
        location_name,
        location_type,
        is_active
    )
    VALUES (
        N'MAIN',
        N'Main Warehouse',
        N'WAREHOUSE',
        1
    );
END;
GO

SELECT N'Mission 22 Parts / Inventory SQL foundation script completed.' AS mission_22_status;
GO
