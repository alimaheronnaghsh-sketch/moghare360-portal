# MOGHARE360 P11.9-B-FIX-F — Public UX + Cache Report

**Phase:** P11.9-B-FIX-F  
**Status:** COMPLETE  
**Date:** 2026-06-26  
**Scope gate:** `MOGHARE360_P11_9_B_FIX_F_PUBLIC_UX_CACHE_SCOPE_REPORT.md`

---

## 1. Scope Gate Result

**PASS** — UI polish, typography, cache/link cleanup only. No Auth/DB/permission changes.

---

## 2. Root / Index / Cache Cause

| Factor | Finding |
|--------|---------|
| `/moghare360/` | `DirectoryIndex` → `index.html` |
| `/moghare360/index.php` | Protected admin PHP |
| **Primary stale cause** | `service-worker.js` pre-cached `./index.php` and served cache-first |
| Secondary | Missing no-cache meta on `index.html`; HTTP cache |
| Deploy drift | XAMPP copy not synced with repo |

---

## 3. Home Link Scan Result

Public login shells use `mirror-layout.php` with Home → `./`. No `href="index.php"` on staff-login, owner-login, customer-request, user-access-request, customer-login, customer-profile.

Remaining `index.php` links: internal ERP pages (`mirror-health.php`, master-console helper) — out of FIX-F allowed scope.

---

## 4. Files Changed

| File | Change |
|------|--------|
| `public_html/index.html` | Premium hero, larger entry cards, section grid, typography, no-cache meta, SW unregister |
| `public_html/index.php` | `Expires` header, no-cache meta, SW unregister on locked view |
| `public_html/includes/mirror-layout.php` | Cache headers/meta, asset `fix-f-v2`, SW unregister (stop register) |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Home landing styles (hero, entry cards, sections) |
| `public_html/.htaccess` | `Cache-Control` for index.html/index.php |
| Scope + implementation reports + 3 tests | Created |

**Not modified:** `service-worker.js` (reported; mitigated via unregister on public pages).

---

## 5. UX Improvements Applied

- Purposeful unified hero brand row (logo + title inline — no empty side box)
- Two-column premium staff/customer entry cards (larger, hover, aligned)
- Four content sections below fold (not wall of text on first screen)
- WhatsApp contact strip
- RTL Persian-first layout retained

---

## 6. Brand Typography Fix

- Title: `مقاره ۳۶۰` in `.m360-home-title`
- Font stack: `Vazirmatn`, `IRANSans`, `Tahoma`, `Arial`
- `letter-spacing: 0` on Persian title
- `MOGHARE360` as separate Latin label with subtle spacing only

---

## 7. Staff/Customer Card Improvements

- `.m360-home-entry-grid` — 2 columns desktop, stack mobile
- Min-height 268px, full-width CTA buttons, stronger titles, gradient card background

---

## 8. Cache/Index Consistency Fix

| Layer | Fix |
|-------|-----|
| PHP headers | `Cache-Control`, `Pragma`, `Expires: 0` on index.php + mirror-layout |
| HTML meta | no-cache on index.html and index.php views |
| `.htaccess` | no-store headers for index files |
| Service worker | Unregister + `caches.delete` on public shell; removed registration from mirror-layout |
| bfcache | `pageshow` reload when persisted |
| CSS | Version bump `?v=fix-f-v2` |

---

## 9. index.php Public Lock Result

Unauthenticated: Persian locked page, `owner-login.php` only, back to `./`. No old Master ERP content in repo. Authenticated: Persian admin hub unchanged.

---

## 10. What Was Not Changed

Auth/Login core, login forms, sessions, `staff-auth.php`, `access-control.php`, permissions, roles, DB, SQL, workflow, private files.

---

## 11. Tests Passed

| Test | Result |
|------|--------|
| `php -l public_html/index.php` | PASS — no syntax errors |
| `test-p11-9-b-fix-f-public-ux-polish.php` | PASS — 46/46 |
| `test-p11-9-b-fix-f-index-cache-consistency.php` | PASS — 35/35 |
| `test-p11-9-b-fix-f-scope-security.php` | PASS — 9/9 |
| `test-v1-production-signoff.php` | PASS — 23/23 |

---

## 12. Browser Validation

1. Clear localhost cache or Ctrl+F5 once after deploy
2. `/moghare360/` — premium green landing, larger cards, elegant title
3. «خانه» — stays on public root
4. `/index.php` — locked admin, no Master ERP
5. Back/forward — no flip to old content after SW cleared

**Deploy:** `index.html`, `index.php`, `.htaccess`, `includes/mirror-layout.php`, `assets/css/moghare360-v1-luxury-ui.css`

---

## 13. Security Confirmation

- No Auth/Login architecture change
- No login behavior change
- No staff-auth.php / access-control.php change
- No permission/role/department/position change
- No DB schema / SQL migration change
- No workflow/action handler change
- No user / JobCard creation
- No OTP change
- No private file change
- No secrets committed
- No P12 scope

---

## 14. Remaining Backlog

- Global back navigation for all application pages
- Admin first-page centralization refinement
- Software Admin vs Company Owner separation
- Product-change request workflow
- Department/position/role taxonomy cleanup
- QC vs Delivery role separation
- Electrical / Undercarriage role mapping
- Product Home full Persian localization
- Full customer portal completion
- Remove or rewrite `service-worker.js` to asset-only cache (optional hardening)
- Fix `mirror-health.php` back link to `./`

---

## 15. Recommended Next Step

1. Copy FIX-F files to XAMPP; hard-refresh once
2. Visit staff-login then Home — confirm no stale index.php
3. Continue P11.9-B-A dry-run preflight

---

P11.9-B-FIX-F improves the public landing UX, fixes brand typography, enlarges and polishes staff/customer entry cards, and stabilizes root/index cache consistency so old Master ERP public content no longer appears, without changing Auth/Login architecture, permissions, roles, departments, positions, database, SQL, workflow, users, JobCards, OTP, private files, secrets, or P12 scope.
