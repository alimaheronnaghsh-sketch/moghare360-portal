/*
 * MOGHARE360 Full Job Lifecycle — Hall/Technician Technical Request Center
 * Additive, idempotent, non-destructive.
 */
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_jobcard_assignments', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_jobcard_assignments
        (
            assignment_id BIGINT IDENTITY(1,1) NOT NULL,
            jobcard_id INT NOT NULL,
            assignment_type NVARCHAR(40) NOT NULL,
            team_code NVARCHAR(60) NULL,
            assigned_to_user_id INT NULL,
            assistant_user_id INT NULL,
            assigned_by_user_id INT NOT NULL,
            priority NVARCHAR(30) NOT NULL CONSTRAINT DF_erp_jobcard_assignments_priority DEFAULT (N'NORMAL'),
            due_at DATETIME2 NULL,
            assignment_description NVARCHAR(1000) NULL,
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_jobcard_assignments_status DEFAULT (N'ACTIVE'),
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_jobcard_assignments_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            closed_at DATETIME2 NULL,
            CONSTRAINT PK_erp_jobcard_assignments PRIMARY KEY CLUSTERED (assignment_id),
            CONSTRAINT CK_erp_jobcard_assignments_type CHECK (assignment_type IN (N'HALL_INTAKE', N'TEAM_ASSIGNMENT', N'TECHNICIAN_ASSIGNMENT', N'ROUTE')),
            CONSTRAINT CK_erp_jobcard_assignments_priority CHECK (priority IN (N'LOW', N'NORMAL', N'HIGH', N'URGENT', N'SAFETY_CRITICAL')),
            CONSTRAINT CK_erp_jobcard_assignments_status CHECK (status IN (N'ACTIVE', N'CLOSED', N'CANCELLED'))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_jobcard_technical_requests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_jobcard_technical_requests
        (
            technical_request_id BIGINT IDENTITY(1,1) NOT NULL,
            request_uid NVARCHAR(120) NOT NULL,
            jobcard_id INT NOT NULL,
            request_type NVARCHAR(60) NOT NULL,
            requested_by_user_id INT NOT NULL,
            requested_by_role NVARCHAR(60) NOT NULL,
            requested_at DATETIME2 NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_requested DEFAULT (SYSUTCDATETIME()),
            assigned_operation_id INT NULL,
            title NVARCHAR(300) NOT NULL,
            description NVARCHAR(MAX) NOT NULL,
            priority NVARCHAR(30) NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_priority DEFAULT (N'NORMAL'),
            risk_level NVARCHAR(30) NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_risk DEFAULT (N'NO_RISK'),
            evidence_files NVARCHAR(MAX) NULL,
            estimated_cost_impact DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_cost DEFAULT (0),
            estimated_time_impact_minutes INT NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_time DEFAULT (0),
            requires_customer_approval BIT NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_customer DEFAULT (0),
            requires_part BIT NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_part DEFAULT (0),
            requires_external_service BIT NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_external DEFAULT (0),
            status NVARCHAR(60) NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_status DEFAULT (N'SUBMITTED_BY_TECHNICIAN'),
            reviewed_by_user_id INT NULL,
            review_decision NVARCHAR(60) NULL,
            review_note NVARCHAR(1000) NULL,
            reviewed_at DATETIME2 NULL,
            customer_task_id BIGINT NULL,
            estimate_version_id BIGINT NULL,
            closed_at DATETIME2 NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_jobcard_technical_requests_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            CONSTRAINT PK_erp_jobcard_technical_requests PRIMARY KEY CLUSTERED (technical_request_id),
            CONSTRAINT UQ_erp_jobcard_technical_requests_uid UNIQUE (request_uid),
            CONSTRAINT CK_erp_jobcard_technical_requests_type CHECK (request_type IN (
                N'TECHNICAL_ADDITIONAL_WORK',
                N'PARTS_MATERIALS_REQUISITION',
                N'EXTERNAL_SERVICE_REQUEST',
                N'CUSTOMER_CLARIFICATION_REQUEST',
                N'WORK_HOLD_SAFETY_STOP'
            )),
            CONSTRAINT CK_erp_jobcard_technical_requests_status CHECK (status IN (
                N'DRAFT',
                N'SUBMITTED_BY_TECHNICIAN',
                N'UNDER_HALL_REVIEW',
                N'NEEDS_MORE_EVIDENCE',
                N'REJECTED_BY_HALL',
                N'ROUTED_TO_OTHER_TEAM',
                N'APPROVED_FOR_ESTIMATE_REVISION',
                N'SENT_TO_INVENTORY',
                N'SENT_TO_PURCHASE',
                N'SENT_TO_CRM',
                N'SENT_TO_CUSTOMER',
                N'CUSTOMER_APPROVED',
                N'CUSTOMER_REJECTED',
                N'EXECUTION_ALLOWED',
                N'EXECUTION_BLOCKED',
                N'CLOSED'
            )),
            CONSTRAINT CK_erp_jobcard_technical_requests_priority CHECK (priority IN (N'LOW', N'NORMAL', N'HIGH', N'URGENT', N'SAFETY_CRITICAL')),
            CONSTRAINT CK_erp_jobcard_technical_requests_risk CHECK (risk_level IN (N'NO_RISK', N'QUALITY_RISK', N'TIME_RISK', N'COST_RISK', N'SAFETY_RISK', N'LEGAL_RISK'))
        );
    END;

    IF OBJECT_ID(N'dbo.erp_jobcard_technical_request_events', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_jobcard_technical_request_events
        (
            event_id BIGINT IDENTITY(1,1) NOT NULL,
            technical_request_id BIGINT NOT NULL,
            jobcard_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            old_status NVARCHAR(60) NULL,
            new_status NVARCHAR(60) NULL,
            event_note NVARCHAR(1000) NULL,
            actor_user_id INT NULL,
            actor_role NVARCHAR(60) NULL,
            event_metadata_json NVARCHAR(MAX) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_jobcard_technical_request_events_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_jobcard_technical_request_events PRIMARY KEY CLUSTERED (event_id)
        );
    END;

    IF OBJECT_ID(N'dbo.erp_external_service_requests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_external_service_requests
        (
            external_service_request_id BIGINT IDENTITY(1,1) NOT NULL,
            technical_request_id BIGINT NULL,
            jobcard_id INT NOT NULL,
            vendor_name NVARCHAR(200) NOT NULL,
            service_title NVARCHAR(300) NOT NULL,
            vehicle_component NVARCHAR(200) NULL,
            send_out_at DATETIME2 NULL,
            responsible_user_id INT NULL,
            expected_return_at DATETIME2 NULL,
            returned_at DATETIME2 NULL,
            estimated_cost DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_external_service_requests_cost DEFAULT (0),
            requires_customer_approval BIT NOT NULL CONSTRAINT DF_erp_external_service_requests_customer DEFAULT (0),
            chain_of_custody_json NVARCHAR(MAX) NULL,
            status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_external_service_requests_status DEFAULT (N'PENDING_HALL_APPROVAL'),
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_external_service_requests_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            created_by_user_id INT NULL,
            CONSTRAINT PK_erp_external_service_requests PRIMARY KEY CLUSTERED (external_service_request_id),
            CONSTRAINT CK_erp_external_service_requests_status CHECK (status IN (
                N'PENDING_HALL_APPROVAL',
                N'APPROVED',
                N'REJECTED',
                N'SENT_OUT',
                N'RETURNED',
                N'CANCELLED'
            ))
        );
    END;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_assignments_jobcard', N'F') IS NULL
       AND OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_jobcard_assignments
            ADD CONSTRAINT FK_erp_jobcard_assignments_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards(jobcard_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_technical_requests_jobcard', N'F') IS NULL
       AND OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_jobcard_technical_requests
            ADD CONSTRAINT FK_erp_jobcard_technical_requests_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards(jobcard_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_technical_request_events_request', N'F') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_jobcard_technical_request_events
            ADD CONSTRAINT FK_erp_jobcard_technical_request_events_request
            FOREIGN KEY (technical_request_id) REFERENCES dbo.erp_jobcard_technical_requests(technical_request_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_external_service_requests_request', N'F') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_external_service_requests
            ADD CONSTRAINT FK_erp_external_service_requests_request
            FOREIGN KEY (technical_request_id) REFERENCES dbo.erp_jobcard_technical_requests(technical_request_id);
    END;

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcard_assignments_jobcard_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_assignments'))
        CREATE INDEX IX_erp_jobcard_assignments_jobcard_status ON dbo.erp_jobcard_assignments(jobcard_id, status, assignment_type);

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcard_technical_requests_jobcard_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcard_technical_requests'))
        CREATE INDEX IX_erp_jobcard_technical_requests_jobcard_status ON dbo.erp_jobcard_technical_requests(jobcard_id, status, request_type);

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_external_service_requests_jobcard_status' AND object_id = OBJECT_ID(N'dbo.erp_external_service_requests'))
        CREATE INDEX IX_erp_external_service_requests_jobcard_status ON dbo.erp_external_service_requests(jobcard_id, status);

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @err NVARCHAR(4000) = ERROR_MESSAGE();
    THROW 51090, @err, 1;
END CATCH;
