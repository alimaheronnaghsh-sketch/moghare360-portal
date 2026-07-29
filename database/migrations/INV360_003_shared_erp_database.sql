/* INV360_003 — Inventory360 tables inside moghare360_ERP (inv360_ prefix). Additive/idempotent. No ERP table changes. */
SET NOCOUNT ON; SET XACT_ABORT ON; SET QUOTED_IDENTIFIER ON; SET ANSI_NULLS ON;
IF DB_NAME() <> N'moghare360_ERP' THROW 57001, 'INV360_003 must run on moghare360_ERP only.', 1;
BEGIN TRY BEGIN TRANSACTION;
IF OBJECT_ID(N'dbo.inv360_users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_users (
        user_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            username NVARCHAR(50) NOT NULL,
            display_name NVARCHAR(120) NULL,
            password_hash NVARCHAR(255) NOT NULL,
            role_code NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_users_role DEFAULT (N'OWNER_ADMIN'),
            is_active BIT NOT NULL CONSTRAINT DF_inv360_users_active DEFAULT (1),
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_users_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_users_username UNIQUE (username)
    );
END;


IF OBJECT_ID(N'dbo.inv360_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_items (
        item_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            item_code NVARCHAR(40) NULL,
            workshop_code NVARCHAR(50) NULL,
            technical_code NVARCHAR(100) NULL,
            item_name_fa NVARCHAR(200) NOT NULL,
            item_name_en NVARCHAR(200) NULL,
            common_name NVARCHAR(200) NULL,
            brand NVARCHAR(100) NULL,
            manufacturer NVARCHAR(100) NULL,
            country_of_origin NVARCHAR(80) NULL,
            part_number NVARCHAR(100) NULL,
            oem_code NVARCHAR(100) NULL,
            alternative_codes NVARCHAR(500) NULL,
            barcode NVARCHAR(100) NULL,
            qr_code NVARCHAR(100) NULL,
            unit NVARCHAR(40) NULL,
            item_type NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_items_type DEFAULT (N'spare_part'),
            category_name NVARCHAR(100) NULL,
            subcategory NVARCHAR(100) NULL,
            family_name NVARCHAR(100) NULL,
            tech_specs NVARCHAR(1000) NULL,
            dimensions_text NVARCHAR(120) NULL,
            weight_kg DECIMAL(18,3) NULL,
            color_name NVARCHAR(60) NULL,
            material_name NVARCHAR(80) NULL,
            capacity_text NVARCHAR(80) NULL,
            install_side NVARCHAR(20) NOT NULL CONSTRAINT DF_inv360_items_side DEFAULT (N'none'),
            item_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_items_status DEFAULT (N'active'),
            min_stock DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_items_min DEFAULT (0),
            max_stock DECIMAL(18,3) NULL,
            reorder_point DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_items_rop DEFAULT (0),
            standard_cost DECIMAL(18,4) NULL,
            last_purchase_price DECIMAL(18,4) NULL,
            search_norm NVARCHAR(1000) NULL,
            quantity DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_items_qty DEFAULT (0.001),
            is_active BIT NOT NULL CONSTRAINT DF_inv360_items_active DEFAULT (1),
            is_deleted BIT NOT NULL CONSTRAINT DF_inv360_items_del DEFAULT (0),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_items_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL
    );
END;


IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_workshop' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_workshop ON dbo.inv360_items(workshop_code);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_tech' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_tech ON dbo.inv360_items(technical_code);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_name' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_name ON dbo.inv360_items(item_name_fa);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_pn' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_pn ON dbo.inv360_items(part_number);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_oem' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_oem ON dbo.inv360_items(oem_code);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_barcode' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_barcode ON dbo.inv360_items(barcode);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_inv360_items_alt' AND object_id=OBJECT_ID(N'dbo.inv360_items')) CREATE INDEX IX_inv360_items_alt ON dbo.inv360_items(alternative_codes);
IF OBJECT_ID(N'dbo.inv360_warehouses', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_warehouses (
        warehouse_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            warehouse_code NVARCHAR(40) NOT NULL,
            warehouse_name NVARCHAR(120) NOT NULL,
            warehouse_type NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_wh_type DEFAULT (N'main'),
            company_name NVARCHAR(120) NULL,
            branch_name NVARCHAR(120) NULL,
            is_active BIT NOT NULL CONSTRAINT DF_inv360_wh_active DEFAULT (1),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_wh_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_wh_code UNIQUE (warehouse_code)
    );
END;


IF OBJECT_ID(N'dbo.inv360_locations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_locations (
        location_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            warehouse_id INT NOT NULL,
            location_code NVARCHAR(80) NOT NULL,
            location_name NVARCHAR(120) NULL,
            zone_code NVARCHAR(20) NULL,
            aisle_code NVARCHAR(20) NULL,
            rack_code NVARCHAR(20) NULL,
            shelf_code NVARCHAR(20) NULL,
            bin_code NVARCHAR(20) NULL,
            is_active BIT NOT NULL CONSTRAINT DF_inv360_loc_active DEFAULT (1),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_loc_created DEFAULT (SYSUTCDATETIME())
    );
END;


IF OBJECT_ID(N'dbo.inv360_stock_balances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_stock_balances (
        balance_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            item_id BIGINT NOT NULL,
            warehouse_id INT NULL,
            location_id BIGINT NULL,
            physical_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_phys DEFAULT (0),
            reserved_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_res DEFAULT (0),
            quarantine_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_qua DEFAULT (0),
            in_transit_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_trn DEFAULT (0),
            blocked_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_blk DEFAULT (0),
            consignment_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_bal_con DEFAULT (0),
            unit_cost DECIMAL(18,4) NULL,
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_bal_upd DEFAULT (SYSUTCDATETIME())
    );
END;


IF OBJECT_ID(N'dbo.inv360_stock_documents', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_stock_documents (
        document_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            doc_no NVARCHAR(40) NOT NULL,
            doc_type NVARCHAR(40) NOT NULL,
            doc_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_doc_st DEFAULT (N'draft'),
            doc_date DATETIME2 NOT NULL CONSTRAINT DF_inv360_doc_dt DEFAULT (SYSUTCDATETIME()),
            source_warehouse_id INT NULL,
            source_location_id BIGINT NULL,
            target_warehouse_id INT NULL,
            target_location_id BIGINT NULL,
            reason_text NVARCHAR(500) NULL,
            reference_type NVARCHAR(40) NULL,
            reference_no NVARCHAR(80) NULL,
            purpose_type NVARCHAR(40) NULL,
            purpose_ref NVARCHAR(80) NULL,
            future_jobcard_ref NVARCHAR(80) NULL,
            cost_center NVARCHAR(80) NULL,
            notes NVARCHAR(1000) NULL,
            created_by INT NULL,
            approved_by INT NULL,
            posted_by INT NULL,
            posted_at DATETIME2 NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_doc_cr DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_doc_no UNIQUE (doc_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_stock_document_lines', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_stock_document_lines (
        line_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            document_id BIGINT NOT NULL,
            item_id BIGINT NOT NULL,
            qty DECIMAL(18,3) NOT NULL,
            unit_cost DECIMAL(18,4) NULL,
            line_note NVARCHAR(300) NULL
    );
END;


IF OBJECT_ID(N'dbo.inv360_reservations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_reservations (
        reservation_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            item_id BIGINT NOT NULL,
            warehouse_id INT NULL,
            location_id BIGINT NULL,
            qty DECIMAL(18,3) NOT NULL,
            purpose_code NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_res_p DEFAULT (N'manual'),
            purpose_ref NVARCHAR(80) NULL,
            res_status NVARCHAR(30) NOT NULL CONSTRAINT DF_inv360_res_s DEFAULT (N'active'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_res_c DEFAULT (SYSUTCDATETIME()),
            released_at DATETIME2 NULL
    );
END;


IF OBJECT_ID(N'dbo.inv360_stock_counts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_stock_counts (
        count_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            item_id BIGINT NOT NULL,
            warehouse_id INT NULL,
            location_id BIGINT NULL,
            system_qty DECIMAL(18,3) NULL,
            counted_qty DECIMAL(18,3) NOT NULL,
            variance_qty DECIMAL(18,3) NULL,
            reason_text NVARCHAR(500) NOT NULL,
            count_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_cnt_st DEFAULT (N'approved'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_cnt_c DEFAULT (SYSUTCDATETIME())
    );
END;


IF OBJECT_ID(N'dbo.inv360_suppliers', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_suppliers (
        supplier_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            supplier_code NVARCHAR(40) NOT NULL,
            supplier_name NVARCHAR(200) NOT NULL,
            contact_name NVARCHAR(120) NULL,
            contact_phone NVARCHAR(40) NULL,
            payment_terms NVARCHAR(200) NULL,
            supplier_status NVARCHAR(30) NOT NULL CONSTRAINT DF_inv360_sup_st DEFAULT (N'active'),
            is_active BIT NOT NULL CONSTRAINT DF_inv360_sup_a DEFAULT (1),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_sup_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_sup_code UNIQUE (supplier_code)
    );
END;


IF OBJECT_ID(N'dbo.inv360_purchase_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_purchase_requests (
        pr_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            pr_no NVARCHAR(40) NOT NULL,
            requester_name NVARCHAR(120) NULL,
            department_name NVARCHAR(120) NULL,
            needed_date DATE NULL,
            urgency_code NVARCHAR(20) NOT NULL CONSTRAINT DF_inv360_pr_u DEFAULT (N'normal'),
            reason_text NVARCHAR(500) NULL,
            item_id BIGINT NULL,
            item_text NVARCHAR(200) NULL,
            qty DECIMAL(18,3) NOT NULL,
            current_stock_snapshot DECIMAL(18,3) NULL,
            suggested_suppliers NVARCHAR(300) NULL,
            pr_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_pr_s DEFAULT (N'submitted'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_pr_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_pr_no UNIQUE (pr_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_rfqs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_rfqs (
        rfq_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            rfq_no NVARCHAR(40) NOT NULL,
            pr_id INT NULL,
            supplier_id INT NULL,
            item_id BIGINT NULL,
            item_text NVARCHAR(200) NULL,
            unit_price DECIMAL(18,4) NOT NULL,
            discount_pct DECIMAL(9,2) NOT NULL CONSTRAINT DF_inv360_rfq_d DEFAULT (0),
            tax_pct DECIMAL(9,2) NOT NULL CONSTRAINT DF_inv360_rfq_t DEFAULT (0),
            freight_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_inv360_rfq_f DEFAULT (0),
            delivery_days INT NULL,
            payment_terms NVARCHAR(200) NULL,
            quality_grade NVARCHAR(40) NULL,
            warranty_text NVARCHAR(200) NULL,
            currency_code NVARCHAR(10) NOT NULL CONSTRAINT DF_inv360_rfq_c DEFAULT (N'IRR'),
            valid_until DATE NULL,
            total_score DECIMAL(9,2) NULL,
            rfq_status NVARCHAR(30) NOT NULL CONSTRAINT DF_inv360_rfq_s DEFAULT (N'received'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_rfq_cr DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_rfq_no UNIQUE (rfq_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_purchase_orders', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_purchase_orders (
        po_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            po_no NVARCHAR(40) NOT NULL,
            supplier_id INT NULL,
            pr_id INT NULL,
            rfq_id INT NULL,
            currency_code NVARCHAR(10) NOT NULL CONSTRAINT DF_inv360_po_c DEFAULT (N'IRR'),
            exchange_rate DECIMAL(18,6) NOT NULL CONSTRAINT DF_inv360_po_x DEFAULT (1),
            delivery_place NVARCHAR(200) NULL,
            payment_terms NVARCHAR(200) NULL,
            warranty_text NVARCHAR(200) NULL,
            delay_penalty NVARCHAR(200) NULL,
            responsible_name NVARCHAR(120) NULL,
            po_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_po_s DEFAULT (N'approved'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_po_cr DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_po_no UNIQUE (po_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_purchase_order_lines', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_purchase_order_lines (
        po_line_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            po_id INT NOT NULL,
            item_id BIGINT NULL,
            item_text NVARCHAR(200) NOT NULL,
            ordered_qty DECIMAL(18,3) NOT NULL,
            received_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_pol_r DEFAULT (0),
            unit_price DECIMAL(18,4) NOT NULL,
            discount_pct DECIMAL(9,2) NOT NULL CONSTRAINT DF_inv360_pol_d DEFAULT (0),
            tax_pct DECIMAL(9,2) NOT NULL CONSTRAINT DF_inv360_pol_t DEFAULT (0)
    );
END;


IF OBJECT_ID(N'dbo.inv360_goods_receipts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_goods_receipts (
        gr_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            gr_no NVARCHAR(40) NOT NULL,
            po_id INT NULL,
            warehouse_id INT NULL,
            location_id BIGINT NULL,
            receipt_type NVARCHAR(40) NULL,
            gr_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_gr_s DEFAULT (N'draft'),
            qty_control_note NVARCHAR(500) NULL,
            quality_control_note NVARCHAR(500) NULL,
            document_control_note NVARCHAR(500) NULL,
            qc_result NVARCHAR(30) NULL,
            created_by INT NULL,
            posted_at DATETIME2 NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_gr_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_gr_no UNIQUE (gr_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_goods_receipt_lines', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_goods_receipt_lines (
        gr_line_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            gr_id INT NOT NULL,
            po_line_id INT NULL,
            item_id BIGINT NULL,
            item_text NVARCHAR(200) NOT NULL,
            received_qty DECIMAL(18,3) NOT NULL,
            accepted_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_grl_a DEFAULT (0),
            rejected_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_grl_r DEFAULT (0),
            quarantine_qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_grl_q DEFAULT (0),
            unit_cost DECIMAL(18,4) NULL
    );
END;


IF OBJECT_ID(N'dbo.inv360_qc_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_qc_events (
        qc_event_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            gr_id INT NULL,
            item_id BIGINT NULL,
            action_code NVARCHAR(40) NOT NULL,
            qty DECIMAL(18,3) NOT NULL,
            note_text NVARCHAR(500) NULL,
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_qc_c DEFAULT (SYSUTCDATETIME())
    );
END;


IF OBJECT_ID(N'dbo.inv360_supplier_returns', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_supplier_returns (
        supplier_return_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            return_no NVARCHAR(40) NOT NULL,
            supplier_id INT NULL,
            item_id BIGINT NULL,
            qty DECIMAL(18,3) NOT NULL,
            reason_text NVARCHAR(500) NOT NULL,
            return_status NVARCHAR(30) NOT NULL CONSTRAINT DF_inv360_sr_s DEFAULT (N'posted'),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_sr_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_sr_no UNIQUE (return_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_landed_costs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_landed_costs (
        landed_cost_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            ref_no NVARCHAR(40) NOT NULL,
            item_id BIGINT NULL,
            valuation_method NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_lc_m DEFAULT (N'weighted_average'),
            purchase_price DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_pp DEFAULT (0),
            foreign_freight DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_ff DEFAULT (0),
            insurance_amount DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_i DEFAULT (0),
            bank_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_b DEFAULT (0),
            inspection_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_in DEFAULT (0),
            customs_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_cu DEFAULT (0),
            duties_amount DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_du DEFAULT (0),
            warehousing_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_w DEFAULT (0),
            clearance_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_cl DEFAULT (0),
            inland_freight DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_if DEFAULT (0),
            broker_fee DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_br DEFAULT (0),
            other_direct_cost DECIMAL(18,4) NOT NULL CONSTRAINT DF_inv360_lc_o DEFAULT (0),
            allocation_method NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_lc_a DEFAULT (N'by_value'),
            qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_inv360_lc_q DEFAULT (1),
            landed_unit_cost DECIMAL(18,4) NULL,
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_lc_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_lc_ref UNIQUE (ref_no)
    );
END;


IF OBJECT_ID(N'dbo.inv360_logistics_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_logistics_requests (
        logistics_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            request_code NVARCHAR(40) NOT NULL,
            carrier_name NVARCHAR(120) NULL,
            vehicle_plate NVARCHAR(40) NULL,
            driver_name NVARCHAR(120) NULL,
            waybill_no NVARCHAR(80) NULL,
            route_text NVARCHAR(200) NULL,
            origin_text NVARCHAR(200) NULL,
            destination_text NVARCHAR(200) NULL,
            weight_kg DECIMAL(18,3) NULL,
            volume_m3 DECIMAL(18,3) NULL,
            package_count INT NULL,
            freight_cost DECIMAL(18,2) NULL,
            tracking_code NVARCHAR(80) NULL,
            proof_note NVARCHAR(500) NULL,
            logistics_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_log_s DEFAULT (N'planned'),
            planned_at DATETIME2 NULL,
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_log_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_log_code UNIQUE (request_code)
    );
END;


IF OBJECT_ID(N'dbo.inv360_tools_assets', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_tools_assets (
        tool_asset_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            tool_code NVARCHAR(40) NOT NULL,
            tool_name NVARCHAR(200) NOT NULL,
            serial_number NVARCHAR(80) NULL,
            location_text NVARCHAR(120) NULL,
            assigned_user_name NVARCHAR(120) NULL,
            health_status NVARCHAR(40) NOT NULL CONSTRAINT DF_inv360_tool_h DEFAULT (N'good'),
            is_active BIT NOT NULL CONSTRAINT DF_inv360_tool_a DEFAULT (1),
            created_by INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_tool_c DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_inv360_tool_code UNIQUE (tool_code)
    );
END;


IF OBJECT_ID(N'dbo.inv360_tool_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_tool_events (
        tool_event_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            tool_asset_id INT NOT NULL,
            event_type NVARCHAR(30) NOT NULL,
            event_user_name NVARCHAR(120) NULL,
            event_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_te_a DEFAULT (SYSUTCDATETIME()),
            note_text NVARCHAR(500) NULL,
            created_by INT NULL
    );
END;


IF OBJECT_ID(N'dbo.inv360_app_audit', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_app_audit (
        app_audit_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            entity_type NVARCHAR(60) NOT NULL,
            entity_id NVARCHAR(60) NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            event_note NVARCHAR(1000) NULL,
            actor_user_id INT NULL,
            actor_username NVARCHAR(50) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_aa_c DEFAULT (SYSUTCDATETIME())
    );
END;


IF OBJECT_ID(N'dbo.inv360_settings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv360_settings (
        setting_key NVARCHAR(80) NOT NULL PRIMARY KEY,
            setting_value NVARCHAR(500) NULL,
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_inv360_set_u DEFAULT (SYSUTCDATETIME())
    );
END;


COMMIT TRANSACTION; END TRY BEGIN CATCH IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION; THROW; END CATCH;