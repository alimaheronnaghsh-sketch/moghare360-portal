# PHASE 3 — SQL Foundation

## File

`public_html/sql/sqlserver/phase_3_rule_engine.sql`

## Tables

1. `dbo.erp_rule_definitions` — rule catalog + seeds
2. `dbo.erp_rule_decisions` — evaluation results
3. `dbo.erp_service_approval_requests` — approval workflow queue
4. `dbo.erp_inventory_rule_requests` — warehouse vs purchase routing
5. `dbo.erp_rule_audit_history` — audit trail

## Seeds (idempotent)

- CONTRACT_OPEN_AUTHORIZATION_LIMIT
- CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD
- SERVICE_OUT_OF_CONTRACT_APPROVAL
- INVENTORY_PART_AVAILABLE_USE_STOCK
- INVENTORY_PART_NOT_AVAILABLE_PURCHASE
- OPERATION_BLOCK_WITHOUT_RULE_CHECK

## Execution

SSMS → `moghare360_ERP` → execute script → confirm completion message.

Run after Phase 1 and Phase 2 SQL if linking to contracts/operations.
