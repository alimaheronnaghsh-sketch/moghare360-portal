# MOGHARE360 P11.9-B-FIX-D — Luxury Entry + Protected Admin Index Scope Report

**Phase:** P11.9-B-FIX-D  
**Mode:** UI / route alignment  
**Date:** 2026-06-26  
**Gate:** Must pass before implementation

---

## 1. Files Served by URL

| URL | After FIX-D | File |
|-----|-------------|------|
| `http://localhost:8080/moghare360/` | Apache `DirectoryIndex` → **first match** | `public_html/index.html` |
| `http://localhost:8080/moghare360/index.php` | Explicit PHP document | `public_html/index.php` |

---

## 2. Public HTML Landing Files

| File | Present | Role |
|------|---------|------|
| `index.html` | **Will be created** | Public luxury Persian landing |
| `index.php` | **Yes** | Will become **protected** admin/master entry |
| `.htaccess` | **Yes** | Currently `DirectoryIndex index.php` only — **will update** to `index.html index.php` |
| Other landing | **No** | — |

---

## 3. Why `/` and `/index.php` Show Different Content

| Factor | Explanation |
|--------|-------------|
| Current `.htaccess` | `DirectoryIndex index.php` — both URLs can hit same file when only `index.php` exists |
| Observed mismatch | XAMPP host has **stale** `index.php` (pre-FIX-B/C Master ERP Entry) while `/` may serve a newer copy or cached variant |
| FIX-C state | Repo `index.php` was public landing — **wrong model** for admin protection requirement |
| FIX-D fix | **Split files**: `index.html` = public; `index.php` = protected admin |

---

## 4. Persian Public Entry (Current)

**File:** `public_html/index.php` (P11.9-B-FIX-C unified public entry)

---

## 5. Old Master ERP Entry

**Was:** `index.php` before FIX-B (v1mc helper, Master Console nav, READY/CHECK badges)  
**May persist on XAMPP** if not redeployed.

---

## 6. Safest Route Model (Required)

| Route | Audience | Content |
|-------|----------|---------|
| `/moghare360/` | Public | Luxury Persian landing — خانه / پرسنل / مشتری |
| `/moghare360/index.php` | Owner/admin only | Persian protected admin hub after session check |

**Direct internal URLs unchanged** — not deleted.

---

## 7. Login Guard for index.php

**Existing safe helpers (no new auth system):**

| Helper | Location | Behavior |
|--------|----------|----------|
| `erp_auth_context_start()` | `includes/erp-auth-context.php` (via `erp-customer-core-helper.php`) | Session start |
| `erp_auth_context_session_user_id()` | Same | **Session-only** user ID (no test fallback) |
| `m360_access_mgmt_actor_is_admin()` | `m360-access-management-helper.php` | Owner / system_admin check |

**Login route:** `owner-login.php` (unchanged)

**Guard approach:** Use **session user ID only** (not `erp_auth_current_user_id()` which has local test fallback `10001`). Unauthenticated → Persian locked page with link to `owner-login.php`. Authenticated admin → Persian hub.

**Stop condition:** **Not triggered** — existing helpers sufficient.

---

## 8. Customer Route

| File | Assessment |
|------|------------|
| `customer-request.php` | **Safe public entry** — online service request (P1) |

**Decision:** Public «مشتری» card links to `customer-request.php`. If missing on host, static HTML shows placeholder text (deploy responsibility).

---

## 9. Public Page Must Not Expose

Confirmed hidden from `index.html`:

- Owner/admin login, Master Console, Unit Access Console, Product Home, signoff, fix register, Access Management, route map, soft run, internal dashboards, READY/CHECK/BLOCKED, legacy SaaS banners.

Admin links only on **authenticated** `index.php` hub.

---

## 10. Scope Gate Decision

**PASS — PROCEED**

| Allowed | Forbidden |
|---------|-----------|
| `index.html`, `index.php`, `.htaccess` DirectoryIndex | Auth/Login file changes |
| Self-contained CSS | DB, SQL, roles, permissions |
| Docs + tests | New customer portal, P12 |

---

## 11. Expected Deliverables

- `public_html/index.html` — luxury public landing  
- `public_html/index.php` — protected admin index  
- `public_html/.htaccess` — `DirectoryIndex index.html index.php`  
- Scope + implementation reports  
- Three test files  

---

P11.9-B-FIX-D scope gate: split public luxury `index.html` from protected Persian admin `index.php` using existing session/admin helpers — no Auth architecture change.
