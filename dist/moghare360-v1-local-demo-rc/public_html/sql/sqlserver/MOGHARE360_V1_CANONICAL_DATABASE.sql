/*
================================================================================
MOGHARE360 V1 — Canonical SQL Server Database Bundle
File: MOGHARE360_V1_CANONICAL_DATABASE.sql

PURPOSE:
  Single authoritative apply path for MOGHARE360 V1 SaaS on SQL Server.
  Idempotent. No DROP. No TRUNCATE. No MySQL. No credentials.

ACTIVE DATABASE:
  moghare360_ERP (or target selected in sqlcmd -d)

RUN:
  sqlcmd -S .\SQLEXPRESS -d moghare360_ERP -E -i MOGHARE360_V1_CANONICAL_DATABASE.sql

LEGACY (DO NOT RUN for V1 SaaS):
  public_html/sql/*.sql with MySQL syntax (CREATE TABLE IF NOT EXISTS)
  public_html/sql/patch_*.sql
  public_html/sql/erp_jobcard_workflow_v1.sql
  Codex ZIP MySQL portal tables (portal_customers_staging, etc.)

INVENTORY SUMMARY:
  Mission SQL (M15-M30 foundation)     — mission_*.sql
  Phase SQL (business layers)          — phase_*.sql
  SaaS V1                              — v1_saas_activation_foundation.sql
  Post-run control                     — v1_post_run_fix_register.sql
  Canonical extensions                 — v1_canonical_extensions.sql
================================================================================
*/

SET NOCOUNT ON;
PRINT N'=== MOGHARE360 V1 Canonical Database Apply START ===';
GO

/* --- Mission ERP Core Chain (M15-M30) --- */
:r mission_15_customer_vehicle_foundation.sql
GO
:r mission_17_jobcard_foundation.sql
GO
:r mission_20_service_operation_foundation.sql
GO
:r mission_22_parts_inventory_foundation.sql
GO
:r mission_24_jobcard_part_usage.sql
GO
:r mission_26_purchase_request_foundation.sql
GO
:r mission_28_payment_foundation.sql
GO
:r mission_30_qc_delivery_foundation.sql
GO

/* --- Phase Business Layers (idempotent) --- */
:r phase_1_customer_core_system.sql
GO
:r phase_2_operation_engine.sql
GO
:r phase_3_rule_engine.sql
GO
:r phase_4_inventory_purchase_system.sql
GO
:r phase_5_financial_system.sql
GO
:r phase_6_crm_system.sql
GO
:r phase_7_hr_internal_admin.sql
GO
:r phase_9_business_ready_system.sql
GO
:r phase_10_commercial_system.sql
GO
:r phase_12_soft_run_pilot.sql
GO

/* --- V1 SaaS + Website/API + Post-Run --- */
:r v1_saas_activation_foundation.sql
GO
:r v1_post_run_fix_register.sql
GO
:r v1_canonical_extensions.sql
GO

PRINT N'=== MOGHARE360 V1 Canonical Database Apply COMPLETE ===';
GO
