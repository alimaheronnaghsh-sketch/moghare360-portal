# MOGHARE360 P11.9-B-FIX-E — Entry Shell + Home Link Report

**Phase:** P11.9-B-FIX-E  
**Status:** COMPLETE  
**Date:** 2026-06-26  
**Scope gate:** `MOGHARE360_P11_9_B_FIX_E_ENTRY_SHELL_HOME_LINK_SCOPE_REPORT.md`

---

## 1. Scope Gate Result

**PASS** — Public shell alignment + home link correction only. No Auth architecture, DB, or permission changes.

---

## 2. Root vs index.php Cause

| URL | File | Purpose |
|-----|------|---------|
| `/moghare360/` | `index.html` | Public green MOGHAREH360 landing |
| `/moghare360/index.php` | `index.php` | Protected admin entry |

Stale XAMPP `index.php` caused old Master ERP if FIX-D not deployed. Home links to `index.php` in `mirror-layout.php` routed public users to admin path.

---

## 3. Home Link Cause

`includes/mirror-layout.php` used `index.php` for brand + «خانه» nav — inherited by staff-login, customer-request, owner-login, user-access-request.

---

## 4. Files Changed

| File | Change |
|------|--------|
| `public_html/index.html` | Restyled with `mirror.css` + `moghare360-v1-luxury-ui.css` (green shell) |
| `public_html/includes/mirror-layout.php` | Home/brand → `./`; removed public nav «ورود مدیریتی» |
| `public_html/index.php` | Back links → `./` |
| `public_html/customer-login.php` | Back link → `./` |
| `public_html/customer-profile.php` | Back links → `./` |
| Scope + implementation reports + 3 tests | Created |

**Not changed:** `staff-login.php`, `owner-login.php`, `customer-request.php` behavior (home via layout only).

---

## 5. Public Root Visual Alignment

- Uses `m360-public-shell` + shared MOGHAREH360 CSS
- Dark green gradient (`--m360-bg`, `--m360-accent`)
- Same header/nav/card pattern as login pages
- Logo + MOGHAREH360 brand row
- No bronze-isolated `lux-*` theme
- No admin/management nav on public root

---

## 6. Home Link Corrections

| Location | Before | After |
|----------|--------|-------|
| `mirror-layout.php` brand | `index.php` | `./` |
| `mirror-layout.php` خانه | `index.php` | `./` |
| `index.php` admin back | `index.html` | `./` |
| `customer-login.php` | `index.php` | `./` |
| `customer-profile.php` | `index.php` | `./` |

---

## 7. index.php Public Lock / Old Entry Removal

- No `v1mc` / Master ERP Entry content
- Unauthenticated: Persian locked page + `owner-login.php` only
- No internal links in locked view
- Authenticated: Persian admin hub (FIX-D)

---

## 8. Customer Entry Route

`customer-request.php` — unchanged; linked from public root and nav.

---

## 9. Staff Entry Route

`staff-login.php` — unchanged; linked from public root and nav.

---

## 10. What Was Not Changed

- Auth/Login architecture and login form processing
- `staff-auth.php`, `access-control.php`, config files
- Permissions, roles, DB, workflow, OTP, private files

---

## 11. Tests Passed

| Test | Result |
|------|--------|
| `php -l public_html/index.php` | PASS — no syntax errors |
| `test-p11-9-b-fix-e-entry-shell-home-links.php` | PASS — 48/48 |
| `test-p11-9-b-fix-e-index-public-lock.php` | PASS — 18/18 |
| `test-p11-9-b-fix-e-scope-security.php` | PASS — 10/10 |
| `test-v1-production-signoff.php` | PASS — 23/23 |

---

## 12. Browser Validation

Deploy to XAMPP: `index.html`, `index.php`, `includes/mirror-layout.php`, `.htaccess`, customer-login/profile if used.

| Check | Expected |
|-------|----------|
| `/moghare360/` | Green MOGHAREH360 shell; خانه/پرسنل/مشتری |
| خانه from staff-login | Stays on `/` not old index.php |
| `/index.php` logged out | Persian locked admin page |
| No Master ERP / READY/CHECK | Confirmed |

---

## 13. Security Confirmation

- No Auth/Login architecture change
- No login behavior change
- No staff-login.php / owner-login.php / customer-request behavior change
- No staff-auth.php / access-control.php change
- No permission/role/DB/SQL/workflow change
- No user/JobCard creation; no OTP/private/secrets/P12

---

## 14. Remaining Backlog

- Global back navigation for all application pages
- Admin first-page centralization refinement
- Software Admin vs Company Owner separation
- Product-change request workflow
- Role taxonomy cleanup
- QC vs Delivery; Electrical / Undercarriage mapping
- Product Home Persian localization
- Full customer portal completion

---

## 15. Recommended Next Step

1. Copy updated files to XAMPP; hard-refresh
2. Verify خانه from staff-login opens `/moghare360/` not old Master ERP
3. Continue P11.9-B-A preflight via staff-login + Access Management

---

P11.9-B-FIX-E aligns the public root with the existing green MOGHAREH360 visual shell, corrects Home links to return to the public root instead of old index.php, and removes old Master ERP public exposure from index.php without changing Auth/Login architecture, permissions, roles, departments, positions, database, SQL, workflow, users, JobCards, OTP, private files, secrets, or P12 scope.
