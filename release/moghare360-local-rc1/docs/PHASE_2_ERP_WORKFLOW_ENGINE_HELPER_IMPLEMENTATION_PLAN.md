# Phase 2 ERP Workflow Engine Helper Implementation Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Implementation Plan
Status: Planning Only
Implementation Status: Not Started

## 1. Purpose

This document defines the implementation plan for the ERP Workflow Engine Helper in MOGHARE360 Phase 2 controlled prototype.

This is a planning document only.

No PHP implementation is approved by this document alone.

## 2. Approved Previous Helpers

Completed helpers:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
```

Confirmed status:

```
Syntax tests passed
No login replacement
No database write
No config change
No user, role, permission, tenant, or workflow change
```

## 3. Next Planned Helper

Planned file:

```
includes/erp-workflow-engine.php
```

Purpose:

```
Provide isolated workflow transition validation for controlled ERP prototype actions.
```

This helper will be used later by:

```
public_html/erp-access-request-transition.php
```

The browser page is not approved for creation yet.

## 4. Implementation Boundary

The Workflow Engine Helper may only validate workflow transitions.

Allowed behavior:

```
Normalize workflow entity name
Normalize current state
Normalize requested next state
Validate allowed transition map
Return approved transition result
Throw safe exception on invalid transition
```

Not allowed behavior:

```
Database connection
INSERT
UPDATE
DELETE
MERGE
DROP
ALTER
TRUNCATE
Login replacement
User creation
Role assignment
Permission creation
Tenant change
Direct workflow state update
Browser output
```

## 5. First Controlled Workflow Scope

The first controlled workflow scope is:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
```

No other workflow transition is approved in this step.

## 6. Planned Functions

The future helper may define:

```
erp_workflow_normalize_value(string $value, string $label)
erp_workflow_get_allowed_transitions()
erp_workflow_can_transition(string $entity, string $from_state, string $to_state)
erp_workflow_require_transition(string $entity, string $from_state, string $to_state)
erp_workflow_build_transition_result(string $entity, string $from_state, string $to_state)
```

## 7. Approved Transition Map

The first transition map may include only:

```
access_request:
    DRAFT:
        SUBMITTED
```

Any additional transition must be planned separately.

## 8. Failure Rule

The helper must reject:

```
Empty entity
Empty from_state
Empty to_state
Unknown entity
Unknown current state
Unknown requested state
Invalid transition
```

Failure must throw:

```
RuntimeException
```

No HTML output is required from the helper.

## 9. Safety Rule

The helper must be isolated.

It must not require:

```
staff-auth.php
access-control.php
staff-login.php
config.php
config.example.php
database connection
```

It may be required later by the controlled browser action page, but that page is not approved yet.

## 10. Required Test

After implementation, syntax check must run:

```
php -l includes/erp-workflow-engine.php
```

Expected result:

```
No syntax errors detected in includes/erp-workflow-engine.php
```

## 11. Commit Boundary

The future implementation commit must include only:

```
includes/erp-workflow-engine.php
```

No other file may be included in that implementation commit.

## 12. Final Decision

The next approved implementation step after this plan and sign-off will be:

```
Create includes/erp-workflow-engine.php
```

Full browser workflow implementation remains blocked.
