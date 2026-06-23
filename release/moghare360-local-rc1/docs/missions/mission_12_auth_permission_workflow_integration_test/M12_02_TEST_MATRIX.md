# Mission 12 Test Matrix

Test user:
- user_id = 10001
- username = mahin.paradigm.owner

Test request:
- request_id = 4
- request_number = AR-20260620-084634-10001
- request_type = ROLE_GRANT
- request_state = APPLIED

| Check | Expected Result | Write Performed |
|---|---|---|
| current user load | user_id 10001 / mahin.paradigm.owner | No |
| roles load | owner, system_admin | No |
| permissions load | count > 0 | No |
| guard approve | OK | No |
| guard apply | OK | No |
| request_id 4 visibility | request visible | No |
| request_state APPLIED | APPLIED | No |
| workflow timeline complete | all 4 history types present | No |
| core_user_roles unchanged | count = 2 | No |
| Real Assignment not performed | NOT PERFORMED | No |
| no write performed | OK | No |

## Required Workflow Timeline Types
- ACCESS_REQUEST_SUBMITTED
- ACCESS_REQUEST_UNDER_REVIEW
- ACCESS_REQUEST_APPROVED
- ACCESS_REQUEST_APPLIED

## CLI Expected Output
```
M12 AUTH + PERMISSION + WORKFLOW INTEGRATION TEST
user_id = 10001
username = mahin.paradigm.owner
roles = owner, system_admin
permissions loaded = [count > 0]
guard access.request.approve = OK
guard access.request.apply = OK
request_id = 4
request_state = APPLIED
workflow timeline = COMPLETE
core_user_roles count = 2
Real Assignment = NOT PERFORMED
No write performed = OK
Overall: OK
```

## Failure Rule
If any required check fails, overall result must be FAIL.

## Mission 12 Boundary
Integration test only.
No mutation.
