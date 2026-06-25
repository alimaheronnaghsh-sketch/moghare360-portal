# PHASE 1 — SQL Foundation

## File

`public_html/sql/sqlserver/phase_1_customer_core_system.sql`

## Tables

| Table | Purpose |
|-------|---------|
| `dbo.erp_customer_intakes` | Customer/lead intake records |
| `dbo.erp_customer_contracts` | Contract header and authorization rules |
| `dbo.erp_customer_contract_acceptances` | Internal controlled acceptance audit |
| `dbo.erp_customer_vehicle_bindings` | Customer–vehicle relationship |
| `dbo.erp_vehicle_photo_records` | Photo metadata placeholders |
| `dbo.erp_customer_core_history` | Cross-entity action history |

## Rules

- SQL Server 2022 compatible
- Idempotent: `IF OBJECT_ID ... IS NULL` for tables
- No `DROP`, no `RENAME`, no `USE` database statement
- Indexes via `IF NOT EXISTS` on `sys.indexes`
- Non-unique indexes for duplicate-check fields (PHP enforces business rules)
- No hard FKs to legacy tables (uncertainty avoided)

## Execution

1. Open SSMS
2. Connect to MOGHARE360 ERP database (e.g. `moghare360_ERP`)
3. Open the SQL file
4. Execute (F5)
5. Confirm message: `Phase 1 Customer Core System SQL completed.`

Do **not** auto-run from PHP.
