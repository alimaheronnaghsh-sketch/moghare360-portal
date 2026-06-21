# Phase 2 Access Request APPROVED to APPLIED Implementation Test Result

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: APPROVED to APPLIED State-Only Write Implementation Test Result
Status: Implementation Completed - Manual Test Verified
Scope: Controlled State-Only Apply Write Transition Only

## 1. Files Inspected

- `docs/PHASE_2_ACCESS_REQUEST_APPLY_DESIGN_LOCK.md`
- `docs/PHASE_2_ACCESS_REQUEST_APPROVED_TO_APPLIED_READINESS.md`
- `docs/PHASE_2_WORKFLOW_ENGINE_APPROVED_TO_APPLIED_TEST_RESULT.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `includes/erp-permission-check.php`
- `includes/erp-workflow-engine.php`
- `includes/erp-config-loader.php`
- `public_html/erp-access-request-approve-transition.php`

## 2. Files Modified

- `public_html/erp-access-request-apply-transition.php` (created)
- `docs/PHASE_2_ACCESS_REQUEST_APPROVED_TO_APPLIED_IMPLEMENTATION_TEST_RESULT.md`

## 3. New State-Only Apply Transition Page Selected

```
public_html/erp-access-request-apply-transition.php
```

Runtime URL after copy:

```
http://localhost:8080/moghare360/erp-access-request-apply-transition.php
```

Existing pages not modified:

```
public_html/erp-access-request-transition.php
public_html/erp-access-request-review-transition.php
public_html/erp-access-request-approve-transition.php
```

## 4. Exact Safety Chain Implemented

POST handling for `APPROVED -> APPLIED` follows this order:

1. Browser form POST with `request_id`, `csrf_token`, `transition_action`
2. CSRF validation: `erp_csrf_require_valid_token('access_request_apply', ...)`
3. Auth check: `erp_auth_require_current_user()`
4. Permission check: `erp_permission_require($context, 'access.request.apply')`
5. Workflow engine: `erp_workflow_require_transition('access_request', 'APPROVED', 'APPLIED')`
6. Workflow result build: `erp_workflow_build_transition_result(...)`
7. SQL transaction open
8. Read target request row
9. State re-check: current `request_state` must be `APPROVED`
10. Apply re-check: `applied_at` must be NULL
11. Apply re-check: `applied_by_user_id` must be NULL
12. Duplicate apply re-check: no existing `ACCESS_REQUEST_APPLIED` row in `core_access_change_history`
13. UPDATE `core_access_requests`
14. INSERT `core_access_change_history`
15. Commit transaction only if request UPDATE and history INSERT succeed
16. Rollback on any failure

## 5. Permission Used

```
access.request.apply
```

## 6. Workflow Transition Used

```
APPROVED -> APPLIED
```

## 7. SQL Tables Touched

- `dbo.core_access_requests` - UPDATE
- `dbo.core_access_change_history` - INSERT

No other tables are touched.

## 8. Columns Updated

### `dbo.core_access_requests`
- `request_state` = `APPLIED`
- `applied_at` = `SYSDATETIME()`
- `applied_by_user_id` = current authenticated user_id
- `updated_at` = `SYSDATETIME()`

Not updated:
- `submitted_at`
- `decided_at`

UPDATE is guarded by:

```
WHERE request_id = ?
  AND request_state = N'APPROVED'
  AND applied_at IS NULL
  AND applied_by_user_id IS NULL
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
change_type = ACCESS_REQUEST_APPLIED
entity_type = core_access_requests
entity_id = request_id
before_json = {"request_state":"APPROVED","applied_at":null,"applied_by_user_id":null,"updated_at":"<previous>"}
after_json = {"request_state":"APPLIED","applied_at":"<timestamp>","applied_by_user_id":"<actor>","updated_at":"<timestamp>"}
changed_by_user_id = current authenticated user_id
changed_at = SYSDATETIME()
```

## 10. Explicit Forbidden Actions Not Performed

This implementation did not:

- Modify `public_html/erp-access-request-transition.php`
- Modify `public_html/erp-access-request-review-transition.php`
- Modify `public_html/erp-access-request-approve-transition.php`
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
- Change `item_decision`
- Touch `core_user_roles`
- Insert into `core_user_roles`
- Update `core_user_roles`
- Update `submitted_at`
- Update `decided_at`
- Update approved approval rows in `core_access_approvals`

## 11. Manual Browser Test Steps

1. Copy `public_html/erp-access-request-apply-transition.php` to runtime:
   `C:\xampp\htdocs\moghare360\erp-access-request-apply-transition.php`
2. Confirm candidate request `request_id = 4` is currently `APPROVED`
3. Confirm `applied_at` is NULL and `applied_by_user_id` is NULL for `request_id = 4`
4. Confirm no `ACCESS_REQUEST_APPLIED` history row exists yet for `request_id = 4`
5. Open:
   `http://localhost:8080/moghare360/erp-access-request-apply-transition.php`
6. Confirm page shows **Controlled Write-Enabled Boundary** for `APPROVED -> APPLIED`
7. Confirm permission shown is `access.request.apply`
8. Click **Submit APPROVED to APPLIED Transition**
9. Confirm success message:
   `Controlled state-only apply transition completed. Database state was updated.`
10. Confirm result table shows:
    - Result: OK
    - Transition: APPROVED -> APPLIED
    - Database Update: Applied
    - History Write: Applied
11. Click submit again on the same request
12. Confirm second submit is blocked because request is no longer in APPROVED state, apply fields are set, or ACCESS_REQUEST_APPLIED history already exists

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
    decided_at,
    applied_at,
    applied_by_user_id,
    updated_at
FROM dbo.core_access_requests
WHERE request_id = 4;
```

Expected after successful transition:

```
request_state = APPLIED
submitted_at unchanged
decided_at unchanged
applied_at IS NOT NULL
applied_by_user_id = 10001
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
change_type = ACCESS_REQUEST_APPLIED
entity_type = core_access_requests
entity_id = 4
changed_by_user_id = 10001
```

Duplicate apply protection verification:

```sql
SELECT COUNT(*) AS applied_history_count
FROM dbo.core_access_change_history
WHERE request_id = 4
  AND change_type = N'ACCESS_REQUEST_APPLIED';
```

Expected after first successful transition:

```
applied_history_count = 1
```

## 13. Duplicate Apply Protection Expectation

After the first successful transition:

- Re-submitting `request_id = 4` must be blocked
- `request_state` must remain `APPLIED`
- `submitted_at` must remain unchanged
- `decided_at` must remain unchanged
- Only one `ACCESS_REQUEST_APPLIED` history row should exist for `request_id = 4`
- No additional `ACCESS_REQUEST_APPLIED` history row should be inserted on blocked duplicate submit

Protection mechanism:

- Pre-update read requires `request_state = APPROVED`
- Pre-update read requires `applied_at IS NULL`
- Pre-update read requires `applied_by_user_id IS NULL`
- Pre-history check blocks existing `ACCESS_REQUEST_APPLIED` row for the same `request_id`
- UPDATE uses `WHERE request_state = APPROVED AND applied_at IS NULL AND applied_by_user_id IS NULL`
- Transaction rolls back if request UPDATE or history INSERT fails

## 14. Confirmation that core_user_roles is Not Touched

`core_user_roles` was not read for write purposes.

No insert into `core_user_roles` was performed.

No update to `core_user_roles` was performed.

No role assignment was performed.

## 15. Rollback Note

No automatic rollback script was created.

If manual reversal is required after testing, it must be separately approved and executed outside this implementation step.

## 16. Syntax Check

```powershell
C:\xampp\php\php.exe -l C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-access-request-apply-transition.php
```

## 17. Verified Browser Result

Browser URL:
http://localhost:8080/moghare360/erp-access-request-apply-transition.php

Verified result:
- Result: OK
- Transition: APPROVED -> APPLIED
- Database Update: Applied
- History Write: Applied
- Request ID: 4
- Request Number: AR-20260620-084634-10001

## 18. Verified SQL Main Request Result

core_access_requests:
- request_id: 4
- request_number: AR-20260620-084634-10001
- request_state: APPLIED
- submitted_at: 2026-06-21 15:00:13.874
- decided_at: 2026-06-21 17:23:32.189
- applied_at: 2026-06-21 18:16:25.031
- applied_by_user_id: 10001
- updated_at: 2026-06-21 18:16:25.031

Confirmed:
- submitted_at was not changed
- decided_at was not changed
- applied_at was set
- applied_by_user_id was set to 10001

## 19. Verified SQL History Result

applied_history_count:
- 1

Newest history row:
- history_id: 7
- request_id: 4
- change_type: ACCESS_REQUEST_APPLIED
- before_json: {"request_state":"APPROVED","applied_at":null,"applied_by_user_id":null,"updated_at":"2026-06-21 17:23:32.189"}
- after_json: {"request_state":"APPLIED","applied_at":"2026-06-21 18:16:25.031","applied_by_user_id":10001,"updated_at":"2026-06-21 18:16:25.031"}
- changed_by_user_id: 10001
- changed_at: 2026-06-21 18:16:25.031

Previous workflow history rows preserved:
- ACCESS_REQUEST_SUBMITTED
- ACCESS_REQUEST_UNDER_REVIEW
- ACCESS_REQUEST_APPROVED

## 20. Verified core_user_roles Result

core_user_roles:
- user_role_count for user_id 10001 remained 2
- existing user_role_id 1 remained unchanged
- existing user_role_id 2 remained unchanged
- no new role assignment row was inserted
- no role assignment row was updated
- no revoked_at value was changed
- no granted_by_request_id value was changed

Confirmed:
State-only apply did not touch core_user_roles.

## 21. Verified Duplicate Apply Protection

Duplicate apply test was executed after request_id 4 was already transitioned to APPLIED.

Browser result:
- Blocked
- ERP access request is not in APPROVED state.

SQL verification:
- request_state remained APPLIED
- applied_history_count remained 1
- user_role_count remained 2
- no additional ACCESS_REQUEST_APPLIED history row was inserted
- no core_user_roles row was inserted or updated

Confirmed protection:
- The apply transition is not repeatable after the request leaves APPROVED state.
- State-based concurrency guard works as expected.
- Duplicate browser submission does not create duplicate workflow history.
- Duplicate browser submission does not touch core_user_roles.

## 22. Final Result

Controlled state-only APPROVED -> APPLIED transition was implemented, executed through browser, verified by SQL, history insert was confirmed, duplicate apply protection was confirmed, and core_user_roles remained untouched.
