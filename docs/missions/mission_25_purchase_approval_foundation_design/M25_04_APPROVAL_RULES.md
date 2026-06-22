# Approval Rules

## Purpose
This document locks purchase request approval rules for future implementation.

## Mission 25 Boundary
Rules documented only. No approval code in Mission 25.

## Status Transition Rules (Design)

### DRAFT
- Editable only by **request creator** or **platform owner** (prototype: user_id 10001)
- May transition to SUBMITTED via `purchase.request.submit`
- May transition to CANCELLED via `purchase.request.cancel`

### SUBMITTED
- Requires approval action — **no automatic approval**
- Not freely editable (field lock policy in Mission 26+)
- May transition to APPROVED via `purchase.request.approve`
- May transition to REJECTED via `purchase.request.reject`
- May transition to CANCELLED via `purchase.request.cancel`

### APPROVED (Locked Side Effects)
- Must **not** create Stock Receipt (no RECEIPT movement)
- Must **not** create Finance Payment (no AP / ledger / journal)
- ORDERED transition reserved for future purchase execution mission

### REJECTED
- **Must have reason** in `change_note` and/or dedicated reject reason field
- `rejected_by_user_id` and `rejected_at` required
- History row with old_status SUBMITTED, new_status REJECTED

### CANCELLED
- **Physical delete forbidden**
- `is_active` may remain 1 with status CANCELLED or soft-deactivate per M26 charter
- History required on cancellation

### ORDERED / RECEIVED / CLOSED
- Reserved for future missions
- Not implemented in Mission 26 initial scope unless explicitly chartered

## Edit Rules (Locked)

| Status | Who May Edit |
|--------|----------------|
| DRAFT | Creator or owner |
| SUBMITTED | No content edit (approval actions only) |
| APPROVED | No edit without future amendment mission |
| REJECTED | No edit; new request if needed |
| CANCELLED | No edit |

## History on Every Status Change (Locked)
Every transition must write `erp_purchase_request_history` with:
- action_code (e.g. PURCHASE_REQUEST_SUBMITTED, PURCHASE_REQUEST_APPROVED)
- old_status, new_status
- changed_by_user_id, changed_at
- change_note (mandatory for REJECTED)

## No Automatic Approval (Locked)
- No rule engine auto-approves on submit
- No time-based auto-approve
- No stock-level auto-approve

## Mission 26 Indicative Scope
Mission 26 may implement:
- Create (DRAFT or SUBMITTED)
- List and detail read-only
- Submit DRAFT → SUBMITTED (optional in M26 charter)

Approval actions (APPROVE/REJECT) may be Mission 26b or Mission 27 unless explicitly included in M26.

## Final Approval Decision
Human approval required; APPROVED has zero stock/finance side effects in M25/M26 design; REJECTED requires reason; CANCELLED replaces delete.
