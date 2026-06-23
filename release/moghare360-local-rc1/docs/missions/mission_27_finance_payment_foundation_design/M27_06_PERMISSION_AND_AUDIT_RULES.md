# Permission and Audit Rules

## Purpose
This document locks permission and audit rules for payment operations.

## Mission 27 Boundary
Rules documented only. No permission seed or PHP in Mission 27.

## Proposed Permission Keys (Locked)

| Permission Key | Purpose |
|----------------|---------|
| payment.create | Create payment (DRAFT or RECEIVED) |
| payment.view | View single payment detail |
| payment.list | List payments |
| payment.summary.view | View JobCard payment summary (read-only aggregates) |
| payment.cancel | Transition to CANCELLED |
| payment.reverse | Transition to REVERSED (future) |

## Permission Mapping (Indicative — Mission 28)

| Action | Permission |
|--------|------------|
| Create page POST | payment.create |
| List page | payment.list |
| Payment detail (if added) | payment.view |
| JobCard summary page | payment.summary.view |
| Cancel action | payment.cancel |
| Reverse action | payment.reverse |

## Prototype Auth Context (Locked Reference)
- user_id = 10001 (platform owner)
- Permission Guard placeholder pattern from prior missions
- CSRF token on all POST writes

## Audit Rules for Future Writes (Locked)

Every future write operation must include:

### 1. Auth Context
- Resolve `received_by_user_id` / `changed_by_user_id` from authenticated session
- Reject unauthenticated requests

### 2. Permission Guard
- Check permission key before action
- Fail closed on missing permission

### 3. CSRF Protection
- Validate CSRF token on POST
- Reject missing or invalid token

### 4. Transaction
- Wrap multi-step writes in database transaction
- Rollback on any failure (payment + history atomic)

### 5. Audit / History
- Insert `erp_payment_history` on every status change and on create
- Include action_code, old_status, new_status, changed_by_user_id, change_note

### 6. No Silent Payment Change
- RECEIVED requires explicit action
- No background job auto-receives payments

### 7. No Direct Balance Overwrite
- No UPDATE to a stored outstanding_balance column
- Summary always calculated from payment rows

### 8. No Accounting Export
- No ledger, journal, or export file on any payment action in M28

## Suggested action_code Values (Future)

| action_code | Trigger |
|-------------|---------|
| PAYMENT_CREATED | Initial insert |
| PAYMENT_RECEIVED | DRAFT → RECEIVED |
| PAYMENT_CANCELLED | → CANCELLED |
| PAYMENT_REVERSED | → REVERSED (future) |

## Forbidden Files (Locked)
Mission 27 and Mission 28 must not modify:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy inventory PHP

## Mission 27 Boundary
Permission and audit rules documented only.

## Final Permission/Audit Decision
Six payment.* permissions; every write requires auth, permission, CSRF, transaction, history; no silent change; no balance overwrite; no accounting export.
