# MOGHARE360 P11.8-C-FIX-A — Persian Label Consistency Scope Report

**Phase:** P11.8-C-FIX-A  
**Gate:** PASS — text-only verification + regression test; no behavior change  
**Date:** 2026-06-26  
**Trigger:** Mixed typo `تشخicصی` observed in P11.8-C chat deliverable (Latin `ic` inside Persian)

---

## 1. Where `تشخicصی` exists

| Location | Found? | Notes |
|----------|--------|-------|
| UI helper (`m360-route-operational-safety-helper.php`) | **No** | Badge is `تشخیصی / مدیریتی` (correct) |
| Route map page (`erp-route-map.php`) | **No** | Help text uses `تشخیصی / مدیریتی` (correct) |
| CSS (`m360-route-map-safety.css`) | **No** | No Persian label strings |
| P11.8-C tests | **No** | No Persian badge literals in test files |
| Docs/audit P11.8-C reports | **No** | `MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_REPORT.md` uses correct spelling |
| Prior chat final summary | **Yes (external)** | Typo in agent deliverable only — not committed to repo |

Repository scan (`grep تشخicصی`) returned **zero** matches under `moghare360-portal/`.

---

## 2. Would browser UI show the typo?

**No.** Runtime badge text comes from `M360_ROUTE_OPS_BADGE_FA[M360_ROUTE_OPS_CLASS_DIAGNOSTIC]` which is already `تشخیصی / مدیریتی`. XAMPP copy uses the same helper source.

---

## 3. Files needing text-only correction

| File | Action |
|------|--------|
| `public_html/includes/m360-route-operational-safety-helper.php` | **None** — already correct |
| `public_html/erp-route-map.php` | **None** — already correct |
| `docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_REPORT.md` | **None** — already correct |
| `tools/test-p11-8-c-persian-label-consistency.php` | **Add** — guardrail test |
| `docs/audit/MOGHARE360_P11_8_C_FIX_A_PERSIAN_LABEL_SCOPE_REPORT.md` | **Add** — this report |
| `docs/audit/MOGHARE360_P11_8_C_FIX_A_PERSIAN_LABEL_REPORT.md` | **Add** — final report |

No source correction required; prevention test added.

---

## 4. Route classification logic changes

**None needed.** Classifier constants, URL patterns, and link rules unchanged.

---

## 5. Registry / Auth / permission / DB / workflow changes

**None needed.** Label-only verification scope.

---

## Expected Persian safety labels (canonical)

| Key | Label |
|-----|-------|
| Operational badge | قابل ورود |
| Guided badge | راهنمای مسیر |
| Action badge | عملیات داخلی |
| API badge | API سیستم |
| Customer badge | مسیر مشتری |
| Diagnostic badge | **تشخیصی / مدیریتی** |
| Runtime hold badge | نیازمند بررسی عملیاتی |
| File exists | فایل موجود |
| File missing | فایل ناموجود |
| Link active | فعال |
| Link disabled | غیرفعال |
| Link technical only | فقط فنی |

**Gate result:** Proceed with regression test + audit docs only.
