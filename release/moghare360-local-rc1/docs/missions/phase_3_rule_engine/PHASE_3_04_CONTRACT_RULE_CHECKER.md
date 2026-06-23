# PHASE 3 — Contract Rule Checker

## Function

`rule_engine_check_contract_authorization($connection, $contractId, $requestedAmount)`

## Logic

| Condition | Status | Next Action | Blocking |
|-----------|--------|-------------|----------|
| No readable contract | NEEDS_REVIEW | REVIEW_MANUALLY | Yes |
| requires_operation_approval = 1 | APPROVAL_REQUIRED | REQUEST_CUSTOMER_APPROVAL | Yes |
| OPEN_AUTHORIZATION, amount ≤ threshold | ALLOWED | CONTINUE_OPERATION | No |
| OPEN_AUTHORIZATION, amount > threshold | APPROVAL_REQUIRED | REQUEST_CUSTOMER_APPROVAL | Yes |
| LIMITED_AUTHORIZATION, amount > threshold | APPROVAL_REQUIRED | REQUEST_CUSTOMER_APPROVAL | Yes |
| Otherwise | ALLOWED | CONTINUE_OPERATION | No |

## Storage

Results written to `erp_rule_decisions` via `rule_engine_create_decision()`.

Approval queue via `rule_engine_create_approval_request_if_needed()` when status = APPROVAL_REQUIRED.
