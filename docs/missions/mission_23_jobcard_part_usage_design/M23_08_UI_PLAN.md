# UI Plan

## Purpose
This document defines the future UI plan for JobCard part usage implementation.

## Mission 23 Boundary
No UI file is created in Mission 23.

## Future UI Pages (Mission 24)

| File | Purpose |
|------|---------|
| public_html/erp-jobcard-part-use.php | Controlled register part usage + ISSUE |
| public_html/erp-jobcard-part-readonly-list.php | Read-only list of part usages |

## Part Use Page Sections (Future — erp-jobcard-part-use.php)
1. jobcard_id selection (validated active JobCard; default JobCard ID = 1 for prototype)
2. service_operation_id (optional; filtered to same JobCard)
3. part_id selection (active parts from erp_parts)
4. stock_location_id (default MAIN if exists)
5. quantity (positive decimal)
6. CSRF token
7. Submit → transaction: usage + history + ISSUE movement
8. Success message (e.g. Part Usage Registered OK)

## Part Use Page — Must Not Include (Mission 24)
- Invoice panel
- Payment panel
- Purchase request form
- Finance posting controls
- Price override fields (unless future mission adds)

## Read-Only List Page (Future)
Display:
- part_usage_id
- jobcard_id
- service_operation_id
- part_code / part_name (join)
- stock_location_id / location_code
- quantity
- usage_status
- created_at
- created_by_user_id

Optional filter: jobcard_id

## Permission Boundary (Future)
- jobcard.part.use + stock.issue.create on use POST
- jobcard.part.list on list page
- Auth Context on all pages
- CSRF on POST

## Explicit Non-Goals (Mission 24 UI)
- Return / reversal form (may defer unless M24 charter includes)
- Customer portal visibility
- Legacy staff-inventory page changes

## Mission 23 Boundary
UI planned only. No PHP operational files.

## Final UI Decision
Two-page Mission 24 prototype: controlled use + read-only list.
