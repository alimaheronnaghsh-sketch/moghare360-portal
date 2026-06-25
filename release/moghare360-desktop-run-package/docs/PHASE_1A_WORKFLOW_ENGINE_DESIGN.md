# Phase 1A Workflow Engine Design (Access Request State Machine)

## Project
MOGHARE360 ERP

## Phase
Phase 1A Extension

## Purpose
Design the approval workflow engine for access requests.

This is a DESIGN ONLY document.

No code is implemented in this step.

No database changes are performed.

---

## Core Concept

The system will manage access requests using a controlled state machine.

Each request transitions through defined states.

---

## Request States

Valid states:

- DRAFT
- SUBMITTED
- UNDER_REVIEW
- PARTIALLY_APPROVED
- APPROVED
- REJECTED
- CANCELLED
- APPLIED

---

## Allowed Transitions

DRAFT → SUBMITTED  
SUBMITTED → UNDER_REVIEW  
UNDER_REVIEW → PARTIALLY_APPROVED  
UNDER_REVIEW → APPROVED  
UNDER_REVIEW → REJECTED  
PARTIALLY_APPROVED → APPROVED  
PARTIALLY_APPROVED → REJECTED  
APPROVED → APPLIED  
ANY STATE → CANCELLED (if not APPLIED)

---

## Business Rules

- Only owner or system_admin can submit DRAFT → SUBMITTED
- Approvers are defined in:
  core_access_approval_rules
- Each request_type has ordered approval chain
- required_order must be respected
- is_required determines mandatory approvals

---

## Approval Model

Each approval step is stored in:

dbo.core_access_approvals

Fields:

- request_id
- approver_user_id
- approver_capacity
- decision (APPROVED / REJECTED / PARTIAL)
- comment
- decided_at

---

## Request Items Execution Model

After APPROVED:

Items in:
dbo.core_access_request_items

will be applied to:

- roles
- departments
- positions
- permissions

---

## Enforcement Rules

- No item is applied before APPROVED state
- No partial application allowed
- All transitions must be logged in:
  core_access_change_history

---

## Security Rules

- Only authorized roles can transition states
- No direct DB state update from UI without validation
- All transitions must be audited

---

## Future Implementation Plan

Next steps:

1. Workflow Engine Service Layer (PHP)
2. Transition validator
3. Approval handler API
4. State change audit logger
5. UI buttons (Approve / Reject / Submit)

---

## Final Note

This document defines the foundation of ERP-grade access control workflow system.

No implementation is included in this step.
