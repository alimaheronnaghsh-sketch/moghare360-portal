# MOGHARE360 UNIFIED INTAKE ROUTE LOCK

**Document type:** Canonical route authority (docs only)  
**Status:** OWNER-LOCKED  
**Date locked:** 2026-07-23  
**Scope:** Intake / walk-in / photo / contract spine only  
**Out of scope:** Estimate, inventory/SCM, Task 41 mutation, synthetic case creation

---

## 1. Owner Decision

The owner has accepted the unified intake route canonicalization plan and locked the following architecture decisions:

1. **Parallel intake routes will be retired.** Online and walk-in are entry channels only; they must not own separate completion, photo, or contract workflows.
2. **Physical deletion only after audit and owner UAT.** No file deletes until Phase I after successful owner UAT.
3. **Point fixes are stopped.** Narrow stop-gap patches outside this spine are forbidden while route canonicalization is in progress.
4. **Commit is forbidden until route canonicalization phases pass.** No product commit, stage, or push for intake/walk-in/photo/contract route work until Phases C–F pass owner UAT. Docs may be committed separately only after explicit owner authorization.
5. **No synthetic master UAT cases.** Codex/Cursor must not create synthetic case-study or master UAT cases. Owner-defined UI cases only.

---

## 2. Canonical Intake Route

The **only** accepted product spine for intake is:

```
Customer Online Request
  → Reception Intake / Completion
  → Customer Signature / OTP
  → Back to Reception
  → Prepayment / Owner Gate
  → Hall / JobCard
```

Any new intake, walk-in, photo, or contract path that bypasses this spine is non-compliant unless the owner explicitly re-opens this lock.

---

## 3. Entry Channels

There are **exactly two** entry channels. Both create the same canonical `online_request` object. After creation, both must enter the shared completion engine.

### A. Online Customer Entry

| Item | Value |
|------|--------|
| Pages | `customer-request.php` |
| API | `api/customer/request.php` |
| `source_channel` | `PUBLIC` (canonical public online channel) |
| Actor | Customer |
| After create | Customer profile / cartable awareness; reception opens shared intake completion |

### B. Walk-in Staff Entry

| Item | Value |
|------|--------|
| Pages | `erp-reception-walkin-create.php` (UI), `erp-reception-walkin-save.php` (create-only POST) |
| Lookup API | `api/staff/customer-lookup.php` |
| `source_channel` | `STAFF_ASSISTED_WALKIN` |
| Actor | Reception / authorized staff |
| Required | `created_by_staff_user_id` (current staff user) |
| Object created | Same canonical `online_request` as online entry |
| After create | Redirect to shared reception intake completion |

Walk-in is defined as: **staff-started online request**. It is not a separate product workflow.

---

## 4. Shared Completion Engine

The **only** reception completion engine is:

| Role | File |
|------|------|
| UI / wizard | `erp-reception-intake-file.php` |
| Write / actions | `erp-reception-intake-save.php` |

### Responsibilities (canonical)

1. Customer confirmation if incomplete  
2. Vehicle confirmation / hydration (no unnecessary plate/brand re-ask when identity is already complete)  
3. Current mileage and fuel (visit fields)  
4. Service / problem classification  
5. Condition checklist  
6. Six reception photos  
7. Documents / diagnostic gate  
8. Contract send to customer cartable  

Online and walk-in must not duplicate this engine. Navigation queues (`erp-reception-online-requests.php`, detail pages, workbench) may open this engine; they must not replace it.

---

## 5. Customer Signature Engine

Canonical customer signature path:

| Role | File / object |
|------|----------------|
| Customer hub | `customer-profile.php` |
| Contract review / sign | `customer-intake-contract-review.php` |
| Task store | `erp_customer_cartable_tasks` (contract signature task) |
| Auth | OTP and/or authenticated customer session required |

### Hard rules

- Staff **cannot** sign or approve the intake contract for the customer.  
- Staff may prepare/send the contract review task from reception intake completion only.  
- Legacy token-only or pre-cartable contract pages are **not** primary product routes.

---

## 6. Reception Return

After the customer signs:

1. Reception sees signed / accepted contract status on the shared intake engine (and workbench as needed).  
2. Prepayment / owner gate opens.  
3. Hall / JobCard handoff proceeds only after contract + financial/operation gates allow it.

Reception return is part of the same spine — not a parallel “post-walk-in” path.

---

## 7. Canonical Data Object Chain

### Primary spine

| Object | Role |
|--------|------|
| `online_request_id` | **Primary spine ID** carried through all intake steps |
| `customer_id` | Bound customer |
| `vehicle_id` | Bound vehicle |
| `request_payload_json.reception_intake` | Canonical nested intake state |
| `erp_customer_cartable_tasks` | Customer-facing contract (and later estimate) tasks |
| Contract state | Nested payload contract + contract records as generated |
| Prepayment gate | Operation / financial gate after signature |
| `jobcard_id` | Downstream hall / operations identity |

### Canonical payload keys under `reception_intake`

- `vehicle`  
- `condition`  
- `service_classification`  
- `documents`  
- `photos`  
- `contract`  
- `reception_completed`  
- `operation_gate`  
- `section_status`  

### Source of truth notes

- Customer / vehicle master data: ERP customer and vehicle tables + relations.  
- Visit / intake snapshot: `request_payload_json.reception_intake`.  
- Photos: filesystem under reception-intake storage + metadata in `reception_intake.photos`.  
- Signature readiness and completion: cartable task + contract state in payload (and contract table when generated).

Deprecated as primary product truth: walk-in-only workflow flags, photo placeholders without `photos.slots`, and legacy customer-contract ack paths that bypass cartable review.

---

## 8. Photo Canonical Path

| Item | Locked value |
|------|----------------|
| Endpoint | `erp-reception-intake-save.php` only |
| Action | `action_type=save_camera_photo` |
| Transport | Base64 in hidden form field (not a separate photo product route) |
| Filesystem | `storage/reception-intake/{online_request_id}/` |
| Metadata | `request_payload_json.reception_intake.photos` |
| Required slots | 6 (front, rear, right, left, cabin, dashboard) |
| Contract gate | Contract send blocked until 6/6 photos complete |

### Forbidden

- Separate photo-only product routes.  
- Walk-in photo placeholder UI that pretends photos are saved.  
- Attaching photos to a different object when the request spine is `online_request_id`.

---

## 9. Contract Canonical Path

| Step | Locked path |
|------|-------------|
| Prepare / send | Reception intake-file / intake-save (`prepare_customer_contract_review` / completion bootstrap) |
| Customer task | Cartable contract signature task |
| Customer UI | `customer-intake-contract-review.php` (canonical) |
| Auth | OTP / session required |
| Staff | May send link/task; **must not** sign for customer |

### Non-primary (retirement rules apply)

- Legacy token pages such as `customer-intake-contract.php` / sign variants  
- `customer-contract.php` and related submit confirmation paths  
- Staff board send/generate pages used as **primary** product entry instead of intake-file  

Staff boards (`erp-intake-contracts.php`, detail) may remain read-only / coordination after UAT, not alternate signing engines.

---

## 10. Route Retirement Rules

### KEEP

- `customer-request.php`  
- `api/customer/request.php`  
- `erp-reception-walkin-create.php` as **slim entry** only  
- `erp-reception-walkin-save.php` as **create-only POST**  
- `api/staff/customer-lookup.php`  
- `erp-reception-intake-file.php`  
- `erp-reception-intake-save.php`  
- `erp-reception-online-requests.php`  
- `erp-reception-online-request-detail.php`  
- `erp-reception-workbench.php`  
- `customer-profile.php`  
- `customer-intake-contract-review.php`  
- Prepayment / hall / JobCard pages (downstream)

### REDIRECT AFTER UAT

- Legacy token contract pages  
- `customer-service-request.php`  
- `submit-service-request.php`  
- `customer-contract.php`  
- `submit-contract-confirmation.php`  
- Direct deep-links to plate/photo-only UIs (must land on intake-file with `online_request_id`)

### READ-ONLY

- `erp-intake-contract-detail.php`  
- `erp-intake-contracts.php`  
- `customer-request-status.php`  
- Legacy `customer-intake-contract.php` until fully redirected  

### REMOVE FROM NAVIGATION

- Hardcoded UAT links  
- Request 28 shortcuts  
- Contract 3 shortcuts  
- Staff operational links treating `customer-request.php` as a staff ops page  
- Misleading wording such as “بدون درخواست آنلاین” for walk-in (walk-in **is** a staff-started online request)

### DELETE AFTER UAT ONLY

- `customer-contract.php`  
- `submit-contract-confirmation.php`  
- `customer-service-request.php`  
- `submit-service-request.php`  
- `customer-intake-contract-sign.php`  
- Obsolete walk-in photo placeholder UI blocks  

**No physical deletion before Phase I and owner UAT.**

---

## 11. Walk-in Conversion Rule

Walk-in **is** staff-started online request.

### Walk-in must not own

- Vehicle completion (beyond seed / select)  
- Photo completion  
- Contract send  
- Customer signature  
- Separate workflow state parallel to `reception_intake`

### Walk-in may only

1. Search / create customer  
2. Select / create vehicle  
3. Seed minimal visit data when needed  
4. Create the canonical `online_request` with `STAFF_ASSISTED_WALKIN` and staff creator id  
5. Redirect to reception intake completion (`erp-reception-intake-file.php?online_request_id=…`)

After create, all remaining intake work belongs to the shared completion engine.

---

## 12. Prohibited Future Work

Without explicit owner approval to reopen this lock, the following are **forbidden**:

1. Creating a new intake route  
2. Creating a new walk-in completion engine  
3. Creating a new photo route  
4. Creating a new contract signing route  
5. Staff signing the customer intake contract  
6. Creating synthetic / master UAT cases by Codex or Cursor  
7. Committing parallel routes as product pages  
8. Physical deletion of retired routes before owner UAT  
9. Point fixes that preserve parallel vehicle/photo/contract workflows  
10. Staging or committing product intake changes before Phases C–F pass owner UAT  

---

## 13. Implementation Phases

| Phase | Name | Intent | Commit |
|-------|------|--------|--------|
| **A** | Route Lock Doc | This document — canonical authority | Docs only; commit only if owner authorizes |
| **B** | Navigation retirement / relabel | Remove-from-nav, relabel; **no deletes** | After owner UAT of nav |
| **C** | Walk-in → staff-started online request | Slim entry; same request object; redirect to intake-file | After owner UAT |
| **D** | Intake-file sole completion engine | Hydration; no plate/brand re-ask when complete; one wizard | After owner UAT |
| **E** | Photo save UX / persistence hardening | One photo path; 6/6 visible and durable | After owner UAT |
| **F** | Contract send / sign canonical path | Cartable + review only; staff cannot sign | After owner UAT |
| **G** | Owner UAT full spine | Owner-defined UI cases only | No |
| **H** | Commit groups | Split, reviewed, non-monolithic | Owner-authorized groups only |
| **I** | Physical delete retired routes | After UAT proof of zero reliance | Last; owner-authorized |

Phase A is complete when this file exists and owner reviews it. Implementation starts at Phase B only after owner direction.

---

## 14. Git Impact

1. The large working tree (≈106 files) remains **HOLD** for product commits related to intake until route phases pass.  
2. Reception / walk-in change group remains **ROUTE_CANONICALIZATION_REQUIRED**.  
3. **No product commit** until Phases C–F pass owner UAT.  
4. **Docs may be committed separately** only after explicit owner authorization.  
5. **No monolithic commit** of the full working tree.  
6. No `git add .` / `git add -A` for this workstream.  
7. Estimate, inventory/SCM, and Task 41 remain outside this lock’s commit eligibility.

---

## 15. Sign-off Statement

This document is the canonical intake routing authority for MOGHARE360.  
Any future intake, walk-in, photo, or contract route must comply with this lock.  
Parallel route creation is forbidden without explicit owner approval.
