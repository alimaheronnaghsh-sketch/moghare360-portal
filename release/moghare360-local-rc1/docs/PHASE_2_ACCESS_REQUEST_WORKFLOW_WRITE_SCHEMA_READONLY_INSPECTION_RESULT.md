# Phase 2 Access Request Workflow Write Schema Read-Only Inspection Result

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Schema Read-Only Inspection Result
Status: Initial Read-Only Inspection Completed
Scope: Read-Only Inspection Result Only

## 1. Result Purpose

This document records the initial read-only schema inspection result for the Access Request workflow write design path.

The inspection was performed before any workflow write implementation.

The inspection was limited to read-only SELECT queries.

This result does not approve implementation.

This result does not approve database writes.

This result does not approve audit/history inserts.

This result does not approve production workflow execution.

## 2. Database Confirmation

Confirmed database:

```
moghare360_ERP
```

The SSMS query window was connected to the expected database.

## 3. Confirmed Access Request Workflow Tables

The following expected tables were found:

```
dbo.core_access_approvals
dbo.core_access_change_history
dbo.core_access_request_items
dbo.core_access_requests
dbo.core_access_restrictions
dbo.core_access_suspensions
```

## 4. Confirmed core_access_requests Columns

The following columns were observed for:

```
dbo.core_access_requests
```

Observed columns:

```
1.  request_id              bigint       identity
2.  request_number          nvarchar(60)
3.  request_type            nvarchar(80)
4.  request_state           nvarchar(60)
5.  priority                nvarchar(40)
6.  subject_user_id         int
7.  requested_by_user_id    int
8.  justification           nvarchar(max)
9.  owner_acknowledged      bit
10. is_emergency            bit
11. migration_source        nvarchar(60), nullable
12. submitted_at            datetime2, nullable
13. decided_at              datetime2, nullable
14. applied_at              datetime2, nullable
15. applied_by_user_id      int, nullable
16. cancelled_at            datetime2, nullable
17. cancelled_by_user_id    int, nullable
18. created_at              datetime2
19. updated_at              datetime2, nullable
20. row_version             timestamp
```

Important confirmed workflow column:

```
request_state
```

Important confirmed concurrency-supporting column:

```
row_version
```

Important confirmed timestamp columns:

```
submitted_at
decided_at
applied_at
cancelled_at
created_at
updated_at
```

## 5. Confirmed core_access_change_history Columns

The following columns were observed for:

```
dbo.core_access_change_history
```

Observed columns:

```
1.  history_id              bigint       identity
2.  user_id                 int
3.  request_id              bigint
4.  change_type             nvarchar(80)
5.  entity_type             nvarchar(100)
6.  entity_id               bigint, nullable
7.  before_json             nvarchar(max), nullable
8.  after_json              nvarchar(max), nullable
9.  changed_by_user_id      int, nullable
10. changed_at              datetime2
```

Confirmed history candidate table:

```
dbo.core_access_change_history
```

## 6. Confirmed Request State Values

Observed request_state values:

```
APPLIED    1
DRAFT      1
```

This confirms that the current workflow state column stores uppercase text values.

Confirmed DRAFT state exists.

Confirmed APPLIED state exists.

No database mutation was performed.

## 7. Observed Access Request Sample Rows

Observed sample rows from:

```
dbo.core_access_requests
```

Row 1:

```
request_id: 1
request_number: BOOTSTRAP-10001
request_type: EMERGENCY
request_state: APPLIED
subject_user_id: 10001
requested_by_user_id: 10001
submitted_at: 2026-06-16 14:53:44.675
decided_at: 2026-06-16 14:53:44.675
applied_at: 2026-06-16 14:53:44.675
cancelled_at: NULL
created_at: 2026-06-16 14:53:44.675
updated_at: NULL
```

Row 2:

```
request_id: 4
request_number: AR-20260620-084634-10001
request_type: ROLE_GRANT
request_state: DRAFT
subject_user_id: 10001
requested_by_user_id: 10001
submitted_at: NULL
decided_at: NULL
applied_at: NULL
cancelled_at: NULL
created_at: 2026-06-20 06:46:34.383
updated_at: NULL
```

## 8. Safe Candidate Row for Future Controlled Write Design

A candidate DRAFT row was identified for future design discussion only:

```
request_id: 4
request_number: AR-20260620-084634-10001
request_type: ROLE_GRANT
request_state: DRAFT
```

This row is only a candidate for future controlled write design.

This document does not approve writing to this row.

This document does not approve submitting this row.

This document does not approve any database mutation.

## 9. Candidate State or Status Columns

Observed candidate state/status columns:

```
dbo.core_access_requests.request_state    nvarchar
dbo.core_access_requests.submitted_at     datetime2
```

Confirmed workflow state column:

```
dbo.core_access_requests.request_state
```

The submitted_at column may support future submit timestamp logic, but no write behavior is approved.

## 10. Candidate Actor or User Columns

Observed candidate actor/user columns:

```
dbo.core_access_change_history.user_id              int
dbo.core_access_change_history.changed_by_user_id   int
dbo.core_access_requests.subject_user_id            int
dbo.core_access_requests.requested_by_user_id       int
dbo.core_access_requests.applied_by_user_id         int
dbo.core_access_requests.cancelled_by_user_id       int
dbo.core_access_restrictions.user_id                int
dbo.core_access_suspensions.user_id                 int
```

Confirmed requester/user columns on access requests:

```
subject_user_id
requested_by_user_id
```

Possible future submit actor column was not confirmed in this reduced inspection.

No user table changes are approved.

## 11. Possible Audit or History Tables

Observed possible audit/history tables:

```
dbo.core_access_change_history
dbo.core_audit_logs
```

Confirmed workflow history candidate:

```
dbo.core_access_change_history
```

Confirmed audit candidate:

```
dbo.core_audit_logs
```

The structure of dbo.core_audit_logs was not captured in this reduced output and remains pending for a later read-only inspection if audit insert design is required.

## 12. Inspection Limitations

This was an initial reduced read-only inspection.

The following were not fully captured in the provided output:

* Primary key constraint names
* Foreign key relationships
* Check constraints
* Index definitions
* Trigger definitions
* dbo.core_audit_logs column structure
* Full approval table column structure
* Full request item table column structure
* Full restriction table column structure
* Full suspension table column structure

These items remain pending for future read-only inspection if required before write implementation.

No assumption should be made from missing metadata.

## 13. Database Safety Confirmation

Only read-only SELECT inspection was performed.

No database write operation was performed.

Not used:

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

No workflow state update was performed.

No audit insert was performed.

No history insert was performed.

No access request row mutation was performed.

## 14. Design Implications

The future DRAFT to SUBMITTED write design should use:

```
Table:
dbo.core_access_requests

State column:
request_state

Candidate current state:
DRAFT

Future target state:
SUBMITTED

Candidate row for future controlled design:
request_id = 4
```

The future implementation must still be separately designed and approved.

The future implementation must still include transaction safety, concurrency re-check, CSRF validation, permission validation, and controlled error handling.

## 15. Explicit Non-Approval

This result does not approve:

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

## 16. Final Result

Initial read-only schema inspection completed successfully.

Confirmed database:

```
moghare360_ERP
```

Confirmed main workflow table:

```
dbo.core_access_requests
```

Confirmed state column:

```
request_state
```

Confirmed current states:

```
APPLIED
DRAFT
```

Confirmed candidate DRAFT row:

```
request_id = 4
request_number = AR-20260620-084634-10001
```

Confirmed history candidate:

```
dbo.core_access_change_history
```

Confirmed audit candidate:

```
dbo.core_audit_logs
```

No database state was changed.

No workflow write was performed.

No audit/history insert was performed.

Next phase must be separately planned and explicitly approved before any write-enabled workflow implementation.
