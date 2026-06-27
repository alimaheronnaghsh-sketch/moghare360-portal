# Access Request Action Map

## Purpose
This document maps Access Request actions to future Permission Guard requirements.

## Action Map

| Action Key | Read/Write | Required Permission | Requires CSRF | Requires Workflow State | Requires Audit | Allowed Current State | Target Table | Forbidden Direct Write |
|---|---|---|---|---|---|---|---|---|
| access.request.create | Write | access.request.create | Yes | No | Yes | N/A | core_access_requests | Yes |
| access.request.submit | Write | access.request.create or access.request.submit | Yes | Yes | Yes | DRAFT | core_access_requests | Yes |
| access.request.review | Write | access.request.approve | Yes | Yes | Yes | SUBMITTED | core_access_requests | Yes |
| access.request.approve | Write | access.request.approve | Yes | Yes | Yes | UNDER_REVIEW | core_access_requests / core_access_approvals | Yes |
| access.request.apply | Write | access.request.apply | Yes | Yes | Yes | APPROVED | core_access_requests | Yes |
| access.request.view | Read | access.request.view_all | No | No | Optional | Any | core_access_requests | Yes |
| access.request.list | Read | access.request.view_all | No | No | Optional | Any | core_access_requests | Yes |
| access.request.readonly_viewer | Read | access.request.view_all | No | No | Optional | Any | core_access_requests / core_access_change_history | Yes |

## Submit Naming Mismatch
Potential mismatch exists:

- access_request.submit
- access.request.submit
- access.request.create

Preferred production naming:
- dot-separated permission keys
- access.request.submit if a separate submit permission is needed
- reuse access.request.create if submit remains part of create permission

## Future Mission Requirement
Submit naming mismatch must be resolved in a future mission before production enforcement.

## Mission 9 Boundary
No permission is created.
No permission key is modified.
No database write is performed.
