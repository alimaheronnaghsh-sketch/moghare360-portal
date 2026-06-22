# Mission 26 - Testing Plan

## Prerequisites
1. Run `mission_26_purchase_request_foundation.sql` in SSMS
2. JobCard jobcard_id = 1 exists
3. XAMPP / local PHP with ODBC

## Step 1 — CLI Test (read-only)
```text
C:\xampp\php\php.exe tools\test-erp-purchase-request-foundation.php
```
Expected after SQL only: tables OK, jobcard OK, purchase request PENDING.

## Step 2 — Browser Create
Open `erp-purchase-request-create.php` on localhost:
- jobcard_id = 1
- requested_part_name = e.g. M26-TEST-PR-001
- requested_quantity = 1
- request_status = DRAFT or SUBMITTED
- Submit form

Expected: **Purchase Request Created OK**

## Step 3 — List Page
Open `erp-purchase-request-readonly-list.php` — row visible.

## Step 4 — Detail Page
Open `erp-purchase-request-detail.php?purchase_request_id=<id>` — header + history PURCHASE_REQUEST_CREATED.

## Step 5 — Re-run CLI
After browser create: purchase request for jobcard 1 OK, history OK, status DRAFT or SUBMITTED OK.

## Negative Checks (must remain true)
- No supplier payment
- No finance write
- No stock receipt
- No automatic approval
- CLI performs no writes

## Signoff
Update M26_90 to PASSED and M26_99 after user confirms all steps.
