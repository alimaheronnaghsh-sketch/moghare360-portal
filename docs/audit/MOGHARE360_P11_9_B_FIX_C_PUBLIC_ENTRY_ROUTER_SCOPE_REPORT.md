# MOGHARE360 P11.9-B-FIX-C — Public Entry Router Scope Report

**Phase:** P11.9-B-FIX-C  
**Mode:** UI / navigation surface alignment  
**Date:** 2026-06-26  
**Gate:** Must pass before `index.php` modification

---

## 1. Which File Is Served?

| URL | Expected server behavior | File |
|-----|-------------------------|------|
| `http://localhost:8080/moghare360/` | Apache `DirectoryIndex` → `index.php` | `public_html/index.php` |
| `http://localhost:8080/moghare360/index.php` | Direct PHP document | `public_html/index.php` |

**Repository configuration:** `.htaccess` sets `DirectoryIndex index.php`. Both URLs **must** resolve to the same file when deployment is in sync.

---

## 2. Public HTML Landing Files

| File | Present in repo? | Role |
|------|------------------|------|
| `public_html/index.php` | **Yes** | Primary public entry (P11.9-B-FIX-B Persian page) |
| `public_html/index.html` | **No** | Not present — cannot override `/` |
| `public_html/.htaccess` | **Yes** | `DirectoryIndex index.php`; no rewrite splitting `/` vs `/index.php` |
| Other landing file | **No** | No alternate root document |

---

## 3. Why Do `/` and `/index.php` Show Different Pages?

**Root cause (operator observation): deployment drift on XAMPP host.**

| Observation | Explanation |
|-------------|-------------|
| `/` shows Persian buyer page | XAMPP copy of `index.php` was updated (or partial sync) after P11.9-B-FIX-B |
| `/index.php` shows old Master ERP Entry | Stale `index.php` on disk, browser cache, or duplicate deploy path serving an older file |

**Repository state:** `index.php` already contains P11.9-B-FIX-B Persian content only — **no** Master ERP Entry helper include. Old Master ERP Entry used `moghare360-v1-master-console-helper.php` (removed in FIX-B).

**Not caused by:** separate `index.html`, `.htaccess` split routing, or two different index files in repo.

**FIX-C action:** Ship unified `index.php` with explicit entry router (خانه / پرسنل / مشتری) and operator instruction to copy single file to XAMPP so both URLs match.

---

## 4. Persian Buyer-Facing Home

**File:** `public_html/index.php` (current P11.9-B-FIX-B content)

---

## 5. Old Master ERP Entry

**Was:** `public_html/index.php` before P11.9-B-FIX-B — used `v1mc_render_head`, Master Console nav, READY/CHECK badges.

**Now:** Removed from repo `index.php`. May still exist only on unstale XAMPP copy at `/index.php` path if not redeployed.

**Internal pages unchanged:** `erp-v1-master-console.php` still exists at direct URL — not linked from public landing.

---

## 6. Staff Entry Route

**Route:** `staff-login.php`  
**Label:** ورود پرسنل  
**Status:** Existing — use for public «پرسنل» choice.

---

## 7. Customer Entry Route

**Inspected public customer pages:**

| File | Assessment |
|------|------------|
| `customer-request.php` | **Safe public entry** — online service request form (P1 intake); linked in pre-FIX-B index as «مشتری» |
| `customer-login.php` | Legacy MySQL portal pattern (`config.php`) — not primary ERP V1 entry |
| Token/sign pages | Require workflow context — not public landing targets |

**Decision:** Link «مشتری» to **`customer-request.php`** (ثبت درخواست خدمات).

If deployment lacks this file, placeholder text applies — in repo file exists.

---

## 8. Admin / Management Visibility

**Confirmed:** Master Console, Unit Access, Product Home, signoff, fix register, owner/management login, Access Management, operational badges, and internal ERP language **must not** appear on public landing.

Direct internal URLs remain reachable — not deleted.

---

## 9. Direct Internal URLs

**Confirmed unchanged:** `erp-v1-master-console.php`, `owner-login.php`, `erp-access-management.php`, etc. — no deletion or Auth changes.

---

## 10. Scope Gate Decision

**PASS — PROCEED**

- Single-file `index.php` update with خانه / پرسنل / مشتری navigation
- Staff → `staff-login.php`
- Customer → `customer-request.php`
- No Auth, permission, role, DB, workflow, or new customer module required

**Stop condition not triggered.**

---

## 11. Expected Deliverables

| File | Action |
|------|--------|
| `public_html/index.php` | Unified public entry router |
| `docs/audit/MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_SCOPE_REPORT.md` | This report |
| `docs/audit/MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_REPORT.md` | Implementation report |
| `tools/test-p11-9-b-fix-c-public-entry-router.php` | Content tests |
| `tools/test-p11-9-b-fix-c-scope-security.php` | Scope tests |

---

P11.9-B-FIX-C scope gate: align `/` and `/index.php` to one Persian public entry with Home, Staff, and Customer choices — no Auth, permissions, roles, database, or workflow changes.
