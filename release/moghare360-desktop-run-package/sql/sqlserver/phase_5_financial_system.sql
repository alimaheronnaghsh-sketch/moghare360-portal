/*
================================================================================
MOGHARE360 ERP — Phase 5 Financial System
Script: phase_5_financial_system.sql
================================================================================

Internal financial layer: pricing, jobcard cost engine, payment tracking,
invoice preview (non-official), financial summary snapshots.

Does NOT create official accounting ledger, tax engine, or final invoice tables.
Does NOT drop/rename legacy Payments / erp_payments.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_finance_service_price_list
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_finance_service_price_list', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_finance_service_price_list
    (
        service_price_id    BIGINT          NOT NULL IDENTITY(1, 1),
        legacy_service_id   BIGINT          NULL,
        service_code        NVARCHAR(100)   NOT NULL,
        service_name        NVARCHAR(300)   NOT NULL,
        service_category    NVARCHAR(100)   NULL,
        base_price          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_fin_svc_price_base DEFAULT (0),
        labour_hours        DECIMAL(18, 2)  NULL,
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_fin_svc_price_active DEFAULT (1),
        effective_from      DATE            NULL,
        effective_to        DATE            NULL,
        notes               NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_svc_price_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_finance_service_price_list PRIMARY KEY CLUSTERED (service_price_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_finance_labour_rates
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_finance_labour_rates', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_finance_labour_rates
    (
        labour_rate_id      BIGINT          NOT NULL IDENTITY(1, 1),
        rate_code           NVARCHAR(100)   NOT NULL,
        rate_name           NVARCHAR(200)   NOT NULL,
        hourly_rate         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_fin_labour_hourly DEFAULT (0),
        technician_level    NVARCHAR(100)   NULL,
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_fin_labour_active DEFAULT (1),
        effective_from      DATE            NULL,
        effective_to        DATE            NULL,
        notes               NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_labour_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_finance_labour_rates PRIMARY KEY CLUSTERED (labour_rate_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_finance_part_margin_rules
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_finance_part_margin_rules', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_finance_part_margin_rules
    (
        margin_rule_id          BIGINT          NOT NULL IDENTITY(1, 1),
        rule_code               NVARCHAR(100)   NOT NULL,
        rule_name               NVARCHAR(200)   NOT NULL,
        item_category           NVARCHAR(100)   NULL,
        supplier_type           NVARCHAR(100)   NULL,
        margin_percent          DECIMAL(9, 2)   NOT NULL
            CONSTRAINT DF_erp_fin_margin_pct DEFAULT (0),
        fixed_margin_amount     DECIMAL(18, 2)  NULL,
        is_active               BIT             NOT NULL
            CONSTRAINT DF_erp_fin_margin_active DEFAULT (1),
        notes                   NVARCHAR(1000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_margin_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_finance_part_margin_rules PRIMARY KEY CLUSTERED (margin_rule_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_jobcard_cost_headers
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcard_cost_headers', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_cost_headers
    (
        cost_header_id      BIGINT          NOT NULL IDENTITY(1, 1),
        operation_case_id   BIGINT          NULL,
        jobcard_id          BIGINT          NULL,
        customer_id         BIGINT          NULL,
        vehicle_binding_id  BIGINT          NULL,
        cost_code           NVARCHAR(100)   NOT NULL,
        calculation_status  NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_jc_cost_calc_status DEFAULT (N'DRAFT'),
        service_total       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_svc_total DEFAULT (0),
        labour_total        DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_lab_total DEFAULT (0),
        parts_total         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_parts_total DEFAULT (0),
        discount_total      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_disc_total DEFAULT (0),
        payable_total       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_payable DEFAULT (0),
        paid_total          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_paid DEFAULT (0),
        remaining_total     DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_remaining DEFAULT (0),
        payment_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_jc_cost_pay_status DEFAULT (N'UNPAID'),
        preview_note        NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_jc_cost_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_jobcard_cost_headers PRIMARY KEY CLUSTERED (cost_header_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_jobcard_cost_lines
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_jobcard_cost_lines', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_cost_lines
    (
        cost_line_id        BIGINT          NOT NULL IDENTITY(1, 1),
        cost_header_id      BIGINT          NOT NULL,
        operation_case_id   BIGINT          NULL,
        service_step_id     BIGINT          NULL,
        inventory_item_id   BIGINT          NULL,
        legacy_service_id   BIGINT          NULL,
        legacy_part_id      BIGINT          NULL,
        line_type           NVARCHAR(80)    NOT NULL,
        line_title          NVARCHAR(300)   NOT NULL,
        qty                 DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_line_qty DEFAULT (1),
        unit_price          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_line_unit DEFAULT (0),
        discount_amount     DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_line_disc DEFAULT (0),
        line_total          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_jc_cost_line_total DEFAULT (0),
        line_note           NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_jc_cost_line_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_jobcard_cost_lines PRIMARY KEY CLUSTERED (cost_line_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6. dbo.erp_payment_records (Phase 5 — skipped if legacy erp_payments exists)
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_payment_records', N'U') IS NULL
   AND OBJECT_ID(N'dbo.erp_payments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_payment_records
    (
        payment_record_id   BIGINT          NOT NULL IDENTITY(1, 1),
        cost_header_id      BIGINT          NULL,
        operation_case_id   BIGINT          NULL,
        jobcard_id          BIGINT          NULL,
        customer_id         BIGINT          NULL,
        payment_code        NVARCHAR(100)   NOT NULL,
        payment_method      NVARCHAR(80)    NOT NULL,
        payment_amount      DECIMAL(18, 2)  NOT NULL,
        payment_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_payment_records_status DEFAULT (N'RECORDED'),
        payment_reference   NVARCHAR(200)   NULL,
        payment_note        NVARCHAR(1500)  NULL,
        paid_at             DATETIME2       NOT NULL
            CONSTRAINT DF_erp_payment_records_paid_at DEFAULT (SYSUTCDATETIME()),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_payment_records_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_payment_records PRIMARY KEY CLUSTERED (payment_record_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6b. Extension when legacy dbo.erp_payments exists
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_payments', N'U') IS NOT NULL
   AND OBJECT_ID(N'dbo.erp_payment_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_payment_records
    (
        payment_record_id   BIGINT          NOT NULL IDENTITY(1, 1),
        cost_header_id      BIGINT          NULL,
        operation_case_id   BIGINT          NULL,
        jobcard_id          BIGINT          NULL,
        customer_id         BIGINT          NULL,
        payment_code        NVARCHAR(100)   NOT NULL,
        payment_method      NVARCHAR(80)    NOT NULL,
        payment_amount      DECIMAL(18, 2)  NOT NULL,
        payment_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_fin_payment_records_status DEFAULT (N'RECORDED'),
        payment_reference   NVARCHAR(200)   NULL,
        payment_note        NVARCHAR(1500)  NULL,
        paid_at             DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_payment_records_paid_at DEFAULT (SYSUTCDATETIME()),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_payment_records_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_fin_payment_records PRIMARY KEY CLUSTERED (payment_record_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   7. dbo.erp_invoice_previews
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_invoice_previews', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_invoice_previews
    (
        invoice_preview_id  BIGINT          NOT NULL IDENTITY(1, 1),
        cost_header_id      BIGINT          NOT NULL,
        preview_code        NVARCHAR(100)   NOT NULL,
        preview_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_invoice_prev_status DEFAULT (N'DRAFT'),
        service_total       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_svc DEFAULT (0),
        labour_total        DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_lab DEFAULT (0),
        parts_total         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_parts DEFAULT (0),
        discount_total      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_disc DEFAULT (0),
        payable_total       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_payable DEFAULT (0),
        paid_total          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_paid DEFAULT (0),
        remaining_total     DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_invoice_prev_remaining DEFAULT (0),
        preview_note        NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_invoice_prev_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_invoice_previews PRIMARY KEY CLUSTERED (invoice_preview_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   8. dbo.erp_financial_summary_snapshots
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_financial_summary_snapshots', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_financial_summary_snapshots
    (
        snapshot_id         BIGINT          NOT NULL IDENTITY(1, 1),
        snapshot_scope      NVARCHAR(80)    NOT NULL,
        operation_case_id   BIGINT          NULL,
        jobcard_id          BIGINT          NULL,
        customer_id         BIGINT          NULL,
        total_payable       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_fin_snap_payable DEFAULT (0),
        total_paid          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_fin_snap_paid DEFAULT (0),
        total_remaining     DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_fin_snap_remaining DEFAULT (0),
        unpaid_count        INT             NOT NULL
            CONSTRAINT DF_erp_fin_snap_unpaid DEFAULT (0),
        partial_paid_count  INT             NOT NULL
            CONSTRAINT DF_erp_fin_snap_partial DEFAULT (0),
        paid_count          INT             NOT NULL
            CONSTRAINT DF_erp_fin_snap_paid_cnt DEFAULT (0),
        snapshot_note       NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_fin_snap_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_financial_summary_snapshots PRIMARY KEY CLUSTERED (snapshot_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   9. dbo.erp_finance_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_finance_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_finance_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_finance_history_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_finance_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* Indexes */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_fin_svc_price_service_code' AND object_id = OBJECT_ID(N'dbo.erp_finance_service_price_list', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_fin_svc_price_service_code ON dbo.erp_finance_service_price_list (service_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_fin_svc_price_service_name' AND object_id = OBJECT_ID(N'dbo.erp_finance_service_price_list', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_fin_svc_price_service_name ON dbo.erp_finance_service_price_list (service_name); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_fin_labour_rate_code' AND object_id = OBJECT_ID(N'dbo.erp_finance_labour_rates', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_fin_labour_rate_code ON dbo.erp_finance_labour_rates (rate_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_fin_margin_rule_code' AND object_id = OBJECT_ID(N'dbo.erp_finance_part_margin_rules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_fin_margin_rule_code ON dbo.erp_finance_part_margin_rules (rule_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_headers_operation_case' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_headers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_headers_operation_case ON dbo.erp_jobcard_cost_headers (operation_case_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_headers_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_headers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_headers_jobcard ON dbo.erp_jobcard_cost_headers (jobcard_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_headers_customer' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_headers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_headers_customer ON dbo.erp_jobcard_cost_headers (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_headers_payment_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_headers', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_headers_payment_status ON dbo.erp_jobcard_cost_headers (payment_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_lines_header' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_lines', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_lines_header ON dbo.erp_jobcard_cost_lines (cost_header_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jc_cost_lines_type' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_cost_lines', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_jc_cost_lines_type ON dbo.erp_jobcard_cost_lines (line_type); END;
GO
IF OBJECT_ID(N'dbo.erp_payment_records', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_payment_records_cost_header' AND object_id = OBJECT_ID(N'dbo.erp_payment_records', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_payment_records_cost_header ON dbo.erp_payment_records (cost_header_id); END;
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_payment_records_operation_case' AND object_id = OBJECT_ID(N'dbo.erp_payment_records', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_payment_records_operation_case ON dbo.erp_payment_records (operation_case_id); END;
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_payment_records_customer' AND object_id = OBJECT_ID(N'dbo.erp_payment_records', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_payment_records_customer ON dbo.erp_payment_records (customer_id); END;
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_payment_records_status' AND object_id = OBJECT_ID(N'dbo.erp_payment_records', N'U'))
    BEGIN CREATE NONCLUSTERED INDEX IX_erp_payment_records_status ON dbo.erp_payment_records (payment_status); END;
END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_invoice_previews_cost_header' AND object_id = OBJECT_ID(N'dbo.erp_invoice_previews', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_invoice_previews_cost_header ON dbo.erp_invoice_previews (cost_header_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_fin_snap_scope' AND object_id = OBJECT_ID(N'dbo.erp_financial_summary_snapshots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_fin_snap_scope ON dbo.erp_financial_summary_snapshots (snapshot_scope); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_finance_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_finance_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_finance_history_entity ON dbo.erp_finance_history (entity_type, entity_id); END;
GO

/* Seeds */
IF OBJECT_ID(N'dbo.erp_finance_labour_rates', N'U') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM dbo.erp_finance_labour_rates WHERE rate_code = N'DEFAULT-LABOUR')
BEGIN
    INSERT INTO dbo.erp_finance_labour_rates (rate_code, rate_name, hourly_rate, created_by)
    VALUES (N'DEFAULT-LABOUR', N'نرخ پیش‌فرض اجرت', 0, N'SYSTEM_SEED');
END;
GO

IF OBJECT_ID(N'dbo.erp_finance_part_margin_rules', N'U') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM dbo.erp_finance_part_margin_rules WHERE rule_code = N'DEFAULT-PART-MARGIN')
BEGIN
    INSERT INTO dbo.erp_finance_part_margin_rules (rule_code, rule_name, margin_percent, created_by)
    VALUES (N'DEFAULT-PART-MARGIN', N'حاشیه سود پیش‌فرض قطعات', 20, N'SYSTEM_SEED');
END;
GO

IF OBJECT_ID(N'dbo.erp_finance_service_price_list', N'U') IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM dbo.erp_finance_service_price_list WHERE service_code = N'MANUAL-SERVICE')
BEGIN
    INSERT INTO dbo.erp_finance_service_price_list (service_code, service_name, base_price, created_by)
    VALUES (N'MANUAL-SERVICE', N'خدمت دستی', 0, N'SYSTEM_SEED');
END;
GO

PRINT N'Phase 5 Financial System SQL completed.';
GO
