# Action Guard Test Matrix

Test user:
- user_id = 10001
- username = mahin.paradigm.owner
- roles = owner, system_admin

All tests:
- write performed = no
- read_only_evaluation = true

| Action Key | Expected Permission | Expected Result | Real or Placeholder | Write Performed |
|---|---|---|---|---|
| access.request.view | access.request.view_all | OK if permission present | Real | No |
| access.request.list | access.request.view_all | OK if permission present | Real | No |
| access.request.submit | access.request.create | OK if permission present | Real | No |
| access.request.review | access.request.approve | OK if permission present | Real | No |
| access.request.approve | access.request.approve | OK | Real | No |
| access.request.apply | access.request.apply | OK | Real | No |
| admin.dashboard.view | placeholder_admin_dashboard_view | PLACEHOLDER or DOCUMENTED | Placeholder | No |
| admin.workflow.viewer.view | access.request.view_all | OK if permission present | Real | No |
| admin.auth.context.test.view | placeholder_admin_auth_context_test_view | PLACEHOLDER | Placeholder | No |

## CLI Required Checks
CLI test must confirm:

- access.request.approve = OK
- access.request.apply = OK
- access.request.view = OK
- access.request.list = OK
- admin.workflow.viewer.view = OK
- admin.dashboard.view = PLACEHOLDER or DOCUMENTED
- No write performed = OK
- Overall = OK

## Browser Required Checks
Browser test must confirm:

- All mapped actions displayed
- Real permissions show OK
- Placeholder permissions show PLACEHOLDER
- No write performed = OK
- Overall Status = OK

## Failure Rule
If any required real permission check fails, overall result must be FAIL.

Placeholder permissions must not cause overall failure when documented correctly.
