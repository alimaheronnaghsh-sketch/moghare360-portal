# Testing Plan

## Purpose
This document defines the future test plan for Service Operation implementation in Mission 20.

## Mission 19 Boundary
Testing is planned only.
No runnable test file is created in Mission 19.
No SQL is executed in Mission 19.

## Future Tests (Mission 20)

### 1. SQL Object Existence
Validate:
- dbo.erp_service_operations exists
- dbo.erp_service_operation_change_history exists
- Required columns present
- Primary keys and foreign keys present
- Indexes on jobcard_id and service_status

### 2. Create Service Operation for JobCard ID = 1
Controlled test:
- Use existing active JobCard with jobcard_id = 1 (or documented test JobCard)
- Create Service Operation with required fields
- Confirm service_operation_id returned
- Confirm row exists in erp_service_operations

### 3. List Page OK
- Open erp-service-operation-readonly-list.php
- Confirm created Service Operation appears
- Confirm Auth Context and Permission Guard enforced

### 4. Detail Page OK
- Open erp-service-operation-detail.php for created service_operation_id
- Confirm title, status, JobCard link, assignee, and timestamps display

### 5. History Contains SERVICE_OPERATION_CREATED
- Query erp_service_operation_change_history
- Confirm action_code = SERVICE_OPERATION_CREATED
- Confirm new_status matches initial service_status
- Confirm changed_by_user_id populated

### 6. No Inventory Write
Confirm no rows written to Inventory tables during create or status change.

### 7. No Finance Write
Confirm no rows written to Finance tables during create or status change.

### 8. No QC Write
Confirm no QC tables or checklist rows written.

### 9. No Delivery Write
Confirm no Delivery tables or handover rows written.

### 10. No Forbidden Files Changed
Confirm no changes to:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy operational files

## Additional Recommended Tests (Mission 20)

### Permission Guard Test
Unauthorized user cannot create Service Operation.

### CSRF Test
POST create rejects missing or invalid CSRF token.

### JobCard Validation Test
Create rejects missing jobcard_id, unknown jobcard_id, and inactive JobCard.

### Transaction Rollback Test
Simulated failure rolls back operation insert and history insert together.

## Mission 19 Test Result
Mission 19 performs no executable tests.
All tests above are deferred to Mission 20.

## Final Testing Decision
Ten core future tests locked; boundary tests confirm no cross-domain writes and no forbidden file changes.
