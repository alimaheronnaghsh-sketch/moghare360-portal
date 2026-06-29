# MOGHARE360 P11.8-A-FIX-A — Staff Home Route Safety UI Scope Report

**Phase:** P11.8-A-FIX-A  
**Goal:** Report existing Staff Home card classifications before UI-only route-safety polish.  
**Verdict:** UI metadata/rendering fix only — no new features, permissions, or backend changes.

---

## 1. Clickable board/list/dashboard routes

These `card_type` values are clickable when the target file exists and is not an action endpoint:

| card_type | Badge | Button | Roles / groups |
|-----------|-------|--------|----------------|
| `nav` | موجود | ورود به صفحه | All operational roles — today/followup/reports boards and lists |
| `ref` | مرجع | مشاهده تابلو | OWNER/SYSTEM_ADMIN — manager reference bridge |
| `ref_coord` | مرجع هماهنگی | مشاهده تابلو | SERVICE_MANAGER — coordination reference bridge |
| `diag` | ابزار تشخیص | مشاهده گزارش (after fix) | OWNER/SYSTEM_ADMIN — dashboards, route map, permission preview |

Examples: `erp-technical-board.php`, `erp-reception-online-requests.php`, `erp-management-dashboard.php`, `erp-jobcard-part-readonly-list.php`, `customer-request.php`.

---

## 2. Detail pages requiring JobCard or record context

`card_type = info` — not directly clickable; opened from a board/list or JobCard record.

| Label (FA) | File | Roles |
|------------|------|-------|
| جزئیات درخواست آنلاین | erp-reception-online-request-detail.php | RECEPTION |
| جزئیات JobCard پذیرش | erp-reception-jobcard-detail.php | RECEPTION |
| جزئیات قرارداد پذیرش | erp-intake-contract-detail.php | RECEPTION |
| جزئیات فنی JobCard | erp-technical-jobcard-detail.php | SERVICE_MANAGER, TECHNICIAN |
| جزئیات اجرای کار | erp-work-execution-detail.php | SERVICE_MANAGER, TECHNICIAN |
| جزئیات عملیات سرویس | erp-service-operation-detail.php | TECHNICIAN |
| جزئیات برآورد | erp-estimate-detail.php | FINANCE |
| جزئیات فاکتور نهایی | erp-final-invoice-detail.php | FINANCE |
| جزئیات تسویه | erp-settlement-detail.php | FINANCE, OWNER bridge |
| جزئیات QC / چک‌لیست | erp-qc-detail.php | QC |
| تایم‌لاین JobCard | erp-jobcard-timeline.php | OWNER bridge, SM bridge, SM reports (reclassified) |

**Issue found:** `info` and `note` cards used green badge «موجود» via `m360_staff_home_route_status()` — misleading.

---

## 3. POST / action endpoints

`card_type = note` — never clickable; executed inside a selected record.

| Label (FA) | File | Roles |
|------------|------|-------|
| پذیرش/رد درخواست آنلاین | erp-reception-online-request-accept.php | RECEPTION |
| عملیات JobCard پذیرش | erp-reception-jobcard-action.php | RECEPTION |
| عملیات فنی JobCard | erp-technical-jobcard-action.php | SERVICE_MANAGER |
| عملیات اجرای کار | erp-work-execution-action.php | SERVICE_MANAGER |
| عملیات فنی/اجرا | erp-technical-jobcard-action.php | TECHNICIAN |
| عملیات فاکتور/تسویه | erp-final-invoice-action.php | FINANCE |
| عملیات QC | erp-qc-action.php | QC |

**Issue found:** `note` returned status «موجود» (green) — must be «عملیات داخلی».

---

## 4. Cards currently showing misleading «موجود» status

Before fix, `m360_staff_home_route_status()` returned «موجود» for:

- All `card_type = note` action cards (every operational role)
- All `card_type = info` detail cards (when file exists — status fell through to «موجود» only for `note`; `info` also needed explicit guided status)

Additionally:

- SERVICE_MANAGER **reports** group listed `erp-jobcard-timeline.php` as default `nav`, making it clickable with «ورود به صفحه» and green «موجود» — incorrect.

---

## 5. Is `erp-jobcard-timeline.php` safe to open directly?

**Inspection result:** Partially accessible but **not a standalone board/list**.

- Requires `jobcard_id` GET parameter to show timeline data.
- Without ID, page shows an empty form: «شناسه JobCard را وارد کنید.»
- Read-only report view for a **single** JobCard — not a fleet/list dashboard.

**Decision:** **Guided/disabled** on Staff Home (`card_type = info`). Label: «از مسیر پرونده JobCard باز می‌شود». Not a direct operational entry card.

---

## 6. Required rendering modes

| Mode | card_type | Clickable | Badge | Button / note |
|------|-----------|-----------|-------|---------------|
| Clickable board/list | nav, ref, ref_coord, diag | Yes | موجود / مرجع / مرجع هماهنگی / ابزار تشخیص | مشاهده تابلو / ورود به صفحه / مشاهده گزارش |
| Guided route note | info | No | راهنمای مسیر | disabled «راهنمای مسیر» + description |
| Disabled action | note | No | عملیات داخلی | description only (no ورود به صفحه) |
| Disabled backlog | backlog | No | نیازمند تکمیل | backlog message |

---

## 7. No new feature required

Confirmed:

- No new permissions, DB schema, workflow, Auth/Login, or action-handler changes.
- Only adjust helper metadata, status labels, CSS, and tests.
- SERVICE_MANAGER timeline reclassification from `nav` → `info` is metadata-only (same page, safer UX).

**Stop condition respected:** All items are UI consistency fixes within allowed scope.

---

## Implementation plan (P11.8-A-FIX-A)

1. Fix `m360_staff_home_route_status()` — `info` → «راهنمای مسیر», `note` → «عملیات داخلی».
2. Fix `m360_staff_home_route_status_class()` — guided/action CSS classes (not green `present`).
3. Add `is-info` styling; diag button → «مشاهده گزارش».
4. Reclassify SERVICE_MANAGER reports timeline to `info`.
5. Align INFO/NOTE description constants with P11.8-A-FIX-A spec.
6. Add `tools/test-p11-8-a-fix-a-route-safety-ui.php` and final audit report.
