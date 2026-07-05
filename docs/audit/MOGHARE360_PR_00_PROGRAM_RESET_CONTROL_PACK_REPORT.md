# MOGHARE360 PR-00 — Program Reset Control Pack Report

**Mission ID:** PR-00  
**Type:** Program Reset — Canonical Control Pack (report + planning only)  
**Date:** 2026-07-05  
**Agent:** Cursor (implementer)  
**Commit Eligibility:** `NOT_ELIGIBLE_PROGRAM_RESET_ONLY`

---

## 1. Scope Gate Result

| Gate | Result |
|------|--------|
| Modify PHP/JS/CSS/SQL | **PASS — none modified** |
| Modify private/config/OTP/Auth | **PASS — none modified** |
| Delete/move/rename runtime files | **PASS — none** |
| Commit / push | **PASS — none** |
| Start C-2D / JobCard conversion | **PASS — not started** |
| Create only authorized files | **PASS — 6 files created** |
| Runtime behavior change | **PASS — none** |

**Scope gate: PASSED**

---

## 2. Files Created

| # | Path | Purpose |
|---|------|---------|
| 1 | `docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` | Mother product blueprint |
| 2 | `docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md` | 30-module DB gap classification |
| 3 | `docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md` | Runtime file classification |
| 4 | `docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md` | 48h → 9-month phased roadmap |
| 5 | `docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md` | Binding Cursor agent rules |
| 6 | `docs/audit/MOGHARE360_PR_00_PROGRAM_RESET_CONTROL_PACK_REPORT.md` | This report |

---

## 3. Files Not Changed

All runtime, config, OTP, Auth, SQL, and non-canonical documentation files remain unchanged, including but not limited to:

- `public_html/**/*.php`, `*.js`, `*.css`
- `private/m360-otp-config.php` (if present on disk)
- `database/migrations/*.sql`
- All 1,229 existing docs outside the 6 files above
- All `tools/`, `dist/`, `release/` paths

---

## 4. Current Product Truth

| Aspect | Truth |
|--------|-------|
| **Owner target** | Complete Persian RTL auto-workshop ERP — not demo, soft run, or partial RC |
| **Program state** | Official **PROGRAM RESET** |
| **Intended product** | Full ERP: reception → cartable → JobCard → workshop → QC → delivery → inventory → purchase → accounting → HR |
| **Vehicle scope** | Owner-defined brands only: Toyota, Lexus, Kia, Hyundai, BYD, Lucano, Chery |
| **UX** | Luxury UI, minimum clicks, camera-direct photos |
| **Deployment** | Internal server, static IP; web now; desktop/Android/iPhone/PWA later |
| **Completion standard** | Browser UAT + SQL truth + owner signoff |
| **Latest intake work** | P11.9 E7D customer cartable bridge (payload hybrid) — fixture PASS, browser UAT pending |
| **JobCard conversion** | **Forbidden** (C-2D) until owner approval |

---

## 5. Current DB Truth

| Metric | Value |
|--------|-------|
| Database | `moghare360_ERP` (SQL Server) |
| Tables | 96 |
| Foreign keys | 77 (0 disabled/untrusted) |
| Empty operational tables | 46 |
| Migrations present | P1, P1.5, P2–P10, P11 lock |
| Structural assessment | Advanced schema; data mostly seed/demo |
| Critical gap | Payload overuse on `erp_customer_online_requests.request_payload_json` |
| Missing domains | External repair, foreign import, logistics, full accounting, parts sales, customer cartable tables |
| ID alignment risk | 52 int/bigint mismatch candidates |
| Duplicate domain risk | 63 heuristic overlaps (inventory paths) |

**DB completeness: NOT PROVEN** — structure exists; live operational proof incomplete.

---

## 6. Current Runtime Truth

| Area | State |
|------|-------|
| **OTP (browser)** | **FAILING** on `customer-request.php` → `api/customer/send-otp.php` |
| **OTP (legacy root)** | HTTP 410 stubs via deprecation helper |
| **OTP helpers** | Modified in open working tree — **freeze violation** |
| **Reception hub** | `erp-reception-workbench.php` active |
| **Intake wizard** | `erp-reception-intake-file.php` canonical; legacy detail pages overlap |
| **Customer cartable** | `customer-intake-contract-review.php` — E7D payload bridge |
| **Live request 18** | Prerequisites not ready (`first_blocker_step: vehicle`); no token hash |
| **JobCard pages** | Many active + duplicate command centers — needs dedup phase |
| **Working tree** | ~114 open files (16 modified, 98 untracked) |
| **XAMPP** | Partial deploy (E7D files only in last copy) |

**Runtime stability: UNSTABLE** for browser UAT.

---

## 7. Current Documentation Truth

| Metric | Value |
|--------|-------|
| Total docs files | ~1,229 |
| Top-level folders | 27 |
| Contradictions | OTP frozen vs modified; fixture PASS vs browser FAIL; multiple RC/mission claims |
| Prior audit reports | Global freeze, docs inventory, E7D scope/final |
| **New authority** | `docs/00_CANONICAL/` — **6 files supersede scattered docs for execution decisions** |

Historical mission/audit docs remain as reference until archived by owner.

---

## 8. Immediate Risks

| # | Risk | Severity |
|---|------|----------|
| 1 | OTP browser failure blocks entire customer intake | **Critical** |
| 2 | Fixture tests pass where browser fails | **Critical** |
| 3 | 114 open files — commit would bundle unstable changes | **High** |
| 4 | OTP files modified while globally frozen | **High** |
| 5 | Payload-as-database for cartable/intake | **High** |
| 6 | Duplicate JobCard/command-center pages | **Medium** |
| 7 | 46 empty DB tables — false sense of completion | **Medium** |
| 8 | No vehicle brand/model DB enforcement | **Medium** |
| 9 | Secret risk if private OTP config committed | **High** |
| 10 | Scope creep from 1,229 conflicting docs | **Medium** |

---

## 9. What Must Stop

| Stop | Reason |
|------|--------|
| New feature phases without canonical read | Reset discipline |
| Fixture-only completion claims | Program rule |
| OTP/Auth edits without explicit phase unlock | Freeze |
| C-2D / automatic JobCard | Owner forbidden |
| DB schema changes without proposal | Reset rule |
| Commit / push by Cursor | Owner only |
| Relying on demo RC / soft run as target | Owner wants full ERP |
| Creating unsolicited documentation | Scope control |
| Physical file deletes | Owner approval required |
| Production-ready or accounting-official claims | UAT gates not met |

---

## 10. What Happens Next

**Recommended sequence (owner-controlled):**

1. **Owner approves** canonical control pack as execution authority.
2. **GLOBAL_FIX_OTP_FIRST** — dedicated OTP phase with browser proof on `customer-request.php`.
3. **Intake + cartable browser UAT** — complete wizard + customer acceptance on live/staging data.
4. **Live data completion** — request 18 (or successor) through all prerequisite steps.
5. **Owner commit split** — docs vs runtime vs OTP isolation (only after UAT).
6. **Runtime cleanup Phase R2** — deprecate legacy reception routes in nav.
7. **C-2D unlock request** — owner approval for controlled JobCard conversion.
8. **Operational core** — per 1-month roadmap milestone.

**Next Cursor task:** OTP recovery phase (when owner assigns) — must reference canonical docs and declare phase Included/Excluded/Forbidden.

---

## 11. Commit Eligibility

```
NOT_ELIGIBLE_PROGRAM_RESET_ONLY
```

No commit, push, or release action is authorized by this mission.

---

## Canonical Document Index

| Document | Role |
|----------|------|
| `MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` | Product scope authority |
| `MOGHARE360_DATABASE_GAP_MATRIX.md` | DB truth and gap classification |
| `MOGHARE360_RUNTIME_CLEANUP_PLAN.md` | File keep/deprecate/delete classification |
| `MOGHARE360_EXECUTION_ROADMAP.md` | Time-phased delivery |
| `MOGHARE360_CURSOR_EXECUTION_RULES.md` | Agent binding rules |

---

MOGHARE360 PR-00 creates the canonical control pack that replaces scattered mission/audit documents as execution authority and prepares the project for DB gap closure, runtime cleanup, OTP recovery, browser-proven intake execution, and controlled ERP completion under locked scope.

---

**END OF PR-00 REPORT**
