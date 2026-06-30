# MOGHARE360 P11.9-A — Go / No-Go Checklist

Complete **before** dry run execution (not part of P11.9-A automation).

---

## GO only if ALL checked

### Access & login

- ☐ OWNER can log in (`owner-login.php`)
- ☐ RECEPTION can log in (`staff-login.php`)
- ☐ SERVICE_MANAGER can log in
- ☐ TECHNICIAN can log in
- ☐ PARTS can log in
- ☐ FINANCE can log in
- ☐ QC can log in

### UI readiness

- ☐ Staff Home loads for all 7 staff roles
- ☐ Route Map **operational view** loads (`erp-route-map.php?view=operational`)
- ☐ Access Management loads for owner
- ☐ Reception JobCards board loads
- ☐ Technical board loads
- ☐ QC board loads

### Demo data

- ☐ One **fresh** `M360-DEMO-*` JobCard exists (not assumed ID 1)
- ☐ Canonical `jobcard_id` + `jobcard_number` recorded in log header
- ☐ Read-only preflight run — no unexpected BLOCKED items

### Operator materials

- ☐ Operator runbook open/printed
- ☐ 115-step execution log ready
- ☐ Incident register template ready
- ☐ Manager observation guide shared with owner

### Skip / defer acknowledgements

- ☐ part-use SKIP (`erp-jobcard-part-use.php`) acknowledged by PARTS/TECH
- ☐ payment-tracking SKIP (`erp-payment-tracking.php`) acknowledged by FINANCE
- ☐ OTP deferral protocol signed (or live SMS verified if not deferring)
- ☐ Action endpoints will be used **only** from detail forms

### Security

- ☐ No passwords stored in repo/docs/log
- ☐ No password screenshots captured
- ☐ No OTP secrets exposed
- ☐ No plan for manual DB fixes during run

### Demo staff provisioning (P11.9-B)

- ☐ All six demo users created via **`erp-access-management.php` → `erp-access-user-create.php`**
- ☐ **`PARTS`** UI role used for `demo.parts` (not INVENTORY)
- ☐ **`SERVICE_MANAGER`** UI role used for `demo.service.manager`
- ☐ Unit Access Console **not** used for user creation
- ☐ Private JSON import **not** used for P11.9-B demo users

---

## NO-GO if ANY checked

- ☐ Owner cannot log in
- ☐ Any required role cannot log in
- ☐ Access Management cannot create required demo users
- ☐ **`PARTS`** or **`SERVICE_MANAGER`** unavailable in Access Management UI role dropdown
- ☐ Operator attempted demo provisioning via Unit Access Console, JSON import, or raw SQL without approved phase
- ☐ No fresh M360-DEMO JobCard identified
- ☐ Staff Home broken for any role
- ☐ Route Map operational view broken
- ☐ Must open action endpoints directly to proceed
- ☐ OTP required but SMS not configured **and** no deferral sign-off
- ☐ Operator cannot identify current step/state
- ☐ BLOCKER on core P2–P7 board/detail load

---

## Decision

| Field | Value |
|-------|-------|
| Date | |
| Preflight SQL run? | YES / NO |
| Overall | **GO** / **NO-GO** / **CONDITIONAL GO** |
| Conditions (if conditional) | |
| Operator | |
| Owner | |

---

## Notes

- **CONDITIONAL GO** matches P11.9-1 default — acceptable with documented WARNINGs
- Execution phase is **after** this checklist — P11.9-A does not execute the run
