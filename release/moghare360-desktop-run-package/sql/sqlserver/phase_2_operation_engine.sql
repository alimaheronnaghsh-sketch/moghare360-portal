/*
================================================================================
MOGHARE360 ERP — Phase 2 Operation Engine
Script: phase_2_operation_engine.sql
================================================================================

Phase 2 orchestration tables linking Customer Core → JobCard → Service → QC → Delivery.
Extends M17/M20/M30 foundations without duplicating them.

Idempotent. No DROP. No RENAME. No USE database statement.
Execute manually in SSMS against moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_operation_cases
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_operation_cases', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_operation_cases
    (
        operation_case_id       BIGINT          NOT NULL IDENTITY(1, 1),
        jobcard_id              BIGINT          NULL,
        intake_id               BIGINT          NULL,
        customer_id             BIGINT          NULL,
        vehicle_binding_id      BIGINT          NULL,
        contract_id             BIGINT          NULL,
        operation_code          NVARCHAR(60)    NOT NULL,
        current_stage           NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_operation_cases_current_stage DEFAULT (N'RECEPTION'),
        current_status          NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_operation_cases_current_status DEFAULT (N'OPEN'),
        priority_level          NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_operation_cases_priority_level DEFAULT (N'NORMAL'),
        reception_summary       NVARCHAR(1500)  NULL,
        internal_notes          NVARCHAR(2000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_operation_cases_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_operation_cases PRIMARY KEY CLUSTERED (operation_case_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_operation_service_steps
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_operation_service_steps', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_operation_service_steps
    (
        service_step_id             BIGINT          NOT NULL IDENTITY(1, 1),
        operation_case_id           BIGINT          NOT NULL,
        step_type                   NVARCHAR(50)    NOT NULL,
        step_title                  NVARCHAR(300)   NOT NULL,
        step_description            NVARCHAR(2000)  NULL,
        assigned_technician_text    NVARCHAR(200)   NULL,
        step_status                 NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_operation_service_steps_step_status DEFAULT (N'OPEN'),
        progress_percent            INT             NOT NULL
            CONSTRAINT DF_erp_operation_service_steps_progress_percent DEFAULT (0),
        started_at                  DATETIME2       NULL,
        completed_at                DATETIME2       NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_operation_service_steps_created_at DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        updated_at                  DATETIME2       NULL,
        updated_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_operation_service_steps PRIMARY KEY CLUSTERED (service_step_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_operation_qc_decisions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_operation_qc_decisions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_operation_qc_decisions
    (
        qc_decision_id      BIGINT          NOT NULL IDENTITY(1, 1),
        operation_case_id   BIGINT          NOT NULL,
        decision_status     NVARCHAR(50)    NOT NULL,
        decision_note       NVARCHAR(1500)  NULL,
        return_to_stage     NVARCHAR(50)    NULL,
        decided_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_operation_qc_decisions_decided_at DEFAULT (SYSUTCDATETIME()),
        decided_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_operation_qc_decisions PRIMARY KEY CLUSTERED (qc_decision_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_operation_delivery_checks
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_operation_delivery_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_operation_delivery_checks
    (
        delivery_check_id           BIGINT          NOT NULL IDENTITY(1, 1),
        operation_case_id           BIGINT          NOT NULL,
        is_ready_for_delivery       BIT             NOT NULL
            CONSTRAINT DF_erp_operation_delivery_checks_is_ready DEFAULT (0),
        customer_contact_required   BIT             NOT NULL
            CONSTRAINT DF_erp_operation_delivery_checks_contact DEFAULT (1),
        payment_preview_required    BIT             NOT NULL
            CONSTRAINT DF_erp_operation_delivery_checks_payment DEFAULT (1),
        qc_passed_required          BIT             NOT NULL
            CONSTRAINT DF_erp_operation_delivery_checks_qc DEFAULT (1),
        final_note                  NVARCHAR(1500)  NULL,
        checked_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_operation_delivery_checks_checked_at DEFAULT (SYSUTCDATETIME()),
        checked_by                  NVARCHAR(100)   NULL,
        source_ip                   NVARCHAR(100)   NULL,
        user_agent                  NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_operation_delivery_checks PRIMARY KEY CLUSTERED (delivery_check_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_operation_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_operation_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_operation_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_operation_history_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_operation_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_operation_code' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_operation_code ON dbo.erp_operation_cases (operation_code); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_jobcard_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_jobcard_id ON dbo.erp_operation_cases (jobcard_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_customer_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_customer_id ON dbo.erp_operation_cases (customer_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_vehicle_binding_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_vehicle_binding_id ON dbo.erp_operation_cases (vehicle_binding_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_current_stage' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_current_stage ON dbo.erp_operation_cases (current_stage); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_cases_current_status' AND object_id = OBJECT_ID(N'dbo.erp_operation_cases', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_cases_current_status ON dbo.erp_operation_cases (current_status); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_service_steps_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_service_steps', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_service_steps_operation_case_id ON dbo.erp_operation_service_steps (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_service_steps_step_status' AND object_id = OBJECT_ID(N'dbo.erp_operation_service_steps', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_service_steps_step_status ON dbo.erp_operation_service_steps (step_status); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_qc_decisions_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_qc_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_qc_decisions_operation_case_id ON dbo.erp_operation_qc_decisions (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_delivery_checks_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_operation_delivery_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_delivery_checks_operation_case_id ON dbo.erp_operation_delivery_checks (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_operation_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_operation_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_operation_history_entity ON dbo.erp_operation_history (entity_type, entity_id); END;
GO

PRINT N'Phase 2 Operation Engine SQL completed.';
GO
