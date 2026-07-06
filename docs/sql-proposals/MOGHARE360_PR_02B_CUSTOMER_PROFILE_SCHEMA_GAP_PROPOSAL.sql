/*
================================================================================
MOGHARE360 PR-02B — Customer Profile Schema Gap Proposal (NOT EXECUTED)
================================================================================
ENVIRONMENT: Proposal only — do NOT auto-run from PHP.
Owner decision lock requires vehicle_delivery_address and authorized_receiver
fields as first-class columns. Current erp_customers supports partial mapping.
================================================================================
*/

-- Gap: no dedicated columns for:
--   vehicle_delivery_address NVARCHAR(500)
--   authorized_receiver_name NVARCHAR(200)
--   authorized_receiver_phone NVARCHAR(30)
--   first_name / last_name (optional split; full_name used today)
--   UNIQUE(company_id, primary_mobile) for duplicate prevention

-- PROPOSED (future controlled migration — not part of PR-02B runtime):
/*
IF COL_LENGTH(N'dbo.erp_customers', N'vehicle_delivery_address') IS NULL
    ALTER TABLE dbo.erp_customers ADD vehicle_delivery_address NVARCHAR(500) NULL;

IF COL_LENGTH(N'dbo.erp_customers', N'authorized_receiver_name') IS NULL
    ALTER TABLE dbo.erp_customers ADD authorized_receiver_name NVARCHAR(200) NULL;

IF COL_LENGTH(N'dbo.erp_customers', N'authorized_receiver_phone') IS NULL
    ALTER TABLE dbo.erp_customers ADD authorized_receiver_phone NVARCHAR(30) NULL;
*/

-- PR-02B interim: supplemental fields stored in erp_customers.notes as JSON
-- prefix PR02B_EXT until columns exist.
