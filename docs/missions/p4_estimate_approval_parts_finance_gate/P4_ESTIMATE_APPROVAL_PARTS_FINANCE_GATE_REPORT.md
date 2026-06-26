# P4 — Estimate / Approval / Parts / Finance Gate

**MOGHARE360 V1** | Mission report

## 1. Schema discovery

| Item | Finding |
|------|---------|
| JobCard | `dbo.erp_jobcards` — PK `jobcard_id` |
| P3 output | `technical_status = WAITING_FOR_APPROVAL` |
| Service operations | `dbo.erp_service_operations` (existing) |
| Part usage | `dbo.erp_jobcard_part_usage` (read/check for gate) |
| Purchase | `dbo.erp_purchase_requests` (read only) |
| Payments | `dbo.erp_payments` (read/check for finance gate) |
| Estimates | **Not existing** — created by P4 migration |
| OTP helper | `m360-otp-helper.php` — reused |

## 2. SQL migration

**Needed:** Yes — `database/migrations/P4_estimate_approval_parts_finance_gate.sql`

Tables: `erp_estimates`, `erp_estimate_items`, `erp_estimate_approvals`, `erp_estimate_events`  
JobCard columns: `estimate_status`, `current_estimate_id`, `estimate_approved_at`, `parts_gate_status`, `finance_gate_status`, `approved_for_work_at`

## 3. Files added

- `erp-estimate-board.php`, `erp-estimate-detail.php`, `erp-estimate-action.php`
- `customer-estimate-approval.php`, `customer-estimate-approval-sign.php`
- `api/customer/estimate-send-otp.php`, `api/customer/estimate-approve.php`
- `m360-estimate-helper.php`, `m360-estimate-approval-helper.php`, `m360-parts-finance-gate-helper.php`
- `assets/css/m360-estimate.css`, `assets/js/m360-estimate-approval.js`
- 6 test suites + this report

## 4–10. Behavior summary

- **Board:** JobCards in `WAITING_FOR_APPROVAL` + active estimates; filters by estimate/gate status
- **Detail/Action:** Draft → items → totals → internal review → send to customer (token hash)
- **Customer:** View estimate, checkboxes, OTP via existing SMS helper, IP/UA/hash on approval
- **Parts gate:** PART items → PENDING; cleared via usage/purchase read or manual clear
- **Finance gate:** 50% advance rounded up to 10M Tomans; cleared via `erp_payments` read or NOT_REQUIRED
- **approve_for_work:** Requires `CUSTOMER_APPROVED` + parts/finance CLEARED or NOT_REQUIRED + contract gate

## 11. Advance calculation

`ceil(total × 0.5 / 10,000,000) × 10,000,000` Tomans

## 12. Scope control

No full purchase, inventory, payment gateway, final invoice, or accounting voucher.

## 13. Tests passed

| Suite | Result |
|-------|--------|
| `test-p4-estimate-schema.php` | 8/8 |
| `test-p4-estimate-board.php` | 13/13 |
| `test-p4-customer-approval-otp.php` | 11/11 |
| `test-p4-parts-finance-gate.php` | 10/10 |
| `test-p4-history-audit.php` | 10/10 |
| `test-p4-scope-control.php` | 8/8 |

`test-v1-production-signoff.php` — not run in this session.

## 14. Security

No credentials, no auth core changes, no gate bypass, non-destructive SQL.

---

**MOGHARE360 P4 enables controlled estimate creation, customer OTP approval, parts readiness gate, and finance clearance gate before JobCard work continues beyond technical diagnosis, without adding full inventory, payment, invoice, or accounting scope.**
