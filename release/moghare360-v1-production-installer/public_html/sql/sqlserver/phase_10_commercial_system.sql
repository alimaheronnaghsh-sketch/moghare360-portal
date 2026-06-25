/*
================================================================================
MOGHARE360 ERP — Phase 10 Commercial System
Script: phase_10_commercial_system.sql
================================================================================

Commercial demo foundation: registry, packages, license preview, readiness checks,
release history. No real SaaS, billing, payment gateway, or tenant activation.

Idempotent. No DROP. No USE database. Execute in SSMS on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_commercial_demo_registry', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_commercial_demo_registry
    (
        demo_registry_id    BIGINT          NOT NULL IDENTITY(1, 1),
        demo_code           NVARCHAR(100)   NOT NULL,
        demo_title          NVARCHAR(300)   NOT NULL,
        demo_type           NVARCHAR(100)   NOT NULL,
        demo_url            NVARCHAR(300)   NULL,
        demo_status         NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_com_demo_status DEFAULT (N'READY'),
        display_order       INT             NOT NULL
            CONSTRAINT DF_erp_com_demo_order DEFAULT (100),
        demo_note           NVARCHAR(1500)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_com_demo_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_commercial_demo_registry PRIMARY KEY CLUSTERED (demo_registry_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_commercial_package_plans', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_commercial_package_plans
    (
        package_plan_id         BIGINT          NOT NULL IDENTITY(1, 1),
        package_code            NVARCHAR(100)   NOT NULL,
        package_name            NVARCHAR(200)   NOT NULL,
        package_tier            NVARCHAR(80)    NOT NULL,
        package_description     NVARCHAR(2000)  NULL,
        target_customer         NVARCHAR(300)   NULL,
        monthly_price_preview   DECIMAL(18, 2)  NULL,
        setup_price_preview     DECIMAL(18, 2)  NULL,
        included_modules        NVARCHAR(MAX)   NULL,
        excluded_modules        NVARCHAR(MAX)   NULL,
        is_active_preview       BIT             NOT NULL
            CONSTRAINT DF_erp_com_pkg_active DEFAULT (1),
        created_at              DATETIME2       NOT NULL
            CONSTRAINT DF_erp_com_pkg_created DEFAULT (SYSUTCDATETIME()),
        created_by              NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_commercial_package_plans PRIMARY KEY CLUSTERED (package_plan_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_license_preview_models', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_license_preview_models
    (
        license_preview_id          BIGINT          NOT NULL IDENTITY(1, 1),
        license_code                NVARCHAR(100)   NOT NULL,
        license_name                NVARCHAR(200)   NOT NULL,
        license_type                NVARCHAR(100)   NOT NULL,
        max_users_preview           INT             NULL,
        max_branches_preview        INT             NULL,
        max_jobcards_monthly_preview INT            NULL,
        support_level               NVARCHAR(100)   NULL,
        license_note                NVARCHAR(2000)  NULL,
        is_active_preview           BIT             NOT NULL
            CONSTRAINT DF_erp_lic_preview_active DEFAULT (1),
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_lic_preview_created DEFAULT (SYSUTCDATETIME()),
        created_by                  NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_license_preview_models PRIMARY KEY CLUSTERED (license_preview_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_commercial_readiness_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_commercial_readiness_checks
    (
        readiness_check_id  BIGINT          NOT NULL IDENTITY(1, 1),
        check_code          NVARCHAR(100)   NOT NULL,
        check_group         NVARCHAR(100)   NOT NULL,
        check_title         NVARCHAR(300)   NOT NULL,
        check_status        NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_com_ready_status DEFAULT (N'PENDING'),
        check_score         DECIMAL(9, 2)   NOT NULL
            CONSTRAINT DF_erp_com_ready_score DEFAULT (0),
        check_note          NVARCHAR(1500)  NULL,
        checked_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_com_ready_checked DEFAULT (SYSUTCDATETIME()),
        checked_by          NVARCHAR(100)   NULL,
        CONSTRAINT PK_erp_commercial_readiness_checks PRIMARY KEY CLUSTERED (readiness_check_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_commercial_release_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_commercial_release_history
    (
        release_history_id  BIGINT          NOT NULL IDENTITY(1, 1),
        release_code        NVARCHAR(100)   NOT NULL,
        release_type        NVARCHAR(100)   NOT NULL,
        release_title       NVARCHAR(300)   NOT NULL,
        release_status      NVARCHAR(80)    NOT NULL
            CONSTRAINT DF_erp_com_rel_status DEFAULT (N'DRAFT'),
        release_summary     NVARCHAR(3000)  NULL,
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_com_rel_created DEFAULT (SYSUTCDATETIME()),
        created_by          NVARCHAR(100)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        CONSTRAINT PK_erp_commercial_release_history PRIMARY KEY CLUSTERED (release_history_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_demo_code' AND object_id = OBJECT_ID(N'dbo.erp_commercial_demo_registry', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_demo_code ON dbo.erp_commercial_demo_registry (demo_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_demo_type' AND object_id = OBJECT_ID(N'dbo.erp_commercial_demo_registry', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_demo_type ON dbo.erp_commercial_demo_registry (demo_type); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_pkg_code' AND object_id = OBJECT_ID(N'dbo.erp_commercial_package_plans', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_pkg_code ON dbo.erp_commercial_package_plans (package_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_pkg_tier' AND object_id = OBJECT_ID(N'dbo.erp_commercial_package_plans', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_pkg_tier ON dbo.erp_commercial_package_plans (package_tier); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_lic_preview_code' AND object_id = OBJECT_ID(N'dbo.erp_license_preview_models', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_lic_preview_code ON dbo.erp_license_preview_models (license_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_lic_preview_type' AND object_id = OBJECT_ID(N'dbo.erp_license_preview_models', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_lic_preview_type ON dbo.erp_license_preview_models (license_type); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_ready_code' AND object_id = OBJECT_ID(N'dbo.erp_commercial_readiness_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_ready_code ON dbo.erp_commercial_readiness_checks (check_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_ready_group' AND object_id = OBJECT_ID(N'dbo.erp_commercial_readiness_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_ready_group ON dbo.erp_commercial_readiness_checks (check_group); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_ready_status' AND object_id = OBJECT_ID(N'dbo.erp_commercial_readiness_checks', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_ready_status ON dbo.erp_commercial_readiness_checks (check_status); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_rel_code' AND object_id = OBJECT_ID(N'dbo.erp_commercial_release_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_rel_code ON dbo.erp_commercial_release_history (release_code); END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_com_rel_type' AND object_id = OBJECT_ID(N'dbo.erp_commercial_release_history', N'U'))
BEGIN CREATE NONCLUSTERED INDEX IX_erp_com_rel_type ON dbo.erp_commercial_release_history (release_type); END;
GO

/* Demo Registry seeds */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_demo_registry WHERE demo_code = N'INTERNAL-ERP-DEMO')
INSERT INTO dbo.erp_commercial_demo_registry (demo_code, demo_title, demo_type, demo_url, demo_status, display_order, demo_note, created_by)
VALUES (N'INTERNAL-ERP-DEMO', N'Soft Run Internal ERP', N'INTERNAL_ERP', N'erp-soft-run-home.php', N'READY', 10, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_demo_registry WHERE demo_code = N'BUSINESS-READY-DEMO')
INSERT INTO dbo.erp_commercial_demo_registry (demo_code, demo_title, demo_type, demo_url, demo_status, display_order, demo_note, created_by)
VALUES (N'BUSINESS-READY-DEMO', N'Business Ready System', N'BUSINESS_READY', N'erp-management-dashboard.php', N'READY', 20, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_demo_registry WHERE demo_code = N'COMMERCIAL-SHOWCASE')
INSERT INTO dbo.erp_commercial_demo_registry (demo_code, demo_title, demo_type, demo_url, demo_status, display_order, demo_note, created_by)
VALUES (N'COMMERCIAL-SHOWCASE', N'Commercial Showcase', N'COMMERCIAL_SHOWCASE', N'moghare360-sales-showcase.php', N'READY', 30, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_demo_registry WHERE demo_code = N'PRODUCT-PACKAGES')
INSERT INTO dbo.erp_commercial_demo_registry (demo_code, demo_title, demo_type, demo_url, demo_status, display_order, demo_note, created_by)
VALUES (N'PRODUCT-PACKAGES', N'Product Packages', N'PRODUCT_PACKAGE', N'moghare360-product-packages.php', N'READY', 40, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_demo_registry WHERE demo_code = N'LICENSE-PREVIEW')
INSERT INTO dbo.erp_commercial_demo_registry (demo_code, demo_title, demo_type, demo_url, demo_status, display_order, demo_note, created_by)
VALUES (N'LICENSE-PREVIEW', N'License Preview', N'LICENSE_PREVIEW', N'moghare360-license-preview.php', N'READY', 50, N'Seed', N'SYSTEM');
GO

/* Package Plans seeds */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_package_plans WHERE package_code = N'STARTER-WORKSHOP')
INSERT INTO dbo.erp_commercial_package_plans (package_code, package_name, package_tier, package_description, target_customer, monthly_price_preview, setup_price_preview, included_modules, excluded_modules, created_by)
VALUES (N'STARTER-WORKSHOP', N'Starter Workshop', N'STARTER', N'ورود مشتری، JobCard، عملیات پایه', N'تعمیرگاه کوچک تک‌شعبه', 2500000, 15000000, N'Customer,Operation,Rule', N'HR,CRM Advanced', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_package_plans WHERE package_code = N'STANDARD-WORKSHOP')
INSERT INTO dbo.erp_commercial_package_plans (package_code, package_name, package_tier, package_description, target_customer, monthly_price_preview, setup_price_preview, included_modules, excluded_modules, created_by)
VALUES (N'STANDARD-WORKSHOP', N'Standard Workshop', N'STANDARD', N'انبار، مالی preview، CRM پایه', N'تعمیرگاه متوسط', 4500000, 25000000, N'Customer,Operation,Inventory,Finance Preview,CRM', N'HR Payroll', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_package_plans WHERE package_code = N'PROFESSIONAL-WORKSHOP')
INSERT INTO dbo.erp_commercial_package_plans (package_code, package_name, package_tier, package_description, target_customer, monthly_price_preview, setup_price_preview, included_modules, excluded_modules, created_by)
VALUES (N'PROFESSIONAL-WORKSHOP', N'Professional Workshop', N'PROFESSIONAL', N'گزارش مدیریتی، HR، CRM کامل', N'تعمیرگاه حرفه‌ای', 7500000, 40000000, N'All Phase 1-9 modules', N'SaaS Multi-tenant', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_package_plans WHERE package_code = N'ENTERPRISE-READY')
INSERT INTO dbo.erp_commercial_package_plans (package_code, package_name, package_tier, package_description, target_customer, monthly_price_preview, setup_price_preview, included_modules, excluded_modules, created_by)
VALUES (N'ENTERPRISE-READY', N'Enterprise Ready', N'ENTERPRISE', N'آماده چندشعبه‌ای — طراحی فقط', N'شبکه تعمیرگاه / نمایندگی', NULL, NULL, N'Full ERP + Commercial Demo', N'Production SaaS (not active)', N'SYSTEM');
GO

/* License Preview seeds */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_license_preview_models WHERE license_code = N'DEMO-ONLY')
INSERT INTO dbo.erp_license_preview_models (license_code, license_name, license_type, max_users_preview, max_branches_preview, max_jobcards_monthly_preview, support_level, license_note, created_by)
VALUES (N'DEMO-ONLY', N'Demo Only', N'DEMO_ONLY', 3, 1, 50, N'None', N'نمایش فروش — بدون enforcement', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_license_preview_models WHERE license_code = N'SINGLE-WORKSHOP')
INSERT INTO dbo.erp_license_preview_models (license_code, license_name, license_type, max_users_preview, max_branches_preview, max_jobcards_monthly_preview, support_level, license_note, created_by)
VALUES (N'SINGLE-WORKSHOP', N'Single Workshop', N'SINGLE_WORKSHOP', 15, 1, 500, N'Standard', N'یک تعمیرگاه', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_license_preview_models WHERE license_code = N'MULTI-BRANCH-READY')
INSERT INTO dbo.erp_license_preview_models (license_code, license_name, license_type, max_users_preview, max_branches_preview, max_jobcards_monthly_preview, support_level, license_note, created_by)
VALUES (N'MULTI-BRANCH-READY', N'Multi Branch Ready', N'MULTI_BRANCH_READY', 50, 5, 2000, N'Priority', N'طراحی tenant-ready — فعال نیست', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_license_preview_models WHERE license_code = N'ENTERPRISE-READY')
INSERT INTO dbo.erp_license_preview_models (license_code, license_name, license_type, max_users_preview, max_branches_preview, max_jobcards_monthly_preview, support_level, license_note, created_by)
VALUES (N'ENTERPRISE-READY', N'Enterprise Ready', N'ENTERPRISE_READY', NULL, NULL, NULL, N'Dedicated', N'سفارشی — بدون SaaS production', N'SYSTEM');
GO

/* Readiness Checks seeds */
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'INTERNAL_ERP_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'INTERNAL_ERP_READY', N'PRODUCT', N'Internal ERP Ready', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'BUSINESS_READY_SYSTEM_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'BUSINESS_READY_SYSTEM_READY', N'PRODUCT', N'Business Ready System', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'COMMERCIAL_DEMO_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'COMMERCIAL_DEMO_READY', N'COMMERCIAL', N'Commercial Demo Ready', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'PRODUCT_PACKAGE_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'PRODUCT_PACKAGE_READY', N'COMMERCIAL', N'Product Package Ready', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'LICENSE_PREVIEW_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'LICENSE_PREVIEW_READY', N'COMMERCIAL', N'License Preview Ready', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'TENANT_ARCHITECTURE_DOCUMENTED')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'TENANT_ARCHITECTURE_DOCUMENTED', N'ARCHITECTURE', N'Tenant Architecture Documented', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'SAAS_NOT_ACTIVE_SAFE')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'SAAS_NOT_ACTIVE_SAFE', N'SAFETY', N'SaaS Not Active Safe', N'PASSED', 10, N'By design', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'AUTH_BOUNDARY_PROTECTED')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'AUTH_BOUNDARY_PROTECTED', N'SAFETY', N'Auth Boundary Protected', N'PASSED', 10, N'No auth rewrite', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'PERMISSION_BOUNDARY_PROTECTED')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'PERMISSION_BOUNDARY_PROTECTED', N'SAFETY', N'Permission Boundary Protected', N'PASSED', 10, N'No permission rewrite', N'SYSTEM');
GO
IF NOT EXISTS (SELECT 1 FROM dbo.erp_commercial_readiness_checks WHERE check_code = N'FINAL_REPORT_READY')
INSERT INTO dbo.erp_commercial_readiness_checks (check_code, check_group, check_title, check_status, check_score, check_note, checked_by)
VALUES (N'FINAL_REPORT_READY', N'RELEASE', N'Final Report Ready', N'PENDING', 0, N'Seed', N'SYSTEM');
GO
