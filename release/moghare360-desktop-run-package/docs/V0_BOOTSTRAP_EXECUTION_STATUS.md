# V0 Bootstrap Execution Status / وضعیت اجرای Bootstrap نسخه ۰

**Scope:** Platform Owner bootstrap — MOGHARE360 ERP Version 0  
**Status:** Completed in Development/Staging

**Related documents:**
- `docs/V0_BOOTSTRAP_APPROVAL_CHECKLIST.md`
- `docs/V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md`
- `docs/V0_SQL_EXECUTION_STATUS.md`
- `docs/PRODUCT_ARCHITECTURE_DECISION.md`

---

## 1) Target database / دیتابیس هدف

- **DB:** `moghare360_ERP`

---

## 2) Environment / محیط اجرا

| Field | Value |
|-------|-------|
| **Environment** | Development / Staging |
| **SQL Server** | `SQLEXPRESS` |

---

## 3) Executed SQL file / فایل SQL اجرا شده

- `public_html/sql/sqlserver/core_v0_09_bootstrap_owner_admin.sql`

---

## 4) Bootstrap owner created / مالک پلتفرم Bootstrap ایجاد شده

| Field | Value |
|-------|-------|
| **user_id** | `10001` |
| **username** | `mahin.paradigm.owner` |
| **full_name** | `MahinParadigmCo.` |
| **mobile** | `+989131173340` |
| **email** | `amiralimaher@yahoo.com` |
| **lifecycle_state** | `ACTIVE` |
| **is_system_owner** | `1` |
| **is_login_enabled** | `1` |

**Table:** `core_users`

---

## 5) Assigned temporary roles / نقش‌های موقت اختصاص‌یافته

| Role | Notes |
|------|-------|
| **owner** | Platform Owner |
| **system_admin** | Temporary setup role (until separate System Admin at `user_id = 2` is approved) |

**Table:** `core_user_roles`  
**Granted via:** bootstrap exception — synthetic request `BOOTSTRAP-10001`

---

## 6) Synthetic request / درخواست مصنوعی Bootstrap

| Field | Value |
|-------|-------|
| **request_number** | `BOOTSTRAP-10001` |
| **request_type** | `EMERGENCY` |
| **request_state** | `APPLIED` |
| **migration_source** | `BOOTSTRAP` |
| **is_emergency** | `1` |
| **subject_user_id** | `10001` |

**Table:** `core_access_requests`  
**Note:** `BOOTSTRAP` is not a valid `request_type` in CHECK constraints; type `EMERGENCY` was used with `migration_source = BOOTSTRAP` per approved bootstrap design.

---

## 7) Validation results / نتایج اعتبارسنجی

| Metric | Count |
|--------|-------|
| **user_count** | `1` |
| **assigned_role_count** | `2` |
| **bootstrap_request_count** | `1` |
| **bootstrap_audit_count** | `1` |
| **bootstrap_history_count** | `3` |

**Interpretation:**
- One Platform Owner user (`10001`)
- Two active roles (`owner`, `system_admin`)
- One synthetic bootstrap request
- One bootstrap audit log entry (`BOOTSTRAP_PLATFORM_OWNER_APPLIED`)
- Three change-history rows (user upsert + two role grants)

---

## 8) Security note / یادداشت امنیتی

- **Real password was not committed to GitHub.**  
  رمز عبور واقعی در GitHub commit نشده است.

- **Real password hash was not committed to GitHub.**  
  هش رمز عبور واقعی در GitHub commit نشده است.

- **Password hash was replaced only inside SSMS during execution.**  
  هش رمز عبور فقط در زمان اجرا و داخل SSMS جایگزین placeholder شده است.

- **The SQL repository file still contains placeholder only.**  
  فایل SQL در ریپازیتوری همچنان فقط شامل placeholder است (`CHANGE_ME_SECURE_PASSWORD_HASH`).

---

## 9) Important rules confirmed / قوانین مهم (تأیید شده)

| Rule | Status |
|------|--------|
| No normal staff users were created | Confirmed |
| No customer users were created | Confirmed |
| No legacy users were migrated | Confirmed |
| No operational roles were assigned | Confirmed |
| No tenant owner table was created | Confirmed |
| Platform Owner and Moghareh Tenant Owner remain conceptually separate | Confirmed |

Per `docs/PRODUCT_ARCHITECTURE_DECISION.md`:
- **Platform Owner** = product/platform governance (`user_id = 10001`)
- **Moghareh Tenant Owner** = future tenant-level responsibility (not merged in V0 schema)

---

## 10) Current state / وضعیت فعلی

**V0 Platform Owner bootstrap completed.**  
Bootstrap پلتفرم مالک سیستم (Platform Owner) در محیط Development/Staging تکمیل شده است.

**Prior foundation (unchanged):**
- V0 SQL core tables and seeds remain in place (`docs/V0_SQL_EXECUTION_STATUS.md`)
- Total users in `core_users` after bootstrap: **1**

---

## 11) Next planned step / گام بعدی پیشنهادی

**Prepare application-level login/admin access bridge or validation plan before creating more users.**

آماده‌سازی پل ارتباطی ورود/ادمین در لایه اپلیکیشن (یا طرح اعتبارسنجی) **قبل از** ایجاد کاربران بیشتر — از جمله:
- اتصال `staff-auth.php` / portal login به `moghare360_ERP.core_users`
- اعتبارسنجی `accessHas()` روی نقش‌های bootstrap
- الزام تغییر رمز در اولین ورود (وقتی لایه اپلیکیشن آماده شد)
- تعریف جداگانه System Admin (`user_id = 2`) پس از تأیید مالک

**Do not create additional users until that plan is approved.**

---

*End of bootstrap execution status document.*
