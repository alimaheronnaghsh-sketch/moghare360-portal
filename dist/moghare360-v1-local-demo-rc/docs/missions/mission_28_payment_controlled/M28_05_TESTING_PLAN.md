# Mission 28 - Testing Plan

## Prerequisites
1. Run `mission_28_payment_foundation.sql` in SSMS
2. JobCard jobcard_id = 1 exists
3. XAMPP / local PHP with ODBC

## Step 1 — CLI Test (read-only)
```text
C:\xampp\php\php.exe tools\test-erp-payment-foundation.php
```
Expected after SQL only: tables OK, jobcard OK, payment PENDING.

## Step 2 — Browser Create
Open `erp-payment-create.php`:
- jobcard_id = 1
- payment_type = ADVANCE (or PARTIAL/FULL)
- payment_method = CASH
- payment_amount = e.g. 1000000
- Submit

Expected: **Payment Created OK**

## Step 3 — List Page
Open `erp-payment-readonly-list.php` — row visible.

## Step 4 — JobCard Summary
Open `erp-jobcard-payment-summary.php?jobcard_id=1` — total_received and payment_count shown.

## Step 5 — Re-run CLI
After browser create: payment OK, history PAYMENT_RECEIVED OK, status RECEIVED OK, summary query OK.

## Signoff
Update M28_90 to PASSED and M28_99 after user confirms.
