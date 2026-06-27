# P7 — Final Invoice / Settlement / Customer Delivery

**MOGHARE360 V1** | Mission report

## 1. Schema discovery

| Item | Finding |
|------|---------|
| JobCard table | `dbo.erp_jobcards` — PK `jobcard_id` |
| DELIVERY_READY | Stored on `jobcard.qc_status = DELIVERY_READY`, and/or `delivery_readiness_status = READY` (P6), and/or `jobcard_status = DELIVERY_READY` |
| `delivery_readiness_status` | **Exists** (P6 migration) |
| `delivery_ready_at` | **Exists** (P6 migration) |
| Estimate | `dbo.erp_estimates` + `erp_estimate_items` + `erp_estimate_approvals` |
| Work / parts | `erp_work_execution_events`, `erp_service_operations`, `erp_jobcard_part_usage` |
| QC / readiness | `erp_qc_checks`, `erp_qc_check_items`, `erp_qc_events`, `erp_delivery_controls` (P6 — READY only until P7 release) |
| Legacy invoice tables | `erp_invoices` / `erp_invoice_items` exist from earlier missions — **P7 uses separate `erp_final_invoices`** to avoid deep accounting scope |
| Settlement legacy | `erp_settlements` may exist — **P7 uses `erp_settlement_controls`** for operational gate |
| Delivery signature legacy | `erp_delivery_signatures` may exist — **P7 uses `erp_customer_delivery_confirmations`** with OTP + signature hash |
| OTP helper | `m360-otp-helper.php` (P1.5/P4) — reused; dev OTP only via existing helper rules |
| Signature canvas | `m360-signature-pad.js` + P1.5 intake contract pattern — reused on customer delivery sign page |
| JobCard history | `erp_jobcard_change_history` via `m360_fi_write_history()` adapter |
| Delivery events | New `erp_delivery_events` (P7) — separate from P6 `erp_qc_events` |

**Status mapping (JobCard):** P7 extends JobCard with `final_invoice_status`, `settlement_status`, `customer_delivery_status`; operational close sets `jobcard_status = CLOSED` after `VEHICLE_RELEASED`.

## 2. SQL migration

**Needed:** Yes — `database/migrations/P7_final_invoice_settlement_customer_delivery.sql`

Non-destructive: `IF OBJECT_ID IS NULL CREATE TABLE`, `IF COL_LENGTH IS NULL ALTER TABLE ADD`, indexes only.

Adds JobCard P7 columns, `erp_final_invoices`, `erp_final_invoice_items`, `erp_settlement_controls`, `erp_customer_delivery_confirmations`, `erp_delivery_events`.

**Security:** Customer delivery link token stored as `delivery_token_hash` only — no raw token column in DB.

## 3. Files added / modified

**Final invoice UI:** `erp-final-invoice-board.php`, `erp-final-invoice-detail.php`, `erp-final-invoice-action.php`

**Settlement UI:** `erp-settlement-detail.php`, `erp-settlement-action.php`

**Customer delivery UI:** `customer-delivery-review.php`, `customer-delivery-sign.php`

**Customer delivery API:** `api/customer/delivery-send-otp.php`, `api/customer/delivery-confirm.php`

**Helpers:** `m360-final-invoice-helper.php`, `m360-settlement-helper.php`, `m360-customer-delivery-helper.php`, `m360-jobcard-close-helper.php`

**Integration (read/check only):** `m360-qc-helper.php`, `m360-delivery-readiness-helper.php`, `m360-estimate-helper.php`, `m360-work-execution-helper.php`, `m360-intake-contract-helper.php` — gate wiring, no bypass

**Assets:** `assets/css/m360-final-delivery.css`, `assets/js/m360-customer-delivery-sign.js`

**SQL:** `P7_final_invoice_settlement_customer_delivery.sql`

**Tests:** 8 suites under `tools/test-p7-*.php`

**Not changed:** `staff-login.php`, `owner-login.php`, `access-control.php`, `config.php`, auth core

## 4. Final invoice board

Lists JobCards at `DELIVERY_READY` or with active P7 workflow. Filters: DELIVERY_READY, DRAFT, CALCULATED, FINALIZED, SETTLEMENT_PENDING, SETTLED, DELIVERY_SIGNED, DELIVERED, CLOSED.

Displays customer, mobile, vehicle, plate, QC, delivery readiness, invoice/settlement/delivery status, amounts. Persian RTL.

## 5. Final invoice detail / action

POST-only actions with CSRF (`M360_FI_CSRF`):

| Action | Purpose |
|--------|---------|
| `create_draft_invoice` | Start draft from gated DELIVERY_READY JobCard |
| `calculate_invoice` | Build lines from approved estimate, completed service ops, consumed parts |
| `add_approved_manual_item` | Controlled manual line (MANUAL_APPROVED) |
| `apply_discount` | Discount line |
| `finalize_invoice` | Lock invoice; triggers settlement recalc; generates hashed delivery token |
| `recalculate_settlement` | Sync settlement from payments |
| `notify_customer` | Mark customer notified (operational flag) |
| `cancel_draft_invoice` | Cancel DRAFT/CALCULATED only |

**Variance:** `estimate_total` vs `total_amount`; threshold 10%. Excess blocks finalize unless `variance_override_reason` provided. Event `FINAL_INVOICE_FINALIZE_BLOCKED_GATE` on gate failure.

## 6. Settlement control

Active only after invoice `FINALIZED`.

- `total_due` = final invoice total
- `total_paid` read from existing `erp_payments` / jobcard payment linkage — **no INSERT into payment tables, no gateway**
- Status: `PAYMENT_PENDING`, `PARTIAL_SETTLED`, `SETTLED`, `MANAGER_RELEASE_APPROVED`, `BLOCKED`
- Manager release requires mandatory reason
- Vehicle release blocked unless `SETTLED` or `MANAGER_RELEASE_APPROVED`

Actions: `recalculate_settlement`, `mark_settled`, `manager_release_approval`, `block_delivery`, plus `release_vehicle` and `close_jobcard` on settlement detail.

## 7. Customer delivery review / sign

**Review** (`customer-delivery-review.php`): secure token (hash match), expiry, vehicle/services/parts/QC/financial summary, mandatory acceptance checkboxes.

**Sign** (`customer-delivery-sign.php`): mobile-friendly, OTP send/verify, signature canvas, final confirm → read-only.

**APIs:** POST-only `delivery-send-otp.php`, `delivery-confirm.php` — OTP verified, signature hash, checkboxes, IP/User-Agent, `confirmation_hash` stored.

## 8. OTP / signature behavior

- Reuses `m360-otp-helper.php` — no new SMS credentials, no production fake OTP hardcode
- Dev OTP only when existing helper allows (`m360_otp_can_use_dev_code`)
- Signature hash required on confirm; no free file upload
- Re-confirmation blocked after `DELIVERY_SIGNED` unless management reset path

## 9. Vehicle release

`m360_vehicle_release()` in `m360-jobcard-close-helper.php` requires:

- Final invoice FINALIZED
- Settlement SETTLED or MANAGER_RELEASE_APPROVED
- Customer delivery DELIVERY_SIGNED
- QC_PASSED + delivery readiness valid

Sets `erp_delivery_controls.delivery_status = RELEASED`, `vehicle_released_at`, `customer_delivery_status = VEHICLE_RELEASED`. Event: `VEHICLE_RELEASED_TO_CUSTOMER`.

## 10. JobCard close

`m360_jobcard_close()` after vehicle release:

- Sets `jobcard_status = CLOSED`, `jobcard_closed_at`, `closed_by_user_id`
- Event: `JOBCARD_CLOSED`
- **No accounting voucher, ledger, or tax filing**

## 11. Audit / history

JobCard history events: `JOBCARD_FINAL_INVOICE_*`, `JOBCARD_SETTLEMENT_*`, `JOBCARD_DELIVERY_*`, `JOBCARD_VEHICLE_RELEASED`, `JOBCARD_CLOSED`, `JOBCARD_DELIVERY_BLOCKED_GATE`.

Delivery events table: `FINAL_INVOICE_*`, `SETTLEMENT_*`, `CUSTOMER_DELIVERY_*`, `VEHICLE_RELEASED_TO_CUSTOMER`, `JOBCARD_CLOSED`, `DELIVERY_BLOCKED_GATE`.

All state changes POST-only; GET never mutates state.

## 12. Scope control confirmation

| Out of scope (confirmed absent) | Status |
|----------------------------------|--------|
| Accounting voucher | Not implemented |
| Ledger / دفترکل | Not implemented |
| Payment gateway | Not implemented |
| Bank integration | Not implemented |
| Tax official integration | Not implemented |
| Free upload bypass | Not implemented — signature canvas + controlled patterns only |
| P1.5 / P2–P6 gate bypass | Not bypassed — `m360_p7_assert_gates()` enforced |

## 13. Tests passed

| Suite | Result |
|-------|--------|
| `test-p7-final-invoice-schema.php` | 13/13 |
| `test-p7-final-invoice-board.php` | 21/21 |
| `test-p7-settlement-control.php` | 14/14 |
| `test-p7-customer-delivery-signature-otp.php` | 22/22 |
| `test-p7-delivery-gate-enforcement.php` | 12/12 |
| `test-p7-jobcard-close.php` | 13/13 |
| `test-p7-history-audit.php` | 32/32 |
| `test-p7-scope-control.php` | 14/14 |

**Total P7 assertions:** 141/141 PASS

PHP `-l` passed on all P7 UI, API, and helper files.

`test-v1-production-signoff.php` — 23/23 PASS.

## 14. Security confirmation

- No credentials or real config in repo
- Non-destructive SQL only (no DROP/DELETE/TRUNCATE)
- Auth/login core unchanged
- `staff-login.php`, `owner-login.php`, `access-control.php` untouched
- Delivery token hash-only storage
- CSRF on all staff POST actions
- P1.5 contract gate + P2–P6 gates enforced before finalize/release/close

---

**MOGHARE360 P7 enables controlled final invoice creation, settlement validation, customer OTP/signature delivery confirmation, vehicle release, and JobCard closure after DELIVERY_READY without adding accounting vouchers, ledger, bank gateway, tax integration, or upload-bypass scope.**
