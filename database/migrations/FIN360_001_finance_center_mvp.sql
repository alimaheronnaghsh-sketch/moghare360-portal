/*
  FIN360_001_finance_center_mvp.sql
  Idempotent Finance360 MVP tables in moghare360_ERP
*/
USE [moghare360_ERP];
GO

IF OBJECT_ID(N'dbo.fin360_accounts',N'U') IS NULL
CREATE TABLE dbo.fin360_accounts (
  account_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  account_code NVARCHAR(40) NOT NULL,
  account_name_fa NVARCHAR(200) NOT NULL,
  account_type NVARCHAR(40) NOT NULL,
  parent_account_id INT NULL,
  is_control_account BIT NOT NULL CONSTRAINT DF_fin360_acc_ctrl DEFAULT(0),
  is_active BIT NOT NULL CONSTRAINT DF_fin360_acc_active DEFAULT(1),
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_acc_created DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_accounts_code UNIQUE(account_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_parties',N'U') IS NULL
CREATE TABLE dbo.fin360_parties (
  party_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  party_type NVARCHAR(32) NOT NULL,
  display_name NVARCHAR(200) NOT NULL,
  mobile NVARCHAR(40) NULL,
  national_id NVARCHAR(40) NULL,
  economic_code NVARCHAR(40) NULL,
  tax_memory_id NVARCHAR(80) NULL,
  source_type NVARCHAR(40) NULL,
  source_ref_text NVARCHAR(200) NULL,
  is_active BIT NOT NULL CONSTRAINT DF_fin360_party_active DEFAULT(1),
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_party_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_cash_accounts',N'U') IS NULL
CREATE TABLE dbo.fin360_cash_accounts (
  cash_account_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  account_type NVARCHAR(32) NOT NULL,
  account_title NVARCHAR(200) NOT NULL,
  bank_name NVARCHAR(120) NULL,
  account_no NVARCHAR(80) NULL,
  iban NVARCHAR(80) NULL,
  pos_terminal_no NVARCHAR(80) NULL,
  gateway_code NVARCHAR(80) NULL,
  currency_code NVARCHAR(8) NOT NULL CONSTRAINT DF_fin360_cash_ccy DEFAULT(N'IRR'),
  opening_balance DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cash_open DEFAULT(0),
  current_book_balance DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cash_bal DEFAULT(0),
  is_active BIT NOT NULL CONSTRAINT DF_fin360_cash_active DEFAULT(1),
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_cash_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_documents',N'U') IS NULL
CREATE TABLE dbo.fin360_documents (
  document_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  document_code NVARCHAR(40) NOT NULL,
  document_type NVARCHAR(40) NOT NULL,
  document_status NVARCHAR(40) NOT NULL CONSTRAINT DF_fin360_doc_status DEFAULT(N'DRAFT'),
  document_date DATE NOT NULL,
  party_id INT NULL,
  jobcard_ref_text NVARCHAR(120) NULL,
  source_type NVARCHAR(40) NULL,
  source_ref_text NVARCHAR(200) NULL,
  currency_code NVARCHAR(8) NOT NULL CONSTRAINT DF_fin360_doc_ccy DEFAULT(N'IRR'),
  exchange_rate DECIMAL(18,6) NOT NULL CONSTRAINT DF_fin360_doc_fx DEFAULT(1),
  subtotal_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_sub DEFAULT(0),
  discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_disc DEFAULT(0),
  tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_tax DEFAULT(0),
  total_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_tot DEFAULT(0),
  paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_paid DEFAULT(0),
  remaining_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_doc_rem DEFAULT(0),
  created_by NVARCHAR(80) NOT NULL,
  approved_by NVARCHAR(80) NULL,
  posted_by NVARCHAR(80) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_doc_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_doc_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_documents_code UNIQUE(document_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_document_lines',N'U') IS NULL
CREATE TABLE dbo.fin360_document_lines (
  line_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  document_id INT NOT NULL,
  line_type NVARCHAR(32) NOT NULL,
  item_title NVARCHAR(300) NOT NULL,
  description NVARCHAR(MAX) NULL,
  quantity DECIMAL(18,4) NOT NULL CONSTRAINT DF_fin360_line_qty DEFAULT(1),
  unit_price DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_line_price DEFAULT(0),
  discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_line_disc DEFAULT(0),
  tax_code NVARCHAR(40) NULL,
  tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_line_tax DEFAULT(0),
  cost_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_line_cost DEFAULT(0),
  line_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_line_tot DEFAULT(0),
  cost_center NVARCHAR(80) NULL,
  profit_center NVARCHAR(80) NULL,
  jobcard_ref_text NVARCHAR(120) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_line_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_payments',N'U') IS NULL
CREATE TABLE dbo.fin360_payments (
  payment_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  payment_code NVARCHAR(40) NOT NULL,
  payment_type NVARCHAR(32) NOT NULL,
  payment_direction NVARCHAR(8) NOT NULL,
  party_id INT NULL,
  cash_account_id INT NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  currency_code NVARCHAR(8) NOT NULL CONSTRAINT DF_fin360_pay_ccy DEFAULT(N'IRR'),
  exchange_rate DECIMAL(18,6) NOT NULL CONSTRAINT DF_fin360_pay_fx DEFAULT(1),
  fee_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_pay_fee DEFAULT(0),
  reference_no NVARCHAR(120) NULL,
  cheque_no NVARCHAR(80) NULL,
  cheque_due_date DATE NULL,
  status_code NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_pay_status DEFAULT(N'DRAFT'),
  allocation_status NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_pay_alloc DEFAULT(N'UNALLOCATED'),
  description NVARCHAR(400) NULL,
  created_by NVARCHAR(80) NOT NULL,
  approved_by NVARCHAR(80) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_pay_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_pay_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_payments_code UNIQUE(payment_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_payment_allocations',N'U') IS NULL
CREATE TABLE dbo.fin360_payment_allocations (
  allocation_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  payment_id INT NOT NULL,
  document_id INT NOT NULL,
  allocated_amount DECIMAL(18,2) NOT NULL,
  allocation_date DATE NOT NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_alloc_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_jobcard_settlements',N'U') IS NULL
CREATE TABLE dbo.fin360_jobcard_settlements (
  settlement_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  settlement_code NVARCHAR(40) NOT NULL,
  jobcard_ref_text NVARCHAR(120) NOT NULL,
  customer_party_id INT NULL,
  service_sales_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_svc DEFAULT(0),
  parts_sales_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_parts DEFAULT(0),
  external_service_sales_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_ext DEFAULT(0),
  additional_charges_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_add DEFAULT(0),
  discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_disc DEFAULT(0),
  tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_tax DEFAULT(0),
  prepayment_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_prep DEFAULT(0),
  paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_paid DEFAULT(0),
  credit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_cred DEFAULT(0),
  final_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_final DEFAULT(0),
  remaining_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_set_rem DEFAULT(0),
  settlement_status NVARCHAR(40) NOT NULL CONSTRAINT DF_fin360_set_status DEFAULT(N'READY_FOR_SETTLEMENT'),
  financial_release_status NVARCHAR(40) NOT NULL CONSTRAINT DF_fin360_set_rel DEFAULT(N'BLOCKED'),
  override_reason NVARCHAR(400) NULL,
  created_by NVARCHAR(80) NOT NULL,
  approved_by NVARCHAR(80) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_set_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_set_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_settlements_code UNIQUE(settlement_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_posting_rules',N'U') IS NULL
CREATE TABLE dbo.fin360_posting_rules (
  posting_rule_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  event_code NVARCHAR(60) NOT NULL,
  document_type NVARCHAR(40) NOT NULL,
  debit_account_id INT NOT NULL,
  credit_account_id INT NOT NULL,
  tax_rule_code NVARCHAR(40) NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  version_no INT NOT NULL CONSTRAINT DF_fin360_pr_ver DEFAULT(1),
  approval_status NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_pr_appr DEFAULT(N'APPROVED'),
  is_active BIT NOT NULL CONSTRAINT DF_fin360_pr_active DEFAULT(1),
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_pr_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_journal_headers',N'U') IS NULL
CREATE TABLE dbo.fin360_journal_headers (
  journal_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  journal_code NVARCHAR(40) NOT NULL,
  document_id INT NULL,
  journal_date DATE NOT NULL,
  journal_status NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_jh_status DEFAULT(N'DRAFT'),
  description NVARCHAR(400) NULL,
  total_debit DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_jh_dr DEFAULT(0),
  total_credit DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_jh_cr DEFAULT(0),
  created_by NVARCHAR(80) NOT NULL,
  posted_by NVARCHAR(80) NULL,
  posted_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_jh_created DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_journals_code UNIQUE(journal_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_journal_lines',N'U') IS NULL
CREATE TABLE dbo.fin360_journal_lines (
  journal_line_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  journal_id INT NOT NULL,
  account_id INT NOT NULL,
  debit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_jl_dr DEFAULT(0),
  credit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_jl_cr DEFAULT(0),
  party_id INT NULL,
  cost_center NVARCHAR(80) NULL,
  profit_center NVARCHAR(80) NULL,
  jobcard_ref_text NVARCHAR(120) NULL,
  description NVARCHAR(400) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_jl_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_tax_rules',N'U') IS NULL
CREATE TABLE dbo.fin360_tax_rules (
  tax_rule_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  rule_type NVARCHAR(60) NOT NULL,
  legal_reference NVARCHAR(200) NOT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  version_no INT NOT NULL CONSTRAINT DF_fin360_tr_ver DEFAULT(1),
  jurisdiction NVARCHAR(80) NOT NULL CONSTRAINT DF_fin360_tr_jur DEFAULT(N'IR'),
  taxpayer_type NVARCHAR(80) NULL,
  rate_or_formula NVARCHAR(200) NOT NULL,
  approval_status NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_tr_appr DEFAULT(N'DRAFT'),
  published_source NVARCHAR(200) NULL,
  last_verified_at DATETIME2 NULL,
  is_active BIT NOT NULL CONSTRAINT DF_fin360_tr_active DEFAULT(0)
);
GO

IF OBJECT_ID(N'dbo.fin360_tax_documents',N'U') IS NULL
CREATE TABLE dbo.fin360_tax_documents (
  tax_document_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  document_id INT NOT NULL,
  tax_invoice_type NVARCHAR(40) NULL,
  tax_pattern NVARCHAR(40) NULL,
  taxpayer_memory_id NVARCHAR(80) NULL,
  tax_unique_number NVARCHAR(80) NULL,
  submission_status NVARCHAR(40) NOT NULL CONSTRAINT DF_fin360_td_status DEFAULT(N'NOT_READY'),
  submission_date DATETIME2 NULL,
  response_code NVARCHAR(40) NULL,
  response_message NVARCHAR(400) NULL,
  correction_reference NVARCHAR(120) NULL,
  cancellation_reference NVARCHAR(120) NULL,
  return_reference NVARCHAR(120) NULL,
  payload_hash NVARCHAR(128) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_td_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.fin360_import_cases',N'U') IS NULL
CREATE TABLE dbo.fin360_import_cases (
  import_case_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  import_case_code NVARCHAR(40) NOT NULL,
  title NVARCHAR(300) NOT NULL,
  supplier_party_id INT NULL,
  currency_code NVARCHAR(8) NOT NULL CONSTRAINT DF_fin360_imp_ccy DEFAULT(N'USD'),
  proforma_rate DECIMAL(18,6) NULL,
  contract_rate DECIMAL(18,6) NULL,
  payment_rate DECIMAL(18,6) NULL,
  customs_rate DECIMAL(18,6) NULL,
  revaluation_rate DECIMAL(18,6) NULL,
  foreign_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_fa DEFAULT(0),
  freight_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_fr DEFAULT(0),
  insurance_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_ins DEFAULT(0),
  bank_fee_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_bf DEFAULT(0),
  customs_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_cu DEFAULT(0),
  clearance_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_cl DEFAULT(0),
  domestic_transport_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_dt DEFAULT(0),
  other_costs_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_ot DEFAULT(0),
  landed_cost_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_imp_lc DEFAULT(0),
  allocation_method NVARCHAR(40) NULL,
  status_code NVARCHAR(32) NOT NULL CONSTRAINT DF_fin360_imp_st DEFAULT(N'DRAFT'),
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_imp_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_imp_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_import_code UNIQUE(import_case_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_cost_calculations',N'U') IS NULL
CREATE TABLE dbo.fin360_cost_calculations (
  cost_calc_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  calc_code NVARCHAR(40) NOT NULL,
  object_type NVARCHAR(40) NOT NULL,
  object_ref_text NVARCHAR(120) NOT NULL,
  calculation_method NVARCHAR(40) NOT NULL,
  revenue_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cc_rev DEFAULT(0),
  direct_cost_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cc_dc DEFAULT(0),
  allocated_cost_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cc_ac DEFAULT(0),
  gross_profit_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_fin360_cc_gp DEFAULT(0),
  gross_margin_percent DECIMAL(9,2) NOT NULL CONSTRAINT DF_fin360_cc_gm DEFAULT(0),
  calculation_trace NVARCHAR(MAX) NULL,
  version_no INT NOT NULL CONSTRAINT DF_fin360_cc_ver DEFAULT(1),
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_cc_created DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_cost_code UNIQUE(calc_code)
);
GO

IF OBJECT_ID(N'dbo.fin360_audit_log',N'U') IS NULL
CREATE TABLE dbo.fin360_audit_log (
  audit_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  event_time DATETIME2 NOT NULL CONSTRAINT DF_fin360_audit_time DEFAULT(SYSUTCDATETIME()),
  actor_user NVARCHAR(80) NOT NULL,
  action_code NVARCHAR(60) NOT NULL,
  entity_name NVARCHAR(80) NOT NULL,
  entity_id NVARCHAR(80) NULL,
  before_json NVARCHAR(MAX) NULL,
  after_json NVARCHAR(MAX) NULL,
  reason NVARCHAR(400) NULL,
  ip_address NVARCHAR(64) NULL,
  device_info NVARCHAR(200) NULL,
  source_page NVARCHAR(120) NULL,
  approver_user NVARCHAR(80) NULL
);
GO

IF OBJECT_ID(N'dbo.fin360_settings',N'U') IS NULL
CREATE TABLE dbo.fin360_settings (
  setting_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  setting_key NVARCHAR(80) NOT NULL,
  setting_value NVARCHAR(MAX) NOT NULL,
  effective_from DATE NULL,
  effective_to DATE NULL,
  is_active BIT NOT NULL CONSTRAINT DF_fin360_setg_active DEFAULT(1),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_fin360_setg_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_fin360_settings_key UNIQUE(setting_key)
);
GO

/* Seed chart of accounts */
IF NOT EXISTS (SELECT 1 FROM dbo.fin360_accounts WHERE account_code=N'1000')
INSERT INTO dbo.fin360_accounts(account_code,account_name_fa,account_type,is_control_account) VALUES
(N'1000',N'نقد و بانک',N'ASSET',1),
(N'1100',N'دریافتنی‌ها',N'ASSET',1),
(N'2000',N'پرداختنی‌ها',N'LIABILITY',1),
(N'2100',N'پیش‌دریافت مشتری',N'LIABILITY',0),
(N'2200',N'پیش‌پرداخت تأمین‌کننده',N'ASSET',0),
(N'4000',N'درآمد خدمات',N'REVENUE',0),
(N'4100',N'درآمد قطعات',N'REVENUE',0),
(N'4200',N'درآمد خدمات بیرونی',N'REVENUE',0),
(N'5000',N'بهای تمام‌شده قطعات',N'COGS',0),
(N'5100',N'بهای تمام‌شده خدمات بیرونی',N'COGS',0),
(N'6000',N'هزینه‌های عملیاتی',N'EXPENSE',0),
(N'2300',N'مالیات پرداختنی',N'LIABILITY',0),
(N'3000',N'سود و زیان',N'EQUITY',1);
GO

/* Indexes */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_fin360_documents_type_status_date' AND object_id=OBJECT_ID(N'dbo.fin360_documents'))
CREATE INDEX IX_fin360_documents_type_status_date ON dbo.fin360_documents(document_type, document_status, document_date);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_fin360_documents_party' AND object_id=OBJECT_ID(N'dbo.fin360_documents'))
CREATE INDEX IX_fin360_documents_party ON dbo.fin360_documents(party_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_fin360_documents_jobcard' AND object_id=OBJECT_ID(N'dbo.fin360_documents'))
CREATE INDEX IX_fin360_documents_jobcard ON dbo.fin360_documents(jobcard_ref_text);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_fin360_payments_cash' AND object_id=OBJECT_ID(N'dbo.fin360_payments'))
CREATE INDEX IX_fin360_payments_cash ON dbo.fin360_payments(cash_account_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_fin360_settlements_jobcard' AND object_id=OBJECT_ID(N'dbo.fin360_jobcard_settlements'))
CREATE INDEX IX_fin360_settlements_jobcard ON dbo.fin360_jobcard_settlements(jobcard_ref_text);
GO