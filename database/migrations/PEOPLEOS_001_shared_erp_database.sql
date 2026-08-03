/* PEOPLEOS_001 — PeopleOS360 tables inside moghare360_ERP (p360_ prefix). Additive/idempotent. */
SET NOCOUNT ON; SET XACT_ABORT ON; SET QUOTED_IDENTIFIER ON; SET ANSI_NULLS ON;
IF DB_NAME() <> N'moghare360_ERP' THROW 58001, 'PEOPLEOS_001 must run on moghare360_ERP only.', 1;
BEGIN TRY BEGIN TRANSACTION;
IF OBJECT_ID(N'dbo.p360_users',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_users (user_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, username NVARCHAR(50) NOT NULL, display_name NVARCHAR(120) NULL, password_hash NVARCHAR(255) NOT NULL, role_code NVARCHAR(40) NOT NULL CONSTRAINT DF_p360_users_role DEFAULT (N'OWNER_ADMIN'), is_active BIT NOT NULL CONSTRAINT DF_p360_users_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_users_c DEFAULT (SYSUTCDATETIME()), CONSTRAINT UQ_p360_users_u UNIQUE (username)); END;

IF OBJECT_ID(N'dbo.p360_companies',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_companies (company_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, company_code NVARCHAR(40) NOT NULL, company_name NVARCHAR(200) NOT NULL, legal_name NVARCHAR(200) NULL, tax_id NVARCHAR(50) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_co_a DEFAULT (1), created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_co_c DEFAULT (SYSUTCDATETIME()), CONSTRAINT UQ_p360_co_code UNIQUE (company_code)); END;

IF OBJECT_ID(N'dbo.p360_legal_entities',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_legal_entities (legal_entity_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, company_id INT NULL, entity_code NVARCHAR(40) NOT NULL, entity_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_le_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_le_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_branches',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_branches (branch_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, company_id INT NULL, branch_code NVARCHAR(40) NOT NULL, branch_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_br_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_br_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_sites',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_sites (site_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, branch_id INT NULL, site_code NVARCHAR(40) NOT NULL, site_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_si_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_si_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_departments',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_departments (department_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, company_id INT NULL, department_code NVARCHAR(40) NOT NULL, department_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_dp_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_dp_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_teams',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_teams (team_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, department_id INT NULL, team_code NVARCHAR(40) NOT NULL, team_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_tm_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_tm_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_positions',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_positions (position_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, department_id INT NULL, position_code NVARCHAR(40) NOT NULL, position_title NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_po_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_po_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_cost_centers',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_cost_centers (cost_center_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, company_id INT NULL, cost_center_code NVARCHAR(40) NOT NULL, cost_center_name NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_cc_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_cc_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_legal_rule_categories',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_legal_rule_categories (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, category_code NVARCHAR(40) NOT NULL, category_name NVARCHAR(200) NOT NULL, description NVARCHAR(500) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_lrc_a DEFAULT (1), CONSTRAINT UQ_p360_lrc UNIQUE (category_code)); END;

IF OBJECT_ID(N'dbo.p360_legal_rules',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_legal_rules (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, category_id INT NULL, rule_code NVARCHAR(60) NOT NULL, rule_title NVARCHAR(200) NOT NULL, rule_text NVARCHAR(MAX) NULL, rule_value_json NVARCHAR(MAX) NULL, law_year INT NULL, effective_from DATE NOT NULL, effective_to DATE NULL, source_title NVARCHAR(200) NULL, source_reference NVARCHAR(200) NULL, approved_by INT NULL, approved_at DATETIME2 NULL, status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_lr_s DEFAULT (N'draft'), version_no INT NOT NULL CONSTRAINT DF_p360_lr_v DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_lr_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employment_types',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employment_types (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, type_code NVARCHAR(40) NOT NULL, type_name NVARCHAR(120) NOT NULL, relation_type NVARCHAR(40) NOT NULL, legal_warning NVARCHAR(500) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_et_a DEFAULT (1), CONSTRAINT UQ_p360_et UNIQUE (type_code)); END;

IF OBJECT_ID(N'dbo.p360_contract_types',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_contract_types (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, type_code NVARCHAR(40) NOT NULL, type_name NVARCHAR(120) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_ct_a DEFAULT (1), CONSTRAINT UQ_p360_ct UNIQUE (type_code)); END;

IF OBJECT_ID(N'dbo.p360_contract_templates',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_contract_templates (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, template_code NVARCHAR(40) NOT NULL, template_title NVARCHAR(200) NOT NULL, employment_type_id INT NULL, contract_type_id INT NULL, company_id INT NULL, template_body NVARCHAR(MAX) NOT NULL, variables_json NVARCHAR(MAX) NULL, effective_from DATE NOT NULL, effective_to DATE NULL, version_no INT NOT NULL CONSTRAINT DF_p360_tpl_v DEFAULT (1), status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_tpl_s DEFAULT (N'draft'), confidentiality_level NVARCHAR(40) NULL, created_by INT NULL, approved_by INT NULL, approved_at DATETIME2 NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_tpl_c DEFAULT (SYSUTCDATETIME()), CONSTRAINT UQ_p360_tpl UNIQUE (template_code)); END;

IF OBJECT_ID(N'dbo.p360_contract_template_versions',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_contract_template_versions (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, template_id INT NOT NULL, version_no INT NOT NULL, template_body_snapshot NVARCHAR(MAX) NOT NULL, variables_json_snapshot NVARCHAR(MAX) NULL, change_reason NVARCHAR(500) NULL, created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_tpv_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employees',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employees (employee_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_code NVARCHAR(40) NOT NULL, first_name NVARCHAR(80) NOT NULL, last_name NVARCHAR(80) NOT NULL, national_code NVARCHAR(20) NULL, mobile NVARCHAR(30) NULL, email NVARCHAR(120) NULL, company_id INT NULL, branch_id INT NULL, department_id INT NULL, position_id INT NULL, employment_type_id INT NULL, hire_date DATE NULL, employee_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_emp_s DEFAULT (N'active'), is_active BIT NOT NULL CONSTRAINT DF_p360_emp_a DEFAULT (1), created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_emp_c DEFAULT (SYSUTCDATETIME()), CONSTRAINT UQ_p360_emp_code UNIQUE (employee_code)); END;

IF OBJECT_ID(N'dbo.p360_employee_family',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_family (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, relation_name NVARCHAR(80) NULL, full_name NVARCHAR(160) NULL, national_code NVARCHAR(20) NULL); END;

IF OBJECT_ID(N'dbo.p360_employee_education',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_education (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, degree_title NVARCHAR(120) NULL, field_name NVARCHAR(120) NULL, institute_name NVARCHAR(200) NULL); END;

IF OBJECT_ID(N'dbo.p360_employee_work_history',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_work_history (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, employer_name NVARCHAR(200) NULL, position_title NVARCHAR(120) NULL, from_date DATE NULL, to_date DATE NULL); END;

IF OBJECT_ID(N'dbo.p360_employee_skills',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_skills (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, skill_name NVARCHAR(120) NOT NULL, skill_level NVARCHAR(40) NULL); END;

IF OBJECT_ID(N'dbo.p360_employee_documents',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_documents (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, doc_title NVARCHAR(200) NOT NULL, doc_type NVARCHAR(60) NULL, file_ref NVARCHAR(300) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ed_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employee_position_history',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_position_history (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, position_id INT NULL, position_title NVARCHAR(200) NULL, department_id INT NULL, effective_from DATE NOT NULL, effective_to DATE NULL, change_reason NVARCHAR(300) NULL, created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_eph_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employee_salary_history',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_salary_history (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, base_salary DECIMAL(18,2) NOT NULL, currency_code NVARCHAR(10) NOT NULL CONSTRAINT DF_p360_esh_c DEFAULT (N'IRR'), effective_from DATE NOT NULL, effective_to DATE NULL, change_reason NVARCHAR(300) NULL, created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_esh_cr DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employee_bank_history',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_bank_history (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, bank_name NVARCHAR(120) NULL, account_no NVARCHAR(60) NULL, iban NVARCHAR(40) NULL, effective_from DATE NOT NULL, effective_to DATE NULL); END;

IF OBJECT_ID(N'dbo.p360_employee_contracts',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_contracts (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, contract_type_id INT NULL, template_id INT NULL, contract_no NVARCHAR(40) NOT NULL, start_date DATE NOT NULL, end_date DATE NULL, contract_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ec_s DEFAULT (N'active'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ec_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employee_contract_snapshots',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_contract_snapshots (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, template_id INT NULL, contract_no NVARCHAR(40) NOT NULL, contract_body_snapshot NVARCHAR(MAX) NOT NULL, variables_snapshot_json NVARCHAR(MAX) NULL, start_date DATE NOT NULL, end_date DATE NULL, status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ecs_s DEFAULT (N'issued'), signed_at DATETIME2 NULL, acknowledged_at DATETIME2 NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ecs_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_contract_addendums',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_contract_addendums (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, contract_id INT NOT NULL, addendum_no NVARCHAR(40) NOT NULL, addendum_text NVARCHAR(MAX) NULL, effective_from DATE NOT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ca_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_labor_law_years',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_labor_law_years (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, law_year INT NOT NULL, title NVARCHAR(200) NOT NULL, effective_from DATE NOT NULL, effective_to DATE NULL, status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_lly_s DEFAULT (N'draft'), approved_by INT NULL, approved_at DATETIME2 NULL); END;

IF OBJECT_ID(N'dbo.p360_payroll_legal_components',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_payroll_legal_components (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, law_year_id INT NULL, component_code NVARCHAR(60) NOT NULL, component_title NVARCHAR(200) NOT NULL, component_type NVARCHAR(40) NOT NULL, calculation_method NVARCHAR(40) NOT NULL, amount DECIMAL(18,2) NULL, percent_value DECIMAL(9,4) NULL, formula_text NVARCHAR(500) NULL, taxable_flag BIT NOT NULL CONSTRAINT DF_p360_plc_t DEFAULT (0), insurance_subject_flag BIT NOT NULL CONSTRAINT DF_p360_plc_i DEFAULT (0), effective_from DATE NOT NULL, effective_to DATE NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_plc_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_leave_rule_definitions',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_leave_rule_definitions (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, rule_code NVARCHAR(40) NOT NULL, leave_type NVARCHAR(60) NOT NULL, title NVARCHAR(200) NOT NULL, entitlement_method NVARCHAR(60) NULL, entitlement_minutes INT NULL, requires_document BIT NOT NULL CONSTRAINT DF_p360_lrd_d DEFAULT (0), requires_approval BIT NOT NULL CONSTRAINT DF_p360_lrd_a DEFAULT (1), effective_from DATE NOT NULL, effective_to DATE NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_lrd_ia DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_jalali_calendar_years',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_jalali_calendar_years (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, jalali_year INT NOT NULL, starts_on_gregorian DATE NOT NULL, ends_on_gregorian DATE NOT NULL, status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_jcy_s DEFAULT (N'sample'), CONSTRAINT UQ_p360_jcy UNIQUE (jalali_year)); END;

IF OBJECT_ID(N'dbo.p360_jalali_calendar_days',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_jalali_calendar_days (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, calendar_year_id INT NOT NULL, jalali_date NVARCHAR(10) NOT NULL, gregorian_date DATE NOT NULL, weekday_name NVARCHAR(20) NULL, day_type NVARCHAR(40) NOT NULL, title NVARCHAR(200) NULL, required_minutes INT NOT NULL CONSTRAINT DF_p360_jcd_m DEFAULT (480), is_active BIT NOT NULL CONSTRAINT DF_p360_jcd_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_attendance_devices',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_attendance_devices (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, device_code NVARCHAR(40) NOT NULL, device_name NVARCHAR(120) NOT NULL, site_id INT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_ad_a DEFAULT (1), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ad_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_raw_attendance_logs',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_raw_attendance_logs (id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, device_id INT NULL, employee_id INT NULL, punch_at DATETIME2 NOT NULL, punch_type NVARCHAR(20) NULL, raw_payload NVARCHAR(500) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ral_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_attendance_records',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_attendance_records (id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, work_date DATE NOT NULL, in_at DATETIME2 NULL, out_at DATETIME2 NULL, worked_minutes INT NOT NULL CONSTRAINT DF_p360_ar_w DEFAULT (0), late_minutes INT NOT NULL CONSTRAINT DF_p360_ar_l DEFAULT (0), overtime_minutes INT NOT NULL CONSTRAINT DF_p360_ar_o DEFAULT (0), record_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ar_s DEFAULT (N'calculated')); END;

IF OBJECT_ID(N'dbo.p360_calendars',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_calendars (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, calendar_code NVARCHAR(40) NOT NULL, calendar_name NVARCHAR(120) NOT NULL, company_id INT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_cal_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_calendar_days',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_calendar_days (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, calendar_id INT NOT NULL, work_date DATE NOT NULL, day_type NVARCHAR(40) NOT NULL, title NVARCHAR(200) NULL, required_minutes INT NOT NULL CONSTRAINT DF_p360_cd_m DEFAULT (480)); END;

IF OBJECT_ID(N'dbo.p360_shifts',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_shifts (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, shift_code NVARCHAR(40) NOT NULL, shift_name NVARCHAR(120) NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, required_minutes INT NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_sh_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_employee_shift_assignments',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_shift_assignments (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, shift_id INT NOT NULL, effective_from DATE NOT NULL, effective_to DATE NULL); END;

IF OBJECT_ID(N'dbo.p360_rule_sets',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_rule_sets (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, rule_set_code NVARCHAR(40) NOT NULL, rule_set_name NVARCHAR(120) NOT NULL, rule_json NVARCHAR(MAX) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_rs_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_payroll_periods',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_payroll_periods (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, period_code NVARCHAR(40) NOT NULL, jalali_year INT NOT NULL, jalali_month INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, period_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_pp_s DEFAULT (N'open'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_pp_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_timesheets',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_timesheets (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, period_id INT NOT NULL, worked_minutes INT NOT NULL CONSTRAINT DF_p360_ts_w DEFAULT (0), overtime_minutes INT NOT NULL CONSTRAINT DF_p360_ts_o DEFAULT (0), timesheet_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ts_s DEFAULT (N'draft'), approved_by INT NULL, approved_at DATETIME2 NULL); END;

IF OBJECT_ID(N'dbo.p360_payroll_slips',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_payroll_slips (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, period_id INT NOT NULL, gross_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_p360_ps_g DEFAULT (0), deduction_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_p360_ps_d DEFAULT (0), net_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_p360_ps_n DEFAULT (0), slip_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ps_s DEFAULT (N'draft'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ps_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_payroll_items',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_payroll_items (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, slip_id INT NOT NULL, component_code NVARCHAR(60) NOT NULL, component_title NVARCHAR(200) NOT NULL, item_type NVARCHAR(40) NOT NULL, amount DECIMAL(18,2) NOT NULL); END;

IF OBJECT_ID(N'dbo.p360_manpower_requests',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_manpower_requests (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, request_no NVARCHAR(40) NOT NULL, department_id INT NULL, position_id INT NULL, headcount INT NOT NULL, reason_text NVARCHAR(500) NULL, request_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_mr_s DEFAULT (N'submitted'), created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_mr_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_vacancies',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_vacancies (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, vacancy_code NVARCHAR(40) NOT NULL, title NVARCHAR(200) NOT NULL, department_id INT NULL, position_id INT NULL, vacancy_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_vac_s DEFAULT (N'open'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_vac_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_candidates',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_candidates (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, vacancy_id INT NULL, full_name NVARCHAR(160) NOT NULL, mobile NVARCHAR(30) NULL, email NVARCHAR(120) NULL, candidate_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_can_s DEFAULT (N'new'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_can_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_candidate_interviews',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_candidate_interviews (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, candidate_id INT NOT NULL, interview_at DATETIME2 NULL, interviewer NVARCHAR(120) NULL, score DECIMAL(9,2) NULL, notes NVARCHAR(500) NULL); END;

IF OBJECT_ID(N'dbo.p360_job_offers',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_job_offers (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, candidate_id INT NOT NULL, offer_salary DECIMAL(18,2) NULL, offer_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_jo_s DEFAULT (N'draft'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_jo_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_onboarding_tasks',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_onboarding_tasks (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, task_title NVARCHAR(200) NOT NULL, task_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ot_s DEFAULT (N'pending')); END;

IF OBJECT_ID(N'dbo.p360_workflow_tasks',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_workflow_tasks (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, task_type NVARCHAR(60) NOT NULL, entity_type NVARCHAR(60) NOT NULL, entity_id NVARCHAR(60) NOT NULL, title NVARCHAR(200) NOT NULL, maker_user_id INT NULL, checker_user_id INT NULL, task_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_wt_s DEFAULT (N'pending'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_wt_c DEFAULT (SYSUTCDATETIME()), decided_at DATETIME2 NULL, decision_note NVARCHAR(500) NULL); END;

IF OBJECT_ID(N'dbo.p360_workflow_events',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_workflow_events (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, task_id INT NULL, event_name NVARCHAR(80) NOT NULL, actor_user_id INT NULL, event_note NVARCHAR(500) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_we_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_employee_requests',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_employee_requests (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, request_type NVARCHAR(60) NOT NULL, request_body NVARCHAR(1000) NULL, request_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_er_s DEFAULT (N'submitted'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_er_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_leave_balances',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_leave_balances (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, leave_type NVARCHAR(60) NOT NULL, balance_minutes INT NOT NULL CONSTRAINT DF_p360_lb_b DEFAULT (0)); END;

IF OBJECT_ID(N'dbo.p360_leave_requests',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_leave_requests (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, leave_type NVARCHAR(60) NOT NULL, from_at DATETIME2 NOT NULL, to_at DATETIME2 NOT NULL, minutes_count INT NOT NULL, reason_text NVARCHAR(500) NULL, request_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_lrq_s DEFAULT (N'submitted'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_lrq_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_mission_requests',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_mission_requests (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, destination NVARCHAR(200) NULL, from_at DATETIME2 NOT NULL, to_at DATETIME2 NOT NULL, reason_text NVARCHAR(500) NULL, request_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_mrq_s DEFAULT (N'submitted'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_mrq_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_overtime_requests',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_overtime_requests (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, work_date DATE NOT NULL, overtime_minutes INT NOT NULL, reason_text NVARCHAR(500) NULL, request_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_or_s DEFAULT (N'submitted'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_or_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_loan_types',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_loan_types (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, type_code NVARCHAR(40) NOT NULL, type_name NVARCHAR(120) NOT NULL, max_amount DECIMAL(18,2) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_lt_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_loans',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_loans (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, loan_type_id INT NULL, principal_amount DECIMAL(18,2) NOT NULL, installment_count INT NOT NULL, loan_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ln_s DEFAULT (N'submitted'), approved_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ln_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_loan_installments',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_loan_installments (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, loan_id INT NOT NULL, installment_no INT NOT NULL, due_date DATE NOT NULL, amount DECIMAL(18,2) NOT NULL, paid_flag BIT NOT NULL CONSTRAINT DF_p360_li_p DEFAULT (0), payroll_period_id INT NULL); END;

IF OBJECT_ID(N'dbo.p360_kpi_definitions',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_kpi_definitions (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, kpi_code NVARCHAR(40) NOT NULL, kpi_title NVARCHAR(200) NOT NULL, weight_pct DECIMAL(9,2) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_kpi_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_performance_reviews',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_performance_reviews (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, review_period NVARCHAR(40) NULL, total_score DECIMAL(9,2) NULL, review_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_pr_s DEFAULT (N'draft'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_pr_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_performance_review_items',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_performance_review_items (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, review_id INT NOT NULL, kpi_id INT NULL, score DECIMAL(9,2) NULL, note_text NVARCHAR(500) NULL); END;

IF OBJECT_ID(N'dbo.p360_training_courses',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_training_courses (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, course_code NVARCHAR(40) NOT NULL, course_title NVARCHAR(200) NOT NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_tc_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_training_records',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_training_records (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, course_id INT NULL, completed_at DATE NULL, score DECIMAL(9,2) NULL); END;

IF OBJECT_ID(N'dbo.p360_rewards',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_rewards (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, reward_title NVARCHAR(200) NOT NULL, reward_amount DECIMAL(18,2) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_rw_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_disciplinary_actions',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_disciplinary_actions (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, action_type NVARCHAR(40) NOT NULL, action_text NVARCHAR(1000) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_da_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_equipment',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_equipment (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, equipment_code NVARCHAR(40) NOT NULL, equipment_name NVARCHAR(200) NOT NULL, serial_no NVARCHAR(80) NULL, is_active BIT NOT NULL CONSTRAINT DF_p360_eq_a DEFAULT (1)); END;

IF OBJECT_ID(N'dbo.p360_equipment_assignments',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_equipment_assignments (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, equipment_id INT NOT NULL, employee_id INT NOT NULL, issued_at DATETIME2 NOT NULL, returned_at DATETIME2 NULL, assignment_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ea_s DEFAULT (N'issued')); END;

IF OBJECT_ID(N'dbo.p360_exit_cases',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_exit_cases (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, employee_id INT NOT NULL, exit_type NVARCHAR(40) NOT NULL, exit_date DATE NOT NULL, exit_status NVARCHAR(30) NOT NULL CONSTRAINT DF_p360_ex_s DEFAULT (N'open'), created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_ex_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_exit_clearance_items',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_exit_clearance_items (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, exit_case_id INT NOT NULL, item_title NVARCHAR(200) NOT NULL, cleared_flag BIT NOT NULL CONSTRAINT DF_p360_eci_c DEFAULT (0), cleared_at DATETIME2 NULL); END;

IF OBJECT_ID(N'dbo.p360_audit_log',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_audit_log (id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, entity_type NVARCHAR(60) NOT NULL, entity_id NVARCHAR(60) NOT NULL, event_name NVARCHAR(80) NOT NULL, event_note NVARCHAR(1000) NULL, actor_user_id INT NULL, actor_username NVARCHAR(50) NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_al_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_announcements',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_announcements (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, title NVARCHAR(200) NOT NULL, body_text NVARCHAR(MAX) NOT NULL, created_by INT NULL, created_at DATETIME2 NOT NULL CONSTRAINT DF_p360_an_c DEFAULT (SYSUTCDATETIME())); END;

IF OBJECT_ID(N'dbo.p360_acknowledgments',N'U') IS NULL BEGIN CREATE TABLE dbo.p360_acknowledgments (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, announcement_id INT NULL, employee_id INT NOT NULL, acknowledged_at DATETIME2 NULL); END;

IF NOT EXISTS (SELECT 1 FROM dbo.p360_legal_rule_categories)
BEGIN
 INSERT INTO dbo.p360_legal_rule_categories(category_code,category_name,description) VALUES
 (N'employment_relation',N'نوع همکاری',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'contract_types',N'انواع قرارداد',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'work_calendar',N'تقویم کاری',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'attendance',N'حضور و کارکرد',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'leave',N'مرخصی',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'payroll',N'حقوق و مزایا',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'insurance',N'تأمین اجتماعی',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'tax',N'مالیات',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'settlement',N'تسویه',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'disciplinary',N'انضباطی و اداری',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'documents',N'اسناد',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی');
END;
IF NOT EXISTS (SELECT 1 FROM dbo.p360_employment_types)
BEGIN
 INSERT INTO dbo.p360_employment_types(type_code,type_name,relation_type,legal_warning) VALUES
 (N'full_time',N'تمام وقت',N'full_time',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'part_time',N'پاره وقت',N'part_time',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'hourly',N'ساعتی',N'hourly',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'daily_wage',N'روزمزد',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'temporary',N'موقت',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'permanent',N'دائمی',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'contractual',N'پیمانی',N'project',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'project',N'پروژه‌ای',N'project',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'intern',N'کارآموز',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'probation',N'آزمایشی',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'remote',N'دورکاری',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'shift',N'شیفتی',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'mission',N'مأموریتی',N'employee',N'نمونه آزمایشی؛ نیازمند تأیید مدیر/مشاور حقوقی'),
 (N'consultant',N'مشاور',N'consultant',N'هشدار حقوقی: نوع غیرکارگری؛ نیازمند تأیید مشاور حقوقی'),
 (N'contractor',N'پیمانکار',N'contractor',N'هشدار حقوقی: رابطه پیمانکاری جدا از کارگر؛ نیازمند تأیید مشاور حقوقی');
END;
IF NOT EXISTS (SELECT 1 FROM dbo.p360_contract_types)
BEGIN
 INSERT INTO dbo.p360_contract_types(type_code,type_name) VALUES
 (N'temp_work',N'قرارداد کار موقت'),(N'perm_work',N'قرارداد کار دائم'),(N'determined',N'قرارداد کار معین'),
 (N'hourly_c',N'قرارداد ساعتی'),(N'part_c',N'قرارداد پاره‌وقت'),(N'probation_c',N'قرارداد آزمایشی'),
 (N'intern_c',N'قرارداد کارآموزی'),(N'project_c',N'قرارداد پیمانی / پروژه‌ای'),(N'addendum',N'الحاقیه قرارداد'),
 (N'renewal',N'تمدید قرارداد'),(N'termination',N'خاتمه قرارداد'),(N'settlement',N'تسویه');
END;
COMMIT TRANSACTION; END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK TRANSACTION; THROW; END CATCH;