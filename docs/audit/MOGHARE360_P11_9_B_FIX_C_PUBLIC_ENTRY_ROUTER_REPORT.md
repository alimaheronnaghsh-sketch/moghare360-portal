# MOGHARE360 P11.9-B-FIX-C — Public Entry Router Report

**Phase:** P11.9-B-FIX-C  
**Status:** COMPLETE  
**Date:** 2026-06-26  
**Scope gate:** `MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_SCOPE_REPORT.md`

---

## 1. Scope Gate Result

**PASS** — Single `index.php` public entry router update only. No Auth, permissions, roles, database, workflow, or new customer module.

---

## 2. Root vs index.php Cause

| Factor | Finding |
|--------|---------|
| Repo routing | `.htaccess` → `DirectoryIndex index.php`; no `index.html` |
| Same file | Both `/moghare360/` and `/moghare360/index.php` must serve `index.php` |
| Observed mismatch | **XAMPP deployment drift** — stale `index.php` on host showing pre-FIX-B Master ERP Entry at explicit `/index.php` while `/` may have been partially updated |
| FIX-C mitigation | Unified self-contained `index.php` + `Cache-Control: no-store` headers; operator must copy single file to XAMPP |

---

## 3. Files Changed

| File | Change |
|------|--------|
| `public_html/index.php` | Unified Persian entry: خانه / پرسنل / مشتری nav + entry cards + FIX-B body |
| `docs/audit/MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_SCOPE_REPORT.md` | Scope gate |
| `docs/audit/MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_REPORT.md` | This report |
| `tools/test-p11-9-b-fix-c-public-entry-router.php` | Content guardrail |
| `tools/test-p11-9-b-fix-c-scope-security.php` | Scope guardrail |

**Not changed:** `index.html` (absent), `.htaccess`, Auth/Login, internal ERP pages.

---

## 4. Public Entry Model Applied

| Choice | Behavior |
|--------|----------|
| **خانه** | Active nav item; Persian introduction content on same page (default) |
| **پرسنل** | Nav link + card → `staff-login.php` («ورود پرسنل») |
| **مشتری** | Nav link + card → `customer-request.php` if file exists |

Top nav: **خانه | پرسنل | مشتری**  
Entry cards visible below nav. Home hero + body content follows.

---

## 5. Staff Entry Route

**Route:** `staff-login.php`  
**Label:** ورود پرسنل  
**Description:** ورود کارکنان مجموعه برای مشاهده میز کار، کارت‌های کار و عملیات روزانه.

---

## 6. Customer Entry Route or Placeholder Decision

**Route (repo):** `customer-request.php` — existing P1 online service request form.

**Fallback (if file missing on host):** Placeholder card — «درگاه مشتری در حال آماده‌سازی است.»

**No new customer module created.**

---

## 7. Internal Links Removed/Hidden

No visible links to: Master Console, Unit Access Console, Product Home, signoff, fix register, route map, soft run, Moghare Ready, owner/management login, Access Management, READY/CHECK/BLOCKED, legacy SaaS banners.

Direct URLs to internal pages remain valid — not deleted.

---

## 8. What Was Not Changed

- Auth/Login (`staff-login.php`, `owner-login.php`)
- Permissions, roles, departments, positions
- Database / SQL migrations
- Workflow / action handlers
- OTP / private files
- Dry run, users, JobCards

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| `php -l public_html/index.php` | **PASS** — no syntax errors |
| `test-p11-9-b-fix-c-public-entry-router.php` | **PASS** — 37/37 |
| `test-p11-9-b-fix-c-scope-security.php` | **PASS** — 9/9 |
| `test-v1-production-signoff.php` | **PASS** — 23/23 |

---

## 10. Browser Validation

Open **both**:

- `http://localhost:8080/moghare360/`
- `http://localhost:8080/moghare360/index.php`

**Expected:** Same Persian landing; خانه / پرسنل / مشتری; staff → `staff-login.php`; customer → `customer-request.php`; no Master ERP Entry; no internal/admin links.

**Operator:** Copy updated `public_html/index.php` to XAMPP `moghare360/` folder; hard-refresh browser (Ctrl+F5).

---

## 11. Security Confirmation

- No Auth/Login change
- No `staff-login.php` change
- No `owner-login.php` change
- No permission/role change
- No department/position change
- No DB schema change
- No SQL migration
- No workflow/action handler change
- No user creation
- No JobCard creation
- No OTP change
- No private file change
- No secrets committed
- No P12 scope

---

## 12. Remaining Backlog

| Item | Status |
|------|--------|
| Admin first-page centralization | Backlog |
| Software Admin vs Company Owner separation | Backlog |
| Product-change request workflow | Backlog |
| Department/position/role taxonomy cleanup | Backlog |
| QC vs Delivery role separation | Backlog |
| Electrical / Undercarriage role mapping | Backlog |
| Product Home full Persian localization | Backlog |
| Real customer portal completion | Backlog if `customer-request.php` insufficient for production |

---

## 13. Recommended Next Step

1. Deploy single `index.php` to XAMPP; verify `/` and `/index.php` match
2. Continue **P11.9-B-A preflight** via direct `staff-login.php` / Access Management — not public home

---

P11.9-B-FIX-C aligns / and /index.php into one Persian public entry page with Home, Staff, and Customer choices, while keeping admin/internal access hidden and without changing Auth/Login, permissions, roles, departments, positions, database, SQL, workflow, users, JobCards, OTP, private files, secrets, or P12 scope.
