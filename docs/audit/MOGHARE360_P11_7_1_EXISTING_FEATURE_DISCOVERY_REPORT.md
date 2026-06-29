# MOGHARE360 P11.7.1-0 — Admin Override / Employee Profile / Existing Feature Discovery Report

**Phase:** P11.7.1-0  
**Mode:** REPORT ONLY — no code, SQL, Auth, permissions, routes, or workflow changes  
**Date:** 2026-06-26  
**Repository:** `moghare360-portal` @ V1 RC

---

## 1. Executive Summary

This report applies the owner’s new rule: **before building anything, discover what already exists, classify it, and recommend upgrade/connect/fix vs controlled build.**

**Findings in brief:**

| Area | Verdict |
|------|---------|
| **Managerial reference access** | **Partial / indirect only.** No impersonation or “act on behalf of staff” model. OWNER/SYSTEM_ADMIN get oversight + access management, not a unified operational workbench. SERVICE_MANAGER gets P3/P5/QC boards only. Operational pages are reachable by direct URL or `erp-product-home.php` because many guards are session-only — but this is **not a designed manager override path** and lacks role-scoped audit for “manager completed staff task.” |
| **Employee personal profile** | **Admin HR exists (Phase 7); employee self-service does not.** `erp-employee-profile.php` is an internal HR dossier (admin). Legacy `staff-profile.php` is a disconnected prototype. `core_staff_profiles.profile_photo_path` exists in schema; no upload UI on ERP path. |
| **HR self-service** | **Missing by design** (Phase 7 scope). Leave, self-service password change, personal documents, and employee-facing attendance/payroll are not built. P11.7 workbench explicitly backlog-labels HR for **P15**. |
| **Staff Home UX** | **Functional but developer-facing.** Workbench cards expose raw `role_code`, PHP filenames, POST/action endpoint names, and English KPI labels (`user_id`, `role_code`, `Permission`). Safe UX-only cleanup is possible in P11.7.1 without workflow changes. |

**Recommended direction:** Do **not** build new manager impersonation or full HR self-service before One-Day Run. **Minimum next patch:** P11.7.1 UX polish on Staff Home (hide internal route strings; humanize identity card). **Connect** existing Phase 7 HR for admin/owner via product home (already reachable). **Defer** employee self-service profile to **P15** per existing scope gates.

---

## 2. Owner Requirement Interpretation

### 2.1 Manager / owner reference access

Owner intent: when staff cannot finish a task, manager or owner must **see and operate the full operational path** of that work.

**Interpretation for this codebase:** This means cross-role visibility and action on P1–P7 workflow pages (reception → delivery), with audit showing the **manager’s identity**, not silent impersonation.

### 2.2 Employee personal profile

Owner intent: each employee has a **personal area** (photo, profile link, password change, documents, leave, overtime, attendance, payroll preview, HR requests).

**Interpretation:** Split into (a) **portal identity** (`core_users` / `core_staff_profiles`) and (b) **HR dossier** (`erp_hr_employees` + related tables). Today only (b) exists as **admin-only** Phase 7; (a) has inline KPI on Staff Home but no profile page link.

### 2.3 Existing HR / personnel pages

Owner remembers Cursor created personnel area — **confirmed:** Phase 7 HR module exists and is tested; it is **not linked from Staff Home** and is **not employee self-service**.

### 2.4 Staff Home / Workbench UX

Owner observes strings like `نقش: SERVICE_MANAGER erp-technical-jobcard-detail.php`. **Confirmed in code** (`m360_staff_home_render_workbench_item`, lines 716–719). This is catalog/debug-oriented output, not end-user polish.

---

## 3. Existing Similar Feature Discovery

### Owner rule applied

For each requirement below: **exists? → where? → status → upgrade vs build?**

| Requirement | Similar feature? | Location | Status | Next step |
|-------------|------------------|----------|--------|-----------|
| Manager sees full operational path | Partial | `erp-product-home.php`, `erp-route-map.php`, P8 dashboards; session-only guards on P1–P7 pages | **Disconnected catalog** — not role workbench | **Upgrade:** add owner/manager operational bridge cards (no new workflow) |
| Manager acts when staff blocked | Partial | Workflow overrides: `manager_override_contract_gate`, `manager_release_approval` | **Exists but not role-scoped** | **Upgrade:** document + optional role hint on workbench; do not expand bypass |
| Manager audit trail | Partial | Jobcard history, settlement events, access change history | **Per-module, not unified “manager reference”** | **Upgrade** audit labels in existing events |
| Employee profile page | Yes (admin) | `erp-employee-profile.php` | **Operational, admin-only** | **Connect** for HR admin roles; not employee self-service |
| Employee profile page | Yes (legacy) | `staff-profile.php` | **Prototype, disconnected** | **Do not reuse** — migrate concepts to P15 |
| Photo / avatar | Partial | `core_staff_profiles.profile_photo_path`; legacy `staff-auth.php` | **Schema only on ERP path** | **Build later** (P15) upload UI |
| Change password (self) | No | — | **Missing** | **Backlog P15** |
| Change password (admin) | Yes | `erp-access-password-reset.php` | **Operational** | **Reuse** — already in access mgmt |
| Attendance / کارکرد | Yes (admin) | `erp-attendance-entry.php` | **Operational, HR admin** | **Connect** admin path; self-service P15 |
| Payroll preview | Yes (admin) | `erp-payroll-preview.php` | **Operational, non-official preview** | Same |
| Leave / مرخصی | No | — | **Missing** | **P15+** |
| Overtime request | No | — | **Missing** (overtime **fields** in attendance/payroll only) | **P15+** |
| Personal documents | No | — | **Missing** | **P15+** |
| Staff Home workbench | Yes | `erp-staff-home.php` + helper | **Operational P11.7** | **Upgrade UX** P11.7.1 |
| Permission preview | Yes | `erp-access-permission-preview.php` | **Operational, admin guard** | Fix workbench link mismatch for non-admin roles |

---

## 4. Managerial Reference Access Audit

### 4.1 Architecture note

- **Staff login** → `erp-staff-home.php` (role workbench catalog).
- **Owner login** → `erp-product-home.php` (module hub).
- **Workbench ≠ enforcement.** Cards are navigation hints; page guards vary (admin-only, session-only, permission-key).

### 4.2 Per-role analysis

#### OWNER

| Question | Answer |
|----------|--------|
| Post-login landing | `owner-login.php` → `erp-product-home.php` (fallback: owner control / mgmt dashboard) |
| All operational workbenches on Staff Home? | **No** — Staff Home shows access mgmt, product home, route map, P8 read-only reports |
| Reception / technical / parts / finance / QC? | **Reachable** via `erp-product-home.php` / route map / direct URL if session exists; **not on OWNER workbench** |
| Act vs view? | **Act:** access management (users, roles, temp passwords). **View:** P8 dashboards. **Can act on workshop** if navigates to P1–P7 (session guard only) |
| Override / delegation / on-behalf? | **No impersonation.** Privileged role assignment requires `is_system_owner` |
| Audit? | Access change history; jobcard/settlement events if manager navigates to those modules |
| Bypass risk if expanded? | **High** if impersonation added without audit; moderate today because session-only guards allow any logged-in user to hit many action pages |

#### SYSTEM_ADMIN

| Question | Answer |
|----------|--------|
| Post-login landing | `staff-login.php` → `erp-staff-home.php` |
| Workbench | **Identical to OWNER** on Staff Home |
| Operational pages | Same as OWNER — via product home, not workbench |
| Act vs view | **Act:** access mgmt (cannot assign owner/system_admin without platform owner flag). **View:** P8 dashboards |
| Override? | Same workflow overrides as any staff if on correct page |
| Audit? | Same as OWNER for admin actions |

#### SERVICE_MANAGER

| Question | Answer |
|----------|--------|
| Post-login landing | `staff-login.php` → `erp-staff-home.php` |
| Workbench | Technical board, work execution board, QC board, timeline, permission preview (link **broken** — page requires admin) |
| Reception / parts / finance? | **Not on workbench**; may reach some URLs directly (session guard) |
| Act vs view? | **Act:** P3/P5 POST actions (assign technician, QC queue, execution). **View:** boards + timeline |
| Override? | `manager_override_contract_gate` available on reception jobcard detail — **not limited to SERVICE_MANAGER role** |
| Audit? | Jobcard change history, technical/work execution events |
| Bypass risk | Workbench understates access; direct URL + weak role guards = inconsistent enforcement |

### 4.3 Existing override patterns (not “reference access”)

| Pattern | File / area | Role-gated? | Audited? |
|---------|-------------|-------------|----------|
| Contract gate manager override | `m360-reception-jobcard-helper.php` | **No** | Yes (`JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE`) |
| Settlement manager release | `erp-settlement-detail.php` | **No** | Yes (`SETTLEMENT_MANAGER_RELEASE_APPROVED`) |
| Invoice variance override | Final invoice helper | Finance context | Stored on invoice |
| Owner control center | `erp-owner-control-center.php` | Session | **Read-only** — explicit banner |

**No safe impersonation mechanism exists.** Preferred future model (if needed): manager performs action **as themselves** with explicit audit — aligns with existing event tables, not new impersonation session.

---

## 5. Owner / System Admin Capability

**Files:** `m360-staff-home-helper.php` (OWNER/SYSTEM_ADMIN items, lines 305–317), `erp-access-management.php`, `erp-product-home.php`, `erp-management-dashboard.php`, `erp-owner-control-center.php`

| Capability | Exists? | Notes |
|------------|---------|-------|
| User / role / password admin | Yes | `erp-access-management.php` |
| View pipeline risk / KPI | Yes | P8 dashboards, read-only |
| Run reception → delivery workflow from Staff Home workbench | **No** | Must use product home or URLs |
| HR employee dossier | Yes | `erp-hr-dashboard.php` chain — not on Staff Home workbench |
| Permission preview | Yes | On workbench |
| “Complete staff task on their behalf” | **No** | No delegation model |

**Gap vs owner requirement:** Oversight and access control **exist**; **unified managerial operational path from Staff Home does not.**

---

## 6. Service Manager Capability

**Files:** `m360-staff-home-helper.php` (SERVICE_MANAGER items, lines 338–355), P3/P5/QC modules

| Capability | Exists? | Notes |
|------------|---------|-------|
| Assign / track / QC boards | Yes | Core SERVICE_MANAGER workbench |
| Reception / finance / parts | **Not on workbench** | Gap for “full path” supervision |
| Dedicated service manager console | **No** | Uses same boards as technician + detail drill-down |
| Manager reference when reception stuck | Partial | Can open reception URLs if knows them; not guided |
| Audit of manager actions | Partial | Per jobcard events |

**Gap:** Manager can operate **technical/QC/execution slice** well; **cannot discover full cross-role path** from Staff Home alone.

---

## 7. Employee Profile Discovery

### 7.1 ERP Phase 7 — internal HR dossier (primary existing feature)

| Item | Detail |
|------|--------|
| **File** | `public_html/erp-employee-profile.php` |
| **Helper** | `public_html/includes/erp-hr-helper.php` |
| **Auth** | `hr_require_auth($conn, 'hr.employee.view')` — permission key, not self-service |
| **Classification** | **Real operational page — admin-only HR** |
| **Features** | Search/list employees; view contracts, attendance, payroll preview, training, discipline |
| **Photo** | **No** — `erp_hr_employees` has no photo column |
| **Staff Home link** | **No** |
| **Employee-safe** | **No** — HR admin view of all employees |

**Related operational pages (Phase 7, all admin):**

- `erp-hr-dashboard.php` — HR hub
- `erp-employee-create.php` / `submit-employee-create.php`
- `erp-employment-contract.php`
- `erp-attendance-entry.php`
- `erp-payroll-preview.php`
- `erp-hr-training-discipline.php`

**Docs:** `docs/missions/phase_7_hr_internal_admin/` — Phase 7 tests **PASSED** (`PHASE_7_90_TEST_RESULT.md`).

### 7.2 Portal identity (login user)

| Item | Detail |
|------|--------|
| **Tables** | `core_users`, `core_staff_profiles` |
| **Staff Home** | Shows `full_name`, `department_name`, `position_name` inline — **no profile link** |
| **Photo column** | `core_staff_profiles.profile_photo_path` — **no ERP upload UI** |
| **Classification** | **Partial** — read-only identity strip only |

### 7.3 Legacy staff portal (disconnected)

| Item | Detail |
|------|--------|
| **File** | `public_html/staff-profile.php` |
| **Auth** | `staff-auth.php` / MySQL legacy — **not** `staff-login.php` → ERP path |
| **Classification** | **Prototype** — placeholder modules (“آماده اتصال”), initial-letter avatar only |
| **Staff Home link** | **No** |
| **Recommendation** | **Do not connect** — superseded by ERP Staff Home; salvage UX ideas in P15 |

---

## 8. HR Self-Service Discovery

Phase 7 scope (`docs/missions/phase_7_hr_internal_admin/PHASE_7_01_SCOPE.md`) explicitly lists **Out of Scope:**

- Self-service staff login portal
- Production login/auth/permission changes

P11.7 scope gate (`docs/audit/MOGHARE360_P11_7_WORKBENCH_SCOPE_GATE_REPORT.md`) lists **HR self-service → P15 backlog**.

| Requirement | Status | Existing file/table | Connected from staff home? | Employee-safe? | Recommended phase |
|-------------|--------|---------------------|------------------------------|----------------|-------------------|
| Profile card | Partial | Inline KPI on `erp-staff-home.php` | N/A (same page) | Yes (own data only) | P11.7.1 UX |
| Profile link | Missing | — | No | — | P15 |
| Photo / avatar | Partial | `core_staff_profiles.profile_photo_path` | No | Would need self-scope | P15 |
| Change password (self) | Missing | Admin: `erp-access-password-reset.php` | No | Admin-only today | P15 |
| Document completion | Missing | — | No | — | P15+ |
| Leave request | Missing | No SQL table | No | — | P15+ |
| Overtime request | Missing | Overtime **hours/amount** in `erp_hr_attendance_records`, `erp_hr_payroll_previews` | No | — | P15+ |
| Attendance / work records (self) | Missing (self) | Admin: `erp-attendance-entry.php` | No | Admin-only | P15 |
| Payroll preview (self) | Missing (self) | Admin: `erp-payroll-preview.php` | No | Admin-only | P15 |
| Personal HR requests | Missing | — | No | — | P15+ |

**Password note:** `erp-access-password-reset.php` is **admin temporary reset** with audit via access helpers — **not** employee self-service change password.

**Planned docs (not implemented):**

- `docs/handover/MOGHARE360_EMPLOYEE_PROFILE_COMPLETION_RULE.md` — `PLANNED_NOT_IMPLEMENTED`
- `docs/handover/MOGHARE360_HR_DOCUMENTS_ATTENDANCE_PAYROLL_PREVIEW_RULE.md` — planned rules only

---

## 9. Staff Home UX Findings

**Files inspected:** `public_html/erp-staff-home.php`, `public_html/includes/m360-staff-home-helper.php`, `public_html/assets/css/m360-staff-home.css`

### 9.1 What users currently see (technical exposure)

| Surface | Example | Source |
|---------|---------|--------|
| Identity KPI labels | `user_id`, `role_code`, `Permission` | `erp-staff-home.php` lines 43–50 |
| Identity KPI values | `SERVICE_MANAGER`, numeric permission count | Same |
| Workbench card meta | `نقش: SERVICE_MANAGER` + `erp-technical-jobcard-detail.php` | Helper lines 716–719 |
| Disabled info cards | Button text = raw filename | Helper lines 727, 733, 738 |
| Descriptions | `POST`, `P1.5`, `read-only`, `JobCard`, `Release Readiness` | Workbench item definitions |
| Backlog group | Legacy filenames (`erp-jobcard-part-usage-list.php`) | Intentional for dev audit; visible to users |

### 9.2 UX issue classification

| Issue | Current example | Risk | Safe to clean in P11.7.1? | Requires deeper change? |
|-------|-----------------|------|---------------------------|-------------------------|
| Raw PHP filenames on cards | `erp-technical-jobcard-detail.php` | Low security; **high UX confusion** | **Yes** — hide or admin-only CSS class | Helper render change only |
| Raw role_code on cards | `نقش: SERVICE_MANAGER` | Low | **Yes** — use Persian role label map | Helper only |
| English KPI field names | `user_id`, `role_code` | Medium UX | **Yes** — Persian labels or hide for non-admin | Page template only |
| Permission count visible | `تعداد Permission مؤثر` | Low; slightly technical | **Yes** — hide for operational roles | Page template |
| POST/action endpoint cards | `erp-technical-jobcard-action.php` | Low | **Yes** — collapse “operations” group for end users or hide filenames | Helper only |
| Backlog dev filenames | `erp-finance-center.php` missing | Low | **Partial** — keep backlog concept, soften filename display | Helper only |
| SERVICE_MANAGER permission preview link | Card present, page 403 | **Medium** — broken UX | **Yes** — remove from non-admin roles | Helper route matrix |
| Manager operational path not shown | OWNER workbench has no P1–P7 cards | **Product gap**, not UX typo | **No** — needs workbench design decision | Route/helper + possibly permissions |

**Verdict:** **Staff Home should be UX-cleaned before One-Day Run** for operational roles (hide internal strings). **Not required** to change workflow logic.

---

## 10. Existing Files and Tables Found

### 10.1 Application files (representative)

**Staff / workbench**

- `public_html/erp-staff-home.php`
- `public_html/includes/m360-staff-home-helper.php`
- `public_html/assets/css/m360-staff-home.css`

**Access / admin**

- `public_html/erp-access-management.php`
- `public_html/erp-access-password-reset.php`
- `public_html/erp-access-permission-preview.php`
- `public_html/includes/m360-access-management-helper.php`

**Owner / management**

- `public_html/erp-owner-control-center.php`
- `public_html/erp-management-dashboard.php`
- `public_html/erp-product-home.php`
- `public_html/erp-route-map.php`

**HR admin (Phase 7)**

- `public_html/erp-hr-dashboard.php`
- `public_html/erp-employee-profile.php`
- `public_html/erp-employee-create.php`
- `public_html/erp-employment-contract.php`
- `public_html/erp-attendance-entry.php`
- `public_html/erp-payroll-preview.php`
- `public_html/erp-hr-training-discipline.php`
- `public_html/includes/erp-hr-helper.php`

**Legacy (disconnected)**

- `public_html/staff-profile.php`
- `public_html/staff-dashboard.php`
- `public_html/staff-auth.php`

### 10.2 Database / SQL (report only — no changes)

**`database/migrations/` (P1–P11):** Workshop workflow only — **no HR tables**.

**HR schema location:** `public_html/sql/sqlserver/phase_7_hr_internal_admin.sql`

| Table | Purpose |
|-------|---------|
| `dbo.erp_hr_employees` | Employee master (admin HR) |
| `dbo.erp_hr_employment_contracts` | Contracts |
| `dbo.erp_hr_attendance_records` | Attendance + `overtime_hours` |
| `dbo.erp_hr_payroll_previews` | Non-official payroll preview |
| `dbo.erp_hr_training_records` | Training |
| `dbo.erp_hr_disciplinary_records` | Discipline |
| `dbo.erp_hr_history` | HR entity audit |

**Identity / portal profile:** `public_html/sql/sqlserver/core_v0_02_master_tables.sql`

| Table | Relevant columns |
|-------|------------------|
| `dbo.core_users` | `username`, `password_hash`, `full_name`, … |
| `dbo.core_staff_profiles` | `user_id`, `department_id`, `position_id`, **`profile_photo_path`**, … |

**Missing tables (searched):** leave requests, overtime requests, employee documents/personnel files, password change audit.

---

## 11. Hidden / Disconnected Features

| Feature | Why hidden / disconnected |
|---------|---------------------------|
| Phase 7 HR module | Not in Staff Home workbench; reachable from `erp-hr-dashboard.php`, master console, operation control center |
| `erp-product-home.php` | Owner landing — operational module hub not mirrored on OWNER Staff Home workbench |
| Legacy `staff-profile.php` | Old login path; current flow uses `staff-login.php` → `erp-staff-home.php` |
| `core_staff_profiles.profile_photo_path` | Column exists; no wired upload/display on ERP staff path |
| P8 management pages | Marked `is_owner_entry` in nav registry but guarded by session only — not linked from SERVICE_MANAGER workbench |
| Workflow overrides | Exist on detail pages but not advertised on manager workbench |

---

## 12. Missing Features

| Missing | Owner expectation | Prior phase decision |
|---------|-------------------|-------------------|
| Manager reference workbench (full P1–P7 path) | Yes | Not built in P11.7 |
| Impersonation / on-behalf action | Not recommended unless safe mechanism | **Absent — keep absent** |
| Employee self-service profile hub | Yes | **P15 backlog** |
| Self-service change password | Yes | Not in V1 RC |
| Leave / overtime **requests** | Yes | No schema |
| Employee document upload | Yes | No schema |
| Avatar upload on ERP path | Yes | Schema only |
| Staff Home profile link | Yes | Not implemented |
| Unified manager audit for “reference completion” | Implied | Partial per-module events only |

---

## 13. Reuse / Upgrade / Build Decision Matrix

### Table 1 — Existing Similar Feature

| Requirement | Similar feature found? | File/table/doc | Status | Reuse/Upgrade/Build | Risk |
|-------------|------------------------|----------------|--------|---------------------|------|
| Manager reference access | Partial | P8 dashboards, product home, workflow overrides | Disconnected | **Upgrade** workbench + docs | Expanding bypass without audit |
| Employee profile (admin) | Yes | `erp-employee-profile.php`, `erp_hr_*` | Operational | **Reuse / connect** for HR admin | Admin-only leakage if mis-linked |
| Employee profile (self) | Partial | Staff Home KPI, legacy `staff-profile.php` | Prototype / partial | **Build P15** | Scope creep if built now |
| HR self-service | No | Phase 7 scope out | Missing | **Build P15** | Auth/permission churn |
| Change password (self) | No | Admin reset only | Missing | **Build P15** | Security if rushed |
| Avatar | Partial | `profile_photo_path` | Schema only | **Upgrade P15** | Storage policy |
| Attendance self-view | No | Admin entry only | Missing | **Build P15** | — |
| Leave / overtime | No | — | Missing | **Build P15+** | Needs schema |
| Staff Home UX | Yes | P11.7 workbench | Operational, technical UI | **Upgrade P11.7.1** | Low |

### Table 2 — Managerial Reference Access Matrix

| Role | Current access | Missing access | Can act? | Audit-safe? | Recommended next action |
|------|----------------|----------------|----------|-------------|-------------------------|
| OWNER | Access mgmt, product home, P8 view, HR via other hubs | Guided P1–P7 workbench on Staff Home | Yes if navigates manually | Partial | Add **optional** operational bridge cards; no impersonation |
| SYSTEM_ADMIN | Same as OWNER on Staff Home | Same | Same | Partial | Same; fix permission preview consistency |
| SERVICE_MANAGER | P3/P5/QC workbench | Reception, parts, finance on workbench | Yes on technical/QC/execution | Partial per jobcard | Add cross-link **cards** (read/nav), not new permissions |

### Table 3 — Employee Profile / HR Self-Service Matrix

| Requirement | Existing file/table | Status | Connected from staff home? | Employee-safe? | Recommended phase |
|-------------|---------------------|--------|----------------------------|----------------|-------------------|
| Profile view (admin) | `erp-employee-profile.php` | Operational | No | No | Connect for OWNER/HR admin only |
| Profile view (self) | — | Missing | No | — | P15 |
| Avatar | `core_staff_profiles.profile_photo_path` | Partial | No | Needs self-scope | P15 |
| Change password | `erp-access-password-reset.php` (admin) | Admin only | No | No | P15 self-service |
| Documents | — | Missing | No | — | P15+ |
| Leave | — | Missing | No | — | P15+ |
| Overtime request | — | Missing | No | — | P15+ |
| Attendance | `erp-attendance-entry.php` | Admin | No | No | P15 self-view |
| Payroll preview | `erp-payroll-preview.php` | Admin preview | No | No | P15 self-view |

### Table 4 — Workbench UX Issue Table

(See section 9.2 — reproduced for gate compliance.)

| Issue | Current example | Risk | Safe to clean in P11.7.1? | Requires deeper change? |
|-------|-----------------|------|---------------------------|-------------------------|
| Filename on card meta | `erp-technical-jobcard-detail.php` | UX | Yes | No |
| role_code in meta | `SERVICE_MANAGER` | UX | Yes | No |
| English KPI keys | `user_id`, `role_code` | UX | Yes | No |
| Broken permission preview for SERVICE_MANAGER | Link on workbench | UX/trust | Yes | Helper only |
| No profile link | Identity card only | Product gap | Partial (link needs target page) | P15 for real profile |

### Table 5 — Final Decision Table

| Area | Existing base? | Next action | Phase suggestion | Build new? |
|------|----------------|-------------|------------------|------------|
| Manager reference access | Partial (product home + weak guards) | Document paths; optional workbench bridge cards | P11.7.1 or P11.8 | **No** (connect/nav) |
| Employee admin profile | Yes (Phase 7) | Link from owner/product/HR admin entry | P11.7.1 connect doc | **No** |
| Employee self-service profile | No | Backlog | **P15** | Yes (later) |
| HR self-service (leave, docs, …) | No | Backlog | **P15+** | Yes (later) |
| Staff Home UX cleanup | Yes (P11.7) | Hide filenames; Persian labels | **P11.7.1** | **No** (polish) |
| Impersonation | No | Do not build | — | **No** |

---

## 14. Risk and Security Notes

1. **Workbench catalog is not authorization.** Showing a card does not grant access; hiding a card does not revoke access. Many operational pages use **session-only** guards.
2. **No impersonation** exists — recommended to keep absent unless a full audit model is designed.
3. **Workflow overrides** (`manager_override_contract_gate`, settlement release) are **not role-scoped** — expanding “manager reference” without tightening guards could increase accidental bypass.
4. **SERVICE_MANAGER permission preview** on workbench points to admin-only page — confusing, not a privilege escalation by itself.
5. **Phase 7 HR** uses separate permission keys (`hr.*`) — connecting to Staff Home must not expose all-employee data to operational roles.
6. **Legacy staff portal** uses old MySQL auth — do not merge without auth review.
7. **No secrets** in this report; no schema or Auth changes performed.

---

## 15. Minimum Next Patch Recommendation

**P11.7.1 — Staff Home UX polish (controlled, no workflow change)**

1. Hide or admin-only display of PHP filenames on workbench cards (`m360-staff-route-meta`, disabled buttons).
2. Replace raw `role_code` in card meta with Persian role labels for display.
3. Persianize or hide technical KPI labels (`user_id`, `role_code`, permission count) for operational roles; optional admin debug mode later.
4. Remove `erp-access-permission-preview.php` from non-admin role workbenches (or replace with read-only message).
5. Optionally add **one** “خانه محصول / نقشه مسیرها” card for SERVICE_MANAGER if owner wants cross-role navigation — **navigation only**, no new permissions.

**Do not in P11.7.1:** impersonation, new HR tables, self-service password, leave module, or permission seed changes.

**Parallel (doc/connect only):** Document that OWNER reaches full operational path via `erp-product-home.php`; HR admin via `erp-hr-dashboard.php`.

---

## 16. Final Persian Answers

**1. آیا دسترسی مرجع مدیر/مالک در پروژه وجود دارد یا فقط لینک‌های محدود دارد؟**  
**وجود دارد اما غیرمتمرکز و ناقص است.** مالک/مدیر سیستم از Staff Home بیشتر به «مدیریت دسترسی» و داشبوردهای نظارتی (P8) می‌رسد؛ مسیر عملیاتی کامل از طریق `erp-product-home.php` و URL مستقیم ممکن است، اما **میز کار مرجع یکپارche** برای P1 تا P7 تعریف نشده است.

**2. آیا مدیر یا مالک الان می‌تواند کارهای نقش‌های زیرمجموعه را از صفر تا صد انجام دهد؟**  
**از نظر فنی اغلب بله** (با session فعال و دانستن مسیر صفحات)، **اما از نظر UX و طراحی محصول خیر** — Staff Home این مسیر را نشان نمی‌دهد و مدیر سالن فقط بخش فنی/QC/اجرا را در workbench دارد. **جایگزینی هویت کارمند (impersonation) وجود ندارد.**

**3. آیا این کار اگر انجام شود نیاز به Permission جدید دارد یا با Role/Route موجود قابل اتصال است؟**  
برای **اتصال و هدایت (navigation)** در اکثر موارد **Permission جدید لازم نیست** — Route و session موجود کافی است. برای **self-service HR** یا **محدودسازی واقعی override** در آینده احتمالاً Permission/audit جدید لازم است. **Impersonation پیشنهاد نمی‌شود.**

**4. آیا پرونده شخصی کارمند قبلاً ساخته شده؟**  
**بله، به‌صورت HR داخلی (ادمین):** `erp-employee-profile.php` و جداول `erp_hr_*`. **پرونده شخصی self-service کارمند ساخته نشده.** نسخه legacy `staff-profile.php` فقط نمونه اولیه جدا از login فعلی است.

**5. اگر ساخته شده، چرا الان در میز کار دیده نمی‌شود؟**  
چون **Phase 7 عمداً admin-only** بود و P11.7 Staff Home فقط **workbench عملیاتی کارگاه** را هدف گرفت؛ HR و legacy profile **به helper/workbench متصل نشده‌اند**. Scope gate هم HR self-service را **P15** گذاشته است.

**6. اگر ساخته نشده، آیا باید الان ساخته شود یا در P15 / HR Self-Service بماند؟**  
**self-service باید در P15 بماند.** برای One-Day Run فقط **اتصال/UX** کافی است؛ ساخت کامل پرونده شخصی، مرخصی، و change password **اکنون out of scope** است.

**7. آیا صفحه Staff Home فعلی باید قبل از One-Day Run از نظر UX پاکسازی شود؟**  
**بله — توصیه می‌شود (P11.7.1).** نمایش `role_code` و نام فایل PHP (`erp-*.php`) برای کاربر نهایی مناسب نیست؛ پاکسازی **UX-only** است و workflow را تغییر نمی‌دهد.

**8. کمترین Patch بعدی چیست؟**  
**P11.7.1 — Staff Home UX polish:** مخفی کردن نام فایل‌ها، برچسب فارسی نقش، حذف لینک شکسته permission preview برای نقش‌های غیرادمین، و (اختیاری) یک کارت راهنما به product home برای مدیر سالن/مالک. **بدون** Auth، Permission seed، schema، یا workflow جدید.

---

P11.7.1-0 applies the owner’s new rule: first discover whether similar capability already exists, then recommend upgrade or controlled build without changing code, schema, Auth/Login, permissions, workflow, or P12 scope.
