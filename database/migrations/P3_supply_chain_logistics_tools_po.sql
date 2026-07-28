/*
 * MOGHARE360 Prompt 3 additive — logistics / tools-assets / purchase-order foundation.
 * Local application only. Idempotent and non-destructive.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_parts', N'U') IS NULL
        THROW 53001, 'P3 supply-chain preflight failed: erp_parts missing.', 1;
    IF OBJECT_ID(N'dbo.erp_suppliers', N'U') IS NULL
        THROW 53002, 'P3 supply-chain preflight failed: erp_suppliers missing.', 1;
    IF OBJECT_ID(N'dbo.erp_stock_locations', N'U') IS NULL
        THROW 53003, 'P3 supply-chain preflight failed: erp_stock_locations missing.', 1;

    IF OBJECT_ID(N'dbo.erp_purchase_orders', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_purchase_orders (
            purchase_order_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            po_number NVARCHAR(40) NOT NULL,
            supplier_id INT NULL,
            purchase_request_id INT NULL,
            po_status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_po_status DEFAULT (N'DRAFT'),
            ordered_at DATETIME2 NULL,
            expected_receive_at DATETIME2 NULL,
            currency_code NVARCHAR(10) NOT NULL CONSTRAINT DF_erp_po_currency DEFAULT (N'IRR'),
            notes NVARCHAR(1000) NULL,
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_po_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            is_active BIT NOT NULL CONSTRAINT DF_erp_po_active DEFAULT (1),
            CONSTRAINT UQ_erp_purchase_orders_po_number UNIQUE (po_number),
            CONSTRAINT CK_erp_purchase_orders_status CHECK (po_status IN (
                N'DRAFT', N'APPROVED', N'SENT', N'PARTIAL_RECEIVED', N'RECEIVED', N'CANCELLED'
            ))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_purchase_order_lines', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_purchase_order_lines (
            po_line_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            purchase_order_id INT NOT NULL,
            part_id INT NULL,
            item_code NVARCHAR(80) NULL,
            item_name NVARCHAR(200) NOT NULL,
            ordered_qty DECIMAL(18,3) NOT NULL,
            received_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_erp_po_line_recv DEFAULT (0),
            unit_cost DECIMAL(18,2) NULL,
            line_note NVARCHAR(500) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_po_line_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT FK_erp_po_lines_po FOREIGN KEY (purchase_order_id) REFERENCES dbo.erp_purchase_orders(purchase_order_id),
            CONSTRAINT CK_erp_po_lines_qty CHECK (ordered_qty > 0 AND received_qty >= 0)
        );
    END;

    IF OBJECT_ID(N'dbo.erp_logistics_requests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_logistics_requests (
            logistics_request_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            request_code NVARCHAR(40) NOT NULL,
            jobcard_id INT NULL,
            purchase_order_id INT NULL,
            carrier_name NVARCHAR(200) NULL,
            driver_name NVARCHAR(200) NULL,
            vehicle_plate NVARCHAR(40) NULL,
            origin_text NVARCHAR(300) NULL,
            destination_text NVARCHAR(300) NULL,
            package_count INT NULL,
            weight_kg DECIMAL(18,3) NULL,
            volume_m3 DECIMAL(18,3) NULL,
            logistics_status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_logistics_status DEFAULT (N'PLANNED'),
            planned_at DATETIME2 NULL,
            loaded_at DATETIME2 NULL,
            dispatched_at DATETIME2 NULL,
            delivered_at DATETIME2 NULL,
            proof_blob_id INT NULL,
            notes NVARCHAR(1000) NULL,
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_logistics_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            is_active BIT NOT NULL CONSTRAINT DF_erp_logistics_active DEFAULT (1),
            CONSTRAINT UQ_erp_logistics_request_code UNIQUE (request_code),
            CONSTRAINT CK_erp_logistics_status CHECK (logistics_status IN (
                N'PLANNED', N'LOADED', N'DISPATCHED', N'DELIVERED', N'CANCELLED'
            ))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_tools_assets', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_tools_assets (
            tool_asset_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            tool_code NVARCHAR(40) NOT NULL,
            tool_name NVARCHAR(200) NOT NULL,
            serial_number NVARCHAR(80) NULL,
            tool_type NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_tools_type DEFAULT (N'TOOL'),
            condition_status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_tools_condition DEFAULT (N'GOOD'),
            calibration_due_at DATE NULL,
            assigned_user_id INT NULL,
            is_active BIT NOT NULL CONSTRAINT DF_erp_tools_active DEFAULT (1),
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_tools_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            CONSTRAINT UQ_erp_tools_assets_code UNIQUE (tool_code),
            CONSTRAINT CK_erp_tools_type CHECK (tool_type IN (N'TOOL', N'ASSET', N'EQUIPMENT')),
            CONSTRAINT CK_erp_tools_condition CHECK (condition_status IN (
                N'GOOD', N'NEEDS_SERVICE', N'DAMAGED', N'MISSING', N'RETIRED'
            ))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_tool_issue_events', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_tool_issue_events (
            tool_issue_event_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            tool_asset_id INT NOT NULL,
            issued_to_user_id INT NOT NULL,
            issued_by_user_id INT NULL,
            issue_status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_tool_issue_status DEFAULT (N'ISSUED'),
            issued_at DATETIME2 NOT NULL CONSTRAINT DF_erp_tool_issue_at DEFAULT (SYSUTCDATETIME()),
            returned_at DATETIME2 NULL,
            condition_on_return NVARCHAR(40) NULL,
            notes NVARCHAR(500) NULL,
            CONSTRAINT FK_erp_tool_issue_asset FOREIGN KEY (tool_asset_id) REFERENCES dbo.erp_tools_assets(tool_asset_id),
            CONSTRAINT CK_erp_tool_issue_status CHECK (issue_status IN (N'ISSUED', N'RETURNED', N'LOST'))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_supply_chain_audit_events', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_supply_chain_audit_events (
            audit_event_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            entity_type NVARCHAR(40) NOT NULL,
            entity_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            event_note NVARCHAR(1000) NULL,
            created_by_user_id INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_sc_audit_created DEFAULT (SYSUTCDATETIME())
        );
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
