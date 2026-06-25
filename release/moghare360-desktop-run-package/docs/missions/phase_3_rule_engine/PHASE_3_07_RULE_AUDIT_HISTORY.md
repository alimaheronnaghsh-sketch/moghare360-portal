# PHASE 3 — Rule Audit History

## Table

`dbo.erp_rule_audit_history`

## Helper

`rule_engine_insert_history($connection, $entityType, $entityId, $actionType, $actionSummary, $oldValue, $newValue)`

## Logged Events

- RULE_DECISION — new decision recorded
- APPROVAL_REQUEST_CREATE — approval queue entry
- APPROVAL_DECISION — approve/reject/cancel
- RULE_CHECK_COMPLETE — console batch run
- APPROVAL_GRANTED — operation case continuation note (safe, no heavy rewrite)

## Fields

Includes `created_by`, `source_ip`, `user_agent` for internal audit trail.
