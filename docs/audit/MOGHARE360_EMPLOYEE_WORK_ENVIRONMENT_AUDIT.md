# MOGHARE360 V1 RC — Employee Work Environment / Runtime Page Audit

**Report ID:** P11.6-1  
**Date:** 2026-06-26  
**Scope:** Read-only audit of `public_html/` runtime pages, role landing, route registry, and operational flow coverage  
**Audit type:** REPORT ONLY — no code, database, Auth/Login, permission, or route changes.

---

## 1. Executive Summary

MOGHARE360 V1 RC contains a **complete P1–P7 operational workflow chain** (intake → reception JobCard → technical → estimate → work execution → QC → invoice/settlement → customer delivery APIs). Action handlers, CSRF, and DB writes exist for most workflow steps.

However, the **daily employee work environment is fragmented**:

- After P11.4.4-B/C, staff land on `erp-staff-home.php` with **3–7 route cards per role** — not a unified workbench.
- **Owner/product navigation** (`erp-product-home.php`, `erp-route-map.php`) mixes operational modules with **demo, soft-run, and release pages**.
- The repository has **234 `erp-*.php` pages** but roughly **half are reporting, demo, soft-run, executive, UX prototype, or access-admin** — not daily employee desks.
- **P1–P10 route registry:** 63/63 registered routes **exist on disk** — no missing core workflow files in the official map.
- **Staff landing gaps:** 2 linked files are **missing** (`erp-jobcard-part-usage-list.php`, `erp-finance-center.php`); several **real work pages exist but are not linked** from role landing (contract board, estimate board, part usage, online request accept).

**Verdict:** Workflow **code exists**; employee **workbench UX does not**. One-Day Run is **technically possible for a guided owner/operator** but **not ready as a self-service daily environment** for reception, technician, parts, or finance staff.

---

## 2. Repository Scale Snapshot

| Metric | Count | Notes |
|--------|------:|-------|
| All PHP under `public_html/` | **486** | Excludes `dist/` / `release/` copies |
| `erp-*.php` pages | **234** | Largest page group |
| `submit-*.php` action handlers | **41** | Non–user-facing POST endpoints |
| `api/**/*.php` endpoints | **26** | Customer + auth + management APIs |
| P1–P10 navigation registry routes | **63** | **63/63 files present** |
| Soft-run pages (`erp-soft-run*`) | **24** | Demo/pilot/findings |
| Executive pages (`erp-executive*`) | **5** | Go/no-go decision tooling |
| UX prototype pages (`erp-*-ux.php`) | **12** | Alternate/prototype UI shells |
| Markdown docs (`docs/`) | **1,122** | High doc-to-workbench ratio |
| CLI tests (`tools/test-*.php`) | **199** | Strong automation; mostly structural |

---

## 3. Page Classification Summary

### Classification rules applied (strict)

| Category | Definition |
|----------|------------|
| **Employee Work Environment** | User can create/update/progress/approve/submit/assign/close operational records |
| **Management / Owner Dashboard** | KPI, command center, review, audit, readiness, status visibility |
| **Demo / Product / Release** | Demo scenario, soft-run, RC, route map, presentation, package |
| **Read-only** | Lists/boards/timelines/previews without direct state change on that screen |
| **Action Endpoint** | POST handler or API — not a daily work screen |
| **Missing / Broken** | Referenced in landing/docs but file absent |
| **Not Connected** | File exists; not linked from staff home / product home / route map role path |

### Heuristic distribution (`erp-*.php` only)

| Bucket | Approx. count | Share |
|--------|--------------:|------:|
| Demo / soft-run / release / RC | 47 | 20% |
| Read-only boards / lists / detail viewers | 69 | 29% |
| Management / KPI / control centers | 10 | 4% |
| Access management (P11.4) | 18 | 8% |
| HR admin prototype | 6 | 3% |
| `erp-*-action.php` POST handlers | 7 | 3% |
| Other operational / mixed | 77 | 33% |

**Important pattern:** P1–P7 workflow uses **board (read-only GET) → detail (GET + forms) → `erp-*-action.php` (POST)**. Boards alone are **not** full work environments; detail pages are where daily actions happen.

---

## 4. Reporting Pages vs Work Pages

| Type | Examples | Employee daily use? |
|------|----------|---------------------|
| **Work chain (P1–P7)** | `erp-reception-jobcards.php` → `erp-reception-jobcard-detail.php` → `erp-reception-jobcard-action.php` | **Yes** — if staff knows the chain |
| **Management read-only** | `erp-management-dashboard.php` (“نمای read-only عملیاتی”) | **No** — visibility only |
| **Owner control** | `erp-owner-control-center.php`, `erp-operational-kpi.php` | **No** — oversight |
| **Soft run / demo** | `erp-soft-run-control-room.php`, `erp-end-to-end-demo-scenario.php` | **No** — test/presentation |
| **Release / RC** | `erp-release-readiness.php`, `erp-route-map.php`, `erp-rc-final-audit.php` | **No** — engineering/owner |
| **Access admin** | `erp-access-management.php` | **Owner/admin only** — not workshop |
| **HR admin prototype** | `erp-employee-create.php` | **HR admin only** — not self-service |
| **Hidden operational** | `erp-intake-contracts.php`, `erp-jobcard-part-use.php`, `erp-estimate-board.php` | **Yes** — but **not linked** from role landing |

**Ratio insight:** For every **1** focused employee landing card on `erp-staff-home.php`, the repo contains **~5–10** report/demo/dashboard pages discoverable via product home or route map.

---

## 5. Role Workbench Matrix

| Role | Expected daily work | Existing pages | Missing / wrong link | Connected after login? | Status |
|------|---------------------|----------------|----------------------|------------------------|--------|
| **OWNER / SYSTEM_ADMIN** | Product oversight, access admin, KPI | Product home, management dashboard, owner control, access mgmt, route map, release readiness | None critical | **Yes** → product home or staff home | **READY** (management); not a workshop desk |
| **RECEPTION** | Online requests, JobCards, contract gate, handoff | Online requests, jobcards, jobcard detail, intake contracts (exists), online accept (exists) | Contract board **not on staff home**; online accept **not on staff home** | **Partial** — 3 cards + permission preview | **PARTIAL** |
| **SERVICE_MANAGER** | Queue, assign tech, supervise execution, QC send/return | Technical board, work execution board, QC board, timeline | No dedicated “assign desk”; assign via technical **detail** | **Partial** — boards only on home | **PARTIAL** |
| **TECHNICIAN** | Assigned jobs, diagnosis, notes, parts, complete | Technical board, work execution board | **No “my jobs” filter** on board (global list); part usage **not linked** | **Partial** | **PARTIAL** |
| **PARTS** | Requests, reserve, issue, stock | Catalog, stock board, part reserve, **broken link** part-usage-list | `erp-jobcard-part-usage-list.php` **MISSING**; real usage = `erp-jobcard-part-use.php` **not linked**; purchase list **missing** | **Partial** — 1 card broken | **PARTIAL** |
| **FINANCE** | Payments, estimate approval gate, invoice, settlement | Payment tracking, final invoice board/detail/action, settlement detail/action | `erp-finance-center.php` **MISSING**; `erp-estimate-board.php` **not linked** | **Partial** — 1 card broken | **PARTIAL** |
| **QC** | QC checklist, pass/fail, delivery readiness | QC board, detail (forms), action; delivery control | QC detail **not on staff home** (reachable from board); delivery control prototype permissions | **Partial** | **PARTIAL** |
| **HR / self-service** | Profile, attendance, leave, payroll self-service | Employee create, profile, payroll preview | Attendance, leave, overtime, contract, self-service **missing** | **Not linked** from staff home | **MISSING** |

---

## 6. Reception Work Environment

### Linked from `erp-staff-home.php` (RECEPTION)

| File | Category | Work capability | R/W | Status |
|------|----------|-----------------|-----|--------|
| `erp-reception-online-requests.php` | Read-only board | View/filter online requests | Read-only list | **EXISTS** — accept action on separate page |
| `erp-reception-jobcards.php` | Read-only board | Filter JobCards, link to detail | Read-only list | **EXISTS** |
| `erp-reception-jobcard-detail.php` | **Work screen** | POST forms → progress workflow, notes, gate override | Read + **write via forms** | **EXISTS** |
| `erp-access-permission-preview.php` | Read-only | Permission preview | Read-only | **EXISTS** |

### Exists but NOT on reception staff home

| File | Purpose | Gap |
|------|---------|-----|
| `erp-reception-online-request-detail.php` | Request detail | Not linked from staff home |
| `erp-reception-online-request-accept.php` | **Accept online request (POST)** | Critical daily action — **hidden** |
| `erp-intake-contracts.php` | **P1.5 contract board** | Contract gate — **hidden** |
| `erp-intake-contract-detail.php` | Generate/send contract | Hidden |
| `customer-request.php` | Public intake | Customer-facing, not staff desk |

### Can reception perform daily work login → handoff?

**Partially.** After login, reception sees **lists only** on the home cards. Real work happens on **detail pages** reached from boards. Contract workflow (`erp-intake-contracts.php`) and online accept are **not advertised** on the landing page — staff must know URLs or use product home / route map.

**P1.5 gate:** Reception JobCards show alert if P1.5 gate files missing — contract path must be operational for controlled flow.

---

## 7. Service Manager / Hall Work Environment

### Linked pages

| File | Type | Actions available |
|------|------|-------------------|
| `erp-technical-board.php` | Read-only board | Filter all technical JobCards — **no assignment filter** |
| `erp-work-execution-board.php` | Read-only board | Monitor execution queue |
| `erp-qc-board.php` | Read-only board | QC queue visibility |
| `erp-jobcard-timeline.php` | Read-only | History/timeline — **reporting** |

### Real work location

| File | Actions |
|------|---------|
| `erp-technical-jobcard-detail.php` | Forms: `assign_technician`, diagnosis, send to execution, etc. |
| `erp-technical-jobcard-action.php` | POST handler |
| `erp-work-execution-detail.php` / `-action.php` | Execution state changes |

**Answer:** There is **no dedicated “service manager desk”** page. The manager uses **the same boards as technicians** plus **detail/action** pages. Supervision is **board + drill-down**, not a consolidated assign/monitor console.

---

## 8. Technician Work Environment

### Linked pages

| File | Assessment |
|------|------------|
| `erp-technical-board.php` | Global board — **not filtered to logged-in technician** |
| `erp-work-execution-board.php` | Global execution board |

### Work actions (via detail, not on home)

| File | Assessment |
|------|------------|
| `erp-technical-jobcard-detail.php` | Diagnosis, notes, service operations — **primary workbench** |
| `erp-technical-jobcard-action.php` | POST actions |
| `erp-work-execution-detail.php` | Record/complete work |
| `erp-jobcard-part-use.php` | Part usage prototype — **exists, not linked** |

**Assignment filtering:** `assigned_technician_user_id` exists in schema/helpers; **`assign_technician` action** on detail; board list **does not filter by current user**. Technicians see **all** cards unless they manually find theirs.

**Answer:** Technician has a **functional but non-personalized** work path: board → detail → action. **No clear “my workbench”** after login.

---

## 9. Parts / Inventory Work Environment

### Linked from staff home

| File | Status | Work capability |
|------|--------|-----------------|
| `erp-parts-catalog.php` | **EXISTS** | Catalog browse |
| `erp-stock-board.php` | **EXISTS** | Stock visibility |
| `erp-part-reserve.php` | **EXISTS** | **Reservation form** (POST via inventory helper) |
| `erp-jobcard-part-usage-list.php` | **MISSING** | Staff home shows **disabled card** |

### Hidden / alternate parts pages

| File | Status | Notes |
|------|--------|-------|
| `erp-jobcard-part-use.php` | **EXISTS** | Controlled prototype — register usage + issue movement |
| `erp-jobcard-part-readonly-list.php` | **EXISTS** | Read-only consumption list |
| `erp-purchase-request-create.php` | **EXISTS** | Purchase request form |
| `erp-purchase-request-list.php` | **MISSING** | Not in repo |

**Answer:** Warehouse staff have **reserve + catalog + stock board**, but the **JobCard consumption queue linked from staff home is broken** (wrong filename). Real usage page exists under a **different name** and is **not connected**.

---

## 10. Finance Work Environment

### Linked from staff home

| File | Status | Work capability |
|------|--------|-----------------|
| `erp-finance-center.php` | **MISSING** | Disabled card on staff home |
| `erp-payment-tracking.php` | **EXISTS** | Payment list (Phase 5 pricing engine) — mostly **tracking view** |
| `erp-final-invoice-board.php` | **EXISTS** | Invoice queue |
| `erp-final-invoice-detail.php` | **EXISTS** | Detail + forms |
| `erp-final-invoice-action.php` | **EXISTS** | POST invoice actions |
| `erp-settlement-detail.php` | **EXISTS** | Settlement view/forms |
| `erp-settlement-action.php` | **EXISTS** | POST settlement |

### Not linked for finance role

| File | Role in flow |
|------|--------------|
| `erp-estimate-board.php` | **P4 estimate approval gate** — finance/parts gate before work |
| `erp-estimate-detail.php` / `-action.php` | Estimate actions |

**Answer:** Finance can **close invoice/settlement** via P7 pages if staff navigate board → detail. There is **no finance hub page** (`erp-finance-center.php`). **Estimate workflow** (often finance-adjacent) is **only on product home**, not finance staff landing.

---

## 11. QC Work Environment

### Linked pages

| File | Assessment |
|------|------------|
| `erp-qc-board.php` | Queue — read-only entry |
| `erp-delivery-control.php` | Delivery release prototype (Mission 30 placeholders) |

### Work via board drill-down (not on home card)

| File | Assessment |
|------|------------|
| `erp-qc-detail.php` | **Checklist + pass/fail forms** — real QC workbench |
| `erp-qc-action.php` | POST handler |

**Answer:** QC **can perform checklist** on detail page. Staff home **does not link QC detail directly** (acceptable if board → detail is obvious). Delivery control connection is **partial** (prototype permission placeholders).

---

## 12. HR / Employee Self-Service Environment

| File | Status | Category |
|------|--------|----------|
| `erp-employee-create.php` | **EXISTS** | HR admin write (form → `submit-employee-create.php`) |
| `erp-employee-profile.php` | **EXISTS** | Profile view |
| `erp-payroll-preview.php` | **EXISTS** | Payroll preview (not self-service portal) |
| `erp-employee-contract.php` | **MISSING** | — |
| `erp-attendance.php` | **MISSING** | — |
| Leave / overtime / self-service | **MISSING** | No pages found |

**Answer:** **HR admin prototype only.** No employee self-service work environment. **Not linked** from `erp-staff-home.php` for any role.

---

## 13. One-Day Run Operational Flow Matrix

| Step | Required page(s) | Existing file | Role | Can perform action? | Gap |
|------|------------------|---------------|------|---------------------|-----|
| 1. Customer request | `customer-request.php` | **EXISTS** | Customer | Yes (public form + API) | Needs online bridge for remote test |
| 2. Reception receives | `erp-reception-online-requests.php` → accept | **EXISTS** | RECEPTION | Yes on accept page | Accept page **not on staff home** |
| 3. Reception creates JobCard | `erp-reception-jobcard-detail.php` + action | **EXISTS** | RECEPTION | Yes (workflow POST) | Requires P1.5 gate for controlled flow |
| 4. Contract/signature gate | `erp-intake-contracts.php` + customer sign pages | **EXISTS** | RECEPTION / Customer | Yes | Contract board **not linked** for reception |
| 5. Service manager assigns | `erp-technical-jobcard-detail.php` | **EXISTS** | SERVICE_MANAGER | Yes (`assign_technician`) | No dedicated assign desk; global board |
| 6. Technician diagnosis | `erp-technical-jobcard-detail.php` | **EXISTS** | TECHNICIAN | Yes | No “my jobs” filter |
| 7. Parts request/reserve/issue | reserve + part-use | **EXISTS** (part-use) | PARTS | Partial | Staff home link **broken**; usage page hidden |
| 8. Finance estimate/payment | `erp-estimate-board.php`, payment tracking | **EXISTS** | FINANCE | Partial | Estimate **not on finance home** |
| 9. Work execution | `erp-work-execution-detail.php` | **EXISTS** | TECHNICIAN | Yes | Reachable via board |
| 10. QC | `erp-qc-detail.php` | **EXISTS** | QC | Yes | — |
| 11. Final invoice/settlement | final invoice + settlement pages | **EXISTS** | FINANCE | Yes | Finance center card broken |
| 12. Customer delivery/close | `customer-delivery-*.php` + APIs | **EXISTS** (customer pages) | Customer / QC | Yes via customer OTP flow | Staff delivery pages partial |

**One-Day Run verdict:** **Technically runnable** with an **owner/operator guide** who knows the full URL chain. **Not runnable** as self-service employee operation from `erp-staff-home.php` alone.

---

## 14. Missing / Broken / Not Linked Pages

### Missing files referenced by staff landing

| File | Referenced by | Substitute |
|------|---------------|------------|
| `erp-jobcard-part-usage-list.php` | PARTS staff home | Use `erp-jobcard-part-readonly-list.php` or `erp-jobcard-part-use.php` |
| `erp-finance-center.php` | FINANCE staff home | Use `erp-payment-tracking.php` + invoice board |

### Missing files (not in staff home but expected in spec)

| File | Notes |
|------|-------|
| `erp-purchase-request-list.php` | Create exists; list missing |
| `erp-delivery-readiness.php` | Not found |
| `erp-customer-delivery.php` | Not found (customer-facing delivery pages exist) |
| `erp-attendance.php` | HR self-service not built |

### Registry P1–P10

**All 63 registered routes exist** — no missing files in official navigation registry.

### Not connected — important operational pages

| File | Should link from | Risk |
|------|------------------|------|
| `erp-intake-contracts.php` | RECEPTION staff home | Contract gate invisible to reception |
| `erp-reception-online-request-accept.php` | RECEPTION staff home | Cannot accept online leads from landing |
| `erp-estimate-board.php` | FINANCE / SERVICE_MANAGER home | Estimate gate bypassed in navigation |
| `erp-jobcard-part-use.php` | PARTS / TECHNICIAN home | Part issue workflow hidden |
| `erp-purchase-request-create.php` | PARTS home | Purchase path hidden |
| `erp-qc-detail.php` | QC home (optional) | Low — reachable from board |
| `erp-reception-jobcard-action.php` | (endpoint) | OK as POST handler |

### Owner-only pages correctly excluded from staff roles

- `erp-access-management.php` and P11.4 pages
- `erp-product-home.php`, `erp-route-map.php`, soft-run suite
- `erp-management-dashboard.php` (for non-owner staff per P11.4.4-B matrix)

---

## 15. Existing Work Pages Hidden in Repository

These **are real work environments** but buried outside role landing:

| Page | Phase | Why hidden |
|------|-------|------------|
| `erp-intake-contracts.php` | P1.5 | Not in RECEPTION staff home matrix |
| `erp-intake-contract-detail.php` | P1.5 | Reachable only from contract board |
| `erp-reception-online-request-detail.php` | P1 | From online list only |
| `erp-reception-online-request-accept.php` | P1 | Not on staff home |
| `erp-estimate-board.php` | P4 | Only on `erp-product-home.php` |
| `erp-jobcard-part-use.php` | P5 | Mission prototype; not in nav registry P1–P10 |
| `erp-purchase-request-create.php` | Inventory | Not linked for PARTS role |
| `erp-technician-workflow-ux.php` | UX proto | Alternate shell — not production landing |
| `erp-jobcard-create-ux.php` | UX proto | Prototype JobCard create UX |

**Discovery paths today:** `erp-product-home.php` (owner-oriented module grid), `erp-route-map.php` (63-route table), or **direct URL knowledge**.

---

## 16. Critical Gaps

1. **No unified per-role workbench** — `erp-staff-home.php` is a **link hub**, not a task queue.
2. **Reporting overload** — 24 soft-run + management KPI + RC pages overshadow **~15 operational desk screens**.
3. **Broken staff home links** — PARTS and FINANCE have **disabled cards** for missing files.
4. **Hidden workflow steps** — contract gate, online accept, estimate board, part usage not on role landing.
5. **No technician assignment filter** — global boards increase noise and training burden.
6. **HR / self-service absent** — not part of V1 One-Day Run scope but listed in owner expectations.
7. **Product home misleads** — mixes P1–P7 work modules with P9 demo and P10 release in one grid.
8. **Two navigation systems** — P10 product registry vs P11.4 staff role matrix **do not align** (different filenames for parts/finance).

---

## 17. Minimum Patch Required

**Scope: navigation/consolidation only — no new workflow domains, no P12, no Auth rewrite.**

| # | Patch | Effort | Impact |
|---|-------|--------|--------|
| 1 | Fix staff home links: `erp-jobcard-part-usage-list.php` → `erp-jobcard-part-readonly-list.php` or `erp-jobcard-part-use.php`; `erp-finance-center.php` → `erp-payment-tracking.php` or create thin finance hub redirect | Small | Removes broken landing cards |
| 2 | Extend RECEPTION staff home: add contract board + online accept/detail | Small | Complete reception desk |
| 3 | Extend FINANCE staff home: add estimate board | Small | Finance gate visible |
| 4 | Extend PARTS staff home: add part-use + purchase create | Small | Warehouse desk complete |
| 5 | Add **“My assignments” filter** on technical/work boards when role = TECHNICIAN | Medium | Personal workbench |
| 6 | Add **role workbench header nav** on P1–P7 detail pages (breadcrumb back to staff home + next step) | Medium | Daily orientation |
| 7 | Split **product home** into “Operations” vs “Release/Demo” sections (UI only) | Small | Owner clarity |
| 8 | Document **One-Day Run URL cheat sheet** per role in `docs/access/` until P11.7 ships | Small | Immediate ops workaround |

**Not required for minimum patch:** new DB tables, new permissions, new workflow states.

---

## 18. Recommended Next Phase Boundary

### Proposed: **P11.7 — Employee Workbench Consolidation**

**In scope:**

- Role landing matrix alignment with **actual filenames**
- Per-role **task queue / “next actions”** on `erp-staff-home.php` (read from existing boards — no new workflow)
- Cross-link P1.5 contract + P4 estimate into role homes
- Technician “my jobs” filter
- Breadcrumb / sub-nav on operational detail pages
- One-Day Run **operator runbook URLs** validated in tests

**Out of scope (P12 / V2):**

- HR self-service (attendance, leave)
- New accounting/payment gateway
- Position seed cleanup (separate backlog)
- New workflow domains

**Answer to “Should we assign P11.7 to Employee Workbench Consolidation?”**  
**Yes.** P11.4 solved **access provisioning and login landing**; P11.7 should solve **discoverability and daily desk UX** without changing workflow logic.

---

## 19. Final Verdict

### Direct answers (Persian)

**1. آیا محیط کاری کارمندها واقعاً ساخته شده یا فقط تکه‌تکه در صفحات مختلف پخش است؟**  
**تکه‌تکه پخش شده است.** منطق workflow در P1–P7 موجود است، اما **میز کار متمرکز per-role** ساخته نشده؛ کارمند باید از board به detail و action برود و بعضی مسیرها (قرارداد، پذیرش آنلاین، برآورد، مصرف قطعه) روی landing نیست.

**2. کدام نقش‌ها الان محیط کاری قابل استفاده دارند؟**  
**OWNER/SYSTEM_ADMIN** (مدیریت)، **RECEPTION** (با راهنما)، **TECHNICIAN** و **SERVICE_MANAGER** (board→detail)، **QC** (board→detail)، **FINANCE** و **PARTS** (**ناقص** به‌خاطر لینک خراب/صفحات پنهان).

**3. کدام نقش‌ها فقط گزارش/داشبورد دارند؟**  
**OWNER** در مسیر product home به KPI، soft-run، release و route map دسترسی دارد — بیشتر **گزارش/نظارت**. **SERVICE_MANAGER** روی staff home عمدتاً **board/read-only** می‌بیند؛ action در detail است.

**4. کدام نقش‌ها صفحه کاری ندارند؟**  
**HR / employee self-service** practically **ندارد**. **UNKNOWN** role هیچ مسیر عملیاتی ندارد.

**5. آیا One-Day Run با وضعیت فعلی قابل اجراست؟**  
**با راهنمای مالک/اپراتور بله؛ به‌صورت self-service کارمندان خیر.** تمام فایل‌های registry موجودند، actionها پیاده شده‌اند، اما landing و discoverability کافی نیست.

**6. اگر نیست، کمترین Patch لازم چیست؟**  
رفع **۲ لینک خراب** staff home + اضافه کردن **۴–۶ مسیر پنهان** (قرارداد، پذیرش آنلاین، برآورد، مصرف قطعه) + **فیلتر کار تکنسین** + breadcrumb — بدون تغییر Auth/DB/workflow.

**7. آیا باید P11.7 را به Employee Workbench Consolidation اختصاص دهیم؟**  
**بله** — مرز منطقی بعد از P11.4.x و قبل از One-Day Run عملیاتی.

---

## Appendix A — Page Classification Table (representative)

| File | Category | Role | Work capability | R/W | Linked from | Status |
|------|----------|------|-----------------|-----|-------------|--------|
| `erp-staff-home.php` | Employee entry | All staff | Route hub only | Read | post-login redirect | **EXISTS** |
| `erp-reception-online-requests.php` | Read-only board | RECEPTION | List requests | R | staff home | **EXISTS** |
| `erp-reception-online-request-accept.php` | Action screen | RECEPTION | Accept request | W | online detail | **NOT CONNECTED** |
| `erp-reception-jobcards.php` | Read-only board | RECEPTION | JobCard queue | R | staff home | **EXISTS** |
| `erp-reception-jobcard-detail.php` | **Work env** | RECEPTION | Progress JobCard | R+W | jobcard board | **EXISTS** |
| `erp-reception-jobcard-action.php` | Action endpoint | RECEPTION | POST handler | W | detail forms | **EXISTS** |
| `erp-intake-contracts.php` | **Work env** | RECEPTION | Contract gate | R+W | product home | **NOT CONNECTED** |
| `erp-technical-board.php` | Read-only board | TECH/SM | Technical queue | R | staff home | **EXISTS** |
| `erp-technical-jobcard-detail.php` | **Work env** | TECH/SM | Assign, diagnose | R+W | board | **EXISTS** |
| `erp-work-execution-board.php` | Read-only board | TECH/SM | Execution queue | R | staff home | **EXISTS** |
| `erp-work-execution-detail.php` | **Work env** | TECHNICIAN | Complete work | R+W | board | **EXISTS** |
| `erp-estimate-board.php` | Read-only board | FINANCE | Estimate gate | R | product home | **NOT CONNECTED** |
| `erp-estimate-detail.php` | **Work env** | FINANCE | Approve/reject | R+W | estimate board | **EXISTS** |
| `erp-part-reserve.php` | **Work env** | PARTS | Reserve stock | R+W | staff home | **EXISTS** |
| `erp-jobcard-part-usage-list.php` | — | PARTS | — | — | staff home | **MISSING** |
| `erp-jobcard-part-use.php` | **Work env** | PARTS | Issue usage | R+W | hidden | **NOT CONNECTED** |
| `erp-finance-center.php` | — | FINANCE | — | — | staff home | **MISSING** |
| `erp-payment-tracking.php` | Read-only + links | FINANCE | Track payments | R | staff home | **EXISTS** |
| `erp-final-invoice-board.php` | Read-only board | FINANCE | Invoice queue | R | staff home | **EXISTS** |
| `erp-final-invoice-detail.php` | **Work env** | FINANCE | Invoice actions | R+W | board | **EXISTS** |
| `erp-qc-board.php` | Read-only board | QC | QC queue | R | staff home | **EXISTS** |
| `erp-qc-detail.php` | **Work env** | QC | Checklist pass/fail | R+W | board | **EXISTS** |
| `erp-delivery-control.php` | Mixed prototype | QC | Delivery release | Partial | staff home | **EXISTS** |
| `erp-management-dashboard.php` | Management dashboard | OWNER | KPI cards | R | product home | **EXISTS** |
| `erp-owner-control-center.php` | Management dashboard | OWNER | Control view | R | product home | **EXISTS** |
| `erp-product-home.php` | Demo/product nav | OWNER | Module grid | R | owner login | **EXISTS** |
| `erp-route-map.php` | Release/report | OWNER | Route catalog | R | product home | **EXISTS** |
| `erp-soft-run-control-room.php` | Demo/soft-run | Owner/demo | Pilot control | R | product home | **EXISTS** |
| `erp-access-management.php` | Admin work env | OWNER | Staff CRUD | R+W | staff home (owner) | **EXISTS** |
| `erp-employee-create.php` | HR admin proto | HR admin | Create employee | R+W | hidden | **NOT CONNECTED** |

---

## Appendix B — Missing Work Environment Table

| Role | Missing work screen | Current substitute | Risk | Recommended phase |
|------|---------------------|-------------------|------|-------------------|
| PARTS | Part usage list (linked name) | `erp-jobcard-part-use.php` unlinked | Staff home **broken card** | **P11.7** |
| FINANCE | Finance center hub | Invoice board + payment tracking | No single finance entry | **P11.7** |
| RECEPTION | Contract desk on landing | `erp-intake-contracts.php` via product home | Contract step skipped | **P11.7** |
| RECEPTION | Online accept on landing | Manual URL / route map | Leads not accepted | **P11.7** |
| TECHNICIAN | “My jobs” filtered desk | Global technical board | Wrong jobs shown | **P11.7** |
| FINANCE | Estimate desk on landing | `erp-estimate-board.php` hidden | Approval gate missed | **P11.7** |
| HR | Self-service portal | None | Out of V1 One-Day Run | **P12/V2** |
| ALL | Unified workbench | `erp-staff-home.php` link hub | Training burden | **P11.7** |

---

## Appendix C — Reporting vs Working Ratio

| Metric | Count |
|--------|------:|
| Total PHP pages (`public_html/`) | 486 |
| `erp-*.php` pages | 234 |
| P1–P7 operational work screens (board+detail pairs) | ~28 |
| P1–P7 action endpoints (`erp-*-action.php`) | 7 |
| Submit handlers (`submit-*.php`) | 41 |
| Management / KPI / owner dashboards | ~10 |
| Demo / soft-run / release / RC pages | ~50+ |
| Access management (P11.4) | 7 UI + helpers |
| Missing pages referenced by staff home | **2** |
| Docs (`.md`) | 1,122 |
| Tests (`tools/test-*.php`) | 199 |

**Working ratio (strict):** ~28 operational desk entry points vs ~234 ERP pages ≈ **12%** of ERP pages are primary daily work entry; **~50%+** are report/demo/release/soft-run/access/UX prototype when broader heuristics applied.

---

MOGHARE360 currently must be judged by usable employee workbenches, not by the number of reports, documents or demo dashboards committed to GitHub.
