# MOGHARE360 Database Migration Plan

## Scope

Planning only — **no migration executed in Phase 14**.

## SQL File Order

Execute in SSMS against `moghare360_ERP` in this order:

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

Phases 11, 12.5, 13, 14 — no SQL required (static/read-only layers).

## Idempotent Migration Rule

- All scripts must be idempotent where possible
- **No DROP** without explicit approval
- **No destructive ALTER** without approval
- **No table rename** in production without freeze window

## Pre-Check

- Verify DB connectivity
- Confirm backup completed and verified
- Review forbidden file boundaries unchanged

## Post-Check

- Run phase test tools
- Smoke test critical write routes (manual)
- Verify no credential exposure in logs

## Rollback Note

If migration fails, restore from pre-migration backup — see `MOGHARE360_ROLLBACK_PLAN.md`.

## Governance

| Item | Owner |
|------|-------|
| Migration execution | DBA + technical lead |
| Database freeze window | Owner approval |
| Production approval gate | Required before production migration |
