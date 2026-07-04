# MOGHARE360 P11.9-C-1 — Reception Action Stabilization Scope Report

**Phase:** P11.9-C-1  
**Mode:** Controlled reception online request action fix  
**Date:** 2026-07-04  
**Gate:** Must pass before implementation

---

## 1. Current Online Request List Page

| Item | Value |
|------|-------|
| File | `public_html/erp-reception-online-requests.php` |
| Method | GET only |
| Auth | `m360_reception_require_staff()` |
| Filter | `?status=` — ALL, NEW, PENDING, UNDER_REVIEW, ACCEPTED, CONVERTED_TO_JOBCARD, REJECTED |

---

## 2. Current Online Request Detail Page

| Item | Value |
|------|-------|
| File | `public_html/erp-reception-online-request-detail.php` |
| Method | GET read-only |
| Actions | Four POST forms → `erp-reception-online-request-accept.php` |

---

## 3. Current Action Routes

| Action | Route | Exists |
|--------|-------|--------|
| under_review, accept, reject, convert | `erp-reception-online-request-accept.php` | Yes |
| reject (separate) | `erp-reception-online-request-reject.php` | **No** |
| review (separate) | `erp-reception-online-request-review.php` | **No** |
| convert (separate) | `erp-reception-online-request-convert.php` | **No** |

All actions consolidated in `accept.php` (POST only).

---

## 4. Current CSRF Helper Behavior

- Loaded via `erp-customer-core-helper.php` → `includes/erp-csrf.php`
- `erp_csrf_create_token($form_key)` **overwrites** session token per key on each call
- `erp_csrf_input()` calls `erp_csrf_create_token()` each time
- Purpose constant: `M360_RECEPTION_CSRF_PURPOSE` = `online_request_reception`
- Invalid token: `erp_csrf_require_valid()` → plain text `ERP security validation failed.`

---

## 5. Multiple CSRF Inputs on Detail Page

**Yes — confirmed bug (pre-fix).** Detail page called `erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE)` **four times** (one per form). Only the last rendered token matched session.

---

## 6. POST/GET Behavior of Action Routes

| Route | GET | POST |
|-------|-----|------|
| `accept.php` | Redirect to list | Process action + redirect to detail |

No unsafe GET state changes on action handler.

---

## 7. Status Values Used by Filters

From `m360-online-request-helper.php`:

- `NEW`, `PENDING`, `UNDER_REVIEW`, `ACCEPTED`, `CONVERTED_TO_JOBCARD`, `REJECTED`
- Filter `NEW` SQL includes `NEW` + `PENDING` (combined «جدید/در انتظار» bucket in list query)

---

## 8. Why Filters Appear Ineffective

1. Test/demo data often homogeneous (all `NEW` or `MIRROR`)
2. `NEW` filter overlaps `PENDING` in SQL while UI shows separate pills
3. No visible active filter label or counts (pre-fix)
4. Empty states did not name active filter

---

## 9. Convert-to-JobCard Path

`erp-reception-online-request-accept.php` → `m360_reception_convert_to_jobcard()` in `m360-reception-helper.php` → customer/vehicle/relation ensure → `moghare360_jobcard_v2_write()`

---

## 10. OTP / Prerequisite Gate Before Conversion

`m360_online_req_payload_otp_verified()` must pass. Failure message: «درخواست بدون تأیید OTP قابل تبدیل نیست.» Also vehicle/customer/plate validation errors.

**No OTP bypass in C-1** — controlled Persian gate page only.

---

## 11. Existing UI Pattern to Reuse

- `moghare360-soft-run-release.css` + `w1c-wrap` / `w1c-card` / `w1c-banner` (P1 pages)
- Flash messages on detail (`?msg=&ok=`)
- POST redirect pattern from existing accept handler

---

## 12. Files to Modify

| File | Change |
|------|--------|
| `public_html/erp-reception-online-request-detail.php` | Single CSRF; gate panel |
| `public_html/erp-reception-online-request-accept.php` | Persian CSRF/gate handling |
| `public_html/erp-reception-online-requests.php` | Filter UX |
| `public_html/includes/m360-reception-helper.php` | CSRF/gate/filter helpers |
| `docs/audit/*` | Scope + report |
| `tools/test-p11-9-c-1-*.php` | Tests |

---

## 13. Files Must NOT Modify

- `staff-auth.php`, `access-control.php`, Auth core
- `includes/erp-csrf.php` (shared CSRF helper)
- OTP helpers / bypass
- DB migrations, SQL
- Private files
- Workflow architecture beyond accept redirect messages

---

## Stop Condition

**PASS** — UI/workflow surface fix only. No Auth/DB/permission/OTP architecture change required.
