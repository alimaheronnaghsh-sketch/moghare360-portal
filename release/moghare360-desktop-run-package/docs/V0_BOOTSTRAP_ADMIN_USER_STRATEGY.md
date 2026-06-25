# V0 Bootstrap Admin User Strategy / استراتژی کاربران Bootstrap (نسخه ۰)

## 1) Purpose / هدف

In Version 0, the Access Lifecycle module is **workflow-driven** (Request → Approval → Apply → Log).  
اما برای اینکه همین چرخه بتواند شروع به کار کند، ما حداقل به **دو کاربر اولیه** نیاز داریم تا:

- اولین ورود به سامانه و راه‌اندازی عملیاتی ماژول انجام شود.
- صفحات/ابزارهای مدیریت دسترسی (Access Management) قابل دسترسی باشند.
- نقش‌ها و مجوزها در محیط Dev/Staging قابل تست و کنترل شوند.
- فرآیندهای بعدی (Onboarding/Offboarding/Suspension/Restriction) امکان اجرا پیدا کنند.

بدون این کاربران اولیه، هیچ‌کس مجاز/قادر نیست درخواست‌ها را مدیریت و اعمال کند، و سیستم در نقطه شروع قفل می‌شود.

---

## 2) Bootstrap principle / اصل Bootstrap

Bootstrap users **exceptional setup users** هستند، نه کاربران عملیاتی روزمره.

اصول کلیدی:

- **Limited**: فقط برای فعال‌سازی و کنترل ماژول Access Lifecycle لازم هستند، نه انجام عملیات واحدها.
- **Documented**: مشخصات دقیق (username, full name, mobile, intent) قبل از ایجاد ثبت می‌شود.
- **Audited**: همه اقدامات Bootstrap باید در آینده در Audit UI قابل مشاهده باشد؛ فعلاً حداقل در `core_audit_logs` ثبت خواهد شد.
- **Temporary/Controlled**: در اولین فرصت، ادمین فنی واقعی تعریف می‌شود و حساب‌های bootstrap بازبینی می‌شوند.

---

## 3) Required bootstrap accounts / حساب‌های Bootstrap لازم

### A) System Owner (مالک سیستم)
- Highest authority for emergency override and governance.
- Used to unblock urgent situations (طبق سیاست).

### B) System Admin (ادمین سیستم)
- Technical operator of the access lifecycle module.
- Applies approved changes when auto-apply is not yet fully integrated.

---

## 4) Recommended first users / کاربران پیشنهادی اولیه

- **System Owner**: **Amir Ali / امیرعلی** — *مالک فرایند ERP*  
- **System Admin**:  
  - Option A (Recommended for early Dev/Staging): **Temporary technical admin** (separate person)  
  - Option B: **Same as Owner** until a real admin is assigned (higher risk, but simplest)

> Decision must be explicitly approved (see section 10).

---

## 5) Rules / قوانین Bootstrap

- **No normal staff user is created in bootstrap phase.**  
  در فاز bootstrap هیچ نیروی عادی (پذیرش/انبار/مالی/...) ساخته نمی‌شود.

- **No operational department access is assigned.**  
  هیچ دسترسی عملیاتی واحدها (inventory, finance, …) به bootstrap کاربران داده نمی‌شود مگر ضرورت تست کنترل‌شده در Dev/Staging.

- **No customer access is created.**  
  هیچ کاربر/نقش/مجوز مربوط به مشتری ساخته نمی‌شود.

- **Bootstrap users exist only to activate the access lifecycle module.**  
  هدف فقط روشن شدن چرخه: Request/Approve/Apply/Log است.

- **All bootstrap actions must be logged later when audit UI exists.**  
  در نسخه ۰ حداقل ثبت در `core_audit_logs` انجام می‌شود؛ در UI آینده قابل ردیابی خواهد بود.

---

## 6) user_id strategy / استراتژی user_id

### Recommendation / پیشنهاد

- `core_users.user_id` در طراحی **manual / non-identity** است تا در آینده امکان **legacy alignment** با `staff_users.id` وجود داشته باشد.  
  (این تصمیم در `core_v0_02_master_tables.sql` اعمال شده است.)

### Bootstrap ID reservation / رزرو ID برای Bootstrap

- Reserve low IDs:
  - `1` = System Owner
  - `2` = System Admin

### Migration timing / زمان مهاجرت

- **Do not migrate old `staff_users` yet.**  
  مهاجرت کاربران قدیمی به `moghare360_ERP` هنوز انجام نمی‌شود تا:
  - سیاست‌ها و ابزارهای UI آماده شوند
  - فرآیندها (Onboarding/Offboarding) عملیاتی شوند
  - ریسک انتقال اشتباه دسترسی کاهش یابد

---

## 7) Password strategy / استراتژی رمز عبور

- **Do not hardcode real passwords in GitHub.**  
  هیچ پسورد واقعی یا hash واقعی نباید در ریپو commit شود.

- **SQL scripts must never contain real password hashes unless approved.**  
  حتی hash هم بدون تأیید مالک نباید وارد اسکریپت شود.

- **Initial password must be created manually in SSMS or via future secure admin tool.**  
  رمز اولیه فقط به‌صورت دستی و امن (یا ابزار مدیریتی امن آینده) تنظیم شود.

- **Password must be changed on first login when application layer exists.**  
  الزام تغییر رمز در اولین ورود باید در لایه اپلیکیشن اضافه شود.

---

## 8) Role assignment strategy / استراتژی اختصاص نقش

Version 0 policy: **No access without request/approval/log.**  
اما برای شروع کار سیستم، Bootstrap یک استثناء کنترل‌شده است:

- Bootstrap role assignment is the **only controlled exception** before the workflow engine is fully operational.
- System Owner receives role: `owner`
- System Admin receives role: `system_admin`
- **All other role assignments MUST go through access request workflow** after bootstrap.

> این استثناء باید با یک رکورد مشخص در `core_audit_logs` ثبت شود (بعد از ایجاد حساب‌ها).

---

## 9) Required future SQL files (propose only) / فایل‌های SQL آینده (فقط پیشنهاد)

Propose (do not create now):

- `core_v0_09_bootstrap_owner_admin.sql`  
  Purpose: create 1–2 bootstrap accounts and minimal required role assignments (controlled exception).

- `core_v0_10_bootstrap_validation.sql`  
  Purpose: post-bootstrap validation queries (no extra data changes).

---

## 10) Required approval before execution / تأیید لازم قبل از اجرا

Bootstrap SQL **must not be executed** until the owner manually approves:

- **exact username** (for Owner and Admin)
- **full name**
- **mobile**
- whether **system admin is separate** or **same as owner**

Approval should be written (message/email) and stored in project documentation.

---

## 11) Risks / ریسک‌ها

- **Overpowered bootstrap accounts**: امکان سوءاستفاده اگر کنترل نشود.
- **Password leakage**: اگر رمز/هش در GitHub یا فایل‌های غیرامن ثبت شود.
- **Skipping workflow**: تبدیل استثناء bootstrap به عادت عملیاتی.
- **Creating real staff too early**: ایجاد کاربران عادی قبل از آماده شدن UI و فرآیندها.
- **Confusing bootstrap with production users**: انتقال bootstrap بدون بازبینی به محیط Production.

Mitigation summary:
- محدود به 1–2 حساب
- عدم seed پسورد
- ثبت audit
- تأیید دستی مالک قبل از اجرا
- برنامه خروج از bootstrap (تعریف ادمین واقعی و کاهش اتکا به Owner)

---

## 12) Final decision / تصمیم نهایی

- **Version 0 SQL foundation is complete.**  
  (تا مرحله seed شدن approval rules طبق `docs/V0_SQL_EXECUTION_STATUS.md`)

- **Next technical step is only to prepare a controlled bootstrap SQL after strategy approval.**  
  گام بعدی فقط آماده‌سازی یک اسکریپت bootstrap کنترل‌شده است — و **تا زمان تأیید، هیچ کاربری ساخته نمی‌شود**.

