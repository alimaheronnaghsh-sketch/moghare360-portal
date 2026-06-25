/*
================================================================================
MOGHARE360 ERP — Phase 4 Inventory & Purchase System
Script: phase_4_inventory_purchase_system.sql
================================================================================

Extension foundation for parts catalog, stock balances, reservations, suppliers,
purchase lifecycle, and movement history.

Does NOT drop/rename legacy dbo.erp_parts, M22 erp_stock_locations/movements,
or M26 erp_purchase_requests when they already exist.

When legacy tables exist, Phase 4 PHP uses:
  - erp_inventory_stock_movements (extension movements)
  - erp_inventory_purchase_requests (extension purchase requests)

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_inventory_items
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_inventory_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_inventory_items
    (
        inventory_item_id   BIGINT          NOT NULL IDENTITY(1, 1),
        legacy_part_id      BIGINT          NULL,
        item_code           NVARCHAR(100)   NOT NULL,
        item_name           NVARCHAR(300)   NOT NULL,
        item_category       NVARCHAR(100)   NULL,
        brand               NVARCHAR(100)   NULL,
        compatible_vehicle  NVARCHAR(300)   NULL,
        unit_name           NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_inventory_items_unit_name DEFAULT (N'عدد'),
        min_stock_qty       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_inventory_items_min_stock_qty DEFAULT (0),
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_inventory_items_is_active DEFAULT (1),
        notes               NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_inventory_items_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_inventory_items PRIMARY KEY CLUSTERED (inventory_item_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_stock_locations (Phase 4 schema — skipped if M22 table exists)
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_locations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_stock_locations
    (
        stock_location_id   BIGINT          NOT NULL IDENTITY(1, 1),
        location_code       NVARCHAR(80)    NOT NULL,
        location_name       NVARCHAR(200)   NOT NULL,
        location_type       NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_p4_stock_locations_location_type DEFAULT (N'MAIN_WAREHOUSE'),
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_p4_stock_locations_is_active DEFAULT (1),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_p4_stock_locations_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_p4_stock_locations PRIMARY KEY CLUSTERED (stock_location_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_stock_balances
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_balances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_stock_balances
    (
        stock_balance_id        BIGINT          NOT NULL IDENTITY(1, 1),
        inventory_item_id       BIGINT          NOT NULL,
        stock_location_id       BIGINT          NULL,
        available_qty           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_stock_balances_available_qty DEFAULT (0),
        reserved_qty            DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_stock_balances_reserved_qty DEFAULT (0),
        pending_receive_qty     DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_stock_balances_pending_receive_qty DEFAULT (0),
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_stock_balances PRIMARY KEY CLUSTERED (stock_balance_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_part_reservations
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_part_reservations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_part_reservations
    (
        reservation_id          BIGINT          NOT NULL IDENTITY(1, 1),
        inventory_item_id       BIGINT          NOT NULL,
        operation_case_id       BIGINT          NULL,
        service_step_id         BIGINT          NULL,
        rule_decision_id        BIGINT          NULL,
        requested_qty           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_part_reservations_requested_qty DEFAULT (1),
        reserved_qty            DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_part_reservations_reserved_qty DEFAULT (0),
        reservation_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_part_reservations_reservation_status DEFAULT (N'PENDING'),
        reservation_reason      NVARCHAR(1000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_part_reservations_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_part_reservations PRIMARY KEY CLUSTERED (reservation_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_suppliers
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_suppliers', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_suppliers
    (
        supplier_id         BIGINT          NOT NULL IDENTITY(1, 1),
        supplier_code       NVARCHAR(80)    NOT NULL,
        supplier_name       NVARCHAR(300)   NOT NULL,
        supplier_type       NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_suppliers_supplier_type DEFAULT (N'LOCAL'),
        contact_name        NVARCHAR(200)   NULL,
        mobile              NVARCHAR(50)    NULL,
        phone               NVARCHAR(50)    NULL,
        address_text        NVARCHAR(1000)  NULL,
        notes               NVARCHAR(1000)  NULL,
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_suppliers_is_active DEFAULT (1),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_suppliers_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_suppliers PRIMARY KEY CLUSTERED (supplier_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6. dbo.erp_purchase_requests (Phase 4 — skipped if M26 table exists)
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_purchase_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_purchase_requests
    (
        purchase_request_id     BIGINT          NOT NULL IDENTITY(1, 1),
        request_code            NVARCHAR(80)    NOT NULL,
        inventory_item_id       BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        service_step_id         BIGINT          NULL,
        rule_decision_id        BIGINT          NULL,
        supplier_id             BIGINT          NULL,
        requested_part_name     NVARCHAR(300)   NOT NULL,
        requested_part_code     NVARCHAR(100)   NULL,
        requested_qty           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_p4_purchase_requests_requested_qty DEFAULT (1),
        urgency_level           NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_p4_purchase_requests_urgency DEFAULT (N'NORMAL'),
        purchase_source         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_p4_purchase_requests_source DEFAULT (N'LOCAL'),
        request_status          NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_p4_purchase_requests_status DEFAULT (N'DRAFT'),
        estimated_cost          DECIMAL(18, 2)  NULL,
        internal_note           NVARCHAR(1500)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_p4_purchase_requests_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_p4_purchase_requests PRIMARY KEY CLUSTERED (purchase_request_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6b. Extension purchase table when M26 erp_purchase_requests already exists
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_purchase_requests', N'U') IS NOT NULL
   AND COL_LENGTH(N'dbo.erp_purchase_requests', N'request_code') IS NULL
   AND OBJECT_ID(N'dbo.erp_inventory_purchase_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_inventory_purchase_requests
    (
        purchase_request_id     BIGINT          NOT NULL IDENTITY(1, 1),
        request_code            NVARCHAR(80)    NOT NULL,
        inventory_item_id       BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        service_step_id         BIGINT          NULL,
        rule_decision_id        BIGINT          NULL,
        supplier_id             BIGINT          NULL,
        requested_part_name     NVARCHAR(300)   NOT NULL,
        requested_part_code     NVARCHAR(100)   NULL,
        requested_qty           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_inv_purchase_requests_qty DEFAULT (1),
        urgency_level           NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_inv_purchase_requests_urgency DEFAULT (N'NORMAL'),
        purchase_source         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_inv_purchase_requests_source DEFAULT (N'LOCAL'),
        request_status          NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_inv_purchase_requests_status DEFAULT (N'DRAFT'),
        estimated_cost          DECIMAL(18, 2)  NULL,
        internal_note           NVARCHAR(1500)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_inv_purchase_requests_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_inventory_purchase_requests PRIMARY KEY CLUSTERED (purchase_request_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   7. dbo.erp_stock_movements (Phase 4 — skipped if M22 exists)
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_movements', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_stock_movements
    (
        stock_movement_id       BIGINT          NOT NULL IDENTITY(1, 1),
        inventory_item_id       BIGINT          NULL,
        stock_location_id       BIGINT          NULL,
        reservation_id          BIGINT          NULL,
        purchase_request_id     BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        movement_type           NVARCHAR(80)    NOT NULL,
        movement_qty            DECIMAL(18, 2)  NOT NULL,
        movement_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_p4_stock_movements_status DEFAULT (N'RECORDED'),
        movement_note           NVARCHAR(1500)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_p4_stock_movements_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        source_ip               NVARCHAR(100)   NULL,
        user_agent              NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_p4_stock_movements PRIMARY KEY CLUSTERED (stock_movement_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   7b. Extension movements when M22 erp_stock_movements exists
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_stock_movements', N'U') IS NOT NULL
   AND COL_LENGTH(N'dbo.erp_stock_movements', N'inventory_item_id') IS NULL
   AND OBJECT_ID(N'dbo.erp_inventory_stock_movements', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_inventory_stock_movements
    (
        stock_movement_id       BIGINT          NOT NULL IDENTITY(1, 1),
        inventory_item_id       BIGINT          NULL,
        stock_location_id       BIGINT          NULL,
        reservation_id          BIGINT          NULL,
        purchase_request_id     BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        movement_type           NVARCHAR(80)    NOT NULL,
        movement_qty            DECIMAL(18, 2)  NOT NULL,
        movement_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_inv_stock_movements_status DEFAULT (N'RECORDED'),
        movement_note           NVARCHAR(1500)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_inv_stock_movements_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        source_ip               NVARCHAR(100)   NULL,
        user_agent              NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_inventory_stock_movements PRIMARY KEY CLUSTERED (stock_movement_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   8. dbo.erp_inventory_purchase_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_inventory_purchase_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_inventory_purchase_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_inventory_purchase_history_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_inventory_purchase_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* Indexes */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_items_item_code' AND object_id = OBJECT_ID(N'dbo.erp_inventory_items', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_items_item_code ON dbo.erp_inventory_items (item_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_items_item_name' AND object_id = OBJECT_ID(N'dbo.erp_inventory_items', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_items_item_name ON dbo.erp_inventory_items (item_name); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_items_legacy_part_id' AND object_id = OBJECT_ID(N'dbo.erp_inventory_items', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_items_legacy_part_id ON dbo.erp_inventory_items (legacy_part_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_stock_balances_inventory_item_id' AND object_id = OBJECT_ID(N'dbo.erp_stock_balances', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_stock_balances_inventory_item_id ON dbo.erp_stock_balances (inventory_item_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_stock_balances_stock_location_id' AND object_id = OBJECT_ID(N'dbo.erp_stock_balances', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_stock_balances_stock_location_id ON dbo.erp_stock_balances (stock_location_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_part_reservations_inventory_item_id' AND object_id = OBJECT_ID(N'dbo.erp_part_reservations', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_part_reservations_inventory_item_id ON dbo.erp_part_reservations (inventory_item_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_part_reservations_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_part_reservations', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_part_reservations_operation_case_id ON dbo.erp_part_reservations (operation_case_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_part_reservations_reservation_status' AND object_id = OBJECT_ID(N'dbo.erp_part_reservations', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_part_reservations_reservation_status ON dbo.erp_part_reservations (reservation_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_suppliers_supplier_code' AND object_id = OBJECT_ID(N'dbo.erp_suppliers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_suppliers_supplier_code ON dbo.erp_suppliers (supplier_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_suppliers_supplier_name' AND object_id = OBJECT_ID(N'dbo.erp_suppliers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_suppliers_supplier_name ON dbo.erp_suppliers (supplier_name); END;
GO

/* Purchase / movement indexes on whichever table exists */
IF OBJECT_ID(N'dbo.erp_purchase_requests', N'U') IS NOT NULL AND COL_LENGTH(N'dbo.erp_purchase_requests', N'request_code') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_p4_purchase_requests_request_code' AND object_id = OBJECT_ID(N'dbo.erp_purchase_requests', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_p4_purchase_requests_request_code ON dbo.erp_purchase_requests (request_code); END;
END;
GO
IF OBJECT_ID(N'dbo.erp_inventory_purchase_requests', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inv_purchase_requests_request_code' AND object_id = OBJECT_ID(N'dbo.erp_inventory_purchase_requests', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_inv_purchase_requests_request_code ON dbo.erp_inventory_purchase_requests (request_code); END;
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inv_purchase_requests_request_status' AND object_id = OBJECT_ID(N'dbo.erp_inventory_purchase_requests', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_inv_purchase_requests_request_status ON dbo.erp_inventory_purchase_requests (request_status); END;
END;
GO
IF OBJECT_ID(N'dbo.erp_inventory_stock_movements', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inv_stock_movements_inventory_item_id' AND object_id = OBJECT_ID(N'dbo.erp_inventory_stock_movements', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_inv_stock_movements_inventory_item_id ON dbo.erp_inventory_stock_movements (inventory_item_id); END;
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inv_stock_movements_movement_type' AND object_id = OBJECT_ID(N'dbo.erp_inventory_stock_movements', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_inv_stock_movements_movement_type ON dbo.erp_inventory_stock_movements (movement_type); END;
END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_purchase_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_inventory_purchase_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_purchase_history_entity ON dbo.erp_inventory_purchase_history (entity_type, entity_id); END;
GO

/* Seeds — stock locations (M22 or Phase 4) */
IF OBJECT_ID(N'dbo.erp_stock_locations', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM dbo.erp_stock_locations WHERE location_code = N'MAIN')
    BEGIN
        IF COL_LENGTH(N'dbo.erp_stock_locations', N'location_type') IS NOT NULL
            INSERT INTO dbo.erp_stock_locations (location_code, location_name, location_type, is_active, created_by)
            VALUES (N'MAIN', N'انبار اصلی', N'MAIN_WAREHOUSE', 1, N'SYSTEM_SEED');
        ELSE
            INSERT INTO dbo.erp_stock_locations (location_code, location_name, location_type, is_active)
            VALUES (N'MAIN', N'انبار اصلی', N'WAREHOUSE', 1);
    END;
    IF NOT EXISTS (SELECT 1 FROM dbo.erp_stock_locations WHERE location_code = N'PENDING')
    BEGIN
        IF COL_LENGTH(N'dbo.erp_stock_locations', N'location_type') IS NOT NULL
            INSERT INTO dbo.erp_stock_locations (location_code, location_name, location_type, is_active, created_by)
            VALUES (N'PENDING', N'در انتظار دریافت', N'PENDING_RECEIVE', 1, N'SYSTEM_SEED');
        ELSE
            INSERT INTO dbo.erp_stock_locations (location_code, location_name, location_type, is_active)
            VALUES (N'PENDING', N'در انتظار دریافت', N'STAGING', 1);
    END;
END;
GO

IF OBJECT_ID(N'dbo.erp_suppliers', N'U') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM dbo.erp_suppliers WHERE supplier_code = N'INTERNAL-MANUAL')
BEGIN
    INSERT INTO dbo.erp_suppliers (supplier_code, supplier_name, supplier_type, notes, created_by)
    VALUES (N'INTERNAL-MANUAL', N'تامین‌کننده دستی داخلی', N'LOCAL', N'Placeholder seed for Phase 4', N'SYSTEM_SEED');
END;
GO

PRINT N'Phase 4 Inventory & Purchase System SQL completed.';
GO
