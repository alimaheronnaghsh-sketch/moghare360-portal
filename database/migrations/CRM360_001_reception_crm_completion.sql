/*
  CRM360_001_reception_crm_completion.sql
  Idempotent CRM/Reception completion tables in moghare360_ERP
*/
USE [moghare360_ERP];
GO

IF OBJECT_ID(N'dbo.crm360_customer_profiles',N'U') IS NULL
CREATE TABLE dbo.crm360_customer_profiles (
  customer_profile_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  existing_customer_id INT NULL,
  customer_ref_text NVARCHAR(120) NULL,
  full_name NVARCHAR(200) NOT NULL,
  mobile NVARCHAR(40) NULL,
  national_id NVARCHAR(40) NULL,
  economic_code NVARCHAR(40) NULL,
  customer_type NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_cust_type DEFAULT(N'PERSON'),
  gender NVARCHAR(16) NULL,
  birth_date DATE NULL,
  city NVARCHAR(80) NULL,
  address NVARCHAR(400) NULL,
  preferred_contact_channel NVARCHAR(40) NULL,
  consent_sms BIT NOT NULL CONSTRAINT DF_crm360_cust_sms DEFAULT(0),
  consent_marketing BIT NOT NULL CONSTRAINT DF_crm360_cust_mkt DEFAULT(0),
  consent_service_reminder BIT NOT NULL CONSTRAINT DF_crm360_cust_rem DEFAULT(1),
  risk_level NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_cust_risk DEFAULT(N'NORMAL'),
  vip_level NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_cust_vip DEFAULT(N'NONE'),
  customer_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_cust_st DEFAULT(N'NEW'),
  source_channel NVARCHAR(40) NULL,
  notes NVARCHAR(MAX) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cust_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cust_updated DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_cust_mobile' AND object_id=OBJECT_ID(N'dbo.crm360_customer_profiles'))
CREATE INDEX IX_crm360_cust_mobile ON dbo.crm360_customer_profiles(mobile);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_cust_status' AND object_id=OBJECT_ID(N'dbo.crm360_customer_profiles'))
CREATE INDEX IX_crm360_cust_status ON dbo.crm360_customer_profiles(customer_status);
GO

IF OBJECT_ID(N'dbo.crm360_vehicle_profiles',N'U') IS NULL
CREATE TABLE dbo.crm360_vehicle_profiles (
  vehicle_profile_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  existing_vehicle_id INT NULL,
  vehicle_ref_text NVARCHAR(120) NULL,
  plate_no NVARCHAR(40) NULL,
  vin NVARCHAR(80) NULL,
  brand NVARCHAR(80) NOT NULL,
  model NVARCHAR(80) NOT NULL,
  trim_level NVARCHAR(80) NULL,
  model_year INT NULL,
  color NVARCHAR(40) NULL,
  mileage INT NULL,
  engine_code NVARCHAR(80) NULL,
  gearbox NVARCHAR(40) NULL,
  fuel_type NVARCHAR(40) NULL,
  warranty_status NVARCHAR(40) NULL,
  service_interval_km INT NULL,
  service_interval_months INT NULL,
  last_service_date DATE NULL,
  last_service_km INT NULL,
  next_service_date DATE NULL,
  next_service_km INT NULL,
  vehicle_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_veh_st DEFAULT(N'ACTIVE'),
  notes NVARCHAR(MAX) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_veh_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_veh_updated DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_veh_cust' AND object_id=OBJECT_ID(N'dbo.crm360_vehicle_profiles'))
CREATE INDEX IX_crm360_veh_cust ON dbo.crm360_vehicle_profiles(customer_profile_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_veh_plate' AND object_id=OBJECT_ID(N'dbo.crm360_vehicle_profiles'))
CREATE INDEX IX_crm360_veh_plate ON dbo.crm360_vehicle_profiles(plate_no);
GO

IF OBJECT_ID(N'dbo.crm360_reception_cases',N'U') IS NULL
CREATE TABLE dbo.crm360_reception_cases (
  case_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  case_code NVARCHAR(40) NOT NULL,
  existing_request_id INT NULL,
  existing_jobcard_id INT NULL,
  existing_contract_id INT NULL,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NOT NULL,
  case_type NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_case_type DEFAULT(N'WALKIN'),
  service_type NVARCHAR(120) NULL,
  reception_source NVARCHAR(40) NULL,
  assigned_staff NVARCHAR(80) NULL,
  responsible_staff NVARCHAR(80) NULL,
  case_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_case_st DEFAULT(N'DRAFT'),
  profile_completion_percent INT NOT NULL CONSTRAINT DF_crm360_case_pct DEFAULT(0),
  required_docs_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_case_docs DEFAULT(N'MISSING'),
  contract_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_case_ctr DEFAULT(N'NONE'),
  satisfaction_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_case_sat DEFAULT(N'NONE'),
  complaint_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_case_cmp DEFAULT(N'NONE'),
  financial_ref_text NVARCHAR(120) NULL,
  work_ref_text NVARCHAR(120) NULL,
  notes NVARCHAR(MAX) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_case_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_case_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_case_code UNIQUE(case_code)
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_case_cust' AND object_id=OBJECT_ID(N'dbo.crm360_reception_cases'))
CREATE INDEX IX_crm360_case_cust ON dbo.crm360_reception_cases(customer_profile_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_case_status' AND object_id=OBJECT_ID(N'dbo.crm360_reception_cases'))
CREATE INDEX IX_crm360_case_status ON dbo.crm360_reception_cases(case_status);
GO

IF OBJECT_ID(N'dbo.crm360_case_documents',N'U') IS NULL
CREATE TABLE dbo.crm360_case_documents (
  document_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  case_id INT NOT NULL,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  document_type NVARCHAR(40) NOT NULL,
  document_title NVARCHAR(200) NOT NULL,
  document_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_doc_st DEFAULT(N'MISSING'),
  vault_blob_id INT NULL,
  file_ref_text NVARCHAR(400) NULL,
  expiry_date DATE NULL,
  signed_at DATETIME2 NULL,
  verified_by NVARCHAR(80) NULL,
  verified_at DATETIME2 NULL,
  notes NVARCHAR(400) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_doc_created DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_doc_case' AND object_id=OBJECT_ID(N'dbo.crm360_case_documents'))
CREATE INDEX IX_crm360_doc_case ON dbo.crm360_case_documents(case_id);
GO

IF OBJECT_ID(N'dbo.crm360_customer_cartable',N'U') IS NULL
CREATE TABLE dbo.crm360_customer_cartable (
  cartable_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  case_id INT NULL,
  item_type NVARCHAR(40) NOT NULL,
  item_title NVARCHAR(300) NOT NULL,
  item_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_cart_st DEFAULT(N'OPEN'),
  due_date DATE NULL,
  priority_code NVARCHAR(16) NOT NULL CONSTRAINT DF_crm360_cart_pri DEFAULT(N'NORMAL'),
  assigned_to NVARCHAR(80) NULL,
  source_type NVARCHAR(40) NULL,
  source_ref_text NVARCHAR(120) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cart_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cart_updated DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_cart_cust' AND object_id=OBJECT_ID(N'dbo.crm360_customer_cartable'))
CREATE INDEX IX_crm360_cart_cust ON dbo.crm360_customer_cartable(customer_profile_id);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_cart_due' AND object_id=OBJECT_ID(N'dbo.crm360_customer_cartable'))
CREATE INDEX IX_crm360_cart_due ON dbo.crm360_customer_cartable(due_date, item_status);
GO

IF OBJECT_ID(N'dbo.crm360_satisfaction_surveys',N'U') IS NULL
CREATE TABLE dbo.crm360_satisfaction_surveys (
  survey_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  case_id INT NULL,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  survey_channel NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_sat_ch DEFAULT(N'MANUAL'),
  overall_score INT NOT NULL CONSTRAINT DF_crm360_sat_ov DEFAULT(0),
  nps_score INT NULL,
  reception_score INT NULL,
  technical_score INT NULL,
  timing_score INT NULL,
  price_score INT NULL,
  cleanliness_score INT NULL,
  communication_score INT NULL,
  comment NVARCHAR(MAX) NULL,
  survey_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_sat_st DEFAULT(N'DRAFT'),
  submitted_at DATETIME2 NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_sat_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.crm360_complaints',N'U') IS NULL
CREATE TABLE dbo.crm360_complaints (
  complaint_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  complaint_code NVARCHAR(40) NOT NULL,
  case_id INT NULL,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  complaint_type NVARCHAR(40) NOT NULL,
  severity NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_cmp_sev DEFAULT(N'MEDIUM'),
  title NVARCHAR(300) NOT NULL,
  description NVARCHAR(MAX) NOT NULL,
  root_cause NVARCHAR(MAX) NULL,
  correction_action NVARCHAR(MAX) NULL,
  corrective_owner NVARCHAR(80) NULL,
  due_date DATE NULL,
  complaint_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_cmp_st DEFAULT(N'OPEN'),
  customer_notified BIT NOT NULL CONSTRAINT DF_crm360_cmp_notif DEFAULT(0),
  closed_at DATETIME2 NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cmp_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_cmp_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_complaint_code UNIQUE(complaint_code)
);
GO

IF OBJECT_ID(N'dbo.crm360_customer_club',N'U') IS NULL
CREATE TABLE dbo.crm360_customer_club (
  club_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  tier_code NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_club_tier DEFAULT(N'NEW'),
  points_balance INT NOT NULL CONSTRAINT DF_crm360_club_pts DEFAULT(0),
  visit_count INT NOT NULL CONSTRAINT DF_crm360_club_vis DEFAULT(0),
  last_visit_date DATE NULL,
  total_revenue_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_crm360_club_rev DEFAULT(0),
  total_paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_crm360_club_paid DEFAULT(0),
  total_discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_crm360_club_disc DEFAULT(0),
  churn_risk_level NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_club_churn DEFAULT(N'LOW'),
  last_activity_date DATE NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_club_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_club_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_club_cust UNIQUE(customer_profile_id)
);
GO

IF OBJECT_ID(N'dbo.crm360_service_reminders',N'U') IS NULL
CREATE TABLE dbo.crm360_service_reminders (
  reminder_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NOT NULL,
  case_id INT NULL,
  reminder_type NVARCHAR(40) NOT NULL,
  reminder_title NVARCHAR(300) NOT NULL,
  due_date DATE NULL,
  due_km INT NULL,
  current_km INT NULL,
  reminder_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_rem_st DEFAULT(N'DRAFT'),
  preferred_channel NVARCHAR(40) NULL,
  message_template NVARCHAR(MAX) NULL,
  last_contacted_at DATETIME2 NULL,
  next_followup_date DATE NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_rem_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_rem_updated DEFAULT(SYSUTCDATETIME())
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name=N'IX_crm360_rem_due' AND object_id=OBJECT_ID(N'dbo.crm360_service_reminders'))
CREATE INDEX IX_crm360_rem_due ON dbo.crm360_service_reminders(due_date, reminder_status);
GO

IF OBJECT_ID(N'dbo.crm360_return_pipeline',N'U') IS NULL
CREATE TABLE dbo.crm360_return_pipeline (
  return_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  reason_code NVARCHAR(40) NOT NULL,
  return_stage NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_ret_st DEFAULT(N'IDENTIFIED'),
  last_visit_date DATE NULL,
  last_contact_date DATE NULL,
  next_action_date DATE NULL,
  assigned_to NVARCHAR(80) NULL,
  offer_ref_text NVARCHAR(120) NULL,
  result_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_ret_res DEFAULT(N'OPEN'),
  notes NVARCHAR(MAX) NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_ret_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_ret_updated DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.crm360_promotions',N'U') IS NULL
CREATE TABLE dbo.crm360_promotions (
  promotion_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  promotion_code NVARCHAR(40) NOT NULL,
  title NVARCHAR(300) NOT NULL,
  description NVARCHAR(MAX) NULL,
  promotion_type NVARCHAR(40) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  target_segment NVARCHAR(80) NULL,
  discount_type NVARCHAR(40) NULL,
  discount_value DECIMAL(18,2) NULL,
  max_usage INT NULL,
  requires_approval BIT NOT NULL CONSTRAINT DF_crm360_promo_appr DEFAULT(0),
  promotion_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_promo_st DEFAULT(N'DRAFT'),
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_promo_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_promo_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_promo_code UNIQUE(promotion_code)
);
GO

IF OBJECT_ID(N'dbo.crm360_promotion_assignments',N'U') IS NULL
CREATE TABLE dbo.crm360_promotion_assignments (
  assignment_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  promotion_id INT NOT NULL,
  customer_profile_id INT NOT NULL,
  vehicle_profile_id INT NULL,
  case_id INT NULL,
  assignment_status NVARCHAR(32) NOT NULL CONSTRAINT DF_crm360_pa_st DEFAULT(N'ASSIGNED'),
  used_at DATETIME2 NULL,
  notes NVARCHAR(400) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_pa_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.crm360_sms_campaigns',N'U') IS NULL
CREATE TABLE dbo.crm360_sms_campaigns (
  campaign_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  campaign_code NVARCHAR(40) NOT NULL,
  title NVARCHAR(300) NOT NULL,
  target_segment NVARCHAR(80) NULL,
  message_text NVARCHAR(MAX) NOT NULL,
  send_mode NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_sms_mode DEFAULT(N'DRAFT_ONLY'),
  campaign_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_sms_st DEFAULT(N'DRAFT'),
  scheduled_at DATETIME2 NULL,
  created_by NVARCHAR(80) NOT NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_sms_created DEFAULT(SYSUTCDATETIME()),
  updated_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_sms_updated DEFAULT(SYSUTCDATETIME()),
  CONSTRAINT UQ_crm360_sms_code UNIQUE(campaign_code)
);
GO

IF OBJECT_ID(N'dbo.crm360_sms_campaign_recipients',N'U') IS NULL
CREATE TABLE dbo.crm360_sms_campaign_recipients (
  recipient_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  campaign_id INT NOT NULL,
  customer_profile_id INT NOT NULL,
  mobile NVARCHAR(40) NOT NULL,
  consent_status NVARCHAR(20) NOT NULL CONSTRAINT DF_crm360_smsr_cons DEFAULT(N'UNKNOWN'),
  recipient_status NVARCHAR(40) NOT NULL CONSTRAINT DF_crm360_smsr_st DEFAULT(N'QUEUED'),
  export_batch_no NVARCHAR(40) NULL,
  sent_manual_at DATETIME2 NULL,
  delivery_status NVARCHAR(40) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT DF_crm360_smsr_created DEFAULT(SYSUTCDATETIME())
);
GO

IF OBJECT_ID(N'dbo.crm360_audit_log',N'U') IS NULL
CREATE TABLE dbo.crm360_audit_log (
  audit_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
  event_time DATETIME2 NOT NULL CONSTRAINT DF_crm360_audit_time DEFAULT(SYSUTCDATETIME()),
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
