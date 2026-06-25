/*
================================================================================
MOGHARE360 ERP — Phase 12 Soft Run Pilot
Script: phase_12_soft_run_pilot.sql
================================================================================

Controlled internal pilot workspace. Separate pilot tables only.
No production data modification. No SaaS/portal/accounting activation.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_soft_run_pilots', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilots
    (
        pilot_id        BIGINT          NOT NULL IDENTITY(1, 1),
        pilot_code      NVARCHAR(100)   NOT NULL,
        pilot_title     NVARCHAR(300)   NOT NULL,
        pilot_status    NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_status DEFAULT (N'DRAFT'),
        pilot_scope     NVARCHAR(300)   NULL,
        pilot_note      NVARCHAR(1500)  NULL,
        started_at      DATETIME2       NULL,
        completed_at    DATETIME2       NULL,
        created_at      DATETIME2       NOT NULL
            CONSTRAINT DF_erp_pilot_created DEFAULT (SYSUTCDATETIME()),
        created_by      NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_soft_run_pilots PRIMARY KEY CLUSTERED (pilot_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_scenarios', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_scenarios
    (
        scenario_id                 BIGINT          NOT NULL IDENTITY(1, 1),
        pilot_id                    BIGINT          NULL,
        scenario_code               NVARCHAR(100)   NOT NULL,
        customer_name               NVARCHAR(250)   NOT NULL,
        mobile                      NVARCHAR(50)    NULL,
        vehicle_plate               NVARCHAR(100)   NULL,
        vehicle_brand_model         NVARCHAR(250)   NULL,
        contract_type               NVARCHAR(100)   NULL,
        authorization_mode          NVARCHAR(100)   NULL,
        jobcard_service_description NVARCHAR(1500)  NULL,
        part_required               BIT             NOT NULL
            CONSTRAINT DF_erp_pilot_scn_part DEFAULT (0),
        payment_preview_amount      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_pilot_scn_pay DEFAULT (0),
        crm_followup_expected       BIT             NOT NULL
            CONSTRAINT DF_erp_pilot_scn_crm DEFAULT (0),
        hr_attendance_sample        BIT             NOT NULL
            CONSTRAINT DF_erp_pilot_scn_hr DEFAULT (0),
        scenario_status             NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_scn_status DEFAULT (N'DRAFT'),
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_pilot_scn_created DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        updated_at                  DATETIME2       NULL,
        updated_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_soft_run_pilot_scenarios PRIMARY KEY CLUSTERED (scenario_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_flow_snapshots', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_flow_snapshots
    (
        flow_snapshot_id        BIGINT          NOT NULL IDENTITY(1, 1),
        scenario_id             BIGINT          NOT NULL,
        customer_step_status    NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_cust DEFAULT (N'PENDING'),
        vehicle_step_status     NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_veh DEFAULT (N'PENDING'),
        contract_step_status    NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_con DEFAULT (N'PENDING'),
        operation_step_status   NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_op DEFAULT (N'PENDING'),
        inventory_step_status   NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_inv DEFAULT (N'PENDING'),
        finance_step_status     NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_fin DEFAULT (N'PENDING'),
        crm_step_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_crm DEFAULT (N'PENDING'),
        hr_step_status          NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_hr DEFAULT (N'PENDING'),
        flow_decision           NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_flow_dec DEFAULT (N'PENDING'),
        flow_note               NVARCHAR(2000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_pilot_flow_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_soft_run_pilot_flow_snapshots PRIMARY KEY CLUSTERED (flow_snapshot_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_feedback', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_feedback
    (
        feedback_id         BIGINT          NOT NULL IDENTITY(1, 1),
        scenario_id         BIGINT          NULL,
        feedback_role       NVARCHAR(100)   NOT NULL,
        page_or_module      NVARCHAR(200)   NULL,
        issue_type          NVARCHAR(100)   NOT NULL,
        severity            NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_fb_sev DEFAULT (N'low'),
        feedback_note       NVARCHAR(2000)  NOT NULL,
        suggested_fix       NVARCHAR(2000)  NULL,
        feedback_status     NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_pilot_fb_status DEFAULT (N'OPEN'),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_pilot_fb_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_soft_run_pilot_feedback PRIMARY KEY CLUSTERED (feedback_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_pilot_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_pilot_history
    (
        pilot_history_id    BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(100)   NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(100)   NOT NULL,
        action_summary      NVARCHAR(1500)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_pilot_hist_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_soft_run_pilot_history PRIMARY KEY CLUSTERED (pilot_history_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_code' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_code ON dbo.erp_soft_run_pilots (pilot_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_status' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_status ON dbo.erp_soft_run_pilots (pilot_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_scn_code' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_scenarios', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_scn_code ON dbo.erp_soft_run_pilot_scenarios (scenario_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_scn_status' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_scenarios', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_scn_status ON dbo.erp_soft_run_pilot_scenarios (scenario_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_scn_mobile' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_scenarios', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_scn_mobile ON dbo.erp_soft_run_pilot_scenarios (mobile); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_flow_scn' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_flow_snapshots', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_flow_scn ON dbo.erp_soft_run_pilot_flow_snapshots (scenario_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_fb_scn' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_feedback', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_fb_scn ON dbo.erp_soft_run_pilot_feedback (scenario_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_fb_sev' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_feedback', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_fb_sev ON dbo.erp_soft_run_pilot_feedback (severity); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_fb_status' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_feedback', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_fb_status ON dbo.erp_soft_run_pilot_feedback (feedback_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_pilot_hist_entity' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_pilot_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_pilot_hist_entity ON dbo.erp_soft_run_pilot_history (entity_type, entity_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_soft_run_pilots)
INSERT INTO dbo.erp_soft_run_pilots (pilot_code, pilot_title, pilot_status, pilot_scope, pilot_note, created_by)
VALUES (
    N'PILOT-LOCAL-RC1',
    N'MOGHARE360 Controlled Soft Run Pilot',
    N'DRAFT',
    N'Internal workshop pilot only; not production; not SaaS; not public portal.',
    N'Seed pilot for Local Release Candidate 1 controlled execution.',
    N'SYSTEM'
);
GO
