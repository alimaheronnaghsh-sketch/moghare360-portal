# Permission and Audit Rules

## Purpose
This document locks permission and audit rules for QC and delivery operations.

## Mission 29 Boundary
Rules documented only. No permission seed or PHP in Mission 29.

## Proposed Permission Keys (Locked)

| Permission Key | Purpose |
|----------------|---------|
| qc.check.create | Create QC check record |
| qc.check.view | View single QC check |
| qc.check.list | List QC checks |
| qc.check.pass | Mark QC PASSED |
| qc.check.fail | Mark QC FAILED |
| delivery.control.view | View delivery control for JobCard |
| delivery.control.release | Release delivery (READY → RELEASED) |
| soft.run.readiness.view | View Soft Run readiness gate page |

## Permission Mapping (Indicative — Mission 30)

| Action | Permission |
|--------|------------|
| QC check create/pass/fail | qc.check.create / pass / fail |
| QC list/detail | qc.check.list / view |
| Delivery control page | delivery.control.view |
| Delivery release POST | delivery.control.release |
| Soft Run readiness | soft.run.readiness.view |

## Prototype Auth Context (Locked Reference)
- user_id = 10001 (platform owner)
- Permission Guard placeholder pattern from prior missions
- CSRF token on all POST writes

## Audit Rules for Future Writes (Locked)

Every future write operation must include:

### 1. Auth Context
- Resolve `checked_by_user_id` / `released_by_user_id` / `changed_by_user_id` from session

### 2. Permission Guard
- Check permission key before action
- Fail closed on missing permission

### 3. CSRF Protection
- Validate CSRF token on POST

### 4. Transaction
- Wrap QC + history or delivery + history in database transaction

### 5. Audit / History
- `erp_qc_check_history` on every QC status change
- `erp_delivery_control_history` on every delivery status change

### 6. No Silent QC Pass
- PASSED requires explicit `qc.check.pass` action
- No auto-pass on service complete

### 7. No Silent Delivery Release
- RELEASED requires explicit `delivery.control.release`
- No auto-release on payment or QC

### 8. No Customer Signature Bypass
- Release without staff user forbidden
- No portal-side release in M30

### 9. No Payment Gate Bypass
- If block_reason set, release must not proceed without policy override permission (future) or resolution

## Suggested action_code Values (Future)

| action_code | Domain |
|-------------|--------|
| QC_CHECK_CREATED | QC |
| QC_CHECK_PASSED | QC |
| QC_CHECK_FAILED | QC |
| QC_CHECK_RECHECK_REQUIRED | QC |
| QC_CHECK_CANCELLED | QC |
| DELIVERY_CONTROL_CREATED | Delivery |
| DELIVERY_BLOCKED | Delivery |
| DELIVERY_READY | Delivery |
| DELIVERY_RELEASED | Delivery |
| DELIVERY_CANCELLED | Delivery |

## Forbidden Files (Locked)
Mission 29 and Mission 30 must not modify:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy inventory PHP

## Mission 29 Boundary
Permission and audit rules documented only.

## Final Permission/Audit Decision
Eight permission keys; full auth/CSRF/transaction/history; no silent QC pass or delivery release; no signature or payment bypass.
