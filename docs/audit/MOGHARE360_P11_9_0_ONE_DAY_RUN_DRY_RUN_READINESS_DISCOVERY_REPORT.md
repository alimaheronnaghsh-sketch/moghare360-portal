# MOGHARE360 P11.9-0 — One-Day Run Dry Run Readiness Discovery Report

**Phase:** P11.9-0  
**Mode:** DISCOVERY / REPORT ONLY  
**Date:** 2026-06-26  
**Product:** MOGHARE360 V1 RC  
**Prerequisite phases:** P11.7, P11.7-FIX-A, P11.7.1-A, P11.8-A, P11.8-A-FIX-A, P11.8-B-A, P11.8-C, P11.8-C-FIX-A

---

## 1. Executive Summary

MOGHARE360 V1 RC is **partially ready** for a **controlled One-Day Run dry run** with real staff roles and one sample JobCard. The **UI navigation layer** (Staff Home, Manager Reference Bridge, Route Map operational view, operational shell on P2–P7 boards/details) is sufficiently mature to guide operators without exposing unsafe links. **Workflow handlers P1–P7 exist**, action endpoints are POST-protected, and management read-only dashboards are available.

**Dry-run verdict:** **PARTIAL — proceed with operator conditions**, not a blind production run.

| Dimension | Status | Headline |
|-----------|--------|----------|
| Navigation / route safety | **READY** | Route Map ops view: 23/63 active links; 40 protected |
| Staff workbench | **READY** | All 8 roles configured; runtime holds on 2 routes |
| P1–P7 UI pages | **PARTIAL** | P2–P7 boards/details strong; P1/P1.5 lack operational shell |
| Staff user provisioning | **WARNING / BLOCKED*** | *BLOCKED if zero `erp_company_users`; code supports all roles |
| Sample JobCard data | **WARNING** | No SQL seed; `M360-DEMO` JobCard must exist in live DB |
| OTP / SMS | **WARNING** | Private OTP config absent on host; customer OTP legs need manual path |
| Soft Run / E2E automation | **BACKLOG** | ~90.91% readiness, E2E NOT_RUN — not required for manual dry run |
| Runtime-not-ready routes | **WARNING** | part-use + payment-tracking disabled by design |

**Security scope of this report:** No code, SQL, Auth, permissions, roles, workflow, action handlers, or data were modified. No secrets exposed.

---

## 2. Owner Requirement Interpretation

The owner’s 12-step workshop flow (customer request → reception → JobCard → contract gate → service manager assignment → diagnosis → parts → estimate/finance → work → QC → settlement → delivery/close) maps to existing P1–P7 pages and Staff Home role cards documented in `MOGHARE360_P11_7_ONE_DAY_RUN_WORKBENCH_COVERAGE.md`.

Dry run means: **real staff logins**, **one controlled sample JobCard** (recommended prefix `M360-DEMO`), **manual operator guidance** where UI gaps exist, **no impersonation**, **no fake production OTP**, and **no P12 accounting/payment-gateway scope**.

---

## 3. One-Day Run Readiness Summary

| Area | Status | Evidence | Risk | Dry-run decision |
|------|--------|----------|------|------------------|
| Staff roles (8 codes) | **READY** | `m360-staff-home-helper.php`, `core_v0_06_seed_roles_permissions.sql`, P11.4 access map | QC maps to seed `technical_manager` — naming only | Use role assignment matrix before run day |
| Staff login | **PARTIAL** | `staff-login.php` + API; requires `erp_company_users` | Owner-only accounts cannot use staff login | Provision dedicated staff users |
| Staff Home workbench | **READY** | P11.7 coverage doc; all roles have «کار امروز» | Detail pages are guided (`info`), not direct nav | Start each role from Staff Home |
| Manager Reference Bridge | **READY** | P11.8-A; OWNER/ADMIN full bridge; SM coordination bridge | Bridge includes runtime-hold cards (part-use, payment) | Use bridge for oversight; respect disabled cards |
| Route Map (P11.8-C) | **READY** | 63 routes; 23 ops-clickable; classifier helper | Technical view still shows full inventory | Default **نمای عملیاتی** for staff |
| Operational shell P2–P7 | **READY** | 7 boards + 7 jobcard detail pages + settlement detail | P1/P1.5 not in shell scope | Accept legacy layout for reception online + contract detail |
| P1–P7 workflow handlers | **READY** | Board/detail/action files exist; POST+CSRF+staff gate | Actions only from detail forms | Never bookmark action URLs |
| Sample JobCard data | **WARNING** | `m360_soft_run_find_demo_jobcard()`; no repo SQL seed | Empty boards OK; no progress without JobCard | **Prepare one `M360-DEMO` JobCard before run** |
| Parts consumption UI | **WARNING** | `erp-jobcard-part-use.php` on runtime hold | PARTS/TECH use `erp-part-reserve.php`; part-use disabled | Reserve path OK; consumption page backlog |
| Payment tracking UI | **WARNING** | `erp-payment-tracking.php` on runtime hold | FINANCE uses estimate/invoice boards | Coordinate payment outside disabled page |
| OTP / customer signature | **WARNING** | OTP diagnostics: `private/m360-otp-config.php` absent; SMS not configured | Customer contract/estimate/delivery OTP legs | Manual customer steps or defer OTP legs |
| Access readiness (P11.4) | **WARNING*** | `m360_access_readiness_report()` | *BLOCKED if staff count = 0 | Run access console checklist |
| Soft Run score ~90.91% | **WARNING** | Demo readiness: 20/22 checklist PASS typical | Non-blocking warnings | Monitor; not dry-run gate |
| E2E Demo NOT_RUN | **BACKLOG** | `erp-end-to-end-demo-scenario.php`; no automated run recorded | Automation not executed | Manual dry run does not require E2E record |
| Link Audit | **WARNING** | Missing **release/demo docs** in hardening list; **route files PASS** | Doc gaps only | Backlog for P10 packaging |
| Management oversight P8 | **READY** | Dashboard, owner control, KPI, bottleneck, financial summary pages | Read-only; needs DB views | Sufficient for dry-run oversight |
| Action endpoint raw messages | **WARNING** | GET on P1–P7 actions redirects to board; legacy pages may show `ERP security validation failed` | Mis-clicks if URL leaked | Not blocker if navigation rules followed |

---

## 4. Role Readiness Matrix

| Role | Login available? | Staff Home ready? | Daily cards ready? | Manager/reference needed? | Gap | Status |
|------|------------------|-------------------|--------------------|-----------------------------|-----|--------|
| **OWNER** | Yes (`owner-login.php`; staff login if in `erp_company_users`) | Yes | Access mgmt + product home | Full Manager Reference Bridge (20+ refs) | Default owner landing is Product Home, not Staff Home | **READY** |
| **SYSTEM_ADMIN** | Same as OWNER | Yes (same workbench as OWNER) | Same | Full bridge | Not in P11.7 automated test matrix (7 roles) | **READY** |
| **RECEPTION** | Yes (staff login + company user) | Yes | Online requests → JobCards → contracts | No | Accept action via detail only; P1 pages no shell | **PARTIAL** |
| **SERVICE_MANAGER** | Yes | Yes | Technical → work → QC boards | Coordination bridge | Assignment from board/detail, not direct card | **READY** |
| **TECHNICIAN** | Yes | Yes | Technical + work execution boards | No | No “my jobs” filter; part-use disabled | **PARTIAL** |
| **PARTS** | Yes | Yes | Reserve → part-use (hold) → purchase request | No | part-use runtime hold; purchase list page missing (backlog) | **PARTIAL** |
| **FINANCE** | Yes | Yes | Payment (hold) → estimate → final invoice | No | payment-tracking hold; finance center missing (backlog) | **PARTIAL** |
| **QC** | Yes | Yes | QC board → delivery control | No | Detail via board; `erp-delivery-control.php` not in route registry | **PARTIAL** |

**Login gate:** Staff login requires active `erp_company_users` row (`MOGHARE360_V1_ONE_DAY_RUN_ACCESS_SETUP.md`). Pure owner without company membership must use owner login for oversight only.

---

## 5. P1–P7 Dry Run Flow Matrix

| Step | Phase | Existing page | Staff role | Current UI readiness | Data readiness | Action readiness | Responsibility/status visibility | Status | Decision |
|------|-------|---------------|------------|----------------------|----------------|------------------|----------------------------------|--------|----------|
| 1. Customer request/arrival | P1 | `customer-request.php`, `erp-reception-online-requests.php` | RECEPTION / Customer | Board loads; no ops shell | Needs online request or walk-in JobCard path | Accept: POST `erp-reception-online-request-accept.php` | No jobcard strip on P1 | **PARTIAL** | RECEPTION starts from Staff Home list |
| 2. Reception receives | P1 | `erp-reception-online-request-detail.php` | RECEPTION | Guided detail; no shell | Needs `request_id` context | Accept from detail form | Inline only | **PARTIAL** | Open from list, not Route Map direct |
| 3. Create/progress JobCard | P2 | `erp-reception-jobcards.php` → detail | RECEPTION | Shell + strip on detail | Needs JobCard row | POST `erp-reception-jobcard-action.php` | Strip: status + next action | **READY** | Primary reception hub |
| 4. Contract/signature gate | P1.5 | `erp-intake-contracts.php` → detail; customer sign pages | RECEPTION / Customer | Board has shell; detail legacy | Contract + customer records | POST generate/send | No shared strip on contract detail | **PARTIAL** | Customer OTP leg needs SMS or manual |
| 5. SM receives/assigns | P3 | `erp-technical-board.php` → detail | SERVICE_MANAGER | Shell + strip | JobCard in technical queue | POST `erp-technical-jobcard-action.php` (`assign_technician`) | Strip visible | **READY** | Assign from technical detail |
| 6. Technician diagnosis | P3 | `erp-technical-board.php` → detail | TECHNICIAN | Shell + strip | Same JobCard | POST technical action | Strip visible | **READY** | Board → detail flow |
| 7. Parts request/reserve/use | P4/P5 | `erp-part-reserve.php`; part-use **hold** | PARTS | Reserve OK; part-use disabled | Part usage table may be empty | part-use action N/A (hold) | Limited | **WARNING** | Use reserve; defer consumption UI |
| 8. Estimate/payment/invoice | P4/P7 | `erp-estimate-board.php`, `erp-final-invoice-board.php`; payment **hold** | FINANCE | Boards + shell; payment page hold | Estimate/invoice rows per JobCard state | POST estimate/FI actions | Strip on estimate detail | **PARTIAL** | Payment tracking outside disabled page |
| 9. Work execution | P5 | `erp-work-execution-board.php` → detail | TECHNICIAN / SM | Shell + strip | JobCard in work queue | POST work action | Strip visible | **READY** | Standard board flow |
| 10. QC | P6 | `erp-qc-board.php` → detail | QC | Shell + strip | QC check records | POST QC action | Strip + delivery readiness fields | **READY** | QC board → detail |
| 11. Settlement/payment check | P7 | `erp-settlement-detail.php`, `erp-final-invoice-board.php` | FINANCE | Shell + strip on settlement | Settlement row linked to JobCard | POST settlement/FI actions | Strip on settlement detail | **READY** | From invoice board context |
| 12. Customer delivery/close | P7 | `erp-delivery-control.php`, customer delivery pages | QC / Customer | Delivery control via QC workbench | Delivery readiness status on JobCard | Customer OTP APIs if SMS configured | QC delivery control page | **PARTIAL** | Customer sign may need manual OTP path |

---

## 6. Data Readiness Matrix

| Data object | Exists? | Usable for dry run? | Missing fields | Risk | Recommended action |
|-------------|---------|---------------------|----------------|------|-------------------|
| SQL seed JobCard | **No in repo** | N/A | Full operational row not shipped | Cannot dry run without live data | **Prepare controlled `M360-DEMO-*` JobCard in DB** (prep phase, not P11.9-0) |
| JobCard ID 1 | **Environment-dependent** | Unknown without live query | May be stale or absent | Sample links in some pages use `jobcard_id=1` as placeholder | Do not assume ID 1; use demo finder or known ID |
| `M360-DEMO` JobCard | **Environment-dependent** | **Required** for Soft Run PASS | Prefix `M360-DEMO` or scenario `M360-DEMO-E2E-V1` | Soft Run shows demo_data **WARNING** if missing | Create one demo JobCard before dry run |
| Customer / vehicle | **Schema yes; seed no** | Required with JobCard | Linked `customer_id`, `vehicle_id` | Walk-in path needs manual entry at reception | Create with JobCard at P2 |
| Online request (P1) | **Optional** | For online-intake path | `request_id` chain | Can skip if walk-in JobCard | Operator choice |
| Intake contract (P1.5) | **Schema yes** | Required for contract gate | Contract status, send/sign state | Blocks later gates if incomplete | Progress via contract board |
| Estimate (P4) | **Schema yes** | Required mid-flow | Approval state | Customer approval path separate | FINANCE + customer pages |
| Parts reserve/usage | **Schema yes** | Reserve usable; usage UI hold | Usage rows may be manual/DB | part-use page disabled | Use reserve; document manual consumption if needed |
| QC check (P6) | **Schema yes** | Required before delivery | `delivery_readiness_status` | Gate for P7 | QC actions from detail |
| Final invoice / settlement (P7) | **Schema yes** | Required for close | Invoice + settlement linkage | FINANCE board flow | Standard P7 path |
| Staff users (`erp_company_users`) | **Schema yes; seed minimal** | **Required** | Per-role assignments | Access readiness **BLOCKED** if zero staff | Use `erp-access-management.php` |
| P8 management views | **Migration-dependent** | For dashboards | SQL views must exist | KPI may WARN if views missing | Verify P8 migration applied |
| Soft Run checklist rows | **P9 tables** | Optional tracking | Stored checklist state | Does not block manual run | Optional operator logging |

**Repo finding:** No destructive or demo JobCard INSERT ships in committed SQL migrations. Demo data is **operator/environment responsibility** per `MOGHARE360_DEMO_SCENARIO_GUIDE.md` and `MOGHARE360_V1_DEMO_DAY_CHECKLIST.md`.

---

## 7. Navigation and Route Safety Readiness

| Check | Status | Evidence |
|-------|--------|----------|
| Route Map total routes | **READY** | 63 registry audit rows (P11.8-C) |
| Operational clickable links | **READY** | 23 active in **نمای عملیاتی** |
| Protected routes | **READY** | 40 non-operational: guided, action, API, customer, runtime patterns |
| POST/action not normal links | **READY** | Class **عملیات داخلی**; Route Map + Staff Home `note` cards |
| API not normal links | **READY** | Class **API سیستم** |
| Detail pages guided | **READY** | Class **راهنمای مسیر**; board → detail pattern |
| Customer routes separated | **READY** | Class **مسیر مشتری**; not ops-clickable |
| File exists ≠ operational ready | **READY** | **فایل موجود** separate from safety badge |
| Product Home entry | **WARNING** | Links P2–P7 boards only; **no P1/P1.5** |
| `erp-delivery-control.php` | **WARNING** | Usable from QC workbench; **not in registry** |

**Dry-run decision:** Route Map after P11.8-C is **safe for dry run** when staff use default operational view.

---

## 8. Staff Home / Manager Bridge Readiness

| Capability | Status | Notes |
|------------|--------|-------|
| Role-aware «کار امروز» | **READY** | All 8 roles |
| Guided detail cards (`info`) | **READY** | No direct href to detail without ID |
| Action cards (`note`) | **READY** | Document endpoints; not clickable |
| Runtime hold cards | **READY** | part-use, payment-tracking disabled with Persian badge |
| OWNER/ADMIN Manager Reference Bridge | **READY** | Cross-unit refs to all P1–P7 boards |
| SERVICE_MANAGER coordination bridge | **READY** | Lighter cross-unit refs |
| HR self-service backlog | **BACKLOG** | Explicitly non-V1 |
| Admin impersonation / override backlog | **BACKLOG** | Cards shown disabled |

---

## 9. Operational Shell / Responsibility Strip Readiness

### Boards with operational shell (7)

`erp-intake-contracts.php`, `erp-reception-jobcards.php`, `erp-technical-board.php`, `erp-estimate-board.php`, `erp-work-execution-board.php`, `erp-qc-board.php`, `erp-final-invoice-board.php`

### Details with shell + responsibility strip (8)

`erp-reception-jobcard-detail.php`, `erp-technical-jobcard-detail.php`, `erp-estimate-detail.php`, `erp-work-execution-detail.php`, `erp-qc-detail.php`, `erp-final-invoice-detail.php`, `erp-settlement-detail.php`

### Without shell (dry-run impact)

| Page | Phase | Blocker? |
|------|-------|----------|
| `erp-reception-online-requests.php` | P1 | **BACKLOG** — usable, legacy nav |
| `erp-reception-online-request-detail.php` | P1 | **BACKLOG** |
| `erp-intake-contract-detail.php` | P1.5 | **BACKLOG** |
| All `*-action.php` | P1–P7 | **N/A** — POST handlers by design |

**Decision:** Missing shell on P1/P1.5 is **not a dry-run blocker** if operators follow Staff Home and accept legacy layout for reception online + contract detail.

---

## 10. OTP / SMS Readiness

| Check | Status | Evidence |
|-------|--------|----------|
| OTP helper / loader code | **READY** | `m360-otp-helper.php`, `m360-otp-config-loader.php` |
| Private config `private/m360-otp-config.php` | **Absent on host** | Not in repo (gitignored); diagnostics: `private_otp_config_found: no` |
| Mirror config | **Absent** | `mirror_config_found: no` |
| SMS configured | **No** | Diagnostics: `sms_configured: no`, provider empty |
| Fake OTP in production | **Forbidden** | Soft Run checklist `no_fake_production_otp`; security lock |
| Live SMS required for all dry run? | **No** | Staff workflow P1–P7 can proceed without SMS |
| Customer OTP legs (contract, estimate, delivery) | **PARTIAL** | Require SMS **or** controlled manual verification path documented by operator |

**Dry-run decision:** OTP/SMS is **WARNING**, not **BLOCKED**, for a **staff-centric dry run**. Customer signature steps need operator plan (manual code entry, defer customer legs, or configure OTP before those steps).

**No secrets** were read or written in this audit.

---

## 11. Management Oversight Readiness

| Page | Role | Status | Dry-run use |
|------|------|--------|-------------|
| `erp-management-dashboard.php` | OWNER | **READY** | Pipeline / operational overview |
| `erp-owner-control-center.php` | OWNER | **READY** | Risk / control list |
| `erp-operational-kpi-dashboard.php` | OWNER | **READY** | KPI read-only |
| `erp-bottleneck-monitor.php` | OWNER / SM | **READY** | Queue bottlenecks |
| `erp-financial-control-summary.php` | OWNER / FINANCE | **READY** | Financial summary read-only |
| `erp-jobcard-timeline.php` | OWNER / SM | **PARTIAL** | Guided — needs `jobcard_id` |
| `erp-route-map.php` (ops view) | OWNER | **READY** | Safe route reference |
| `erp-access-management.php` | OWNER | **READY** | Pre-run staff provisioning |

**Decision:** Read-only P8 dashboards are **sufficient** for dry-run oversight.

---

## 12. Runtime Route Risks

| Route | Current status | Clickable Staff Home / Route Map ops? | Runtime result | Risk | Decision |
|-------|----------------|-------------------------------------|----------------|------|----------|
| `erp-jobcard-part-use.php` | **Runtime hold** | **No** (disabled card; Route Map non-clickable) | File may exist; not product-ready | Parts consumption UX gap | **WARNING** — use `erp-part-reserve.php`; fix in backlog |
| `erp-payment-tracking.php` | **Runtime hold** | **No** | File may exist; load issues reported | Payment visibility gap | **WARNING** — use estimate/invoice boards |
| `erp-part-reserve.php` | Operational | **Yes** (PARTS nav) | Expected load | Low | **READY** |
| P2–P7 board pages | Operational | **Yes** | Empty board OK if clean load | Low | **READY** |
| P1 online list/detail | Operational / guided | List clickable; detail guided | Legacy layout | Medium UX | **PARTIAL** |
| `*-action.php` endpoints | Action class | **No** | GET → redirect to board | Low if not bookmarked | **READY** (protected) |
| Customer `customer-*` pages | Customer class | **No** in ops view | Customer token flow | Medium — separate actor | **PARTIAL** |
| `api/customer/*` OTP APIs | API class | **No** | JSON API | Needs SMS for live OTP | **WARNING** |

**Empty boards:** Acceptable for dry run if pages load without PHP warnings and operators understand a fresh JobCard starts empty queues.

---

## 13. Blocking Issue Register

| Issue | Severity | Evidence | Must fix before dry run? | Recommended phase |
|-------|----------|----------|--------------------------|-------------------|
| Zero dedicated staff users | **HIGH** | `m360_access_readiness_report()` → BLOCKED when staff count = 0 | **Yes** | Pre-run: P11.4 access setup (ops, not code) |
| No controlled sample JobCard | **MEDIUM** | `m360_soft_run_find_demo_jobcard()` null → demo_data WARNING | **Yes** (for meaningful end-to-end) | Pre-run data prep (`M360-DEMO-*`) |
| OTP not configured for customer legs | **MEDIUM** | OTP diagnostics: SMS not configured | **Only if** dry run includes live customer OTP | Configure `private/m360-otp-config.php` or defer customer steps |
| part-use page disabled | **LOW** | `M360_STAFF_HOME_RUNTIME_NOT_READY` | **No** — alternate reserve path | P11.9+ or P12 backlog |
| payment-tracking disabled | **LOW** | Same runtime hold list | **No** — use FI/estimate boards | P11.9+ or P12 backlog |
| P1/P1.5 no operational shell | **LOW** | P11.8-B-A scope exclusion | **No** | P11.9-B or backlog UX |

---

## 14. Backlog Register

| Item | Why not needed for dry run | Future phase | Notes |
|------|----------------------------|--------------|-------|
| E2E Demo Scenario NOT_RUN | Manual role-by-role walkthrough suffices | P9 automation | Record optional after dry run |
| Soft Run doc warnings (Link Audit) | Route **files** exist; missing release **docs** only | P10 packaging | ~90.91% score acceptable |
| `erp-finance-center.php` missing | FINANCE has board cards | P12 | Backlog card on Staff Home |
| `erp-purchase-request-list.php` missing | PARTS has reserve path | P12 | Backlog card |
| Technician “my jobs” filter | Board works without filter | P12 | UX enhancement |
| HR self-service | Explicitly excluded V1 | P15 | Backlog cards only |
| Impersonation / manager override engine | Forbidden V1 | Post-V1 | Disabled admin backlog cards |
| Operational shell on P1/P1.5 | Pages functional | P11.9-B | UX polish |
| Friendly action security UI | GET redirects already; raw message on legacy pages | P11.9-C | Not navigation blocker |
| `erp-jobcard-part-use.php` product fix | Reserve workaround exists | Runtime fix wave | Remove runtime hold after validation |
| `erp-payment-tracking.php` load fix | FI path exists | Runtime fix wave | Remove runtime hold after validation |
| Payment gateway / accounting | P12 excluded | P12 | Not V1 RC |
| Route registry entry for `erp-delivery-control.php` | Page reachable from QC | P10 registry hygiene | Optional |

---

## 15. Minimum Next Patch Recommendation

**Minimum before dry run (operations, not code):**

1. Run `erp-access-management.php` — ensure staff users exist, login enabled, roles assigned (RECEPTION, SERVICE_MANAGER, TECHNICIAN, PARTS, FINANCE, QC minimum).
2. Create **one controlled JobCard** with prefix **`M360-DEMO`** (and linked customer/vehicle) progressing from reception — documented in demo runbook.
3. Brief operators: Staff Home entry, board → detail flow, disabled part-use/payment cards, Route Map **نمای عملیاتی** only.
4. Decide customer OTP strategy (configure SMS **or** manual/deferred customer signature steps).

**Minimum next code patch (optional, post P11.9-0):**

- **P11.9-A:** Controlled demo data checklist page / operator script linkage (no workflow change).
- **P11.9-B:** Operational shell for P1/P1.5 list+detail (UI-only).
- **Runtime fix:** Validate and re-enable `erp-jobcard-part-use.php` and `erp-payment-tracking.php` after browser proof.

---

## 16. Final Persian Answers

### 1. آیا الان می‌توان One-Day Run را شروع کرد؟

**با شرط — به‌صورت Dry Run کنترل‌شده بله؛ به‌معنای اجرای کامل بدون آماده‌سازی خیر.** لایه ناوبری و صفحات P2–P7 آماده است، اما باید کاربران پرسنلی، یک JobCard نمونه `M360-DEMO` و راهنمای اپراتور آماده باشد.

### 2. اگر بله، با چه شرط‌هایی؟

- ایجاد و فعال‌سازی لاگین پرسنل در `erp-access-management.php`
- یک JobCard کنترل‌شده با پیشوند `M360-DEMO`
- شروع هر نقش از **میز کار پرسنل** (Staff Home)
- استفاده از **نمای عملیاتی** نقشه مسیرها
- پذیرش غیرفعال بودن **مصرف قطعه** و **پیگیری پرداخت** تا رفع runtime hold
- برنامه مشخص برای مراحل OTP مشتری (SMS یا مسیر دستی/موکول)

### 3. اگر نه، چه چیزی blocker است؟

- **Blocker عملیاتی:** نبود کاربر پرسنل اختصاصی (همه روی owner login)
- **Blocker داده:** نبود JobCard نمونه قابل پیگیری end-to-end
- **Blocker نیست:** Soft Run 90.91%، E2E NOT_RUN، هشدار Link Audit docs، shell نبودن P1

### 4. آیا داده تست کافی وجود دارد؟

**در repo خیر؛ در DB محیط اجرا نامشخص.** seed SQL برای JobCard عملیاتی commit نشده. Soft Run انتظار JobCard با `M360-DEMO` دارد. فرض **`jobcard_id=1`** توصیه نمی‌شود.

### 5. آیا نقش‌های پرسنلی برای اجرا کافی هستند؟

**بله از نظر تعریف UI و seed نقش‌ها (۸ نقش).** کافی بودن **اجرایی** منوط به assign شدن در `erp_company_users` است.

### 6. آیا مسیر پذیرش تا ترخیص از Staff Home قابل اجراست؟

**بله — PARTIAL.** هر نقش کارت «کار امروز» دارد؛ جزئیات از تابلو/فهرست باز می‌شود؛ P1/P1.5 و OTP مشتری نیاز به راهنمایی بیشتر دارند.

### 7. آیا مدیر/مالک می‌تواند مسیر را کنترل و راهبری کند؟

**بله.** Manager Reference Bridge، داشبوردهای P8، Owner Control، KPI، گلوگاه، خلاصه مالی، و Route Map امن کافی است.

### 8. آیا OTP/SMS برای dry run blocker است؟

**برای مسیر پرسنلی خیر؛ برای امضای/OTP مشتری بله — WARNING.** SMS روی host پیکربندی نشده (`private/m360-otp-config.php` موجود نیست). fake OTP در production ممنوع است.

### 9. آیا Route Map بعد از P11.8-C برای Dry Run امن است؟

**بله — در نمای عملیاتی.** ۲۳ لینک فعال امن؛ ۴۰ مسیر محافظت‌شده. «فایل موجود» به معنی آمادگی عملیاتی نیست.

### 10. آیا action endpointهای خام blocker هستند؟

**خیر — برای dry run کنترل‌شده.** از ناوبری عادی حذف شده‌اند؛ GET به board redirect می‌شود. پیام خام legacy در باز کردن مستقیم برخی صفحات قدیمی — **WARNING**، backlog UI.

### 11. کمترین Patch بعدی چیست؟

**آماده‌سازی عملیاتی (بدون کد):** staff users + JobCard `M360-DEMO` + briefing. **کد اختیاری:** P11.9-B shell برای P1/P1.5 یا رفع runtime hold پس از browser validation.

### 12. چه چیزهایی باید برای P12 یا P15 بماند؟

- **P12:** finance center، purchase list، payment gateway، حسابداری، tax، part-use/payment-tracking fix کامل
- **P15:** HR self-service، impersonation، manager override engine
- **P10 packaging:** Link Audit doc gaps، registry hygiene برای delivery-control

---

## Security Confirmation (Report Scope)

This P11.9-0 discovery confirms:

- No DB change performed
- No SQL migration performed
- No Auth/Login change
- No permission/role seed change
- No workflow state change
- No action handler change
- No OTP secret exposure
- No production fake OTP introduced
- No impersonation
- No manager override implementation
- No HR self-service implementation
- No P12 scope introduced

---

P11.9-0 determines whether MOGHARE360 V1 RC is ready for a controlled One-Day Run dry run by auditing staff roles, data readiness, P1–P7 workflow usability, navigation safety, operational shell coverage, OTP readiness, management oversight, runtime route risks and remaining blockers without changing code, SQL, Auth/Login, permissions, roles, workflow, action handlers, or P12 scope.
