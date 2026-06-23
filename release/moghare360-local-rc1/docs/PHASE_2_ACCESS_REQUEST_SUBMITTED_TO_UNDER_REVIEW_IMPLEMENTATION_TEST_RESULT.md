# Phase 2 Access Request SUBMITTED to UNDER_REVIEW Implementation Test Result

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: SUBMITTED to UNDER_REVIEW Write Implementation Test Result
Status: Implementation Completed - Manual Test Verified
Scope: Controlled Write Transition Only

## 1. Files Inspected

- `docs/PHASE_2_ACCESS_REQUEST_SUBMITTED_TO_UNDER_REVIEW_READINESS.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `docs/PHASE_2_WORKFLOW_ENGINE_SUBMITTED_TO_UNDER_REVIEW_TEST_RESULT.md`
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `includes/erp-permission-check.php`
- `includes/erp-workflow-engine.php`
- `includes/erp-config-loader.php`
- `public_html/erp-access-request-transition.php`

## 2. Files Modified

- `public_html/erp-access-request-review-transition.php` (created)
- `docs/PHASE_2_ACCESS_REQUEST_SUBMITTED_TO_UNDER_REVIEW_IMPLEMENTATION_TEST_RESULT.md`

## 3. New Transition Page Selected

```
public_html/erp-access-request-review-transition.php
```

Runtime URL after copy:

```
http://localhost:8080/moghare360/erp-access-request-review-transition.php
```

The existing submit transition page was not modified:

```
public_html/erp-access-request-transition.php
```

## 4. Exact Safety Chain Implemented

POST handling for `SUBMITTED -> UNDER_REVIEW` follows this order:

1. Browser form POST with `request_id`, `csrf_token`, `transition_action`
2. CSRF validation: `erp_csrf_require_valid_token('access_request_review', ...)`
3. Auth check: `erp_auth_require_current_user()`
4. Permission check: `erp_permission_require($context, 'access.request.approve')`
5. Workflow engine: `erp_workflow_require_transition('access_request', 'SUBMITTED', 'UNDER_REVIEW')`
6. Workflow result build: `erp_workflow_build_transition_result(...)`
7. SQL transaction open
8. Read target request row
9. State re-check: current `request_state` must be `SUBMITTED`
10. UPDATE `core_access_requests` (`request_state`, `updated_at` only)
11. INSERT `core_access_change_history`
12. Commit transaction only if UPDATE and history INSERT succeed
13. Rollback on any failure

## 5. Permission Used

```
access.request.approve
```

## 6. Workflow Transition Used

```
SUBMITTED -> UNDER_REVIEW
```

## 7. SQL Tables Touched

- `dbo.core_access_requests` - UPDATE
- `dbo.core_access_change_history` - INSERT

No other tables are touched.

## 8. Columns Updated

### `dbo.core_access_requests`
- `request_state` = `UNDER_REVIEW`
- `updated_at` = `SYSDATETIME()`

Not updated:
- `submitted_at`

UPDATE is guarded by:

```
WHERE request_id = ?
  AND request_state = N'SUBMITTED'
```

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

## 9. History Row Shape

```
user_id = subject_user_id from the target request
request_id = target request_id
change_type = ACCESS_REQUEST_UNDER_REVIEW
entity_type = core_access_requests
entity_id = request_id
before_json = {"request_state":"SUBMITTED","updated_at":"<previous or null>"}
after_json = {"request_state":"UNDER_REVIEW","updated_at":"<timestamp>"}
changed_by_user_id = current authenticated user_id
changed_at = SYSDATETIME()
```

## 10. Explicit Forbidden Actions Not Performed

This implementation did not:

- Modify `public_html/erp-access-request-transition.php`
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
- Insert into `core_access_approvals`
- Touch `core_user_roles` or equivalent role assignment tables
- Insert into `core_audit_logs`

## 11. Manual Browser Test Steps

1. Copy `public_html/erp-access-request-review-transition.php` to runtime:
   `C:\xampp\htdocs\moghare360\erp-access-request-review-transition.php`
2. Confirm candidate request `request_id = 4` is currently `SUBMITTED`
3. Open:
   `http://localhost:8080/moghare360/erp-access-request-review-transition.php`
4. Confirm page shows **Controlled Write-Enabled Boundary** for `SUBMITTED -> UNDER_REVIEW`
5. Confirm permission shown is `access.request.approve`
6. Click **Submit SUBMITTED to UNDER_REVIEW Transition**
7. Confirm success message:
   `Controlled review transition completed. Database state was updated.`
8. Confirm result table shows:
   - Result: OK
   - Transition: SUBMITTED -> UNDER_REVIEW
   - Database Update: Applied
   - History Write: Applied
9. Click submit again on the same request
10. Confirm second submit is blocked because request is no longer in SUBMITTED state

## 12. SQL Verification Queries

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
    updated_at
FROM dbo.core_access_requests
WHERE request_id = 4;
```

Expected after successful transition:

```
request_state = UNDER_REVIEW
submitted_at unchanged from prior SUBMITTED transition
updated_at IS NOT NULL
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
change_type = ACCESS_REQUEST_UNDER_REVIEW
entity_type = core_access_requests
entity_id = 4
changed_by_user_id = 10001
```

## 13. Duplicate Submit Protection Expectation

After the first successful transition:

- Re-submitting `request_id = 4` must be blocked
- `request_state` must remain `UNDER_REVIEW`
- `submitted_at` must remain unchanged
- No additional `ACCESS_REQUEST_UNDER_REVIEW` history row should be inserted on blocked duplicate submit

Protection mechanism:

- Pre-update read requires `request_state = SUBMITTED`
- UPDATE uses `WHERE request_state = SUBMITTED`
- Transaction rolls back if UPDATE or history INSERT fails

## 14. Rollback Note

No automatic rollback script was created.

If manual reversal is required after testing, it must be separately approved and executed outside this implementation step.

## 15. Syntax Check

```
No syntax errors detected in public_html/erp-access-request-review-transition.php
```

## 16. Verified Browser Result

Browser URL:
http://localhost:8080/moghare360/erp-access-request-review-transition.php

Verified result:
- Result: OK
- Transition: SUBMITTED -> UNDER_REVIEW
- Database Update: Applied
- History Write: Applied
- Request ID: 4
- Request Number: AR-20260620-084634-10001

## 17. Verified SQL History Result

core_access_change_history newest row:
- history_id: 5
- user_id: 10001
- request_id: 4
- change_type: ACCESS_REQUEST_UNDER_REVIEW
- entity_type: core_access_requests
- entity_id: 4
- before_json: {"request_state":"SUBMITTED","updated_at":"2026-06-21 15:00:13.874"}
- after_json: {"request_state":"UNDER_REVIEW","updated_at":"2026-06-21 16:29:50.052"}
- changed_by_user_id: 10001
- changed_at: 2026-06-21 16:29:50.052

Previous history row preserved:
- history_id: 4
- change_type: ACCESS_REQUEST_SUBMITTED

## 18. Verified SQL Main Request Result

core_access_requests:
- request_id: 4
- request_number: AR-20260620-084634-10001
- request_type: ROLE_GRANT
- request_state: UNDER_REVIEW
- subject_user_id: 10001
- requested_by_user_id: 10001
- submitted_at: 2026-06-21 15:00:13.874
- updated_at: 2026-06-21 16:29:50.052

## 19. Verified Duplicate Submit Protection

Duplicate submit test was executed after request_id 4 was already transitioned to UNDER_REVIEW.

Browser result:
- Blocked
- ERP access request is not in SUBMITTED state.

SQL verification:
- request_state remained UNDER_REVIEW
- submitted_at remained 2026-06-21 15:00:13.874
- updated_at remained 2026-06-21 16:29:50.052
- under_review_history_count remained 1
- No additional ACCESS_REQUEST_UNDER_REVIEW history row was inserted.

Confirmed protection:
- The transition is not repeatable after the request leaves SUBMITTED state.
- State-based concurrency guard works as expected.
- Duplicate browser submission does not create duplicate workflow history.

## 20. Final Result

Controlled write-enabled SUBMITTED -> UNDER_REVIEW transition was implemented, executed through browser, verified by SQL, and duplicate submit protection was confirmed.
