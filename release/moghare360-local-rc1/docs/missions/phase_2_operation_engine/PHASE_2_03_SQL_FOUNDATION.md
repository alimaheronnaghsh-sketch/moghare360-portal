# PHASE 2 — SQL Foundation

## File

`public_html/sql/sqlserver/phase_2_operation_engine.sql`

## Tables

| Table | Purpose |
|-------|---------|
| `dbo.erp_operation_cases` | Master operation case with stage/status |
| `dbo.erp_operation_service_steps` | Service/technician steps |
| `dbo.erp_operation_qc_decisions` | QC pass/fail/hold decisions |
| `dbo.erp_operation_delivery_checks` | Delivery readiness checks |
| `dbo.erp_operation_history` | Action history |

## Rules

- Idempotent, non-destructive
- No DROP, RENAME, or USE database
- No FK to legacy tables (safe extension)
- Non-unique indexes for lookup fields

## Execution

1. SSMS → connect to `moghare360_ERP`
2. Open `phase_2_operation_engine.sql`
3. Execute (F5)
4. Confirm: `Phase 2 Operation Engine SQL completed.`

Run **after** Phase 1 SQL if Customer Core linking is needed.
