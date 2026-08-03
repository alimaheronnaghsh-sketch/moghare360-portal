# SQL Migration Order — moghare360_ERP

**Phase:** Phase 7 Production Preparation  
**Database:** `moghare360_ERP` only (single writable app DB)  
**Host:** SQL Server on Windows VPS (not XAMPP/MySQL as Production)

> Run migrations from an admin workstation or server CLI/SSMS.  
> **Never** expose migration scripts as anonymous HTTP endpoints.  
> **No secrets** in this file — use `PLACEHOLDER_*` for instance/login names.

---

## Hard rules

| Rule | Detail |
|------|--------|
| Target DB | `moghare360_ERP` only |
| Dual-write | Forbidden |
| DROP / TRUNCATE / blanket DELETE | Forbidden without written Owner + DBA approval |
| Backup | Mandatory verified backup before any production migration |
| Idempotency | Prefer idempotent scripts; stop on first failure |
| Rollback | Restore from backup — see `docs/release/v1/PRODUCTION_ROLLBACK_FA.md` |

Pre-check:

- Instance: PLACEHOLDER_SQL_INSTANCE
- Operator: PLACEHOLDER_DBA_OPERATOR
- Backup ID: PLACEHOLDER_DB_BACKUP_ID
- Maintenance window: PLACEHOLDER_MAINTENANCE_WINDOW

---

## A. Core foundation (v0) — if building a fresh DB

Execute in order under `public_html/sql/sqlserver/` (or the release package equivalent):

1. `core_v0_01_create_database.sql` — only on empty/new servers; confirm DB name remains `moghare360_ERP`
2. `core_v0_02_master_tables.sql`
3. `core_v0_03_workflow_tables.sql`
4. `core_v0_04_history_audit_tables.sql`
5. `core_v0_05_seed_org.sql`
6. `core_v0_06_seed_roles_permissions.sql`
7. `core_v0_07_seed_approval_rules.sql`

> Prefer `core_v0_08_run_all.sql` **only** when it is the approved umbrella for your package and still targets `moghare360_ERP`. Do not mix umbrella + individual re-runs blindly.

---

## B. Phase system scripts (legacy phase pack)

Canonical order (aligns with `docs/deployment/MOGHARE360_DATABASE_MIGRATION_PLAN.md`):

1. `phase_1_customer_core_system.sql`
2. `phase_2_operation_engine.sql`
3. `phase_3_rule_engine.sql`
4. `phase_4_inventory_purchase_system.sql`
5. `phase_5_financial_system.sql`
6. `phase_6_crm_system.sql`
7. `phase_7_hr_internal_admin.sql`
8. `phase_9_business_ready_system.sql`
9. `phase_10_commercial_system.sql`
10. `phase_12_soft_run_pilot.sql`

Phases without SQL in that pack: follow product docs (static/read-only layers).

---

## C. Mission / foundation increments (when present in package)

Apply only scripts that exist in the **release artifact** you are deploying, after sections A–B (or after confirming schema watermark). Typical numeric order:

1. `mission_15_customer_vehicle_foundation.sql`
2. `mission_17_jobcard_foundation.sql`
3. `mission_20_service_operation_foundation.sql`
4. `mission_22_parts_inventory_foundation.sql`
5. `mission_24_jobcard_part_usage.sql`
6. `mission_26_purchase_request_foundation.sql`
7. `mission_28_payment_foundation.sql`
8. `mission_30_qc_delivery_foundation.sql`

If additional `mission_*.sql` files exist, sort by mission number ascending and record the exact list in `DEPLOYMENT_MANIFEST_TEMPLATE.md`.

---

## D. V1 operational / RC SQL (when present)

If the release includes P-series scripts (names may vary by package), apply in numeric product order, for example:

1. `P1_online_request_intake.sql`
2. `P1_5_intake_contract_signature.sql`
3. `P2_reception_jobcard_workflow.sql`
4. `P3_technical_operation_workflow.sql`
5. `P4_estimate_approval_parts_finance_gate.sql`
6. `P5_work_execution_parts_consumption.sql`
7. `P6_qc_final_inspection_delivery_readiness.sql`
8. `P7_final_invoice_settlement_customer_delivery.sql`
9. `P8_management_dashboard_owner_control.sql`
10. `P9_end_to_end_soft_run.sql`
11. `P10_release_hardening_navigation_rc.sql`

Skip missing files; do not invent substitutes. Record applied set as PLACEHOLDER_MIGRATION_SCRIPT_LIST.

---

## E. Tooling migrations (CLI only)

PHP migrate tools under `tools/*migrate*.php` (if used) must:

- Refuse any database name other than `moghare360_ERP`
- Run via CLI on the server/admin host — **not** via public URL
- Follow the same backup-first rule

Example invocation pattern (placeholders only):

```powershell
php PLACEHOLDER_REPO_PATH\tools\PLACEHOLDER_MIGRATE_SCRIPT.php
```

---

## F. Post-migration checks

- [ ] App connects to `moghare360_ERP` only
- [ ] Smoke / health scripts PASS
- [ ] No credential leakage in SQL logs
- [ ] Manifest updated: PLACEHOLDER_SCHEMA_VERSION

On failure: stop → `docs/release/v1/PRODUCTION_ROLLBACK_FA.md`.

---

## References

- `docs/deployment/MOGHARE360_DATABASE_MIGRATION_PLAN.md`
- `docs/release/v1/DEPLOYMENT_RUNBOOK_FA.md`
- `docs/release/v1/PRODUCTION_BACKUP_CHECKLIST_FA.md`
