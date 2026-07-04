# MOGHARE360 P11.9-C-2B-REWORK-FINAL — Scope Gate Report

**Phase:** P11.9-C-2B-REWORK-FINAL  
**Date:** 2026-07-04  
**Mode:** Scope gate before implementation closure  
**Decision:** **CONTINUE** — fixes require no DB schema, SQL migration, Auth, permissions, workflow, or OTP changes.

---

## 1. Current uncommitted reception-related files (14 tracked)

| # | File | Git status |
|---|------|------------|
| 1 | `public_html/erp-reception-workbench.php` | untracked (new) |
| 2 | `public_html/erp-reception-intake-file.php` | untracked (new) |
| 3 | `public_html/includes/m360-reception-workbench-helper.php` | untracked (new) |
| 4 | `public_html/erp-reception-online-requests.php` | modified |
| 5 | `public_html/erp-reception-online-request-detail.php` | modified |
| 6 | `public_html/erp-staff-home.php` | modified |
| 7 | `public_html/assets/css/moghare360-v1-luxury-ui.css` | modified |
| 8–11 | `docs/audit/MOGHARE360_P11_9_C_2B*.md` (4 reports) | untracked |
| 12–16 | `tools/test-p11-9-c-2b-*.php` (5 prior tests) | untracked |

**Not separate files (embedded in workbench + helper):**
- `erp-reception-personnel-hub.php` → `?section=profile`
- `erp-reception-process-hub.php` → `?section=reception`
- `erp-reception-temporary.php` → hub card + filtered list
- `erp-reception-admission-hub.php` → hub «پذیرش» subs
- `erp-reception-qc-hub.php` → hub card placeholder
- `erp-reception-delivery-hub.php` → hub card placeholder

---

## 2. Useful files to keep

All 7 public_html files + CSS + docs + tests — foundation is salvageable (Option D approved).

---

## 3. Files that must be modified

| File | Why |
|------|-----|
| `m360-reception-workbench-helper.php` | Gate factory, intake fetch fix, messaging, QC de-link |
| `erp-reception-workbench.php` | Mock UX de-link, walk-in placeholder |
| `erp-reception-intake-file.php` | 5-section structure, service class text |
| `erp-reception-online-request-detail.php` | Luxury UI, controlled actions section |
| `erp-reception-online-requests.php` | Luxury UI alignment |
| `moghare360-v1-luxury-ui.css` | List/detail/section styles |
| All rework-final tests | New HTTP + runtime coverage |

---

## 4. Remove from operational navigation

| Route | Action |
|-------|--------|
| `erp-jobcard-create-ux.php?role=reception` | Demote to collapsed «راهنمای نمایشی / غیرعملیاتی» |
| `erp-customer-vehicle-create-ux.php?role=reception` | Same |
| `erp-qc-board.php` from QC hub card | Remove href while placeholder |

---

## 5. Docs/tests only

- `docs/audit/MOGHARE360_P11_9_C_2B*.md` — audit trail
- `tools/test-p11-9-c-2b-*.php` — automated gates
- `tools/test-p11-9-c-2b-rework-final-*.php` — final gate suite

---

## 6. Runtime fatal cause

When FIX-A added 9th parameter `?array $jobcard` to `m360_rw_build_gate()`, the empty-gate bootstrap in `m360_rw_build_intake_file()` remained at **8 arguments**. Every intake page load hit line ~558 before rendering HTML → `ArgumentCountError`.

**Fix:** `m360_rw_build_empty_gate()` factory; all call sites pass 9 args with `null` when no jobcard.

---

## 7. Failed runtime/browser paths

| URL | Failure |
|-----|---------|
| `erp-reception-intake-file.php?online_request_id=18` | Fatal before REWORK-A |
| `erp-reception-intake-file.php?online_request_id=20` | Fatal before REWORK-A |
| Any intake URL | Same — always calls `m360_rw_build_intake_file()` |

---

## 8. Insufficient tests (prior)

| Test | Gap |
|------|-----|
| `test-p11-9-c-2b-intake-completion-shell.php` | String + direct gate; skipped `m360_rw_build_intake_file()` |
| `test-p11-9-c-2b-reception-workbench.php` | No runtime |
| `test-p11-9-c-2b-fix-a-process-correction.php` | No HTTP |
| No HTTP smoke | Browser failure invisible |

---

## 9. Mock UX incorrectly linked

| Location | Mock link |
|----------|-----------|
| `erp-reception-workbench.php?section=walkin` (before) | Primary buttons to jobcard/customer UX mocks |

---

## 10. Pages needing UX alignment

- `erp-reception-online-requests.php` — was legacy blue
- `erp-reception-online-request-detail.php` — was legacy blue
- `erp-reception-intake-file.php` — structure + messaging

---

## 11. Actions exposed too early (before REWORK-FINAL)

- Detail page had accept/reject/convert/under_review as first-level POST (fixed REWORK-A)
- Walk-in mock UX as primary path

---

## 12. C-2C write-action scope (deferred)

- Save service classification (multi-select)
- Save intake field completions
- Save temp reception status transitions
- Expert review / initial diagnosis persistence
- Cost agreement + final confirmation writes
- Gate-driven conversion enablement via payload updates

---

## 13. Scope confirmation

| Constraint | Required? | Status |
|------------|-----------|--------|
| DB schema change | No | ✓ Not required |
| SQL migration | No | ✓ Not required |
| Auth/Login change | No | ✓ Not required |
| Permission/role change | No | ✓ Not required |
| Workflow rewrite | No | ✓ Not required |
| OTP bypass | No | ✓ Not required |

**Scope gate result: CONTINUE**
