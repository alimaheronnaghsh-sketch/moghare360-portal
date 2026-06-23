# V0 ERP Bootstrap Status Page Plan
# طرح صفحه تشخیصی وضعیت Bootstrap — نسخه ۰

**Document type:** Implementation planning (diagnostic page only — no code in this phase)  
**Status:** Proposed — awaiting approval before PHP implementation  
**Future page path:** `public_html/erp-bootstrap-status.php` (**do not create yet**)

**Parent plan:** `docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md` (Phase 1)

**Related documents:**
- `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`
- `docs/V0_SQL_EXECUTION_STATUS.md`
- `docs/PRODUCT_ARCHITECTURE_DECISION.md`

---

## 1) Purpose / هدف

A **read-only diagnostic page** to confirm that the new **SQL Server ERP foundation** (`moghare360_ERP`) is **reachable and healthy** from the PHP application layer.

**صفحه تشخیصی فقط-خواندنی** برای تأیید اینکه زیرساخت ERP روی SQL Server از لایه PHP قابل دسترسی است و وضعیت Bootstrap پلتفرم سالم است.

This page is **not** a login page, **not** an admin console, and **not** a migration tool. It only **reports** the result of predefined `SELECT` checks.

---

## 2) Why this page is needed / چرا این صفحه لازم است

| Fact | Implication |
|------|-------------|
| Current portal login still uses **legacy MySQL** `staff_users` | Staff workflow today does **not** prove ERP connectivity. |
| ERP core access exists in **SQL Server** `moghare360_ERP` | A separate bridge test is required before any auth change. |
| V0 foundation + Platform Owner bootstrap are **executed in SSMS** | Application layer has **never** read `core_users` yet. |
| Login replacement is **high risk** (`docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md`) | We need a **safe, read-only** test before `erp-admin-login.php` or `staff-auth.php` changes. |

**Persian summary:**  
تا وقتی پرتال فعلی روی MySQL کار می‌کند، نمی‌توانیم فرض کنیم PHP به SQL Server وصل می‌شود. این صفحه اولین پل امن بین اپلیکیشن و ERP است — **بدون تغییر ورود**.

---

## 3) Page must check / بررسی‌های الزامی صفحه

All checks are **SELECT-only**. Expected values reflect current Development/Staging state per execution status documents.

### 3.1 Connection and database metadata

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C01 | SQL Server connection to `moghare360_ERP` | **OK** | Open dedicated ERP connection (not portal `getPdo()`). |
| C02 | Database name | `moghare360_ERP` | `SELECT DB_NAME()` |
| C03 | Collation | `Persian_100_CI_AS` | `SELECT DATABASEPROPERTYEX(DB_NAME(), 'Collation')` or `sys.databases` |

### 3.2 Core schema health

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C04 | `core_*` table count | `16` | `SELECT COUNT(*) FROM sys.tables WHERE name LIKE 'core_%'` |

Reference tables (informational display only — not every row validated individually in V0):

`core_users`, `core_staff_profiles`, `core_departments`, `core_positions`, `core_roles`, `core_permissions`, `core_role_permissions`, `core_access_requests`, `core_access_request_items`, `core_access_approvals`, `core_user_roles`, `core_access_suspensions`, `core_access_restrictions`, `core_access_change_history`, `core_audit_logs`, `core_access_approval_rules`

### 3.3 Bootstrap Platform Owner

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C05 | `user_id = 10001` exists in `core_users` | **1 row** | `SELECT ... WHERE user_id = 10001` — **exclude `password_hash` from SELECT list** |
| C06 | Username | `mahin.paradigm.owner` | Same row — `username` column match |
| C07 | Lifecycle / login flags (display only) | `lifecycle_state = ACTIVE`, `is_login_enabled = 1`, `is_system_owner = 1` | Non-secret columns only |

**Display allowed fields:** `user_id`, `username`, `full_name`, `email`, `mobile`, `lifecycle_state`, `is_system_owner`, `is_login_enabled`  
**Never display:** `password_hash`

### 3.4 Assigned roles for user 10001

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C08 | Role `owner` assigned (active) | **Yes** | Join `core_user_roles` → `core_roles` where `role_code = 'owner'` and `revoked_at IS NULL` |
| C09 | Role `system_admin` assigned (active) | **Yes** | Same for `role_code = 'system_admin'` |
| C10 | Active assigned role count for user 10001 | `2` | Count active `core_user_roles` rows |

### 3.5 Bootstrap synthetic request

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C11 | Request `BOOTSTRAP-10001` exists | **1 row** | `core_access_requests` WHERE `request_number = 'BOOTSTRAP-10001'` |
| C12 | Request metadata | `request_type = EMERGENCY`, `request_state = APPLIED`, `migration_source = BOOTSTRAP`, `is_emergency = 1`, `subject_user_id = 10001` | Display non-sensitive fields |

### 3.6 Audit and history counts

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C13 | Bootstrap-related audit log count | `>= 1` (expected `1`) | Filter `core_audit_logs` by bootstrap action code if present (e.g. `BOOTSTRAP_PLATFORM_OWNER_APPLIED`) |
| C14 | Bootstrap-related history count | `>= 3` (expected `3`) | `core_access_change_history` rows tied to bootstrap user/request |
| C15 | Total user count | `1` | `SELECT COUNT(*) FROM core_users` |
| C16 | Total active user-role assignments (all users) | `2` | Active rows in `core_user_roles` |

### 3.7 Customer access guard

| Check ID | What to verify | Expected (current) | SQL hint (plan only) |
|----------|----------------|--------------------|----------------------|
| C17 | No `CUSTOMER` role in `core_roles` | `customer_role_count = 0` | `SELECT COUNT(*) FROM core_roles WHERE role_code = 'CUSTOMER'` (or equivalent seed naming) |

Per `docs/V0_SQL_EXECUTION_STATUS.md` and architecture decision: **customer access is out of V0**.

---

## 4) Page must be read-only / صفحه باید فقط-خواندنی باشد

The future `erp-bootstrap-status.php` and any included helpers for this page must enforce:

| Allowed | Forbidden |
|---------|-----------|
| `SELECT` | `INSERT` |
| Read metadata queries (`DB_NAME()`, `sys.tables`, etc.) | `UPDATE` |
| Display aggregated counts | `DELETE` |
| | `CREATE` |
| | `ALTER` |
| | `DROP` |
| | `EXEC` on mutating procedures |
| | Any POST handler that writes to ERP |

**Implementation note (plan only):** Use a read-only SQL login if possible; at minimum, code review must confirm **zero write statements**.

**Persian:** این صفحه فقط گزارش می‌دهد؛ هیچ تغییری در دیتابیس ERP ایجاد نمی‌کند.

---

## 5) Security restrictions / محدودیت‌های امنیتی

| Rule | Detail |
|------|--------|
| **Do not show `password_hash`** | Omit column from all queries and UI. |
| **Do not show sensitive config** | No connection strings, usernames, passwords, or server IPs in HTML output. |
| **Do not expose SQL errors publicly in production** | Log details server-side; show generic “connection failed” to browser in prod. |
| **Must be protected or removed before public deployment** | IP allowlist, HTTP basic auth, VPN-only, or delete file after validation. |
| **Must not be linked from public pages** | No links from `index.php`, `staff-login.php`, `customer-login.php`, or sitemap. |
| **No search engine indexing** | `noindex` meta or robots disallow if temporarily on a reachable host. |
| **Separate from portal session** | Page must **not** require or modify `$_SESSION['staff_user']`. |
| **No credential echo on failure** | Connection errors must not print DSN or password fragments. |

### Suggested access modes (choose one at implementation)

| Mode | Use case |
|------|----------|
| **Local-only** | `localhost` / dev machine — default for first test |
| **IP allowlist** | Office or VPN IP only |
| **Shared secret query key** | Temporary `?key=` — weak alone; combine with IP restriction |
| **HTTP Basic Auth** | Extra gate on staging |

**Production rule:** Remove the file or block at web server level when diagnostics are complete.

---

## 6) Required future config decision / تصمیم پیکربندی آینده

SQL Server connection settings **must not be hardcoded** in `erp-bootstrap-status.php`.

### Proposed config strategy (later — not in GitHub with secrets)

| Approach | Description |
|----------|-------------|
| **Separate ERP config file** | e.g. `config.erp.php` (server-local, gitignored) loaded only by ERP bridge pages |
| **Example template in repo** | e.g. `config.erp.example.php` with placeholders only — **no real credentials** |
| **Distinct from portal MySQL** | Portal keeps `config.php` + `getPdo()`; ERP uses `getErpSqlConnection()` or equivalent |
| **Environment variables** | Optional on dev: `ERP_DB_HOST`, `ERP_DB_NAME`, `ERP_DB_USER`, `ERP_DB_PASS` |

### Planned settings (placeholders only)

```
ERP_DB_HOST     → e.g. localhost\SQLEXPRESS (dev) — not committed
ERP_DB_NAME     → moghare360_ERP
ERP_DB_USER     → dedicated read-only or read-write login (diagnostic needs SELECT only)
ERP_DB_PASS     → never in GitHub
```

**Persian:** تنظیمات SQL Server جدا از MySQL پرتال باشد و هرگز در GitHub commit نشود.

---

## 7) Proposed output sections for the future page / بخش‌های خروجی پیشنهادی

The HTML/report layout for `erp-bootstrap-status.php` should use these sections in order:

### 7.1 Environment / محیط

- Page title: ERP Bootstrap Status (read-only)
- Run timestamp (Asia/Tehran)
- PHP version
- `sqlsrv` / `pdo_sqlsrv` extension loaded: **Yes / No**
- Deployment hint: `local` | `staging` | `unknown` (non-secret label only)

### 7.2 Database Status / وضعیت دیتابیس

| Row | Value |
|-----|-------|
| Connection | OK / FAIL |
| Database name | `moghare360_ERP` |
| Collation | `Persian_100_CI_AS` (expected) |
| Server time (optional) | from `SELECT SYSDATETIME()` |

### 7.3 Core Tables / جداول هسته

| Row | Value |
|-----|-------|
| `core_*` table count | `16` expected |
| Status | OK if count matches expected V0 foundation |

### 7.4 Bootstrap Owner / مالک Bootstrap

| Field | Expected |
|-------|----------|
| user_id | `10001` |
| username | `mahin.paradigm.owner` |
| full_name | `MahinParadigmCo.` |
| lifecycle_state | `ACTIVE` |
| is_system_owner | `1` |
| is_login_enabled | `1` |
| Row found | OK / FAIL |

### 7.5 Assigned Roles / نقش‌های اختصاص‌یافته

| Role code | Status |
|-----------|--------|
| `owner` | OK / MISSING |
| `system_admin` | OK / MISSING |
| Active role count | `2` expected |

### 7.6 Bootstrap Request / درخواست Bootstrap

| Field | Expected |
|-------|----------|
| request_number | `BOOTSTRAP-10001` |
| request_type | `EMERGENCY` |
| request_state | `APPLIED` |
| migration_source | `BOOTSTRAP` |
| is_emergency | `1` |
| subject_user_id | `10001` |
| Row found | OK / FAIL |

### 7.7 Audit / History / ممیزی و تاریخچه

| Metric | Expected |
|--------|----------|
| `user_count` | `1` |
| `assigned_role_count` (all users, active) | `2` |
| `bootstrap_request_count` | `1` |
| `bootstrap_audit_count` | `>= 1` |
| `bootstrap_history_count` | `>= 3` |

### 7.8 Customer Access Check / بررسی دسترسی مشتری

| Metric | Expected |
|--------|----------|
| `customer_role_count` | `0` |
| Status | OK if zero |

### 7.9 Overall Status / وضعیت کلی

- **ALL CHECKS PASSED** / **FAILED** (single banner)
- List of failed check IDs (C01–C17) if any
- Reminder: read-only page — no login replacement performed
- Link to related docs (internal): `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md` — optional footer for operators only

**UI style (plan only):** Simple HTML table; green/red status pills; no charts required in V0.

---

## 8) Success criteria / معیارهای موفقیت

The diagnostic run is **successful** when **all** of the following are true:

| # | Criterion |
|---|-----------|
| S1 | SQL Server connection to `moghare360_ERP` succeeds |
| S2 | Database name = `moghare360_ERP` |
| S3 | Collation = `Persian_100_CI_AS` (or documented acceptable match) |
| S4 | `core_*` table count = `16` |
| S5 | `user_id = 10001` exists |
| S6 | Username = `mahin.paradigm.owner` |
| S7 | Active role `owner` assigned to user 10001 |
| S8 | Active role `system_admin` assigned to user 10001 |
| S9 | Request `BOOTSTRAP-10001` exists with expected state |
| S10 | Audit/history counts meet or exceed bootstrap execution status |
| S11 | `customer_role_count = 0` |
| S12 | **No write operation** was performed (code path review + read-only DB user if used) |
| S13 | **No secrets** appeared in page output |

**Exit gate for next phase (`erp-admin-login.php`):** Product owner signs off on a successful local/staging diagnostic screenshot or log — not on production public URL.

---

## 9) Risks / ریسک‌ها

| ID | Risk | Impact | Mitigation |
|----|------|--------|------------|
| R1 | **PHP `sqlsrv` driver not installed** | Page cannot connect | Verify `php -m` / `phpinfo()` locally first; document driver version for SQL Server |
| R2 | **cPanel hosting may not support SQL Server** | Diagnostic fails on production host | Run Phase 1 on **local XAMPP/WAMP + SQLEXPRESS**; treat cPanel as separate decision |
| R3 | **Local XAMPP needs SQL Server PHP driver** | Dev blocked | Install Microsoft ODBC Driver + `sqlsrv` DLLs matching PHP thread safety (TS/NTS) and version |
| R4 | **Exposing diagnostic page publicly** | Schema/user enumeration | IP restrict, no public links, remove after test |
| R5 | **Mixing MySQL portal config and SQL Server ERP config** | Wrong connection, false negatives | Separate `getErpSqlConnection()`; never point `getPdo()` at ERP for this page |
| R6 | **Verbose SQL errors** | Information leak | Generic user message; log full error to file outside web root |
| R7 | **Assuming bootstrap IDs unchanged** | False FAIL after re-bootstrap | Document expected values; version stamp in page footer |
| R8 | **Operators confuse diagnostic with login** | Premature login migration | Clear page title “Read-only — not a login page” |

---

## 10) Recommended implementation order after approval / ترتیب پیاده‌سازی پس از تأیید

Execute in this order **after** this plan is approved:

```
Step 1  → Verify PHP sqlsrv (or pdo_sqlsrv) extension locally
Step 2  → Add server-local ERP config (gitignored) — not in GitHub
Step 3  → Create erp-bootstrap-status.php — LOCAL ONLY first
Step 4  → Test against SQLEXPRESS / moghare360_ERP on dev machine
Step 5  → Confirm all checks C01–C17 pass (Section 8)
Step 6  → Keep staff-login.php / staff-auth.php UNCHANGED
Step 7  → Review file for secrets; commit PHP only if zero credentials in code
Step 8  → Product owner approval → proceed to erp-admin-login.php plan (Phase 2)
```

### Per-step notes

| Step | Persian / English note |
|------|------------------------|
| 1 | افزونه `sqlsrv` را روی همان PHP که XAMPP استفاده می‌کند نصب و فعال کنید. |
| 2 | فایل `config.erp.php` فقط روی سرور/لوکال؛ الگو با placeholder در repo در صورت نیاز. |
| 3 | صفحه را ابتدا فقط روی `localhost` باز کنید — نه روی دامنه عمومی. |
| 4 | نتایج را با `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md` مقایسه کنید. |
| 5 | پرتال فعلی را دست نزنید — `staff_users` همچنان مسیر ورود است. |
| 6 | قبل از commit، جستجوی `password`, `PWD`, connection string در فایل PHP. |

**Do not implement until Step 0 (this document) is explicitly approved.**

---

## 11) Final rule / قانون نهایی

> **No login replacement until this read-only diagnostic page is approved, implemented locally, and tested.**  
> **تا زمانی که این صفحه تشخیصی فقط-خواندنی تأیید، به‌صورت محلی پیاده‌سازی و تست نشود، هیچ جایگزینی برای ورود انجام نشود.**

This means:

- Do **not** modify `staff-auth.php`, `staff-login.php`, or `access-control.php` for ERP bridge work in Phase 1.
- Do **not** create `erp-admin-login.php` until diagnostic success is recorded.
- Do **not** create users or assign roles from PHP.
- Do **not** deploy `erp-bootstrap-status.php` to a public URL without protection.

**Sequence:** Approve this plan → implement diagnostic locally → all checks OK → approve Phase 2 (ERP admin login) → only later consider staff login migration.

---

## Appendix A — Checklist summary (for operators)

| Check | Expected | Pass? |
|-------|----------|-------|
| C01 Connection | OK | ☐ |
| C02 DB name | moghare360_ERP | ☐ |
| C03 Collation | Persian_100_CI_AS | ☐ |
| C04 core_* tables | 16 | ☐ |
| C05 user 10001 | exists | ☐ |
| C06 username | mahin.paradigm.owner | ☐ |
| C08 role owner | assigned | ☐ |
| C09 role system_admin | assigned | ☐ |
| C11 BOOTSTRAP-10001 | exists | ☐ |
| C13–C16 counts | per bootstrap status | ☐ |
| C17 customer roles | 0 | ☐ |
| Read-only | no writes | ☐ |
| No secrets in output | confirmed | ☐ |

---

## Appendix B — Relationship to architecture

Per `docs/PRODUCT_ARCHITECTURE_DECISION.md`:

- Diagnostic validates **Layer 1: Core Platform** (`core_*` in `moghare360_ERP`).
- Platform Owner (`user_id = 10001`) is **not** Moghareh Tenant Owner — page labels should say “Platform Owner bootstrap”.
- Customer access remains **out of V0** — `customer_role_count = 0` is a required pass.

---

*End of V0 ERP Bootstrap Status Page Plan.*
