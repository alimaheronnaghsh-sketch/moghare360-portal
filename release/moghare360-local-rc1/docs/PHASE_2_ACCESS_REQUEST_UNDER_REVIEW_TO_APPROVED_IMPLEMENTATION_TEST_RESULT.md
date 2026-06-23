# Phase 2 Access Request UNDER_REVIEW to APPROVED Implementation Test Result

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: UNDER_REVIEW to APPROVED Write Implementation Test Result
Status: Implementation Completed - Manual Test Verified
Scope: Controlled Approval Write Transition Only

## 1. Files Inspected

- `docs/PHASE_2_ACCESS_REQUEST_UNDER_REVIEW_TO_APPROVED_READINESS.md`
- `docs/PHASE_2_ACCESS_REQUEST_APPROVAL_INSERT_SHAPE_LOCK.md`
- `docs/PHASE_2_WORKFLOW_ENGINE_UNDER_REVIEW_TO_APPROVED_TEST_RESULT.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `includes/erp-permission-check.php`
- `includes/erp-workflow-engine.php`
- `includes/erp-config-loader.php`
- `public_html/erp-access-request-review-transition.php`

## 2. Files Modified

- `public_html/erp-access-request-approve-transition.php` (created)
- `docs/PHASE_2_ACCESS_REQUEST_UNDER_REVIEW_TO_APPROVED_IMPLEMENTATION_TEST_RESULT.md`

## 3. New Approval Transition Page Selected

```
public_html/erp-access-request-approve-transition.php
```

Runtime URL after copy:

```
http://localhost:8080/moghare360/erp-access-request-approve-transition.php
```

Existing pages not modified:

```
public_html/erp-access-request-transition.php
public_html/erp-access-request-review-transition.php
```

## 4. Exact Safety Chain Implemented

POST handling for `UNDER_REVIEW -> APPROVED` follows this order:

1. Browser form POST with `request_id`, `csrf_token`, `transition_action`
2. CSRF validation: `erp_csrf_require_valid_token('access_request_approve', ...)`
3. Auth check: `erp_auth_require_current_user()`
4. Permission check: `erp_permission_require($context, 'access.request.approve')`
5. Workflow engine: `erp_workflow_require_transition('access_request', 'UNDER_REVIEW', 'APPROVED')`
6. Workflow result build: `erp_workflow_build_transition_result(...)`
7. SQL transaction open
8. Read target request row
9. State re-check: current `request_state` must be `UNDER_REVIEW`
10. Duplicate approval re-check: no existing `APPROVED` row in `core_access_approvals`
11. INSERT `core_access_approvals`
12. UPDATE `core_access_requests`
13. INSERT `core_access_change_history`
14. Commit transaction only if approval INSERT, request UPDATE, and history INSERT succeed
15. Rollback on any failure

## 5. Permission Used

```
access.request.approve
```

## 6. Workflow Transition Used

```
UNDER_REVIEW -> APPROVED
```

## 7. SQL Tables Touched

- `dbo.core_access_approvals` - INSERT
- `dbo.core_access_requests` - UPDATE
- `dbo.core_access_change_history` - INSERT

No other tables are touched.

## 8. Columns Updated

### `dbo.core_access_requests`
- `request_state` = `APPROVED`
- `decided_at` = `SYSDATETIME()`
- `updated_at` = `SYSDATETIME()`

Not updated:
- `submitted_at`
- `applied_at`

UPDATE is guarded by:

```
WHERE request_id = ?
  AND request_state = N'UNDER_REVIEW'
```

### `dbo.core_access_approvals`
- `request_id`
- `approver_user_id`
- `approver_capacity`
- `decision`
- `comment`
- `decided_at`
- `created_at`

### `dbo.core_access_change_history`
- `user_id`
- `request_id`
- `change_type`
- `entity_type`
- `entity_id`
- `before_json`
- `after_json`
- `changed_by_user_id`
- `changed_at`

## 9. Approval Row Shape

```
request_id = target request_id
approver_user_id = current authenticated user_id
approver_capacity = OWNER
decision = APPROVED
comment = Controlled prototype approval for UNDER_REVIEW to APPROVED
decided_at = SYSDATETIME()
created_at = SYSDATETIME()
```

## 10. History Row Shape

```
user_id = subject_user_id from the target request
request_id = target request_id
change_type = ACCESS_REQUEST_APPROVED
entity_type = core_access_requests
entity_id = request_id
before_json = {"request_state":"UNDER_REVIEW","decided_at":null,"updated_at":"<previous>"}
after_json = {"request_state":"APPROVED","decided_at":"<timestamp>","updated_at":"<timestamp>"}
changed_by_user_id = current authenticated user_id
changed_at = SYSDATETIME()
```

## 11. Explicit Forbidden Actions Not Performed

This implementation did not:

- Modify `public_html/erp-access-request-transition.php`
- Modify `public_html/erp-access-request-review-transition.php`
- Modify `config.php`
- Modify `config.example.php`
- Modify `staff-auth.php`
- Modify `access-control.php`
- Modify customer portal files
- Change database schema
- Assign roles
- Change permissions
- Create users from UI
- Modify tenant behavior
- Update `core_access_request_items`
- Touch `core_user_roles` or equivalent role assignment tables
- Update `applied_at`
- Change `submitted_at`

## 12. Manual Browser Test Steps

1. Copy `public_html/erp-access-request-approve-transition.php` to runtime:
   `C:\xampp\htdocs\moghare360\erp-access-request-approve-transition.php`
2. Confirm candidate request `request_id = 4` is currently `UNDER_REVIEW`
3. Confirm no `APPROVED` row exists yet in `dbo.core_access_approvals` for `request_id = 4`
4. Open:
   `http://localhost:8080/moghare360/erp-access-request-approve-transition.php`
5. Confirm page shows **Controlled Write-Enabled Boundary** for `UNDER_REVIEW -> APPROVED`
6. Confirm permission shown is `access.request.approve`
7. Click **Submit UNDER_REVIEW to APPROVED Transition**
8. Confirm success message:
   `Controlled approval transition completed. Database state was updated.`
9. Confirm result table shows:
   - Result: OK
   - Transition: UNDER_REVIEW -> APPROVED
   - Approval Insert: Applied
   - Database Update: Applied
   - History Write: Applied
10. Click submit again on the same request
11. Confirm second submit is blocked because request is no longer in UNDER_REVIEW state or APPROVED approval row already exists

## 13. SQL Verification Queries

Request verification:

```sql
SELECT
    request_id,
    request_number,
    request_type,
    request_state,
    subject_user_id,
    requested_by_user_id,
    submitted_at,
    decided_at,
    applied_at,
    updated_at
FROM dbo.core_access_requests
WHERE request_id = 4;
```

Expected after successful transition:

```
request_state = APPROVED
submitted_at unchanged
applied_at IS NULL
decided_at IS NOT NULL
updated_at IS NOT NULL
```

Approval verification:

```sql
SELECT
    approval_id,
    request_id,
    approver_user_id,
    approver_capacity,
    decision,
    comment,
    decided_at,
    created_at
FROM dbo.core_access_approvals
WHERE request_id = 4
ORDER BY approval_id DESC;
```

Expected newest row:

```
approver_capacity = OWNER
decision = APPROVED
approver_user_id = 10001
```

History verification:

```sql
SELECT TOP (5)
    history_id,
    user_id,
    request_id,
    change_type,
    entity_type,
    entity_id,
    before_json,
    after_json,
    changed_by_user_id,
    changed_at
FROM dbo.core_access_change_history
WHERE request_id = 4
ORDER BY history_id DESC;
```

Expected newest row:

```
change_type = ACCESS_REQUEST_APPROVED
entity_type = core_access_requests
entity_id = 4
changed_by_user_id = 10001
```

## 14. Duplicate Approval Protection Expectation

After the first successful transition:

- Re-submitting `request_id = 4` must be blocked
- `request_state` must remain `APPROVED`
- `submitted_at` must remain unchanged
- Only one `APPROVED` row should exist in `core_access_approvals` for `request_id = 4`
- No additional `ACCESS_REQUEST_APPROVED` history row should be inserted on blocked duplicate submit

Protection mechanism:

- Pre-update read requires `request_state = UNDER_REVIEW`
- Pre-insert check blocks existing `APPROVED` row in `core_access_approvals`
- UPDATE uses `WHERE request_state = UNDER_REVIEW`
- Transaction rolls back if approval INSERT, request UPDATE, or history INSERT fails

## 15. Rollback Note

No automatic rollback script was created.

If manual reversal is required after testing, it must be separately approved and executed outside this implementation step.

## 16. Syntax Check

```
No syntax errors detected in public_html/erp-access-request-approve-transition.php
```

## 17. Verified Browser Result

Browser URL:
http://localhost:8080/moghare360/erp-access-request-approve-transition.php

Verified result:
- Result: OK
- Transition: UNDER_REVIEW -> APPROVED
- Database Update: Applied
- Approval Insert: Applied
- History Write: Applied
- Request ID: 4
- Request Number: AR-20260620-084634-10001

## 18. Verified SQL Main Request Result

core_access_requests:
- request_id: 4
- request_number: AR-20260620-084634-10001
- request_type: ROLE_GRANT
- request_state: APPROVED
- subject_user_id: 10001
- requested_by_user_id: 10001
- submitted_at: 2026-06-21 15:00:13.874
- decided_at: 2026-06-21 17:23:32.189
- applied_at: NULL
- updated_at: 2026-06-21 17:23:32.189

## 19. Verified Approval Row Result

core_access_approvals:
- approval_id: 1
- request_id: 4
- approver_user_id: 10001
- approver_capacity: OWNER
- decision: APPROVED
- comment: Controlled prototype approval for UNDER_REVIEW to APPROVED
- decided_at: 2026-06-21 17:23:32.186
- created_at: 2026-06-21 17:23:32.186

## 20. Verified SQL History Result

Newest history row:
- history_id: 6
- user_id: 10001
- request_id: 4
- change_type: ACCESS_REQUEST_APPROVED
- entity_type: core_access_requests
- entity_id: 4
- before_json: {"request_state":"UNDER_REVIEW","decided_at":null,"updated_at":"2026-06-21 16:29:50.052"}
- after_json: {"request_state":"APPROVED","decided_at":"2026-06-21 17:23:32.189","updated_at":"2026-06-21 17:23:32.189"}
- changed_by_user_id: 10001
- changed_at: 2026-06-21 17:23:32.190

Previous history rows preserved:
- ACCESS_REQUEST_UNDER_REVIEW
- ACCESS_REQUEST_SUBMITTED

## 21. Verified Duplicate Approval Protection

Duplicate approval test was executed after request_id 4 was already transitioned to APPROVED.

Browser result:
- Blocked
- ERP access request is not in UNDER_REVIEW state.

SQL verification:
- request_state remained APPROVED
- approved_approval_count remained 1
- approved_history_count remained 1
- No additional APPROVED approval row was inserted.
- No additional ACCESS_REQUEST_APPROVED history row was inserted.

Confirmed protection:
- The approval transition is not repeatable after the request leaves UNDER_REVIEW state.
- State-based concurrency guard works as expected.
- Duplicate browser submission does not create duplicate approval or workflow history.

## 22. Final Result

Controlled write-enabled UNDER_REVIEW -> APPROVED transition was implemented, executed through browser, verified by SQL, approval insert was confirmed, and duplicate approval protection was confirmed.
