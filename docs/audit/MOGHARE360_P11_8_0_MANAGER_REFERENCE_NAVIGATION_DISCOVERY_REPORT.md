# MOGHARE360 P11.8-0 — Manager Reference Navigation Bridge Discovery Report

**Phase:** P11.8-0  
**Mode:** REPORT ONLY — no code, SQL, Auth, permissions, roles, routes, or workflow changes  
**Date:** 2026-06-26  
**Repository:** `moghare360-portal` @ V1 RC  
**Prior reports:** P11.7.1-0, P11.7.1-A, P11.7 One-Day Run coverage

---

## 1. Executive Summary

MOGHARE360 **already has the building blocks** for a Manager Reference Navigation Bridge, but they are **fragmented and not unified** on Staff Home for manager roles.

| Layer | Exists? | Usable for P11.8-A? |
|-------|---------|---------------------|
| P1–P7 operational boards & detail pages | **Yes** — complete | **Reuse** as link targets |
| `erp-product-home.php` module hub | **Yes** | **Reuse** — already lists P1–P7 entry boards |
| `m360-navigation-registry.php` + `erp-route-map.php` | **Yes** — full catalog | **Reuse** — filter/group for bridge |
| P8 management dashboards | **Yes** — read-only oversight | **Reuse** — already on OWNER workbench |
| Permission preview | **Yes** — admin-only | **Reuse** — OWNER/SYSTEM_ADMIN only |
| Staff Home role workbenches | **Yes** — P11.7 + P11.7.1-A | **Upgrade** — add reference bridge group |
| Workflow action audit (`changed_by_user_id`) | **Partial** — per module | **Reuse** — no new schema required |
| Unified manager reference UI | **No** | **Build minimal** — navigation-only cards |
| Impersonation / act-as-staff | **No** | **Must not build** |

**Verdict:** P11.8-A should **connect and group existing routes** on Staff Home (and optionally a thin reference section derived from `m360_nav_registry()`), **not** build new workflow, permissions, or impersonation.

**Risk:** Many P1–P7 page guards use **session-only** checks (`m360_*_require_staff()`), not role keys. Navigation expansion does **not** require permission seed changes, but **action safety** depends on workflow gates inside handlers — managers acting as themselves may perform actions if they reach detail pages. This is acceptable only if audit captures `erp_auth_current_user_id()` (generally yes) and workflow gates remain enforced (generally yes).

---

## 2. Owner Requirement Interpretation

**Owner intent:** Managers and owners need a **reference/oversight path**. When staff cannot complete work, a manager must **find the relevant operational area**, **inspect workflow state**, and **guide or perform permitted actions as themselves**, with audit awareness.

**Interpretation for MOGHARE360:**

| Allowed in P11.8 | Forbidden in P11.8 |
|------------------|-------------------|
| Guided navigation to existing boards/lists | Impersonation |
| Read-only oversight dashboards | Silent “act as staff” session swap |
| Timeline / permission preview for diagnosis | Bypassing contract/OTP/signature gates |
| Performing actions **already allowed** to logged-in user on detail pages | New POST/action shortcut cards |
| Audit via existing `changed_by_user_id` | Permission or role seed expansion |
| Backlog for unsafe gaps | New workflow actions |

**Preferred model:** Manager logs in as **themselves**, navigates via bridge → board → detail, performs actions only where existing handlers permit, audit records **their** `user_id`.

---

## 3. Existing Similar Capability Discovery

### Owner standing rule applied

| Question | Answer for manager reference |
|----------|------------------------------|
| Similar capability exists? | **Yes — partial, fragmented** |
| Where? | Product home, route map, nav registry, P8 dashboards, per-role workbenches, jobcard timeline |
| Status | Operational but **disconnected** from OWNER/SYSTEM_ADMIN Staff Home operational path |
| Next step | **Upgrade/connect** — not greenfield build |
| If none existed | Would need minimal nav group only — but assets **already exist** |

### Table 1 — Existing Similar Capability

| Requirement | Existing file/table/doc | Status | Reuse/Upgrade/Build | Risk |
|-------------|-------------------------|--------|---------------------|------|
| Owner operational oversight | `erp-owner-control-center.php`, `m360-owner-control-helper.php` | Read-only, operational | **Reuse** | Session-only guard |
| Management KPI dashboard | `erp-management-dashboard.php`, `m360-management-kpi-helper.php` | Read-only P1–P7 KPIs | **Reuse** | On OWNER workbench |
| Full P1–P7 module entry | `erp-product-home.php` | Operational nav hub | **Upgrade** — link from Staff Home bridge | Missing intake/online-requests card |
| Complete route catalog | `m360-navigation-registry.php`, `erp-route-map.php` | Complete registry | **Reuse** | Dev-oriented URLs visible on route map |
| Role workbench | `m360-staff-home-helper.php`, `erp-staff-home.php` | Operational P11.7.1-A | **Upgrade** — add reference group | Workbench ≠ enforcement |
| Permission inspection | `erp-access-permission-preview.php` | Admin-only, read-only | **Reuse** | Requires `user_id` query |
| Jobcard audit trail | `erp-jobcard-timeline.php`, `erp_jobcard_change_history` | Operational | **Reuse** | Per-jobcard ID required |
| Access change audit | `m360-access-audit-helper.php`, `core_access_change_history` | Operational | **Reuse** | Admin scope only |
| Manager reference backlog card | P11.7.1-A backlog on Staff Home | Documented only | **Upgrade** in P11.8-A | Not yet navigable |
| Impersonation | — | **Absent** | **Do not build** | High security |
| Unified manager bridge page | — | **Missing** | **Build minimal** (nav group only) | Low if nav-only |

---

## 4. Owner / System Admin Reference Access

### Current state (post P11.7.1-A)

**Login paths:**

- **OWNER:** `owner-login.php` → `erp-product-home.php` (primary); may also use `staff-login.php` → `erp-staff-home.php` if company user exists (`is_system_owner` → role OWNER).
- **SYSTEM_ADMIN:** `staff-login.php` → `erp-staff-home.php`.

**Staff Home workbench (OWNER / SYSTEM_ADMIN):**

| Group | Cards |
|-------|-------|
| کار امروز | مدیریت دسترسی پرسنل |
| پیگیری | خانه محصول، نقشه مسیرها |
| گزارش | داشبورد مدیریت، مرکز کنترل مالک، آمادگی انتشار، پیش‌نمایش دسترسی |
| backlog | محیط مرجع مدیر/مالک (disabled), HR P15 items |

**What owner can see:**

- **Operational status:** Yes — P8 dashboards (KPI, high-risk, owner control sections).
- **P1–P7 boards from Staff Home:** **No direct board cards** — must use **خانه محصول** or route map.
- **P1–P7 from product home:** Yes — Reception, Technical, Estimate, Work, QC, Final Invoice boards (partial — no online-requests, intake contracts, payment tracking as separate cards).
- **Read-only vs actionable:** P8 = read-only by design. P1–P7 boards actionable if manager navigates and workflow permits.
- **Connected to Staff Home:** Admin/oversight yes; **operational reference bridge no** (backlog only).

**Can owner access staff work pages directly?**

**Yes**, if session active — guards are predominantly `m360_*_require_staff()` / `m360_mgmt_require_staff()`, **not role-scoped**. Workbench omission does not block URL access.

**Should P11.8-A add guided operational reference bridge?**

**Yes — navigation-only.** Add a Staff Home group (e.g. «مرجع عملیاتی One-Day Run») linking to **existing board/list pages** only — mirror product home + gaps (online requests, intake contracts, payment tracking, delivery control, timeline). **No new permissions.**

**SYSTEM_ADMIN:** Same workbench as OWNER on Staff Home; same P11.8-A recommendation. Privileged role assignment still restricted to platform owner flag.

---

## 5. Service Manager Reference Access

### Current state

**Staff Home workbench:**

| Group | Content |
|-------|---------|
| کار امروز | تابلوی فنی، تابلوی اجرای کار، تابلوی QC |
| پیگیری | جزئیات فنی/اجرا (info/guided) |
| عملیات | action notes (non-clickable) |
| گزارش | تایم‌لاین JobCard |
| backlog | HR P15 items, technician-style gaps N/A |

**Visibility gaps vs full One-Day Run:**

- Reception (online requests, jobcards, contracts): **not on workbench**
- Parts (reserve, use, stock): **not on workbench**
- Finance (estimate, payment, invoice, settlement): **not on workbench**

**Can SERVICE_MANAGER reach those URLs?**

Technically **yes** (session guard) if URL known; **not guided** from Staff Home.

**Should service manager see all roles or only workshop floor?**

**Recommendation for P11.8-A:**

| Area | SERVICE_MANAGER bridge |
|------|------------------------|
| Technical / execution / QC | **Primary** — already on workbench |
| Reception / contract | **Reference link** (read/nav) — manager often unblocks intake |
| Parts | **Reference link** — when parts block work |
| Finance | **Reference link (read-only emphasis)** — high audit risk on settlement |
| Full finance action | **Do not expose** as primary bridge unless owner policy confirms |

**Expanding visibility:** **Navigation-only** — **no permission change** required today due to session-level guards. **Risk:** unintended finance/settlement actions — mitigate with **read-only labeling**, link to **boards not detail**, keep settlement detail as info/backlog.

**Action access safe with current permissions?**

**Partially.** SERVICE_MANAGER can act on P3/P5/P6 where handlers allow. Reception `manager_override_contract_gate` exists and is **not role-gated** — already a workflow-level manager action with audit. Expanding nav **does not add new override actions**.

**What should remain disabled/backlog:**

- Impersonation
- Direct POST/action endpoint cards
- Direct detail pages without JobCard context (keep info/guided pattern from P11.7.1-A)
- HR self-service (P15 backlog)

---

## 6. Route Map and Navigation Registry Findings

**Files:**

- `public_html/includes/m360-navigation-registry.php` — canonical P1–P10 route catalog with phase, title_fa, url, category, method, flags (`is_staff_entry`, `is_owner_entry`, etc.)
- `public_html/erp-route-map.php` — renders full registry with file-exists audit via `m360-route-audit-helper.php`
- `public_html/erp-product-home.php` — curated subset of module entry boards

**Registry completeness:**

- P1 intake, P1.5 contract, P2 reception, P3 technical, P4 estimate, P5 work, P6 QC, P7 invoice/settlement/delivery, P8 management, P9 soft run, P10 release — **documented**
- POST actions marked with notes — suitable for **exclusion** from clickable bridge

**Role filtering:**

- Registry has `access_type`, `is_staff_entry`, `is_owner_entry` flags — **can filter programmatically** without schema change
- **No existing “manager bridge filter” function** — would be new helper logic in P11.8-A (nav grouping only)

**Reuse for manager bridge:**

| Source | Reuse approach |
|--------|----------------|
| `m360_nav_registry()` | Filter `is_staff_entry` + GET + board/list URLs |
| `erp-product-home.php` cards | Duplicate as Persian Staff Home cards |
| `erp-route-map.php` | Link as “فهرست کامل مسیرها” for admins |

**Gap in product home vs One-Day Run:**

Product home cards omit: `erp-reception-online-requests.php`, `erp-intake-contracts.php`, `erp-payment-tracking.php`, `erp-delivery-control.php`, parts boards. Bridge should **include these** for complete reference.

---

## 7. Permission and Audit Findings

### Permission model

| Component | Behavior |
|-----------|----------|
| `erp-auth-context.php` | Session user_id, company_id |
| `m360_access_mgmt_require_admin()` | OWNER or SYSTEM_ADMIN for access UI |
| `m360_*_require_staff()` | Session only for most P1–P7 modules |
| `hr_require_auth($conn, 'hr.*')` | Permission-key based — HR admin separate |
| Workbench | **Catalog only** — not authorization enforcement |

**Permission preview (`erp-access-permission-preview.php`):**

- Requires admin guard + valid `user_id`
- Read-only route/permission listing for troubleshooting
- **Safe for OWNER/SYSTEM_ADMIN only** (already on their workbench post P11.7.1-A)
- Helps manager **understand why staff cannot access a page** — valuable for reference bridge **support tool**, not daily ops card for SERVICE_MANAGER

### Audit trail

| Mechanism | Table/helper | Records actor? |
|-----------|--------------|----------------|
| Jobcard workflow | `erp_jobcard_change_history` | `changed_by_user_id` — P2–P7 helpers |
| Contract events | `erp_intake_contract_events` | user_id on events |
| Settlement | `m360-settlement-helper.php` | `SETTLEMENT_MANAGER_RELEASE_APPROVED` |
| Contract manager override | `m360-intake-contract-helper.php` | `manager_override`, reason stored |
| Access changes | `m360-access-audit-helper.php` | `changed_by_user_id` |
| Timeline aggregation | `m360-jobcard-timeline-helper.php` | Merges history tables |
| Login | Auth API | Request logging |

**“Manager performed this action” pattern:**

- **No dedicated flag** — actions recorded as **logged-in user**
- Sufficient for no-impersonation model **if** managers use their own login
- **Gap:** no unified UI label “manager reference intervention” — audit is implicit via user_id

**Risk if audit unproven for a module:**

All major P1–P7 POST handlers reviewed use `erp_auth_current_user_id()` in history inserts. **Low risk** for bridge navigation phase. **Report as backlog:** formal manager-reference audit taxonomy (optional future, not blocking P11.8-A nav).

---

## 8. Impersonation / Act-As-Staff Finding

**Search results:** No `impersonate`, `act_as`, or `act-on-behalf` session mechanism in operational ERP code.

| Finding | Location | Safe? |
|---------|----------|-------|
| Backlog warning text | `m360-staff-home-helper.php` | Documentation only — rejects raw impersonation |
| Pilot `DELEGATE` mode | `erp-pilot-scenario-builder.php` | P12 pilot UI — **not production manager bridge** |
| Contract `manager_override` | Reception/intake workflow | Manager acts **as self** with reason + event — not impersonation |

**Conclusion:** **Impersonation does not exist.** P11.8 **must not create it.** Manager reference = **same session, same user_id, existing pages**.

---

## 9. One-Day Run Manager Reference Matrix

### Table 3 — One-Day Run Manager Reference Matrix

| Flow step | Existing page | Staff role | Manager visibility now | Manager action now | Audit risk | P11.8-A recommendation |
|-----------|---------------|------------|------------------------|-------------------|------------|------------------------|
| 1. Customer request | `customer-request.php` | Customer | Product home indirect; route map | Public form — N/A | Low | **Link** (reference) for OWNER/ADMIN |
| 2. Reception receives | `erp-reception-online-requests.php` | RECEPTION | Route map / direct URL only | Yes if navigates | Medium — accept action audited | **Link** board for OWNER/ADMIN; **reference link** for SERVICE_MANAGER |
| 3. Reception JobCard | `erp-reception-jobcards.php` → detail | RECEPTION | Route map / URL | Yes — reception actions | Medium | **Link** board; detail via list (no direct detail card) |
| 4. Contract/signature gate | `erp-intake-contracts.php` | RECEPTION | Route map / URL | Yes; override action exists | Medium — override audited | **Link** board; keep OTP/sign gates |
| 5. Service manager assigns | `erp-technical-board.php` → detail | SERVICE_MANAGER | On SM workbench | Yes — assign_technician | Low — history table | **Already linked** for SM; **link** for OWNER/ADMIN |
| 6. Technician diagnosis | `erp-technical-board.php` → detail | TECHNICIAN | SM/tech workbench | Yes | Low | **Link** for OWNER/ADMIN reference |
| 7. Parts reserve/use | `erp-part-reserve.php`, `erp-jobcard-part-use.php` | PARTS | Route map / URL | Yes | Medium | **Reference link** SM/OWNER/ADMIN |
| 8. Estimate/payment/invoice | `erp-estimate-board.php`, `erp-payment-tracking.php`, `erp-final-invoice-board.php` | FINANCE | Route map / URL | Yes — finance gates apply | **High** on settlement | **Link** boards; settlement detail **info/backlog** or read-only note |
| 9. Work execution | `erp-work-execution-board.php` | TECHNICIAN/SM | SM workbench | Yes | Low | **Already linked** SM; **link** OWNER/ADMIN |
| 10. QC | `erp-qc-board.php` | QC/SM | SM/QC workbench | Yes | Low | **Link** all manager roles |
| 11. Settlement | `erp-settlement-detail.php` | FINANCE | Route map / URL | Yes — manager_release action | **High** | **Board link** only; detail guided; no action shortcut |
| 12. Delivery/close | `erp-delivery-control.php`, customer pages | QC/Customer | Partial | Partial | Medium | **Link** delivery control; customer pages reference only |

**Cross-cutting:** `erp-jobcard-timeline.php?jobcard_id=` — **link** as «تشخیص پرونده» tool for all manager roles (requires ID — guided from boards).

---

## 10. Existing Routes That Can Be Reused

### Table 4 — Route Reuse

| Existing route | Purpose | Current linked from | Candidate bridge group | Clickable? | Notes |
|----------------|---------|---------------------|------------------------|------------|-------|
| `erp-reception-online-requests.php` | Online intake board | RECEPTION workbench | مرجع پذیرش | Yes (GET board) | Missing from product home |
| `erp-reception-jobcards.php` | Reception jobcards | RECEPTION workbench | مرجع پذیرش | Yes | |
| `erp-intake-contracts.php` | Contract gate board | RECEPTION workbench | مرجع پذیرش / قرارداد | Yes | |
| `erp-technical-board.php` | Technical ops | SM/TECH workbench | مرجع سالن / فنی | Yes | |
| `erp-work-execution-board.php` | Work execution | SM/TECH workbench | مرجع سالن | Yes | |
| `erp-qc-board.php` | QC board | SM/QC workbench | مرجع QC | Yes | |
| `erp-part-reserve.php` | Parts reserve | PARTS workbench | مرجع انبار | Yes | SM reference optional |
| `erp-jobcard-part-use.php` | Parts use | PARTS/TECH | مرجع انبار | Yes | Needs JobCard context in page |
| `erp-estimate-board.php` | Estimate gate | FINANCE workbench | مرجع مالی | Yes | |
| `erp-payment-tracking.php` | Payments | FINANCE workbench | مرجع مالی | Yes | |
| `erp-final-invoice-board.php` | Final invoice | FINANCE workbench | مرجع مالی | Yes | |
| `erp-delivery-control.php` | Delivery readiness | QC workbench | مرجع تحویل | Yes | |
| `erp-jobcard-timeline.php` | Audit timeline | SM reports | تشخیص / نظارت | Yes with jobcard_id | Guided from boards |
| `erp-management-dashboard.php` | KPI oversight | OWNER workbench | نظارت مدیریتی | Yes | Read-only |
| `erp-owner-control-center.php` | Risk flags | OWNER workbench | نظارت مدیریتی | Yes | Read-only |
| `erp-product-home.php` | Module hub | OWNER followup | پشتیبان | Yes | Already linked |
| `erp-route-map.php` | Full catalog | OWNER followup | پشتیبان فنی | Yes | Dev URLs visible |
| `erp-access-permission-preview.php` | Permission debug | OWNER reports | پشتیبانی دسترسی | Yes | Admin only |
| `*-detail.php` | Record detail | Info cards | **Not direct bridge** | No | JobCard ID required |
| `*-action.php` | POST handlers | Note cards | **Never bridge** | No | Workflow gate |

---

## 11. Missing or Unsafe Areas

| Gap | Severity | P11.8-A treatment |
|-----|----------|-------------------|
| No unified manager reference group on Staff Home | High UX | **Build** nav group |
| Product home omits several One-Day Run boards | Medium | **Include in bridge** |
| Workbench ≠ page authorization | Medium | Document; no permission change in P11.8-A |
| SERVICE_MANAGER lacks cross-role reference links | Medium | **Add reference links** (nav only) |
| No impersonation (correct) | N/A | Keep absent |
| No “manager reference” audit event type | Low | Backlog — optional taxonomy |
| Settlement/finance direct action risk | High | Boards only; no action cards |
| Route map shows raw URLs | Low | Accept for admin; Staff Home stays polished |
| `erp-pilot-scenario-builder.php` DELEGATE | Out of scope | Not P11.8 |

---

## 12. Security and Workflow Risks

**Enforced for P11.8 (discovery phase and future P11.8-A):**

- No impersonation
- No silent act-as-staff
- No bypassing workflow gates (contract OTP, estimate approval, QC, settlement)
- No direct POST/action endpoint cards
- No direct detail cards without record context
- No permission expansion in discovery
- No role seed change
- No database schema change

**Existing risks (pre-bridge, remain):**

| Risk | Detail |
|------|--------|
| Session-only guards | Any staff user may hit many URLs directly |
| `manager_override_contract_gate` | Not limited to SERVICE_MANAGER role |
| Finance/settlement actions | High impact if manager navigates to detail |
| Shared owner login | Documented in access readiness (`OWNER_SHARED_LOGIN_RISK`) |

**Mitigation for P11.8-A (nav-only):**

- Link **boards and lists** only
- Persian labels: «مرجع — ورود از تابلو»
- Keep P11.7.1-A info/note/backlog patterns
- Timeline as diagnostic tool with explicit JobCard requirement
- Do not add new override actions

---

## 13. Reuse / Upgrade / Build Decision

### Table 2 — Manager Role Capability Matrix

| Role | Current visibility | Current action ability | Missing bridge | Safe next action | Permission change? | Workflow change? |
|------|-------------------|------------------------|----------------|------------------|-------------------|------------------|
| OWNER | P8 + product home + route map | Yes on P1–P7 if navigates | One-Day Run board group on Staff Home | **Add reference nav group** | No | No |
| SYSTEM_ADMIN | Same as OWNER | Same | Same | **Same bridge as OWNER** | No | No |
| SERVICE_MANAGER | P3/P5/QC + timeline | Yes on workshop modules | Reception/parts/finance reference | **Add limited reference links** | No | No |
| RECEPTION | Own workbench | Own actions | Manager bridge | **No** | — | — |
| TECHNICIAN | Own workbench | Own actions | — | **No** | — | — |
| PARTS | Own workbench | Own actions | — | **No** | — | — |
| FINANCE | Own workbench | Own actions | — | **No** | — | — |
| QC | Own workbench | Own actions | — | **No** | — | — |

### Table 5 — Risk / Backlog

| Item | Why not now | Required future phase | Security concern |
|------|-------------|----------------------|------------------|
| Impersonation | Forbidden by policy | Never / alternative audit model | Identity fraud |
| Manager override engine | Needs audit + role design | P11.8-B or later | Workflow bypass |
| Permission expansion for managers | Out of scope | Only if session guards tightened | Over-privilege |
| Unified manager audit event type | Nice-to-have | Optional schema/docs | Forensics |
| HR self-service | P15 | P15 | Self-scope auth |
| Role-based page guards | Large refactor | Post-V1 hardening | Defense in depth |
| Finance detail in SM bridge | High action risk | Policy decision first | Settlement misuse |

---

## 14. Minimum P11.8-A Recommendation

**Scope: Manager Reference Navigation Bridge — navigation only**

### OWNER / SYSTEM_ADMIN

Add Staff Home group **«مرجع عملیاتی One-Day Run»** (or upgrade existing backlog card to active nav group):

| Card (Persian) | Target | Type |
|----------------|--------|------|
| درخواست‌های آنلاین | `erp-reception-online-requests.php` | Board link |
| JobCardهای پذیرش | `erp-reception-jobcards.php` | Board link |
| قراردادهای پذیرش | `erp-intake-contracts.php` | Board link |
| تابلوی فنی | `erp-technical-board.php` | Board link |
| تابلوی اجرای کار | `erp-work-execution-board.php` | Board link |
| تابلوی QC | `erp-qc-board.php` | Board link |
| رزرو/مصرف قطعه | `erp-part-reserve.php`, `erp-jobcard-part-use.php` | Board links |
| برآورد / پرداخت / فاکتور | estimate, payment, invoice boards | Board links |
| کنترل تحویل | `erp-delivery-control.php` | Board link |
| تایم‌لاین JobCard | `erp-jobcard-timeline.php` | Guided (needs ID) |

Keep existing P8 + access tools. **Replace** disabled «محیط مرجع» backlog with this group **or** activate alongside with clear Persian scope note.

### SERVICE_MANAGER

Add smaller group **«مرجع نقش‌های مرتبط»**:

- Reception boards (online, jobcards, contracts) — reference
- Parts boards — reference
- Finance boards — reference read-only labeling
- Keep existing workshop cards as primary

### Implementation constraints (P11.8-A)

- Modify only `m360-staff-home-helper.php` (+ tests/docs) — same pattern as P11.7.1-A
- **No new PHP pages**
- **No permission/role/SQL/Auth changes**
- **No action/detail direct links**
- Reuse `m360_staff_home_item()` + existing render rules

### Tests to add in P11.8-A

- Bridge cards exist for OWNER/SYSTEM_ADMIN
- Bridge boards are GET nav only (no action endpoints)
- SERVICE_MANAGER reference group scope
- No impersonation markers
- Existing P11.7 matrices still pass

---

## 15. Final Persian Answers

**1. آیا دسترسی مرجع مدیر/مالک قبلاً ساخته شده یا فقط پراکنده است؟**  
**پراکنده است.** اجزاء وجود دارد (خانه محصول، نقشه مسیر، داشبوردهای P8، تایم‌لاین، workbench نقش‌ها) اما **پل مرجع یکپارche روی Staff Home ساخته نشده** — فقط کارت backlog غیرفعال در P11.7.1-A ثبت شده است.

**2. آیا مالک و مدیر سیستم الان می‌توانند مسیر کامل P1 تا P7 را از یک صفحه ببینند؟**  
**نه از Staff Home.** از **خانه محصول** و **نقشه مسیرها** می‌توانند به بخش‌های P1–P7 بروند، اما حتی خانه محصول همه تابلوهای One-Day Run (مثل درخواست آنلاین، قرارداد، پیگیری پرداخت) را یکجا نشان نمی‌دهد.

**3. آیا مدیر سرویس باید همه نقش‌ها را ببیند یا فقط سالن/فنی/QC؟**  
**کار اصلی:** سالن/فنی/QC/اجرا (همین الان روی workbench است). **مرجع:** دیدن پذیرش، انبار و مالی به‌صورت **لینک راهنما** مفید است وقتی کار گیر کرده — نه به‌عنوان جایگزین نقش‌های دیگر.

**4. آیا برای P11.8-A نیاز به Permission جدید داریم؟**  
**خیر** — برای پل ناوبری فقط، با guardهای session فعلی و لینک به صفحات موجود کافی است. سخت‌گیری آینده روی role guard جداگانه است.

**5. آیا برای P11.8-A نیاز به DB جدید داریم؟**  
**خیر.** registry، workbench و audit موجود کافی است.

**6. آیا impersonation یا act-as-staff وجود دارد؟**  
**خیر** — در ERP عملیاتی وجود ندارد.

**7. آیا باید چنین چیزی ساخته شود؟**  
**Impersonation نباید ساخته شود.** **پل ناوبری مرجع** باید ساخته شود — با اتصال مسیرهای موجود، بدون workflow جدید.

**8. کمترین Patch امن بعدی چیست؟**  
**P11.8-A:** گروه کارت «مرجع عملیاتی» روی Staff Home برای OWNER/SYSTEM_ADMIN (+ مرجع محدود برای SERVICE_MANAGER) — فقط لینک به **تابلو/فهرست** موجود، با همان قواعد P11.7.1-A (بدون action مستقیم، بدون detail بدون ID).

**9. چه چیزهایی باید backlog بماند؟**  
Impersonation؛ موتور override مرجع با audit اختصاصی؛ گسترش Permission/role seed؛ guard نقش‌محور سراسری؛ HR self-service (P15)； لینک مستقیم detail/action؛ finance/settlement action برای مدیر سالن بدون سیاست صریح.

---

P11.8-0 discovers whether a manager reference navigation bridge can be built by reusing existing workbench, route, permission-preview, management and audit structures without impersonation, permission changes, database schema changes, workflow changes, or P12 scope.
