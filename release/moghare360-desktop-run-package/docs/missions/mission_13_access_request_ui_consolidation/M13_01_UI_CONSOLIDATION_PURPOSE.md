# UI Consolidation Purpose

## Why Access Request UI Consolidation Is Needed
After Mission 8 Auth Context, Mission 10 Permission Guard, and Mission 12 integration validation, the project needs one consolidated admin page to view Access Request data in a controlled read-only layout.

This reduces scattered read-only navigation and prepares a single admin surface for future guarded actions.

## What Admin Controlled UI Means
Admin Controlled UI in Mission 13 means:

- authenticated context is displayed
- permission guard results are displayed
- access request list and detail are visible
- workflow timeline is visible
- no workflow action is executed

## Why This Page Is Read/List/View Only
Mission 13 is consolidation, not execution.

The page must:

- list all access requests
- show selected request detail
- show items, approvals, and timeline
- remain SELECT only

## Why Workflow Writes Are Not Allowed in Mission 13
Workflow transitions require future integration of:

- Permission Guard require()
- CSRF protection
- audit strategy
- explicit mission approval

Mission 13 does not implement submit, review, approve, or apply execution.

## Why Real Assignment Remains Forbidden
Request 4 reached APPLIED as state-only.

Mission 13 must continue to display:

- Real Assignment = NOT PERFORMED
- core_user_roles write = FORBIDDEN
- item_decision update = FORBIDDEN

## Mission 13 Boundary
One new admin UI page only.
No mutation.
No existing page modification.
