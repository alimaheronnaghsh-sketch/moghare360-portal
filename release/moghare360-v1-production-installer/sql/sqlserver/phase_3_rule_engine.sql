/*
================================================================================
MOGHARE360 ERP — Phase 3 Rule Engine
Script: phase_3_rule_engine.sql
================================================================================

Decision brain for contract authorization, service approval, inventory routing.
Extends Phase 1 (contracts) and Phase 2 (operations) without duplicating them.

Idempotent. No DROP. No RENAME. No USE database statement.
Execute manually in SSMS against moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. dbo.erp_rule_definitions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_rule_definitions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_rule_definitions
    (
        rule_id             BIGINT          NOT NULL IDENTITY(1, 1),
        rule_code           NVARCHAR(80)    NOT NULL,
        rule_name           NVARCHAR(200)   NOT NULL,
        rule_domain         NVARCHAR(80)    NOT NULL,
        rule_type           NVARCHAR(80)    NOT NULL,
        rule_description    NVARCHAR(1500)  NULL,
        is_active           BIT             NOT NULL
            CONSTRAINT DF_erp_rule_definitions_is_active DEFAULT (1),
        priority_order      INT             NOT NULL
            CONSTRAINT DF_erp_rule_definitions_priority_order DEFAULT (100),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_rule_definitions_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        updated_at          DATETIME2       NULL,
        updated_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_rule_definitions PRIMARY KEY CLUSTERED (rule_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   2. dbo.erp_rule_decisions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_rule_decisions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_rule_decisions
    (
        decision_id         BIGINT          NOT NULL IDENTITY(1, 1),
        rule_id             BIGINT          NULL,
        operation_case_id   BIGINT          NULL,
        service_step_id     BIGINT          NULL,
        contract_id         BIGINT          NULL,
        customer_id         BIGINT          NULL,
        vehicle_binding_id  BIGINT          NULL,
        part_id             BIGINT          NULL,
        decision_code       NVARCHAR(80)    NOT NULL,
        decision_status     NVARCHAR(80)    NOT NULL,
        decision_reason     NVARCHAR(1500)  NULL,
        requested_amount    DECIMAL(18, 2)  NULL,
        threshold_amount    DECIMAL(18, 2)  NULL,
        inventory_status    NVARCHAR(80)    NULL,
        next_action         NVARCHAR(80)    NOT NULL,
        is_blocking         BIT             NOT NULL
            CONSTRAINT DF_erp_rule_decisions_is_blocking DEFAULT (0),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_rule_decisions_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_rule_decisions PRIMARY KEY CLUSTERED (decision_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   3. dbo.erp_service_approval_requests
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_service_approval_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_service_approval_requests
    (
        approval_request_id     BIGINT          NOT NULL IDENTITY(1, 1),
        decision_id             BIGINT          NULL,
        operation_case_id       BIGINT          NULL,
        service_step_id         BIGINT          NULL,
        contract_id             BIGINT          NULL,
        customer_id             BIGINT          NULL,
        approval_type           NVARCHAR(80)    NOT NULL,
        approval_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_service_approval_requests_approval_status DEFAULT (N'PENDING'),
        requested_amount        DECIMAL(18, 2)  NULL,
        approval_reason         NVARCHAR(1500)  NULL,
        internal_note           NVARCHAR(1500)  NULL,
        controlled_decision_by    NVARCHAR(100)   NULL,
        controlled_decision_at  DATETIME2       NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_service_approval_requests_created_at DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        source_ip               NVARCHAR(100)   NULL,
        user_agent              NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_service_approval_requests PRIMARY KEY CLUSTERED (approval_request_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   4. dbo.erp_inventory_rule_requests
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_inventory_rule_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_inventory_rule_requests
    (
        inventory_rule_request_id   BIGINT          NOT NULL IDENTITY(1, 1),
        decision_id                 BIGINT          NULL,
        operation_case_id           BIGINT          NULL,
        service_step_id             BIGINT          NULL,
        part_id                     BIGINT          NULL,
        part_code                   NVARCHAR(100)   NULL,
        part_name                   NVARCHAR(300)   NULL,
        requested_qty               DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_inventory_rule_requests_requested_qty DEFAULT (1),
        available_qty               DECIMAL(18, 2)  NULL,
        inventory_decision          NVARCHAR(80)    NOT NULL,
        next_action                 NVARCHAR(80)    NOT NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_inventory_rule_requests_created_at DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_inventory_rule_requests PRIMARY KEY CLUSTERED (inventory_rule_request_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   5. dbo.erp_rule_audit_history
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.erp_rule_audit_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_rule_audit_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_rule_audit_history_created_at DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_rule_audit_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

/* ----------------------------------------------------------------------------
   Indexes
---------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_definitions_rule_code' AND object_id = OBJECT_ID(N'dbo.erp_rule_definitions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_definitions_rule_code ON dbo.erp_rule_definitions (rule_code); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_definitions_rule_domain' AND object_id = OBJECT_ID(N'dbo.erp_rule_definitions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_definitions_rule_domain ON dbo.erp_rule_definitions (rule_domain); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_definitions_is_active' AND object_id = OBJECT_ID(N'dbo.erp_rule_definitions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_definitions_is_active ON dbo.erp_rule_definitions (is_active); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_decisions_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_rule_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_decisions_operation_case_id ON dbo.erp_rule_decisions (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_decisions_service_step_id' AND object_id = OBJECT_ID(N'dbo.erp_rule_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_decisions_service_step_id ON dbo.erp_rule_decisions (service_step_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_decisions_contract_id' AND object_id = OBJECT_ID(N'dbo.erp_rule_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_decisions_contract_id ON dbo.erp_rule_decisions (contract_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_decisions_decision_status' AND object_id = OBJECT_ID(N'dbo.erp_rule_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_decisions_decision_status ON dbo.erp_rule_decisions (decision_status); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_decisions_next_action' AND object_id = OBJECT_ID(N'dbo.erp_rule_decisions', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_decisions_next_action ON dbo.erp_rule_decisions (next_action); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_service_approval_requests_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_service_approval_requests', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_service_approval_requests_operation_case_id ON dbo.erp_service_approval_requests (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_service_approval_requests_approval_status' AND object_id = OBJECT_ID(N'dbo.erp_service_approval_requests', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_service_approval_requests_approval_status ON dbo.erp_service_approval_requests (approval_status); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_rule_requests_operation_case_id' AND object_id = OBJECT_ID(N'dbo.erp_inventory_rule_requests', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_rule_requests_operation_case_id ON dbo.erp_inventory_rule_requests (operation_case_id); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_inventory_rule_requests_inventory_decision' AND object_id = OBJECT_ID(N'dbo.erp_inventory_rule_requests', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_inventory_rule_requests_inventory_decision ON dbo.erp_inventory_rule_requests (inventory_decision); END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_rule_audit_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_rule_audit_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_rule_audit_history_entity ON dbo.erp_rule_audit_history (entity_type, entity_id); END;
GO

/* ----------------------------------------------------------------------------
   Seed rule definitions (idempotent)
---------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'CONTRACT_OPEN_AUTHORIZATION_LIMIT')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'CONTRACT_OPEN_AUTHORIZATION_LIMIT', N'Open Authorization Limit', N'CONTRACT', N'AUTHORIZATION',
            N'Allow operations within open authorization threshold.', 10, N'SYSTEM_SEED');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD', N'Limited Authorization Threshold', N'CONTRACT', N'THRESHOLD',
            N'Block or require approval when amount exceeds limited authorization.', 20, N'SYSTEM_SEED');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'SERVICE_OUT_OF_CONTRACT_APPROVAL')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'SERVICE_OUT_OF_CONTRACT_APPROVAL', N'Out of Contract Service Approval', N'SERVICE', N'APPROVAL_REQUIRED',
            N'Extra services outside contract require approval.', 30, N'SYSTEM_SEED');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'INVENTORY_PART_AVAILABLE_USE_STOCK')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'INVENTORY_PART_AVAILABLE_USE_STOCK', N'Part Available Use Stock', N'INVENTORY', N'PART_AVAILABILITY',
            N'Route to warehouse reserve when part is available.', 40, N'SYSTEM_SEED');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'INVENTORY_PART_NOT_AVAILABLE_PURCHASE')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'INVENTORY_PART_NOT_AVAILABLE_PURCHASE', N'Part Not Available Purchase', N'INVENTORY', N'PART_AVAILABILITY',
            N'Route to purchase request when part is not available.', 50, N'SYSTEM_SEED');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_rule_definitions WHERE rule_code = N'OPERATION_BLOCK_WITHOUT_RULE_CHECK')
BEGIN
    INSERT INTO dbo.erp_rule_definitions (rule_code, rule_name, rule_domain, rule_type, rule_description, priority_order, created_by)
    VALUES (N'OPERATION_BLOCK_WITHOUT_RULE_CHECK', N'Block Without Rule Check', N'OPERATION', N'WORKFLOW_BLOCK',
            N'No operation may proceed without rule evaluation.', 5, N'SYSTEM_SEED');
END;
GO

PRINT N'Phase 3 Rule Engine SQL completed.';
GO
