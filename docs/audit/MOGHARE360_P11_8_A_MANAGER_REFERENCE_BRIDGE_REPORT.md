# MOGHARE360 P11.8-A — Manager Reference Navigation Bridge Report

**Phase:** P11.8-A  
**Date:** 2026-06-26  
**Status:** Complete

---

## 1. Scope Gate Result

Scope gate: `docs/audit/MOGHARE360_P11_8_A_MANAGER_REFERENCE_BRIDGE_SCOPE_REPORT.md`

**Approved and implemented.** Navigation bridge only — no forbidden scope touched.

---

## 2. Included P11.8-0 Findings

| Finding | Implementation |
|---------|----------------|
| Fragmented manager reference | Staff Home group «مرجع عملیاتی One-Day Run» (OWNER/SYSTEM_ADMIN) |
| Missing One-Day Run boards on product home | Online requests, contracts, payment, parts, delivery in bridge |
| SERVICE_MANAGER cross-unit gap | Group «مرجع هماهنگی سالن» |
| Route registry reuse | Existing board/list URLs only |
| Permission preview diagnostic | In admin bridge as `diag` card |
| P8 oversight | Management dashboard + owner control in bridge |
| Timeline diagnosis | Info/guided card (JobCard ID required) |
| Impersonation forbidden | Disabled backlog cards |

---

## 3. Excluded Findings and Why

| Excluded | Reason |
|----------|--------|
| Impersonation / act-as-staff | Forbidden — backlog only |
| Manager override engine | Forbidden — backlog only |
| Permission expansion | Forbidden — backlog only |
| HR self-service | P15 — existing backlog |
| Role-based page guards | Out of P11.8-A scope |
| Clickable detail/settlement pages | JobCard/record ID required — info only |
| New PHP pages | Not in scope |

---

## 4. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | Bridge groups, ref/diag card types, backlog updates |
| `public_html/assets/css/m360-staff-home.css` | Bridge/reference/diagnostic styling |
| `docs/audit/MOGHARE360_P11_8_A_MANAGER_REFERENCE_BRIDGE_SCOPE_REPORT.md` | Scope gate |
| `docs/audit/MOGHARE360_P11_8_A_MANAGER_REFERENCE_BRIDGE_REPORT.md` | This report |
| `tools/test-p11-8-a-manager-reference-bridge.php` | 14 tests |
| `tools/test-p11-8-a-no-impersonation-scope.php` | 9 tests |
| `tools/test-p11-8-a-route-safety.php` | 91 tests |
| `tools/test-p11-7-role-workbench-matrix.php` | Group count 7 |
| `tools/test-p11-7-1-staff-home-ux-polish.php` | Bridge group assertion |
| `tools/test-p11-7-1-no-new-scope.php` | Backlog text assertion |

`erp-staff-home.php` unchanged (render via helper).

---

## 5. OWNER / SYSTEM_ADMIN Bridge

**Group:** `مرجع عملیاتی One-Day Run` (`M360_STAFF_HOME_GROUP_MANAGER_REF`)

**Clickable reference/diagnostic cards (21+):**

- Reception: online requests, jobcards, contracts
- Technical/execution: technical board, work execution board
- Parts: stock board, part reserve, part use, purchase create
- Finance: payment tracking, estimate board, final invoice board
- QC/delivery: QC board, delivery control
- Diagnostics: permission preview, route map, owner control, management dashboard

**Guided info (not clickable):**

- JobCard timeline
- Settlement detail

**Backlog in bridge:**

- Purchase request list (file missing)

**Button label:** «مشاهده تابلو» for ref/diag cards

---

## 6. SERVICE_MANAGER Coordination Bridge

**Group:** `مرجع هماهنگی سالن` (`M360_STAFF_HOME_GROUP_COORDINATION_REF`)

**Reference cards (`ref_coord`):**

- Reception jobcards, intake contracts
- Part reserve, part use
- Payment tracking, estimate board, final invoice board

**Guided info:**

- JobCard timeline

**Excluded:** access management, permission preview, owner dashboards

Primary workshop cards remain in «کار امروز» unchanged.

---

## 7. Route Safety Handling

| Rule | Enforcement |
|------|-------------|
| Board/list only clickable | `card_type` ref/ref_coord/diag/nav + `m360_staff_home_is_action_endpoint()` exclusion |
| No detail pages clickable | timeline/settlement = `info` |
| No action endpoints | Never in bridge items |
| Missing files | Backlog card |
| Visible filenames | Hidden — `data-route` only (P11.7.1-A) |
| Status badges | مرجع / مرجع هماهنگی / ابزار تشخیص / نیازمند تکمیل |

---

## 8. Backlog Cards (OWNER/SYSTEM_ADMIN)

1. انجام کار به جای پرسنل / Impersonation — غیرمجاز در V1  
2. موتور Override مدیریتی — نیازمند طراحی امنیتی مستقل  
3. افزایش Permission نقش‌ها — نیازمند فاز مستقل دسترسی  
4. HR Self-Service — P15  

Plus existing P15 HR item backlog for all roles.

---

## 9. Tests Passed

```
php -l erp-staff-home.php + helper                          OK
test-p11-8-a-manager-reference-bridge.php                   14/14
test-p11-8-a-no-impersonation-scope.php                      9/9
test-p11-8-a-route-safety.php                               91/91
test-p11-7-1-staff-home-ux-polish.php                       35/35
test-p11-7-1-no-new-scope.php                               14/14
test-p11-7-role-workbench-matrix.php                        31/31
test-p11-7-staff-home-persian-encoding.php                  16/16
test-p11-7-scope-security.php                               10/10
test-p11-7-broken-link-fixes.php                             9/9
test-p11-4-4-staff-home-authorization.php                   52/52
test-v1-production-signoff.php                              23/23
```

---

## 10. Browser Validation

Files copied to `C:\xampp\htdocs\moghare360\`. Post-login validation expected:

- OWNER/SYSTEM_ADMIN: «مرجع عملیاتی One-Day Run» with board links and diagnostic tools
- SERVICE_MANAGER: «مرجع هماهنگی سالن» without admin tools
- Other roles: no bridge groups
- P11.7.1-A UX rules preserved

---

## 11. Security Confirmation

- No Auth/Login architecture change
- No password/session logic change
- No permission/role seed change
- No DB schema change
- No workflow state change
- No impersonation
- No act-as-staff
- No manager override engine
- No HR self-service build
- No P12 scope
- No secrets committed

---

## 12. Remaining Gaps

- Page guards remain session-level — navigation does not tighten authorization
- Settlement/finance actions possible if manager navigates from board to detail manually (existing behavior)
- Purchase request list page still missing
- Unified “manager reference” audit event label not added (uses existing `changed_by_user_id`)
- Role-based guard hardening deferred to post-V1

---

## 13. Recommended Next Step

**P11.8-B (optional):** Role-based page guard review + manager-reference audit taxonomy — only if owner requires tighter enforcement beyond navigation.

**P15:** HR self-service backlog items.

---

P11.8-A adds a controlled Manager Reference Navigation Bridge by reusing existing safe board/list routes for owner, system admin, and service manager coordination without impersonation, permission changes, database schema changes, workflow changes, Auth/Login changes, HR self-service, or P12 scope.
