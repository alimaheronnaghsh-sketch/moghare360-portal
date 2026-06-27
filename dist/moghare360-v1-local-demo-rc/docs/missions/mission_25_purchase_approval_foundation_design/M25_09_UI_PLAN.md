# UI Plan

## Purpose
This document defines the UI plan for purchase request operations.

## Mission 25 Boundary
UI planned only. **No PHP files created in Mission 25.**

## Mission 26 Planned Pages (Locked Reference)

### 1. erp-purchase-request-create.php
**Purpose:** Create one purchase request linked to JobCard (and optional Service Operation / part).

**Fields (indicative):**
- jobcard_id (required)
- service_operation_id (optional)
- part_id (optional)
- requested_part_name (required)
- requested_quantity (required)
- request_reason (optional)
- request_status initial: DRAFT or SUBMITTED
- estimated_unit_cost (optional, informational)
- currency_code (optional)

**Behavior:**
- POST with CSRF + permission `purchase.request.create`
- Transaction: insert request + history PURCHASE_REQUEST_CREATED
- supplier_id remains NULL
- No approve/reject on create page in initial M26 scope (unless chartered)

### 2. erp-purchase-request-readonly-list.php
**Purpose:** List purchase requests for operational visibility.

**Columns (indicative):**
- purchase_request_id
- jobcard_id
- requested_part_name
- requested_quantity
- request_status
- requested_at
- requested_by_user_id

**Behavior:**
- Read-only list
- Permission: `purchase.request.list`
- Filter by status optional (M26+)

### 3. erp-purchase-request-detail.php
**Purpose:** View single purchase request and history.

**Sections:**
- Request header fields
- Status badge
- Approval fields (approved/rejected metadata when set)
- History table from erp_purchase_request_history

**Behavior:**
- Read-only detail in initial M26 scope
- Permission: `purchase.request.view`
- No payment section
- No stock receipt section
- No supplier contract section

## UI Elements Explicitly Deferred
- Approve / Reject buttons (future mission or M26 extension)
- Supplier picker
- PO generation
- Payment status
- Stock receipt confirmation
- Automatic approval indicators

## Navigation (Indicative)
Link from JobCard context or ERP admin menu (Mission 26 wiring).

## Forbidden UI Changes (Locked)
- No Customer Portal pages
- No legacy inventory PHP modification
- No finance dashboard integration in M26

## Mission 25 Deliverable
This UI plan document only.

## Final UI Decision
Three PHP pages in Mission 26: create, readonly list, detail; no supplier/finance/receipt UI in initial scope.
