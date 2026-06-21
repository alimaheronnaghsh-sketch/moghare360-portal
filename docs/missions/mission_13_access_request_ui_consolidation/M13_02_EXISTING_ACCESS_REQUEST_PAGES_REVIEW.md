# Existing Access Request Pages Review

## Mission 5 Read-Only Workflow Viewer Exists
File:
- public_html/erp-access-request-workflow-readonly.php

Purpose:
- fixed request_id = 4
- SELECT-only workflow viewer
- timeline, items, approvals, state-only apply verification

Mission 13 links to this viewer as a read-only reference only.

## Mission 12 Integration Read-Only Test Exists
Files:
- tools/test-erp-auth-permission-workflow-integration.php
- public_html/erp-auth-permission-workflow-readonly-test.php

Purpose:
- validate Auth Context + Permission Guard + workflow data together
- confirm request_id = 4 / APPLIED / timeline complete / core_user_roles count = 2

## Current Workflow Cycle Exists
Locked workflow cycle for request 4:

- DRAFT -> SUBMITTED
- SUBMITTED -> UNDER_REVIEW
- UNDER_REVIEW -> APPROVED
- APPROVED -> APPLIED

History types confirmed:
- ACCESS_REQUEST_SUBMITTED
- ACCESS_REQUEST_UNDER_REVIEW
- ACCESS_REQUEST_APPROVED
- ACCESS_REQUEST_APPLIED

## APPLIED Is State-Only
APPLIED does not perform Real Assignment.

core_user_roles count for user_id 10001 remains 2.
item_decision remains unchanged.

## No Existing Page Should Be Modified in Mission 13
Mission 13 creates:
- public_html/erp-access-request-admin.php

Mission 13 does not modify:
- workflow transition pages
- Mission 5 viewer
- Mission 8/10/11/12 helpers or tests
- login, config, or legacy files

## Mission 13 Decision
Consolidated admin UI is additive only.
