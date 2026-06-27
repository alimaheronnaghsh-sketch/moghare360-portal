# UI Plan

## Purpose
This document defines the UI plan for payment operations.

## Mission 27 Boundary
UI planned only. **No PHP files created in Mission 27.**

## Mission 28 Planned Pages (Locked Reference)

### 1. erp-payment-create.php
**Purpose:** Register one customer payment against JobCard.

**Fields (indicative):**
- jobcard_id (required)
- payment_type (ADVANCE / PARTIAL / FULL)
- payment_method (CASH / CARD / BANK_TRANSFER / POS / OTHER)
- payment_amount (required, > 0)
- currency_code (default IRR)
- payment_status initial: DRAFT or RECEIVED
- payment_reference (optional)
- payment_note (optional)

**Behavior:**
- POST with CSRF + permission `payment.create`
- Transaction: insert payment + history PAYMENT_CREATED
- customer_id denormalized from JobCard when available
- No invoice generation
- No accounting export trigger

### 2. erp-payment-readonly-list.php
**Purpose:** List received and draft payments for operational visibility.

**Columns (indicative):**
- payment_id
- jobcard_id
- payment_type
- payment_method
- payment_amount
- currency_code
- payment_status
- received_at

**Behavior:**
- Read-only list
- Permission: `payment.list`
- No edit/delete buttons in M28 initial scope

### 3. erp-jobcard-payment-summary.php
**Purpose:** Read-only JobCard payment summary.

**Sections:**
- JobCard header (jobcard_id, number, customer)
- total_received (calculated)
- outstanding_balance (calculated placeholder when expected_total unavailable)
- payment_count
- List of payments for JobCard (embedded or linked)

**Behavior:**
- Read-only aggregates
- Permission: `payment.summary.view`
- No balance write
- No delivery release button

## UI Elements Explicitly Deferred
- Invoice create / finalize
- Tax breakdown display
- Accounting export download
- Supplier payment UI
- Payment reverse/refund UI (unless M28 extension)
- Delivery gate UI

## Navigation (Indicative)
Link from JobCard context or ERP admin finance menu (Mission 28 wiring).

## Forbidden UI Changes (Locked)
- No Customer Portal payment pages in M28
- No legacy finance PHP modification
- No config / auth file changes

## Mission 27 Deliverable
This UI plan document only.

## Final UI Decision
Three PHP pages in Mission 28: payment create, readonly list, JobCard payment summary; no invoice/tax/export/supplier UI.
