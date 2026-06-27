# UI Plan

## Purpose
This document defines the future UI plan for Service Operation foundation.

## Mission 19 Boundary
No UI file is created in Mission 19.

## Future UI Pages (Mission 20)
Planned PHP pages:

| File | Purpose |
|------|---------|
| public_html/erp-service-operation-create.php | Create Service Operation under JobCard |
| public_html/erp-service-operation-readonly-list.php | Read-only list of Service Operations |
| public_html/erp-service-operation-detail.php | Read-only detail with history timeline |

## Create UI Sections (Future)
Future create page should include:

1. JobCard selection or jobcard_id input (validated against active JobCard)
2. Service title (required)
3. Service description (optional)
4. Assigned technician placeholder (optional; assigned_to_user_id nullable)
5. Initial status (default DRAFT; read-only or hidden in prototype)
6. CSRF token
7. Create result with service_operation_id

## Read-Only List UI (Future)
Future list page should show:

- service_operation_id
- jobcard_id
- jobcard_number (join from JobCard)
- service_title
- service_status
- assigned_to_user_id or assignee display name
- created_at
- is_active

Optional filters:
- jobcard_id
- service_status
- assigned_to_user_id

## Detail UI (Future)
Future detail page should show:

- Service Operation header (id, title, status)
- Parent JobCard summary (read-only link)
- Service description
- Assignee (or "Unassigned")
- Timestamps (created_at, updated_at)
- History timeline (action_code, old_status, new_status, changed_by, changed_at, change_note)

## Permission Boundary (Future)
Future UI must require:
- Auth Context on all pages
- Permission Guard:
  - service.operation.create on create page (POST)
  - service.operation.list on list page
  - service.operation.view on detail page
- CSRF for all POST writes
- Audit / history on successful writes

## Explicit Non-Goals (UI)
Future UI must not include in initial Mission 20 scope:
- Inventory parts picker
- Finance / invoice panels
- QC checklist forms
- Delivery handover forms
- JobCard status change controls from Service Operation pages

## Mission 19 Boundary
UI is planned only.
No PHP operational file is created.
No page is deployed.

## Final UI Decision
Three-page prototype pattern (create, list, detail) aligned with JobCard foundation (M16/M17).
