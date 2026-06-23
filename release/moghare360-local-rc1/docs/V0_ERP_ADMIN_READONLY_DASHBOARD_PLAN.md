# V0 ERP Admin Read-Only Dashboard Plan
# طرح داشبورد فقط-خواندنی ادمین ERP — نسخه ۰

**Document type:** Implementation planning (dashboard only — no code in this phase)  
**Status:** Proposed — awaiting approval before PHP implementation  
**Future page path:** `public_html/erp-admin-readonly-dashboard.php` (**do not create yet**)

**Prerequisite completed:** `public_html/erp-bootstrap-status.php` — approved locally (`docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_TEST_RESULT.md`)

**Related documents:**
- `docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md`
- `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`
- `docs/V0_SQL_EXECUTION_STATUS.md`
- `docs/PRODUCT_ARCHITECTURE_DECISION.md`
- `docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_TEST_RESULT.md`

---

## Executive summary / خلاصه

After the **bootstrap diagnostic page** passed all checks (C01–C15), the next safe bridge step is a **broader read-only ERP admin dashboard** that summarizes V0 foundation health and access lifecycle state — **without authentication, without writes, and without replacing portal login**.

**Final rule:** **ERP Admin Login must not be built until this read-only dashboard is approved and tested.**

---

## 1) Purpose / هدف

Provide a **read-only ERP Admin Dashboard** that shows:

- MOGHARE360 ERP **Version 0 foundation health**
- **Access lifecycle** summary (org, roles, permissions, approval rules, requests, audit/history)
- **Platform Owner** bootstrap context (`user_id = 10001`)

**Without changing any data** in `moghare360_ERP`.

**Persian:** نمایش وضعیت سالم زیرساخت ERP و خلاصه چرخه عمر دسترسی — فقط مشاهده، بدون ویرایش.

| This page is | This page is not |
|--------------|------------------|
| A local/admin-only summary view | A production admin panel |
| A bridge validation tool | ERP login (`erp-admin-login.php`) |
| SELECT-only reporting | User/role management UI |
| A gate before ERP admin login planning | A replacement for `staff-dashboard.php` |

---

## 2) Why before login replacement / چرا قبل از جایگزینی ورود

| Current state | Implication |
|---------------|-------------|
| **Portal login is legacy** | `staff-auth.php` uses MySQL `staff_users` — not ERP `core_users` |
| **ERP diagnostic page approved locally** | PHP → SQL Server ODBC path works; bootstrap integrity confirmed |
| **Login replacement is high risk** | Auth migration must not precede operational visibility |

**Why a dashboard before login:**

1. **Safer than jumping to ERP admin login** — operators see full V0 picture without sessions, passwords, or write paths.
2. **Validates richer read queries** — counts across org, RBAC, workflow, and audit tables beyond the 15 diagnostic checks.
3. **Reduces confusion** — separates “can we read ERP?” (diagnostic ✓) from “can we operate ERP admin UI?” (dashboard → then login).
4. **Aligns with bridge plan** — `docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md` Option A: diagnostic first, separate ERP path second, staff migration last.

**Sequence:**

```
erp-bootstrap-status.php     ✓ (approved locally)
    → erp-admin-readonly-dashboard.php   ← this plan
    → ERP Admin Login Plan + implementation
    → staff login migration (much later)
```

---

## 3) Dashboard sections / بخش‌های داشبورد

The future page `erp-admin-readonly-dashboard.php` should render these sections in order:

### 3.1 Environment / محیط

| Display item | Source |
|--------------|--------|
| Page title + read-only banner | Static |
| Run timestamp (Asia/Tehran) | PHP |
| PHP version | `PHP_VERSION` |
| ODBC extension status | `extension_loaded('odbc')` |
| Connection method label | `ODBC Trusted_Connection (local)` — no DSN secrets |
| SQL Server instance hint | `.\SQLEXPRESS` (label only) |

### 3.2 Database Health / سلامت دیتابیس

| Display item | Expected (current dev) |
|--------------|------------------------|
| Connection status | OK / FAIL |
| Database name | `moghare360_ERP` |
| Collation | `Persian_100_CI_AS` |
| Server time (optional) | `SYSDATETIME()` |

### 3.3 Core Tables Summary / خلاصه جداول هسته

| Display item | Expected |
|--------------|----------|
| `core_*` table count | `16` |
| Table list (names only) | 16 `core_*` tables from `sys.tables` |

Reference list per `docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_PLAN.md`:

`core_users`, `core_staff_profiles`, `core_departments`, `core_positions`, `core_roles`, `core_permissions`, `core_role_permissions`, `core_access_requests`, `core_access_request_items`, `core_access_approvals`, `core_user_roles`, `core_access_suspensions`, `core_access_restrictions`, `core_access_change_history`, `core_audit_logs`, `core_access_approval_rules`

### 3.4 Platform Owner Summary / خلاصه مالک پلتفرم

Display **non-secret** fields for `user_id = 10001`:

| Field | Expected |
|-------|----------|
| user_id | `10001` |
| username | `mahin.paradigm.owner` |
| full_name | `MahinParadigmCo.` |
| lifecycle_state | `ACTIVE` |
| is_system_owner | `1` |
| is_login_enabled | `1` |
| Assigned roles (active) | `owner`, `system_admin` |
| Bootstrap request | `BOOTSTRAP-10001` (EMERGENCY / APPLIED) |

**Never display:** `password_hash`, email/mobile optional (may show if already in bootstrap status docs — prefer minimal: username + full_name + flags only).

**Architecture note:** Label as **Platform Owner**, not Moghareh Tenant Owner (`docs/PRODUCT_ARCHITECTURE_DECISION.md`).

### 3.5 Roles and Permissions Summary / خلاصه نقش‌ها و مجوزها

| Metric | Expected (foundation) |
|--------|----------------------|
| roles count | `18` |
| permissions count | `43` |
| role_permissions count | `165` |
| active user_role assignments (all users) | `2` (bootstrap only) |
| customer_role_count | `0` |

Optional read-only table: top roles by `role_key` + permission count (no edit links).

### 3.6 Approval Rules Summary / خلاصه قوانین تأیید

| Metric | Expected |
|--------|----------|
| approval_rules count | `16` |

Optional: list `request_type` + required approver roles (read-only, limited rows).

### 3.7 Access Requests Summary / خلاصه درخواست‌های دسترسی

| Metric | Expected (current) |
|--------|-------------------|
| Total requests | `1` |
| Bootstrap request | `BOOTSTRAP-10001` |
| By state | `APPLIED: 1` |
| Pending / draft counts | `0` (expected at bootstrap-only stage) |

No create/approve buttons.

### 3.8 Audit / History Summary / خلاصه ممیزی و تاریخچه

| Metric | Expected (bootstrap user 10001) |
|--------|--------------------------------|
| audit_logs (subject_user_id = 10001) | `>= 1` (expected `1`) |
| access_change_history (user_id = 10001) | `>= 3` (expected `3`) |
| Total audit_logs (all) | informational |
| Total history rows (all) | informational |

Optional: latest 5 audit `action` values — no `details_json` if it may contain sensitive payloads.

### 3.9 Security Warnings / هشدارهای امنیتی

Static panel on every load:

- `LOCAL / ADMIN READ-ONLY — REMOVE OR PROTECT BEFORE DEPLOYMENT`
- No `password_hash` or credentials shown
- No write operations
- Portal staff login unchanged
- Not linked from public pages

### 3.10 Next Actions / اقدامات بعدی

Read-only footer (not interactive workflow):

| Step | Status |
|------|--------|
| Bootstrap diagnostic | ✓ Complete |
| Read-only dashboard | This plan → implement after approval |
| ERP admin login | Blocked until dashboard tested |
| Staff login migration | Blocked until explicit approval |

---

## 4) Required checks / بررسی‌های الزامی

All checks are **SELECT-only**. Expected values reflect current Development/Staging per `docs/V0_SQL_EXECUTION_STATUS.md` and `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`.

| Check ID | Metric | Expected | SQL hint (plan only) |
|----------|--------|----------|----------------------|
| D01 | Database name | `moghare360_ERP` | `SELECT DB_NAME()` |
| D02 | Collation | `Persian_100_CI_AS` | `DATABASEPROPERTYEX(DB_NAME(), 'Collation')` |
| D03 | core table count | `16` | `COUNT(*)` from `sys.tables` WHERE `name LIKE 'core_%'` |
| D04 | departments count | `14` | `SELECT COUNT(*) FROM core_departments` |
| D05 | positions count | `43` | `SELECT COUNT(*) FROM core_positions` |
| D06 | roles count | `18` | `SELECT COUNT(*) FROM core_roles` |
| D07 | permissions count | `43` | `SELECT COUNT(*) FROM core_permissions` |
| D08 | role_permissions count | `165` | `SELECT COUNT(*) FROM core_role_permissions` |
| D09 | approval_rules count | `16` | `SELECT COUNT(*) FROM core_access_approval_rules` |
| D10 | users count | `1` | `SELECT COUNT(*) FROM core_users` |
| D11 | Platform Owner exists | `user_id = 10001`, username `mahin.paradigm.owner` | `SELECT` without `password_hash` |
| D12 | No CUSTOMER role | `customer_role_count = 0` | `core_roles` WHERE `access_level = 'CUSTOMER'` OR `role_key IN ('customer','CUSTOMER')` |
| D13 | Audit count (bootstrap user) | `>= 1` | `core_audit_logs` WHERE `subject_user_id = 10001` |
| D14 | History count (bootstrap user) | `>= 3` | `core_access_change_history` WHERE `user_id = 10001` |
| D15 | Owner + system_admin roles for 10001 | both active | join `core_user_roles` → `core_roles` |

**Overall dashboard health:** OK when D01–D15 pass (connection + counts + bootstrap integrity).

**Difference from `erp-bootstrap-status.php`:** Dashboard adds **org/RBAC/approval/request summaries** and sectioned UI; diagnostic page remains the minimal pass/fail gate.

---

## 5) Read-only rules / قوانین فقط-خواندنی

| Allowed | Forbidden |
|---------|-----------|
| `SELECT` | `INSERT` |
| Read metadata (`DB_NAME()`, `sys.tables`) | `UPDATE` |
| Aggregates (`COUNT`, `GROUP BY`) | `DELETE` |
| Display non-secret columns | `CREATE` |
| | `ALTER` |
| | `DROP` |
| | `EXEC` on mutating procedures |
| | HTML forms with POST that write to ERP |
| | Links to “add user”, “assign role”, “approve request” |

**Persian:** این داشبورد فقط گزارش می‌دهد؛ هیچ دکمه یا مسیر ذخیره‌سازی در ERP ندارد.

---

## 6) Security rules / قوانین امنیتی

| Rule | Requirement |
|------|-------------|
| **No `password_hash` display** | Omit from all queries and HTML |
| **No config secrets display** | No connection strings, usernames, passwords, or server credentials in output |
| **Local-only / protected** | Run on `localhost` or IP-restricted staging; not public internet |
| **Not linked publicly** | No links from `index.php`, `staff-login.php`, `customer-login.php`, or navigation menus |
| **No login replacement** | Do not modify `staff-auth.php`, `staff-login.php`, `access-control.php` |
| **No session requirement in V0** | Dashboard is **unauthenticated read-only** on local dev only — protection is network/file access, not ERP login |
| **Robots** | `noindex, nofollow` meta |
| **Error handling** | Generic connection failure message in browser; log details server-side only |
| **No user/role creation** | No PHP paths that INSERT into `core_users` or `core_user_roles` |

**Deployment rule:** Remove file or block at web server before any public hosting.

---

## 7) Recommended implementation / پیاده‌سازی پیشنهادی

| Topic | Recommendation |
|-------|----------------|
| **Connection** | PHP **ODBC** (same as approved diagnostic) |
| **Local auth** | **`Trusted_Connection=yes`** on Windows dev (`.\SQLEXPRESS`) |
| **Driver order** | ODBC Driver 17 first; fallback Driver 18 with `TrustServerCertificate=yes` |
| **Credentials in GitHub** | **Never** — connection strings stay in page-local constants for dev only, or future gitignored `config.erp.php` |
| **Portal login** | **Keep unchanged** — `getPdo()` / MySQL path untouched |
| **Code structure** | Self-contained PHP file (like `erp-bootstrap-status.php`) or shared read-only ODBC helper — **no** `config.php` dependency required for V0 local |
| **UI** | Simple HTML, RTL-friendly, Persian labels, inline minimal CSS — no external CSS/JS |
| **Reuse** | May share ODBC connect pattern from `erp-bootstrap-status.php` but **do not modify** that file unless separately approved |

**Not for production yet:** ODBC is a **local bridge** per `docs/V0_ODBC_CONNECTION_TEST_STATUS.md`; production may use `sqlsrv` / API later.

---

## 8) Risks / ریسک‌ها

| ID | Risk | Impact | Mitigation |
|----|------|--------|------------|
| R1 | **Public exposure** | Schema and governance enumeration | Local-only, IP restrict, remove before deploy |
| R2 | **Confusing diagnostic vs dashboard vs real admin** | Operators expect write actions | Clear titles; “Read-Only Dashboard”; no action buttons |
| R3 | **Relying on ODBC for production** | Wrong long-term architecture | Document as dev bridge; plan `sqlsrv`/API separately |
| R4 | **Accidentally adding write actions too early** | Bypass access workflow | Code review: SELECT only; no POST handlers |
| R5 | **Displaying PII unnecessarily** | Privacy leak | Show minimal owner fields; no password_hash |
| R6 | **Duplicating staff-dashboard.php** | Wrong mental model | ERP dashboard is platform layer; staff dashboard stays legacy |
| R7 | **Skipping dashboard and building login first** | Auth without operational visibility | Enforce final rule below |

---

## 9) Success criteria / معیارهای موفقیت

The read-only dashboard implementation is **successful** when:

| # | Criterion |
|---|-----------|
| S1 | Dashboard loads locally via browser (`localhost`) without errors |
| S2 | All summaries show **correct counts** matching D01–D15 expected values |
| S3 | Platform Owner section shows `user_id = 10001` and roles `owner`, `system_admin` |
| S4 | `customer_role_count = 0` confirmed |
| S5 | **No write actions** exist (no forms, no mutating SQL) |
| S6 | **`password_hash` and config secrets** do not appear in output |
| S7 | **Current portal login behavior unchanged** — no edits to staff auth files |
| S8 | Product owner approves dashboard test result document (future: `V0_ERP_ADMIN_READONLY_DASHBOARD_TEST_RESULT.md`) |

**Exit gate for ERP Admin Login Plan:** Dashboard approved and tested locally — **then** plan/implement `erp-admin-login.php`.

---

## 10) Final rule / قانون نهایی

> **ERP Admin Login must not be built until this read-only dashboard is approved and tested.**  
> **تا زمانی که این داشبورد فقط-خواندنی تأیید و تست نشود، ورود ادمین ERP (`erp-admin-login.php`) ساخته نشود.**

This means:

- Do **not** create `erp-admin-login.php`, `erp-auth.php`, or session-based ERP login yet.
- Do **not** replace `staff-auth.php` or migrate portal users.
- Do **not** create users or assign roles from PHP.
- **Do** implement and test `erp-admin-readonly-dashboard.php` after this plan is approved.
- **Do** record test results in a follow-up status document before ERP login planning.

**Approved sequence:**

```
1. erp-bootstrap-status.php          ✓ tested
2. erp-admin-readonly-dashboard.php  ← implement after this plan approval
3. ERP Admin Login Plan              ← after dashboard test
4. erp-admin-login.php               ← after login plan approval
5. staff login migration             ← explicit approval only
```

---

## Appendix A — Expected count reference (current dev)

From `docs/V0_SQL_EXECUTION_STATUS.md` + bootstrap:

| Metric | Value |
|--------|-------|
| core_table_count | 16 |
| department_count | 14 |
| position_count | 43 |
| role_count | 18 |
| permission_count | 43 |
| role_permission_count | 165 |
| approval_rule_count | 16 |
| user_count | 1 |
| customer_role_count | 0 |
| bootstrap audit (user 10001) | 1 |
| bootstrap history (user 10001) | 3 |

---

## Appendix B — Relationship to existing pages

| Page | Role |
|------|------|
| `erp-bootstrap-status.php` | Minimal pass/fail diagnostic (C01–C15) |
| `erp-admin-readonly-dashboard.php` | Richer read-only summary (this plan) |
| `staff-dashboard.php` | Legacy portal — unchanged |
| `erp-admin-login.php` | Future — blocked until dashboard tested |

---

*End of V0 ERP Admin Read-Only Dashboard Plan.*
