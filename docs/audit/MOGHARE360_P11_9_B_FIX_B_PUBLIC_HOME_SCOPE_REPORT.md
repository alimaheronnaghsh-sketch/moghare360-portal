# MOGHARE360 P11.9-B-FIX-B — Public Home Scope Report

**Phase:** P11.9-B-FIX-B  
**Mode:** UI / text / public access surface cleanup  
**Date:** 2026-06-26  
**Gate:** Must pass before `public_html/index.php` modification

---

## 1. Current Public Home Page Path

**File:** `public_html/index.php`

**Current behavior:** Uses `moghare360-v1-master-console-helper.php` to render a V1 **Master ERP entry** page with operational navigation cards, READY/CHECK badges, and links to internal consoles.

---

## 2. Current Issues

| Issue | Evidence in current `index.php` |
|-------|----------------------------------|
| Public page exposes operational/admin links | Nav links to Master Console, Unit Access Console, Soft Run Home, Moghare Ready |
| Mixes product intro and internal system entry | Hero text: «ورود نرم‌افزار مادر (Master ERP)» + operational cards |
| Management access visible publicly | `owner-login.php` link «ورود مدیریتی»; Production Signoff; Fix Register |
| Not fully buyer-facing Persian | English labels: Master Console, Soft Run Home, Production Signoff, Fix Register, READY, CHECK |
| Weak product/customer message | No buyer value proposition; internal SaaS/ERP entry framing |

**Additional exposure via helper footer:** `v1mc_render_foot()` adds Master Console, Unit Access Console, Moghare Ready, Production Signoff links — will be removed by making `index.php` self-contained.

---

## 3. What Will Be Changed

| Change | Detail |
|--------|--------|
| Replace public home content | Professional Persian buyer-facing introduction (specified copy) |
| Remove/hide public admin links | No Master Console, Unit Access, Product Home, signoff, fix register, owner/management login, route map, soft run, etc. on `index.php` |
| Presentation-only public home | Contact via WhatsApp; no internal operational URL buttons |
| Self-contained `index.php` | Stop using `v1mc_render_head` / `v1mc_render_foot` on public home to avoid operational footer |
| Optional minimal inline CSS | RTL layout in `index.php` only if needed |
| Audit + tests | Scope report, implementation report, guardrail tests |

**Direct login URLs remain reachable** — only public home exposure removed.

**Auth/Login untouched** — no changes to login pages or auth helpers.

---

## 4. What Will NOT Be Changed

| Item | Status |
|------|--------|
| `staff-login.php` | Not modified |
| `owner-login.php` | Not modified |
| `erp-product-home.php` | Not modified |
| `erp-v1-master-console.php` | Not modified |
| `erp-access-management.php` | Not modified |
| Role / permission / dept / position model | Not modified |
| Database / SQL migrations | Not modified |
| Workflow / action handlers | Not modified |
| OTP config / private files | Not modified |
| P12 scope | Not introduced |
| Dry run / users / JobCards | Not executed or created |

---

## 5. Confirmations

| Statement | Answer |
|-----------|--------|
| This is **not** a role taxonomy fix | **Confirmed** — public home text/links only |
| This is **not** an admin dashboard redesign | **Confirmed** — internal pages unchanged |
| This is **not** Software Admin vs Company Owner architecture change | **Confirmed** — backlog item |
| Admin first-page centralization | Remains **backlog/discovery** |
| Product Home Persian localization | Remains **backlog** |

---

## 6. Scope Gate Decision

**PASS — PROCEED**

Changes are limited to `public_html/index.php` (and optional inline styling within that file), plus docs/tests. No Auth, role, permission, DB, workflow, route registry, or admin architecture changes required.

**Stop condition not triggered.**

---

## 7. Expected Deliverables

| File | Action |
|------|--------|
| `public_html/index.php` | Modify |
| `docs/audit/MOGHARE360_P11_9_B_FIX_B_PUBLIC_HOME_SCOPE_REPORT.md` | This report |
| `docs/audit/MOGHARE360_P11_9_B_FIX_B_PUBLIC_HOME_REPORT.md` | After implementation |
| `tools/test-p11-9-b-fix-b-public-home.php` | Create |
| `tools/test-p11-9-b-fix-b-scope-security.php` | Create |

---

P11.9-B-FIX-B scope gate: public home Persian buyer-facing rewrite and internal link removal on `index.php` only — no Auth, permissions, roles, database, workflow, or architecture changes.
