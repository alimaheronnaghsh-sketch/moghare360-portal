# Phase 2 Access Request Workflow Write Design Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Workflow Write Design Plan
Status: Draft for Review
Scope: Design Only

## 1. Purpose

This document defines the design plan for moving from the signed-off controlled browser action preview to a future controlled workflow write implementation.

The previous signed-off prototype validated only a safe transition preview:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
Permission concept: access_request.submit
```

The prototype result was:

```
Result: OK
Database Update: Blocked
Audit / History Write: Blocked
```

This document does not approve implementation.

This document does not approve database writes.

This document does not approve audit/history inserts.

This document does not approve production workflow execution.

## 2. Current Approved Boundary

The currently approved boundary is:

```
Controlled local browser transition preview only
```

Approved result:

```
DRAFT -> SUBMITTED preview validated successfully
```

Approved safety state:

```
Database Update: Blocked
Audit / History Write: Blocked
```

No database state was changed during the prototype.

## 3. Next Target Boundary

The next target boundary is to design a controlled workflow write path for:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
```

The future implementation must be explicitly approved before any database write is introduced.

The future implementation must remain limited to the Access Request workflow.

The future implementation must not generalize workflow writes across the ERP without a separate design and approval.

## 4. Required Read-Only Schema Verification Before Write Design

Before any write implementation is approved, the following must be verified using read-only inspection:

* Actual table name for access requests
* Actual primary key column
* Actual request state/status column
* Actual request owner/requester column if applicable
* Actual approval-related tables
* Actual audit table or audit mechanism
* Actual history table or history mechanism
* Existing constraints
* Existing triggers
* Existing foreign keys
* Existing indexes relevant to request state transitions

No schema change is approved in this plan.

No migration is approved in this plan.

No write query is approved in this plan.

## 5. Proposed Future Workflow Write Sequence

The future workflow write sequence should follow this controlled order:

1. Start authenticated session
2. Load current user context
3. Validate CSRF token
4. Validate permission:
   access_request.submit
5. Load target access request using read operation
6. Verify entity:
   access_request
7. Verify current state:
   DRAFT
8. Verify requested target state:
   SUBMITTED
9. Validate workflow rule:
   DRAFT -> SUBMITTED
10. Open database transaction
11. Re-read or lock the target access request row inside the transaction
12. Re-confirm current state is still DRAFT
13. Update access request state to SUBMITTED
14. Insert workflow history record if approved
15. Insert audit record if approved
16. Commit transaction
17. Return success result
18. On failure, rollback transaction and return controlled error

This sequence is design-only.

No implementation is approved yet.

## 6. Transaction Safety Requirements

Any future write implementation must use a transaction.

The transaction must guarantee that these operations either succeed together or fail together:

* Access request state update
* Workflow history insert
* Audit insert

If any operation fails, the full transaction must rollback.

Partial workflow state changes are not allowed.

Partial audit/history writes are not allowed.

## 7. Concurrency Safety Requirements

The future implementation must handle concurrent requests safely.

At minimum, the implementation must prevent two users or two browser submissions from submitting the same DRAFT request twice.

The future implementation must re-check the current request state inside the transaction before updating.

If the state is no longer DRAFT, the write must be blocked.

The user-facing result must show a controlled error, not a fatal PHP error.

## 8. Idempotency and Duplicate Submit Protection

The future implementation must protect against:

* Double-click submit
* Browser refresh after submit
* Back button resubmission
* Repeated POST request
* Expired CSRF token reuse
* Stale form submission

No duplicate history record should be created for the same transition.

No duplicate audit record should be created for the same transition.

## 9. Permission and Role Safety Requirements

The future implementation must require:

```
access_request.submit
```

The implementation must not bypass permission checks.

The implementation must not infer authorization only from login status.

The implementation must not rely on browser-hidden fields for permission decisions.

The implementation must not create or change users.

The implementation must not create or change roles.

The implementation must not create or change permissions.

## 10. CSRF and Request Method Requirements

The future write action must require POST.

GET must not perform any write.

The future write action must require a valid CSRF token.

Invalid CSRF token must block the write.

Expired CSRF token must block the write.

Missing CSRF token must block the write.

## 11. Audit and History Requirements

Audit/history write behavior must be separately confirmed before implementation.

The future implementation must answer:

* Which audit table is used?
* Which history table is used?
* Which columns are mandatory?
* What actor/user ID is recorded?
* What before-state is recorded?
* What after-state is recorded?
* What request ID is recorded?
* What timestamp source is used?
* What IP/user agent fields are used, if any?
* What happens if audit insert fails?
* What happens if history insert fails?

Until this is confirmed, audit/history writes remain blocked.

## 12. Error Handling Requirements

The future implementation must return controlled errors for:

* Not logged in
* Missing permission
* Invalid CSRF token
* Missing request ID
* Invalid request ID
* Request not found
* Current state is not DRAFT
* Transition not allowed
* Database transaction failure
* Audit/history failure
* Unexpected exception

Fatal PHP errors are not acceptable.

Raw database errors must not be exposed to browser users.

## 13. UI Requirements for Future Write Mode

The future write page must clearly show whether it is operating in:

* Preview mode
* Write-enabled mode

If write-enabled mode is introduced, the page must show a clear warning boundary.

The write action button must not be confused with preview action.

The result must clearly state whether database state was changed.

## 14. Required Implementation Files for Future Phase

Potential implementation files for a future approved phase may include:

```
public_html/erp-access-request-transition.php
includes/erp-workflow-engine.php
includes/erp-permission-check.php
includes/erp-csrf.php
includes/erp-auth-context.php
```

Additional database access helper may be required only after a separate design approval.

No file changes are approved by this plan.

## 15. Required Test Plan Before Implementation

Before write implementation, a separate test plan must be created for:

* Source syntax check
* Runtime copy
* Runtime syntax check
* GET page load test
* Invalid CSRF POST test
* Missing permission test
* Invalid state transition test
* Valid DRAFT -> SUBMITTED write test
* Duplicate submit test
* Audit/history verification
* Rollback verification
* Database row verification
* No unrelated table mutation verification

No tests are executed by this plan.

## 16. Explicit Non-Approval

This design plan does not approve:

* Production workflow execution
* Database state update
* Access request row mutation
* Approval creation
* Audit insert
* History insert
* Role assignment changes
* Permission changes
* User changes
* Tenant data changes
* SQL schema changes
* Live operational workflow changes
* Runtime file changes
* Browser write test

Production workflow implementation remains blocked.

Database writes remain blocked.

Audit/history database writes remain blocked.

## 17. Next Required Document

Before any implementation, the next required document should be:

```
PHASE_2_ACCESS_REQUEST_WORKFLOW_WRITE_SCHEMA_READONLY_INSPECTION_PLAN.md
```

That document must define the exact read-only schema inspection required before writing any database mutation code.

## 18. Final Decision

This document defines the design direction only.

The project may proceed to read-only schema inspection planning.

The project may not proceed to database write implementation yet.

The project may not proceed to audit/history insert implementation yet.

The project may not proceed to production workflow execution yet.
