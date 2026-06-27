/*
================================================================================
MOGHARE360 ERP — Phase 6 CRM System
Script: phase_6_crm_system.sql
================================================================================

Follow-up scheduler, satisfaction surveys, customer scoring, VIP detection,
upsell opportunities, and CRM history. No external messaging automation.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_crm_followup_schedules
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_crm_followup_schedules
    (
        followup_schedule_id  BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id         BIGINT          NULL,
        intake_id           BIGINT          NULL,
        vehicle_binding_id  BIGINT          NULL,
        operation_case_id   BIGINT          NULL,
        jobcard_id          BIGINT          NULL,
        cost_header_id      BIGINT          NULL,
        followup_code       NVARCHAR(100)   NOT NULL,
        followup_reason     NVARCHAR(100)   NOT NULL
            CONSTRAINT DF_erp_crm_followup_reason DEFAULT (N'POST_DELIVERY'),
        scheduled_at        DATETIME2       NOT NULL,
        followup_status     NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_crm_followup_status DEFAULT (N'PENDING'),
        priority_level      NVARCHAR(50)    NOT NULL
            CONSTRAINT DF_erp_crm_followup_priority DEFAULT (N'NORMAL'),
        assigned_to_text    NVARCHAR(200)   NULL,
        source_note         NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_crm_followup_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_crm_followup_schedules PRIMARY KEY CLUSTERED (followup_schedule_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_crm_followup_records
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_crm_followup_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_crm_followup_records
    (
        followup_record_id      BIGINT          NOT NULL IDENTITY(1, 1),
        followup_schedule_id    BIGINT          NULL,
        customer_id             BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        contact_channel         NVARCHAR(80)    NOT NULL,
        contact_result          NVARCHAR(80)    NOT NULL,
        customer_sentiment      NVARCHAR(80)    NULL,
        followup_note           NVARCHAR(2000)  NULL,
        next_followup_at        DATETIME2       NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_crm_followup_rec_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        source_ip               NVARCHAR(100)   NULL,
        user_agent              NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_crm_followup_records PRIMARY KEY CLUSTERED (followup_record_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_customer_satisfaction_surveys
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_satisfaction_surveys', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_satisfaction_surveys
    (
        satisfaction_id                 BIGINT          NOT NULL IDENTITY(1, 1),
        followup_schedule_id            BIGINT          NULL,
        customer_id                     BIGINT          NULL,
        operation_case_id               BIGINT          NULL,
        overall_score                   INT             NOT NULL,
        service_quality_score           INT             NULL,
        delivery_score                  INT             NULL,
        price_score                     INT             NULL,
        staff_behavior_score            INT             NULL,
        comeback_probability_score      INT             NULL,
        complaint_text                  NVARCHAR(2000)  NULL,
        positive_note                   NVARCHAR(2000)  NULL,
        survey_status                   NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_cust_sat_status DEFAULT (N'RECORDED'),
        created_at                      DATETIME2       NOT NULL
            CONSTRAINT DF_erp_cust_sat_created DEFAULT (SYSUTCDATETIME()),
        created_by                      NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_customer_satisfaction_surveys PRIMARY KEY CLUSTERED (satisfaction_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_customer_score_cards
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_customer_score_cards', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_score_cards
    (
        customer_score_id       BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id             BIGINT          NULL,
        intake_id               BIGINT          NULL,
        mobile                  NVARCHAR(50)    NULL,
        score_code              NVARCHAR(100)   NOT NULL,
        total_score             DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_cust_score_total DEFAULT (0),
        satisfaction_score      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_cust_score_sat DEFAULT (0),
        revenue_score           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_cust_score_rev DEFAULT (0),
        loyalty_score           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_cust_score_loy DEFAULT (0),
        complaint_penalty       DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_cust_score_pen DEFAULT (0),
        vip_status              NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_cust_score_vip DEFAULT (N'STANDARD'),
        score_note              NVARCHAR(1500)  NULL,
        calculated_at           DATETIME2       NOT NULL
            CONSTRAINT DF_erp_cust_score_calc_at DEFAULT (SYSUTCDATETIME()),
        calculated_by           NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_customer_score_cards PRIMARY KEY CLUSTERED (customer_score_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_upsell_opportunities
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_upsell_opportunities', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_upsell_opportunities
    (
        upsell_id                   BIGINT          NOT NULL IDENTITY(1, 1),
        customer_id                 BIGINT          NULL,
        intake_id                   BIGINT          NULL,
        vehicle_binding_id          BIGINT          NULL,
        operation_case_id           BIGINT          NULL,
        opportunity_code            NVARCHAR(100)   NOT NULL,
        opportunity_type            NVARCHAR(100)   NOT NULL,
        opportunity_title           NVARCHAR(300)   NOT NULL,
        opportunity_description     NVARCHAR(2000)  NULL,
        estimated_value             DECIMAL(18, 2)  NULL,
        opportunity_status          NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_upsell_status DEFAULT (N'OPEN'),
        next_action                 NVARCHAR(200)   NULL,
        due_at                      DATETIME2       NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_upsell_created DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        updated_at                  DATETIME2       NULL,
        updated_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_upsell_opportunities PRIMARY KEY CLUSTERED (upsell_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   6. dbo.erp_crm_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_crm_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_crm_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_crm_history_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_crm_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* Indexes */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_code' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_code ON dbo.erp_crm_followup_schedules (followup_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_customer' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_customer ON dbo.erp_crm_followup_schedules (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_operation_case' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_operation_case ON dbo.erp_crm_followup_schedules (operation_case_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_status' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_status ON dbo.erp_crm_followup_schedules (followup_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_scheduled_at' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_schedules', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_scheduled_at ON dbo.erp_crm_followup_schedules (scheduled_at); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_rec_schedule' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_rec_schedule ON dbo.erp_crm_followup_records (followup_schedule_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_followup_rec_customer' AND object_id = OBJECT_ID(N'dbo.erp_crm_followup_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_followup_rec_customer ON dbo.erp_crm_followup_records (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_cust_sat_customer' AND object_id = OBJECT_ID(N'dbo.erp_customer_satisfaction_surveys', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_cust_sat_customer ON dbo.erp_customer_satisfaction_surveys (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_cust_sat_operation_case' AND object_id = OBJECT_ID(N'dbo.erp_customer_satisfaction_surveys', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_cust_sat_operation_case ON dbo.erp_customer_satisfaction_surveys (operation_case_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_cust_score_customer' AND object_id = OBJECT_ID(N'dbo.erp_customer_score_cards', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_cust_score_customer ON dbo.erp_customer_score_cards (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_cust_score_vip' AND object_id = OBJECT_ID(N'dbo.erp_customer_score_cards', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_cust_score_vip ON dbo.erp_customer_score_cards (vip_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_upsell_customer' AND object_id = OBJECT_ID(N'dbo.erp_upsell_opportunities', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_upsell_customer ON dbo.erp_upsell_opportunities (customer_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_upsell_status' AND object_id = OBJECT_ID(N'dbo.erp_upsell_opportunities', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_upsell_status ON dbo.erp_upsell_opportunities (opportunity_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_crm_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_crm_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_crm_history_entity ON dbo.erp_crm_history (entity_type, entity_id); END;
GO

PRINT N'Phase 6 CRM System SQL completed.';
GO
