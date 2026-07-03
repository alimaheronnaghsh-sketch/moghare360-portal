# MOGHARE360 P11.9-B-FIX-F — Public UX + Cache Scope Report

**Phase:** P11.9-B-FIX-F  
**Mode:** UI / navigation / cache surface cleanup  
**Date:** 2026-06-26  
**Gate:** Must pass before implementation

---

## 1. Files Served by URL

| URL | Server file | Role |
|-----|-------------|------|
| `/moghare360/` | `DirectoryIndex` → `index.html` first | Public green MOGHAREH360 landing |
| `/moghare360/index.php` | `index.php` | Protected admin (locked if unauthenticated) |

`.htaccess`: `DirectoryIndex index.html index.php`

---

## 2. Public HTML / Cache Artifacts

| Artifact | Present | Notes |
|----------|---------|-------|
| `index.html` | Yes | Public root |
| `index.php` | Yes | Admin lock / hub |
| `.htaccess` | Yes | DirectoryIndex order explicit |
| `service-worker.js` | Yes | **Caches `./index.php`** — root cause of stale Master ERP |
| `manifest.webmanifest` | Yes | PWA manifest |
| Cache headers on `index.php` | Partial | `Cache-Control`, `Pragma` — missing `Expires` |
| Cache meta on `index.html` | No | **Add in FIX-F** |
| `mirror-layout.php` SW register | Yes | Registers SW on login pages |

---

## 3. Old Master ERP Entry in index.html / index.php

**Search in repo (FIX-F pre-scan):**

| Marker | index.html | index.php |
|--------|------------|-----------|
| Local Master ERP Entry | Absent | Absent |
| SQL Server SaaS | Absent | Absent |
| Legacy MySQL portal inactive | Absent | Absent |
| Master ERP | Absent | Absent |
| READY / CHECK / BLOCKED | Absent | Absent |
| Soft Run Home | Absent | Absent |
| Moghare Ready | Absent | Absent |
| Unit Access Console | Absent | Absent (hub links only when authenticated) |

**Conclusion:** Repo files are clean. Stale appearance is **deploy + cache**, not source text.

---

## 4. Remaining index.php / Old Route Links

| File | Finding | FIX-F action |
|------|---------|--------------|
| `includes/mirror-layout.php` | Home → `./` | OK |
| `customer-login.php` / `customer-profile.php` | `./` | OK (FIX-E) |
| `mirror-health.php` | `href="index.php"` | Out of allowed list — note in backlog |
| `includes/moghare360-v1-master-console-helper.php` | `index.php`, Master Console | Internal ERP — not public entry |
| `sql/customer-profile.php` | `index.php` | Legacy path under sql/ — not public entry |

No public login shell files link Home to `index.php`.

---

## 5. Why Old index.php Appears Until F5

| Cause | Evidence | FIX-F mitigation |
|-------|----------|------------------|
| **Service worker cache** | `service-worker.js` pre-caches `./index.php`; fetch handler serves cache-first for `index.php` | Unregister SW + clear caches from public pages; stop re-registering in `mirror-layout.php` |
| Browser HTTP cache | Static `index.html` without no-cache meta | Add meta + `.htaccess` headers |
| XAMPP stale copy | Owner deploy drift | Document copy list |
| Wrong link target | Was `index.php` for Home (FIX-E fixed) | Verified `./` |
| bfcache | Back/forward may show old DOM | `pageshow` reload + no-cache headers |
| DirectoryIndex | `index.html` before `index.php` for `/` | Already correct |

**Primary root cause:** `public_html/service-worker.js` caching outdated `index.php` response.

**Allowed fix path:** UI/cache/link cleanup only — no Auth changes. Modify `mirror-layout.php`, `index.html`, `index.php`, `.htaccess`; report SW; unregister without deleting SW file.

---

## 6. UI-Only Fix Viable?

**Yes** — Premium landing polish, typography, larger cards, no-cache strategy, SW unregister on public shell pages. No Auth/DB/permission changes.

---

## Stop Condition

**PASS** — Proceed with FIX-F implementation.
