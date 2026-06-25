# V0 Bootstrap Approval Checklist
# چک‌لیست تأیید دستی Bootstrap — نسخه ۰ MOGHARE360 ERP

**Document type:** Manual approval gate (must be completed before bootstrap SQL)  
**Target database:** `moghare360_ERP`  
**Environment:** Development / Staging only — NOT Production

**Related documents:**
- `docs/PRODUCT_ARCHITECTURE_DECISION.md`
- `docs/V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md`
- `docs/V0_SQL_EXECUTION_STATUS.md`
- `docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md`

---

## ⚠️ Final warning / هشدار نهایی

> **This checklist must be approved before any bootstrap SQL is created or executed.**  
> **این چک‌لیست باید قبل از ایجاد یا اجرای هر SQL مربوط به Bootstrap تأیید شود.**

Until approval is recorded below:
- Do **not** run `core_v0_09_bootstrap_owner_admin.sql` (when created)
- Do **not** insert into `core_users`, `core_user_roles`, or related tables manually
- Do **not** commit passwords or password hashes to GitHub

---

## 1) Final recommended approach / رویکرد پیشنهادی نهایی

| # | Decision | Status |
|---|----------|--------|
| 1 | Create **only** `user_id = 1` as **Platform Owner / System Owner** | ☐ Confirmed |
| 2 | Temporarily allow `user_id = 1` to also hold **`system_admin`** role (setup phase only) | ☐ Confirmed |
| 3 | **Reserve** `user_id = 2` for future real **System Admin** (do not create user 2 in first bootstrap) | ☐ Confirmed |
| 4 | **Moghareh Tenant Owner** is a **future tenant-level concept** — must **not** be mixed with Platform Owner in V0 database design | ☐ Confirmed |

**English summary:**  
One bootstrap account (`user_id = 1`) starts the platform. It holds both `owner` and `system_admin` roles temporarily. `user_id = 2` stays reserved. Tenant ownership for Moghareh the business is documented separately later.

**خلاصه فارسی:**  
فقط یک حساب bootstrap (`user_id = 1`) ساخته می‌شود. موقتاً هر دو نقش `owner` و `system_admin` دارد. `user_id = 2` رزرو می‌ماند. مالک tenant مغاره با مالک پلتفرم در طراحی V0 ادغام نمی‌شود.

---

## 2) Required owner information / اطلاعات لازم مالک سیستم

Complete **before** bootstrap SQL. Values below are **proposals** — owner must approve exact values.

| Field | Proposed value | Approved value (fill in) |
|-------|----------------|--------------------------|
| **user_id** | `1` | _________________ |
| **username** | *(owner to confirm)* e.g. `owner` / `amir` | _________________ |
| **full_name** | Amir Ali / امیرعلی | _________________ |
| **mobile** | *(owner to confirm)* | _________________ |
| **email** | *(optional)* | _________________ |
| **is_system_owner** | `1` (true) | ☐ `1` ☐ other: _____ |
| **lifecycle_state** | `ACTIVE` | ☐ `ACTIVE` ☐ other: _____ |
| **is_login_enabled** | `1` (true) | ☐ `1` ☐ `0` |

**Database table:** `core_users`  
**Staff profile:** Not required for bootstrap phase (no `core_staff_profiles` row unless explicitly approved later).

---

## 3) Required temporary role assignment / اختصاص نقش موقت

Bootstrap is the **only controlled exception** before the access request workflow is fully operational.

| Role (`core_roles.role_key`) | Assign to user_id = 1? | Temporary? | Notes |
|------------------------------|------------------------|------------|-------|
| **owner** | ☐ Yes ☐ No | Permanent for platform | Platform Owner |
| **system_admin** | ☐ Yes ☐ No | ☐ Yes — remove when user_id = 2 exists | Setup / technical admin |

**Tables affected (after approval only):**
- `core_user_roles` (with `granted_by_request_id` = bootstrap migration marker / synthetic request — per future SQL design)
- `core_access_change_history` + `core_audit_logs` (mandatory)

**Do not assign:** `reception_staff`, `inventory_staff`, `finance_staff`, or any operational role.

---

## 4) Password safety / امنیت رمز عبور

| Rule | Confirmed |
|------|-----------|
| No real password in GitHub | ☐ |
| No password hash in GitHub unless **manually approved** in writing | ☐ |
| Initial password created **manually and securely** (SSMS / secure admin tool — not in repo) | ☐ |
| Password **must be changed** on first login when application login exists | ☐ |

**Password creation method (check one):**
- ☐ Manual hash generation offline, inserted only in approved bootstrap SQL at execution time (not committed)
- ☐ Placeholder row + password set in SSMS after insert (documented)
- ☐ Other (describe): _________________________________________________

---

## 5) Manual approval fields / فیلدهای تأیید دستی

**To be completed by Platform Owner / authorized decision maker:**

| Field | Value |
|-------|-------|
| **Approved by** (name + role) | _________________________________________________ |
| **Approval date** | ____ / ____ / ________ |
| **Approved username** | _________________________________________________ |
| **Approved full name** | _________________________________________________ |
| **Approved mobile** | _________________________________________________ |
| **Approved email** | _________________________________________________ |
| **System Admin separate now?** | ☐ Yes (create user_id = 2 later) ☐ **No** (user_id = 1 holds both roles temporarily) |
| **user_id = 2 reserved?** | ☐ **Yes** ☐ No |
| **Notes** | |
| | _________________________________________________ |
| | _________________________________________________ |
| | _________________________________________________ |

**Signature / confirmation channel:**  
☐ Written message ☐ Email ☐ Meeting minutes ☐ Other: _________________

---

## 6) Forbidden actions / اقدامات ممنوع

Confirm understanding — **none** of the following during bootstrap phase:

| Forbidden action | Understood |
|------------------|------------|
| Do **not** create normal staff users | ☐ |
| Do **not** create customer users | ☐ |
| Do **not** migrate legacy `staff_users` | ☐ |
| Do **not** create access requests yet (except documented synthetic bootstrap audit if required by SQL) | ☐ |
| Do **not** assign operational roles (reception, inventory, finance, etc.) | ☐ |
| Do **not** hardcode passwords in repo | ☐ |
| Do **not** create Moghareh-specific tenant owner tables yet | ☐ |

---

## 7) Next SQL file after approval / فایل SQL بعد از تأیید

**Only after this checklist is fully approved:**

```
public_html/sql/sqlserver/core_v0_09_bootstrap_owner_admin.sql
```

**Expected scope of that file (when created):**
- Insert `core_users` row for `user_id = 1` only (unless separate System Admin approved)
- Assign `owner` + temporary `system_admin` roles
- Write audit / change history records
- **No** password in committed file unless explicitly approved and documented

**Follow-up validation (proposed, not created yet):**
```
public_html/sql/sqlserver/core_v0_10_bootstrap_validation.sql
```

---

## 8) Pre-approval verification / بررسی قبل از تأیید

Confirm V0 SQL foundation is already in place (`docs/V0_SQL_EXECUTION_STATUS.md`):

| Check | Expected | OK? |
|-------|----------|-----|
| Database `moghare360_ERP` exists | Yes | ☐ |
| `core_*` tables seeded (org, roles, permissions, approval rules) | Yes | ☐ |
| No real users exist yet | Yes | ☐ |
| `customer_role_count` | `0` | ☐ |
| Bootstrap strategy read | `V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md` | ☐ |
| Product architecture read | `PRODUCT_ARCHITECTURE_DECISION.md` | ☐ |

---

## 9) Approval gate summary / جمع‌بندی دروازه تأیید

| Gate | Status |
|------|--------|
| Section 1 — Recommended approach confirmed | ☐ |
| Section 2 — Owner information filled and approved | ☐ |
| Section 3 — Temporary roles confirmed | ☐ |
| Section 4 — Password safety confirmed | ☐ |
| Section 5 — Manual approval fields completed | ☐ |
| Section 6 — Forbidden actions acknowledged | ☐ |
| Section 8 — Pre-approval verification passed | ☐ |

**Overall approval to proceed with bootstrap SQL:**

☐ **APPROVED** — may create/run `core_v0_09_bootstrap_owner_admin.sql` in Dev/Staging  
☐ **NOT APPROVED** — do not create or execute bootstrap SQL

**Approver signature / name:** _________________________________________________  
**Date:** ____ / ____ / ________

---

*End of checklist. Store completed copy outside Git if it contains personal data (mobile, email).*
