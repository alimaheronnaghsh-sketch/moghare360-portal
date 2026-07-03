# MOGHARE360 P11.9-B-FIX-E — Entry Shell + Home Link Scope Report

**Phase:** P11.9-B-FIX-E  
**Mode:** UI / navigation surface alignment  
**Date:** 2026-06-26  
**Gate:** Must pass before implementation

---

## 1. Files Served by URL

| URL | Server file | After FIX-E |
|-----|-------------|-------------|
| `/moghare360/` | `DirectoryIndex` → `index.html` first | Green MOGHAREH360 public shell |
| `/moghare360/index.php` | `index.php` | Protected admin (FIX-D model retained) |

`.htaccess`: `DirectoryIndex index.html index.php`

---

## 2. Public HTML Files

| File | Present | Role |
|------|---------|------|
| `index.html` | Yes | Public root — **restyle to mirror shell** |
| `index.php` | Yes | Protected admin — **no old Master ERP** |
| `.htaccess` | Yes | `index.html` before `index.php` |
| `index.html` override | N/A | — |

---

## 3. Black/Bronze Public Landing

**File:** `public_html/index.html` (P11.9-B-FIX-D)

**Cause:** Self-contained `lux-*` CSS with gold/bronze-heavy theme — **not** linked to `assets/css/mirror.css` / `moghare360-v1-luxury-ui.css` used by login pages.

---

## 4. Old Master ERP Entry

**Was:** Pre-FIX-B `index.php` with `v1mc_render_head`, Master Console, READY/CHECK.

**Observed on XAMPP:** Stale deploy at `/index.php` if FIX-D files not copied.

**Repo `index.php`:** FIX-D protected admin — no Master ERP public content.

**Home link cause:** `includes/mirror-layout.php` nav brand + «خانه» pointed to **`index.php`** → opened admin path / stale Master ERP.

---

## 5. «خانه» Links Pointing to index.php

| File | Finding |
|------|---------|
| `includes/mirror-layout.php` | Brand `href="index.php"`; nav `'index' => ['index.php', 'خانه']` — **used by** staff-login, customer-request, owner-login, user-access-request |
| `staff-login.php` | Uses `mirror_render_head` — home via layout |
| `customer-request.php` | Uses `mirror_render_head` — home via layout |
| `owner-login.php` | Uses `mirror_render_head` — home via layout |
| `user-access-request.php` | Uses `mirror_render_head` — home via layout |
| `customer-login.php` | Direct `href="index.php"` — **fix to `./`** |
| `customer-profile.php` | Direct `href="index.php"` — **fix to `./`** (customer public) |

---

## 6. Shared CSS / Layout (Login Pages)

| Asset | Path |
|-------|------|
| Layout | `includes/mirror-layout.php` → `mirror_render_head` / `mirror_render_foot` |
| CSS | `assets/css/mirror.css` → imports `moghare360-v1-luxury-ui.css` |
| Variables | `--m360-bg`, `--m360-accent` (#22c55e), dark green shell |

**Classes:** `m360-public-shell`, `m360-public-header`, `m360-hero`, `m360-card`, `m360-btn`

---

## 7. Public Root Restyle Without Auth Change?

**Yes** — static `index.html` can link same CSS and mirror HTML structure. No Auth/Login changes.

---

## 8. index.php Guard

**Safe existing guard (FIX-D):**

- `erp_auth_context_session_user_id()` + `m360_access_mgmt_actor_is_admin()`
- Unauthenticated: Persian locked page → `owner-login.php`
- Authenticated: Persian admin hub

**No new auth system.**

---

## 9. Old Master ERP Public Exposure

**Must be removed** from any public view. Repo `index.php` already guarded; **home links must not route public users to index.php**.

---

## 10. Scope Gate Decision

**PASS — PROCEED**

| Change | Allowed |
|--------|---------|
| Restyle `index.html` with mirror CSS | Yes |
| Fix `mirror-layout.php` home links → `./` | Yes (public shell) |
| Fix customer-login/profile back links | Yes |
| `index.php` back link → `./` | Yes |
| Remove public nav «ورود مدیریتی» | Yes (staff-login retains form footer link) |

**Stop condition not triggered.**

---

P11.9-B-FIX-E scope gate: align public root with green MOGHAREH360 shell, fix Home links to `./`, keep protected `index.php` — no Auth architecture change.
