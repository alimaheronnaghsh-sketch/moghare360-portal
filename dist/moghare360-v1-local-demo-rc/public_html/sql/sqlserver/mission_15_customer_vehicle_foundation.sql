/*
================================================================================
MOGHARE360 ERP — Mission 15
Script: mission_15_customer_vehicle_foundation.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Mission 15 Customer / Vehicle foundation tables.
Design reference: docs/missions/mission_14_customer_vehicle_foundation_design/

Creates if missing:
  1. dbo.erp_customers
  2. dbo.erp_customer_phones
  3. dbo.erp_vehicles
  4. dbo.erp_customer_vehicle_relations
  5. dbo.erp_customer_vehicle_change_history

Idempotent: skips CREATE TABLE when table already exists.
No DROP. No TRUNCATE. No destructive migration. No legacy table modification.

Execute manually in SSMS only. Do not auto-run from PHP.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_customers
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customers', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customers
    (
        customer_id             INT             NOT NULL IDENTITY(1, 1),
        customer_code           NVARCHAR(50)    NOT NULL,
        customer_type           NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_customers_customer_type DEFAULT (N'PERSON'),
        full_name               NVARCHAR(200)   NOT NULL,
        national_id             NVARCHAR(20)    NULL,
        primary_mobile          NVARCHAR(30)    NOT NULL,
        secondary_mobile        NVARCHAR(30)    NULL,
        email                   NVARCHAR(150)   NULL,
        address                 NVARCHAR(500)   NULL,
        city                    NVARCHAR(100)   NULL,
        notes                   NVARCHAR(1000)  NULL,
        lifecycle_state         NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_customers_lifecycle_state DEFAULT (N'ACTIVE'),
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_customers_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        created_by_user_id      INT             NOT NULL,
        updated_by_user_id      INT             NULL,
        CONSTRAINT PK_erp_customers PRIMARY KEY CLUSTERED (customer_id),
        CONSTRAINT UQ_erp_customers_customer_code UNIQUE (customer_code)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_customer_phones
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_phones', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_phones
    (
        phone_id                INT             NOT NULL IDENTITY(1, 1),
        customer_id             INT             NOT NULL,
        phone_type              NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_customer_phones_phone_type DEFAULT (N'MOBILE'),
        phone_number            NVARCHAR(30)    NOT NULL,
        is_primary              BIT             NOT NULL
            CONSTRAINT DF_erp_customer_phones_is_primary DEFAULT (0),
        is_verified             BIT             NOT NULL
            CONSTRAINT DF_erp_customer_phones_is_verified DEFAULT (0),
        do_not_contact          BIT             NOT NULL
            CONSTRAINT DF_erp_customer_phones_do_not_contact DEFAULT (0),
        lifecycle_state         NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_customer_phones_lifecycle_state DEFAULT (N'ACTIVE'),
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_customer_phones_created_at DEFAULT (SYSUTCDATETIME()),
        created_by_user_id      INT             NOT NULL,
        CONSTRAINT PK_erp_customer_phones PRIMARY KEY CLUSTERED (phone_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_customer_phones_customer', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_customer_phones
        ADD CONSTRAINT FK_erp_customer_phones_customer
            FOREIGN KEY (customer_id) REFERENCES dbo.erp_customers (customer_id);
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_vehicles
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_vehicles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_vehicles
    (
        vehicle_id              INT             NOT NULL IDENTITY(1, 1),
        vehicle_code            NVARCHAR(50)    NOT NULL,
        plate_number            NVARCHAR(50)    NULL,
        vin                     NVARCHAR(50)    NULL,
        chassis_number          NVARCHAR(80)    NULL,
        engine_number           NVARCHAR(80)    NULL,
        brand                   NVARCHAR(100)   NOT NULL,
        model                   NVARCHAR(100)   NOT NULL,
        production_year         INT             NULL,
        color                   NVARCHAR(80)    NULL,
        mileage                 INT             NULL,
        fuel_type               NVARCHAR(50)    NULL,
        transmission_type       NVARCHAR(50)    NULL,
        lifecycle_state         NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_vehicles_lifecycle_state DEFAULT (N'ACTIVE'),
        notes                   NVARCHAR(1000)  NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_vehicles_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        created_by_user_id      INT             NOT NULL,
        updated_by_user_id      INT             NULL,
        CONSTRAINT PK_erp_vehicles PRIMARY KEY CLUSTERED (vehicle_id),
        CONSTRAINT UQ_erp_vehicles_vehicle_code UNIQUE (vehicle_code)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_customer_vehicle_relations
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_vehicle_relations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_vehicle_relations
    (
        relation_id             INT             NOT NULL IDENTITY(1, 1),
        customer_id             INT             NOT NULL,
        vehicle_id              INT             NOT NULL,
        relation_type           NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_relations_relation_type DEFAULT (N'OWNER'),
        is_primary_owner        BIT             NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_relations_is_primary_owner DEFAULT (1),
        valid_from              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_relations_valid_from DEFAULT (SYSUTCDATETIME()),
        valid_to                DATETIME2(3)    NULL,
        lifecycle_state         NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_relations_lifecycle_state DEFAULT (N'ACTIVE'),
        notes                   NVARCHAR(1000)  NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_relations_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        created_by_user_id      INT             NOT NULL,
        updated_by_user_id      INT             NULL,
        CONSTRAINT PK_erp_customer_vehicle_relations PRIMARY KEY CLUSTERED (relation_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_customer_vehicle_relations_customer', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_customer_vehicle_relations
        ADD CONSTRAINT FK_erp_customer_vehicle_relations_customer
            FOREIGN KEY (customer_id) REFERENCES dbo.erp_customers (customer_id);
END;
GO

IF OBJECT_ID(N'dbo.FK_erp_customer_vehicle_relations_vehicle', N'F') IS NULL
BEGIN
    ALTER TABLE dbo.erp_customer_vehicle_relations
        ADD CONSTRAINT FK_erp_customer_vehicle_relations_vehicle
            FOREIGN KEY (vehicle_id) REFERENCES dbo.erp_vehicles (vehicle_id);
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_customer_vehicle_change_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_vehicle_change_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_vehicle_change_history
    (
        history_id              INT             NOT NULL IDENTITY(1, 1),
        entity_type             NVARCHAR(80)    NOT NULL,
        entity_id               INT             NOT NULL,
        change_type             NVARCHAR(100)   NOT NULL,
        change_summary          NVARCHAR(1000)  NULL,
        changed_by_user_id      INT             NOT NULL,
        changed_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_erp_customer_vehicle_change_history_changed_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_customer_vehicle_change_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_customers_primary_mobile'
      AND object_id = OBJECT_ID(N'dbo.erp_customers', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customers_primary_mobile
        ON dbo.erp_customers (primary_mobile);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_customers_full_name'
      AND object_id = OBJECT_ID(N'dbo.erp_customers', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customers_full_name
        ON dbo.erp_customers (full_name);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_vehicles_plate_number'
      AND object_id = OBJECT_ID(N'dbo.erp_vehicles', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_vehicles_plate_number
        ON dbo.erp_vehicles (plate_number);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_vehicles_vin'
      AND object_id = OBJECT_ID(N'dbo.erp_vehicles', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_vehicles_vin
        ON dbo.erp_vehicles (vin);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_relations_customer_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_relations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_relations_customer_id
        ON dbo.erp_customer_vehicle_relations (customer_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_relations_vehicle_id'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_relations', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_relations_vehicle_id
        ON dbo.erp_customer_vehicle_relations (vehicle_id);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_customer_vehicle_change_history_entity'
      AND object_id = OBJECT_ID(N'dbo.erp_customer_vehicle_change_history', N'U')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_erp_customer_vehicle_change_history_entity
        ON dbo.erp_customer_vehicle_change_history (entity_type, entity_id);
END;
GO

SELECT N'Mission 15 Customer Vehicle SQL foundation script completed.' AS mission_15_status;
GO
