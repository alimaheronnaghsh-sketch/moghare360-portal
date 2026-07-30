/*
  CRM360_002_customer_legacy_vip_governance.sql
  Idempotent CRM legacy import + VIP governance tables in moghare360_ERP
*/
USE [moghare360_ERP];
GO

IF COL_LENGTH(N'dbo.crm360_reception_cases', N'source_ref_text') IS NULL
BEGIN
  ALTER TABLE dbo.crm360_reception_cases ADD source_ref_text NVARCHAR(200) NULL;
END
GO

IF OBJECT_ID(N'dbo.crm360_import_batches', N'U') IS NULL
CREATE TABLE dbo.crm360_import_batches (
  import_batch_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  batch_code NVARCHAR(40) NOT NULL,
  import_type NVARCHAR(40) NOT NULL,
  source_name NVARCHAR(200) NOT NULL,
  uploaded_file_name NVARCHAR(260) NULL,
  import_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_imp_st DEFAULT(N'DRAFT'),
  total_rows INT NOT NULL CONSTRAINT DF_crm360_imp_total DEFAULT(0),
  valid_rows INT NOT NULL CONSTRAINT DF_crm360_imp_valid DEFAULT(0),
  invalid_rows INT NOT NULL CONSTRAINT DF_crm360_imp_invalid DEFAULT(0),
  duplicate_rows INT NOT NULL CONSTRAINT DF_crm360_imp_dup DEFAULT(0),
  created_by NVARCHAR(80) NOT NULL,
  approved_by NVARCHAR(80) NULL,
  approved_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_imp_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_imp_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_imp_batch_code UNIQUE(batch_code)
);
GO

IF OBJECT_ID(N'dbo.crm360_import_rows', N'U') IS NULL
CREATE TABLE dbo.crm360_import_rows (
  import_row_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  import_batch_id INT NOT NULL,
  row_no INT NOT NULL,
  raw_json NVARCHAR(MAX) NOT NULL,
  normalized_json NVARCHAR(MAX) NULL,
  validation_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_impr_st DEFAULT(N'PENDING'),
  validation_message NVARCHAR(400) NULL,
  created_customer_profile_id INT NULL,
  created_vehicle_profile_id INT NULL,
  created_case_id INT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_impr_created DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_impr_batch' AND object_id=OBJECT_ID(N'dbo.crm360_import_rows'))
CREATE INDEX IX_crm360_impr_batch ON dbo.crm360_import_rows(import_batch_id, row_no);
GO

IF OBJECT_ID(N'dbo.crm360_vip_rules', N'U') IS NULL
CREATE TABLE dbo.crm360_vip_rules (
  vip_rule_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  rule_code NVARCHAR(40) NOT NULL,
  rule_title NVARCHAR(200) NOT NULL,
  rule_type NVARCHAR(40) NOT NULL,
  rule_formula NVARCHAR(200) NOT NULL,
  threshold_value DECIMAL(18,2) NULL,
  is_active BIT NOT NULL CONSTRAINT DF_crm360_vipr_active DEFAULT(1),
  approval_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_vipr_appr DEFAULT(N'APPROVED'),
  effective_from DATE NOT NULL CONSTRAINT DF_crm360_vipr_from DEFAULT(CONVERT(date, SYSUTCDATETIME())),
  effective_to DATE NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_vipr_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_vipr_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_vip_rule_code UNIQUE(rule_code)
);
GO

IF OBJECT_ID(N'dbo.crm360_vip_requests', N'U') IS NULL
CREATE TABLE dbo.crm360_vip_requests (
  vip_request_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  request_type NVARCHAR(40) NOT NULL,
  requested_tier NVARCHAR(20) NOT NULL,
  reason NVARCHAR(400) NOT NULL,
  evidence_json NVARCHAR(MAX) NULL,
  request_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_vipq_st DEFAULT(N'SUBMITTED'),
  requested_by NVARCHAR(80) NOT NULL,
  reviewed_by NVARCHAR(80) NULL,
  reviewed_at DATETIME2 NULL,
  review_note NVARCHAR(400) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_vipq_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_vipq_updated DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_vipq_cust' AND object_id=OBJECT_ID(N'dbo.crm360_vip_requests'))
CREATE INDEX IX_crm360_vipq_cust ON dbo.crm360_vip_requests(customer_profile_id, request_status);
GO

IF NOT EXISTS (SELECT 1 FROM dbo.crm360_vip_rules WHERE rule_code=N'VISIT_COUNT_4')
INSERT INTO dbo.crm360_vip_rules (rule_code, rule_title, rule_type, rule_formula, threshold_value, is_active, approval_status, created_by)
VALUES (N'VISIT_COUNT_4', N'حداقل ۴ مراجعه', N'VISIT_COUNT', N'visit_count >= threshold', 4, 1, N'APPROVED', N'system');

IF NOT EXISTS (SELECT 1 FROM dbo.crm360_vip_rules WHERE rule_code=N'REVENUE_1B')
INSERT INTO dbo.crm360_vip_rules (rule_code, rule_title, rule_type, rule_formula, threshold_value, is_active, approval_status, created_by)
VALUES (N'REVENUE_1B', N'درآمد تجمعی یک میلیارد ریال', N'REVENUE', N'total_revenue_amount >= threshold', 1000000000, 1, N'APPROVED', N'system');

IF NOT EXISTS (SELECT 1 FROM dbo.crm360_vip_rules WHERE rule_code=N'MANUAL_OWNER')
INSERT INTO dbo.crm360_vip_rules (rule_code, rule_title, rule_type, rule_formula, threshold_value, is_active, approval_status, created_by)
VALUES (N'MANUAL_OWNER', N'تصمیم مالک / مدیر', N'MANUAL', N'manual_nomination_approved', NULL, 1, N'APPROVED', N'system');
GO
