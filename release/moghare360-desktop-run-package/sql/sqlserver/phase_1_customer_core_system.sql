/*
================================================================================
MOGHARE360 ERP — Phase 1 Customer Core System
Script: phase_1_customer_core_system.sql
================================================================================

Phase 1 Customer Core foundation tables.
Intake, contract, vehicle binding, photo metadata, and core history.

Idempotent: skips CREATE TABLE when table already exists.
No DROP. No RENAME. No destructive migration. No legacy table modification.
Execute manually in SSMS against the current MOGHARE360 ERP database.
Do not auto-run from PHP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_customer_intakes
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_intakes', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_intakes
    (
        intake_id           BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id         BIGINT          NULL,
        full_name           NVARCHAR(200)   NOT NULL,
        mobile              NVARCHAR(30)    NOT NULL,
        national_code       NVARCHAR(30)    NULL,
        license_plate       NVARCHAR(50)    NULL,
        intake_channel      NVARCHAR(50)    NOT NULL,
        intake_type         NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_intakes_intake_type DEFAULT (N'CUSTOMER'),
        source_description  NVARCHAR(300)   NULL,
        notes               NVARCHAR(1000)  NULL,
        duplicate_status    NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_intakes_duplicate_status DEFAULT (N'NEW'),
        duplicate_reason    NVARCHAR(500)   NULL,
        status              NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_intakes_status DEFAULT (N'OPEN'),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_customer_intakes_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_customer_intakes PRIMARY KEY CLUSTERED (intake_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_customer_contracts
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_contracts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_contracts
    (
        contract_id                 BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id                 BIGINT          NULL,
        intake_id                   BIGINT          NULL,
        contract_code               NVARCHAR(50)    NOT NULL,
        contract_type               NVARCHAR(50)    NOT NULL,
        authorization_mode          NVARCHAR(50)    NOT NULL,
        approval_threshold_amount   DECIMAL(18, 2)  NULL,
        requires_operation_approval BIT             NOT NULL
            CONSTRAINT DF_erp_customer_contracts_requires_operation_approval DEFAULT (1),
        requires_parts_approval     BIT             NOT NULL
            CONSTRAINT DF_erp_customer_contracts_requires_parts_approval DEFAULT (1),
        terms_summary               NVARCHAR(2000)  NULL,
        status                      NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_contracts_status DEFAULT (N'DRAFT'),
        accepted_at                 DATETIME2       NULL,
        accepted_by                 NVARCHAR(100)   NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_customer_contracts_created_at DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        updated_at                  DATETIME2       NULL,
        updated_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_customer_contracts PRIMARY KEY CLUSTERED (contract_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_customer_contract_acceptances
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_contract_acceptances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_contract_acceptances
    (
        acceptance_id       BIGINT          NOT NULL IDENTITY(1, 1),
        contract_id         BIGINT          NOT NULL,
        acceptance_type     NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_customer_contract_acceptances_acceptance_type DEFAULT (N'INTERNAL_CONTROLLED'),
        accepted_by         NVARCHAR(100)   NULL,
        acceptance_note     NVARCHAR(1000)  NULL,
        accepted_at         DATETIME2       NOT NULL
            CONSTRAINT DF_erp_customer_contract_acceptances_accepted_at DEFAULT (SYSUTCDATETIME()),
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_customer_contract_acceptances PRIMARY KEY CLUSTERED (acceptance_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_customer_vehicle_bindings
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_vehicle_bindings
    (
        binding_id          BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id         BIGINT          NULL,
        intake_id           BIGINT          NULL,
        vehicle_id          BIGINT          NULL,
        relationship_type   NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_bindings_relationship_type DEFAULT (N'OWNER'),
        license_plate       NVARCHAR(50)    NOT NULL,
        vin                 NVARCHAR(100)   NULL,
        brand               NVARCHAR(100)   NULL,
        model               NVARCHAR(100)   NULL,
        model_year          INT             NULL,
        color               NVARCHAR(100)   NULL,
        mileage_km          INT             NULL,
        binding_status      NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_bindings_binding_status DEFAULT (N'ACTIVE'),
        notes               NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_bindings_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_customer_vehicle_bindings PRIMARY KEY CLUSTERED (binding_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_vehicle_photo_records
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_vehicle_photo_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_vehicle_photo_records
    (
        photo_record_id     BIGINT          NOT NULL IDENTITY(1, 1),
        binding_id          BIGINT          NULL,
        vehicle_id          BIGINT          NULL,
        photo_type          NVARCHAR(50)    NOT NULL,
        placeholder_label   NVARCHAR(200)   NULL,
        file_path           NVARCHAR(500)   NULL,
        storage_status      NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_vehicle_photo_records_storage_status DEFAULT (N'PLACEHOLDER'),
        notes               NVARCHAR(1000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_vehicle_photo_records_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_vehicle_photo_records PRIMARY KEY CLUSTERED (photo_record_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6. dbo.erp_customer_core_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_core_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_core_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_customer_core_history_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_customer_core_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   Indexes — non-unique duplicate-check support
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_intakes_mobile'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_intakes', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_intakes_mobile
        ON dbo.erp_customer_intakes (mobile);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_intakes_national_code'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_intakes', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_intakes_national_code
        ON dbo.erp_customer_intakes (national_code);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_intakes_license_plate'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_intakes', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_intakes_license_plate
        ON dbo.erp_customer_intakes (license_plate);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_contracts_customer_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_contracts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_contracts_customer_id
        ON dbo.erp_customer_contracts (customer_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_contracts_intake_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_contracts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_contracts_intake_id
        ON dbo.erp_customer_contracts (intake_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_contracts_contract_code'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_contracts', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_contracts_contract_code
        ON dbo.erp_customer_contracts (contract_code);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_bindings_customer_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_bindings_customer_id
        ON dbo.erp_customer_vehicle_bindings (customer_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_bindings_vehicle_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_bindings_vehicle_id
        ON dbo.erp_customer_vehicle_bindings (vehicle_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_bindings_license_plate'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_bindings_license_plate
        ON dbo.erp_customer_vehicle_bindings (license_plate);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_bindings_vin'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_bindings_vin
        ON dbo.erp_customer_vehicle_bindings (vin);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_vehicle_photo_records_binding_id'
      AND object_id = OBJECT_ID(N'dbo.erp_vehicle_photo_records', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_vehicle_photo_records_binding_id
        ON dbo.erp_vehicle_photo_records (binding_id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = N'IX_erp_customer_core_history_entity'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_core_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_core_history_entity
        ON dbo.erp_customer_core_history (entity_type, entity_id);
END;
GO

PRINT N'Phase 1 Customer Core System SQL completed.';
GO
