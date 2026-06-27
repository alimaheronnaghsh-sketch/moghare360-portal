# Admin UI Layout Plan

## Page
public_html/erp-access-request-admin.php

## Layout Sections

### Header
- warning banner
- page title
- Mission 13 read-only note

### Auth/Permission Summary
- PHP version
- ODBC extension status
- connection status
- user_id, username, roles
- permissions count
- guard access.request.view
- guard access.request.list
- guard access.request.approve
- guard access.request.apply

### Access Request Admin Summary
- Viewer Mode = READ ONLY
- Workflow Write = DISABLED
- Real Assignment = NOT PERFORMED
- No Form Submit
- No Direct Action Execution

### Access Request List
- SELECT all rows from core_access_requests
- ORDER BY request_id DESC
- detail link per row using GET request_id

### Selected Request Detail
- default request_id = 4
- GET request_id integer only
- SELECT TOP 1 detail query

### Request Items
- SELECT items for selected request_id
- ORDER BY item_id

### Approval Result
- SELECT approvals for selected request_id
- ORDER BY approval_id

### Workflow Timeline
- SELECT history for selected request_id
- ORDER BY changed_at, history_id
- for request_id = 4 show Timeline status = COMPLETE when all required history types exist

### Read-Only Links
- detail link to same page
- viewer link to erp-access-request-workflow-readonly.php when file exists
- no submit/review/approve/apply links

### State-Only Warning
- APPLIED = State-Only
- Real Assignment = NOT PERFORMED
- core_user_roles write = FORBIDDEN
- item_decision update = FORBIDDEN

### Overall Status
Overall Status = OK only if:
- connection OK
- user loaded
- guard list/view OK
- request list loaded
- selected request loaded
- no write performed
- no form exists

## Mission 13 Boundary
Layout is read/list/view only.
