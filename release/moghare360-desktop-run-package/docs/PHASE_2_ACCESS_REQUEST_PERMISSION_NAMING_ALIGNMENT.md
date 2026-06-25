# MOGHARE360 ERP - Phase 2 Access Request Permission Naming Alignment

## 1. Purpose
Lock the real permission naming pattern before continuing Access Request workflow transitions.

## 2. Confirmed Existing Permissions
From SSMS:

- access.request.apply
- access.request.approve
- access.request.create
- access.request.view_all

## 3. Missing Prototype Permissions
The following prototype-style permissions are not present in dbo.core_permissions:

- access_request.submit
- access_request.review

## 4. Risk
The Phase 2 transition prototype used access_request.submit.
That key is not present in core_permissions.
The previous DRAFT → SUBMITTED browser test succeeded because the current permission layer allows platform-owner prototype access.
This is acceptable for controlled prototype testing only, but it is not acceptable for sellable workflow permission design.

## 5. Decision Required
Before implementing SUBMITTED → UNDER_REVIEW, choose one naming standard:

Option A:
Use existing database permission style:
- access.request.create
- access.request.approve
- access.request.apply
- access.request.view_all

Option B:
Create new normalized workflow permissions later through controlled SQL migration:
- access_request.submit
- access_request.review

Current recommendation:
Use existing database style for the next controlled step.

Recommended mapping:
- DRAFT → SUBMITTED should align to access.request.create or a future explicit submit permission.
- SUBMITTED → UNDER_REVIEW should align to access.request.approve for now.
- APPROVED → APPLIED should align to access.request.apply.

## 6. Current Blocker Status

- Permission naming blocker: resolved.
- Role coverage blocker: resolved.
- Next blocker: workflow engine transition rule for SUBMITTED -> UNDER_REVIEW.

## 7. Confirmed Permission Decision

Option A approved for controlled continuation:

Use existing database permission style from `dbo.core_permissions`.

Confirmed mapping for next workflow steps:
- DRAFT -> SUBMITTED: `access.request.create` or a future explicit submit permission
- SUBMITTED -> UNDER_REVIEW: `access.request.approve`
- APPROVED -> APPLIED: `access.request.apply`

## 9. Confirmed Role Coverage for access.request.approve

SSMS confirmed that permission `access.request.approve` is active and assigned to these active roles:

- department_manager
- operations_manager
- owner
- system_admin

Confirmed key roles for controlled prototype continuation:
- owner
- system_admin

Conclusion:
`access.request.approve` has valid role coverage for the next controlled workflow step.

## 10. Sign-Off
No runtime behavior was changed.
No database data or schema was changed.
This document only records the permission naming blocker and recommended alignment.
