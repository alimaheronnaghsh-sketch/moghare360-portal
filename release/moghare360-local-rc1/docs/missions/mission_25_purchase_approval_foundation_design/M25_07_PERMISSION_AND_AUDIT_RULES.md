# Permission and Audit Rules

## Purpose
This document locks permission and audit rules for purchase request operations.

## Mission 25 Boundary
Rules documented only. No permission seed or PHP in Mission 25.

## Proposed Permission Keys (Locked)

| Permission Key | Purpose |
|----------------|---------|
| purchase.request.create | Create purchase request (DRAFT or SUBMITTED) |
| purchase.request.view | View single purchase request detail |
| purchase.request.list | List purchase requests |
| purchase.request.submit | Transition DRAFT → SUBMITTED |
| purchase.request.approve | Transition SUBMITTED → APPROVED |
| purchase.request.reject | Transition SUBMITTED → REJECTED |
| purchase.request.cancel | Transition to CANCELLED |

## Permission Mapping (Indicative — Mission 26)

| Action | Permission |
|--------|------------|
| Create page POST | purchase.request.create |
| List page | purchase.request.list |
| Detail page | purchase.request.view |
| Submit button | purchase.request.submit |
| Approve button | purchase.request.approve |
| Reject button | purchase.request.reject |
| Cancel button | purchase.request.cancel |

## Prototype Auth Context (Locked Reference)
- user_id = 10001 (platform owner)
- Permission Guard placeholder pattern from prior missions
- CSRF token on all POST writes

## Audit Rules for Future Writes (Locked)

Every future write operation must include:

### 1. Auth Context
- Resolve `requested_by_user_id` / `changed_by_user_id` from authenticated session
- Reject unauthenticated requests

### 2. Permission Guard
- Check permission key before action
- Fail closed on missing permission

### 3. CSRF Protection
- Validate CSRF token on POST
- Reject missing or invalid token

### 4. Transaction
- Wrap multi-step writes in database transaction
- Rollback on any failure (request + history atomic)

### 5. Audit / History
- Insert `erp_purchase_request_history` on every status change
- Include action_code, old_status, new_status, changed_by_user_id, change_note

### 6. No Silent Approval
- APPROVED requires explicit approve action with permission
- No background job auto-approves

### 7. No Automatic Finance Write
- No payment, AP, or ledger on any status transition in M26 scope

### 8. No Automatic Stock Write
- No RECEIPT movement on APPROVED or SUBMITTED
- Stock receipt reserved for future mission

## Suggested action_code Values (Future)

| action_code | Trigger |
|-------------|---------|
| PURCHASE_REQUEST_CREATED | Initial insert |
| PURCHASE_REQUEST_SUBMITTED | DRAFT → SUBMITTED |
| PURCHASE_REQUEST_APPROVED | SUBMITTED → APPROVED |
| PURCHASE_REQUEST_REJECTED | SUBMITTED → REJECTED |
| PURCHASE_REQUEST_CANCELLED | → CANCELLED |
| PURCHASE_REQUEST_UPDATED | DRAFT field edit (optional M26+) |

## Forbidden Files (Locked)
Mission 25 and Mission 26 must not modify:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy inventory PHP

## Mission 25 Boundary
Permission and audit rules documented only.

## Final Permission/Audit Decision
Seven purchase.request.* permissions; every write requires auth, permission, CSRF, transaction, history; no silent approval; no auto finance/stock writes.
