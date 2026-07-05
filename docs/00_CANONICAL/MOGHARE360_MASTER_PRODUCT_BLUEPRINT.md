# MOGHARE360 — Master Product Blueprint

**Document ID:** CANONICAL-001  
**Status:** EXECUTION AUTHORITY — Program Reset PR-00; **vehicle scope aligned PR-02-GOVERNANCE-LOCK (2026-07-06)**  
**Supersedes:** Scattered mission/audit docs for scope decisions (implementation detail remains in phase reports until archived)  
**Encoding:** UTF-8

---

## 1. Product Identity

| Attribute | Definition |
|-----------|------------|
| **Product name** | MOGHARE360 ERP |
| **Brand** | Moghareh Motors / MOGHARE360 |
| **Type** | Persian RTL auto-workshop ERP |
| **Primary language** | Persian (managerial UI); controlled English technical terms allowed |
| **Deployment model** | Internal server with static IP; web-first; desktop/Android/iPhone/PWA as later packaging layers |
| **Adaptability** | Architecture must support adaptation to other service companies without uncontrolled fork explosion |
| **UX principle** | Luxury UI, minimum clicks, camera-direct media capture (no upload bypass unless owner-approved) |
| **Security principle** | Enterprise-grade internal security; role-based access; audit trail; no secrets in repository |
| **Completion standard** | Browser UAT + SQL truth + owner signoff — **not** fixture-only pass, demo, dry run, or partial RC |

**This document is the mother product blueprint.** All future Cursor tasks must align to this file before writing code.

---

## 2. Final ERP Modules

| # | Module | Purpose |
|---|--------|---------|
| 1 | Reception (online + in-person) | Customer intake, file completion, temporary/full reception |
| 2 | QC | Quality inspection, pass/fail, delivery readiness |
| 3 | Delivery / release | Vehicle release, customer delivery sign-off |
| 4 | Legal contracts | Intake contract templates, acceptance, audit |
| 5 | Customer cartable | Customer-facing contract/approval tasks (real portal later; hybrid bridge interim) |
| 6 | JobCard | Operational case file from reception through workshop |
| 7 | Internal repairs | In-house workshop execution |
| 8 | External repairs | Subcontract / external vendor repair tracking |
| 9 | Accounting | Full ledger-grade accounting (future activation; not claimed until UAT) |
| 10 | Audit | Financial and operational audit trail |
| 11 | HR | Employees, attendance, contracts, payroll integration path |
| 12 | Administration | Users, roles, access, company settings |
| 13 | Inventory | Parts catalog, stock, locations, movements |
| 14 | Logistics | Shipping, receiving, transfer between locations |
| 15 | Domestic purchase | Local supplier purchase requests and approval |
| 16 | Foreign import | Import orders, customs, foreign supplier flow |
| 17 | Service sales | Billable service lines, service price lists |
| 18 | Parts sales | Counter/retail parts sales |
| 19 | CRM / follow-up | Post-service follow-up, satisfaction, upsell |
| 20 | Owner dashboard | KPI, management oversight, go/no-go |
| 21 | Security / audit logs | Access control, change history, security events |

---

## 3. Vehicle Scope

**Governance lock:** PR-02-GOVERNANCE-LOCK (2026-07-06). PR-02A runtime implementation must not start until owner reviews this section and the preflight report.

### 3.1 Current approved brand allowlist (production MOGHARE360)

Only the following brands are permitted in **current** production data entry unless owner explicitly approves additions:

| Brand (EN) | Brand (FA) | Normal supported brand? |
|------------|------------|-------------------------|
| Benz / Mercedes-Benz | بنز | **Yes** |
| BMW | ب‌ام‌و | **Yes** |
| Porsche | پورشه | **Yes** |
| Volvo | ولوو | **Yes** |
| Volkswagen | فولکس‌واگن | **Yes** |
| Other | سایر | **No** — exception path only (§3.2) |

**Discovery source for model/subclass lists:** `public_html/assets/js/vehicle-brand-classes.js` on the customer online site (`customer-request.php`). Implementers must not invent model/subclass lists. If lists are incomplete, report **OWNER_MODEL_LIST_DECISION_REQUIRED** — do not guess.

### 3.2 Top-level Other / سایر (brand-level exception)

Top-level **Other / سایر** is **not** a normal supported vehicle brand.

| Rule | Requirement |
|------|-------------|
| Meaning | **خارج از محدوده استاندارد** / **نیازمند بررسی** / **نیازمند تأیید مدیر** |
| Model/subclass UI | Must **not** activate normal model/subclass dropdowns |
| Explanation | **Required** |
| Manager exception | **Required** to proceed |
| Management reporting | Cases must be reported separately in management reports |
| Who may approve | Receptionist **cannot** approve; customer **cannot** approve; system marks **نیازمند تأیید مدیر** |
| PR-02A scope | Full manager-approval UI/workflow only if already safely supported; otherwise report **SQL_OR_WORKFLOW_PROPOSAL_NEEDED_LATER** |

### 3.3 Per-brand model / subclass rules

1. Model and subclass options must **depend on the selected brand** (cascading select).
2. **Porsche** → only Porsche models/subclasses (e.g. Macan) after Porsche is selected.
3. **Benz** → only Benz classes/models after Benz is selected.
4. **BMW** → only BMW series/models after BMW is selected.
5. **Volvo** → only Volvo models after Volvo is selected.
6. **Volkswagen** → only Volkswagen models after Volkswagen is selected.
7. **No free-text** brand/model in final production UI for the five supported brands.
8. Cursor must **not** invent lists — use existing customer-site mapping; gaps → **OWNER_MODEL_LIST_DECISION_REQUIRED**.

### 3.4 Per-brand model Other / سایر (model-level gap — not brand-level Other)

Per-brand model **Other / سایر** inside a supported brand list is **not** the same as top-level brand Other (§3.2).

| Aspect | Per-brand model سایر | Top-level brand سایر |
|--------|----------------------|----------------------|
| Brand scope | Supported brand selected | Outside standard brand universe |
| Meaning | Model/subclass missing from curated list | Vehicle outside MOGHARE360 brand scope |
| Model lists | Other brands’ lists remain disabled | No model lists at all |
| Explanation | **Required** | **Required** |
| Classification | **MODEL_LIST_GAP** / **نیازمند تکمیل لیست مدل** | **نیازمند تأیید مدیر** / exception |
| Bypass | Must **not** bypass brand scope control | Exception path only |

### 3.5 Calendar / visit date rules (customer online + aligned reception)

| Rule | Requirement |
|------|-------------|
| Calendar system | **Persian / Solar Hijri / Jalali** |
| Horizon | **30 working days** (not 30 consecutive calendar days) |
| Fridays | **Disabled** |
| Iran official holidays | **Disabled** |
| Free-text date | **Not** normal workflow |
| Holiday data source | If no official holiday source exists in repo/runtime, report **IRAN_OFFICIAL_HOLIDAY_SOURCE_MISSING** — **do not invent** holiday data |

**Current runtime note (discovery):** `customer-request.php` today renders ~30 consecutive calendar days; alignment to 30 **working** days + holiday disable is a PR-02A engineering task after governance lock review.

### 3.6 General vehicle data rules

1. **No new brand or model** may be added to master data, dropdowns, or validation allowlists without **written owner approval**.
2. Model lists are **owner-curated per brand** — not free-text in final production UI (except governed exception paths in §3.2–3.4).
3. VIN/plate validation must reject vehicles outside approved brand scope when enforcement phase is active.
4. Legacy/test brands in database (e.g. mirror test data) must be cleaned or flagged — not treated as production truth.

### 3.7 Future scope — Asian brands project (NOT current MOGHARE360)

The following brands were previously listed in error as **current** scope. They belong to a **separate / future Asian-brands project only** and are **not** part of current MOGHARE360 production allowlist until a future owner-approved program unlocks them:

| Future project only | |
|---------------------|---|
| Toyota | |
| Lexus | |
| Kia | |
| Hyundai | |
| BYD | |
| Lucano | |
| Chery | |

Do not implement, validate, or market these brands in current MOGHARE360 intake/reception without a new governance lock.

---

## 4. Customer Journey (End-to-End)

```
Online request OR in-person trigger
  → OTP identity verification
  → Reception intake file opened
  → Vehicle data (owner-approved brands/models)
  → 6 camera photos (mandatory slots)
  → Condition notes
  → Service / diagnostic classification gate
  → Referral (when required)
  → Diagnostic document + cost agreement
  → Legal contract / customer cartable task assigned
  → Customer acceptance (OTP-verified identity)
  → Staff signature + intake lock
  → Controlled JobCard conversion (owner-approved phase only)
  → Workshop / hall
  → Estimate → approval → parts → work
  → QC → invoice → delivery → CRM follow-up
```

**Interim (until real customer portal):** Customer cartable may use token-based hybrid bridge; UI/copy must say **کارتابل مشتری**, not raw public link.

---

## 5. Staff / Reception Journey

| Step | Staff action | Completion gate |
|------|--------------|-----------------|
| 1 | Open intake from online request or walk-in | Staff auth |
| 2 | Verify/resend customer OTP | OTP verified on request |
| 3 | Complete vehicle identity | Canonical fields non-empty |
| 4 | Complete condition | All condition fields |
| 5 | Classify service route | Path clear gate |
| 6 | Assign referral team | When wizard requires |
| 7 | Capture 6 photos | Camera-direct; 6/6 complete |
| 8 | Upload diagnostic + cost agreement | Documents step |
| 9 | Assign customer cartable contract task | Prerequisites gate |
| 10 | Wait for customer acceptance | Cartable status |
| 11 | Staff signature + lock | Post-signature lock immutable |

**Canonical pages:** `erp-reception-workbench.php` (hub) → `erp-reception-intake-file.php` (wizard).

**Legacy pages** (`erp-reception-online-request-detail.php`, etc.) remain until runtime cleanup phase deprecates them.

---

## 6. Operation Journey

| Phase | Activities |
|-------|------------|
| JobCard creation | Controlled conversion from locked intake — **not automatic** |
| Hall / workshop | Assignment, bay, technician |
| Internal repair | Service operations, steps, labour |
| External repair | External vendor tracking |
| Estimate | Line items, customer approval gate |
| Parts | Reservation, consumption, finance gate |
| Work execution | P5 workflow |
| QC | Inspection checklist, pass/fail |
| Invoice | Final invoice, settlement |
| Delivery | Customer delivery sign, vehicle release |
| Follow-up | CRM scheduling |

---

## 7. Forbidden Scope Until Owner Approval

| Forbidden item | Reason |
|----------------|--------|
| SaaS multi-tenant production | Not in current program target |
| Payment gateway integration | External dependency; regulatory |
| Official full accounting claim | Until accounting module UAT complete |
| Real customer portal / customer login architecture | Deferred; hybrid bridge only |
| Automatic JobCard on intake save | Owner-controlled conversion only |
| P12 scope | Explicitly out of program reset |
| Production deploy to live customer site | Until hardened product milestone |
| Auth/Login rewrite (`staff-auth.php`, `access-control.php`) | Frozen boundary |
| Permission model / role seed rewrite | Frozen boundary |
| DB schema expansion | Requires full SQL proposal + owner approval |
| Physical file delete in runtime | Owner approval required |
| Commit / push by Cursor | Owner-only |
| Fixture-only test pass as completion | Program rule violation |
| Production-ready claim | Until browser UAT + owner signoff |

---

## 8. Definition of “Complete”

A module, phase, or feature is **complete** only when **all** of the following are true:

| Criterion | Required |
|-----------|----------|
| Browser UAT | Staff and customer flows tested on XAMPP/staging with real click path |
| SQL truth | Data persisted in intended tables; payload-only hacks documented with migration plan |
| No fixture-only pass | CLI tests supplement but do not replace browser proof |
| No secret risk | Private config gitignored; no API keys in repo |
| No open unstable runtime | No known browser regression in module scope |
| Owner signoff | Explicit approval recorded |
| Canonical docs updated | Scope/report reflects actual state |
| Commit eligibility declared | Owner decides when to commit |

**Partial RC, soft run, demo, and dry run are not "complete."**

---

## 9. Architecture Principles (Locked)

1. **UI → Validation → Workflow → Database → Audit** — no layer skipping.
2. **Camera direct only** for intake photos unless owner unlocks upload.
3. **Persian RTL** default for all product-facing pages.
4. **CSRF** on all staff POST writes.
5. **Token hash only** for customer-facing secure links — never store raw tokens in DB/payload.
6. **Idempotent SQL migrations** — no destructive DDL without owner approval.
7. **Minimum clicks** — wizard/stepper UX over fragmented page chains.

---

## 10. Document Hierarchy

```
docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md  ← THIS FILE (scope authority)
docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md           ← time/phases
docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md      ← agent rules
docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md         ← DB truth
docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md        ← file classification
```

Phase-specific scope reports implement slices of this blueprint. On conflict: **this blueprint wins for product intent**; phase scope wins for file-level implementation detail.

---

**END OF MASTER PRODUCT BLUEPRINT**
