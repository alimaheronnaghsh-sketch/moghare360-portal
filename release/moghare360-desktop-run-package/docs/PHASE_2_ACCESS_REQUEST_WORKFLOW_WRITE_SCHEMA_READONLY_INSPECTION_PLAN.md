# Phase 2 Access Request Workflow Write Schema Read-Only Inspection Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Schema Read-Only Inspection Plan
Status: Draft for Review
Scope: Planning Only

## 1. Purpose

This document defines the read-only schema inspection plan required before any Access Request workflow write implementation.

The previous approved design document defined a future controlled write path for:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
Permission concept: access_request.submit
```

This inspection plan does not approve implementation.

This inspection plan does not approve database writes.

This inspection plan does not approve audit/history inserts.

This inspection plan does not approve production workflow execution.

## 2. Current Approved Boundary

The currently approved boundary remains:

```
Controlled local browser transition preview only
```

The approved browser result was:

```
Result: OK
Transition: DRAFT -> SUBMITTED
Database Update: Blocked
Audit / History Write: Blocked
```

No database state was changed.

## 3. Inspection Goal

The goal of the read-only schema inspection is to identify the exact existing database structures needed before designing any write query.

The inspection must answer:

* Which table stores access requests?
* Which column is the access request primary key?
* Which column stores request state/status?
* Which column stores request code or reference number?
* Which column stores requester/user identity?
* Which table stores access request items?
* Which table stores approvals?
* Which table stores access change history?
* Which table stores suspensions/restrictions if relevant?
* Which audit mechanism exists?
* Which history mechanism exists?
* Which constraints, triggers, and foreign keys can affect workflow writes?

## 4. Database Safety Boundary

Only read-only inspection is allowed.

Allowed SQL operation type:

```
SELECT
```

Blocked SQL operation types:

```
INSERT
UPDATE
DELETE
MERGE
DROP
ALTER
TRUNCATE
CREATE
EXEC
GRANT
REVOKE
```

No data mutation is allowed.

No schema mutation is allowed.

No stored procedure execution is allowed unless separately reviewed and explicitly approved as read-only.

## 5. Target Database

Expected database:

```
moghare360_ERP
```

The database name must be confirmed during inspection.

The inspection must not assume table or column names without verifying them.

## 6. Expected Tables to Inspect

The following tables are expected based on current Phase 2 context, but must be verified read-only:

```
dbo.core_access_requests
dbo.core_access_request_items
dbo.core_access_approvals
dbo.core_access_change_history
dbo.core_access_suspensions
dbo.core_access_restrictions
```

The inspection must also search for related audit/history tables.

## 7. Required Metadata Inspection

The inspection must collect metadata for the target tables:

* Table name
* Column name
* Data type
* Max length
* Nullability
* Identity flag
* Default constraints
* Primary key columns
* Foreign key relationships
* Check constraints
* Indexes
* Triggers

This metadata must be collected using read-only system catalog queries.

## 8. Required Data Sample Inspection

The inspection may collect limited read-only sample rows from target tables.

Sample inspection must be limited and safe.

Recommended limit:

```
TOP (10)
```

The sample inspection must not expose unnecessary sensitive data.

The purpose of sample rows is only to identify:

* Current status/state values
* Existing request identifiers
* Existing workflow lifecycle shape
* Existing audit/history row format
* Existing actor/user references

## 9. Required State/Status Value Inspection

The inspection must identify actual stored values for request state/status.

Required questions:

* Is the state stored as DRAFT/SUBMITTED?
* Is the state stored in uppercase, lowercase, Persian text, numeric code, or foreign key?
* Is there a separate status table?
* Is there a workflow state table?
* Are values constrained by CHECK constraint?
* Are values inferred from approval/history records instead of a direct status column?

No assumption is allowed.

## 10. Required Actor/User Reference Inspection

The inspection must identify how actor and requester are stored.

Required questions:

* Which column stores the requester?
* Which column stores the creator?
* Which column stores the submitter, if any?
* Which table stores users?
* Which user ID type is used?
* Is user_id numeric?
* Is username stored directly?
* Are tenant/user boundaries enforced through columns?

No user table changes are approved.

## 11. Required Audit/History Inspection

The inspection must identify existing audit/history mechanisms.

Required questions:

* Which table stores access request change history?
* Which table stores audit records?
* Are audit and history separate?
* Which columns are mandatory?
* Is before_state recorded?
* Is after_state recorded?
* Is actor_user_id recorded?
* Is request_id recorded?
* Is created_at recorded?
* Is IP/user agent recorded?
* Are inserts controlled by trigger?
* Are inserts expected to be manual?
* Are there existing rows for the Bootstrap request?

No audit/history insert is approved.

## 12. Required Constraint and Trigger Inspection

The inspection must identify constraints and triggers that could affect future workflow writes.

Required objects:

* Primary keys
* Foreign keys
* Unique constraints
* Check constraints
* Default constraints
* Triggers
* Indexes

Special focus:

* Constraints on status/state columns
* Triggers on access request tables
* Foreign keys from history/audit tables
* Required non-null columns for audit/history inserts

## 13. Required Concurrency Inspection

The inspection must identify columns that can support safe concurrency.

Possible columns to look for:

* rowversion
* timestamp
* updated_at
* modified_at
* version
* status_updated_at
* submitted_at
* submitted_by

If no concurrency-supporting column exists, future implementation must rely on transactional state re-checking.

No schema change is approved by this plan.

## 14. Required Output Document After Inspection

After read-only inspection is executed, the next result document should be:

```
PHASE_2_ACCESS_REQUEST_WORKFLOW_WRITE_SCHEMA_READONLY_INSPECTION_RESULT.md
```

That result document must include:

* Confirmed table names
* Confirmed primary keys
* Confirmed state/status column
* Confirmed current state values
* Confirmed requester/actor columns
* Confirmed audit/history tables
* Confirmed constraints/triggers
* Confirmed safe candidate row for future controlled write test, if one exists
* Confirmation that only SELECT queries were used
* Confirmation that no database writes were performed

## 15. Required SQL Execution Boundary

The future inspection execution may use SSMS.

The future inspection execution must be manual and controlled.

The SQL script must be reviewed before execution.

The SQL script must use only SELECT queries.

The SQL script must not include write statements.

The SQL script must not include schema mutation statements.

## 16. Explicit Non-Approval

This plan does not approve:

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
* Runtime file changes
* Browser write test
* PHP database connection implementation
* Any write-enabled workflow code

Production workflow implementation remains blocked.

Database writes remain blocked.

Audit/history database writes remain blocked.

## 17. Next Step After This Plan

After this plan is reviewed and committed, the next step is to create a controlled read-only SQL inspection script.

Expected next document or artifact:

```
PHASE_2_ACCESS_REQUEST_WORKFLOW_WRITE_SCHEMA_READONLY_INSPECTION_SQL.md
```

That document must contain only SELECT statements.

## 18. Final Decision

This document approves planning only.

The project may proceed to read-only SQL inspection script design.

The project may not proceed to database write implementation.

The project may not proceed to audit/history insert implementation.

The project may not proceed to production workflow execution.
