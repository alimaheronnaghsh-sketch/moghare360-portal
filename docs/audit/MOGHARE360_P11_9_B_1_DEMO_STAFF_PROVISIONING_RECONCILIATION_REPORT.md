# MOGHARE360 P11.9-B-1 — Demo Staff Provisioning Method Reconciliation Report

**Phase:** P11.9-B-1  
**Mode:** REPORT ONLY  
**Product:** MOGHARE360 V1 RC  
**Date:** 2026-06-26  
**Trigger:** Owner browser observation — Unit Access Console vs P11.9-A Access Management guidance; PARTS vs INVENTORY role_code mismatch

---

## 1. Executive Summary

Inspection confirms **two documented provisioning paths** coexist in V1 RC:

| Path | Status for dry run |
|------|-------------------|
| **Access Management UI** (`erp-access-management.php` → `erp-access-user-create.php`) | **Primary — correct for P11.9-B-A demo staff** |
| **Private JSON import** (`private/production-users.json` + PowerShell script) | **Bootstrap/fallback — production deployment; not aligned with full 6-role dry run without customization** |

**`erp-v1-unit-access-console.php` is a read-only route console.** It does not create users. Its banner correctly states «بدون ساخت کاربر · بدون دریافت رمز» and points operators to the **production JSON import** path documented in V1 release materials — not to the P11.4 Access Management UI.

**P11.9-A dry run pack guidance is substantively correct** for method (Access Management UI) and dry-run role vocabulary (`PARTS`, `SERVICE_MANAGER`). The **blocker is operational confusion**, not missing application capability: an operator who follows Unit Access Console alone will not find user creation and may assign wrong `role_code` values if using JSON import unchanged.

**Reconciliation decision:**

- Create demo staff via **`erp-access-user-create.php`** as owner/admin.
- Use UI role codes: `RECEPTION`, `SERVICE_MANAGER`, `TECHNICIAN`, **`PARTS`**, `FINANCE`, `QC`.
- Do **not** use Unit Access Console for provisioning.
- JSON import is optional fallback only; template lacks `SERVICE_MANAGER` and uses `INVENTORY` instead of `PARTS`.

**No code, SQL, Auth, permission, role seed, or OTP change is required** to proceed. A **docs-only clarification** in dry-run pack / B-0 plan is recommended before B-A continues.

---

## 2. Owner Observation

From browser validation of `erp-v1-unit-access-console.php`:

- Page banner: **«Unit Access Check Console — مسیرهای امن فقط · بدون ساخت کاربر · بدون دریافت رمز»**
- Body text: real users are created only with **`private/production-users.json`** on server + import script.
- INVENTORY unit row shows `suggested_role_code` = **`INVENTORY`**, not `PARTS`.
- No row for **SERVICE_MANAGER** in the unit console table.
- P11.9-A pack directs provisioning through **`erp-access-management.php`**.

**Interpretation:** Owner opened the **V1 Master Console route reference**, not the **P11.4 Access Management product UI**. These are different pages with different purposes. The apparent contradiction is a **documentation/routing confusion**, not proof that Access Management lacks user creation.

---

## 3. Provisioning Method Discovery

### 3.1 Access Management UI (P11.4)

**`erp-access-management.php`** (verified):

- Requires admin via `m360_access_mgmt_require_admin()`.
- Displays banner: **«مدیریت دسترسی از این UI — JSON import فقط bootstrap/fallback است.»**
- Links **«+ ایجاد پرسنل»** → `erp-access-user-create.php`.
- Lists staff, readiness KPIs, edit/role/password/history actions.

**`erp-access-user-create.php`** (verified):

- POST handler calls `m360_access_user_create()`.
- Form fields: username, display name, department, position, **`role_code`**, **temporary password**, lifecycle, login enabled.
- Creates `core_users`, staff profile, `erp_company_users`, `core_user_roles`, audit/history — **no Auth/Login file changes**.

**Supporting docs:** `docs/access/MOGHARE360_V1_OWNER_ACCESS_MANAGEMENT_RUNBOOK.md` — UI is primary; JSON import is fallback.

### 3.2 Access Request Admin (Mission 13)

**`erp-access-request-admin.php`** (verified):

- Header comment: **SELECT only. No form. No POST. No workflow write.**
- Read-only admin view of access **requests** — does **not** create users.

### 3.3 Unit Access Console (V1 Master Console)

**`erp-v1-unit-access-console.php`** (verified):

- Static route matrix via `v1mc_access_units()`.
- Each unit’s `user_creation_route` points to **`private/production-users.json`** (role-specific) or template + PowerShell script.
- **No forms, no POST, no DB writes** — documentation/routing only.

### 3.4 Production JSON import path

**`private/templates/production-users.template.json`** (verified — template only, no secrets):

- `allowed_role_codes`: OWNER, SYSTEM_ADMIN, RECEPTION, TECHNICIAN, **`INVENTORY`**, FINANCE, QC, CRM, COMPANY_OWNER_VIEWER.
- **No `SERVICE_MANAGER`.** **No `PARTS`.**
- Placeholder usernames: `inventory.placeholder` with `role_code: INVENTORY`.

**`private/production-users.json`:** Path referenced in code/docs; **not present in repository** (expected gitignored runtime file). Existence on operator machine: **operator must verify locally** — contents not inspected (may contain secrets).

**`tools/production/CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1`** (verified):

- Reads **`private/production-users.json` only** (never template as import source).
- Maps `INVENTORY` → `inventory_staff` core role key.
- Idempotent upsert to `core_users` + `erp_company_users`.
- Hashes passwords via PHP; never logs plain passwords.
- **Required only for JSON import path**, not for Access Management UI.

### 3.5 Role vocabulary split

| Layer | PARTS / inventory | Service manager |
|-------|-------------------|-----------------|
| Access Mgmt UI (`M360_ACCESS_MGMT_ROLE_CODE_MAP`) | UI code **`PARTS`** → `erp_company_users.role_code = PARTS` → `inventory_staff` | **`SERVICE_MANAGER`** → `operations_manager` |
| Production JSON / Unit Console | **`INVENTORY`** → `inventory_staff` | **Not in template** |
| Staff Home (`m360_staff_home_resolve_role_code`) | Accepts **`PARTS`** in `erp_company_users`; maps `inventory_staff` → PARTS via `core_user_roles` fallback | Accepts **`SERVICE_MANAGER`**; maps `operations_manager` → SERVICE_MANAGER |
| Preflight SQL (`P11_9_A_READONLY_PREFLIGHT_CHECK.sql`) | Counts **`PARTS`** in `erp_company_users` | Counts **`SERVICE_MANAGER`** |

**Implication:** Users created via UI with `PARTS` satisfy preflight and Staff Home directly. Users created via JSON with `INVENTORY` may still get Staff Home via `core_user_roles` fallback but **preflight PARTS count may show WARNING** unless operator interprets INVENTORY equivalently or preflight doc is updated (docs/SQL note only — out of scope for B-1 code change).

---

## 4. Provisioning Method Matrix

| Method | File / page / script | Creates user? | Creates password? | Assigns role? | Safe for dry run? | Requires secret handling? | Decision |
|--------|----------------------|---------------|-------------------|---------------|-------------------|---------------------------|----------|
| Access Management UI | `erp-access-management.php` → `erp-access-user-create.php` | **Yes** | **Yes** (temporary, form entry; not stored in repo) | **Yes** (`role_code` + `core_user_roles` + `erp_company_users`) | **Yes — primary** | Yes — operator sets password outside repo | **USE for P11.9-B-A** |
| Access Management edit/role | `erp-access-user-edit.php`, `erp-access-role-assign.php` | No (existing) | Reset via `erp-access-password-reset.php` | Yes | Yes | Yes | Support only |
| Access Request Admin | `erp-access-request-admin.php` | **No** | **No** | **No** (read-only requests) | N/A | No | **Do not use for creation** |
| Unit Access Console | `erp-v1-unit-access-console.php` | **No** | **No** | **No** | N/A (reference only) | No | **Do not use for creation** |
| Production JSON import | `private/production-users.json` + `CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1` | **Yes** | **Yes** (private file) | **Yes** (`INVENTORY` not `PARTS`; no `SERVICE_MANAGER` in template) | **Partial** — missing SM; role_code mismatch vs preflight | **Yes** — private JSON never committed | **Fallback only**; customize JSON if used |
| Raw SQL user insert | N/A | Would | Would | Would | **No** | Yes | **Forbidden** unless future explicit approval |
| staff-login / owner-login | Login pages only | No | No | No | N/A | No | Login test only after create |

---

## 5. Role Code Reconciliation Matrix

| Intended dry run role | P11.9-A documented role_code | Actual / reference role_code found | Username | Landing page | Status | Decision |
|----------------------|------------------------------|-----------------------------------|----------|--------------|--------|----------|
| OWNER | *(existing)* | OWNER (UI + JSON) | existing owner/admin | Product Home / oversight | Pre-existing | Use existing owner |
| RECEPTION | RECEPTION | RECEPTION (UI + JSON + Staff Home) | `demo.reception` | `erp-staff-home.php` | Aligned | Create via UI |
| SERVICE_MANAGER | SERVICE_MANAGER | **UI: SERVICE_MANAGER**; JSON template: **absent**; Unit Console: **absent** | `demo.service.manager` | `erp-staff-home.php` | **Gap in JSON/console only** | **UI role `SERVICE_MANAGER` required** |
| TECHNICIAN | TECHNICIAN | TECHNICIAN (UI + JSON) | `demo.technician` | `erp-staff-home.php` | Aligned | Create via UI |
| PARTS / inventory | PARTS | **UI: PARTS**; JSON/Console: **INVENTORY**; Staff Home accepts PARTS; preflight counts PARTS | `demo.parts` | `erp-staff-home.php` | **Naming split** | **Use UI `PARTS`**, not JSON `INVENTORY` |
| FINANCE | FINANCE | FINANCE (UI + JSON) | `demo.finance` | `erp-staff-home.php` | Aligned | Create via UI |
| QC | QC | QC (UI + JSON) | `demo.qc` | `erp-staff-home.php` | Aligned | Create via UI |

**Core role_key mapping (Access Management UI — authoritative for dry run):**

| UI role_code | core_roles.role_key | erp_company_users.role_code |
|--------------|---------------------|----------------------------|
| SERVICE_MANAGER | operations_manager | SERVICE_MANAGER |
| PARTS | inventory_staff | PARTS |

---

## 6. User Creation Risk Matrix

| Risk | Evidence | Severity | Dry-run impact | Stop condition | Recommended action |
|------|----------|----------|----------------|----------------|------------------|
| Operator uses Unit Access Console expecting user creation | Console banner «بدون ساخت کاربر»; no create form | **High** | No users created; preflight BLOCKED | Zero demo staff after attempted provisioning | Route operator to **`erp-access-management.php`** |
| JSON import without SERVICE_MANAGER row | Template has no SERVICE_MANAGER | **High** | Dry run missing coordination role (P11.9-1 minimum) | Preflight WARNING on SERVICE_MANAGER | Add SM via **UI** or custom JSON row (not in template) |
| JSON import uses INVENTORY; preflight expects PARTS | Preflight SQL counts `role_code = PARTS` | **Medium** | False WARNING; Staff Home may still work via core_user_roles | Misread as missing PARTS role | Prefer UI **`PARTS`**; or document INVENTORY equivalence |
| Password in repo/docs/screenshots | P11.9-A rules; runbook | **High** | Security breach | Secret committed | Manual password only; no screenshots of password fields |
| Owner shared login for all roles | Readiness WARNING when no staff logins | **Medium** | Invalid dry run (not real role separation) | All work under owner session | Create dedicated demo staff logins |
| Conflicting primary path docs | Unit Console vs Access Mgmt runbook | **Medium** | Operator paralysis / wrong path | Preflight stalled | **Docs-only clarification** (B-FIX-A docs) |
| Raw SQL user creation | P11.9-A forbidden unless approved | **High** | Audit/security violation | Unauthorized DB change | Use UI only |
| Access Request Admin mistaken for provisioning | SELECT-only UI | **Low** | No users created | Same as console confusion | Do not use for create |

---

## 7. Required Correction Matrix

| Document / file | Issue | Required correction | Code change needed? | SQL change needed? | Priority |
|-----------------|-------|---------------------|---------------------|-------------------|----------|
| `docs/dry-run/P11_9_A_ROLE_PROVISIONING_CHECKLIST.md` | Does not warn against Unit Access Console | Add note: use **`erp-access-management.php`**, not `erp-v1-unit-access-console.php` | No | No | **P1** |
| `docs/dry-run/P11_9_A_ONE_DAY_RUN_DRY_RUN_PACK.md` | Same routing ambiguity | Cross-link Access Management as sole UI path; JSON as fallback | No | No | P1 |
| `docs/audit/MOGHARE360_P11_9_B_0_DRY_RUN_PREFLIGHT_EXECUTION_PLAN.md` | Phase 4 says Access Management — good; no console warning | Add explicit «do not use Unit Access Console for create» | No | No | P1 |
| `database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql` | Counts PARTS not INVENTORY | Optional footnote in dry-run docs: JSON `INVENTORY` users may need manual PASS note | No | Optional docs note only | P2 |
| `erp-v1-unit-access-console.php` | Points only to JSON path; omits Access Management UI | **Out of scope for B-1** — document discrepancy; optional future console row for P11.4 UI | Future optional | No | P3 backlog |
| `private/templates/production-users.template.json` | No SERVICE_MANAGER; INVENTORY not PARTS | Document for production bootstrap; dry run should not rely on unmodified template | No | No | P2 doc note |

**No application code fix is required** to unblock demo staff creation today.

---

## 8. Dry Run Impact

| Area | Impact |
|------|--------|
| P11.9-B-A preflight Phase 4 | **Unblocked** once operator uses Access Management UI with reconciled role codes |
| P11.9-1 minimum 6 roles + OWNER | Achievable via UI including **SERVICE_MANAGER** and **PARTS** |
| Staff Home | Works with UI-created users (`erp_company_users.role_code` = PARTS / SERVICE_MANAGER) |
| Read-only preflight SQL | Aligns with UI path; may WARN if JSON INVENTORY used instead of PARTS |
| 115-step dry run | No change to execution model; still requires real per-role logins |
| Unit Access Console | Remains valid as **route/security reference** — not provisioning tool |

**Blocker classification:** **Process/documentation blocker**, not application capability blocker. Operator can create users **now** via Access Management without waiting for code changes.

---

## 9. Final Persian Answers

**1. آیا الان مجازیم کاربران دمو را بسازیم؟**  
بله — **با UI مدیریت دسترسی** (`erp-access-management.php` → `erp-access-user-create.php`) و رمز دستی خارج از repo. P11.9-B-1 خودش کاربر نمی‌سازد؛ ولی مسیر امن مشخص است.

**2. مسیر صحیح ساخت کاربر دمو چیست؟**  
ورود owner/admin → **`erp-access-management.php`** → **ایجاد پرسنل** → انتخاب `role_code` صحیح → رمز موقت → تست login از `staff-login.php` → تأیید Staff Home.

**3. آیا Access Management واقعاً user creation دارد؟**  
**بله.** صفحه `erp-access-user-create.php` با POST واقعی کاربر را در `core_users` و نقش/شرکت می‌سازد. `erp-access-management.php` hub لیست + لینک ایجاد است.

**4. آیا باید از private/production-users.json استفاده شود؟**  
**نه به‌عنوان مسیر اصلی dry run.** فقط bootstrap/fallback تولید. برای ۶ نقش dry run (به‌ویژه SERVICE_MANAGER) **UI اولویت دارد**.

**5. نقش قطعات PARTS است یا INVENTORY؟**  
برای dry run و UI: **`PARTS`**. **`INVENTORY`** نام legacy در JSON import و Unit Console برای همان `inventory_staff` است.

**6. نقش SERVICE_MANAGER قطعی است یا نیاز به mapping دارد؟**  
**قطعی در Access Management UI** — `SERVICE_MANAGER` → `operations_manager`. در JSON template **وجود ندارد**؛ برای dry run باید از UI ساخته شود.

**7. قبل از ادامه P11.9-B-A چه چیزی باید اصلاح شود؟**  
**توضیح مستندات** (نه کد): Unit Access Console ≠ محل ساخت کاربر؛ JSON fallback محدودیت دارد؛ role_codeهای UI را استفاده کنید. سپس Phase 4 preflight را با UI اجرا کنید.

**8. آیا این موضوع blocker است؟**  
**Blocker عملیاتی/مستنداتی** اگر اپراتور Console را دنبال کند — **نه blocker فنی** اگر Access Management استفاده شود.

**9. آیا نیاز به DB/Auth/Permission/Role change داریم؟**  
**خیر** برای ادامه preflight. نقش‌ها و UI از P11.4 موجود است.

**10. فاز بعدی باید B-A ادامه پیدا کند یا B-FIX-A؟**  
**B-FIX-A docs-only** (اصلاح یادداشت‌های pack/B-0) **سپس B-A ادامه** — نه B-FIX-A کد.

---

## 10. Recommended Next Step

### Answers to Section B questions (English)

| # | Answer |
|---|--------|
| 1 | **Primary:** Access Management UI. **Fallback:** private JSON + PowerShell import. |
| 2 | **`erp-access-management.php`** orchestrates; **`erp-access-user-create.php`** creates users. |
| 3 | **`erp-access-request-admin.php`** — read-only; **does not** create users. |
| 4 | JSON import is **intended production bootstrap path**, not primary dry-run path. |
| 5 | PowerShell script **required only for JSON import**, not for UI path. |
| 6 | **Yes** — UI uses existing Auth; no Auth file edits. |
| 7 | **Yes** — UI path avoids raw SQL. |
| 8 | UI codes: RECEPTION, SERVICE_MANAGER, TECHNICIAN, **PARTS**, FINANCE, QC (+ OWNER). JSON adds INVENTORY, CRM, etc. without SERVICE_MANAGER. |
| 9 | Dry run / UI / Staff Home / preflight: **`PARTS`**. Production JSON / Unit Console: **`INVENTORY`** (same core role, different stored code). |
| 10 | **`SERVICE_MANAGER` is real** in Access Management UI (`operations_manager`). Not in production JSON template. |
| 11 | Usernames unchanged: `demo.reception`, `demo.service.manager`, `demo.technician`, `demo.parts`, `demo.finance`, `demo.qc`. |
| 12 | **P11.9-B-A Phase 4:** provision via Access Management UI; login test each; record in preflight user report. |
| 13 | **Yes — docs-only correction** recommended (console vs access mgmt; PARTS vs INVENTORY note). |
| 14 | **No code fix required.** |
| 15 | **Conditional blocker** — resolved by using correct UI; not a hard platform blocker. |

### Immediate operator sequence

1. Owner login → open **`http://localhost:8080/moghare360/erp-access-management.php`** (not Unit Access Console).
2. For each demo user, **`erp-access-user-create.php`**: set username, role_code per matrix §5, temporary password (not logged).
3. Login each user at **`staff-login.php`** → confirm **`erp-staff-home.php`**.
4. Re-run read-only preflight SQL → expect PARTS/SERVICE_MANAGER counts if UI used.
5. Continue P11.9-B-A phases 5–10 per B-0 plan.
6. Optional follow-up phase: **P11.9-B-FIX-A (docs-only)** to patch dry-run pack routing notes.

---

P11.9-B-1 reconciles the safe demo staff provisioning method and role_code mapping before P11.9-B-A continues, without creating users, passwords, demo JobCards, SQL changes, Auth/Login changes, permission changes, role changes, workflow actions, OTP changes, secrets exposure, or P12 scope.
