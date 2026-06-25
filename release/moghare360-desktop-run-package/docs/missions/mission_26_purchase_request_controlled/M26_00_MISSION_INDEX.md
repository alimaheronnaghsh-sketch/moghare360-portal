# Mission 26 - Purchase Request Controlled Prototype

## Mission Goal
Implement controlled purchase request create, list, and detail per Mission 25 design.

## Dependencies
- Mission 25 = Purchase Approval Foundation Design (completed)
- Mission 24 = JobCard Part Usage Controlled Prototype (completed)
- Mission 20, 22 foundations available

## Created Files
- public_html/sql/sqlserver/mission_26_purchase_request_foundation.sql
- public_html/erp-purchase-request-create.php
- public_html/erp-purchase-request-readonly-list.php
- public_html/erp-purchase-request-detail.php
- tools/test-erp-purchase-request-foundation.php
- docs/missions/mission_26_purchase_request_controlled/ (8 Markdown files)

## Boundaries
- Create only (DRAFT or SUBMITTED)
- No approval actions in Mission 26
- No supplier payment
- No finance write
- No stock receipt
- No automatic approval
- No purchase order execution

## SQL Rule
Manual SSMS execution only.

## Success Message
Purchase Request Created OK
