# MOGHARE360 P11.8-B-A — Operational Shell Scope Report

**Phase:** P11.8-B-A  
**Gate:** PASS — UI-only operational shell + read-only responsibility strip + Staff Home runtime reclassification  
**Date:** 2026-06-26  
**Based on:** `MOGHARE360_P11_8_B_0_OPERATIONAL_NAVIGATION_DOCUMENT_RESPONSIBILITY_DISCOVERY_REPORT.md`

---

## 1. Existing navigation patterns reused

| Pattern | Reuse in P11.8-B-A |
|---------|-------------------|
| Staff Home (`erp-staff-home.php`) | Target for «میز کار من» link |
| Product Home (`erp-product-home.php`) | Target for «صفحه اصلی محصول» |
| Route Map (`erp-route-map.php`) | Optional link for owner/admin context |
| Ad-hoc back links on detail pages | Replaced/supplemented by shared shell back href to parent board |
| Soft-run release CSS | Coexists with new `m360-operational-shell.css` |
| Navigation registry metadata | Breadcrumb section titles (display only) |

**Not reused:** UI shell demo breadcrumb component (prototype only) — new lightweight breadcrumb in operational shell helper.

---

## 2. Existing DB fields reused (read-only display)

| Field | Source table | Display label |
|-------|--------------|---------------|
| Customer name | jobcard join / row | درخواست‌کننده |
| `created_by_user_id` | `erp_jobcards`, domain docs | ایجادکننده |
| `assigned_reception_user_id` | `erp_jobcards` | مسئول فعلی (reception) |
| `assigned_technician_user_id` | `erp_jobcards` | ارجاع‌شده / انجام‌دهنده |
| `final_technician_user_id` | `erp_jobcards` | انجام‌دهنده (work) |
| `qc_user_id` | `erp_jobcards` | بازبین / QC |
| `closed_by_user_id` | `erp_jobcards` | بستن پرونده |
| `updated_by_user_id` | `erp_jobcards` | آخرین تغییر |
| History `changed_by_user_id` | `erp_jobcard_change_history` | آخرین تغییر (fallback) |
| Domain `*_status` columns | jobcard + documents | وضعیت فعلی |
| `core_users.full_name` | join by user_id | Name resolution |

**No new columns. No writes.**

---

## 3. Pages receiving navigation shell only (boards/lists)

| Page | Section title |
|------|---------------|
| `erp-reception-jobcards.php` | JobCardهای پذیرش |
| `erp-intake-contracts.php` | قراردادهای پذیرش |
| `erp-technical-board.php` | برد عملیات فنی |
| `erp-work-execution-board.php` | برد اجرای کار |
| `erp-qc-board.php` | برد QC |
| `erp-estimate-board.php` | برد برآورد |
| `erp-final-invoice-board.php` | برد فاکتور نهایی |

Back link: `erp-staff-home.php` (no parent board).

---

## 4. Detail pages receiving navigation shell + responsibility strip

| Page | Domain | Record context |
|------|--------|----------------|
| `erp-reception-jobcard-detail.php` | reception | `jobcard_id` |
| `erp-technical-jobcard-detail.php` | technical | `jobcard_id` |
| `erp-work-execution-detail.php` | work | `jobcard_id` |
| `erp-estimate-detail.php` | estimate | `jobcard_id` + estimate row |
| `erp-final-invoice-detail.php` | invoice | `jobcard_id` + invoice row |
| `erp-qc-detail.php` | qc | `jobcard_id` |
| `erp-settlement-detail.php` | settlement | `jobcard_id` + settlement row |
| `erp-jobcard-timeline.php` | timeline | when `jobcard_id > 0` |

---

## 5. Excluded pages (legacy / test / not product-ready)

| Page | Reason |
|------|--------|
| `erp-jobcard-part-use.php` | Runtime-not-ready (Mission 24 legacy); Staff Home reclassified only — **not fixed** |
| `erp-payment-tracking.php` | Not loadable in browser; Staff Home reclassified only — **not fixed** |
| `*-action.php` POST endpoints | Action handlers — no shell |
| `customer-delivery-*.php` | Customer token flow |
| Legacy Mission detail pages | Out of m360 scope |
| HR self-service backlog cards | P15 backlog |

---

## 6. Staff Home routes reclassified as runtime_hold (not clickable)

| Route | New status badge | Description |
|-------|------------------|-------------|
| `erp-jobcard-part-use.php` | نیازمند بازبینی عملیاتی | All workbench + bridge + coordination refs |
| `erp-payment-tracking.php` | نیازمند بررسی عملیاتی | FINANCE today, OWNER/SM bridge/coordination |

Empty clean boards (technical, QC, reception lists) **remain clickable** with موجود/مرجع.

---

## 7. Why no DB / permission / workflow / Auth change

- Shell renders **existing HTML navigation** and **reads existing columns**.
- User names resolved via existing `core_users` SELECT.
- Next action derived from **existing** `m360_*_allowed_actions()` outputs — no new actions.
- Staff Home reclassification is **card metadata only** — same destination files, disabled links.
- Page guards and POST handlers untouched.

---

## 8. Stop conditions

| Item | Action |
|------|--------|
| DB schema / SQL migration | **Do not implement** — backlog |
| Permission / role seed | **Do not implement** |
| Auth / Login change | **Do not implement** |
| Workflow state machine change | **Do not implement** |
| P1–P7 action handler change | **Do not implement** |
| Fix part-use or payment-tracking pages | **Do not implement** — deferred |
| Impersonation / manager override / HR | **Do not implement** — backlog |
| P12 scope | **Do not implement** |

**Gate result:** Proceed with UI-only shell, responsibility strip, Staff Home runtime_hold reclassification, tests, and audit report.
