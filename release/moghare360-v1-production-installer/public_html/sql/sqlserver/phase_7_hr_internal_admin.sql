/*
================================================================================
MOGHARE360 ERP — Phase 7 HR & Internal Admin System
Script: phase_7_hr_internal_admin.sql
================================================================================

Internal HR foundation: employees, contracts, attendance, payroll preview,
training, disciplinary records. No official payroll/insurance/tax documents.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_hr_employees', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_employees
    (
        employee_id                 BIGINT          NOT NULL IDENTITY(1, 1),
        employee_code               NVARCHAR(100)   NOT NULL,
        full_name                   NVARCHAR(250)   NOT NULL,
        mobile                      NVARCHAR(50)    NULL,
        national_code               NVARCHAR(50)    NULL,
        birth_date                  DATE            NULL,
        marital_status              NVARCHAR(50)    NULL,
        children_count              INT             NULL,
        emergency_contact_name      NVARCHAR(200)   NULL,
        emergency_contact_mobile    NVARCHAR(50)    NULL,
        department_name             NVARCHAR(150)   NULL,
        position_title              NVARCHAR(150)   NULL,
        employment_status           NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_employees_status DEFAULT (N'ACTIVE'),
        hire_date                   DATE            NULL,
        exit_date                   DATE            NULL,
        notes                       NVARCHAR(1500)  NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_employees_created DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        updated_at                  DATETIME2       NULL,
        updated_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_employees PRIMARY KEY CLUSTERED (employee_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_employment_contracts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_employment_contracts
    (
        contract_id             BIGINT          NOT NULL IDENTITY(1, 1),
        employee_id             BIGINT          NOT NULL,
        contract_code           NVARCHAR(100)   NOT NULL,
        contract_type           NVARCHAR(80)    NOT NULL,
        start_date              DATE            NOT NULL,
        end_date                DATE            NULL,
        base_salary             DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_contract_base DEFAULT (0),
        allowance_total         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_contract_allow DEFAULT (0),
        overtime_allowed        BIT             NOT NULL
            CONSTRAINT DF_erp_hr_contract_ot DEFAULT (1),
        friday_work_allowed     BIT             NOT NULL
            CONSTRAINT DF_erp_hr_contract_fri DEFAULT (0),
        night_work_allowed      BIT             NOT NULL
            CONSTRAINT DF_erp_hr_contract_night DEFAULT (0),
        settlement_mode         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_contract_settle DEFAULT (N'MONTHLY_PREVIEW'),
        contract_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_contract_status DEFAULT (N'DRAFT'),
        terms_summary           NVARCHAR(2000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_contract_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_employment_contracts PRIMARY KEY CLUSTERED (contract_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_attendance_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_attendance_records
    (
        attendance_id           BIGINT          NOT NULL IDENTITY(1, 1),
        employee_id             BIGINT          NOT NULL,
        attendance_date         DATE            NOT NULL,
        check_in_time           TIME            NULL,
        check_out_time          TIME            NULL,
        work_hours              DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_work DEFAULT (0),
        break_hours             DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_break DEFAULT (0),
        net_work_hours          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_net DEFAULT (0),
        required_hours          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_req DEFAULT (0),
        overtime_hours          DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_ot DEFAULT (0),
        absence_hours           DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_att_abs DEFAULT (0),
        is_friday_or_holiday    BIT             NOT NULL
            CONSTRAINT DF_erp_hr_att_fri DEFAULT (0),
        attendance_status       NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_att_status DEFAULT (N'RECORDED'),
        notes                   NVARCHAR(1000)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_att_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_attendance_records PRIMARY KEY CLUSTERED (attendance_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_payroll_previews', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_payroll_previews
    (
        payroll_preview_id      BIGINT          NOT NULL IDENTITY(1, 1),
        employee_id             BIGINT          NOT NULL,
        contract_id             BIGINT          NULL,
        payroll_period_start    DATE            NOT NULL,
        payroll_period_end      DATE            NOT NULL,
        base_salary             DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_base DEFAULT (0),
        allowance_total         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_allow DEFAULT (0),
        overtime_amount         DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_ot DEFAULT (0),
        friday_work_amount      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_fri DEFAULT (0),
        bonus_amount            DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_bonus DEFAULT (0),
        deduction_amount        DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_ded DEFAULT (0),
        gross_preview_amount    DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_gross DEFAULT (0),
        net_preview_amount      DECIMAL(18, 2)  NOT NULL
            CONSTRAINT DF_erp_hr_pay_net DEFAULT (0),
        preview_status          NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_pay_status DEFAULT (N'DRAFT'),
        preview_note            NVARCHAR(1500)  NULL,
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_pay_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        updated_at              DATETIME2       NULL,
        updated_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_payroll_previews PRIMARY KEY CLUSTERED (payroll_preview_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_training_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_training_records
    (
        training_id         BIGINT          NOT NULL IDENTITY(1, 1),
        employee_id         BIGINT          NOT NULL,
        training_title      NVARCHAR(300)   NOT NULL,
        training_type       NVARCHAR(100)   NOT NULL
            CONSTRAINT DF_erp_hr_train_type DEFAULT (N'INTERNAL'),
        training_date       DATE            NULL,
        trainer_name        NVARCHAR(200)   NULL,
        result_status       NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_train_result DEFAULT (N'RECORDED'),
        score_value         DECIMAL(18, 2)  NULL,
        notes               NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_train_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_training_records PRIMARY KEY CLUSTERED (training_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_disciplinary_records', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_disciplinary_records
    (
        disciplinary_id     BIGINT          NOT NULL IDENTITY(1, 1),
        employee_id         BIGINT          NOT NULL,
        record_type         NVARCHAR(100)   NOT NULL,
        record_title        NVARCHAR(300)   NOT NULL,
        record_date         DATE            NOT NULL,
        severity_level      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_hr_disc_sev DEFAULT (N'LOW'),
        action_taken        NVARCHAR(1500)  NULL,
        notes               NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_disc_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_hr_disciplinary_records PRIMARY KEY CLUSTERED (disciplinary_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_hr_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_hr_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        entity_type         NVARCHAR(80)    NOT NULL,
        entity_id           BIGINT          NULL,
        action_type         NVARCHAR(80)    NOT NULL,
        action_summary      NVARCHAR(1000)  NULL,
        old_value           NVARCHAR(MAX)   NULL,
        new_value           NVARCHAR(MAX)   NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_hr_history_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_hr_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_employees_code' AND object_id = OBJECT_ID(N'dbo.erp_hr_employees', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_employees_code ON dbo.erp_hr_employees (employee_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_employees_name' AND object_id = OBJECT_ID(N'dbo.erp_hr_employees', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_employees_name ON dbo.erp_hr_employees (full_name); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_employees_mobile' AND object_id = OBJECT_ID(N'dbo.erp_hr_employees', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_employees_mobile ON dbo.erp_hr_employees (mobile); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_employees_national' AND object_id = OBJECT_ID(N'dbo.erp_hr_employees', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_employees_national ON dbo.erp_hr_employees (national_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_employees_emp_status' AND object_id = OBJECT_ID(N'dbo.erp_hr_employees', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_employees_emp_status ON dbo.erp_hr_employees (employment_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_contracts_employee' AND object_id = OBJECT_ID(N'dbo.erp_hr_employment_contracts', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_contracts_employee ON dbo.erp_hr_employment_contracts (employee_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_contracts_code' AND object_id = OBJECT_ID(N'dbo.erp_hr_employment_contracts', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_contracts_code ON dbo.erp_hr_employment_contracts (contract_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_contracts_status' AND object_id = OBJECT_ID(N'dbo.erp_hr_employment_contracts', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_contracts_status ON dbo.erp_hr_employment_contracts (contract_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_attendance_employee' AND object_id = OBJECT_ID(N'dbo.erp_hr_attendance_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_attendance_employee ON dbo.erp_hr_attendance_records (employee_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_attendance_date' AND object_id = OBJECT_ID(N'dbo.erp_hr_attendance_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_attendance_date ON dbo.erp_hr_attendance_records (attendance_date); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_payroll_employee' AND object_id = OBJECT_ID(N'dbo.erp_hr_payroll_previews', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_payroll_employee ON dbo.erp_hr_payroll_previews (employee_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_payroll_period' AND object_id = OBJECT_ID(N'dbo.erp_hr_payroll_previews', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_payroll_period ON dbo.erp_hr_payroll_previews (payroll_period_start, payroll_period_end); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_training_employee' AND object_id = OBJECT_ID(N'dbo.erp_hr_training_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_training_employee ON dbo.erp_hr_training_records (employee_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_disciplinary_employee' AND object_id = OBJECT_ID(N'dbo.erp_hr_disciplinary_records', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_disciplinary_employee ON dbo.erp_hr_disciplinary_records (employee_id); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_hr_history_entity' AND object_id = OBJECT_ID(N'dbo.erp_hr_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_hr_history_entity ON dbo.erp_hr_history (entity_type, entity_id); END;
GO

PRINT N'Phase 7 HR & Internal Admin System SQL completed.';
GO
