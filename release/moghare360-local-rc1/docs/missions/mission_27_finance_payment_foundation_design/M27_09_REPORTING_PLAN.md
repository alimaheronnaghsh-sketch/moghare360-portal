# Reporting Plan

## Purpose
This document defines the reporting plan for Soft Run finance visibility.

## Mission 27 Boundary
Reporting planned only. **No reports implemented in Mission 27.**

## Mission 28 Reporting Scope (Locked)

### 1. JobCard Payment Summary
- Per JobCard: total_received, payment_count, last_payment_at
- outstanding_balance placeholder (calculated when expected_total available)
- Delivered via `erp-jobcard-payment-summary.php`

### 2. Received Payments List
- Filter/list payments with status RECEIVED (and optionally DRAFT)
- Delivered via `erp-payment-readonly-list.php`
- Sort by received_at DESC

### 3. Outstanding Balance Placeholder
- When `expected_total_amount` not defined: display "TBD" or "—"
- Formula documented in M27_03; no stored balance column
- Future mission may add expected_total on JobCard or quote table

## Reports Explicitly Out of Scope (Locked)

| Report | Status |
|--------|--------|
| Accounting ledger report | Not in M27/M28 |
| General journal | Not in M27/M28 |
| Tax report (VAT, etc.) | Not in M27/M28 |
| Supplier AP aging | Not in M27/M28 |
| Purchase payment report | Not in M27/M28 |
| Invoice register | Not in M27/M28 |
| Delivery release report | Not in M27/M28 |

## Export Formats (Future)
- CSV/PDF export deferred to post–Soft Run finance mission
- No accounting system export in M28

## Aggregation Rules (Locked)
All monetary aggregates computed at query time from `erp_payments`:
- Only `payment_status = RECEIVED` and `is_active = 1` count toward received totals
- CANCELLED and REVERSED excluded from received sum

## Mission 27 Deliverable
This reporting plan document only.

## Final Reporting Decision
M28 delivers JobCard summary + payments list + outstanding placeholder only; no ledger, tax, or accounting reports.
