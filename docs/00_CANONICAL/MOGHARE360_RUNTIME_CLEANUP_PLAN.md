# MOGHARE360 — Runtime Cleanup Plan

**Document ID:** CANONICAL-003  
**Status:** Classification only — **no delete, move, rename, or modify**  
**Scope:** `public_html/` primary runtime (excludes `dist/`, `release/` copies)  
**Purpose:** Single authority for which files are active, deprecated, or forbidden

---

## Classification Legend

| Class | Meaning |
|-------|---------|
| **KEEP_ACTIVE** | Canonical production path; maintain and UAT |
| **KEEP_HELPER** | Include/library; required by active pages |
| **DEPRECATE_ROUTE** | Still reachable but superseded; redirect or hide in nav |
| **ARCHIVE_NOT_EXECUTED** | Exists in repo; not in active user journey |
| **DELETE_CANDIDATE_AFTER_APPROVAL** | Safe to remove only after owner approval + nav audit |
| **SECRET_RISK** | May expose or mishandle secrets; do not commit config |
| **UNKNOWN_REVIEW_REQUIRED** | Needs owner/browser trace before classification |
| **FORBIDDEN_TO_TOUCH** | Frozen by program reset; no Cursor edits |

---

## OTP Domain

| File | Purpose | Active | Duplicate | Risk | Classification | Commit |
|------|---------|--------|-----------|------|----------------|--------|
| `public_html/check-otp.php` | Legacy OTP check endpoint | No | Yes — vs `api/customer/*` | Medium — 410 stub | **DEPRECATE_ROUTE** | Only after OTP phase |
| `public_html/send-otp.php` | Legacy send OTP | No | Yes | Medium — 410 stub | **DEPRECATE_ROUTE** | Only after OTP phase |
| `public_html/verify-otp.php` | Legacy verify OTP | No | Yes | Medium — 410 stub | **DEPRECATE_ROUTE** | Only after OTP phase |
| `public_html/send-contract-otp.php` | Contract OTP send (legacy root) | No | Yes — vs contract API | Medium | **DEPRECATE_ROUTE** | After OTP phase |
| `public_html/verify-contract-otp.php` | Contract OTP verify (legacy root) | No | Yes | Medium | **DEPRECATE_ROUTE** | After OTP phase |
| `public_html/includes/m360-otp-helper.php` | Canonical OTP send/verify logic | **Yes** | No | **High** — IPPanel auth; modified in open tree | **FORBIDDEN_TO_TOUCH** | **NOT_ELIGIBLE** |
| `public_html/includes/m360-otp-config-loader.php` | Loads private OTP config | **Yes** | No | **SECRET_RISK** — reads `private/m360-otp-config.php` | **FORBIDDEN_TO_TOUCH** | **NOT_ELIGIBLE** |
| `public_html/includes/m360-legacy-otp-deprecation-stub.php` | Returns HTTP 410 for legacy routes | Yes | No | Low | **KEEP_HELPER** | After OTP browser pass |
| `private/m360-otp-config.example.php` | Example config template | Yes | No | Low if no secrets | **KEEP_HELPER** | Safe (template only) |
| `public_html/api/customer/send-otp.php` | Canonical customer OTP send | **Yes** | No | **High** — browser failing | **KEEP_ACTIVE** | After browser OTP pass |
| `public_html/api/customer/verify-otp.php` | Canonical customer OTP verify | **Yes** | No | High | **KEEP_ACTIVE** | After browser OTP pass |

**OTP diagnosis (current):** Browser failure on `customer-request.php` → `api/customer/send-otp.php`. Likely IPPanel auth/header regression + runtime config. **GLOBAL_FIX_OTP_FIRST** before intake UAT.

---

## Customer / Reception Domain

| File | Purpose | Active | Duplicate | Risk | Classification | Commit |
|------|---------|--------|-----------|------|----------------|--------|
| `public_html/customer-request.php` | Customer online intake wizard | **Yes** | No | **High** — OTP fail blocks flow | **KEEP_ACTIVE** | After browser pass |
| `public_html/erp-reception-workbench.php` | Staff reception hub (landing + sections) | **Yes** | No | Medium | **KEEP_ACTIVE** | After intake UAT |
| `public_html/erp-reception-intake-file.php` | P11.9 intake wizard (canonical) | **Yes** | Overlaps legacy detail pages | Medium | **KEEP_ACTIVE** | After intake UAT |
| `public_html/erp-reception-intake-save.php` | Intake POST handler | **Yes** | No | Medium | **KEEP_ACTIVE** | After intake UAT |
| `public_html/customer-intake-contract-review.php` | Customer cartable (کارتابل مشتری) | **Yes** | Was "public link" framing | Medium | **KEEP_ACTIVE** | After cartable browser UAT |
| `public_html/includes/m360-reception-helper.php` | Reception utilities | **Yes** | No | Low | **KEEP_HELPER** | With intake phase |
| `public_html/includes/m360-reception-workbench-helper.php` | Workbench + cartable payload logic | **Yes** | No | Medium — payload heavy | **KEEP_ACTIVE** | After cartable UAT |
| `public_html/assets/js/m360-reception-intake.js` | Intake wizard client | **Yes** | No | Medium | **KEEP_ACTIVE** | After intake UAT |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Luxury UI stylesheet | **Yes** | No | Low | **KEEP_ACTIVE** | With UI phase |
| `public_html/erp-reception-online-requests.php` | Legacy online request list | Partial | Yes — vs workbench hub | Low | **DEPRECATE_ROUTE** | After nav consolidation |
| `public_html/erp-reception-online-request-detail.php` | Legacy request detail | Partial | Yes — vs intake-file | Medium | **DEPRECATE_ROUTE** | After intake UAT |
| `public_html/erp-reception-online-request-accept.php` | Legacy accept action | Partial | Yes | Medium | **DEPRECATE_ROUTE** | After intake UAT |

---

## JobCard / Operation Domain (public_html only)

### KEEP_ACTIVE — Core operational path (P2–P7)

| File | Purpose | Active | Classification |
|------|---------|--------|----------------|
| `erp-reception-jobcards.php` | Reception JobCard list | Yes | **KEEP_ACTIVE** |
| `erp-reception-jobcard-detail.php` | Reception JobCard detail | Yes | **KEEP_ACTIVE** |
| `erp-reception-jobcard-action.php` | Reception JobCard actions | Yes | **KEEP_ACTIVE** |
| `erp-technical-jobcard-detail.php` | Technical JobCard detail | Yes | **KEEP_ACTIVE** |
| `erp-technical-jobcard-action.php` | Technical actions | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-timeline.php` | JobCard timeline | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-operation-flow.php` | Operation flow stepper | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-authorization-gate.php` | Authorization gate | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-delivery-eligibility.php` | Delivery eligibility | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-delivery-clearance.php` | Delivery clearance | Yes | **KEEP_ACTIVE** |
| `erp-jobcard-final-readiness.php` | Final readiness | Yes | **KEEP_ACTIVE** |

### ARCHIVE_NOT_EXECUTED / UNKNOWN — Duplicate command centers

Multiple overlapping "command center" and "workbench" pages exist from iterative RC builds. **Not all are linked from canonical nav.**

| File | Classification | Notes |
|------|----------------|-------|
| `erp-jobcard-command-center.php` | **UNKNOWN_REVIEW_REQUIRED** | Overlaps command-workbench |
| `erp-jobcard-command-workbench.php` | **UNKNOWN_REVIEW_REQUIRED** | May be demo RC path |
| `erp-operational-command-center.php` | **ARCHIVE_NOT_EXECUTED** | RC iteration |
| `erp-operation-control-center.php` | **ARCHIVE_NOT_EXECUTED** | RC iteration |
| `erp-unified-operational-closure-dashboard.php` | **ARCHIVE_NOT_EXECUTED** | RC iteration |
| `erp-service-operation-board-ux.php` | **UNKNOWN_REVIEW_REQUIRED** | UX variant |
| `erp-service-operation-detail-ux.php` | **UNKNOWN_REVIEW_REQUIRED** | UX variant |
| `erp-service-operation-workbench-ux.php` | **UNKNOWN_REVIEW_REQUIRED** | UX variant |
| `erp-operation-performance-report.php` | **KEEP_HELPER** | Reporting |
| `erp-operational-kpi.php` | **KEEP_HELPER** | KPI |

**Rule:** Do not delete command-center duplicates until owner picks **one** operational entry point per role.

---

## Auth / Security — FORBIDDEN_TO_TOUCH

| File | Classification |
|------|----------------|
| `public_html/includes/staff-auth.php` | **FORBIDDEN_TO_TOUCH** |
| `public_html/includes/access-control.php` | **FORBIDDEN_TO_TOUCH** |
| `public_html/login.php` / staff login routes | **FORBIDDEN_TO_TOUCH** |

---

## Tools / Tests (Reference Only — Not Runtime Routes)

| Pattern | Classification |
|---------|----------------|
| `tools/test-*.php` | **KEEP_HELPER** — fixture tests; not completion proof |
| `tools/diagnose-*.php` | **KEEP_HELPER** |
| `tools/fixtures/*` | **KEEP_HELPER** — never production data |

---

## Release / Dist Copies

| Path | Classification |
|------|----------------|
| `dist/moghare360-v1-local-demo-rc/` | **ARCHIVE_NOT_EXECUTED** — do not edit |
| `release/moghare360-*` | **ARCHIVE_NOT_EXECUTED** — packaging snapshots |

**Rule:** All cleanup decisions apply to `public_html/` source only.

---

## Cleanup Phases (No Action Until Approved)

### Phase R1 — OTP stabilization (48h)
- Fix browser OTP on canonical API path only
- Leave legacy root routes as 410 stubs
- No helper rewrite unless phase explicitly allows

### Phase R2 — Reception path consolidation (2 weeks)
- Mark legacy online-request pages DEPRECATE_ROUTE
- Single nav path: workbench → intake-file
- Browser UAT all wizard steps

### Phase R3 — JobCard UI deduplication (1 month)
- Owner selects canonical command center per role
- ARCHIVE_NOT_EXECUTED pages removed from nav
- DELETE_CANDIDATE_AFTER_APPROVAL list finalized

### Phase R4 — Physical delete (owner only)
- No file delete until R1–R3 complete + git clean split approved

---

## Working Tree Risk (Current)

| Risk | Count / state |
|------|---------------|
| Open modified/untracked files | ~114 entries |
| OTP files modified while frozen | Violation — revert or isolate in commit split |
| Fixture PASS vs browser FAIL | Tests cannot drive cleanup alone |

---

## Commit Eligibility by Class

| Class | Commit rule |
|-------|-------------|
| KEEP_ACTIVE fixes | After browser UAT for that module |
| FORBIDDEN_TO_TOUCH | **NOT_ELIGIBLE** without owner unlock |
| DEPRECATE_ROUTE | Document only until redirect implemented |
| DELETE_CANDIDATE | **NOT_ELIGIBLE** until owner approval |
| SECRET_RISK | Never commit private config |

---

**END OF RUNTIME CLEANUP PLAN**
