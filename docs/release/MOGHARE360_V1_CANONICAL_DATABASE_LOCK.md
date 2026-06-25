# MOGHARE360 V1 — Canonical Database Lock

## Purpose

MOGHARE360 V1 SaaS runs exclusively on **SQL Server**. Legacy Codex MySQL portal SQL and runtime (`config.php`, `submit-customer.php`, `submit-service-request.php`, `admin-pending.php`) are **reference-only** and must not be activated.

This document locks the single apply path and verification path for the V1 database layer.

## Active database

| Setting | Value |
|---------|--------|
| Engine | Microsoft SQL Server |
| Default database name | `moghare360_ERP` |
| Config source | `private/erp-config.php` (not committed) |
| Connection | ODBC via `mogh_tenant_db_connect()` |

Read the live name from config; installer parameter `-DatabaseName` must match.

## Why MySQL legacy was removed from runtime

- Codex ZIP used cPanel MySQL staging tables (`portal_customers_staging`, etc.).
- V1 ERP missions built the authoritative schema on SQL Server (`erp_*` tables).
- Mirror website and API now write only to `erp_customer_online_requests` on SQL Server.
- Keeping MySQL paths active would split customer intake across two databases.

## Canonical SQL (run these)

| File | Role |
|------|------|
| `public_html/sql/sqlserver/MOGHARE360_V1_CANONICAL_DATABASE.sql` | **Single orchestrator** — missions, phases, SaaS, post-run, extensions |
| `public_html/sql/sqlserver/MOGHARE360_V1_DATABASE_VERIFY.sql` | Read-only verification + row-count report |
| `public_html/sql/sqlserver/v1_canonical_extensions.sql` | Additive columns (`request_payload_json`, `request_type`) |

### Orchestrator includes (in order)

**Mission foundation (M15–M30):**

- `mission_15_customer_vehicle_foundation.sql`
- `mission_17_jobcard_foundation.sql`
- `mission_20_service_operation_foundation.sql`
- `mission_22_parts_inventory_foundation.sql`
- `mission_24_jobcard_part_usage.sql`
- `mission_26_purchase_request_foundation.sql`
- `mission_28_payment_foundation.sql`
- `mission_30_qc_delivery_foundation.sql`

**Phase layers:**

- `phase_1_customer_core_system.sql` … `phase_12_soft_run_pilot.sql` (phases 1–7, 9, 10, 12)

**V1 SaaS / API / post-run:**

- `v1_saas_activation_foundation.sql`
- `v1_post_run_fix_register.sql`
- `v1_canonical_extensions.sql`

## Legacy / reference SQL (do NOT run for V1 SaaS)

| Location | Notes |
|----------|--------|
| `public_html/sql/*.sql` | MySQL syntax (`CREATE TABLE IF NOT EXISTS`) — Codex-era |
| `public_html/sql/patch_*.sql` | MySQL patches |
| `public_html/sql/erp_jobcard_workflow_v1.sql` | MySQL workflow prototype |
| `public_html/sql/MOTHER_RUN_ALL_IN_ORDER.sql` | MySQL mother script |
| `public_html/sql/wave_*.sql` | Wave logs (SQL Server fragments; included in phases where needed) |
| Codex ZIP extracts | Reference only under `tools/_legacy_codex_review/` |

Duplicate copies under `release/*` packages mirror repo SQL for packaging; **canonical source is `public_html/sql/sqlserver/`**.

## Core V1 tables (minimum lock set)

### ERP core

- `erp_customers`, `erp_customer_phones`, `erp_vehicles`, `erp_customer_vehicle_relations`
- `erp_jobcards`, `erp_service_operations`, `erp_jobcard_part_usage`
- `erp_purchase_requests`, `erp_payments`, `erp_qc_checks`, `erp_delivery_controls`

### History / audit

- `erp_*_history` tables created by mission scripts (customer, vehicle, jobcard, etc.)
- `erp_api_request_log`, `erp_mirror_requests`

### SaaS

| Canonical name | Alternate (not used) |
|----------------|----------------------|
| `erp_companies` | `saas_tenants` |
| `erp_company_domains` | `saas_tenant_domains` |
| `erp_company_users` | — |
| `erp_saas_storage_objects` | — |
| `erp_deployment_health_checks` | — |

Default tenant: `company_code = MOGHAREH_MAIN`

### Website / mirror / API

- `erp_customer_online_requests` (+ `request_payload_json`, `request_type` via extensions)
- `erp_user_access_requests`

### Post-run control

- `erp_v1_production_run_signoff`
- `erp_v1_post_run_fix_register`

## Application wiring

| Component | Database target |
|-----------|-----------------|
| `public_html/api/customer/request.php` | SQL Server `erp_customer_online_requests` |
| Mirror `customer-request.php` | POST → Master `/api/customer/request.php` only |
| Installer `INSTALL_MOGHARE360_V1.ps1` | Applies canonical SQL + sqlcmd verify |
| Auto deploy `AUTO_DEPLOY_MOGHARE360_V1.ps1` | Installer + smoke + `test-v1-canonical-database.php` |

## How to apply

```powershell
cd public_html\sql\sqlserver
sqlcmd -S .\SQLEXPRESS -d moghare360_ERP -E -i MOGHARE360_V1_CANONICAL_DATABASE.sql
sqlcmd -S .\SQLEXPRESS -d moghare360_ERP -E -i MOGHARE360_V1_DATABASE_VERIFY.sql
```

Or use production installer (creates backup report first):

```powershell
.\tools\production\INSTALL_MOGHARE360_V1.ps1 -InstallPath C:\xampp\htdocs\moghare360
```

## How to verify

**SQL:**

```powershell
sqlcmd -S .\SQLEXPRESS -d moghare360_ERP -E -i public_html\sql\sqlserver\MOGHARE360_V1_DATABASE_VERIFY.sql
```

**PHP:**

```powershell
C:\xampp\php\php.exe tools\test-v1-canonical-database.php
C:\xampp\php\php.exe tools\test-v1-production-run-smoke.php
C:\xampp\php\php.exe tools\test-v1-production-signoff.php
C:\xampp\php\php.exe tools\test-legacy-codex-site-review.php
```

## Prohibitions

- No `DROP TABLE`, `TRUNCATE`, or destructive cleanup without explicit operational approval and confirmed real data backup.
- No activation of legacy MySQL `config.php` or submit scripts.
- No credentials or real customer data in SQL files or Git.
- No duplicate table creation when an `erp_*` table already exists — reuse and document mapping.

## Data posture (current)

User confirmed no real production data yet. Verification reports test/seed row counts (`TEST_V1_RUN_DO_NOT_USE`, pilot seeds). If real customer/jobcard/payment data appears later, use **additive migrations only** and report before any cleanup.

---

**Lock statement:** MOGHARE360 V1 database layer is consolidated on the canonical SQL Server SaaS schema, and the legacy Codex MySQL site remains reference-only.
