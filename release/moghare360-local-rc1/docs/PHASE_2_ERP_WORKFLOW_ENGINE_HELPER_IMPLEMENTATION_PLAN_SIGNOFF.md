# Phase 2 ERP Workflow Engine Helper Implementation Plan Sign-Off

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Sign-Off
Status: Approved for ERP Workflow Engine Helper Implementation
Implementation Status: Not Started

## 1. Sign-Off Purpose

This document confirms that the Phase 2 ERP Workflow Engine Helper Implementation Plan has been reviewed and accepted.

This sign-off approves moving to the isolated ERP Workflow Engine Helper implementation step.

This sign-off does not approve browser workflow implementation yet.

## 2. Approved Source Document

Approved document:

```
docs/PHASE_2_ERP_WORKFLOW_ENGINE_HELPER_IMPLEMENTATION_PLAN.md
```

Approved next implementation file:

```
includes/erp-workflow-engine.php
```

## 3. Confirmed Previous Helpers

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

## 4. Approved Workflow Helper Boundary

The ERP Workflow Engine Helper may only validate workflow transitions.

Allowed behavior:

```
Normalize workflow entity name
Normalize current state
Normalize requested next state
Validate allowed transition map
Return approved transition result
Throw safe exception on invalid transition
```

## 5. Approved First Controlled Workflow Scope

The first controlled workflow scope is:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
```

No other workflow transition is approved in this sign-off.

## 6. Approved Planned Functions

The next implementation may define:

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

## 8. Approved Failure Rule

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

## 9. Not Approved in This Sign-Off

This sign-off does not approve:

* Browser transition page
* Direct workflow state update
* Database connection
* INSERT
* UPDATE
* DELETE
* MERGE
* DROP
* ALTER
* TRUNCATE
* Login replacement
* Config changes
* User creation
* Role creation
* Role assignment
* Permission creation
* Tenant creation
* SQL schema change

## 10. Files That Must Not Be Modified

The next implementation step must not modify:

```
staff-auth.php
access-control.php
staff-login.php
config.php
config.example.php
customer portal files
inventory files
legacy files
existing dashboard files
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
```

## 11. Required Test After Implementation

After creating includes/erp-workflow-engine.php, run:

```
php -l includes/erp-workflow-engine.php
```

Expected result:

```
No syntax errors detected in includes/erp-workflow-engine.php
```

## 12. Commit Boundary

The implementation commit must include only:

```
includes/erp-workflow-engine.php
```

No other file may be included.

## 13. Final Sign-Off Decision

The Phase 2 ERP Workflow Engine Helper Implementation Plan is approved.

The next approved step is only:

```
Create includes/erp-workflow-engine.php
```

Full browser workflow implementation remains blocked.
