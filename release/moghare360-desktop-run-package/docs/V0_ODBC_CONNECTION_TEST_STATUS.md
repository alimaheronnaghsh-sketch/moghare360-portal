# V0 ODBC Connection Test Status
# وضعیت تست اتصال ODBC — نسخه ۰

**Document type:** Local environment test status (connection validation only)  
**Status:** ODBC path confirmed for local diagnostic work  
**Scope:** PHP → SQL Server `moghare360_ERP` on development machine — **not** production architecture

**Related documents:**
- `docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_PLAN.md`
- `docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md`
- `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`
- `docs/V0_SQL_EXECUTION_STATUS.md`

---

## 1) Environment / محیط

| Field | Value |
|-------|-------|
| **PHP executable** | `C:\xampp\php\php.exe` |
| **PHP version** | `8.0.30` |
| **SQL Server instance** | `SQLEXPRESS` |
| **Target database** | `moghare360_ERP` |
| **Environment type** | Local development (XAMPP + SQL Server Express) |
| **Portal context** | Legacy portal login unchanged — still MySQL `staff_users` on cPanel path |

**Persian:** تست روی ماشین توسعه محلی با XAMPP و SQL Server Express انجام شده است.

---

## 2) Driver status / وضعیت درایورها

| Extension | Status | Notes |
|-----------|--------|-------|
| **`sqlsrv`** | **Not installed** | Microsoft SQL Server driver for PHP — not available in current XAMPP build |
| **`pdo_sqlsrv`** | **Not installed** | PDO SQL Server driver — not available in current XAMPP build |
| **`odbc`** | **Enabled** | PHP ODBC extension active — used for successful connection test |

**Implication:** Local PHP cannot use `sqlsrv_connect()` or `pdo_sqlsrv` DSN today. ODBC is the **only working PHP path** on this machine without installing additional Microsoft PHP drivers.

---

## 3) Test results / نتایج تست

### 3.1 sqlcmd (baseline — outside PHP)

| Test | Result |
|------|--------|
| Connection to `moghare360_ERP` | **Succeeded** |
| `core_table_count` | **16** |

Confirms database foundation matches `docs/V0_SQL_EXECUTION_STATUS.md` (16 `core_*` tables after approval rules seed).

---

### 3.2 PHP ODBC connection

| Test | Result |
|------|--------|
| PHP ODBC connection to SQL Server | **Succeeded** |
| PHP ODBC query output | **`moghare360_ERP \| 16`** |

Interpretation: PHP reached the correct database and read `core_*` table count = **16** (database name + count reported in single diagnostic output).

---

### 3.3 Bootstrap owner query (PHP ODBC)

| Field | Value |
|-------|-------|
| Query | **Succeeded** |
| **user_id** | `10001` |
| **username** | `mahin.paradigm.owner` |
| **full_name** | `MahinParadigmCo.` |

Matches `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md` Platform Owner bootstrap record.

**Not queried for display:** `password_hash` (by policy — must remain excluded from diagnostic output).

---

### 3.4 Summary matrix

| Layer | Tool | Connection | Key validation |
|-------|------|------------|----------------|
| SQL Server CLI | `sqlcmd` | OK | DB + 16 core tables |
| PHP application | ODBC | OK | `moghare360_ERP`, count 16, user 10001 |

**Bridge plan checkpoint:** Application layer can **read** ERP core data locally — prerequisite for `erp-bootstrap-status.php` per `docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_PLAN.md`.

---

## 4) Decision / تصمیم

| Decision | Detail |
|----------|--------|
| **Local diagnostic page** | Use **PHP ODBC temporarily** for `public_html/erp-bootstrap-status.php` on local XAMPP |
| **Current portal login** | **Do not replace** — `staff-login.php` / `staff-auth.php` remain on legacy MySQL path |
| **Production architecture** | **Do not adopt ODBC as production standard yet** — this is a dev/staging bridge only |
| **Future production options** | May use `sqlsrv` / `pdo_sqlsrv` when drivers are installed on target host, or an **API service** layer between cPanel PHP and SQL Server ERP |

**Persian summary:**  
برای صفحه تشخیصی محلی، فعلاً ODBC کافی است. این تصمیم معماری نهایی تولید نیست و جایگزین ورود پرتال نمی‌شود.

**Rationale:**

- ODBC works today without modifying XAMPP driver stack.
- `sqlsrv` / `pdo_sqlsrv` remain the preferred long-term PHP drivers where hosting supports them.
- cPanel production may not reach `SQLEXPRESS` at all — separate hosting decision still required (`docs/V0_LOGIN_ADMIN_BRIDGE_PLAN.md`).

---

## 5) Security / امنیت

| Rule | Status / requirement |
|------|----------------------|
| **No SQL Server credentials in GitHub** | Confirmed policy — connection strings and passwords stay server-local / gitignored |
| **Local authentication** | **`Trusted_Connection`** used on local Windows dev (Windows auth to SQL Server) |
| **Diagnostic page exposure** | Must be **local-only or protected** — not public internet, no links from public portal pages |
| **No `password_hash` in output** | Bootstrap owner query must select identity fields only — never display hash column |
| **No sensitive config in repo** | ERP connection settings belong in gitignored local config when PHP page is implemented |

**Persian:** اتصال محلی با Trusted Connection است؛ هیچ رمز یا connection string در GitHub قرار نمی‌گیرد.

---

## 6) Next step / گام بعدی

**Approved next action (after this status document):**

Create read-only **local** diagnostic page:

- **Path:** `public_html/erp-bootstrap-status.php`
- **Behavior:** **SELECT queries only** — no INSERT, UPDATE, DELETE, CREATE, ALTER
- **Connection:** PHP ODBC (temporary local strategy per Section 4)
- **Checks:** Per `docs/V0_ERP_BOOTSTRAP_STATUS_PAGE_PLAN.md` (connection, DB name, collation, core tables, user 10001, roles, bootstrap request, audit/history counts, `customer_role_count = 0`)
- **Login:** **No login replacement** — do not modify `staff-auth.php`, `staff-login.php`, or `access-control.php`

**Exit criteria for following phase:**

- All diagnostic checks pass via ODBC on localhost
- Page runs without exposing secrets or `password_hash`
- Product owner approves proceeding toward separate ERP admin login (`erp-admin-login.php`) — still not staff login migration

---

## 7) Final rule / قانون نهایی

> **No login replacement until the read-only diagnostic page is implemented locally and tested.**  
> **تا زمانی که صفحه تشخیصی فقط-خواندنی محلی پیاده‌سازی و تست نشود، هیچ جایگزینی برای ورود انجام نشود.**

ODBC success in this document **only** validates local connectivity — it does **not** authorize production deployment or auth migration.

---

*End of V0 ODBC Connection Test Status document.*
